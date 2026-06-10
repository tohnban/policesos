<?php
/** @var string $legalKicker */
/** @var string $legalTitle */
/** @var string $legalLead */
?>
<section class="dashboard-view-hero compact legal-page-hero">
    <div>
        <span class="dashboard-hero-kicker"><?php echo htmlspecialchars($legalKicker, ENT_QUOTES, 'UTF-8'); ?></span>
        <h1><?php echo htmlspecialchars($legalTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p><?php echo htmlspecialchars($legalLead, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</section>
