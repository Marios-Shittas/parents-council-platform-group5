<?php
$pageHeaderTitle = $pageHeaderTitle ?? '';
$pageHeaderSubtitle = $pageHeaderSubtitle ?? '';
$pageHeaderIcon = $pageHeaderIcon ?? 'fas fa-circle';
$pageHeaderEyebrow = $pageHeaderEyebrow ?? 'Δημόσια Σελίδα';
?>
<section class="public-page-header" aria-labelledby="public-page-title">
    <div class="container">
        <div class="public-page-header__card">
            <div class="public-page-header__icon" aria-hidden="true">
                <i class="<?php echo htmlspecialchars($pageHeaderIcon); ?>"></i>
            </div>
            <div class="public-page-header__content">
                <span class="public-page-header__eyebrow"><?php echo htmlspecialchars($pageHeaderEyebrow); ?></span>
                <h1 id="public-page-title"><?php echo htmlspecialchars($pageHeaderTitle); ?></h1>
                <?php if ($pageHeaderSubtitle !== ''): ?>
                    <p class="public-page-header__subtitle"><?php echo htmlspecialchars($pageHeaderSubtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
