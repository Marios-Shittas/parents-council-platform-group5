<?php
require_once __DIR__ . '/../../app/services/ParentsPageService.php';

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

function parentsAdminTrim($value)
{
    return trim((string)$value);
}

function parentsAdminTextarea($value)
{
    $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
    return trim($value);
}

function parentsAdminUrl($value)
{
    return trim((string)$value);
}

function parentsAdminTextareaToList($value)
{
    $lines = explode("\n", parentsAdminTextarea($value));
    $items = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $items[] = $line;
        }
    }

    return $items;
}

function parentsAdminTextareaToRows($value, array $keys)
{
    $lines = explode("\n", parentsAdminTextarea($value));
    $rows = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', array_pad(explode('|', $line), count($keys), ''));
        $row = [];
        $hasValue = false;

        foreach ($keys as $index => $key) {
            $row[$key] = $parts[$index] ?? '';
            if ($row[$key] !== '') {
                $hasValue = true;
            }
        }

        if ($hasValue) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function parentsAdminListToTextarea($items)
{
    return implode("\n", is_array($items) ? $items : []);
}

function parentsAdminRowsToTextarea($rows, array $keys)
{
    if (!is_array($rows)) {
        return '';
    }

    $lines = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $parts = [];
        $hasValue = false;
        foreach ($keys as $key) {
            $value = trim((string)($row[$key] ?? ''));
            $parts[] = $value;
            if ($value !== '') {
                $hasValue = true;
            }
        }

        if ($hasValue) {
            $lines[] = implode(' | ', $parts);
        }
    }

    return implode("\n", $lines);
}

function parentsAdminMergeBoardArchiveReferenceRows(array $rows, array $referenceRows)
{
    $mergedRows = is_array($rows) ? $rows : [];
    $existingYears = [];

    foreach ($mergedRows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $year = trim((string)($row['year'] ?? ''));
        if ($year !== '') {
            $existingYears[(string)preg_replace('/\s*-\s*/', '-', $year)] = true;
        }
    }

    foreach ($referenceRows as $referenceRow) {
        if (!is_array($referenceRow)) {
            continue;
        }

        $year = trim((string)($referenceRow['year'] ?? ''));
        $normalizedYear = (string)preg_replace('/\s*-\s*/', '-', $year);
        if ($year === '' || isset($existingYears[$normalizedYear])) {
            continue;
        }

        foreach ($referenceRows as $candidateRow) {
            if (is_array($candidateRow) && trim((string)($candidateRow['year'] ?? '')) === $year) {
                $mergedRows[] = $candidateRow;
            }
        }

        $existingYears[$normalizedYear] = true;
    }

    return $mergedRows;
}

function parentsAdminGroupBoardArchiveRowsByYear(array $rows)
{
    $groupedRows = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $year = trim((string)($row['year'] ?? ''));
        if ($year === '') {
            continue;
        }

        if (!isset($groupedRows[$year])) {
            $groupedRows[$year] = [];
        }

        $groupedRows[$year][] = [
            'role' => trim((string)($row['role'] ?? '')),
            'name' => trim((string)($row['name'] ?? '')),
        ];
    }

    return $groupedRows;
}

function parentsAdminBoardArchiveGroupToTextarea(array $rows)
{
    return parentsAdminRowsToTextarea($rows, ['role', 'name']);
}

function parentsAdminBoardArchiveBlocksToRows($years, $rowsPerYear)
{
    $archiveRows = [];
    $years = is_array($years) ? $years : [];
    $rowsPerYear = is_array($rowsPerYear) ? $rowsPerYear : [];

    foreach ($years as $index => $yearValue) {
        $year = trim((string)$yearValue);
        $rowsText = (string)($rowsPerYear[$index] ?? '');

        if ($year === '' && trim($rowsText) === '') {
            continue;
        }

        if ($year === '') {
            continue;
        }

        $parsedRows = parentsAdminTextareaToRows($rowsText, ['role', 'name']);
        foreach ($parsedRows as $row) {
            $archiveRows[] = [
                'year' => $year,
                'role' => trim((string)($row['role'] ?? '')),
                'name' => trim((string)($row['name'] ?? '')),
            ];
        }
    }

    return $archiveRows;
}

