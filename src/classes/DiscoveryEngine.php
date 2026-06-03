<?php

namespace Src\classes;

use App\model\ManipularBanco;
use App\model\Property;
use App\model\PropertyImpression;

class DiscoveryEngine
{
    public const SURFACE_HOME_CAROUSEL = 'home_carousel';
    public const SURFACE_PROPERTY_LIST = 'property_list';
    public const SURFACE_FEATURED_LIST = 'featured_list';
    public const SURFACE_CONTINUE = 'continue_exploring';
    public const SURFACE_FOR_YOU = 'for_you';

    public static function isActive(?int $viewerUserId, ?string $viewerVisitorKey): bool
    {
        if (!ClassCookieConsent::hasBehavioralConsent()) {
            return false;
        }
        if (ClassSettings::int('behavior_ranking_enabled', 0) !== 1) {
            return false;
        }
        $viewerUserId = (int) ($viewerUserId ?? 0);
        $visitorKey = trim((string) ($visitorVisitorKey ?? ''));
        return $viewerUserId > 0 || $visitorKey !== '';
    }

    /**
     * @return array{sql:string,params:array<int,mixed>}
     */
    public static function buildBehaviorScoreSelect(string $propertyIdExpr = 'p.id'): array
    {
        $lookbackDays = max(7, ClassSettings::int('behavior_ranking_lookback_days', 90));
        $weightView = max(0, ClassSettings::int('behavior_weight_view', 1));
        $weightFavorite = max(0, ClassSettings::int('behavior_weight_favorite', 4));
        $weightRequest = max(0, ClassSettings::int('behavior_weight_request', 8));
        $maxScore = max(1, ClassSettings::int('behavior_max_score_per_property', 50));
        $decayLambda = max(0.001, ClassSettings::float('behavior_decay_lambda', 0.035));
        $viewPenaltyThreshold = max(2, ClassSettings::int('behavior_view_penalty_threshold', 4));
        $viewPenaltyPoints = max(0, ClassSettings::int('behavior_view_penalty_points', 6));

        return [
            'sql' => '',
            'params' => [],
            'lookback_days' => $lookbackDays,
            'weight_view' => $weightView,
            'weight_favorite' => $weightFavorite,
            'weight_request' => $weightRequest,
            'max_score' => $maxScore,
            'decay_lambda' => $decayLambda,
            'view_penalty_threshold' => $viewPenaltyThreshold,
            'view_penalty_points' => $viewPenaltyPoints,
            'property_id_expr' => $propertyIdExpr,
        ];
    }

