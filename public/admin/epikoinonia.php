<?php
require_once __DIR__ . '/../../app/services/EpikoinoniaService.php';
require_once __DIR__ . '/../../app/services/EpikoinoniaPageService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function epikoinoniaAdminTrim($value)
{
    return trim((string)$value);
}

function epikoinoniaAdminTextarea($value)
{
    $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
    return trim($value);
}

$service = new EpikoinoniaService();
$pageService = new EpikoinoniaPageService();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirectUrl = 'epikoinonia.php';

    if ($action === 'update_content_section') {
        $sectionKey = $_POST['section_key'] ?? '';
        $saved = false;

        switch ($sectionKey) {
            case 'page_header':
                $saved = $pageService->updateSection(
                    'page_header',
                    epikoinoniaAdminTrim($_POST['title'] ?? ''),
                    epikoinoniaAdminTrim($_POST['subtitle'] ?? ''),
                    [
                        'eyebrow' => epikoinoniaAdminTrim($_POST['eyebrow'] ?? ''),
                        'icon' => epikoinoniaAdminTrim($_POST['icon'] ?? ''),
                    ]
                );
                break;

            case 'contact_info':
                $cards = [];
                for ($i = 1; $i <= 4; $i++) {
                    $cards[] = [
                        'title' => epikoinoniaAdminTrim($_POST["card_{$i}_title"] ?? ''),
                        'text' => epikoinoniaAdminTextarea($_POST["card_{$i}_text"] ?? ''),
                        'icon' => epikoinoniaAdminTrim($_POST["card_{$i}_icon"] ?? ''),
                        'link_label' => epikoinoniaAdminTrim($_POST["card_{$i}_link_label"] ?? ''),
                        'link_url' => epikoinoniaAdminTrim($_POST["card_{$i}_link_url"] ?? ''),
                    ];
                }

                $saved = $pageService->updateSection(
                    'contact_info',
                    epikoinoniaAdminTrim($_POST['title'] ?? ''),
                    epikoinoniaAdminTrim($_POST['subtitle'] ?? ''),
                    ['cards' => $cards]
                );
                break;

            case 'map_section':
                $saved = $pageService->updateSection(
                    'map_section',
                    epikoinoniaAdminTrim($_POST['title'] ?? ''),
                    epikoinoniaAdminTrim($_POST['subtitle'] ?? ''),
                    [
                        'embed_url' => epikoinoniaAdminTrim($_POST['embed_url'] ?? ''),
                    ]
                );
                break;

            case 'form_section':
                $saved = $pageService->updateSection(
                    'form_section',
                    epikoinoniaAdminTrim($_POST['title'] ?? ''),
                    epikoinoniaAdminTrim($_POST['subtitle'] ?? ''),
                    [
                        'description' => epikoinoniaAdminTextarea($_POST['description'] ?? ''),
                        'button_text' => epikoinoniaAdminTrim($_POST['button_text'] ?? ''),
                        'success_message' => epikoinoniaAdminTrim($_POST['success_message'] ?? ''),
                    ]
                );
                break;

            case 'social_section':
                $items = [];
                for ($i = 1; $i <= 3; $i++) {
                    $items[] = [
                        'title' => epikoinoniaAdminTrim($_POST["social_{$i}_title"] ?? ''),
                        'url' => epikoinoniaAdminTrim($_POST["social_{$i}_url"] ?? ''),
                        'icon' => epikoinoniaAdminTrim($_POST["social_{$i}_icon"] ?? ''),
                    ];
                }

                $saved = $pageService->updateSection(
                    'social_section',
                    epikoinoniaAdminTrim($_POST['title'] ?? ''),
                    epikoinoniaAdminTrim($_POST['subtitle'] ?? ''),
                    ['items' => $items]
                );
                break;
        }

        $_SESSION['flash_message'] = $saved
            ? 'Το περιεχόμενο της σελίδας επικοινωνίας ενημερώθηκε επιτυχώς.'
            : 'Παρουσιάστηκε σφάλμα κατά την αποθήκευση. ' . $pageService->getLastError();
        $_SESSION['flash_type'] = $saved ? 'success' : 'danger';
        $redirectUrl .= '#content-management';
    } elseif ($action === 'delete' && isset($_POST['message_id'])) {
        $messageId = intval($_POST['message_id']);
        if ($service->deleteMessage($messageId)) {
            $_SESSION['flash_message'] = 'Το μήνυμα διαγράφηκε επιτυχώς.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Αποτυχία διαγραφής του μηνύματος.';
            $_SESSION['flash_type'] = 'danger';
        }
        if (isset($_GET['id'])) {
            $redirectUrl .= '?id=' . intval($_GET['id']);
        }
    } elseif ($action === 'mark_read' && isset($_POST['message_id'])) {
        $messageId = intval($_POST['message_id']);
        if ($service->markAsRead($messageId)) {
            $_SESSION['flash_message'] = 'Το μήνυμα σημειώθηκε ως αναγνωσμένο.';
            $_SESSION['flash_type'] = 'success';
        }
        if (isset($_GET['id'])) {
            $redirectUrl .= '?id=' . intval($_GET['id']);
        }
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 20;
$offset = ($page - 1) * $itemsPerPage;

$contentSections = $pageService->getAllSections();
$pageHeaderSection = $contentSections['page_header'];
$contactInfoSection = $contentSections['contact_info'];
$mapSection = $contentSections['map_section'];
$formSection = $contentSections['form_section'];
$socialSection = $contentSections['social_section'];

$allMessages = $service->getAllMessages(999999, 0);
$filteredMessages = $allMessages;

if (!empty($search)) {
    $searchLower = strtolower($search);
    $filteredMessages = array_filter($filteredMessages, function($msg) use ($searchLower) {
        return stripos($msg['name'], $searchLower) !== false ||
               stripos($msg['email'], $searchLower) !== false ||
               stripos($msg['subject'], $searchLower) !== false;
    });
}

if (!empty($status)) {
    if ($status === 'new') {
        $filteredMessages = array_filter($filteredMessages, function($msg) {
            return $msg['is_read'] == 0;
        });
    } elseif ($status === 'read') {
        $filteredMessages = array_filter($filteredMessages, function($msg) {
            return $msg['is_read'] == 1;
        });
    }
}

if (!empty($dateFrom)) {
    $dateFromObj = new DateTime($dateFrom);
    $filteredMessages = array_filter($filteredMessages, function($msg) use ($dateFromObj) {
        $msgDate = new DateTime($msg['created_at']);
        return $msgDate >= $dateFromObj;
    });
}

if (!empty($dateTo)) {
    $dateToObj = new DateTime($dateTo . ' 23:59:59');
    $filteredMessages = array_filter($filteredMessages, function($msg) use ($dateToObj) {
        $msgDate = new DateTime($msg['created_at']);
        return $msgDate <= $dateToObj;
    });
}

$filteredMessages = array_values($filteredMessages);

$totalMessages = count($filteredMessages);
$totalPages = ceil($totalMessages / $itemsPerPage);

$paginatedMessages = array_slice($filteredMessages, $offset, $itemsPerPage);
$pageMessages = array_map(function($msg) {
    return $msg;
}, $paginatedMessages);

$totalCount = count($allMessages);
$readCount = count(array_filter($allMessages, function($msg) {
    return $msg['is_read'] == 1;
}));
$unreadCount = $totalCount - $readCount;

$viewDetail = isset($_GET['id']) && !empty($_GET['id']);
$detailMessage = null;
if ($viewDetail) {
    $detailMessage = $service->getMessageById(intval($_GET['id']));
    if ($detailMessage && $detailMessage['is_read'] == 0) {
        $service->markAsRead(intval($_GET['id']));
    }
}

$flashMessage = $_SESSION['flash_message'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Επικοινωνίας - Πίνακας Ελέγχου</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_epikoinonia.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include_once __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>
        
        <div class="admin-content">
            <!-- Back Link - Only show in list view -->
            <?php if (!$viewDetail): ?>
                <a href="announcements.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Πίσω στον Πίνακα Ελέγχου
                </a>
            <?php endif; ?>

            <!-- Flash Messages -->
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flashType); ?>">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($flashMessage); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($viewDetail && $detailMessage): ?>
                <!-- MESSAGE DETAIL VIEW -->
                <a href="epikoinonia.php" class="back-link mb-2">
                    <i class="fas fa-arrow-left"></i>
                    Πίσω στα Μηνύματα
                </a>

                <div class="message-detail-card">
                    <div class="message-detail-header">
                        <i class="fas fa-envelope-open"></i>
                        <h2>Λεπτομέρειες Μηνύματος</h2>
                    </div>

                    <div class="message-info-grid">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user"></i>
                                ΟΝΟΜΑ ΑΠΟΣΤΟΛΕΑ
                            </div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($detailMessage['name']); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-envelope"></i>
                                ΔΙΕΥΘΥΝΣΗ EMAIL
                            </div>
                            <div class="info-value">
                                <a href="mailto:<?php echo htmlspecialchars($detailMessage['email']); ?>">
                                    <?php echo htmlspecialchars($detailMessage['email']); ?>
                                </a>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-phone"></i>
                                ΑΡΙΘΜΟΣ ΤΗΛΕΦΩΝΟΥ
                            </div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($detailMessage['phone']); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-calendar"></i>
                                ΗΕΜΡΟΜΗΝΙΑ ΛΗΨΗΣ
                            </div>
                            <div class="info-value">
                                <?php echo date('M d, Y - g:i A', strtotime($detailMessage['created_at'])); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-tag"></i>
                                ΚΑΤΑΣΤΑΣΗ
                            </div>
                            <div class="info-value">
                                <?php if ($detailMessage['is_read'] == 0): ?>
                                    <span class="badge-unread">
                                        <i class="fas fa-circle"></i>
                                        ΝΕΟ
                                    </span>
                                <?php else: ?>
                                    <span class="badge-read">
                                        <i class="fas fa-check-circle"></i>
                                        ΑΝΑΓΝΩΣΜΕΝΟ
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="message-subject-section">
                        <div class="message-subject-label">
                            ΘΕΜΑ
                        </div>
                        <div class="message-subject-text">
                            <?php echo htmlspecialchars($detailMessage['subject']); ?>
                        </div>
                    </div>

                    <div class="message-content-section">
                        <div class="message-content-label">
                            <i class="fas fa-message"></i>
                            ΠΕΡΙΕΧΟΜΕΝΟ ΜΗΝΥΜΑΤΟΣ
                        </div>
                        <div class="message-content">
                            <?php 
                                $msgText = $detailMessage['message'];
                                $msgText = preg_replace('/^\s+|\s+$/u', '', $msgText);
                                $msgText = nl2br(htmlspecialchars($msgText, ENT_QUOTES, 'UTF-8'));
                                echo $msgText;
                            ?>
                        </div>
                    </div>

                    <div class="message-actions">
                        <form method="POST" style="display: inline;" class="delete-form" data-message-id="<?php echo $detailMessage['message_id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="message_id" value="<?php echo $detailMessage['message_id']; ?>">
                            <button type="button" class="btn btn-danger delete-btn">
                                <i class="fas fa-trash"></i>
                                Διαγραφή Μηνύματος
                            </button>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <!-- LIST VIEW -->
                <!-- Page Header -->
                <div class="admin-header">
                    <h1>
                        <i class="fas fa-comments"></i>
                        Διαχείριση Επικοινωνίας
                    </h1>
                </div>

                <div id="content-management" class="content-management">
                    <div class="content-management__intro">
                        <div>
                            <h2><i class="fas fa-edit"></i> Διαχείριση Public Περιεχομένου</h2>
                            <p>Από εδώ αλλάζεις το κοινό περιεχόμενο που εμφανίζεται και στο <code>public/epikoinonia.php</code> και στο <code>public/parent/epikoinonia.php</code>. Κάθε ενότητα αποθηκεύεται ξεχωριστά, όπως και στο <code>useful-information</code>.</p>
                        </div>
                    </div>

                    <div class="content-sections-grid">
                        <section class="content-editor-card">
                            <div class="content-editor-card__header">
                                <div>
                                    <h3>Page Header</h3>
                                    <p>Τίτλος, υπότιτλος και eyebrow της κορυφής της σελίδας.</p>
                                </div>
                                <span class="content-editor-card__icon"><i class="fas fa-heading"></i></span>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="update_content_section">
                                <input type="hidden" name="section_key" value="page_header">

                                <div class="content-form-grid">
                                    <div class="form-group">
                                        <label for="page-header-title">Τίτλος</label>
                                        <input type="text" class="form-control" id="page-header-title" name="title" value="<?php echo htmlspecialchars($pageHeaderSection['title']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="page-header-eyebrow">Eyebrow</label>
                                        <input type="text" class="form-control" id="page-header-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($pageHeaderSection['content']['eyebrow'] ?? ''); ?>">
                                    </div>

                                    <div class="form-group full-width">
                                        <label for="page-header-subtitle">Υπότιτλος</label>
                                        <textarea class="form-control content-textarea" id="page-header-subtitle" name="subtitle"><?php echo htmlspecialchars($pageHeaderSection['subtitle']); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="page-header-icon">Icon class</label>
                                        <input type="text" class="form-control" id="page-header-icon" name="icon" value="<?php echo htmlspecialchars($pageHeaderSection['content']['icon'] ?? 'fas fa-envelope'); ?>">
                                    </div>
                                </div>

                                <div class="content-editor-card__actions">
                                    <button type="submit" class="btn-save-section">
                                        <i class="fas fa-save"></i> Αποθήκευση Header
                                    </button>
                                </div>
                            </form>
                        </section>

                        <section class="content-editor-card">
                            <div class="content-editor-card__header">
                                <div>
                                    <h3>Πληροφορίες Επικοινωνίας</h3>
                                    <p>Οι 4 κάρτες που εμφανίζονται στο πρώτο section της front-end σελίδας.</p>
                                </div>
                                <span class="content-editor-card__icon"><i class="fas fa-address-card"></i></span>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="update_content_section">
                                <input type="hidden" name="section_key" value="contact_info">

                                <div class="content-form-grid">
                                    <div class="form-group">
                                        <label for="contact-info-title">Τίτλος section</label>
                                        <input type="text" class="form-control" id="contact-info-title" name="title" value="<?php echo htmlspecialchars($contactInfoSection['title']); ?>">
                                    </div>

                                    <div class="form-group full-width">
                                        <label for="contact-info-subtitle">Υπότιτλος section</label>
                                        <textarea class="form-control content-textarea" id="contact-info-subtitle" name="subtitle"><?php echo htmlspecialchars($contactInfoSection['subtitle']); ?></textarea>
                                    </div>

                                    <?php for ($index = 0; $index < 4; $index++): ?>
                                        <?php
                                        $card = $contactInfoSection['content']['cards'][$index] ?? [];
                                        $cardNumber = $index + 1;
                                        ?>
                                        <div class="content-subcard">
                                            <h4>Κάρτα <?php echo $cardNumber; ?></h4>

                                            <div class="form-group">
                                                <label for="card-<?php echo $cardNumber; ?>-title">Τίτλος</label>
                                                <input type="text" class="form-control" id="card-<?php echo $cardNumber; ?>-title" name="card_<?php echo $cardNumber; ?>_title" value="<?php echo htmlspecialchars($card['title'] ?? ''); ?>">
                                            </div>

                                            <div class="form-group">
                                                <label for="card-<?php echo $cardNumber; ?>-icon">Icon class</label>
                                                <input type="text" class="form-control" id="card-<?php echo $cardNumber; ?>-icon" name="card_<?php echo $cardNumber; ?>_icon" value="<?php echo htmlspecialchars($card['icon'] ?? ''); ?>">
                                            </div>

                                            <div class="form-group">
                                                <label for="card-<?php echo $cardNumber; ?>-text">Κείμενο</label>
                                                <textarea class="form-control content-textarea" id="card-<?php echo $cardNumber; ?>-text" name="card_<?php echo $cardNumber; ?>_text"><?php echo htmlspecialchars($card['text'] ?? ''); ?></textarea>
                                                <small class="content-help">Χρησιμοποίησε νέα γραμμή όπου θέλεις αλλαγή σειράς.</small>
                                            </div>

                                            <div class="form-group">
                                                <label for="card-<?php echo $cardNumber; ?>-link-label">Link label</label>
                                                <input type="text" class="form-control" id="card-<?php echo $cardNumber; ?>-link-label" name="card_<?php echo $cardNumber; ?>_link_label" value="<?php echo htmlspecialchars($card['link_label'] ?? ''); ?>">
                                            </div>

                                            <div class="form-group">
                                                <label for="card-<?php echo $cardNumber; ?>-link-url">Link URL</label>
                                                <input type="text" class="form-control" id="card-<?php echo $cardNumber; ?>-link-url" name="card_<?php echo $cardNumber; ?>_link_url" value="<?php echo htmlspecialchars($card['link_url'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>

                                <div class="content-editor-card__actions">
                                    <button type="submit" class="btn-save-section">
                                        <i class="fas fa-save"></i> Αποθήκευση Καρτών
                                    </button>
                                </div>
                            </form>
                        </section>

                        <section class="content-editor-card">
                            <div class="content-editor-card__header">
                                <div>
                                    <h3>Χάρτης</h3>
                                    <p>Τίτλος, περιγραφή και το iframe URL του Google Maps.</p>
                                </div>
                                <span class="content-editor-card__icon"><i class="fas fa-map-marked-alt"></i></span>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="update_content_section">
                                <input type="hidden" name="section_key" value="map_section">

                                <div class="content-form-grid">
                                    <div class="form-group">
                                        <label for="map-title">Τίτλος</label>
                                        <input type="text" class="form-control" id="map-title" name="title" value="<?php echo htmlspecialchars($mapSection['title']); ?>">
                                    </div>

                                    <div class="form-group full-width">
                                        <label for="map-subtitle">Υπότιτλος</label>
                                        <textarea class="form-control content-textarea" id="map-subtitle" name="subtitle"><?php echo htmlspecialchars($mapSection['subtitle']); ?></textarea>
                                    </div>

                                    <div class="form-group full-width">
                                        <label for="map-embed-url">Iframe URL</label>
                                        <textarea class="form-control content-textarea content-textarea--large" id="map-embed-url" name="embed_url"><?php echo htmlspecialchars($mapSection['content']['embed_url'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="content-editor-card__actions">
                                    <button type="submit" class="btn-save-section">
                                        <i class="fas fa-save"></i> Αποθήκευση Χάρτη
                                    </button>
                                </div>
                            </form>
                        </section>

                        <section class="content-editor-card">
                            <div class="content-editor-card__header">
                                <div>
                                    <h3>Φόρμα Επικοινωνίας</h3>
                                    <p>Τα κείμενα που εμφανίζονται πριν και μετά την αποστολή του μηνύματος.</p>
                                </div>
                                <span class="content-editor-card__icon"><i class="fas fa-paper-plane"></i></span>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="update_content_section">
                                <input type="hidden" name="section_key" value="form_section">

                                <div class="content-form-grid">
                                    <div class="form-group">
                                        <label for="form-title">Τίτλος</label>
                                        <input type="text" class="form-control" id="form-title" name="title" value="<?php echo htmlspecialchars($formSection['title']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="form-button-text">Κείμενο κουμπιού</label>
                                        <input type="text" class="form-control" id="form-button-text" name="button_text" value="<?php echo htmlspecialchars($formSection['content']['button_text'] ?? ''); ?>">
                                    </div>

                                    <div class="form-group full-width">
                                        <label for="form-subtitle">Υπότιτλος</label>
                                        <textarea class="form-control content-textarea" id="form-subtitle" name="subtitle"><?php echo htmlspecialchars($formSection['subtitle']); ?></textarea>
                                    </div>

                                    <div class="form-group full-width">
                                        <label for="form-description">Προαιρετική περιγραφή πάνω από τη φόρμα</label>
                                        <textarea class="form-control content-textarea" id="form-description" name="description"><?php echo htmlspecialchars($formSection['content']['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="form-group full-width">
                                        <label for="form-success-message">Μήνυμα επιτυχίας</label>
                                        <textarea class="form-control content-textarea" id="form-success-message" name="success_message"><?php echo htmlspecialchars($formSection['content']['success_message'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="content-editor-card__actions">
                                    <button type="submit" class="btn-save-section">
                                        <i class="fas fa-save"></i> Αποθήκευση Φόρμας
                                    </button>
                                </div>
                            </form>
                        </section>

                        <section class="content-editor-card">
                            <div class="content-editor-card__header">
                                <div>
                                    <h3>Social Links</h3>
                                    <p>Τίτλος section, περιγραφή και οι σύνδεσμοι των social buttons.</p>
                                </div>
                                <span class="content-editor-card__icon"><i class="fas fa-share-alt"></i></span>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="action" value="update_content_section">
                                <input type="hidden" name="section_key" value="social_section">

                                <div class="content-form-grid">
                                    <div class="form-group">
                                        <label for="social-title">Τίτλος</label>
                                        <input type="text" class="form-control" id="social-title" name="title" value="<?php echo htmlspecialchars($socialSection['title']); ?>">
                                    </div>

                                    <div class="form-group full-width">
                                        <label for="social-subtitle">Υπότιτλος</label>
                                        <textarea class="form-control content-textarea" id="social-subtitle" name="subtitle"><?php echo htmlspecialchars($socialSection['subtitle']); ?></textarea>
                                    </div>

                                    <?php for ($index = 0; $index < 3; $index++): ?>
                                        <?php
                                        $item = $socialSection['content']['items'][$index] ?? [];
                                        $socialNumber = $index + 1;
                                        ?>
                                        <div class="content-subcard">
                                            <h4>Social <?php echo $socialNumber; ?></h4>

                                            <div class="form-group">
                                                <label for="social-<?php echo $socialNumber; ?>-title">Τίτλος</label>
                                                <input type="text" class="form-control" id="social-<?php echo $socialNumber; ?>-title" name="social_<?php echo $socialNumber; ?>_title" value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
                                            </div>

                                            <div class="form-group">
                                                <label for="social-<?php echo $socialNumber; ?>-icon">Icon class</label>
                                                <input type="text" class="form-control" id="social-<?php echo $socialNumber; ?>-icon" name="social_<?php echo $socialNumber; ?>_icon" value="<?php echo htmlspecialchars($item['icon'] ?? ''); ?>">
                                            </div>

                                            <div class="form-group">
                                                <label for="social-<?php echo $socialNumber; ?>-url">URL</label>
                                                <input type="text" class="form-control" id="social-<?php echo $socialNumber; ?>-url" name="social_<?php echo $socialNumber; ?>_url" value="<?php echo htmlspecialchars($item['url'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>

                                <div class="content-editor-card__actions">
                                    <button type="submit" class="btn-save-section">
                                        <i class="fas fa-save"></i> Αποθήκευση Social Links
                                    </button>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-number"><?php echo $totalCount; ?></div>
                                <div class="stat-label">ΣΥΝΟΛΟ ΜΗΝΥΜΑΤΩΝ</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-number"><?php echo $unreadCount; ?></div>
                                <div class="stat-label">ΝΕΑ ΜΗΝΥΜΑΤΑ</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-number"><?php echo $readCount; ?></div>
                                <div class="stat-label">ΑΝΑΓΝΩΣΜΕΝΑ ΜΗΝΥΜΑΤΑ</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Search & Filters Section -->
                <div class="search-filters-section">
                    <div class="search-filters-header">
                        <i class="fas fa-filter"></i>
                        <h5>ΑΝΑΖΗΤΗΣΗ & ΦΙΛΤΡΑ</h5>
                    </div>

                    <form method="GET" class="search-filters-content">
                        <div class="search-group">
                            <label for="search"><i class="fas fa-search"></i> ΑΝΑΖΗΤΗΣΗ</label>
                            <input type="text" id="search" name="search" placeholder="Αναζήτηση ανά όνομα, email, θέμα..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="status"><i class="fas fa-tag"></i> ΚΑΤΑΣΤΑΣΗ</label>
                            <select id="status" name="status">
                                <option value="">Όλες οι Καταστάσεις</option>
                                <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>Νέο</option>
                                <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Αναγνωσμένο</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="date_from"><i class="fas fa-calendar"></i> ΑΠΟ ΗΜΕΡΟΜΗΝΙΑ</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="date_to"><i class="fas fa-calendar"></i> ΕΩΣ ΗΜΕΡΟΜΗΝΙΑ</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>

                        <div>
                            <button type="submit" class="btn-search">
                                <i class="fas fa-search"></i>
                                Εφαρμογή Φίλτρων
                            </button>
                        </div>
                    </form>

                    <?php if (!empty($search) || !empty($status) || !empty($dateFrom) || !empty($dateTo)): ?>
                        <div style="margin-top: 1rem;">
                            <a href="epikoinonia.php" class="btn-reset">
                                <i class="fas fa-times"></i>
                                Εκκαθάριση Φίλτρων
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Messages Container -->
                <div class="messages-container">
                    <div class="messages-header">
                        <div class="messages-header-left">
                            <h5>
                                <i class="fas fa-list"></i>
                                ΟΛΑ ΤΑ ΜΗΝΥΜΑΤΑ
                            </h5>
                            <span class="messages-count"><?php echo count($pageMessages); ?> από <?php echo $totalMessages; ?></span>
                        </div>
                    </div>

                    <?php if (empty($pageMessages)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="empty-title">Δεν Βρέθηκαν Μηνύματα</div>
                            <div class="empty-text">
                                <?php if (!empty($search) || !empty($status) || !empty($dateFrom)): ?>
                                    Δοκιμάστε να προσαρμόσετε τα φίλτρα ή τα κριτήρια αναζήτησης
                                <?php else: ?>
                                    Δεν έχουν λαμβανθεί ακόμα μηνύματα
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">ΑΠΟΣΤΟΛΕΑΣ</th>
                                        <th style="width: 25%;">ΘΕΜΑ</th>
                                        <th style="width: 20%;">ΗΜΕΡΟΜΗΝΙΑ</th>
                                        <th style="width: 15%;">ΚΑΤΑΣΤΑΣΗ</th>
                                        <th style="width: 15%; text-align: center;">ΕΝΕΡΓΕΙΕΣ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pageMessages as $message): ?>
                                        <tr class="message-row <?php echo $message['is_read'] == 0 ? 'unread' : ''; ?>">
                                            <td>
                                                <div class="sender-info">
                                                    <div class="sender-name">
                                                        <i class="fas fa-user-circle"></i>
                                                        <?php echo htmlspecialchars($message['name']); ?>
                                                    </div>
                                                    <div class="sender-email">
                                                        <?php echo htmlspecialchars($message['email']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="message-subject">
                                                    <?php echo htmlspecialchars(substr($message['subject'], 0, 50)) . (strlen($message['subject']) > 50 ? '...' : ''); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="message-date">
                                                    <?php echo date('M d, Y', strtotime($message['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($message['is_read'] == 0): ?>
                                                    <span class="badge-unread">
                                                        <i class="fas fa-circle"></i>
                                                        Νέο
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-read">
                                                        <i class="fas fa-check-circle"></i>
                                                        Αναγνωσμένο
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="epikoinonia.php?id=<?php echo $message['message_id']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                        Προβολή
                                                    </a>
                                                    <form method="POST" style="display: inline;" 
                                                          class="delete-form" data-message-id="<?php echo $message['message_id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                                                        <button type="button" class="btn btn-sm btn-danger delete-btn">
                                                            <i class="fas fa-trash"></i>
                                                            Διαγραφή
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="d-flex justify-content-center">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF'] . '?page=1' . ($search ? '&search=' . urlencode($search) : '') . ($status ? '&status=' . urlencode($status) : '') . ($dateFrom ? '&date_from=' . urlencode($dateFrom) : ''); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                                Πρώτη
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF'] . '?page=' . ($page - 1) . ($search ? '&search=' . urlencode($search) : '') . ($status ? '&status=' . urlencode($status) : '') . ($dateFrom ? '&date_from=' . urlencode($dateFrom) : ''); ?>">
                                                Προηγούμενη
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);

                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF'] . '?page=' . $i . ($search ? '&search=' . urlencode($search) : '') . ($status ? '&status=' . urlencode($status) : '') . ($dateFrom ? '&date_from=' . urlencode($dateFrom) : ''); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF'] . '?page=' . ($page + 1) . ($search ? '&search=' . urlencode($search) : '') . ($status ? '&status=' . urlencode($status) : '') . ($dateFrom ? '&date_from=' . urlencode($dateFrom) : ''); ?>">
                                                Επόμενη
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF'] . '?page=' . $totalPages . ($search ? '&search=' . urlencode($search) : '') . ($status ? '&status=' . urlencode($status) : '') . ($dateFrom ? '&date_from=' . urlencode($dateFrom) : ''); ?>">
                                                Τελευταία
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="delete-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h4>Επιβεβαίωση Διαγραφής</h4>
            </div>
            <div class="delete-modal-body">
                <p>Θέλετε σίγουρα να διαγράψετε αυτό το μήνυμα;</p>
                <p class="text-muted">Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.</p>
            </div>
            <div class="delete-modal-footer">
                <button id="cancelBtn" class="btn btn-secondary">
                    Όχι
                </button>
                <button id="confirmBtn" class="btn btn-danger">
                    Ναι, Διαγραφή
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    
    <script>
        let pendingDeleteForm = null;
        
        // Show delete confirmation modal
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('.delete-form');
                pendingDeleteForm = form;
                document.getElementById('deleteModal').classList.add('active');
            });
        });
        
        // Cancel deletion
        document.getElementById('cancelBtn').addEventListener('click', function() {
            document.getElementById('deleteModal').classList.remove('active');
            pendingDeleteForm = null;
        });
        
        // Confirm deletion
        document.getElementById('confirmBtn').addEventListener('click', function() {
            if (pendingDeleteForm) {
                pendingDeleteForm.submit();
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                pendingDeleteForm = null;
            }
        });
    </script>
</body>
</html>
