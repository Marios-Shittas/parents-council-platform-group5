<?php
require_once __DIR__ . '/../../includes/site_context.php';
require_once __DIR__ . '/../../includes/ParentProfileViewHelper.php';

$profileCssPath = __DIR__ . '/../../../public/assets/css/user_css/parent-profile.css';
$profileCssVersion = file_exists($profileCssPath) ? (string) filemtime($profileCssPath) : (string) time();

$fullName = trim((string)($parentUser['name'] ?? '') . ' ' . (string)($parentUser['surname'] ?? ''));
$fullName = $fullName !== '' ? $fullName : 'Γονέας';
$accountStatus = (string)($parentUser['account_status'] ?? 'active');

$pastOrders = array_values(array_filter($orders, static function (array $order): bool {
    return (string)($order['order_status'] ?? '') !== 'pending';
}));

$completedPayments = array_values(array_filter($payments, static function (array $payment): bool {
    return (string)($payment['payment_status'] ?? '') === 'completed';
}));

$totalPaidAmount = array_reduce($completedPayments, static function (float $carry, array $payment): float {
    return $carry + (float)($payment['amount'] ?? 0);
}, 0.0);

$latestPaymentDate = !empty($payments[0]['payment_date']) ? (string)$payments[0]['payment_date'] : '';
$latestOrderDate = !empty($orders[0]['created_at']) ? (string)$orders[0]['created_at'] : '';
$childrenCount = count($children);
$paidOrdersCount = count(array_filter($orders, static function (array $order): bool {
    return (string)($order['order_status'] ?? '') === 'paid';
}));
$insurancePaymentStatus = trim((string)($_GET['insurance_payment_status'] ?? ''));
$insurancePaymentMessage = trim((string)($_GET['insurance_payment_message'] ?? ''));
$uninsuredChildrenNames = array_values(array_map(static function (array $child): string {
    return trim((string)($child['name'] ?? '') . ' ' . (string)($child['surname'] ?? ''));
}, $childrenPendingInsurance));
$initials = mb_strtoupper(mb_substr((string)($parentUser['name'] ?? ''), 0, 1) . mb_substr((string)($parentUser['surname'] ?? ''), 0, 1));
if (trim($initials) === '') {
    $initials = 'PG';
}
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
    <link rel="stylesheet" href="<?php echo SiteContext::assetUrl('css/user_css/parent-profile.css'); ?>?v=<?php echo urlencode($profileCssVersion); ?>">

    <title>Το Προφίλ Μου</title>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php
$pageHeaderTitle = 'Το Προφίλ Μου';
$pageHeaderSubtitle = 'Δες συγκεντρωμένα τα στοιχεία σου, τα παιδιά σου, τις παραγγελίες και τις πληρωμές σου.';
$pageHeaderIcon = 'fas fa-user-circle';
$pageHeaderEyebrow = 'Χώρος Γονέα';
include __DIR__ . '/../../includes/public_page_header.php';
?>