$parentsPageService = new ParentsPageService();
$flashMessage = $_SESSION['flash_message'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectUrl = 'parents.php';

    if ($action === 'update_content_section') {
        $sectionKey = $_POST['section_key'] ?? '';
        $saved = false;

        switch ($sectionKey) {
            case 'page_header':
                $saved = $parentsPageService->updateSection(
                    'page_header',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    parentsAdminTextarea($_POST['subtitle'] ?? ''),
                    [
                        'public_eyebrow' => parentsAdminTrim($_POST['public_eyebrow'] ?? ''),
                        'parent_eyebrow' => parentsAdminTrim($_POST['parent_eyebrow'] ?? ''),
                        'icon' => parentsAdminTrim($_POST['icon'] ?? ''),
                    ]
                );
                break;

            case 'history_section':
                $saved = $parentsPageService->updateSection(
                    'history_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    '',
                    [
                        'eyebrow' => parentsAdminTrim($_POST['eyebrow'] ?? ''),
                        'items' => parentsAdminTextareaToList($_POST['items'] ?? ''),
                    ]
                );
                break;

            case 'association_section':
                $saved = $parentsPageService->updateSection(
                    'association_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    parentsAdminTextarea($_POST['subtitle'] ?? ''),
                    [
                        'eyebrow' => parentsAdminTrim($_POST['eyebrow'] ?? ''),
                        'greeting_title' => parentsAdminTrim($_POST['greeting_title'] ?? ''),
                        'greeting_body' => parentsAdminTextarea($_POST['greeting_body'] ?? ''),
                        'purpose_title' => parentsAdminTrim($_POST['purpose_title'] ?? ''),
                        'purpose_body' => parentsAdminTextarea($_POST['purpose_body'] ?? ''),
                        'history_title' => parentsAdminTrim($_POST['history_title'] ?? ''),
                        'history_body' => parentsAdminTextarea($_POST['history_body'] ?? ''),
                        'contact_label' => parentsAdminTrim($_POST['contact_label'] ?? ''),
                        'contact_value' => parentsAdminTrim($_POST['contact_value'] ?? ''),
                    ]
                );
                break;

            case 'attendance_portal_section':
                $saved = $parentsPageService->updateSection(
                    'attendance_portal_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    parentsAdminTextarea($_POST['subtitle'] ?? ''),
                    [
                        'eyebrow' => parentsAdminTrim($_POST['eyebrow'] ?? ''),
                        'link_label' => parentsAdminTrim($_POST['link_label'] ?? ''),
                        'link_url' => parentsAdminTrim($_POST['link_url'] ?? ''),
                    ]
                );
                break;

            case 'schedule_section':
                $blocks = [];
                for ($index = 1; $index <= 2; $index++) {
                    $blocks[] = [
                        'title' => parentsAdminTrim($_POST["block_{$index}_title"] ?? ''),
                        'rows' => parentsAdminTextareaToRows($_POST["block_{$index}_rows"] ?? '', ['period', 'time']),
                    ];
                }

                $saved = $parentsPageService->updateSection(
                    'schedule_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    '',
                    [
                        'eyebrow' => parentsAdminTrim($_POST['eyebrow'] ?? ''),
                        'period_label' => parentsAdminTrim($_POST['period_label'] ?? ''),
                        'time_label' => parentsAdminTrim($_POST['time_label'] ?? ''),
                        'blocks' => $blocks,
                    ]
                );
                break;

            case 'board_section':
                $saved = $parentsPageService->updateSection(
                    'board_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    parentsAdminTextarea($_POST['subtitle'] ?? ''),
                    [
                        'eyebrow' => parentsAdminTrim($_POST['eyebrow'] ?? ''),
                        'current_board_label' => parentsAdminTrim($_POST['current_board_label'] ?? ''),
                        'position_label' => parentsAdminTrim($_POST['position_label'] ?? ''),
                        'name_label' => parentsAdminTrim($_POST['name_label'] ?? ''),
                        'committee_label' => parentsAdminTrim($_POST['committee_label'] ?? ''),
                        'contact_email_label' => parentsAdminTrim($_POST['contact_email_label'] ?? ''),
                        'contact_email_value' => parentsAdminTrim($_POST['contact_email_value'] ?? ''),
                        'board_members' => parentsAdminTextareaToRows($_POST['board_members'] ?? '', ['role', 'name']),
                        'committee_members' => parentsAdminTextareaToList($_POST['committee_members'] ?? ''),
                    ]
                );
                break;

            case 'board_archive_section':
                $saved = $parentsPageService->updateSection(
                    'board_archive_section',
                    parentsAdminTrim($_POST['title'] ?? ''),
                    parentsAdminTextarea($_POST['subtitle'] ?? ''),
                    [
                        'eyebrow' => parentsAdminTrim($_POST['eyebrow'] ?? ''),
                        'year_label' => parentsAdminTrim($_POST['year_label'] ?? ''),
                        'position_label' => parentsAdminTrim($_POST['position_label'] ?? ''),
                        'name_label' => parentsAdminTrim($_POST['name_label'] ?? ''),
                        'rows' => parentsAdminBoardArchiveBlocksToRows($_POST['archive_years'] ?? [], $_POST['archive_rows'] ?? []),
                    ]
                );
                break;

        }

        $_SESSION['flash_message'] = $saved
            ? 'Το περιεχόμενο της σελίδας Συνδεσμος Γωνεων ενημερώθηκε επιτυχώς.'
            : 'Παρουσιάστηκε σφάλμα κατά την αποθήκευση. ' . $parentsPageService->getLastError();
        $_SESSION['flash_type'] = $saved ? 'success' : 'danger';
        $redirectTab = preg_replace('/[^a-z0-9_-]/i', '', (string)$sectionKey);
        $redirectUrl .= '?active_tab=' . urlencode($redirectTab) . '#content-management';
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$sections = $parentsPageService->getAllSections();
$pageHeaderSection = $sections['page_header'];
$historySection = $sections['history_section'];
$associationSection = $sections['association_section'];
$attendancePortalSection = $sections['attendance_portal_section'];
$scheduleSection = $sections['schedule_section'];
$boardSection = $sections['board_section'];
$boardArchiveSection = $sections['board_archive_section'];
$boardArchiveRowsForEditor = parentsAdminMergeBoardArchiveReferenceRows(
    $boardArchiveSection['content']['rows'] ?? [],
    $parentsPageService->getBoardArchiveReferenceRows()
);
$boardArchiveGroupsForEditor = parentsAdminGroupBoardArchiveRowsByYear($boardArchiveRowsForEditor);
$parentsContentTabs = [
    'page_header' => ['label' => 'Header', 'icon' => 'fas fa-heading'],
    'history_section' => ['label' => 'Ιστορικό', 'icon' => 'fas fa-landmark'],
    'association_section' => ['label' => 'Σύνδεσμος', 'icon' => 'fas fa-handshake'],
    'attendance_portal_section' => ['label' => 'Επιπρόσθετα Στοιχεία', 'icon' => 'fas fa-folder-open'],
    'schedule_section' => ['label' => 'Ωράριο', 'icon' => 'fas fa-clock'],
    'board_section' => ['label' => 'Δ.Σ.', 'icon' => 'fas fa-user-friends'],
    'board_archive_section' => ['label' => 'Αρχείο Δ.Σ.', 'icon' => 'fas fa-archive'],
];
$activeParentsTab = (string)($_GET['active_tab'] ?? 'page_header');
if (!isset($parentsContentTabs[$activeParentsTab])) {
    $activeParentsTab = 'page_header';
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Διαχείριση Σελίδας Συνδεσμος Γωνεων - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_epikoinonia.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_parents.css">
</head>
<body>
<div class="admin-wrapper">
    <?php include_once __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Πίσω στο Dashboard
        </a>

        <div class="admin-header">
            <h1>
                <i class="fas fa-users"></i>
                Διαχείριση Σελίδας Συνδεσμος Γωνεων
            </h1>
        </div>

        <?php if ($flashMessage): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flashType); ?>">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $flashMessage; ?></span>
            </div>
        <?php endif; ?>

        <div id="content-management" class="content-management">
            <div class="content-management__intro">
                <div>
                    <h2><i class="fas fa-edit"></i> Διαχείριση Περιεχομένου</h2>
                    <p>Από εδώ ενημερώνεις το περιεχόμενο που προβάλλεται δημόσια στη σελίδα του Συνδέσμου Γονέων, τόσο για τους επισκέπτες όσο και για τους συνδεδεμένους γονείς. Κάθε ενότητα αποθηκεύεται ξεχωριστά, ώστε να μπορείς να διαχειρίζεσαι τίτλους, κείμενα και πληροφορίες με μεγαλύτερη ασφάλεια και ακρίβεια.</p>
                </div>
            </div>

            <ul class="nav nav-tabs admin-section-tabs mb-4" role="tablist">
                <?php foreach ($parentsContentTabs as $tabKey => $tab): ?>
                    <?php $isActiveTab = $activeParentsTab === $tabKey; ?>
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

            <div class="tab-content content-sections-grid admin-section-tabs-content">
                <section class="content-editor-card tab-pane fade <?php echo $activeParentsTab === 'page_header' ? 'show active' : ''; ?>" id="tab-page_header" role="tabpanel" aria-labelledby="tab-page_header-link">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Page Header</h3>
                            <p>Τίτλος, υπότιτλος και διαφορετικός μικρός τίτλος για δημόσια και γονική προβολή.</p>
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
                                <label for="page-header-icon">Icon class</label>
                                <input type="text" class="form-control" id="page-header-icon" name="icon" value="<?php echo htmlspecialchars($pageHeaderSection['content']['icon'] ?? 'fas fa-users'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="page-header-public-eyebrow">Μικρός τίτλος για δημόσια προβολή</label>
                                <input type="text" class="form-control" id="page-header-public-eyebrow" name="public_eyebrow" value="<?php echo htmlspecialchars($pageHeaderSection['content']['public_eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="page-header-parent-eyebrow">Μικρός τίτλος για γονική προβολή</label>
                                <input type="text" class="form-control" id="page-header-parent-eyebrow" name="parent_eyebrow" value="<?php echo htmlspecialchars($pageHeaderSection['content']['parent_eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="page-header-subtitle">Υπότιτλος</label>
                                <textarea class="form-control content-textarea" id="page-header-subtitle" name="subtitle"><?php echo htmlspecialchars($pageHeaderSection['subtitle']); ?></textarea>
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Header
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card tab-pane fade <?php echo $activeParentsTab === 'history_section' ? 'show active' : ''; ?>" id="tab-history_section" role="tabpanel" aria-labelledby="tab-history_section-link">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Ιστορικό Σχολείου</h3>
                            <p>Τίτλος ενότητας, μικρός τίτλος και bullets της πρώτης κάρτας.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-landmark"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="history_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="history-title">Τίτλος</label>
                                <input type="text" class="form-control" id="history-title" name="title" value="<?php echo htmlspecialchars($historySection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="history-eyebrow">Μικρός τίτλος ενότητας</label>
                                <input type="text" class="form-control" id="history-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($historySection['content']['eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="history-items">Bullets, μία γραμμή ανά στοιχείο</label>
                                <textarea class="form-control content-textarea content-textarea--large" id="history-items" name="items"><?php echo htmlspecialchars(parentsAdminListToTextarea($historySection['content']['items'] ?? [])); ?></textarea>
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Ιστορικού
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card tab-pane fade <?php echo $activeParentsTab === 'association_section' ? 'show active' : ''; ?>" id="tab-association_section" role="tabpanel" aria-labelledby="tab-association_section-link">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Συνδεσμος Γωνεων</h3>
                            <p>Ξεχωριστά πεδία για χαιρετισμό, σκοπό, ιστορικό και στοιχεία επικοινωνίας του Συνδεσμου Γωνεων.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-handshake"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="association_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="association-title">Τίτλος</label>
                                <input type="text" class="form-control" id="association-title" name="title" value="<?php echo htmlspecialchars($associationSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="association-eyebrow">Μικρός τίτλος ενότητας</label>
                                <input type="text" class="form-control" id="association-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($associationSection['content']['eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="association-subtitle">Εισαγωγικό κείμενο</label>
                                <textarea class="form-control content-textarea" id="association-subtitle" name="subtitle"><?php echo htmlspecialchars($associationSection['subtitle']); ?></textarea>
                            </div>

                            <div class="content-subcard">
                                <h4>Χαιρετισμός</h4>
                                <div class="form-group">
                                    <label for="association-greeting-title">Τίτλος</label>
                                    <input type="text" class="form-control" id="association-greeting-title" name="greeting_title" value="<?php echo htmlspecialchars($associationSection['content']['greeting_title'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="association-greeting-body">Κείμενο</label>
                                    <textarea class="form-control content-textarea content-textarea--large" id="association-greeting-body" name="greeting_body"><?php echo htmlspecialchars($associationSection['content']['greeting_body'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="content-subcard">
                                <h4>Σκοπός</h4>
                                <div class="form-group">
                                    <label for="association-purpose-title">Τίτλος</label>
                                    <input type="text" class="form-control" id="association-purpose-title" name="purpose_title" value="<?php echo htmlspecialchars($associationSection['content']['purpose_title'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="association-purpose-body">Κείμενο</label>
                                    <textarea class="form-control content-textarea content-textarea--large" id="association-purpose-body" name="purpose_body"><?php echo htmlspecialchars($associationSection['content']['purpose_body'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="content-subcard">
                                <h4>Ιστορικό Συνδέσμου</h4>
                                <div class="form-group">
                                    <label for="association-history-title">Τίτλος</label>
                                    <input type="text" class="form-control" id="association-history-title" name="history_title" value="<?php echo htmlspecialchars($associationSection['content']['history_title'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="association-history-body">Κείμενο</label>
                                    <textarea class="form-control content-textarea content-textarea--large" id="association-history-body" name="history_body"><?php echo htmlspecialchars($associationSection['content']['history_body'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="association-contact-label">Label επικοινωνίας</label>
                                <input type="text" class="form-control" id="association-contact-label" name="contact_label" value="<?php echo htmlspecialchars($associationSection['content']['contact_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="association-contact-value">Τιμή επικοινωνίας</label>
                                <input type="text" class="form-control" id="association-contact-value" name="contact_value" value="<?php echo htmlspecialchars($associationSection['content']['contact_value'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Συνδεσμου Γωνεων
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card tab-pane fade <?php echo $activeParentsTab === 'attendance_portal_section' ? 'show active' : ''; ?>" id="tab-attendance_portal_section" role="tabpanel" aria-labelledby="tab-attendance_portal_section-link">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Επιπρόσθετα Στοιχεία</h3>
                            <p>Περιεχόμενο για πρόσθετη κάρτα ή βοηθητικά στοιχεία που εμφανίζονται κάτω από την ενότητα του Συνδέσμου Γονέων.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-folder-open"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="attendance_portal_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="attendance-portal-title">Τίτλος</label>
                                <input type="text" class="form-control" id="attendance-portal-title" name="title" value="<?php echo htmlspecialchars($attendancePortalSection['title'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="attendance-portal-eyebrow">Μικρός τίτλος ενότητας</label>
                                <input type="text" class="form-control" id="attendance-portal-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($attendancePortalSection['content']['eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="attendance-portal-subtitle">Περιγραφή</label>
                                <textarea class="form-control content-textarea" id="attendance-portal-subtitle" name="subtitle"><?php echo htmlspecialchars($attendancePortalSection['subtitle'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="attendance-portal-link-label">Κείμενο κουμπιού</label>
                                <input type="text" class="form-control" id="attendance-portal-link-label" name="link_label" value="<?php echo htmlspecialchars($attendancePortalSection['content']['link_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="attendance-portal-link-url">URL συνδέσμου</label>
                                <input type="text" class="form-control" id="attendance-portal-link-url" name="link_url" value="<?php echo htmlspecialchars($attendancePortalSection['content']['link_url'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Επιπρόσθετων Στοιχείων
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card tab-pane fade <?php echo $activeParentsTab === 'schedule_section' ? 'show active' : ''; ?>" id="tab-schedule_section" role="tabpanel" aria-labelledby="tab-schedule_section-link">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Ωράριο</h3>
                            <p>Οι 2 πίνακες του ωραρίου. Μορφή γραμμής: <code>Περίοδος | Ώρα</code>.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-clock"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="schedule_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="schedule-title">Τίτλος</label>
                                <input type="text" class="form-control" id="schedule-title" name="title" value="<?php echo htmlspecialchars($scheduleSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="schedule-eyebrow">Μικρός τίτλος ενότητας</label>
                                <input type="text" class="form-control" id="schedule-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($scheduleSection['content']['eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="schedule-period-label">Κεφαλίδα 1ης στήλης</label>
                                <input type="text" class="form-control" id="schedule-period-label" name="period_label" value="<?php echo htmlspecialchars($scheduleSection['content']['period_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="schedule-time-label">Κεφαλίδα 2ης στήλης</label>
                                <input type="text" class="form-control" id="schedule-time-label" name="time_label" value="<?php echo htmlspecialchars($scheduleSection['content']['time_label'] ?? ''); ?>">
                            </div>

                            <?php for ($index = 0; $index < 2; $index++): ?>
                                <?php
                                $block = $scheduleSection['content']['blocks'][$index] ?? [];
                                $blockNumber = $index + 1;
                                ?>
                                <div class="content-subcard">
                                    <h4>Πίνακας <?php echo $blockNumber; ?></h4>

                                    <div class="form-group">
                                        <label for="schedule-block-<?php echo $blockNumber; ?>-title">Τίτλος πίνακα</label>
                                        <input type="text" class="form-control" id="schedule-block-<?php echo $blockNumber; ?>-title" name="block_<?php echo $blockNumber; ?>_title" value="<?php echo htmlspecialchars($block['title'] ?? ''); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="schedule-block-<?php echo $blockNumber; ?>-rows">Γραμμές</label>
                                        <textarea class="form-control content-textarea content-textarea--large" id="schedule-block-<?php echo $blockNumber; ?>-rows" name="block_<?php echo $blockNumber; ?>_rows"><?php echo htmlspecialchars(parentsAdminRowsToTextarea($block['rows'] ?? [], ['period', 'time'])); ?></textarea>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Ωραρίου
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card tab-pane fade <?php echo $activeParentsTab === 'board_section' ? 'show active' : ''; ?>" id="tab-board_section" role="tabpanel" aria-labelledby="tab-board_section-link">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Συνδεσμος Γωνεων</h3>
                            <p>Lead paragraph, labels πίνακα και μέλη Δ.Σ. Μορφή γραμμής: <code>Θέση | Ονοματεπώνυμο</code>.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-user-friends"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="board_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="board-title">Τίτλος</label>
                                <input type="text" class="form-control" id="board-title" name="title" value="<?php echo htmlspecialchars($boardSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-eyebrow">Μικρός τίτλος ενότητας</label>
                                <input type="text" class="form-control" id="board-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($boardSection['content']['eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-current-label">Label τρέχοντος συμβουλίου</label>
                                <input type="text" class="form-control" id="board-current-label" name="current_board_label" value="<?php echo htmlspecialchars($boardSection['content']['current_board_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-position-label">Κεφαλίδα στήλης θέσης</label>
                                <input type="text" class="form-control" id="board-position-label" name="position_label" value="<?php echo htmlspecialchars($boardSection['content']['position_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-name-label">Κεφαλίδα στήλης ονόματος</label>
                                <input type="text" class="form-control" id="board-name-label" name="name_label" value="<?php echo htmlspecialchars($boardSection['content']['name_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-committee-label">Label για τα μέλη</label>
                                <input type="text" class="form-control" id="board-committee-label" name="committee_label" value="<?php echo htmlspecialchars($boardSection['content']['committee_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-contact-email-label">Label email</label>
                                <input type="text" class="form-control" id="board-contact-email-label" name="contact_email_label" value="<?php echo htmlspecialchars($boardSection['content']['contact_email_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-contact-email-value">Email</label>
                                <input type="text" class="form-control" id="board-contact-email-value" name="contact_email_value" value="<?php echo htmlspecialchars($boardSection['content']['contact_email_value'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="board-subtitle">Lead κείμενο</label>
                                <textarea class="form-control content-textarea" id="board-subtitle" name="subtitle"><?php echo htmlspecialchars($boardSection['subtitle']); ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label for="board-members">Μέλη Δ.Σ.</label>
                                <textarea class="form-control content-textarea content-textarea--large" id="board-members" name="board_members"><?php echo htmlspecialchars(parentsAdminRowsToTextarea($boardSection['content']['board_members'] ?? [], ['role', 'name'])); ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label for="committee-members">Απλά μέλη, μία γραμμή ανά όνομα</label>
                                <textarea class="form-control content-textarea" id="committee-members" name="committee_members"><?php echo htmlspecialchars(parentsAdminListToTextarea($boardSection['content']['committee_members'] ?? [])); ?></textarea>
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Συνδέσμου
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card tab-pane fade <?php echo $activeParentsTab === 'board_archive_section' ? 'show active' : ''; ?>" id="tab-board_archive_section" role="tabpanel" aria-labelledby="tab-board_archive_section-link">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Συμβούλια ανά Σχολική Χρονιά</h3>
                            <p>Αρχείο συνθέσεων ανά χρονιά. Για κάθε block γράφεις γραμμές στη μορφή <code>Θέση | Ονοματεπώνυμο</code>.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-archive"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="board_archive_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="board-archive-title">Τίτλος</label>
                                <input type="text" class="form-control" id="board-archive-title" name="title" value="<?php echo htmlspecialchars($boardArchiveSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-archive-eyebrow">Μικρός τίτλος ενότητας</label>
                                <input type="text" class="form-control" id="board-archive-eyebrow" name="eyebrow" value="<?php echo htmlspecialchars($boardArchiveSection['content']['eyebrow'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="board-archive-subtitle">Εισαγωγικό κείμενο</label>
                                <textarea class="form-control content-textarea" id="board-archive-subtitle" name="subtitle"><?php echo htmlspecialchars($boardArchiveSection['subtitle']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="board-archive-year-label">Κεφαλίδα χρονιάς</label>
                                <input type="text" class="form-control" id="board-archive-year-label" name="year_label" value="<?php echo htmlspecialchars($boardArchiveSection['content']['year_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-archive-position-label">Κεφαλίδα θέσης</label>
                                <input type="text" class="form-control" id="board-archive-position-label" name="position_label" value="<?php echo htmlspecialchars($boardArchiveSection['content']['position_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="board-archive-name-label">Κεφαλίδα ονόματος</label>
                                <input type="text" class="form-control" id="board-archive-name-label" name="name_label" value="<?php echo htmlspecialchars($boardArchiveSection['content']['name_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label>Συνθέσεις ανά σχολική χρονιά</label>
                                <div class="board-archive-editor">
                                    <?php foreach ($boardArchiveGroupsForEditor as $year => $rows): ?>
                                        <div class="board-archive-editor__block">
                                            <div class="form-group">
                                                <label>Σχολική Χρονιά</label>
                                                <input type="text" class="form-control" name="archive_years[]" value="<?php echo htmlspecialchars($year); ?>">
                                            </div>
                                            <div class="form-group mb-0">
                                                <label>Μέλη για <?php echo htmlspecialchars($year); ?></label>
                                                <textarea class="form-control content-textarea content-textarea--large" name="archive_rows[]"><?php echo htmlspecialchars(parentsAdminBoardArchiveGroupToTextarea($rows)); ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="board-archive-editor__block board-archive-editor__block--new">
                                        <div class="form-group">
                                            <label>Νέα Σχολική Χρονιά</label>
                                            <input type="text" class="form-control" name="archive_years[]" value="" placeholder="π.χ. 2021-2022">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label>Μέλη νέας χρονιάς</label>
                                            <textarea class="form-control content-textarea content-textarea--large" name="archive_rows[]" placeholder="ΠΡΟΕΔΡΟΣ | Όνομα Επώνυμο&#10;ΑΝΤΙΠΡΟΕΔΡΟΣ | Όνομα Επώνυμο"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Αρχείου Συμβουλίων
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card tab-pane fade <?php echo $activeParentsTab === 'class_responsibles_section' ? 'show active' : ''; ?>" id="tab-class_responsibles_section" role="tabpanel" aria-labelledby="tab-class_responsibles_section-link">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Υπεύθυνοι Τμημάτων</h3>
                            <p>Widget, modal τίτλος και πίνακας. Μορφή γραμμής: <code>Τμήμα | Υπεύθυνος | Βοηθός | Αίθουσα</code>.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-table"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="class_responsibles_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="class-widget-title">Τίτλος widget</label>
                                <input type="text" class="form-control" id="class-widget-title" name="title" value="<?php echo htmlspecialchars($classResponsiblesSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="class-widget-button">Κείμενο κουμπιού widget</label>
                                <input type="text" class="form-control" id="class-widget-button" name="subtitle" value="<?php echo htmlspecialchars($classResponsiblesSection['subtitle']); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="class-modal-title">Τίτλος modal</label>
                                <input type="text" class="form-control" id="class-modal-title" name="modal_title" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['modal_title'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="class-label">Κεφαλίδα στήλης 1</label>
                                <input type="text" class="form-control" id="class-label" name="class_label" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['class_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="responsible-label">Κεφαλίδα στήλης 2</label>
                                <input type="text" class="form-control" id="responsible-label" name="responsible_label" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['responsible_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="assistant-label">Κεφαλίδα στήλης 3</label>
                                <input type="text" class="form-control" id="assistant-label" name="assistant_label" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['assistant_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="room-label">Κεφαλίδα στήλης 4</label>
                                <input type="text" class="form-control" id="room-label" name="room_label" value="<?php echo htmlspecialchars($classResponsiblesSection['content']['room_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="class-rows">Γραμμές πίνακα</label>
                                <textarea class="form-control content-textarea content-textarea--large" id="class-rows" name="rows"><?php echo htmlspecialchars(parentsAdminRowsToTextarea($classResponsiblesSection['content']['rows'] ?? [], ['class', 'responsible', 'assistant', 'room'])); ?></textarea>
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Υπευθύνων
                            </button>
                        </div>
                    </form>
                </section>

                <section class="content-editor-card tab-pane fade <?php echo $activeParentsTab === 'electronic_admin_section' ? 'show active' : ''; ?>" id="tab-electronic_admin_section" role="tabpanel" aria-labelledby="tab-electronic_admin_section-link">
                    <div class="content-editor-card__header">
                        <div>
                            <h3>Ηλεκτρονική Διοίκηση</h3>
                            <p>Όλα τα κείμενα του widget και του modal. Κάθε textarea list παίρνει μία γραμμή ανά βήμα.</p>
                        </div>
                        <span class="content-editor-card__icon"><i class="fas fa-laptop-house"></i></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_content_section">
                        <input type="hidden" name="section_key" value="electronic_admin_section">

                        <div class="content-form-grid">
                            <div class="form-group">
                                <label for="electronic-title">Τίτλος widget</label>
                                <input type="text" class="form-control" id="electronic-title" name="title" value="<?php echo htmlspecialchars($electronicAdminSection['title']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="electronic-subtitle">Κείμενο κουμπιού widget</label>
                                <input type="text" class="form-control" id="electronic-subtitle" name="subtitle" value="<?php echo htmlspecialchars($electronicAdminSection['subtitle']); ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="electronic-modal-title">Τίτλος modal</label>
                                <input type="text" class="form-control" id="electronic-modal-title" name="modal_title" value="<?php echo htmlspecialchars($electronicAdminSection['content']['modal_title'] ?? ''); ?>">
                            </div>

                            <div class="content-subcard">
                                <h4>Ενότητα Εγγραφής</h4>

                                <div class="form-group">
                                    <label for="registration-heading">Τίτλος</label>
                                    <input type="text" class="form-control" id="registration-heading" name="registration_heading" value="<?php echo htmlspecialchars($electronicAdminSection['content']['registration_heading'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="registration-intro">Εισαγωγικό κείμενο</label>
                                    <textarea class="form-control content-textarea" id="registration-intro" name="registration_intro"><?php echo htmlspecialchars($electronicAdminSection['content']['registration_intro'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="registration-steps">Βήματα</label>
                                    <textarea class="form-control content-textarea content-textarea--large" id="registration-steps" name="registration_steps"><?php echo htmlspecialchars(parentsAdminListToTextarea($electronicAdminSection['content']['registration_steps'] ?? [])); ?></textarea>
                                </div>
                            </div>

                            <div class="content-subcard">
                                <h4>Ενότητα Εισόδου</h4>

                                <div class="form-group">
                                    <label for="login-heading">Τίτλος</label>
                                    <input type="text" class="form-control" id="login-heading" name="login_heading" value="<?php echo htmlspecialchars($electronicAdminSection['content']['login_heading'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="login-steps">Γενικά βήματα</label>
                                    <textarea class="form-control content-textarea" id="login-steps" name="login_steps"><?php echo htmlspecialchars(parentsAdminListToTextarea($electronicAdminSection['content']['login_steps'] ?? [])); ?></textarea>
                                </div>
                            </div>

                            <div class="content-subcard">
                                <h4>Microsoft Edge</h4>

                                <div class="form-group">
                                    <label for="edge-heading">Τίτλος</label>
                                    <input type="text" class="form-control" id="edge-heading" name="edge_heading" value="<?php echo htmlspecialchars($electronicAdminSection['content']['edge_heading'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="edge-steps">Βήματα</label>
                                    <textarea class="form-control content-textarea content-textarea--large" id="edge-steps" name="edge_steps"><?php echo htmlspecialchars(parentsAdminListToTextarea($electronicAdminSection['content']['edge_steps'] ?? [])); ?></textarea>
                                </div>
                            </div>

                            <div class="content-subcard">
                                <h4>Google Chrome</h4>

                                <div class="form-group">
                                    <label for="chrome-heading">Τίτλος</label>
                                    <input type="text" class="form-control" id="chrome-heading" name="chrome_heading" value="<?php echo htmlspecialchars($electronicAdminSection['content']['chrome_heading'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="chrome-steps">Βήματα</label>
                                    <textarea class="form-control content-textarea content-textarea--large" id="chrome-steps" name="chrome_steps"><?php echo htmlspecialchars(parentsAdminListToTextarea($electronicAdminSection['content']['chrome_steps'] ?? [])); ?></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="system-link-label">Label συνδέσμου</label>
                                <input type="text" class="form-control" id="system-link-label" name="link_label" value="<?php echo htmlspecialchars($electronicAdminSection['content']['link_label'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="system-link-url">URL συνδέσμου</label>
                                <input type="text" class="form-control" id="system-link-url" name="link_url" value="<?php echo htmlspecialchars($electronicAdminSection['content']['link_url'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="content-editor-card__actions">
                            <button type="submit" class="btn-save-section">
                                <i class="fas fa-save"></i> Αποθήκευση Ηλεκτρονικής Διοίκησης
                            </button>
                        </div>
                    </form>
                </section>

            </div>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
