<?php
require_once __DIR__ . '/../../app/services/UsefulInformationService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /parents-council-platform-group5/public/login.php');
    exit;
}

function usefulInfoTrim($value)
{
    return trim((string)$value);
}

function usefulInfoTextareaToList($value)
{
    $normalized = str_replace(["\r\n", "\r"], "\n", (string)$value);
    $lines = explode("\n", $normalized);
    $items = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $items[] = $line;
        }
    }

    return $items;
}

function usefulInfoTextareaToPairs($value)
{
    $normalized = str_replace(["\r\n", "\r"], "\n", (string)$value);
    $lines = explode("\n", $normalized);
    $rows = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '|') === false) {
            continue;
        }

        [$left, $right] = array_pad(explode('|', $line, 2), 2, '');
        $left = trim($left);
        $right = trim($right);

        if ($left !== '' && $right !== '') {
            $rows[] = ['date' => $left, 'name' => $right];
        }
    }

    return $rows;
}

function usefulInfoListToTextarea($items)
{
    return implode("\n", is_array($items) ? $items : []);
}

function usefulInfoPairsToTextarea($rows)
{
    if (!is_array($rows)) {
        return '';
    }

    $lines = [];
    foreach ($rows as $row) {
        $date = trim((string)($row['date'] ?? ''));
        $name = trim((string)($row['name'] ?? ''));
        if ($date !== '' && $name !== '') {
            $lines[] = $date . ' | ' . $name;
        }
    }

    return implode("\n", $lines);
}

$usefulInformationService = new UsefulInformationService();
$sections = $usefulInformationService->getAllSections();

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sectionKey = $_POST['section_key'] ?? '';
    $saved = false;

    if ($action === 'update_section') {
        switch ($sectionKey) {
            case 'page_header':
                $saved = $usefulInformationService->updateSection(
                    'page_header',
                    usefulInfoTrim($_POST['title'] ?? ''),
                    usefulInfoTrim($_POST['subtitle'] ?? ''),
                    [
                        'eyebrow' => usefulInfoTrim($_POST['eyebrow'] ?? ''),
                    ]
                );
                break;

            case 'quick_links':
                $items = [];
                for ($i = 1; $i <= 3; $i++) {
                    $items[] = [
                        'title' => usefulInfoTrim($_POST["link_{$i}_title"] ?? ''),
                        'description' => usefulInfoTrim($_POST["link_{$i}_description"] ?? ''),
                        'url' => usefulInfoTrim($_POST["link_{$i}_url"] ?? ''),
                        'icon' => usefulInfoTrim($_POST["link_{$i}_icon"] ?? ''),
                    ];
                }

                $saved = $usefulInformationService->updateSection(
                    'quick_links',
                    usefulInfoTrim($_POST['title'] ?? ''),
                    usefulInfoTrim($_POST['subtitle'] ?? ''),
                    ['items' => $items]
                );
                break;

            case 'school_year':
                $items = [];
                for ($i = 1; $i <= 3; $i++) {
                    $items[] = [
                        'label' => usefulInfoTrim($_POST["item_{$i}_label"] ?? ''),
                        'date' => usefulInfoTrim($_POST["item_{$i}_date"] ?? ''),
                        'description' => usefulInfoTrim($_POST["item_{$i}_description"] ?? ''),
                    ];
                }

                $saved = $usefulInformationService->updateSection(
                    'school_year',
                    usefulInfoTrim($_POST['title'] ?? ''),
                    usefulInfoTrim($_POST['subtitle'] ?? ''),
                    [
                        'items' => $items,
                        'note' => usefulInfoTrim($_POST['note'] ?? ''),
                    ]
                );
                break;

            case 'holidays':
                $saved = $usefulInformationService->updateSection(
                    'holidays',
                    usefulInfoTrim($_POST['title'] ?? ''),
                    usefulInfoTrim($_POST['subtitle'] ?? ''),
                    [
                        'rows' => usefulInfoTextareaToPairs($_POST['holiday_rows'] ?? ''),
                    ]
                );
                break;

            case 'safety':
                $downloads = [];
                for ($i = 1; $i <= 3; $i++) {
                    $downloads[] = [
                        'title' => usefulInfoTrim($_POST["download_{$i}_title"] ?? ''),
                        'url' => usefulInfoTrim($_POST["download_{$i}_url"] ?? ''),
                        'icon' => usefulInfoTrim($_POST["download_{$i}_icon"] ?? ''),
                    ];
                }

                $saved = $usefulInformationService->updateSection(
                    'safety',
                    usefulInfoTrim($_POST['title'] ?? ''),
                    usefulInfoTrim($_POST['subtitle'] ?? ''),
                    [
                        'bullets' => usefulInfoTextareaToList($_POST['bullets'] ?? ''),
                        'downloads' => $downloads,
                    ]
                );
                break;

            case 'uniform':
                $cards = [];
                for ($i = 1; $i <= 3; $i++) {
                    $cards[] = [
                        'title' => usefulInfoTrim($_POST["card_{$i}_title"] ?? ''),
                        'items' => usefulInfoTextareaToList($_POST["card_{$i}_items"] ?? ''),
                    ];
                }

                $saved = $usefulInformationService->updateSection(
                    'uniform',
                    usefulInfoTrim($_POST['title'] ?? ''),
                    usefulInfoTrim($_POST['subtitle'] ?? ''),
                    [
                        'cards' => $cards,
                        'note' => usefulInfoTrim($_POST['note'] ?? ''),
                        'button_text' => usefulInfoTrim($_POST['button_text'] ?? ''),
                        'button_url' => usefulInfoTrim($_POST['button_url'] ?? ''),
                    ]
                );
                break;
        }
    }

    $_SESSION['flash_message'] = $saved
        ? 'Οι χρήσιμες πληροφορίες ενημερώθηκαν επιτυχώς.'
        : 'Παρουσιάστηκε σφάλμα κατά την αποθήκευση. ' . $usefulInformationService->getLastError();
    $_SESSION['flash_message_type'] = $saved ? 'success' : 'danger';

    $redirectTab = preg_replace('/[^a-z0-9_-]/i', '', (string)$sectionKey);
    header('Location: useful-information.php?active_tab=' . urlencode($redirectTab));
    exit;
}

