<?php
namespace Src\classes;

use App\model\ManipularBanco;
use App\model\User;

/**
 * Single source of truth for trust-badge request eligibility (selo de confiança).
 */
class ClassTrustBadgeEligibility {
    public const GUARD_VERSION = '20260526c';

    public static function getConfig(): array {
        $minWonDeals = max(0, ClassSettings::int('trust_badge_min_won_deals', 3));
        $minAccountDays = max(0, ClassSettings::int('trust_badge_min_account_days', 90));
        $requireConfirmedClosing = ClassSettings::int('trust_badge_require_confirmed_closing', 1) === 1;

        return [
            'min_won_deals' => $minWonDeals,
            'min_account_days' => $minAccountDays,
            'require_confirmed_closing' => $requireConfirmedClosing,
        ];
    }

    /**
     * @return array{eligible:int,total_fechado_ganho:int,excluded_contested:int,excluded_pending:int}
     */
    public static function getWonDealsStats(int $userId, bool $requireConfirmedClosing): array {
        $empty = [
            'eligible' => 0,
            'total_fechado_ganho' => 0,
            'excluded_contested' => 0,
            'excluded_pending' => 0,
        ];
        if ($userId <= 0) {
            return $empty;
        }

        $db = new ManipularBanco();
        $baseSql = "FROM requests r
                INNER JOIN properties p ON p.id = r.property_id
                WHERE r.status = 'fechado_ganho'
                  AND (p.affiliate_id = ? OR r.affiliate_id = ?)";
        $params = [$userId, $userId];

        $stmt = $db->prepare("SELECT COUNT(DISTINCT r.id) {$baseSql}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        if (!$requireConfirmedClosing) {
            return [
                'eligible' => $total,
                'total_fechado_ganho' => $total,
                'excluded_contested' => 0,
                'excluded_pending' => 0,
            ];
        }

        $stmt = $db->prepare("SELECT COUNT(DISTINCT r.id) {$baseSql}
                AND (r.closing_confirmation_status = 'confirmado'
                     OR r.closing_confirmation_status IS NULL)");
        $stmt->execute($params);
        $eligible = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(DISTINCT r.id) {$baseSql}
                AND r.closing_confirmation_status = 'contestada'");
        $stmt->execute($params);
        $excludedContested = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(DISTINCT r.id) {$baseSql}
                AND r.closing_confirmation_status = 'pendente'");
        $stmt->execute($params);
        $excludedPending = (int) $stmt->fetchColumn();

        return [
            'eligible' => $eligible,
            'total_fechado_ganho' => $total,
            'excluded_contested' => $excludedContested,
            'excluded_pending' => $excludedPending,
        ];
    }

    public static function getAccountAgeDays(?array $user): int {
        return User::getAccountAgeDays($user);
    }

    /**
     * @return array{
     *   won_deals: array{current:int,required:int,met:bool,total_fechado_ganho:int,excluded_contested:int,excluded_pending:int},
     *   account_age_days: array{current:int,required:int,met:bool},
     *   met: bool
     * }
     */
    public static function evaluateEligibility(int $userId, ?array $user = null): array {
        $user = $user ?? User::findById($userId);
        $config = self::getConfig();

        $wonDealsStats = self::getWonDealsStats($userId, (bool) $config['require_confirmed_closing']);
        $wonDealsCurrent = (int) ($wonDealsStats['eligible'] ?? 0);
        $wonDealsRequired = (int) $config['min_won_deals'];
        $wonDealsMet = $wonDealsRequired <= 0 || $wonDealsCurrent >= $wonDealsRequired;

        $accountAgeCurrent = self::getAccountAgeDays($user);
        $accountAgeRequired = (int) $config['min_account_days'];
        $accountAgeMet = $accountAgeRequired <= 0 || $accountAgeCurrent >= $accountAgeRequired;

        return [
            'won_deals' => [
                'current' => $wonDealsCurrent,
                'required' => $wonDealsRequired,
                'met' => $wonDealsMet,
                'total_fechado_ganho' => (int) ($wonDealsStats['total_fechado_ganho'] ?? 0),
                'excluded_contested' => (int) ($wonDealsStats['excluded_contested'] ?? 0),
                'excluded_pending' => (int) ($wonDealsStats['excluded_pending'] ?? 0),
            ],
            'account_age_days' => [
                'current' => $accountAgeCurrent,
                'required' => $accountAgeRequired,
                'met' => $accountAgeMet,
            ],
            'met' => $wonDealsMet && $accountAgeMet,
        ];
    }

    /**
     * @return string[]
     */
    public static function getBlockers(array $eligibility): array {
        $blockers = [];

        $wonDeals = $eligibility['won_deals'] ?? [];
        if (($wonDeals['met'] ?? false) !== true) {
            $required = (int) ($wonDeals['required'] ?? 0);
            $current = (int) ($wonDeals['current'] ?? 0);
            $missing = max(0, $required - $current);
            if ($missing > 0) {
                $blockers[] = $missing === 1
                    ? 'Falta 1 negociação ganha confirmada'
                    : 'Faltam ' . $missing . ' negociações ganhas confirmadas';
            }
        }

        $accountAge = $eligibility['account_age_days'] ?? [];
        if (($accountAge['met'] ?? false) !== true) {
            $required = (int) ($accountAge['required'] ?? 0);
            $current = (int) ($accountAge['current'] ?? 0);
            $missing = max(0, $required - $current);
            if ($missing > 0) {
                $blockers[] = $missing === 1
                    ? 'Falta 1 dia na plataforma'
                    : 'Faltam ' . $missing . ' dias na plataforma';
            }
        }

        return $blockers;
    }

    public static function normalizeBadgeStatus(?array $user): string {
        $badgeStatus = strtolower(trim((string) ($user['trust_badge_status'] ?? '')));
        if ($badgeStatus === '' || $badgeStatus === 'none' || $badgeStatus === 'nenhum') {
            return 'nenhum';
        }
        if ($badgeStatus === 'pending' || $badgeStatus === 'pendente') {
            return 'pendente';
        }
        if ($badgeStatus === 'approved' || $badgeStatus === 'aprovado') {
            return 'aprovado';
        }
        if ($badgeStatus === 'rejected' || $badgeStatus === 'rejeitado') {
            return 'rejeitado';
        }

        return $badgeStatus;
    }

    /**
     * @return array{
     *   allowed: bool,
     *   blockers: array<int,string>,
     *   badge_status: string,
     *   eligibility: array<string,mixed>
     * }
     */
    public static function assertCanRequest(int $userId): array {
        $userId = (int) $userId;
        $user = $userId > 0 ? User::findById($userId) : null;

        if (!$user || ($user['status'] ?? '') !== 'ativo') {
            return [
                'allowed' => false,
                'blockers' => ['Conta ainda não verificada'],
                'badge_status' => self::normalizeBadgeStatus($user),
                'eligibility' => [],
            ];
        }

        $badgeStatus = self::normalizeBadgeStatus($user);
        $badgeAllowsRequest = in_array($badgeStatus, ['nenhum', 'rejeitado'], true);
        if (!$badgeAllowsRequest) {
            return [
                'allowed' => false,
                'blockers' => ['Já existe um pedido de selo em curso ou concluído'],
                'badge_status' => $badgeStatus,
                'eligibility' => [],
            ];
        }

        $eligibility = self::evaluateEligibility($userId, $user);
        $blockers = self::getBlockers($eligibility);
        $requirementsMet = (($eligibility['met'] ?? false) === true);

        return [
            'allowed' => $requirementsMet,
            'blockers' => $blockers,
            'badge_status' => $badgeStatus,
            'eligibility' => $eligibility,
        ];
    }

    /**
     * Estado único para a secção do selo no perfil (evita ramos UI inconsistentes).
     *
     * @return array{
     *   view: string,
     *   gate: array<string,mixed>,
     *   trust: array<string,mixed>,
     *   can_submit: bool
     * }
     */
    public static function resolveProfileUiState(int $userId): array {
        $userId = (int) $userId;
        $gate = self::assertCanRequest($userId);
        $trust = User::getTrustMetrics($userId);
        $badgeStatus = (string) ($gate['badge_status'] ?? 'nenhum');
        $canSubmit = ($gate['allowed'] ?? false) === true;

        if ($badgeStatus === 'pendente') {
            return ['view' => 'pending', 'gate' => $gate, 'trust' => $trust, 'can_submit' => false];
        }

        if ($badgeStatus === 'aprovado' && empty($trust['fee_paid'])) {
            return ['view' => 'payment_pending', 'gate' => $gate, 'trust' => $trust, 'can_submit' => false];
        }

        if (!empty($trust['trusted'])) {
            return ['view' => 'active', 'gate' => $gate, 'trust' => $trust, 'can_submit' => false];
        }

        if (($trust['verified'] ?? false) !== true) {
            return ['view' => 'unverified', 'gate' => $gate, 'trust' => $trust, 'can_submit' => false];
        }

        if ($canSubmit) {
            return ['view' => 'form', 'gate' => $gate, 'trust' => $trust, 'can_submit' => true];
        }

        return ['view' => 'locked', 'gate' => $gate, 'trust' => $trust, 'can_submit' => false];
    }
}
