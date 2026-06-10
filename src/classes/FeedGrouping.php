<?php

namespace Src\classes;

/**
 * Temporal grouping for user-facing activity feeds (inbox, requests, chats).
 * Not intended for admin desktop-only table views.
 */
class FeedGrouping
{
    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>
     */
    public static function byRecency(array $items, string $dateField = 'created_at', ?int $now = null): array
    {
        $groups = [
            ['key' => 'today', 'label' => 'Hoje', 'items' => []],
            ['key' => 'week', 'label' => 'Esta semana', 'items' => []],
            ['key' => 'earlier', 'label' => 'Anteriores', 'items' => []],
        ];

        $now = $now ?? time();

        foreach ($items as $item) {
            $timestamp = strtotime((string) ($item[$dateField] ?? ''));
            if ($timestamp === false) {
                $groups[2]['items'][] = $item;
                continue;
            }

            if (date('Y-m-d', $timestamp) === date('Y-m-d', $now)) {
                $groups[0]['items'][] = $item;
                continue;
            }

            if ($timestamp >= strtotime('-7 days', $now)) {
                $groups[1]['items'][] = $item;
                continue;
            }

            $groups[2]['items'][] = $item;
        }

        return array_values(array_filter(
            $groups,
            static fn (array $group): bool => !empty($group['items'])
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param callable(array<string, mixed>): bool $isUnread
     * @param callable(array<string, mixed>): int|false $timestampResolver
     *
     * @return array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>
     */
    public static function byRecencyWithUnreadBucket(
        array $items,
        callable $isUnread,
        callable $timestampResolver,
        ?int $now = null
    ): array {
        $groups = [
            ['key' => 'unread', 'label' => 'Com mensagens novas', 'items' => []],
            ['key' => 'today', 'label' => 'Hoje', 'items' => []],
            ['key' => 'week', 'label' => 'Esta semana', 'items' => []],
            ['key' => 'earlier', 'label' => 'Anteriores', 'items' => []],
        ];

        $now = $now ?? time();

        foreach ($items as $item) {
            if ($isUnread($item)) {
                $groups[0]['items'][] = $item;
                continue;
            }

            $timestamp = $timestampResolver($item);
            if ($timestamp === false) {
                $groups[3]['items'][] = $item;
                continue;
            }

            if (date('Y-m-d', $timestamp) === date('Y-m-d', $now)) {
                $groups[1]['items'][] = $item;
                continue;
            }

            if ($timestamp >= strtotime('-7 days', $now)) {
                $groups[2]['items'][] = $item;
                continue;
            }

            $groups[3]['items'][] = $item;
        }

        return array_values(array_filter(
            $groups,
            static fn (array $group): bool => !empty($group['items'])
        ));
    }

    /**
     * Groups items by payment due urgency (pending commissions, etc.).
     *
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>
     */
    public static function byDueUrgency(array $items, string $dateField = 'due_at', ?int $now = null): array
    {
        $groups = [
            ['key' => 'overdue', 'label' => 'Em atraso', 'items' => []],
            ['key' => 'today', 'label' => 'Vence hoje', 'items' => []],
            ['key' => 'week', 'label' => 'Esta semana', 'items' => []],
            ['key' => 'later', 'label' => 'Mais tarde', 'items' => []],
        ];

        $now = $now ?? time();

        foreach ($items as $item) {
            $timestamp = strtotime((string) ($item[$dateField] ?? ''));
            if ($timestamp === false) {
                $groups[3]['items'][] = $item;
                continue;
            }

            if ($timestamp < $now) {
                $groups[0]['items'][] = $item;
                continue;
            }

            if (date('Y-m-d', $timestamp) === date('Y-m-d', $now)) {
                $groups[1]['items'][] = $item;
                continue;
            }

            if ($timestamp <= strtotime('+7 days', $now)) {
                $groups[2]['items'][] = $item;
                continue;
            }

            $groups[3]['items'][] = $item;
        }

        return array_values(array_filter(
            $groups,
            static fn (array $group): bool => !empty($group['items'])
        ));
    }
}
