<?php

namespace Src\classes;

use App\model\Commission;

/**
 * Per-request memoization for expensive cross-cutting lookups.
 */
class RequestContext
{
    /** @var array<int, string|null> */
    private static array $commissionBlockReason = [];

    public static function commissionBlockReason(int $ownerId): ?string
    {
        if ($ownerId <= 0) {
            return null;
        }

        if (!array_key_exists($ownerId, self::$commissionBlockReason)) {
            self::$commissionBlockReason[$ownerId] = Commission::getOverdueBlockReason($ownerId);
        }

        return self::$commissionBlockReason[$ownerId];
    }

    public static function clearCommissionBlockReason(int $ownerId): void
    {
        if ($ownerId > 0) {
            unset(self::$commissionBlockReason[$ownerId]);
        }
    }
}
