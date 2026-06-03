<?php
namespace App\model;

use App\model\Notification;

class UserSubscription extends ManipularBanco {
    protected $table = 'user_subscriptions';

    /** SQL fragment: subscription still grants plan benefits (active or past_due in grace). */
    public static function sqlEffectiveBenefitsCondition(string $alias = 'us'): string {
        $a = preg_replace('/[^a-z_]/', '', strtolower($alias)) ?: 'us';
        return "(
            ({$a}.status = 'active' AND {$a}.starts_at <= NOW() AND ({$a}.ends_at IS NULL OR {$a}.ends_at >= NOW()))
            OR ({$a}.status = 'past_due' AND {$a}.grace_until IS NOT NULL AND {$a}.grace_until >= NOW())
        )";
    }

    /** Open lifecycle rows: at most one per user should exist. */
    public static function sqlOpenStatusesCondition(string $alias = 'us'): string {
        $a = preg_replace('/[^a-z_]/', '', strtolower($alias)) ?: 'us';
        return "{$a}.status IN ('pending_activation', 'active', 'past_due')";
    }

    /**
     * Subquery SQL: maps each user_id to a single canonical open subscription_id.
     */
    public static function sqlPrimaryOpenSubscriptionPickSubquery(): string {
        $db = new self();
        $table = $db->table;
        return "(
            SELECT user_id,
                COALESCE(
                    MAX(CASE WHEN status = 'pending_activation' THEN id END),
                    MAX(CASE WHEN status = 'past_due' AND grace_until IS NOT NULL AND grace_until >= NOW() THEN id END),
                    MAX(CASE WHEN status = 'active' AND starts_at <= NOW()
                        AND (ends_at IS NULL OR ends_at >= NOW()) THEN id END),
                    MAX(id)
                ) AS subscription_id
            FROM {$table}
            WHERE status IN ('pending_activation', 'active', 'past_due')
            GROUP BY user_id
        )";
    }

    public static function hasOpenSubscription(int $userId): bool {
        if ($userId <= 0) {
            return false;
        }
        $db = new self();
        $open = self::sqlOpenStatusesCondition('us');
        $sql = "SELECT COUNT(*) FROM {$db->table} us WHERE us.user_id = ? AND {$open}";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cancel all open subscriptions for a user (keeps at most one open row when combined with insert).
     */
    public static function closeOpenSubscriptionsForUser(
        int $userId,
        ?int $exceptSubscriptionId = null,
        ?\PDO $conn = null,
        string $cancelNote = 'Subscrição substituída'
    ): int {
        if ($userId <= 0) {
            return 0;
        }

        $db = new self();
        $conn = $conn ?? $db->ConexaoDB();
        $open = self::sqlOpenStatusesCondition('us');
        $sql = "UPDATE {$db->table} us
                SET status = 'cancelled',
                    ends_at = NOW(),
                    auto_renew = 0,
                    grace_until = NULL,
                    notes = CASE
                        WHEN ? <> '' THEN CONCAT(COALESCE(notes, ''), ' | ', ?)
                        ELSE notes
                    END
                WHERE us.user_id = ? AND {$open}";
        $params = [$cancelNote, $cancelNote, $userId];

        if ($exceptSubscriptionId !== null && $exceptSubscriptionId > 0) {
            $sql .= ' AND us.id <> ?';
            $params[] = $exceptSubscriptionId;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function getCurrentByUser(int $userId): ?array {
        $db = new self();
        $effective = self::sqlEffectiveBenefitsCondition('us');
        $sql = "SELECT us.*, sp.code AS plan_code, sp.name AS plan_name,
                       sp.max_active_properties, sp.visibility_tier,
                       sp.ranking_weight, sp.has_featured_in_results,
                       sp.has_reports, sp.has_advanced_reports,
                       sp.has_priority_support, sp.has_auto_renew,
                       sp.has_institutional_page, sp.is_custom_pricing,
                       sp.monthly_price_aoa
                FROM {$db->table} us
                INNER JOIN subscription_plans sp ON sp.id = us.plan_id
                WHERE us.user_id = ?
                  AND {$effective}
                  AND sp.is_active = 1
                ORDER BY us.starts_at DESC, us.id DESC
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

      public static function getHistoryByUser(int $userId, int $limit = 30): array {
        $db = new self();
        $limit = max(1, min(200, $limit));

        $sql = "SELECT us.*, sp.code AS plan_code, sp.name AS plan_name, sp.monthly_price_aoa
            FROM {$db->table} us
            INNER JOIN subscription_plans sp ON sp.id = us.plan_id
            WHERE us.user_id = ?
            ORDER BY us.created_at DESC, us.id DESC
            LIMIT {$limit}";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
      }

      public static function activatePlanForUser(
        int $userId,
        string $planCode,
        bool $autoRenew = true,
        ?int $createdBy = null,
        string $notes = '',
        int $billingCycleMonths = 1,
        bool $allowCustomPricing = false,
        ?float $negotiatedPriceAoa = null
      ): bool {
        $db = new self();
        $conn = $db->ConexaoDB();
        $plan = SubscriptionPlan::findByCode($planCode);

        if (!$plan || empty($plan['is_active'])) {
          return false;
        }

        if (!empty($plan['is_custom_pricing']) && !$allowCustomPricing) {
          return false;
        }

        $billingCycleMonths = max(1, min(12, $billingCycleMonths));
        $isPaidPlan = (float) ($plan['monthly_price_aoa'] ?? 0) > 0
            || ($allowCustomPricing && $negotiatedPriceAoa !== null && $negotiatedPriceAoa > 0);
        $effectiveAutoRenew = $isPaidPlan && !empty($plan['has_auto_renew']) ? $autoRenew : false;
        $startsAt = date('Y-m-d H:i:s');
        $endsAt = $isPaidPlan ? date('Y-m-d H:i:s', strtotime('+' . $billingCycleMonths . ' month')) : null;
        $negotiatedStored = ($negotiatedPriceAoa !== null && $negotiatedPriceAoa > 0) ? $negotiatedPriceAoa : null;
        $eventSource = $allowCustomPricing ? 'admin_manual_activation' : 'dashboard_change_plan';

        try {
          $conn->beginTransaction();

          $current = self::getCurrentByUser($userId);
          if ($current && (int) ($current['plan_id'] ?? 0) === (int) $plan['id']) {
            self::closeOpenSubscriptionsForUser($userId, (int) $current['id'], $conn, 'Consolidação de subscrição duplicada');
            $updateSql = "UPDATE {$db->table}
                    SET auto_renew = ?,
                        billing_cycle_months = ?,
                        negotiated_price_aoa = COALESCE(?, negotiated_price_aoa),
                        status = 'active',
                        grace_until = NULL,
                        notes = CASE WHEN ? <> '' THEN ? ELSE notes END
                    WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([
              $effectiveAutoRenew ? 1 : 0,
              $billingCycleMonths,
              $negotiatedStored,
              $notes,
              $notes,
              (int) $current['id'],
            ]);
            $conn->commit();
            return true;
          }

          self::closeOpenSubscriptionsForUser($userId, null, $conn, 'Substituição de plano');

          $insertSql = "INSERT INTO {$db->table}
                 (user_id, plan_id, status, starts_at, ends_at, auto_renew, billing_cycle_months,
                  negotiated_price_aoa, notes, created_by)
                  VALUES (?, ?, 'active', ?, ?, ?, ?, ?, ?, ?)";
          $insertStmt = $conn->prepare($insertSql);
          $insertStmt->execute([
            $userId,
            (int) $plan['id'],
            $startsAt,
            $endsAt,
            $effectiveAutoRenew ? 1 : 0,
            $billingCycleMonths,
            $negotiatedStored,
            $notes,
            $createdBy,
          ]);

          $eventType = 'activated';
          if ($current) {
            $currentWeight = (int) ($current['ranking_weight'] ?? 0);
            $newWeight = (int) ($plan['ranking_weight'] ?? 0);
            $eventType = $newWeight >= $currentWeight ? 'upgraded' : 'downgraded';
          }

          $eventSql = "INSERT INTO subscription_events
                (user_id, from_plan_id, to_plan_id, event_type, metadata, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)";
          $eventStmt = $conn->prepare($eventSql);
          $eventStmt->execute([
            $userId,
            $current ? (int) ($current['plan_id'] ?? 0) : null,
            (int) $plan['id'],
            $eventType,
            json_encode(['source' => $eventSource], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $createdBy,
          ]);

          $conn->commit();
          return true;
        } catch (\Throwable $e) {
          if ($conn->inTransaction()) {
            $conn->rollBack();
          }
          return false;
        }
      }

      public static function createPendingActivationForUser(
        int $userId,
        string $planCode,
        int $billingCycleMonths = 1,
        bool $autoRenew = false,
        ?int $createdBy = null,
        string $notes = ''
      ): int|false {
        $db = new self();
        $conn = $db->ConexaoDB();
        $plan = SubscriptionPlan::findByCode($planCode);

        if (!$plan || empty($plan['is_active'])) {
          return false;
        }

        if (!empty($plan['is_custom_pricing'])) {
          return false;
        }

        $billingCycleMonths = max(1, min(12, $billingCycleMonths));
        $isPaidPlan = (float) ($plan['monthly_price_aoa'] ?? 0) > 0;
        $effectiveAutoRenew = $isPaidPlan && !empty($plan['has_auto_renew']) ? $autoRenew : false;

        $startsAt = date('Y-m-d H:i:s');
        $endsAt = $isPaidPlan ? date('Y-m-d H:i:s', strtotime('+' . $billingCycleMonths . ' month')) : null;

        try {
          $conn->beginTransaction();

          self::closeOpenSubscriptionsForUser($userId, null, $conn, 'Nova solicitação de subscrição');

          $insertSql = "INSERT INTO {$db->table}
                (user_id, plan_id, status, starts_at, ends_at, auto_renew, billing_cycle_months, notes, created_by)
                VALUES (?, ?, 'pending_activation', ?, ?, ?, ?, ?, ?)";
          $insertStmt = $conn->prepare($insertSql);
          $insertStmt->execute([
            $userId,
            (int) $plan['id'],
            $startsAt,
            $endsAt,
            $effectiveAutoRenew ? 1 : 0,
            $billingCycleMonths,
            $notes,
            $createdBy,
          ]);

          $pendingId = (int) $conn->lastInsertId();

          $eventSql = "INSERT INTO subscription_events
                (user_id, from_plan_id, to_plan_id, event_type, metadata, created_by)
                VALUES (?, NULL, ?, 'manual_adjustment', ?, ?)";
          $eventStmt = $conn->prepare($eventSql);
          $eventStmt->execute([
            $userId,
            (int) $plan['id'],
            json_encode([
              'source' => 'dashboard_manual_checkout',
              'status' => 'pending_activation',
              'pending_subscription_id' => $pendingId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $createdBy,
          ]);

          $conn->commit();
          return $pendingId > 0 ? $pendingId : false;
        } catch (\Throwable $e) {
          if ($conn->inTransaction()) {
            $conn->rollBack();
          }
          return false;
        }
      }

      public static function activatePendingSubscriptionById(
        int $pendingSubscriptionId,
        ?int $activatedBy = null,
        string $notes = ''
      ): bool {
        $db = new self();
        $conn = $db->ConexaoDB();

        $sql = "SELECT us.*, sp.code AS plan_code, sp.monthly_price_aoa, sp.has_auto_renew, sp.ranking_weight
                FROM {$db->table} us
                INNER JOIN subscription_plans sp ON sp.id = us.plan_id
                WHERE us.id = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$pendingSubscriptionId]);
        $pending = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pending || (string) ($pending['status'] ?? '') !== 'pending_activation') {
          return false;
        }

        $userId = (int) ($pending['user_id'] ?? 0);
        if ($userId <= 0) {
          return false;
        }

        $months = max(1, min(12, (int) ($pending['billing_cycle_months'] ?? 1)));
        $isPaidPlan = (float) ($pending['monthly_price_aoa'] ?? 0) > 0;
        $effectiveAutoRenew = $isPaidPlan && !empty($pending['has_auto_renew']) ? !empty($pending['auto_renew']) : false;
        $startsAt = date('Y-m-d H:i:s');
        $endsAt = $isPaidPlan ? date('Y-m-d H:i:s', strtotime('+' . $months . ' month')) : null;

        try {
          $conn->beginTransaction();

          self::closeOpenSubscriptionsForUser($userId, $pendingSubscriptionId, $conn, 'Activada após confirmação de pagamento');

          $current = self::getCurrentByUser($userId);

          $activateSql = "UPDATE {$db->table}
                  SET status = 'active',
                      starts_at = ?,
                      ends_at = ?,
                      auto_renew = ?,
                      notes = CASE WHEN ? <> '' THEN CONCAT(COALESCE(notes, ''), ' | ', ?) ELSE notes END,
                      created_by = COALESCE(created_by, ?)
                  WHERE id = ? AND status = 'pending_activation'";
          $activateStmt = $conn->prepare($activateSql);
          $activateStmt->execute([
            $startsAt,
            $endsAt,
            $effectiveAutoRenew ? 1 : 0,
            $notes,
            $notes,
            $activatedBy,
            $pendingSubscriptionId,
          ]);

          if ($activateStmt->rowCount() === 0) {
            throw new \RuntimeException('pending_activation_not_updated');
          }

          $eventType = 'activated';
          if ($current) {
            $currentWeight = (int) ($current['ranking_weight'] ?? 0);
            $newWeight = (int) ($pending['ranking_weight'] ?? 0);
            $eventType = $newWeight >= $currentWeight ? 'upgraded' : 'downgraded';
          }

          $eventSql = "INSERT INTO subscription_events
                (user_id, from_plan_id, to_plan_id, event_type, metadata, created_by)
                VALUES (?, ?, ?, ?, ?, ?)";
          $eventStmt = $conn->prepare($eventSql);
          $eventStmt->execute([
            $userId,
            $current ? (int) ($current['plan_id'] ?? 0) : null,
            (int) ($pending['plan_id'] ?? 0),
            $eventType,
            json_encode([
              'source' => 'finance_payment_confirmation',
              'pending_subscription_id' => $pendingSubscriptionId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $activatedBy,
          ]);

          $conn->commit();
          return true;
        } catch (\Throwable $e) {
          if ($conn->inTransaction()) {
            $conn->rollBack();
          }
          return false;
        }
      }

      public static function renewActiveSubscriptionByPayment(
        int $subscriptionId,
        int $paymentTransactionId,
        ?int $confirmedBy = null,
        string $notes = ''
      ): bool {
        $db = new self();
        $conn = $db->ConexaoDB();

        $sql = "SELECT us.*, sp.code AS plan_code, sp.monthly_price_aoa, sp.ranking_weight
                FROM {$db->table} us
                INNER JOIN subscription_plans sp ON sp.id = us.plan_id
                WHERE us.id = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$subscriptionId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
          return false;
        }

        $status = (string) ($row['status'] ?? '');
        if ($status === 'pending_activation') {
          return false;
        }

        if (!in_array($status, ['active', 'past_due'], true)) {
          return false;
        }

        $userId = (int) ($row['user_id'] ?? 0);
        if ($userId <= 0) {
          return false;
        }

        try {
          $conn->beginTransaction();

          self::closeOpenSubscriptionsForUser($userId, $subscriptionId, $conn, 'Consolidação na renovação');

          $nextStartExpr = "COALESCE(ends_at, DATE_ADD(starts_at, INTERVAL billing_cycle_months MONTH), NOW())";
          $renewSql = "UPDATE {$db->table}
                  SET starts_at = {$nextStartExpr},
                      ends_at = DATE_ADD({$nextStartExpr}, INTERVAL billing_cycle_months MONTH),
                      status = 'active',
                      grace_until = NULL,
                      last_payment_transaction_id = ?,
                      notes = CASE WHEN ? <> '' THEN CONCAT(COALESCE(notes, ''), ' | ', ?) ELSE notes END
                  WHERE id = ?";
          $renewStmt = $conn->prepare($renewSql);
          $renewStmt->execute([
            $paymentTransactionId,
            $notes,
            $notes,
            $subscriptionId,
          ]);

          if ($renewStmt->rowCount() === 0) {
            throw new \RuntimeException('subscription_renew_not_updated');
          }

          $eventSql = "INSERT INTO subscription_events
                (user_id, from_plan_id, to_plan_id, event_type, metadata, created_by)
                 VALUES (?, ?, ?, 'renewed', ?, ?)";
          $eventStmt = $conn->prepare($eventSql);
          $eventStmt->execute([
            $userId,
            (int) ($row['plan_id'] ?? 0),
            (int) ($row['plan_id'] ?? 0),
            json_encode([
              'source' => 'finance_payment_confirmation',
              'subscription_id' => $subscriptionId,
              'payment_transaction_id' => $paymentTransactionId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $confirmedBy,
          ]);

          $conn->commit();

          try {
            Notification::notifyUser(
              $userId,
              'subscription_renewed',
              'Plano renovado com sucesso',
              'O seu plano foi renovado após confirmação do pagamento.',
              ['subscription_id' => $subscriptionId, 'payment_transaction_id' => $paymentTransactionId]
            );
          } catch (\Throwable $ignored) {}

          return true;
        } catch (\Throwable $e) {
          if ($conn->inTransaction()) {
            $conn->rollBack();
          }
          return false;
        }
      }

      public static function cancelPendingSubscriptionById(
        int $pendingSubscriptionId,
        string $notes = ''
      ): bool {
        $db = new self();
        $sql = "UPDATE {$db->table}
                SET status = 'cancelled', auto_renew = 0,
                    notes = CASE WHEN ? <> '' THEN CONCAT(COALESCE(notes, ''), ' | ', ?) ELSE notes END
                WHERE id = ? AND status = 'pending_activation'";
        $stmt = $db->prepare($sql);
        $ok = $stmt->execute([$notes, $notes, $pendingSubscriptionId]);
        return $ok && $stmt->rowCount() > 0;
      }

      public static function runRenewalCycle(int $limit = 200): array {
        $db = new self();
        $conn = $db->ConexaoDB();
        $limit = max(1, min(1000, $limit));

        $stats = [
          'invoiced' => 0,
          'skipped' => 0,
          'failed' => 0,
          'downgraded' => 0,
        ];

        $graceDays = max(1, (int) \Src\classes\ClassSettings::int('subscription_grace_days', 5));

        $dueSql = "SELECT us.*, sp.code AS plan_code, sp.name AS plan_name, sp.monthly_price_aoa
               FROM {$db->table} us
               INNER JOIN subscription_plans sp ON sp.id = us.plan_id
               WHERE us.status = 'active'
               AND us.auto_renew = 1
               AND sp.monthly_price_aoa > 0
               AND (
                (us.ends_at IS NOT NULL AND us.ends_at <= NOW())
                OR (us.ends_at IS NULL AND DATE_ADD(us.starts_at, INTERVAL us.billing_cycle_months MONTH) <= NOW())
               )
               ORDER BY us.id ASC
               LIMIT {$limit}";
        $dueStmt = $conn->prepare($dueSql);
        $dueStmt->execute();
        $dueRows = $dueStmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($dueRows as $row) {
          $subscriptionId = (int) ($row['id'] ?? 0);
          $userId = (int) ($row['user_id'] ?? 0);
          $months = max(1, (int) ($row['billing_cycle_months'] ?? 1));
          $basePrice = (float) ($row['monthly_price_aoa'] ?? 0);
          $negotiated = isset($row['negotiated_price_aoa']) ? (float) $row['negotiated_price_aoa'] : null;
          $amount = max(0.0, $negotiated !== null && $negotiated > 0 ? $negotiated : ($basePrice * $months));

          if ($subscriptionId <= 0 || $userId <= 0 || $amount <= 0) {
            $stats['failed']++;
            continue;
          }

          self::closeOpenSubscriptionsForUser($userId, $subscriptionId, $conn, 'Consolidação antes de renovação');

          if (PaymentTransaction::hasOpenSubscriptionInvoice($subscriptionId)) {
            $stats['skipped']++;
            continue;
          }

          try {
            $paymentId = PaymentTransaction::create([
              'transaction_type' => 'subscription_fee',
              'direction' => 'incoming',
              'status' => 'pendente',
              'amount' => $amount,
              'currency' => 'AOA',
              'counterparty_user_id' => $userId,
              'related_entity_type' => 'user_subscription',
              'related_entity_id' => $subscriptionId,
              'notes' => 'Renovação do plano ' . (string) ($row['plan_name'] ?? '') . ' (' . $months . ' mês(es)) — aguarda confirmação financeira',
            ]);

            if (!$paymentId) {
              throw new \RuntimeException('payment_transaction_failed');
            }

            $pastDueSql = "UPDATE {$db->table}
                    SET status = 'past_due', grace_until = DATE_ADD(NOW(), INTERVAL ? DAY)
                    WHERE id = ? AND status = 'active'";
            $pastDueStmt = $conn->prepare($pastDueSql);
            $pastDueStmt->execute([$graceDays, $subscriptionId]);

            $eventSql = "INSERT INTO subscription_events
                  (user_id, from_plan_id, to_plan_id, event_type, metadata)
                   VALUES (?, ?, ?, 'manual_adjustment', ?)";
            $eventStmt = $conn->prepare($eventSql);
            $eventStmt->execute([
              $userId,
              (int) ($row['plan_id'] ?? 0),
              (int) ($row['plan_id'] ?? 0),
              json_encode([
                'source' => 'renewal_scheduler',
                'subscription_id' => $subscriptionId,
                'amount' => $amount,
                'months' => $months,
                'payment_transaction_id' => (int) $paymentId,
                'grace_days' => $graceDays,
              ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            $stats['invoiced']++;

            try {
              Notification::notifyUser(
                $userId,
                'subscription_renewal_due',
                'Renovação do plano pendente',
                'O seu plano ' . (string) ($row['plan_name'] ?? '') . ' venceu. Foi gerada uma cobrança de '
                  . number_format($amount, 0, ',', '.') . ' Kz. Tem ' . $graceDays
                  . ' dias de carência — regularize em Pagamentos ou contacte o suporte.',
                [
                  'subscription_id' => $subscriptionId,
                  'amount' => $amount,
                  'payment_transaction_id' => (int) $paymentId,
                  'grace_days' => $graceDays,
                ]
              );
            } catch (\Throwable $ignored) {}

          } catch (\Throwable $e) {
            $stats['failed']++;
          }
        }

        $essential = SubscriptionPlan::findByCode('essential');
        if ($essential) {
          $expiredSql = "SELECT us.id, us.user_id, us.plan_id
                  FROM {$db->table} us
                  INNER JOIN subscription_plans sp ON sp.id = us.plan_id
                  WHERE sp.code <> 'essential'
                  AND (
                    (us.status = 'past_due' AND us.grace_until IS NOT NULL AND us.grace_until < NOW())
                    OR (us.status = 'active' AND us.auto_renew = 0 AND us.ends_at IS NOT NULL AND us.ends_at < NOW())
                  )
                  ORDER BY us.id ASC
                  LIMIT {$limit}";
          $expiredStmt = $conn->prepare($expiredSql);
          $expiredStmt->execute();
          $expiredRows = $expiredStmt->fetchAll(\PDO::FETCH_ASSOC);

          foreach ($expiredRows as $row) {
            $ok = self::activatePlanForUser(
              (int) ($row['user_id'] ?? 0),
              'essential',
              false,
              null,
              'Downgrade automatico por expiracao/inadimplencia'
            );
            if ($ok) {
              $stats['downgraded']++;
              try {
                Notification::notifyUser(
                  (int) ($row['user_id'] ?? 0),
                  'subscription_downgraded',
                  'Plano alterado para Essencial',
                  'O seu plano foi rebaixado para Essencial por expiração ou inadimplência. Renove para recuperar as funcionalidades.',
                  ['subscription_id' => (int) ($row['id'] ?? 0)]
                );
              } catch (\Throwable $ignored) {}
            }
          }
        }

        return $stats;
      }
}
