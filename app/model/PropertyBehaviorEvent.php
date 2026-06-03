<?php

namespace App\model;

use Src\classes\ClassCookieConsent;
use Src\classes\ClassSession;

class PropertyBehaviorEvent extends ManipularBanco
{
    protected $table = 'property_behavior_events';

    private const EVENT_TYPES = ['view', 'favorite', 'request'];

    public static function track(?int $userId, int $propertyId, string $eventType, ?string $visitorKey = null): bool
    {
        if (!ClassCookieConsent::hasBehavioralConsent()) {
            return false;
        }

        if ($propertyId <= 0 || !in_array($eventType, self::EVENT_TYPES, true)) {
            return false;
        }

        $userId = (int) ($userId ?? 0);
        $visitorKey = trim((string) ($visitorKey ?? ''));

        if ($userId <= 0 && $visitorKey === '') {
            return false;
        }

        $db = new self();

        // Avoid noisy duplicates for passive views.
        if ($eventType === 'view') {
            if ($userId > 0) {
                $dedupeSql = "SELECT id FROM {$db->table}
                              WHERE user_id = ? AND property_id = ? AND event_type = 'view'
                                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                              LIMIT 1";
                $dedupeParams = [$userId, $propertyId];
            } else {
                $dedupeSql = "SELECT id FROM {$db->table}
                              WHERE visitor_key = ? AND property_id = ? AND event_type = 'view'
                                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                              LIMIT 1";
                $dedupeParams = [$visitorKey, $propertyId];
            }
            $dedupeStmt = $db->prepare($dedupeSql);
            $dedupeStmt->execute($dedupeParams);
            if ($dedupeStmt->fetch(\PDO::FETCH_ASSOC)) {
                return true;
            }
        }

        $sql = "INSERT INTO {$db->table} (user_id, visitor_key, property_id, event_type, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);

        $ok = $stmt->execute([
            $userId > 0 ? $userId : null,
            $visitorKey !== '' ? $visitorKey : null,
            $propertyId,
            $eventType,
        ]);

        if ($ok) {
            ClassSession::bumpBehaviorProfileVersion();
        }

        return $ok;
    }
}
