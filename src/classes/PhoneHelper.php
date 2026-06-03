<?php

namespace Src\classes;

/**
 * Normalização de telefones angolanos (+244) para login e registo.
 */
class PhoneHelper
{
    public static function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public static function normalize(string $phone): string
    {
        $trimmed = trim($phone);
        if ($trimmed === '') {
            return '';
        }

        $digits = self::digitsOnly($trimmed);
        if ($digits === '') {
            return $trimmed;
        }

        if (str_starts_with($digits, '244')) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        if (preg_match('/^9\d{8}$/', $digits)) {
            return '+244' . $digits;
        }

        return '+' . $digits;
    }

    /** Dígitos comparáveis (sem +, espaços ou zeros à esquerda locais). */
    public static function comparableDigits(string $phone): string
    {
        $digits = self::digitsOnly($phone);
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '244')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        if (preg_match('/^9\d{8}$/', $digits)) {
            return '244' . $digits;
        }

        return $digits;
    }
}
