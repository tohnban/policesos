<?php
namespace App\services;

use App\model\Commission;
use App\model\PaymentTransaction;
use App\model\UserPaymentAccount;

class CommissionSettlementService {
    public static function approveOwnerPayment(int $commissionId, int $adminId, string $reference = ''): bool {
        $commission = Commission::findById($commissionId);
        if (!$commission || !Commission::canValidateOwnerPayment($commission)) {
            return false;
        }

        $reference = trim($reference);
        if ($reference === '') {
            $reference = trim((string) ($commission['owner_payment_reference'] ?? ''));
        }

        $db = new Commission();
        $conn = $db->ConexaoDB();

        try {
            $conn->beginTransaction();

            $now = date('Y-m-d H:i:s');
            $ownerId = (int) ($commission['owner_id'] ?? 0);
            $total = (float) ($commission['amount'] ?? 0);
            $payoutAccountId = null;

            if (Commission::hasValidAffiliate($commission)) {
                $affiliateId = (int) ($commission['affiliate_id'] ?? 0);
                $account = UserPaymentAccount::getDefaultActiveForUser($affiliateId);
                $payoutAccountId = $account ? (int) ($account['id'] ?? 0) : null;
            }

            $affiliatePayoutStatus = Commission::hasValidAffiliate($commission)
                ? Commission::AFFILIATE_PAYOUT_PENDENTE
                : Commission::AFFILIATE_PAYOUT_NENHUM;

            $sql = "UPDATE commissions
                    SET owner_payment_status = ?,
                        owner_payment_validated_by = ?,
                        owner_payment_validated_at = NOW(),
                        owner_payment_rejection_reason = NULL,
                        status = 'pago',
                        paid_at = NOW(),
                        payment_reference = ?,
                        affiliate_payout_account_id = ?,
                        affiliate_payout_status = ?
                    WHERE id = ? AND status = 'pendente' AND owner_payment_status = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                Commission::OWNER_PAYMENT_APROVADO,
                $adminId > 0 ? $adminId : null,
                $reference !== '' ? $reference : null,
                $payoutAccountId > 0 ? $payoutAccountId : null,
                $affiliatePayoutStatus,
                $commissionId,
                Commission::OWNER_PAYMENT_ENVIADO,
            ]);

            if ($stmt->rowCount() <= 0) {
                $conn->rollBack();
                return false;
            }

            PaymentTransaction::create([
                'transaction_type' => 'commission_owner_payment',
                'direction' => 'incoming',
                'status' => 'confirmado',
                'amount' => $total,
                'currency' => 'AOA',
                'method_id' => !empty($commission['owner_payment_method_id']) ? (int) $commission['owner_payment_method_id'] : null,
                'system_channel_id' => !empty($commission['owner_payment_channel_id']) ? (int) $commission['owner_payment_channel_id'] : null,
                'counterparty_user_id' => $ownerId > 0 ? $ownerId : null,
                'related_entity_type' => 'commission',
                'related_entity_id' => $commissionId,
                'reference_code' => $reference !== '' ? $reference : null,
                'proof_file' => (string) ($commission['owner_payment_proof_path'] ?? ''),
                'confirmed_by' => $adminId > 0 ? $adminId : null,
                'confirmed_at' => $now,
                'notes' => 'Pagamento de comissão pelo proprietário',
            ]);

            if (Commission::hasValidAffiliate($commission) && !PaymentTransaction::hasAffiliatePayoutForCommission($commissionId)) {
                $affiliateId = (int) ($commission['affiliate_id'] ?? 0);
                $affiliateAmount = (float) ($commission['affiliate_amount'] ?? 0);
                $account = $payoutAccountId > 0
                    ? UserPaymentAccount::findByIdForUser($payoutAccountId, $affiliateId)
                    : null;

                PaymentTransaction::create([
                    'transaction_type' => 'commission_payout',
                    'direction' => 'outgoing',
                    'status' => 'pendente',
                    'amount' => $affiliateAmount,
                    'currency' => 'AOA',
                    'method_id' => $account ? (int) ($account['method_id'] ?? 0) : null,
                    'user_account_id' => $payoutAccountId > 0 ? $payoutAccountId : null,
                    'counterparty_user_id' => $affiliateId,
                    'related_entity_type' => 'commission',
                    'related_entity_id' => $commissionId,
                    'reference_code' => $reference !== '' ? $reference : null,
                    'notes' => 'Pagamento ao afiliado — aguarda comprovativo',
                    'created_by' => $adminId > 0 ? $adminId : null,
                ]);
            }

            $systemAmount = (float) ($commission['system_amount'] ?? 0);
            if ($systemAmount > 0) {
                PaymentTransaction::create([
                    'transaction_type' => 'system_commission',
                    'direction' => 'incoming',
                    'status' => 'confirmado',
                    'amount' => $systemAmount,
                    'currency' => 'AOA',
                    'counterparty_user_id' => null,
                    'related_entity_type' => 'commission',
                    'related_entity_id' => $commissionId,
                    'reference_code' => $reference !== '' ? $reference : null,
                    'confirmed_by' => $adminId > 0 ? $adminId : null,
                    'confirmed_at' => $now,
                    'notes' => Commission::hasValidAffiliate($commission)
                        ? 'Receita da plataforma (taxa sistema)'
                        : 'Receita da plataforma (comissão integral)',
                ]);
            }

