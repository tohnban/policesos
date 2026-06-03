<?php

namespace App\model;

class SubscriptionPlan extends ManipularBanco
{
    protected $table = 'subscription_plans';

    public static function findById(int $id): ?array
    {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE id = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE code = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([trim(strtolower($code))]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getActiveCatalog(): array
    {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE is_active = 1 ORDER BY ranking_weight ASC, id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
