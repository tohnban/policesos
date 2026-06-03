<?php
namespace Src\classes;

/**
 * Bloqueia POST de pedido de selo antes do controller (todos os utilizadores).
 */
class ClassTrustBadgeGuard {
    public static function enforce(string $controller, array $url): void {
        if ($controller !== 'ControllerDashboard') {
            return;
        }

        $method = (string) ($url[1] ?? '');
        if ($method !== 'requestTrustedBadge') {
            return;
        }

        if (!ClassAuth::check()) {
            return;
        }

        $user = ClassAuth::user();
        $userId = (int) ($user['id'] ?? 0);
        $gate = ClassTrustBadgeEligibility::assertCanRequest($userId);

        if (($gate['allowed'] ?? false) === true) {
            return;
        }

        $blockers = $gate['blockers'] ?? [];
        $errorMsg = !empty($blockers)
            ? implode('. ', $blockers)
            : 'Não é possível solicitar o selo neste momento';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            header('Location: ' . DIRPAGE . 'profile?error=' . urlencode($errorMsg) . '#trust-badge-section');
            exit;
        }
    }
}
