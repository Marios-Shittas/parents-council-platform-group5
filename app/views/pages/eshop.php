<?php
require_once __DIR__ . '/../../includes/site_context.php';
require_once __DIR__ . '/../../services/EshopSettingsService.php';

$paymentsScriptPath = __DIR__ . '/../../../public/assets/js/payments.jsx';
$paymentsScriptVersion = file_exists($paymentsScriptPath) ? (string) filemtime($paymentsScriptPath) : (string) time();
$paymentsCssPath = __DIR__ . '/../../../public/assets/css/user_css/payments.css';
$paymentsCssVersion = file_exists($paymentsCssPath) ? (string) filemtime($paymentsCssPath) : (string) time();
$eshopSettingsService = new EshopSettingsService();
$isShopVisible = $eshopSettingsService->isShopVisible();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/user_css/public-page-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/user_css/payments.css'); ?>?v=<?php echo urlencode($paymentsCssVersion); ?>">

    <title>Αγορές</title>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <?php
    $pageHeaderTitle = 'Αγορές';
    $pageHeaderSubtitle = 'Περιηγηθείτε στα διαθέσιμα προϊόντα και διαχειριστείτε το καλάθι σας.';
    $pageHeaderIcon = 'fas fa-shopping-bag';
    $pageHeaderEyebrow = 'Ηλεκτρονικές Αγορές';
    include __DIR__ . '/../../includes/public_page_header.php';
    ?>

    <?php if ($isShopVisible): ?>
        <div id="payments"></div>
    <?php else: ?>
        <section class="eshop-coming-soon">
            <div class="eshop-coming-soon__card">
                <span class="eshop-coming-soon__eyebrow">Κατάστημα</span>
                <h2>Έρχεται Σύντομα</h2>
                <p>Το κατάστημα δεν είναι διαθέσιμο αυτή τη στιγμή. Δοκιμάστε ξανά σύντομα.</p>
            </div>
        </section>
    <?php endif; ?>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($isShopVisible): ?>
        <script type="text/babel" src="<?php echo SiteContext::assetUrl('js/payments.jsx'); ?>?v=<?php echo urlencode($paymentsScriptVersion); ?>"></script>
    <?php endif; ?>
</body>
</html>
