<?php
// Arxeio: app\views\pages\parent-profile.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
require_once __DIR__ . '/../../includes/site_context.php';

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function parentProfileFormatPhoneNumber($phone): string
{
    $rawPhone = trim((string)$phone);
    if ($rawPhone === '') {
        return 'â€”';
    }

    $digits = preg_replace('/\D+/', '', $rawPhone);
    if (!is_string($digits) || $digits === '') {
        return $rawPhone;
    }

    if (strpos($digits, '357') === 0) {
        $localNumber = substr($digits, 3);
        if ($localNumber !== '') {
            return '+357 ' . $localNumber;
        }
    }

    if (strlen($digits) === 8) {
        return '+357 ' . $digits;
    }

    return $rawPhone;
}

$profileCssPath = __DIR__ . '/../../../public/assets/css/user_css/parent-profile.css';
$profileCssVersion = file_exists($profileCssPath) ? (string) filemtime($profileCssPath) : (string) time();

$fullName = trim((string)($parentUser['name'] ?? '') . ' ' . (string)($parentUser['surname'] ?? ''));
$fullName = $fullName !== '' ? $fullName : 'Î“Î¿Î½Î­Î±Ï‚';
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
    <link rel="stylesheet" href="<?php echo site_asset_url('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/public-page-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/parent-profile.css'); ?>?v=<?php echo urlencode($profileCssVersion); ?>">

    <title>Î¤Î¿ Î ÏÎ¿Ï†Î¯Î» ÎœÎ¿Ï…</title>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php
$pageHeaderTitle = 'Î¤Î¿ Î ÏÎ¿Ï†Î¯Î» ÎœÎ¿Ï…';
$pageHeaderSubtitle = 'Î”ÎµÏ‚ ÏƒÏ…Î³ÎºÎµÎ½Ï„ÏÏ‰Î¼Î­Î½Î± Ï„Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± ÏƒÎ¿Ï…, Ï„Î± Ï€Î±Î¹Î´Î¹Î¬ ÏƒÎ¿Ï…, Ï„Î¹Ï‚ Ï€Î±ÏÎ±Î³Î³ÎµÎ»Î¯ÎµÏ‚ ÎºÎ±Î¹ Ï„Î¹Ï‚ Ï€Î»Î·ÏÏ‰Î¼Î­Ï‚ ÏƒÎ¿Ï….';
$pageHeaderIcon = 'fas fa-user-circle';
$pageHeaderEyebrow = 'Î§ÏŽÏÎ¿Ï‚ Î“Î¿Î½Î­Î±';
include __DIR__ . '/../../includes/public_page_header.php';
?>