<main class="parent-profile-page">
    <div class="profile-shell container">
        <section class="profile-hero-card">
            <div class="profile-identity">
                <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="profile-identity-copy">
                    <p class="profile-kicker">Προσωπικός Χώρος</p>
                    <h1><?php echo htmlspecialchars($fullName); ?></h1>
                    <div class="profile-meta-strip">
                        <span class="<?php echo htmlspecialchars(ParentProfilePageHelper::accountStatusClass($accountStatus)); ?>">
                            <?php echo htmlspecialchars(ParentProfilePageHelper::formatAccountStatusLabel($accountStatus)); ?>
                        </span>
                        <span><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars((string)($parentUser['email'] ?? '—')); ?></span>
                        <span><i class="fas fa-phone-alt mr-2"></i><?php echo htmlspecialchars(ParentProfileViewHelper::formatPhoneNumber($parentUser['phone_number'] ?? '')); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="profile-stats-grid">
            <article class="profile-stat-card">
                <span class="stat-label">Παιδιά</span>
                <strong class="stat-value"><?php echo $childrenCount; ?></strong>
                <p class="stat-note">Καταχωρημένα προφίλ παιδιών</p>
            </article>
            <article class="profile-stat-card">
                <span class="stat-label">Πληρωμένες Παραγγελίες</span>
                <strong class="stat-value"><?php echo $paidOrdersCount; ?></strong>
                <p class="stat-note">Ολοκληρωμένες αγορές από το κατάστημα</p>
            </article>
            <article class="profile-stat-card">
                <span class="stat-label">Ολοκληρωμένες Πληρωμές</span>
                <strong class="stat-value"><?php echo count($completedPayments); ?></strong>
                <p class="stat-note">Πληρωμές που πέρασαν επιτυχώς</p>
            </article>
            <article class="profile-stat-card accent-card">
                <span class="stat-label">Συνολικό Ποσό</span>
                <strong class="stat-value">€<?php echo number_format($totalPaidAmount, 2); ?></strong>
                <p class="stat-note">Άθροισμα ολοκληρωμένων πληρωμών</p>
            </article>
        </section>

        <?php if ($insurancePaymentMessage !== ''): ?>
            <section class="profile-inline-message profile-inline-message-<?php echo htmlspecialchars($insurancePaymentStatus !== '' ? $insurancePaymentStatus : 'pending'); ?>">
                <i class="fas fa-info-circle"></i>
                <span><?php echo htmlspecialchars($insurancePaymentMessage); ?></span>
            </section>
        <?php endif; ?>

        <?php if ($pendingInsuranceChildrenCount > 0): ?>
            <section class="profile-insurance-cta">
                <div class="insurance-cta-copy">
                    <p class="panel-kicker">Ασφάλεια Παιδιών</p>
                    <h2>Εκκρεμεί ασφάλεια για <?php echo $pendingInsuranceChildrenCount; ?> <?php echo $pendingInsuranceChildrenCount === 1 ? 'παιδί' : 'παιδιά'; ?></h2>
                    <p>
                        Δεν έχει καταχωρηθεί ολοκληρωμένη πληρωμή ασφάλειας για:
                        <strong><?php echo htmlspecialchars(implode(', ', $uninsuredChildrenNames)); ?></strong>
                    </p>
                    <div class="insurance-cta-meta">
                        <span><i class="fas fa-child mr-2"></i><?php echo $pendingInsuranceChildrenCount; ?> <?php echo $pendingInsuranceChildrenCount === 1 ? 'παιδί' : 'παιδιά'; ?></span>
                        <span><i class="fas fa-euro-sign mr-2"></i>€<?php echo number_format($insurancePricePerChild, 2); ?> ανά παιδί</span>
                        <span><i class="fas fa-receipt mr-2"></i>Σύνολο €<?php echo number_format($pendingInsuranceTotal, 2); ?></span>
                    </div>
                </div>
                <div class="insurance-cta-actions">
                    <a class="insurance-cta-btn" href="/parents-council-platform-group5/app/services/InsuranceJCC.php?action=checkout">
                        Πληρωμή Ασφάλειας μέσω JCC
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <section class="profile-main-grid">
            <div class="profile-main-column">
                <article class="profile-panel info-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="panel-kicker">Στοιχεία Λογαριασμού</p>
                            <h2>Βασικές πληροφορίες</h2>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-tile">
                            <span class="info-label">Ονοματεπώνυμο</span>
                            <strong><?php echo htmlspecialchars($fullName); ?></strong>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Email</span>
                            <strong><?php echo htmlspecialchars((string)($parentUser['email'] ?? '—')); ?></strong>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Τηλέφωνο</span>
                            <strong><?php echo htmlspecialchars(ParentProfileViewHelper::formatPhoneNumber($parentUser['phone_number'] ?? '')); ?></strong>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Τελευταία Πληρωμή</span>
                            <strong><?php echo $latestPaymentDate !== '' ? htmlspecialchars(date('d/m/Y H:i', strtotime($latestPaymentDate))) : '—'; ?></strong>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Τελευταία Παραγγελία</span>
                            <strong><?php echo $latestOrderDate !== '' ? htmlspecialchars(date('d/m/Y H:i', strtotime($latestOrderDate))) : '—'; ?></strong>
                        </div>
                    </div>
                </article>

                <article class="profile-panel children-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="panel-kicker">Οικογένεια</p>
                            <h2>Τα παιδιά μου</h2>
                        </div>
                    </div>

                    <?php if (empty($children)): ?>
                        <div class="panel-empty">
                            <i class="fas fa-child"></i>
                            <h3>Δεν υπάρχουν ακόμη καταχωρημένα παιδιά</h3>
                            <p>Όταν υπάρχουν διαθέσιμες εγγραφές για τον λογαριασμό σου, εδώ θα βλέπεις τα παιδιά που συνδέονται με το προφίλ σου.</p>
                        </div>
                    <?php else: ?>
                        <div class="children-grid">
                            <?php foreach ($children as $child): ?>
                                <article class="child-card">
                                    <div class="child-card-top">
                                        <span class="child-badge"><i class="fas fa-child mr-2"></i>Παιδί</span>
                                        <span class="child-class"><?php echo htmlspecialchars((string)($child['school_class'] ?? '—')); ?></span>
                                    </div>
                                    <h3><?php echo htmlspecialchars(trim((string)($child['name'] ?? '') . ' ' . (string)($child['surname'] ?? ''))); ?></h3>
                                    <p>
                                        <i class="far fa-calendar-alt mr-2"></i>
                                        <?php echo !empty($child['date_of_birth']) ? htmlspecialchars(date('d/m/Y', strtotime((string)$child['date_of_birth']))) : '—'; ?>
                                    </p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        </section>

        <section class="profile-history-grid">
            <article class="profile-panel history-panel">
                <div class="panel-heading">
                    <div>
                        <p class="panel-kicker">Ιστορικό Αγορών</p>
                        <h2>Παραγγελίες</h2>
                    </div>
                </div>

                <?php if (empty($pastOrders)): ?>
                    <div class="panel-empty">
                        <i class="fas fa-receipt"></i>
                        <h3>Δεν υπάρχει ακόμη ιστορικό παραγγελιών</h3>
                        <p>Οι παλιές σου αγορές θα εμφανιστούν εδώ μόλις ολοκληρωθούν πληρωμές από το κατάστημα.</p>
                    </div>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($pastOrders as $order): ?>
                            <?php $orderId = (int)($order['order_id'] ?? 0); ?>
                            <details class="history-card history-expandable order-card">
                                <summary class="history-summary">
                                    <div class="history-summary-main">
                                        <div class="history-card-top">
                                            <strong>Παραγγελία #<?php echo $orderId; ?></strong>
                                            <span class="<?php echo htmlspecialchars(ParentProfilePageHelper::orderStatusClass((string)($order['order_status'] ?? 'pending'))); ?>">
                                                <?php echo htmlspecialchars(ParentProfilePageHelper::formatOrderStatusLabel((string)($order['order_status'] ?? 'pending'))); ?>
                                            </span>
                                        </div>
                                        <div class="history-card-meta">
                                            <span><i class="far fa-calendar-alt mr-2"></i><?php echo !empty($order['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$order['created_at']))) : '—'; ?></span>
                                            <span><i class="fas fa-euro-sign mr-2"></i><?php echo number_format((float)($order['total_price'] ?? 0), 2); ?></span>
                                            <span><i class="fas fa-box-open mr-2"></i><?php echo count($orderItemsByOrderId[$orderId] ?? []); ?> είδη</span>
                                        </div>
                                    </div>
                                    <span class="history-expand-icon" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                                </summary>
                                <?php if (!empty($orderItemsByOrderId[$orderId])): ?>
                                    <div class="history-expand-content">
                                        <div class="line-items">
                                            <?php foreach (($orderItemsByOrderId[$orderId] ?? []) as $item): ?>
                                                <div class="line-item">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars((string)($item['product_name'] ?? 'Προϊόν')); ?></strong>
                                                        <span>
                                                            <?php echo (int)($item['quantity'] ?? 0); ?> τεμ.
                                                            <?php if (!empty($item['size'])): ?>
                                                                • Μέγεθος <?php echo htmlspecialchars((string)$item['size']); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <strong>€<?php echo number_format((float)($item['line_total'] ?? 0), 2); ?></strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="profile-panel history-panel">
                <div class="panel-heading">
                    <div>
                        <p class="panel-kicker">Οικονομική Εικόνα</p>
                        <h2>Πληρωμές</h2>
                    </div>
                    <span class="panel-pill"><?php echo count($payments); ?> πληρωμές</span>
                </div>

                <?php if (empty($payments)): ?>
                    <div class="panel-empty">
                        <i class="fas fa-credit-card"></i>
                        <h3>Δεν υπάρχουν ακόμη πληρωμές</h3>
                        <p>Μόλις πραγματοποιήσεις κάποια πληρωμή, εδώ θα βλέπεις το ιστορικό και την κατάστασή της.</p>
                    </div>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($payments as $payment): ?>
                            <details class="history-card history-expandable payment-card">
                                <summary class="history-summary">
                                    <div class="history-summary-main">
                                        <div class="history-card-top">
                                            <strong>Πληρωμή #<?php echo (int)($payment['payment_id'] ?? 0); ?></strong>
                                            <span class="<?php echo htmlspecialchars(ParentProfilePageHelper::paymentStatusClass((string)($payment['payment_status'] ?? 'pending'))); ?>">
                                                <?php echo htmlspecialchars(ParentProfilePageHelper::formatPaymentStatusLabel((string)($payment['payment_status'] ?? 'pending'))); ?>
                                            </span>
                                        </div>
                                        <div class="history-card-meta">
                                            <span><i class="far fa-calendar-alt mr-2"></i><?php echo !empty($payment['payment_date']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$payment['payment_date']))) : '—'; ?></span>
                                            <span><i class="fas fa-euro-sign mr-2"></i><?php echo number_format((float)($payment['amount'] ?? 0), 2); ?></span>
                                            <span><i class="fas fa-tag mr-2"></i><?php echo htmlspecialchars(ParentProfilePageHelper::formatPaymentTypeLabel((string)($payment['payment_type'] ?? 'product'))); ?></span>
                                        </div>
                                    </div>
                                    <span class="history-expand-icon" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                                </summary>
                                <div class="history-expand-content">
                                    <?php if (!empty($payment['transaction_id'])): ?>
                                        <div class="transaction-chip">
                                            <i class="fas fa-link mr-2"></i>Συναλλαγή: <?php echo htmlspecialchars((string)$payment['transaction_id']); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="transaction-chip transaction-chip-muted">
                                            <i class="fas fa-info-circle mr-2"></i>Δεν υπάρχει διαθέσιμο transaction id για αυτή την πληρωμή.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
