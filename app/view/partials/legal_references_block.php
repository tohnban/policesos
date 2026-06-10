<?php
/** @var string $legalReferencesTitle */
/** @var string $legalReferencesContent */
$legalReferencesTitle = isset($legalReferencesTitle) ? (string) $legalReferencesTitle : 'Referências legais (Angola)';
$legalReferencesContent = isset($legalReferencesContent) ? (string) $legalReferencesContent : '';
?>
<details class="legal-references">
    <summary class="legal-references-summary"><?php echo htmlspecialchars($legalReferencesTitle, ENT_QUOTES, 'UTF-8'); ?></summary>
    <div class="legal-references-body">
        <?php echo $legalReferencesContent; ?>
    </div>
</details>
