<?php
declare(strict_types=1);

class AppEngine 
{
    /**
     * Read CSV or XLSX files as rows using a lightweight parser.
     */
    public static function streamSpreadsheet(string $path): Generator {
        if (!file_exists($path) || !is_readable($path)) return;

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            $h = fopen($path, 'r');
            if ($h === false) return;
            while (($row = fgetcsv($h, 0, ',')) !== false) { yield $row; }
            fclose($h);
            return;
        }

        if ($extension !== 'xlsx') return;
        if (!class_exists('ZipArchive')) return;

        try {
            $zip = new ZipArchive();
            if ($zip->open($path) !== true) return;

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
                try {
                    $xml = new SimpleXMLElement($sharedStringsXml);
                    foreach ($xml->si as $si) {
                        $parts = [];
                        foreach ($si->children() as $child) {
                            $name = $child->getName();
                            if ($name === 't') {
                                $parts[] = (string)$child;
                            } elseif ($name === 'r') {
                                foreach ($child->children() as $grandChild) {
                                    if ($grandChild->getName() === 't') {
                                        $parts[] = (string)$grandChild;
                                    }
                                }
                            }
                        }
                        $sharedStrings[] = implode('', $parts);
                    }
                } catch (Exception $e) {}
            }

            $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetData === false) {
                $zip->close();
                return;
            }

            try {
                $xml = new SimpleXMLElement($sheetData);
                foreach ($xml->sheetData->row as $rowNode) {
                    $row = [];
                    foreach ($rowNode->c as $cell) {
                        $row[] = self::decodeCellValue($cell, $sharedStrings);
                    }
                    yield $row;
                }
            } catch (Exception $e) {}

            $zip->close();
        } catch (Exception $e) {}
    }

    private static function decodeCellValue(SimpleXMLElement $cell, array $sharedStrings): string {
        try {
            $type = isset($cell['t']) ? (string)$cell['t'] : '';

            if ($type === 's') {
                $index = isset($cell->v) ? (int)((string)$cell->v) : -1;
                return ($index >= 0 && isset($sharedStrings[$index])) ? (string)$sharedStrings[$index] : '';
            }

            if ($type === 'inlineStr' && isset($cell->is)) {
                $parts = [];
                foreach ($cell->is->children() as $child) {
                    $name = $child->getName();
                    if ($name === 't') {
                        $parts[] = (string)$child;
                    } elseif ($name === 'r') {
                        foreach ($child->children() as $grandChild) {
                            if ($grandChild->getName() === 't') {
                                $parts[] = (string)$grandChild;
                            }
                        }
                    }
                }
                return implode('', $parts);
            }

            return isset($cell->v) ? (string)$cell->v : '';
        } catch (Exception $e) {
            return '';
        }
    }

    public static function getPreview(string $path, int $limit = 6): array {
        $rows = [];
        foreach (self::streamSpreadsheet($path) as $row) {
            $rows[] = $row;
            if (count($rows) >= $limit) break;
        }
        return $rows;
    }

    public static function cleanStr(string $in): string {
        return trim(mb_strtolower(preg_replace('/\s+/', ' ', $in), 'UTF-8'));
    }

    public static function smartNorm(string $in): string {
        return trim(mb_strtolower(preg_replace('/[\-\.\/\s]/', '', $in), 'UTF-8'));
    }

    public static function normIC(string $ic): string {
        return preg_replace('/[^0-9]/', '', $ic);
    }

    public static function compare(string $fA, string $fB, array $mA, array $mB, string $mode): array {
        $dataA = []; $res = [];
        $sum = ['total_a' => 0, 'total_b' => 0, 'matched' => 0, 'modified' => 0, 'missing_a' => 0, 'missing_b' => 0, 'invalid' => 0];

        $cA = 0;
        foreach (self::streamSpreadsheet($fA) as $row) {
            if ($cA++ === 0) continue;
            $ic = self::normIC($row[$mA['ic']] ?? '');
            if (strlen($ic) !== 12) $sum['invalid']++;
            $name = ($mode === 'smart') ? self::smartNorm($row[$mA['name']] ?? '') : self::cleanStr($row[$mA['name']] ?? '');
            
            $k = $name . '_' . $ic;
            if ($k !== '_') {
                $dataA[$k] = ['row' => $row, 'seen' => false];
                $sum['total_a']++;
            }
        }

        $cB = 0;
        foreach (self::streamSpreadsheet($fB) as $row) {
            if ($cB++ === 0) continue;
            $sum['total_b']++;
            $ic = self::normIC($row[$mB['ic']] ?? '');
            $name = ($mode === 'smart') ? self::smartNorm($row[$mB['name']] ?? '') : self::cleanStr($row[$mB['name']] ?? '');
            $k = $name . '_' . $ic;

            $nB = $row[$mB['name']] ?? ''; $icB = $row[$mB['ic']] ?? '';
            $idB = isset($mB['id'], $row[$mB['id']]) ? $row[$mB['id']] : 'N/A';

            if (isset($dataA[$k])) {
                $dataA[$k]['seen'] = true;
                $rA = $dataA[$k]['row'];
                $nA = $rA[$mA['name']] ?? ''; $icA = $rA[$mA['ic']] ?? '';
                $idA = isset($mA['id'], $rA[$mA['id']]) ? $rA[$mA['id']] : 'N/A';

                if (self::cleanStr($nA) !== self::cleanStr($nB) || self::normIC($icA) !== self::normIC($icB)) {
                    $sum['modified']++; $status = 'Modified';
                } else {
                    $sum['matched']++; $status = 'Match';
                }
                $res[] = compact('status','nA','nB','icA','icB','idA','idB');
            } else {
                $sum['missing_a']++;
                $res[] = ['status' => 'Missing in A', 'nA' => '', 'nB' => $nB, 'icA' => '', 'icB' => $icB, 'idA' => '', 'idB' => $idB];
            }
        }

        foreach ($dataA as $item) {
            if (!$item['seen']) {
                $sum['missing_b']++;
                $rA = $item['row'];
                $idA = isset($mA['id'], $rA[$mA['id']]) ? $rA[$mA['id']] : 'N/A';
                $res[] = ['status' => 'Missing in B', 'nA' => $rA[$mA['name']] ?? '', 'nB' => '', 'icA' => $rA[$mA['ic']] ?? '', 'icB' => '', 'idA' => $idA, 'idB' => ''];
            }
        }
        return ['summary' => $sum, 'details' => $res];
    }
}
