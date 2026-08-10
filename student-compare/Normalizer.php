<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Normalizer
 * Provides string normalization, name cleaning, and Malaysian IC verification.
 */
class Normalizer
{
    /**
     * Clean standard text by removing excess whitespace and trimming.
     */
    public static function cleanString(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        $cleaned = preg_replace('/\s+/', ' ', $input);
        return trim((string)$cleaned);
    }

    /**
     * Smart name normalization: lowercase, strip special characters, dashes, slashes, and dots.
     */
    public static function normalizeName(?string $name): string
    {
        if ($name === null) {
            return '';
        }
        $cleaned = mb_strtolower($name, 'UTF-8');
        $cleaned = preg_replace('/[\-\.\/\_\,\@\s]+/', ' ', $cleaned);
        return trim((string)$cleaned);
    }

    /**
     * Normalize IC numbers: remove non-numeric characters and pad 11-digit numbers with a leading zero.
     */
    public static function normalizeIC(?string $ic): string
    {
        if ($ic === null) {
            return '';
        }
        $digits = preg_replace('/[^0-9]/', '', $ic);
        if (strlen((string)$digits) === 11) {
            $digits = '0' . $digits;
        }
        return (string)$digits;
    }

    /**
     * Validate Malaysian MyKad IC number format (YYMMDD-PB-###G).
     */
    public static function validateIC(string $ic): bool
    {
        $normalized = self::normalizeIC($ic);
        if (strlen($normalized) !== 12) {
            return false;
        }

        $yy = (int)substr($normalized, 0, 2);
        $mm = (int)substr($normalized, 2, 2);
        $dd = (int)substr($normalized, 4, 2);

        if ($mm < 1 || $mm > 12) {
            return false;
        }
        if ($dd < 1 || $dd > 31) {
            return false;
        }

        return true;
    }

    /**
     * Normalize email address.
     */
    public static function normalizeEmail(?string $email): string
    {
        if ($email === null) {
            return '';
        }
        return strtolower(trim($email));
    }

    /**
     * Normalize phone number to pure digits.
     */
    public static function normalizePhone(?string $phone): string
    {
        if ($phone === null) {
            return '';
        }
        return preg_replace('/[^0-9]/', '', $phone);
    }
}