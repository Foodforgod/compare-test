<?php
declare(strict_types=1);

namespace App\Classes;

use Generator;
use ZipArchive;
use SimpleXMLElement;
use Exception;

// If project installed PhpSpreadsheet via Composer, include its autoloader so we can use it.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * ExcelReader
 * Uses PhpSpreadsheet when available for robust XLSX/CSV parsing. Falls back to
 * a lightweight Zip + XML parser for XLSX and fgetcsv for CSV when PhpSpreadsheet
 * is not installed.
 */
class ExcelReader
{
    /**
     * Stream rows line-by-line without loading entire dataset into RAM.
     */
    public static function streamRows(string $filePath): Generator
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // If PhpSpreadsheet is available, use it for both CSV and XLSX for robustness.
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($filePath);
                $sheet = $spreadsheet->getActiveSheet();

                foreach ($sheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $cols = [];
                    foreach ($cellIterator as $cell) {
                        $cols[] = trim((string)$cell->getValue());
                    }
                    yield $cols;
                }
                return;
            } catch (Exception $e) {
                // Fall back to lightweight parsers below
            }
        }

        if ($ext === 'csv') {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return;
            }
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                yield array_map('trim', $row);
            }
            fclose($handle);
            return;
        }

        if ($ext === 'xlsx' && class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                return;
            }

            $sharedStrings = [];
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml !== false) {
                try {
                    $xml = new SimpleXMLElement($sharedStringsXml);
                    foreach ($xml->si as $si) {
                        $parts = [];
                        foreach ($si->children() as $child) {
                            if ($child->getName() === 't') {
                                $parts[] = (string)$child;
                            } elseif ($child->getName() === 'r') {
                                foreach ($child->children() as $grandChild) {
                                    if ($grandChild->getName() === 't') {
                                        $parts[] = (string)$grandChild;
                                    }
                                }
                            }
                        }
                        $sharedStrings[] = implode('', $parts);
                    }
                } catch (Exception $e) {
                    // Fallback on XML parse error
                }
            }

            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml !== false) {
                try {
                    $xml = new SimpleXMLElement($sheetXml);
                    foreach ($xml->sheetData->row as $rowNode) {
                        $row = [];
                        foreach ($rowNode->c as $cell) {
                            $row[] = self::decodeCellValue($cell, $sharedStrings);
                        }
                        yield $row;
                    }
                } catch (Exception $e) {
                    // Fallback on sheet parse error
                }
            }
            $zip->close();
        }
    }

    /**
     * Extract string values from XLSX XML cell nodes.
     */
    private static function decodeCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string)($cell['t'] ?? '');
        if ($type === 's') {
            $idx = isset($cell->v) ? (int)((string)$cell->v) : -1;
            return $sharedStrings[$idx] ?? '';
        }
        return isset($cell->v) ? (string)$cell->v : '';
    }

    /**
     * Fetch top N rows for column mapping preview.
     */
    public static function preview(string $filePath, int $limit = 20): array
    {
        // If PhpSpreadsheet available, use it for preview (faster and more robust)
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = [];
                $count = 0;
                foreach ($sheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $vals = [];
                    foreach ($cellIterator as $cell) {
                        $vals[] = trim((string)$cell->getValue());
                    }
                    $rows[] = $vals;
                    $count++;
                    if ($count >= $limit) break;
                }
                return $rows;
            } catch (Exception $e) {
                // Fall back to generator-based preview below
            }
        }

        $previewRows = [];
        $count = 0;
        foreach (self::streamRows($filePath) as $row) {
            $previewRows[] = $row;
            $count++;
            if ($count >= $limit) {
                break;
            }
        }
        return $previewRows;
    }
}