            $conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return false;
        }
    }

    public static function rejectOwnerPayment(int $commissionId, int $adminId, string $reason = ''): bool {
        $commission = Commission::findById($commissionId);
        if (!$commission || !Commission::canValidateOwnerPayment($commission)) {
            return false;
        }

        $reason = trim($reason);
        $db = new Commission();
        $sql = "UPDATE {$db->table}
                SET owner_payment_status = ?,
                    owner_payment_validated_by = ?,
                    owner_payment_validated_at = NOW(),
                    owner_payment_rejection_reason = ?,
                    owner_payment_proof_path = NULL,
                    owner_payment_reference = NULL,
                    owner_payment_submitted_at = NULL,
                    owner_payment_method_id = NULL,
                    owner_payment_channel_id = NULL
                WHERE id = ?
                  AND status = 'pendente'
                  AND owner_payment_status = ?";
        $stmt = $db->prepare($sql);
        $ok = $stmt->execute([
            Commission::OWNER_PAYMENT_REJEITADO,
            $adminId > 0 ? $adminId : null,
            $reason !== '' ? $reason : null,
            $commissionId,
            Commission::OWNER_PAYMENT_ENVIADO,
        ]);

        return $ok && $stmt->rowCount() > 0;
    }

    public static function confirmAffiliatePayout(
        int $commissionId,
        int $adminId,
        string $proofPath,
        string $reference = ''
    ): bool {
        $proofPath = trim($proofPath);
        if ($proofPath === '') {
            return false;
        }

        $reference = trim($reference);
        $db = new Commission();
        $conn = $db->ConexaoDB();

        try {
            $conn->beginTransaction();

            $commission = Commission::findByIdForUpdate($commissionId);
            if (!$commission || (string) ($commission['status'] ?? '') !== 'pago') {
                $conn->rollBack();
                return false;
            }
            if (!Commission::hasValidAffiliate($commission)) {
                $conn->rollBack();
                return false;
            }
            if (Commission::resolveAffiliatePayoutStatus($commission) === Commission::AFFILIATE_PAYOUT_PAGO) {
                $conn->rollBack();
                return false;
            }
            if (PaymentTransaction::hasConfirmedAffiliatePayout($commissionId)) {
                $conn->rollBack();
                return false;
            }

            $payoutTx = PaymentTransaction::findPendingAffiliatePayout($commissionId);
            if (!$payoutTx && !PaymentTransaction::hasAffiliatePayoutForCommission($commissionId)) {
                $affiliateAmount = (float) ($commission['affiliate_amount'] ?? 0);
                $affiliateId = (int) ($commission['affiliate_id'] ?? 0);
                $accountId = (int) ($commission['affiliate_payout_account_id'] ?? 0);
                $account = $accountId > 0
                    ? UserPaymentAccount::findByIdForUser($accountId, $affiliateId)
                    : UserPaymentAccount::getDefaultActiveForUser($affiliateId);

                $created = PaymentTransaction::create([
                    'transaction_type' => 'commission_payout',
                    'direction' => 'outgoing',
                    'status' => 'pendente',
                    'amount' => $affiliateAmount,
                    'currency' => 'AOA',
                    'method_id' => $account ? (int) ($account['method_id'] ?? 0) : null,
                    'user_account_id' => $account ? (int) ($account['id'] ?? 0) : null,
                    'counterparty_user_id' => $affiliateId,
                    'related_entity_type' => 'commission',
                    'related_entity_id' => $commissionId,
                    'notes' => 'Pagamento ao afiliado — aguarda comprovativo',
                    'created_by' => $adminId > 0 ? $adminId : null,
                ]);
                if (!$created) {
                    $conn->rollBack();
                    return false;
                }
                $payoutTx = PaymentTransaction::findPendingAffiliatePayout($commissionId);
            }

            if (!$payoutTx) {
                $conn->rollBack();
                return false;
            }

            $txId = (int) ($payoutTx['id'] ?? 0);
            if (!PaymentTransaction::confirmWithProof($txId, $adminId, $proofPath, $reference)) {
                $conn->rollBack();
                return false;
            }

            if (PaymentTransaction::countAffiliatePayouts($commissionId, 'confirmado') > 1) {
                $conn->rollBack();
                return false;
            }

            $sql = "UPDATE commissions
                    SET affiliate_payout_completed_at = NOW(),
                        affiliate_payout_status = ?
                    WHERE id = ?
                      AND affiliate_payout_status = ?
                      AND affiliate_payout_completed_at IS NULL";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                Commission::AFFILIATE_PAYOUT_PAGO,
                $commissionId,
                Commission::AFFILIATE_PAYOUT_PENDENTE,
            ]);

            if ($stmt->rowCount() <= 0) {
                $conn->rollBack();
                return false;
            }

            $conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return false;
        }
    }
}
