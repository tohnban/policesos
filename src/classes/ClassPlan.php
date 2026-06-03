<?php

namespace Src\classes;

use App\model\User;
use App\model\UserSubscription;

class ClassPlan
{
    public const PLAN_ESSENTIAL    = 'essential';
    public const PLAN_PROFESSIONAL = 'professional';
    public const PLAN_ENTERPRISE   = 'enterprise';

    /**
     * Resolve the official plan for a user.
     * When no effective subscription exists, auto-provisions Essential.
     * Legacy users.account_plan is deprecated (see migration_20260521_deprecate_account_plan.sql).
     */
    public static function getOfficialPlanByUser(int $userId): array
    {
        $subscription = UserSubscription::getCurrentByUser($userId);
        if ($subscription) {
            return [
                'code'                   => (string) ($subscription['plan_code'] ?? self::PLAN_ESSENTIAL),
                'name'                   => (string) ($subscription['plan_name'] ?? 'Plano Essencial'),
                'max_active_properties'  => isset($subscription['max_active_properties'])
                    ? (int) $subscription['max_active_properties']
                    : null,
                'visibility_tier'        => (string) ($subscription['visibility_tier'] ?? 'basic'),
                'has_reports'            => !empty($subscription['has_reports']),
                'has_advanced_reports'   => !empty($subscription['has_advanced_reports']),
                'has_institutional_page' => !empty($subscription['has_institutional_page']),
                'has_priority_support'   => !empty($subscription['has_priority_support']),
                'has_featured_in_results' => !empty($subscription['has_featured_in_results']),
                'ranking_weight'         => (int) ($subscription['ranking_weight'] ?? 0),
                'is_fallback'            => false,
            ];
        }

        // Pending or other open row exists — do not create a second subscription.
        if (UserSubscription::hasOpenSubscription($userId)) {
            return [
                'code'                   => self::PLAN_ESSENTIAL,
                'name'                   => 'Plano Essencial',
                'max_active_properties'  => 3,
                'visibility_tier'        => 'basic',
                'has_reports'            => false,
                'has_advanced_reports'   => false,
                'has_institutional_page' => false,
                'has_priority_support'   => false,
                'has_featured_in_results' => false,
                'ranking_weight'         => 0,
                'is_fallback'            => true,
            ];
        }

        UserSubscription::activatePlanForUser($userId, self::PLAN_ESSENTIAL, false);

        return [
            'code'                   => self::PLAN_ESSENTIAL,
            'name'                   => 'Plano Essencial',
            'max_active_properties'  => 3,
            'visibility_tier'        => 'basic',
            'has_reports'            => false,
            'has_advanced_reports'   => false,
            'has_institutional_page' => false,
            'has_priority_support'   => false,
            'has_featured_in_results' => false,
            'ranking_weight'         => 0,
            'is_fallback'            => false,
        ];
    }

    public static function getMaxActivePropertiesByUser(int $userId): ?int
    {
        $plan = self::getOfficialPlanByUser($userId);
        return $plan['max_active_properties'] ?? 3;
    }

    public static function canPublishProperty(int $userId, int $activePropertyCount): array
    {
        $plan = self::getOfficialPlanByUser($userId);
        $max = $plan['max_active_properties'] ?? null;

        if ($max === null) {
            return ['allowed' => true, 'max' => null, 'plan' => $plan];
        }

        $allowed = $activePropertyCount < (int) $max;
        return ['allowed' => $allowed, 'max' => (int) $max, 'plan' => $plan];
    }

    public static function mapPlanToPropertyVisibility(array $plan): string
    {
        return (($plan['visibility_tier'] ?? 'basic') === 'premium') ? 'premium' : 'basic';
    }

    /** Gate: user can access basic stats (Professional+). */
    public static function canViewReports(int $userId): bool
    {
        return (bool) (self::getOfficialPlanByUser($userId)['has_reports'] ?? false);
    }

    /** Gate: user can access advanced analytics (Enterprise). */
    public static function canViewAdvancedReports(int $userId): bool
    {
        return (bool) (self::getOfficialPlanByUser($userId)['has_advanced_reports'] ?? false);
    }

    /** Gate: user can publish an institutional/agency page (Enterprise). */
    public static function canUseInstitutionalPage(int $userId): bool
    {
        return (bool) (self::getOfficialPlanByUser($userId)['has_institutional_page'] ?? false);
    }

    /** Public profile URL: institutional page for Enterprise, owner profile otherwise. */
    public static function getPublicProfileUrl(int $userId): string
    {
        if ($userId <= 0) {
            return DIRPAGE . 'properties';
        }
        if (self::canUseInstitutionalPage($userId)) {
            return DIRPAGE . 'property/agency/' . $userId;
        }
        return DIRPAGE . 'property/owner/' . $userId;
    }

    /** Gate: user has priority results ranking (Professional+). */
    public static function hasFeaturedInResults(int $userId): bool
    {
        return (bool) (self::getOfficialPlanByUser($userId)['has_featured_in_results'] ?? false);
    }
}
