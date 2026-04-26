<?php
// Arxeio: app\includes\public_page_header.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
// Emfanizei koino header gia tis dimosies selides me titlo kai perigrafi.
// Kathe selida mporei na orisei ta variables prin kanei include auto to component.
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
                <!-- To subtitlos einai proairetiko, opote den kratame adeio p tag otan den yparxei keimeno. -->
                <?php if ($pageHeaderSubtitle !== ''): ?>
                    <p class="public-page-header__subtitle"><?php echo htmlspecialchars($pageHeaderSubtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
