<?php

namespace App\model;

class Country extends ManipularBanco
{
    protected $table = 'countries';

    public static function getActive(): array
    {
        try {
            $db = new self();
            $sql = "SELECT id, code, name FROM {$db->table} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function exists(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            $db = new self();
            $sql = "SELECT 1 FROM {$db->table} WHERE id = ? AND is_active = 1 LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
