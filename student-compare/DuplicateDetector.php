<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * DuplicateDetector
 * Detects duplicated records inside individual files.
 */
class DuplicateDetector
{
    /**
     * Analyze a dataset for internal duplicate rows based on key columns.
     */
    public static function analyze(array $rows, int $keyColIndex): array
    {
        $seen = [];
        $duplicates = [];
        $duplicateRows = [];
        $duplicateCount = 0;

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 0) {
                continue; // Skip header
            }
            $val = Normalizer::cleanString($row[$keyColIndex] ?? '');
            if ($val === '') {
                continue;
            }

            if (isset($seen[$val])) {
                $duplicateCount++;
                $duplicates[$val][] = $rowIndex + 1;
                $duplicateRows[] = [
                    'row_num' => $rowIndex + 1,
                    'value' => $val,
                    'first_seen_row' => $seen[$val]
                ];
            } else {
                $seen[$val] = $rowIndex + 1;
            }
        }

        return [
            'total_duplicates' => $duplicateCount,
            'duplicate_values' => $duplicates,
            'duplicate_rows' => $duplicateRows
        ];
    }
}