<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Comparison
 * Core engine for comparing two datasets with Exact and Smart match rules.
 */
class Comparison
{
    public const MODE_EXACT = 'exact';
    public const MODE_SMART = 'smart';

    /**
     * Execute comparison between File A and File B.
     */
    public static function run(
        string $fileA,
        string $fileB,
        array $mapA,
        array $mapB,
        string $mode = self::MODE_SMART,
        array $rules = ['name', 'ic']
    ): array {
        $datasetA = [];
        $results = [];
        $summary = [
            'total_a' => 0,
            'total_b' => 0,
            'matched' => 0,
            'modified' => 0,
            'missing_a' => 0,
            'missing_b' => 0,
            'duplicates_a' => 0,
            'duplicates_b' => 0,
            'invalid_ic_a' => 0,
            'invalid_ic_b' => 0,
            'match_percentage' => 0.0
        ];

        // Process File A
        $rowIdxA = 0;
        foreach (ExcelReader::streamRows($fileA) as $row) {
            $rowIdxA++;
            if ($rowIdxA === 1) {
                continue;
            }

            $name = $row[$mapA['name']] ?? '';
            $ic = $row[$mapA['ic']] ?? '';
            $id = $row[$mapA['id']] ?? '';

            if (empty($name) && empty($ic) && empty($id)) {
                continue;
            }

            $normIC = Normalizer::normalizeIC($ic);
            if ($normIC !== '' && !Normalizer::validateIC($ic)) {
                $summary['invalid_ic_a']++;
            }

            $key = self::generateKey($name, $normIC, $id, $mode, $rules);
            if ($key !== '') {
                if (isset($datasetA[$key])) {
                    $summary['duplicates_a']++;
                }
                $datasetA[$key] = [
                    'row_num' => $rowIdxA,
                    'raw_name' => $name,
                    'raw_ic' => $ic,
                    'raw_id' => $id,
                    'seen' => false,
                    'full_row' => $row
                ];
                $summary['total_a']++;
            }
        }

        // Process File B and Compare against File A
        $rowIdxB = 0;
        foreach (ExcelReader::streamRows($fileB) as $row) {
            $rowIdxB++;
            if ($rowIdxB === 1) {
                continue;
            }

            $nameB = $row[$mapB['name']] ?? '';
            $icB = $row[$mapB['ic']] ?? '';
            $idB = $row[$mapB['id']] ?? '';

            if (empty($nameB) && empty($icB) && empty($idB)) {
                continue;
            }

            $summary['total_b']++;
            $normICB = Normalizer::normalizeIC($icB);
            if ($normICB !== '' && !Normalizer::validateIC($icB)) {
                $summary['invalid_ic_b']++;
            }

            $keyB = self::generateKey($nameB, $normICB, $idB, $mode, $rules);

            if (isset($datasetA[$keyB])) {
                $match = &$datasetA[$keyB];
                $match['seen'] = true;

                $nameCleanA = Normalizer::normalizeName($match['raw_name']);
                $nameCleanB = Normalizer::normalizeName($nameB);
                $icNormA = Normalizer::normalizeIC($match['raw_ic']);

                if ($nameCleanA !== $nameCleanB || $icNormA !== $normICB) {
                    $summary['modified']++;
                    $status = 'Modified';
                    $remark = 'Attribute variation detected';
                } else {
                    $summary['matched']++;
                    $status = 'Match';
                    $remark = 'Exact match on selected fields';
                }

                $results[] = [
                    'status' => $status,
                    'name_a' => $match['raw_name'],
                    'name_b' => $nameB,
                    'ic_a' => $match['raw_ic'],
                    'ic_b' => $icB,
                    'id_a' => $match['raw_id'],
                    'id_b' => $idB,
                    'difference' => $nameCleanA !== $nameCleanB ? 'Name difference' : ($icNormA !== $normICB ? 'IC difference' : 'None'),
                    'remarks' => $remark
                ];
            } else {
                $summary['missing_a']++;
                $results[] = [
                    'status' => 'Missing in A',
                    'name_a' => '-',
                    'name_b' => $nameB,
                    'ic_a' => '-',
                    'ic_b' => $icB,
                    'id_a' => '-',
                    'id_b' => $idB,
                    'difference' => 'Record not present in File A',
                    'remarks' => 'Extra record in File B'
                ];
            }
        }

        // Catch Records Missing in File B
        foreach ($datasetA as $item) {
            if (!$item['seen']) {
                $summary['missing_b']++;
                $results[] = [
                    'status' => 'Missing in B',
                    'name_a' => $item['raw_name'],
                    'name_b' => '-',
                    'ic_a' => $item['raw_ic'],
                    'ic_b' => '-',
                    'id_a' => $item['raw_id'],
                    'id_b' => '-',
                    'difference' => 'Record not present in File B',
                    'remarks' => 'Extra record in File A'
                ];
            }
        }

        // Compute overall match percentage
        $totalUnique = max(1, $summary['total_a'] + $summary['missing_a']);
        $summary['match_percentage'] = round(($summary['matched'] / $totalUnique) * 100, 2);

        return [
            'summary' => $summary,
            'details' => $results
        ];
    }

    /**
     * Generate lookup key based on mode and rules.
     */
    private static function generateKey(string $name, string $ic, string $id, string $mode, array $rules): string
    {
        $parts = [];
        if (in_array('name', $rules, true)) {
            $parts[] = ($mode === self::MODE_SMART) ? Normalizer::normalizeName($name) : Normalizer::cleanString($name);
        }
        if (in_array('ic', $rules, true)) {
            $parts[] = Normalizer::normalizeIC($ic);
        }
        if (in_array('id', $rules, true)) {
            $parts[] = Normalizer::cleanString($id);
        }

        return implode('||', array_filter($parts));
    }
}