$sections = $usefulInformationService->getAllSections();
$usefulInfoTabs = [
    'page_header' => ['label' => 'Header', 'icon' => 'fas fa-heading'],
    'quick_links' => ['label' => 'Σύνδεσμοι', 'icon' => 'fas fa-link'],
    'school_year' => ['label' => 'Σχολική Χρονιά', 'icon' => 'fas fa-calendar-check'],
    'holidays' => ['label' => 'Αργίες', 'icon' => 'fas fa-calendar-alt'],
    'safety' => ['label' => 'Ασφάλεια', 'icon' => 'fas fa-user-shield'],
    'uniform' => ['label' => 'Στολή', 'icon' => 'fas fa-tshirt'],
];
$activeUsefulInfoTab = (string)($_GET['active_tab'] ?? 'page_header');
if (!isset($usefulInfoTabs[$activeUsefulInfoTab])) {
    $activeUsefulInfoTab = 'page_header';
}
$pageHeader = $sections['page_header'];
$quickLinks = $sections['quick_links'];
$schoolYear = $sections['school_year'];
$holidays = $sections['holidays'];
$safety = $sections['safety'];
$uniform = $sections['uniform'];
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_useful_information.css">

    <title>Διαχείριση Χρήσιμων Πληροφοριών - Admin</title>