<main class="parent-profile-page">
    <div class="profile-shell container">
        <section class="profile-hero-card">
            <div class="profile-identity">
                <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="profile-identity-copy">
                    <p class="profile-kicker">Î ÏÎ¿ÏƒÏ‰Ï€Î¹ÎºÏŒÏ‚ Î§ÏŽÏÎ¿Ï‚</p>
                    <h1><?php echo htmlspecialchars($fullName); ?></h1>
                    <div class="profile-meta-strip">
                        <span class="<?php echo htmlspecialchars(parentProfileAccountStatusClass($accountStatus)); ?>">
                            <?php echo htmlspecialchars(parentProfileFormatAccountStatusLabel($accountStatus)); ?>
                        </span>
                        <span><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars((string)($parentUser['email'] ?? 'â€”')); ?></span>
                        <span><i class="fas fa-phone-alt mr-2"></i><?php echo htmlspecialchars(parentProfileFormatPhoneNumber($parentUser['phone_number'] ?? '')); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="profile-stats-grid">
            <article class="profile-stat-card">
                <span class="stat-label">Î Î±Î¹Î´Î¹Î¬</span>
                <strong class="stat-value"><?php echo $childrenCount; ?></strong>
                <p class="stat-note">ÎšÎ±Ï„Î±Ï‡Ï‰ÏÎ·Î¼Î­Î½Î± Ï€ÏÎ¿Ï†Î¯Î» Ï€Î±Î¹Î´Î¹ÏŽÎ½</p>
            </article>
            <article class="profile-stat-card">
                <span class="stat-label">Î Î»Î·ÏÏ‰Î¼Î­Î½ÎµÏ‚ Î Î±ÏÎ±Î³Î³ÎµÎ»Î¯ÎµÏ‚</span>
                <strong class="stat-value"><?php echo $paidOrdersCount; ?></strong>
                <p class="stat-note">ÎŸÎ»Î¿ÎºÎ»Î·ÏÏ‰Î¼Î­Î½ÎµÏ‚ Î±Î³Î¿ÏÎ­Ï‚ Î±Ï€ÏŒ Ï„Î¿ ÎºÎ±Ï„Î¬ÏƒÏ„Î·Î¼Î±</p>
            </article>
            <article class="profile-stat-card">
                <span class="stat-label">ÎŸÎ»Î¿ÎºÎ»Î·ÏÏ‰Î¼Î­Î½ÎµÏ‚ Î Î»Î·ÏÏ‰Î¼Î­Ï‚</span>
                <strong class="stat-value"><?php echo count($completedPayments); ?></strong>
                <p class="stat-note">Î Î»Î·ÏÏ‰Î¼Î­Ï‚ Ï€Î¿Ï… Ï€Î­ÏÎ±ÏƒÎ±Î½ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚</p>
            </article>
            <article class="profile-stat-card accent-card">
                <span class="stat-label">Î£Ï…Î½Î¿Î»Î¹ÎºÏŒ Î Î¿ÏƒÏŒ</span>
                <strong class="stat-value">â‚¬<?php echo number_format($totalPaidAmount, 2); ?></strong>
                <p class="stat-note">Î†Î¸ÏÎ¿Î¹ÏƒÎ¼Î± Î¿Î»Î¿ÎºÎ»Î·ÏÏ‰Î¼Î­Î½Ï‰Î½ Ï€Î»Î·ÏÏ‰Î¼ÏŽÎ½</p>
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
                    <p class="panel-kicker">Î‘ÏƒÏ†Î¬Î»ÎµÎ¹Î± Î Î±Î¹Î´Î¹ÏŽÎ½</p>
                    <h2>Î•ÎºÎºÏÎµÎ¼ÎµÎ¯ Î±ÏƒÏ†Î¬Î»ÎµÎ¹Î± Î³Î¹Î± <?php echo $pendingInsuranceChildrenCount; ?> <?php echo $pendingInsuranceChildrenCount === 1 ? 'Ï€Î±Î¹Î´Î¯' : 'Ï€Î±Î¹Î´Î¹Î¬'; ?></h2>
                    <p>
                        Î”ÎµÎ½ Î­Ï‡ÎµÎ¹ ÎºÎ±Ï„Î±Ï‡Ï‰ÏÎ·Î¸ÎµÎ¯ Î¿Î»Î¿ÎºÎ»Î·ÏÏ‰Î¼Î­Î½Î· Ï€Î»Î·ÏÏ‰Î¼Î® Î±ÏƒÏ†Î¬Î»ÎµÎ¹Î±Ï‚ Î³Î¹Î±:
                        <strong><?php echo htmlspecialchars(implode(', ', $uninsuredChildrenNames)); ?></strong>
                    </p>
                    <div class="insurance-cta-meta">
                        <span><i class="fas fa-child mr-2"></i><?php echo $pendingInsuranceChildrenCount; ?> <?php echo $pendingInsuranceChildrenCount === 1 ? 'Ï€Î±Î¹Î´Î¯' : 'Ï€Î±Î¹Î´Î¹Î¬'; ?></span>
                        <span><i class="fas fa-euro-sign mr-2"></i>â‚¬<?php echo number_format($insurancePricePerChild, 2); ?> Î±Î½Î¬ Ï€Î±Î¹Î´Î¯</span>
                        <span><i class="fas fa-receipt mr-2"></i>Î£ÏÎ½Î¿Î»Î¿ â‚¬<?php echo number_format($pendingInsuranceTotal, 2); ?></span>
                    </div>
                </div>
                <div class="insurance-cta-actions">
                    <a class="insurance-cta-btn" href="/parents-council-platform-group5/app/services/InsuranceJCC.php?action=checkout">
                        Î Î»Î·ÏÏ‰Î¼Î® Î‘ÏƒÏ†Î¬Î»ÎµÎ¹Î±Ï‚ Î¼Î­ÏƒÏ‰ JCC
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <section class="profile-main-grid">
            <div class="profile-main-column">
                <article class="profile-panel info-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="panel-kicker">Î£Ï„Î¿Î¹Ï‡ÎµÎ¯Î± Î›Î¿Î³Î±ÏÎ¹Î±ÏƒÎ¼Î¿Ï</p>
                            <h2>Î’Î±ÏƒÎ¹ÎºÎ­Ï‚ Ï€Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚</h2>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-tile">
                            <span class="info-label">ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿</span>
                            <strong><?php echo htmlspecialchars($fullName); ?></strong>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Email</span>
                            <strong><?php echo htmlspecialchars((string)($parentUser['email'] ?? 'â€”')); ?></strong>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Î¤Î·Î»Î­Ï†Ï‰Î½Î¿</span>
                            <strong><?php echo htmlspecialchars(parentProfileFormatPhoneNumber($parentUser['phone_number'] ?? '')); ?></strong>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Î¤ÎµÎ»ÎµÏ…Ï„Î±Î¯Î± Î Î»Î·ÏÏ‰Î¼Î®</span>
                            <strong><?php echo $latestPaymentDate !== '' ? htmlspecialchars(date('d/m/Y H:i', strtotime($latestPaymentDate))) : 'â€”'; ?></strong>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Î¤ÎµÎ»ÎµÏ…Ï„Î±Î¯Î± Î Î±ÏÎ±Î³Î³ÎµÎ»Î¯Î±</span>
                            <strong><?php echo $latestOrderDate !== '' ? htmlspecialchars(date('d/m/Y H:i', strtotime($latestOrderDate))) : 'â€”'; ?></strong>
                        </div>
                    </div>
                </article>

                <article class="profile-panel children-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="panel-kicker">ÎŸÎ¹ÎºÎ¿Î³Î­Î½ÎµÎ¹Î±</p>
                            <h2>Î¤Î± Ï€Î±Î¹Î´Î¹Î¬ Î¼Î¿Ï…</h2>
                        </div>
                    </div>

                    <?php if (empty($children)): ?>
                        <div class="panel-empty">
                            <i class="fas fa-child"></i>
                            <h3>Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î±ÎºÏŒÎ¼Î· ÎºÎ±Ï„Î±Ï‡Ï‰ÏÎ·Î¼Î­Î½Î± Ï€Î±Î¹Î´Î¹Î¬</h3>
                            <p>ÎŒÏ„Î±Î½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î´Î¹Î±Î¸Î­ÏƒÎ¹Î¼ÎµÏ‚ ÎµÎ³Î³ÏÎ±Ï†Î­Ï‚ Î³Î¹Î± Ï„Î¿Î½ Î»Î¿Î³Î±ÏÎ¹Î±ÏƒÎ¼ÏŒ ÏƒÎ¿Ï…, ÎµÎ´ÏŽ Î¸Î± Î²Î»Î­Ï€ÎµÎ¹Ï‚ Ï„Î± Ï€Î±Î¹Î´Î¹Î¬ Ï€Î¿Ï… ÏƒÏ…Î½Î´Î­Î¿Î½Ï„Î±Î¹ Î¼Îµ Ï„Î¿ Ï€ÏÎ¿Ï†Î¯Î» ÏƒÎ¿Ï….</p>
                        </div>
                    <?php else: ?>
                        <div class="children-grid">
                            <?php foreach ($children as $child): ?>
                                <article class="child-card">
                                    <div class="child-card-top">
                                        <span class="child-badge"><i class="fas fa-child mr-2"></i>Î Î±Î¹Î´Î¯</span>
                                        <span class="child-class"><?php echo htmlspecialchars((string)($child['school_class'] ?? 'â€”')); ?></span>
                                    </div>
                                    <h3><?php echo htmlspecialchars(trim((string)($child['name'] ?? '') . ' ' . (string)($child['surname'] ?? ''))); ?></h3>
                                    <p>
                                        <i class="far fa-calendar-alt mr-2"></i>
                                        <?php echo !empty($child['date_of_birth']) ? htmlspecialchars(date('d/m/Y', strtotime((string)$child['date_of_birth']))) : 'â€”'; ?>
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
                        <p class="panel-kicker">Î™ÏƒÏ„Î¿ÏÎ¹ÎºÏŒ Î‘Î³Î¿ÏÏŽÎ½</p>
                        <h2>Î Î±ÏÎ±Î³Î³ÎµÎ»Î¯ÎµÏ‚</h2>
                    </div>
                </div>

                <?php if (empty($pastOrders)): ?>
                    <div class="panel-empty">
                        <i class="fas fa-receipt"></i>
                        <h3>Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡ÎµÎ¹ Î±ÎºÏŒÎ¼Î· Î¹ÏƒÏ„Î¿ÏÎ¹ÎºÏŒ Ï€Î±ÏÎ±Î³Î³ÎµÎ»Î¹ÏŽÎ½</h3>
                        <p>ÎŸÎ¹ Ï€Î±Î»Î¹Î­Ï‚ ÏƒÎ¿Ï… Î±Î³Î¿ÏÎ­Ï‚ Î¸Î± ÎµÎ¼Ï†Î±Î½Î¹ÏƒÏ„Î¿ÏÎ½ ÎµÎ´ÏŽ Î¼ÏŒÎ»Î¹Ï‚ Î¿Î»Î¿ÎºÎ»Î·ÏÏ‰Î¸Î¿ÏÎ½ Ï€Î»Î·ÏÏ‰Î¼Î­Ï‚ Î±Ï€ÏŒ Ï„Î¿ ÎºÎ±Ï„Î¬ÏƒÏ„Î·Î¼Î±.</p>
                    </div>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($pastOrders as $order): ?>
                            <?php $orderId = (int)($order['order_id'] ?? 0); ?>
                            <details class="history-card history-expandable order-card">
                                <summary class="history-summary">
                                    <div class="history-summary-main">
                                        <div class="history-card-top">
                                            <strong>Î Î±ÏÎ±Î³Î³ÎµÎ»Î¯Î± #<?php echo $orderId; ?></strong>
                                            <span class="<?php echo htmlspecialchars(parentProfileOrderStatusClass((string)($order['order_status'] ?? 'pending'))); ?>">
                                                <?php echo htmlspecialchars(parentProfileFormatOrderStatusLabel((string)($order['order_status'] ?? 'pending'))); ?>
                                            </span>
                                        </div>
                                        <div class="history-card-meta">
                                            <span><i class="far fa-calendar-alt mr-2"></i><?php echo !empty($order['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$order['created_at']))) : 'â€”'; ?></span>
                                            <span><i class="fas fa-euro-sign mr-2"></i><?php echo number_format((float)($order['total_price'] ?? 0), 2); ?></span>
                                            <span><i class="fas fa-box-open mr-2"></i><?php echo count($orderItemsByOrderId[$orderId] ?? []); ?> ÎµÎ¯Î´Î·</span>
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
                                                        <strong><?php echo htmlspecialchars((string)($item['product_name'] ?? 'Î ÏÎ¿ÏŠÏŒÎ½')); ?></strong>
                                                        <span>
                                                            <?php echo (int)($item['quantity'] ?? 0); ?> Ï„ÎµÎ¼.
                                                            <?php if (!empty($item['size'])): ?>
                                                                â€¢ ÎœÎ­Î³ÎµÎ¸Î¿Ï‚ <?php echo htmlspecialchars((string)$item['size']); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <strong>â‚¬<?php echo number_format((float)($item['line_total'] ?? 0), 2); ?></strong>
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
                        <p class="panel-kicker">ÎŸÎ¹ÎºÎ¿Î½Î¿Î¼Î¹ÎºÎ® Î•Î¹ÎºÏŒÎ½Î±</p>
                        <h2>Î Î»Î·ÏÏ‰Î¼Î­Ï‚</h2>
                    </div>
                    <span class="panel-pill"><?php echo count($payments); ?> Ï€Î»Î·ÏÏ‰Î¼Î­Ï‚</span>
                </div>

                <?php if (empty($payments)): ?>
                    <div class="panel-empty">
                        <i class="fas fa-credit-card"></i>
                        <h3>Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î±ÎºÏŒÎ¼Î· Ï€Î»Î·ÏÏ‰Î¼Î­Ï‚</h3>
                        <p>ÎœÏŒÎ»Î¹Ï‚ Ï€ÏÎ±Î³Î¼Î±Ï„Î¿Ï€Î¿Î¹Î®ÏƒÎµÎ¹Ï‚ ÎºÎ¬Ï€Î¿Î¹Î± Ï€Î»Î·ÏÏ‰Î¼Î®, ÎµÎ´ÏŽ Î¸Î± Î²Î»Î­Ï€ÎµÎ¹Ï‚ Ï„Î¿ Î¹ÏƒÏ„Î¿ÏÎ¹ÎºÏŒ ÎºÎ±Î¹ Ï„Î·Î½ ÎºÎ±Ï„Î¬ÏƒÏ„Î±ÏƒÎ® Ï„Î·Ï‚.</p>
                    </div>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($payments as $payment): ?>
                            <details class="history-card history-expandable payment-card">
                                <summary class="history-summary">
                                    <div class="history-summary-main">
                                        <div class="history-card-top">
                                            <strong>Î Î»Î·ÏÏ‰Î¼Î® #<?php echo (int)($payment['payment_id'] ?? 0); ?></strong>
                                            <span class="<?php echo htmlspecialchars(parentProfilePaymentStatusClass((string)($payment['payment_status'] ?? 'pending'))); ?>">
                                                <?php echo htmlspecialchars(parentProfileFormatPaymentStatusLabel((string)($payment['payment_status'] ?? 'pending'))); ?>
                                            </span>
                                        </div>
                                        <div class="history-card-meta">
                                            <span><i class="far fa-calendar-alt mr-2"></i><?php echo !empty($payment['payment_date']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$payment['payment_date']))) : 'â€”'; ?></span>
                                            <span><i class="fas fa-euro-sign mr-2"></i><?php echo number_format((float)($payment['amount'] ?? 0), 2); ?></span>
                                            <span><i class="fas fa-tag mr-2"></i><?php echo htmlspecialchars(parentProfileFormatPaymentTypeLabel((string)($payment['payment_type'] ?? 'product'))); ?></span>
                                        </div>
                                    </div>
                                    <span class="history-expand-icon" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                                </summary>
                                <div class="history-expand-content">
                                    <?php if (!empty($payment['transaction_id'])): ?>
                                        <div class="transaction-chip">
                                            <i class="fas fa-link mr-2"></i>Î£Ï…Î½Î±Î»Î»Î±Î³Î®: <?php echo htmlspecialchars((string)$payment['transaction_id']); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="transaction-chip transaction-chip-muted">
                                            <i class="fas fa-info-circle mr-2"></i>Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡ÎµÎ¹ Î´Î¹Î±Î¸Î­ÏƒÎ¹Î¼Î¿ transaction id Î³Î¹Î± Î±Ï…Ï„Î® Ï„Î·Î½ Ï€Î»Î·ÏÏ‰Î¼Î®.
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
