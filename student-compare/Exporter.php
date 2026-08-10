<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Exporter
 * Generates downloadable CSV exports and formatted report structures.
 */
class Exporter
{
    /**
     * Stream CSV file to browser for download.
     */
    public static function exportCSV(array $results, string $filename = 'Comparison_Report.csv'): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Status', 'Name (File A)', 'Name (File B)', 'IC (File A)', 'IC (File B)', 'ID (File A)', 'ID (File B)', 'Difference', 'Remarks']);

        foreach ($results as $row) {
            fputcsv($out, [
                $row['status'] ?? '',
                $row['name_a'] ?? '',
                $row['name_b'] ?? '',
                $row['ic_a'] ?? '',
                $row['ic_b'] ?? '',
                $row['id_a'] ?? '',
                $row['id_b'] ?? '',
                $row['difference'] ?? '',
                $row['remarks'] ?? ''
            ]);
        }

        fclose($out);
        exit;
    }
}