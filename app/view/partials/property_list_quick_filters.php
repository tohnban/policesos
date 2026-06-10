<?php
/**
 * Quick filter pills for /properties (mobile-first horizontal scroll).
 *
 * @var string $purpose
 * @var string $type
 * @var array<string, string> $purposeLabels
 * @var array<string, string> $typeFilterOptions
 * @var array<string, mixed> $queryParams
 */

$queryParams = is_array($queryParams ?? null) ? $queryParams : [];
$purpose = (string) ($purpose ?? '');
$type = (string) ($type ?? '');
$purposeLabels = is_array($purposeLabels ?? null) ? $purposeLabels : [];
$typeFilterOptions = is_array($typeFilterOptions ?? null) ? $typeFilterOptions : [];

$buildQuickFilterUrl = static function (string $field, string $value) use ($queryParams, $purpose, $type): string {
    $params = $queryParams;
    unset($params['page'], $params['cursor'], $params['filters_open']);

    $current = $field === 'purpose' ? $purpose : $type;
    if ($value === '' || $current === $value) {
        unset($params[$field]);
    } else {
        $params[$field] = $value;
    }

    $query = http_build_query($params);

    return DIRPAGE . 'properties' . ($query !== '' ? ('?' . $query) : '');
};

$purposePills = [
    '' => 'Todos',
    'venda' => $purposeLabels['venda'] ?? 'Venda',
    'aluguer_curto' => $purposeLabels['aluguer_curto'] ?? 'Aluguer curto',
    'aluguer_longo' => $purposeLabels['aluguer_longo'] ?? 'Aluguer longo',
];
?>

<nav class="properties-quick-filters" aria-label="Filtros rápidos">
    <div class="properties-quick-filters-group">
        <span class="properties-quick-filters-label" id="propertiesQuickPurposeLabel">Finalidade</span>
        <div class="properties-quick-filters-pills properties-quick-filters-pills--scroll" role="group" aria-labelledby="propertiesQuickPurposeLabel">
            <?php foreach ($purposePills as $purposeValue => $purposeLabel): ?>
                <?php
                    $isActive = $purposeValue === ''
                        ? $purpose === ''
                        : $purpose === $purposeValue;
                ?>
                <a href="<?php echo htmlspecialchars($buildQuickFilterUrl('purpose', $purposeValue), ENT_QUOTES, 'UTF-8'); ?>"
                   class="properties-quick-filter-pill<?php echo $isActive ? ' is-active' : ''; ?>"
                   <?php echo $isActive ? 'aria-current="true"' : ''; ?>>
                    <?php echo htmlspecialchars($purposeLabel); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($typeFilterOptions)): ?>
        <div class="properties-quick-filters-group properties-quick-filters-group--types">
            <span class="properties-quick-filters-label" id="propertiesQuickTypeLabel">Tipo</span>
            <div class="properties-quick-filters-pills properties-quick-filters-pills--scroll" role="group" aria-labelledby="propertiesQuickTypeLabel">
                <a href="<?php echo htmlspecialchars($buildQuickFilterUrl('type', ''), ENT_QUOTES, 'UTF-8'); ?>"
                   class="properties-quick-filter-pill<?php echo $type === '' ? ' is-active' : ''; ?>"
                   <?php echo $type === '' ? 'aria-current="true"' : ''; ?>>
                    Todos
                </a>
                <?php foreach ($typeFilterOptions as $typeValue => $typeLabel): ?>
                    <?php $typeActive = $type === $typeValue; ?>
                    <a href="<?php echo htmlspecialchars($buildQuickFilterUrl('type', (string) $typeValue), ENT_QUOTES, 'UTF-8'); ?>"
                       class="properties-quick-filter-pill<?php echo $typeActive ? ' is-active' : ''; ?>"
                       <?php echo $typeActive ? 'aria-current="true"' : ''; ?>>
                        <?php echo htmlspecialchars((string) $typeLabel); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</nav>