</head>
<body>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Πίσω στο Dashboard
        </a>

        <div class="admin-header">
            <h1><i class="fas fa-info-circle mr-2"></i>Χρήσιμες Πληροφορίες</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card card-custom page-intro">
            <p class="mb-2"><strong>Διαχείριση περιεχομένου public page</strong></p>
            <p>Από εδώ αλλάζεις τα κείμενα, links, αργίες και ενότητες που εμφανίζονται στο <code>public/useful-information.php</code>. Κάθε ενότητα αποθηκεύεται ξεχωριστά.</p>
        </div>

        <ul class="nav nav-tabs admin-section-tabs mb-4" role="tablist">
            <?php foreach ($usefulInfoTabs as $tabKey => $tab): ?>
                <?php $isActiveTab = $activeUsefulInfoTab === $tabKey; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $isActiveTab ? 'active' : ''; ?>"
                       id="tab-<?php echo htmlspecialchars($tabKey); ?>-link"
                       data-toggle="tab"
                       href="#tab-<?php echo htmlspecialchars($tabKey); ?>"
                       role="tab"
                       aria-controls="tab-<?php echo htmlspecialchars($tabKey); ?>"
                       aria-selected="<?php echo $isActiveTab ? 'true' : 'false'; ?>">
                        <i class="<?php echo htmlspecialchars($tab['icon']); ?> mr-2"></i><?php echo htmlspecialchars($tab['label']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content admin-section-tabs-content">
            <section class="card card-custom section-editor tab-pane fade <?php echo $activeUsefulInfoTab === 'page_header' ? 'show active' : ''; ?>" id="tab-page_header" role="tabpanel" aria-labelledby="tab-page_header-link">
                <div class="section-editor__header">
                    <div>
                        <h2>Header Σελίδας</h2>
                        <p>Τίτλος, υπότιτλος και υπέρτιτλος στην κορυφή της σελίδας.</p>
                    </div>
                    <span class="section-editor__icon"><i class="fas fa-heading"></i></span>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_section">
                    <input type="hidden" name="section_key" value="page_header">

                    <div class="section-form-grid">
                        <div>
                            <label><strong>Υπέρτιτλος</strong></label>
                            <input type="text" name="eyebrow" class="form-control form-control-custom" value="<?php echo htmlspecialchars($pageHeader['content']['eyebrow'] ?? ''); ?>">
                        </div>
                        <div>
                            <label><strong>Τίτλος</strong></label>
                            <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($pageHeader['title']); ?>">
                        </div>
                        <div class="full-width">
                            <label><strong>Υπότιτλος</strong></label>
                            <textarea name="subtitle" class="form-control form-control-custom"><?php echo htmlspecialchars($pageHeader['subtitle']); ?></textarea>
                        </div>
                    </div>

                    <div class="section-actions">
                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Κεφαλίδας</button>
                    </div>
                </form>
            </section>

            <section class="card card-custom section-editor tab-pane fade <?php echo $activeUsefulInfoTab === 'quick_links' ? 'show active' : ''; ?>" id="tab-quick_links" role="tabpanel" aria-labelledby="tab-quick_links-link">
                <div class="section-editor__header">
                    <div>
                        <h2>Γρήγοροι Σύνδεσμοι</h2>
                        <p>Οι τρεις cards με εξωτερικά links στην αρχή της σελίδας.</p>
                    </div>
                    <span class="section-editor__icon"><i class="fas fa-link"></i></span>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_section">
                    <input type="hidden" name="section_key" value="quick_links">

                    <div class="section-form-grid">
                        <div>
                            <label><strong>Τίτλος Ενότητας</strong></label>
                            <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($quickLinks['title']); ?>">
                        </div>
                        <div>
                            <label><strong>Υπότιτλος Ενότητας</strong></label>
                            <input type="text" name="subtitle" class="form-control form-control-custom" value="<?php echo htmlspecialchars($quickLinks['subtitle']); ?>">
                        </div>

                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <?php $item = $quickLinks['content']['items'][$i] ?? ['title' => '', 'description' => '', 'url' => '', 'icon' => '']; ?>
                            <div class="editor-subcard">
                                <h3>Σύνδεσμος <?php echo $i + 1; ?></h3>
                                <div class="form-group">
                                    <label><strong>Τίτλος</strong></label>
                                    <input type="text" name="link_<?php echo $i + 1; ?>_title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($item['title']); ?>">
                                </div>
                                <div class="form-group">
                                    <label><strong>Περιγραφή</strong></label>
                                    <textarea name="link_<?php echo $i + 1; ?>_description" class="form-control form-control-custom"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label><strong>URL</strong></label>
                                    <input type="url" name="link_<?php echo $i + 1; ?>_url" class="form-control form-control-custom" value="<?php echo htmlspecialchars($item['url']); ?>">
                                </div>
                                <div class="form-group mb-0">
                                    <label><strong>Κλάση Εικονιδίου</strong></label>
                                    <input type="text" name="link_<?php echo $i + 1; ?>_icon" class="form-control form-control-custom" value="<?php echo htmlspecialchars($item['icon']); ?>">
                                    <small class="editor-help">Παράδειγμα: <code>fas fa-school</code></small>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="section-actions">
                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Συνδέσμων</button>
                    </div>
                </form>
            </section>

            <section class="card card-custom section-editor tab-pane fade <?php echo $activeUsefulInfoTab === 'school_year' ? 'show active' : ''; ?>" id="tab-school_year" role="tabpanel" aria-labelledby="tab-school_year-link">
                <div class="section-editor__header">
                    <div>
                        <h2>Σχολική Χρονιά</h2>
                        <p>Οι τρεις κάρτες με ημερομηνίες και το ενημερωτικό note από κάτω.</p>
                    </div>
                    <span class="section-editor__icon"><i class="fas fa-calendar-check"></i></span>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_section">
                    <input type="hidden" name="section_key" value="school_year">

                    <div class="section-form-grid">
                        <div>
                            <label><strong>Τίτλος Ενότητας</strong></label>
                            <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($schoolYear['title']); ?>">
                        </div>
                        <div>
                            <label><strong>Υπότιτλος Ενότητας</strong></label>
                            <input type="text" name="subtitle" class="form-control form-control-custom" value="<?php echo htmlspecialchars($schoolYear['subtitle']); ?>">
                        </div>

                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <?php $item = $schoolYear['content']['items'][$i] ?? ['label' => '', 'date' => '', 'description' => '']; ?>
                            <div class="editor-subcard">
                                <h3>Κάρτα <?php echo $i + 1; ?></h3>
                                <div class="form-group">
                                    <label><strong>Ετικέτα Κάρτας</strong></label>
                                    <input type="text" name="item_<?php echo $i + 1; ?>_label" class="form-control form-control-custom" value="<?php echo htmlspecialchars($item['label']); ?>">
                                </div>
                                <div class="form-group">
                                    <label><strong>Ημερομηνία</strong></label>
                                    <input type="text" name="item_<?php echo $i + 1; ?>_date" class="form-control form-control-custom" value="<?php echo htmlspecialchars($item['date']); ?>">
                                </div>
                                <div class="form-group mb-0">
                                    <label><strong>Περιγραφή</strong></label>
                                    <textarea name="item_<?php echo $i + 1; ?>_description" class="form-control form-control-custom"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <div class="full-width">
                            <label><strong>Σημείωση Ενότητας</strong></label>
                            <textarea name="note" class="form-control form-control-custom"><?php echo htmlspecialchars($schoolYear['content']['note'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="section-actions">
                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Σχολικής Χρονιάς</button>
                    </div>
                </form>
            </section>

            <section class="card card-custom section-editor tab-pane fade <?php echo $activeUsefulInfoTab === 'holidays' ? 'show active' : ''; ?>" id="tab-holidays" role="tabpanel" aria-labelledby="tab-holidays-link">
                <div class="section-editor__header">
                    <div>
                        <h2>Επίσημες Αργίες</h2>
                        <p>Μία γραμμή ανά αργία, με μορφή <code>Ημερομηνία | Τίτλος αργίας</code>.</p>
                    </div>
                    <span class="section-editor__icon"><i class="fas fa-calendar-alt"></i></span>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_section">
                    <input type="hidden" name="section_key" value="holidays">

                    <div class="section-form-grid">
                        <div>
                            <label><strong>Τίτλος Ενότητας</strong></label>
                            <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($holidays['title']); ?>">
                        </div>
                        <div>
                            <label><strong>Υπότιτλος Ενότητας</strong></label>
                            <input type="text" name="subtitle" class="form-control form-control-custom" value="<?php echo htmlspecialchars($holidays['subtitle']); ?>">
                        </div>
                        <div class="full-width">
                            <label><strong>Αργίες</strong></label>
                            <textarea name="holiday_rows" class="form-control form-control-custom textarea-xl"><?php echo htmlspecialchars(usefulInfoPairsToTextarea($holidays['content']['rows'] ?? [])); ?></textarea>
                            <small class="editor-help">Παράδειγμα: <code>25 Μαρτίου 2026 | Εθνική Επέτειος</code></small>
                        </div>
                    </div>

                    <div class="section-actions">
                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Αργιών</button>
                    </div>
                </form>
            </section>

            <section class="card card-custom section-editor tab-pane fade <?php echo $activeUsefulInfoTab === 'safety' ? 'show active' : ''; ?>" id="tab-safety" role="tabpanel" aria-labelledby="tab-safety-link">
                <div class="section-editor__header">
                    <div>
                        <h2>Ασφάλεια & Λήψεις</h2>
                        <p>Bullet points αριστερά και downloadable links δεξιά.</p>
                    </div>
                    <span class="section-editor__icon"><i class="fas fa-user-shield"></i></span>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_section">
                    <input type="hidden" name="section_key" value="safety">

                    <div class="section-form-grid">
                        <div>
                            <label><strong>Τίτλος Ενότητας</strong></label>
                            <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($safety['title']); ?>">
                        </div>
                        <div>
                            <label><strong>Υπότιτλος Ενότητας</strong></label>
                            <input type="text" name="subtitle" class="form-control form-control-custom" value="<?php echo htmlspecialchars($safety['subtitle']); ?>">
                        </div>
                        <div class="editor-subcard">
                            <h3>Σημεία Λίστας</h3>
                            <label><strong>Ένα item ανά γραμμή</strong></label>
                            <textarea name="bullets" class="form-control form-control-custom textarea-xl"><?php echo htmlspecialchars(usefulInfoListToTextarea($safety['content']['bullets'] ?? [])); ?></textarea>
                        </div>

                        <div class="editor-subcard">
                            <h3>Λήψεις</h3>
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <?php $download = $safety['content']['downloads'][$i] ?? ['title' => '', 'url' => '', 'icon' => '']; ?>
                                <div class="mb-3">
                                    <label><strong>Τίτλος <?php echo $i + 1; ?></strong></label>
                                    <input type="text" name="download_<?php echo $i + 1; ?>_title" class="form-control form-control-custom mb-2" value="<?php echo htmlspecialchars($download['title']); ?>">
                                    <input type="url" name="download_<?php echo $i + 1; ?>_url" class="form-control form-control-custom mb-2" value="<?php echo htmlspecialchars($download['url']); ?>" placeholder="https://...">
                                    <input type="text" name="download_<?php echo $i + 1; ?>_icon" class="form-control form-control-custom" value="<?php echo htmlspecialchars($download['icon']); ?>" placeholder="fas fa-download">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="section-actions">
                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Ασφάλειας</button>
                    </div>
                </form>
            </section>

            <section class="card card-custom section-editor tab-pane fade <?php echo $activeUsefulInfoTab === 'uniform' ? 'show active' : ''; ?>" id="tab-uniform" role="tabpanel" aria-labelledby="tab-uniform-link">
                <div class="section-editor__header">
                    <div>
                        <h2>Μαθητική Στολή</h2>
                        <p>Τρεις κάρτες με λίστες στολής, note και κουμπί κανονισμών.</p>
                    </div>
                    <span class="section-editor__icon"><i class="fas fa-tshirt"></i></span>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_section">
                    <input type="hidden" name="section_key" value="uniform">

                    <div class="section-form-grid">
                        <div>
                            <label><strong>Τίτλος Ενότητας</strong></label>
                            <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($uniform['title']); ?>">
                        </div>
                        <div>
                            <label><strong>Υπότιτλος Ενότητας</strong></label>
                            <input type="text" name="subtitle" class="form-control form-control-custom" value="<?php echo htmlspecialchars($uniform['subtitle']); ?>">
                        </div>

                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <?php $card = $uniform['content']['cards'][$i] ?? ['title' => '', 'items' => []]; ?>
                            <div class="editor-subcard">
                                <h3>Κάρτα <?php echo $i + 1; ?></h3>
                                <div class="form-group">
                                    <label><strong>Τίτλος</strong></label>
                                    <input type="text" name="card_<?php echo $i + 1; ?>_title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($card['title']); ?>">
                                </div>
                                <div class="form-group mb-0">
                                    <label><strong>Στοιχεία λίστας</strong></label>
                                    <textarea name="card_<?php echo $i + 1; ?>_items" class="form-control form-control-custom textarea-tall"><?php echo htmlspecialchars(usefulInfoListToTextarea($card['items'] ?? [])); ?></textarea>
                                    <small class="editor-help">Ένα item ανά γραμμή.</small>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <div class="full-width">
                            <label><strong>Σημείωση Ενότητας</strong></label>
                            <textarea name="note" class="form-control form-control-custom"><?php echo htmlspecialchars($uniform['content']['note'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label><strong>Κείμενο Κουμπιού</strong></label>
                            <input type="text" name="button_text" class="form-control form-control-custom" value="<?php echo htmlspecialchars($uniform['content']['button_text'] ?? ''); ?>">
                        </div>
                        <div>
                            <label><strong>URL Κανονισμών</strong></label>
                            <input type="url" name="button_url" class="form-control form-control-custom" value="<?php echo htmlspecialchars($uniform['content']['button_url'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="section-actions">
                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save mr-1"></i>Αποθήκευση Στολής</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