    /**
     * SQL fragment for viewer_behavior_score with temporal decay and view-without-conversion penalty.
     *
     * @return array{sql:string,params:array<int,mixed>}
     */
    public static function behaviorScoreSql(
        int $viewerUserId,
        string $viewerVisitorKey,
        string $propertyIdExpr = 'p.id'
    ): array {
        $cfg = self::buildBehaviorScoreSelect($propertyIdExpr);
        $lookbackDays = $cfg['lookback_days'];
        $wv = $cfg['weight_view'];
        $wf = $cfg['weight_favorite'];
        $wr = $cfg['weight_request'];
        $maxScore = $cfg['max_score'];
        $lambda = $cfg['decay_lambda'];
        $threshold = $cfg['view_penalty_threshold'];
        $penalty = $cfg['view_penalty_points'];

        if ($viewerUserId > 0 && $viewerVisitorKey !== '') {
            $behaviorWhere = 'pbe.user_id = ? OR pbe.visitor_key = ?';
            $identityBind = [$viewerUserId, $viewerVisitorKey];
            $penaltyIdentity = '(pbev.user_id = ? OR pbev.visitor_key = ?)';
            $penaltyIdentity2 = '(pbec.user_id = ? OR pbec.visitor_key = ?)';
        } elseif ($viewerUserId > 0) {
            $behaviorWhere = 'pbe.user_id = ?';
            $identityBind = [$viewerUserId];
            $penaltyIdentity = 'pbev.user_id = ?';
            $penaltyIdentity2 = 'pbec.user_id = ?';
        } else {
            $behaviorWhere = 'pbe.visitor_key = ?';
            $identityBind = [$viewerVisitorKey];
            $penaltyIdentity = 'pbev.visitor_key = ?';
            $penaltyIdentity2 = 'pbec.visitor_key = ?';
        }

        $params = array_merge($identityBind, $identityBind, $identityBind);

        $sql = "LEAST(GREATEST(
            COALESCE((
                SELECT SUM(
                    (CASE pbe.event_type
                        WHEN 'view' THEN {$wv}
                        WHEN 'favorite' THEN {$wf}
                        WHEN 'request' THEN {$wr}
                        ELSE 0
                    END) * EXP(-{$lambda} * GREATEST(0, TIMESTAMPDIFF(DAY, pbe.created_at, NOW())))
                )
                FROM property_behavior_events pbe
                WHERE pbe.property_id = {$propertyIdExpr}
                  AND ({$behaviorWhere})
                  AND pbe.created_at >= DATE_SUB(NOW(), INTERVAL {$lookbackDays} DAY)
            ), 0)
            - COALESCE((
                SELECT CASE
                    WHEN COUNT(*) >= {$threshold}
                     AND NOT EXISTS (
                        SELECT 1 FROM property_behavior_events pbec
                        WHERE pbec.property_id = {$propertyIdExpr}
                          AND pbec.event_type IN ('favorite', 'request')
                          AND {$penaltyIdentity2}
                          AND pbec.created_at >= DATE_SUB(NOW(), INTERVAL {$lookbackDays} DAY)
                     )
                    THEN {$penalty}
                    ELSE 0
                END
                FROM property_behavior_events pbev
                WHERE pbev.property_id = {$propertyIdExpr}
                  AND pbev.event_type = 'view'
                  AND {$penaltyIdentity}
                  AND pbev.created_at >= DATE_SUB(NOW(), INTERVAL {$lookbackDays} DAY)
            ), 0),
        0), {$maxScore}) AS viewer_behavior_score";

        return ['sql' => $sql, 'params' => $params];
    }

    public static function discoveryOrderSql(): string
    {
        $seed = ClassSession::getDiscoverySeed();
        return 'CRC32(CONCAT(p.id, ' . self::sqlQuote($seed) . '))';
    }

    private static function sqlQuote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * Cache / filter fingerprint for personalized queries.
     */
    public static function viewerFingerprint(?int $viewerUserId, ?string $visitorKey): string
    {
        $viewerUserId = (int) ($viewerUserId ?? 0);
        $visitorKey = trim((string) ($visitorKey ?? ''));
        if (!self::isActive($viewerUserId, $visitorKey)) {
            return 'discovery:off';
        }

        return implode(':', [
            'u' . $viewerUserId,
            'v' . substr(hash('sha256', $visitorKey), 0, 12),
            'p' . ClassSession::getBehaviorProfileVersion(),
            'd' . date('Y-m-d'),
            's' . substr(ClassSession::getDiscoverySeed(), 0, 8),
        ]);
    }

    /**
     * Re-rank rows that already include viewer_behavior_score; apply cooldown + MMR-lite + explore slots.
     *
     * @param array<int,array<string,mixed>> $properties
     * @return array<int,array<string,mixed>>
     */
    public static function diversifySelection(
        array $properties,
        ?int $viewerUserId,
        ?string $visitorKey,
        string $surface,
        int $limit
    ): array {
        $limit = max(1, $limit);
        if (empty($properties)) {
            return [];
        }

        $cooldownHours = max(1, ClassSettings::int('behavior_impression_cooldown_hours', 24));
        $excludeIds = PropertyImpression::getRecentPropertyIds($viewerUserId, $visitorKey, $surface, $cooldownHours);
        $excludeMap = array_fill_keys($excludeIds, true);

        $candidates = array_values(array_filter($properties, static function (array $row) use ($excludeMap): bool {
            $id = (int) ($row['id'] ?? 0);
            return $id > 0 && !isset($excludeMap[$id]);
        }));

        if (count($candidates) < $limit) {
            $candidates = array_values($properties);
        }

        usort($candidates, static function (array $a, array $b): int {
            $scoreCmp = ((float) ($b['viewer_behavior_score'] ?? 0)) <=> ((float) ($a['viewer_behavior_score'] ?? 0));
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }
            $seed = ClassSession::getDiscoverySeed();
            $ha = crc32((string) ($a['id'] ?? '') . $seed);
            $hb = crc32((string) ($b['id'] ?? '') . $seed);
            return $hb <=> $ha;
        });

        $exploreRatio = min(30, max(0, ClassSettings::int('behavior_explore_ratio', 15)));
        $exploreSlots = (int) floor($limit * $exploreRatio / 100);
        $exploitSlots = max(0, $limit - $exploreSlots);

        $selected = [];
        $selectedIds = [];

        $lowScorePool = array_values(array_filter($candidates, static function (array $row): bool {
            return ((float) ($row['viewer_behavior_score'] ?? 0)) < 1.0;
        }));

        for ($i = 0; $i < $exploreSlots && !empty($lowScorePool); $i++) {
            $pick = self::pickDiverse($lowScorePool, $selected);
            if ($pick === null) {
                break;
            }
            $selected[] = $pick;
            $selectedIds[(int) $pick['id']] = true;
            $lowScorePool = array_values(array_filter($lowScorePool, static function (array $row) use ($pick): bool {
                return (int) ($row['id'] ?? 0) !== (int) ($pick['id'] ?? 0);
            }));
        }

        $remaining = array_values(array_filter($candidates, static function (array $row) use ($selectedIds): bool {
            return !isset($selectedIds[(int) ($row['id'] ?? 0)]);
        }));

        while (count($selected) < $limit && !empty($remaining)) {
            $pick = self::pickDiverse($remaining, $selected);
            if ($pick === null) {
                break;
            }
            $selected[] = $pick;
            $selectedIds[(int) $pick['id']] = true;
            $remaining = array_values(array_filter($remaining, static function (array $row) use ($pick): bool {
                return (int) ($row['id'] ?? 0) !== (int) ($pick['id'] ?? 0);
            }));
        }

        if (count($selected) < $limit) {
            foreach ($candidates as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0 || isset($selectedIds[$id])) {
                    continue;
                }
                $selected[] = $row;
                $selectedIds[$id] = true;
                if (count($selected) >= $limit) {
                    break;
                }
            }
        }

        $ids = array_map(static function (array $row): int {
            return (int) ($row['id'] ?? 0);
        }, array_slice($selected, 0, $limit));

        PropertyImpression::recordMany($viewerUserId, $visitorKey, $ids, $surface);

        return array_slice($selected, 0, $limit);
    }

    /**
     * @param array<int,array<string,mixed>> $pool
     * @param array<int,array<string,mixed>> $selected
     */
    private static function pickDiverse(array $pool, array $selected): ?array
    {
        $best = null;
        $bestValue = -INF;

        foreach ($pool as $candidate) {
            $score = (float) ($candidate['viewer_behavior_score'] ?? 0);
            $similarity = self::maxSimilarity($candidate, $selected);
            $value = $score - ($similarity * 3.5);
            if ($value > $bestValue) {
                $bestValue = $value;
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @param array<int,array<string,mixed>> $selected
     */
    private static function maxSimilarity(array $candidate, array $selected): float
    {
        if (empty($selected)) {
            return 0.0;
        }

        $max = 0.0;
        foreach ($selected as $other) {
            $max = max($max, self::similarity($candidate, $other));
        }
        return $max;
    }

    private static function similarity(array $a, array $b): float
    {
        $sim = 0.0;
        if (!empty($a['region_id']) && (int) $a['region_id'] === (int) ($b['region_id'] ?? 0)) {
            $sim += 0.45;
        }
        if (!empty($a['type']) && (string) $a['type'] === (string) ($b['type'] ?? '')) {
            $sim += 0.35;
        }
        $priceA = (float) ($a['price'] ?? 0);
        $priceB = (float) ($b['price'] ?? 0);
        if ($priceA > 0 && $priceB > 0) {
            $delta = abs($priceA - $priceB) / max($priceA, $priceB);
            if ($delta <= 0.15) {
                $sim += 0.35;
            } elseif ($delta <= 0.3) {
                $sim += 0.15;
            }
        }
        if (!empty($a['purpose']) && (string) $a['purpose'] === (string) ($b['purpose'] ?? '')) {
            $sim += 0.15;
        }
        return min(1.0, $sim);
    }

    /**
     * Split pool into promoted (featured) and organic listings.
     *
     * @param array<int,array<string,mixed>> $pool
     * @return array{0:array<int,array<string,mixed>>,1:array<int,array<string,mixed>>}
     */
    public static function splitByFeatured(array $pool): array
    {
        $promoted = [];
        $organic = [];
        foreach ($pool as $row) {
            if (!empty($row['featured'])) {
                $promoted[] = $row;
            } else {
                $organic[] = $row;
            }
        }
        return [$promoted, $organic];
    }

    /**
     * Interleave promoted properties among organic ones (badge stays on promoted cards in the view).
     *
     * @param array<int,array<string,mixed>> $organic
     * @param array<int,array<string,mixed>> $promoted
     * @return array<int,array<string,mixed>>
     */
    public static function blendPromotedInto(array $organic, array $promoted, int $limit, int $interval = 4): array
    {
        $limit = max(1, $limit);
        $interval = max(2, $interval);
        $organic = array_values($organic);
        $promoted = array_values($promoted);

        if (empty($promoted)) {
            return array_slice($organic, 0, $limit);
        }
        if (empty($organic)) {
            return array_slice($promoted, 0, $limit);
        }

        $result = [];
        $organicIndex = 0;
        $promotedIndex = 0;
        $position = 0;

        while (count($result) < $limit && ($organicIndex < count($organic) || $promotedIndex < count($promoted))) {
            $shouldPlacePromoted = $promotedIndex < count($promoted)
                && $position > 0
                && ($position % $interval) === 0;

            if ($shouldPlacePromoted) {
                $result[] = $promoted[$promotedIndex++];
            } elseif ($organicIndex < count($organic)) {
                $result[] = $organic[$organicIndex++];
            } elseif ($promotedIndex < count($promoted)) {
                $result[] = $promoted[$promotedIndex++];
            }

            $position++;
        }

        return array_slice($result, 0, $limit);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,personalized:bool}
     */
    public static function resolveMixedFeed(
        array $filters,
        ?int $viewerUserId,
        ?string $visitorKey,
        string $surface,
        int $limit,
        int $poolSize,
        int $offset = 0
    ): array {
        $limit = max(1, $limit);
        $poolSize = max($limit, $poolSize);
        $viewerUserId = (int) ($viewerUserId ?? 0);
        $visitorKey = trim((string) ($visitorKey ?? ''));

        if (!self::isActive($viewerUserId, $visitorKey)) {
            return [
                'items' => Property::getFiltered($filters, $limit, $offset),
                'personalized' => false,
            ];
        }

        $filters['discovery_blend'] = true;
        $pool = Property::getFiltered($filters, $poolSize, $offset);
        [$promoted, $organic] = self::splitByFeatured($pool);

        $interval = max(3, ClassSettings::int('behavior_promoted_interval', 4));
        $maxPromotedSlots = max(1, (int) ceil($limit / $interval));
        $promotedPick = self::diversifySelection(
            $promoted,
            $viewerUserId,
            $visitorKey,
            $surface . '_promoted',
            min(count($promoted), $maxPromotedSlots)
        );

        $organicTarget = max($limit, $limit + 6);
        $organicPick = self::diversifySelection($organic, $viewerUserId, $visitorKey, $surface, $organicTarget);
        $items = self::blendPromotedInto($organicPick, $promotedPick, $limit, $interval);

        return [
            'items' => $items,
            'personalized' => true,
        ];
    }

    /**
     * Personalized home carousel: promoted + organic mixed with Patrocinado badge on featured.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function homeCarousel(
        ?int $viewerUserId,
        ?string $visitorKey,
        int $displayLimit
    ): array {
        $displayLimit = max(1, $displayLimit);
        $filters = [];
        if ($viewerUserId > 0) {
            $filters['viewer_user_id'] = $viewerUserId;
        }
        if ($visitorKey !== '') {
            $filters['viewer_visitor_key'] = $visitorKey;
        }

        $result = self::resolveMixedFeed(
            $filters,
            $viewerUserId,
            $visitorKey,
            self::SURFACE_HOME_CAROUSEL,
            $displayLimit,
            max($displayLimit * 4, 24),
            0
        );

        return $result['items'];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{
     *   properties:array<int,array<string,mixed>>,
     *   discoveryPersonalized:bool,
     *   continueExploring:array<int,array<string,mixed>>
     * }
     */
    public static function propertyListingPage(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $viewerUserId = (int) ($filters['viewer_user_id'] ?? 0);
        $visitorKey = trim((string) ($filters['viewer_visitor_key'] ?? ''));

        $continueLimit = max(3, ClassSettings::int('behavior_continue_exploring_size', 6));
        $continueExploring = self::continueExploring($viewerUserId, $visitorKey, $continueLimit);

        $feed = self::resolveMixedFeed(
            $filters,
            $viewerUserId,
            $visitorKey,
            self::SURFACE_PROPERTY_LIST,
            $perPage,
            max($perPage * 4, 36),
            $offset
        );

        return [
            'properties' => $feed['items'],
            'discoveryPersonalized' => $feed['personalized'],
            'continueExploring' => $continueExploring,
        ];
    }

    /**
     * Featured-only listing (/featured) with discovery rotation on promoted inventory.
     *
     * @return array{
     *   properties:array<int,array<string,mixed>>,
     *   discoveryPersonalized:bool,
     *   continueExploring:array<int,array<string,mixed>>
     * }
     */
    public static function featuredListingPage(int $page, int $perPage, ?int $viewerUserId, ?string $visitorKey): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $viewerUserId = (int) ($viewerUserId ?? 0);
        $visitorKey = trim((string) ($visitorKey ?? ''));

        $continueLimit = max(3, ClassSettings::int('behavior_continue_exploring_size', 6));
        $continueExploring = self::continueExploring($viewerUserId, $visitorKey, $continueLimit);

        if (!self::isActive($viewerUserId, $visitorKey)) {
            return [
                'properties' => Property::getFeatured($perPage, $offset, $viewerUserId ?: null, $visitorKey !== '' ? $visitorKey : null),
                'discoveryPersonalized' => false,
                'continueExploring' => $continueExploring,
            ];
        }

        $poolSize = max($perPage * 4, 36);
        $pool = Property::getFeatured($poolSize, $offset, $viewerUserId, $visitorKey);
        $properties = self::diversifySelection($pool, $viewerUserId, $visitorKey, self::SURFACE_FEATURED_LIST, $perPage);

        return [
            'properties' => $properties,
            'discoveryPersonalized' => true,
            'continueExploring' => $continueExploring,
        ];
    }

    /**
     * Properties the user viewed recently but did not favorite or request.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function continueExploring(?int $viewerUserId, ?string $visitorKey, int $limit): array
    {
        $limit = max(1, min(12, $limit));
        $viewerUserId = (int) ($viewerUserId ?? 0);
        $visitorKey = trim((string) ($visitorKey ?? ''));
        if (!self::isActive($viewerUserId, $visitorKey)) {
            return [];
        }

        $lookbackDays = max(7, ClassSettings::int('behavior_ranking_lookback_days', 90));
        $db = new ManipularBanco();

        if ($viewerUserId > 0 && $visitorKey !== '') {
            $identityWhere = '(pbe.user_id = ? OR pbe.visitor_key = ?)';
            $identityParams = [$viewerUserId, $visitorKey];
            $noConversion = '(pbec.user_id = ? OR pbec.visitor_key = ?)';
            $noConversionParams = [$viewerUserId, $visitorKey];
        } elseif ($viewerUserId > 0) {
            $identityWhere = 'pbe.user_id = ?';
            $identityParams = [$viewerUserId];
            $noConversion = 'pbec.user_id = ?';
            $noConversionParams = [$viewerUserId];
        } else {
            $identityWhere = 'pbe.visitor_key = ?';
            $identityParams = [$visitorKey];
            $noConversion = 'pbec.visitor_key = ?';
            $noConversionParams = [$visitorKey];
        }

        $requestBlock = '';
        $requestParams = [];
        if ($viewerUserId > 0) {
            $requestBlock = ' AND NOT EXISTS (
                SELECT 1 FROM requests r
                WHERE r.property_id = p.id AND r.user_id = ?
            )';
            $requestParams[] = $viewerUserId;
        }

        $sql = "SELECT p.*, u.username AS owner_username, u.name AS owner_name, u.phone AS owner_phone, lv.last_viewed_at
                FROM properties p
                INNER JOIN (
                    SELECT property_id, MAX(created_at) AS last_viewed_at
                    FROM property_behavior_events pbe
                    WHERE pbe.event_type = 'view'
                      AND {$identityWhere}
                      AND pbe.created_at >= DATE_SUB(NOW(), INTERVAL {$lookbackDays} DAY)
                    GROUP BY property_id
                ) lv ON lv.property_id = p.id
                LEFT JOIN users u ON p.affiliate_id = u.id
                WHERE p.status = 'disponivel'
                  AND NOT EXISTS (
                      SELECT 1 FROM property_behavior_events pbec
                      WHERE pbec.property_id = p.id
                        AND pbec.event_type IN ('favorite', 'request')
                        AND {$noConversion}
                        AND pbec.created_at >= DATE_SUB(NOW(), INTERVAL {$lookbackDays} DAY)
                  )
                  {$requestBlock}
                ORDER BY lv.last_viewed_at DESC
                LIMIT " . (int) ($limit * 2);

        $params = array_merge($identityParams, $noConversionParams, $requestParams);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row['viewer_behavior_score'] = 1;
        }
        unset($row);

        return self::diversifySelection($rows, $viewerUserId, $visitorKey, self::SURFACE_CONTINUE, $limit);
    }
}
