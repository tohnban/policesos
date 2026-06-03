<?php
namespace Src\classes;

class ClassCookieConsent {
    public const COOKIE_BEHAVIORAL = 'imobil_behavioral_consent';
    public const VALUE_ACCEPTED = 'accepted';
    public const VALUE_REJECTED = 'rejected';

    public static function behavioralValue(): string {
        $value = strtolower(trim((string) ($_COOKIE[self::COOKIE_BEHAVIORAL] ?? '')));
        if ($value === self::VALUE_ACCEPTED || $value === self::VALUE_REJECTED) {
            return $value;
        }
        return '';
    }

    public static function hasBehavioralConsent(): bool {
        return self::behavioralValue() === self::VALUE_ACCEPTED;
    }

    public static function hasBehavioralPreference(): bool {
        return self::behavioralValue() !== '';
    }
}
