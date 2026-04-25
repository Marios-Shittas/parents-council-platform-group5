<?php
require_once __DIR__ . '/../../includes/site_context.php';
require_once __DIR__ . '/../../services/ParentsPageService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Leitourgia parentsPageRenderMultiline: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentsPageRenderMultiline($value)
{
    return nl2br(htmlspecialchars(parentsPageNormalizeText($value), ENT_QUOTES, 'UTF-8'));
}

// Leitourgia parentsPageNormalizeText: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentsPageNormalizeText($value)
{
    return is_array($value) ? '' : trim((string)$value);
}

// Leitourgia parentsPageSanitizeList: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentsPageSanitizeList($items)
{
    if (!is_array($items)) {
        return [];
    }

    $sanitizedItems = [];
    foreach ($items as $item) {
        $item = parentsPageNormalizeText($item);
        if ($item !== '') {
            $sanitizedItems[] = $item;
        }
    }
    return $sanitizedItems;
}

// Leitourgia parentsPageSanitizeRows: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentsPageSanitizeRows($rows)
{
    if (!is_array($rows)) {
        return [];
    }
    $sanitizedRows = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $sanitizedRow = [];
            foreach ($row as $key => $cell) {
                $sanitizedRow[$key] = parentsPageNormalizeText($cell);
            }
            // Only add non-empty rows
            if (count(array_filter($sanitizedRow, function ($cell) { return $cell !== ''; })) > 0) {
                $sanitizedRows[] = $sanitizedRow;
            }
        }
    }
    return $sanitizedRows;
}

// Leitourgia parentsPageSanitizeScheduleBlocks: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentsPageSanitizeScheduleBlocks($blocks)
{
    if (!is_array($blocks)) {
        return [];
    }

    $sanitizedBlocks = [];
    foreach ($blocks as $block) {
        if (!is_array($block)) {
            continue;
        }

        $sanitizedBlock = [
            'title' => parentsPageNormalizeText($block['title'] ?? ''),
            'rows' => parentsPageSanitizeRows($block['rows'] ?? []),
        ];

        if ($sanitizedBlock['title'] === '' && empty($sanitizedBlock['rows'])) {
            continue;
        }

        $sanitizedBlocks[] = $sanitizedBlock;
    }

    return $sanitizedBlocks;
}

// Leitourgia parentsPageGroupArchiveRowsByYear: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentsPageGroupArchiveRowsByYear($rows)
{
    $grouped = [];

    foreach (parentsPageSanitizeRows($rows) as $row) {
        $year = trim((string)($row['year'] ?? ''));
        if ($year === '') {
            $year = 'Χωρίς σχολική χρονιά';
        }

        if (!isset($grouped[$year])) {
            $grouped[$year] = [];
        }

        $grouped[$year][] = $row;
    }

    return $grouped;
}

// Leitourgia parentsPageMergeBoardArchiveReferenceRows: xeirizetai to antistoixo kommati tis selidas i tou service.
function parentsPageMergeBoardArchiveReferenceRows(array $rows, array $referenceRows)
{
    $mergedRows = parentsPageSanitizeRows($rows);
    $existingYears = [];

    foreach ($mergedRows as $row) {
        $year = trim((string)($row['year'] ?? ''));
        if ($year !== '') {
            $existingYears[(string)preg_replace('/\s*-\s*/', '-', $year)] = true;
        }
    }

    foreach (parentsPageSanitizeRows($referenceRows) as $referenceRow) {
        $year = trim((string)($referenceRow['year'] ?? ''));
        $normalizedYear = (string)preg_replace('/\s*-\s*/', '-', $year);
        if ($year === '' || isset($existingYears[$normalizedYear])) {
            continue;
        }

        foreach (parentsPageSanitizeRows($referenceRows) as $candidateRow) {
            if (trim((string)($candidateRow['year'] ?? '')) === $year) {
                $mergedRows[] = $candidateRow;
            }
        }

        $existingYears[$normalizedYear] = true;
    }

    return $mergedRows;
}

$parentsPageService = new ParentsPageService();
$sections = $parentsPageService->getAllSections();
$pageHeaderSection = $sections['page_header'] ?? ['title' => 'Σύνδεσμος Γονέων', 'subtitle' => '', 'content' => []];
$historySection = $sections['history_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$associationSection = $sections['association_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$attendancePortalSection = $sections['attendance_portal_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$scheduleSection = $sections['schedule_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$boardSection = $sections['board_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$boardArchiveSection = $sections['board_archive_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$classResponsiblesSection = $sections['class_responsibles_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$electronicAdminSection = $sections['electronic_admin_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];

$historyItems = parentsPageSanitizeList($historySection['content']['items'] ?? []);
$scheduleBlocks = parentsPageSanitizeScheduleBlocks($scheduleSection['content']['blocks'] ?? []);
$boardMembers = parentsPageSanitizeRows($boardSection['content']['board_members'] ?? []);
$committeeMembers = parentsPageSanitizeList($boardSection['content']['committee_members'] ?? []);
$boardArchiveRows = parentsPageGroupArchiveRowsByYear(
    parentsPageMergeBoardArchiveReferenceRows(
        $boardArchiveSection['content']['rows'] ?? [],
        $parentsPageService->getBoardArchiveReferenceRows()
    )
);
$classResponsibles = parentsPageSanitizeRows($classResponsiblesSection['content']['rows'] ?? []);
$registrationSteps = parentsPageSanitizeList($electronicAdminSection['content']['registration_steps'] ?? []);
$loginSteps = parentsPageSanitizeList($electronicAdminSection['content']['login_steps'] ?? []);
$edgeSteps = parentsPageSanitizeList($electronicAdminSection['content']['edge_steps'] ?? []);
$chromeSteps = parentsPageSanitizeList($electronicAdminSection['content']['chrome_steps'] ?? []);

$pageHeaderTitle = str_replace('Συνδεσμος Γωνεων', 'Σύνδεσμος Γονέων', (string)($pageHeaderSection['title'] ?? ''));
$pageHeaderSubtitle = str_replace('Συνδεσμος Γωνεων', 'Σύνδεσμος Γονέων', (string)($pageHeaderSection['subtitle'] ?? ''));
$pageHeaderIcon = $pageHeaderSection['content']['icon'] ?? 'fas fa-users';
$pageHeaderEyebrow = site_is_parent()
    ? ($pageHeaderSection['content']['parent_eyebrow'] ?? 'Χώρος Γονέα')
    : ($pageHeaderSection['content']['public_eyebrow'] ?? 'Δημόσια Πύλη');
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
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/parents.css'); ?>">
    <title><?php echo htmlspecialchars($pageHeaderTitle); ?> - Γυμνάσιο Αγίου Αθανασίου</title>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php include __DIR__ . '/../../includes/public_page_header.php'; ?>

<main class="parents-page pb-5">
    <div class="container">
        <div class="parents-layout">
            <section class="parents-main">
                <div class="parents-card parents-card--history">
                    <div class="parents-section-heading">
                        <span class="parents-section-heading__icon"><i class="fas fa-landmark"></i></span>
                        <div>
                            <p class="parents-section-heading__eyebrow"><?php echo htmlspecialchars($historySection['content']['eyebrow'] ?? ''); ?></p>
                            <h2><?php echo htmlspecialchars($historySection['title']); ?></h2>
                        </div>
                    </div>

                    <?php if (!empty($historyItems)): ?>
                        <ul class="parents-history-list mb-0">
                            <?php foreach ($historyItems as $item): ?>
                                <li><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="parents-card parents-card--association">
                    <div class="parents-section-heading">
                        <span class="parents-section-heading__icon"><i class="fas fa-handshake"></i></span>
                        <div>
                            <p class="parents-section-heading__eyebrow"><?php echo htmlspecialchars($associationSection['content']['eyebrow'] ?? ''); ?></p>
                            <h2><?php echo htmlspecialchars($associationSection['title']); ?></h2>
                        </div>
                    </div>

                    <?php if (trim((string)$associationSection['subtitle']) !== ''): ?>
                        <p class="parents-lead"><?php echo parentsPageRenderMultiline($associationSection['subtitle']); ?></p>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-4 mb-3">
                            <article class="content-card h-100">
                                <h3><?php echo htmlspecialchars($associationSection['content']['greeting_title'] ?? 'Χαιρετισμός'); ?></h3>
                                <p class="mb-0"><?php echo parentsPageRenderMultiline($associationSection['content']['greeting_body'] ?? ''); ?></p>
                            </article>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <article class="content-card h-100">
                                <h3><?php echo htmlspecialchars($associationSection['content']['purpose_title'] ?? 'Σκοπός'); ?></h3>
                                <p class="mb-0"><?php echo parentsPageRenderMultiline($associationSection['content']['purpose_body'] ?? ''); ?></p>
                            </article>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <article class="content-card h-100">
                                <h3><?php echo htmlspecialchars($associationSection['content']['history_title'] ?? 'Ιστορικό'); ?></h3>
                                <p class="mb-0"><?php echo parentsPageRenderMultiline($associationSection['content']['history_body'] ?? ''); ?></p>
                            </article>
                        </div>
                        <div class="col-12">
                            <article class="content-card parents-link-card h-100">
                                <div class="parents-link-card__content">
                                    <?php if (trim((string)($attendancePortalSection['content']['eyebrow'] ?? '')) !== ''): ?>
                                        <p class="parents-section-heading__eyebrow mb-2"><?php echo htmlspecialchars($attendancePortalSection['content']['eyebrow'] ?? ''); ?></p>
                                    <?php endif; ?>
                                    <h3><?php echo htmlspecialchars($attendancePortalSection['title'] ?? 'Πύλη Απουσιολογίου'); ?></h3>
                                    <?php if (trim((string)($attendancePortalSection['subtitle'] ?? '')) !== ''): ?>
                                        <p class="mb-0"><?php echo parentsPageRenderMultiline($attendancePortalSection['subtitle'] ?? ''); ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php if (trim((string)($attendancePortalSection['content']['link_url'] ?? '')) !== ''): ?>
                                    <a class="btn parents-link-card__button"
                                       href="<?php echo htmlspecialchars($attendancePortalSection['content']['link_url'] ?? ''); ?>"
                                       target="_blank"
                                       rel="noopener noreferrer">
                                        <i class="fas fa-external-link-alt mr-2"></i><?php echo htmlspecialchars($attendancePortalSection['content']['link_label'] ?? 'Μετάβαση στην Πύλη'); ?>
                                    </a>
                                <?php endif; ?>
                            </article>
                        </div>
                    </div>
                </div>

                <div class="parents-card parents-card--schedule">
                    <div class="parents-section-heading">
                        <span class="parents-section-heading__icon"><i class="fas fa-clock"></i></span>
                        <div>
                            <p class="parents-section-heading__eyebrow"><?php echo htmlspecialchars($scheduleSection['content']['eyebrow'] ?? ''); ?></p>
                            <h2><?php echo htmlspecialchars($scheduleSection['title']); ?></h2>
                        </div>
                    </div>

                    <div class="parents-timetable-grid">
                        <?php foreach ($scheduleBlocks as $block): ?>
                            <?php $rows = parentsPageSanitizeRows($block['rows'] ?? []); ?>
                            <article class="parents-timetable-block">
                                <h3><?php echo htmlspecialchars($block['title'] ?? ''); ?></h3>
                                <div class="table-responsive">
                                    <table class="table parents-table parents-table--compact mb-0">
                                        <thead>
                                            <tr>
                                                <th><?php echo htmlspecialchars($scheduleSection['content']['period_label'] ?? 'Περίοδος'); ?></th>
                                                <th><?php echo htmlspecialchars($scheduleSection['content']['time_label'] ?? 'Ώρα'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rows as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['period'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($row['time'] ?? ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="parents-card parents-card--board">
                    <div class="parents-section-heading">
                        <span class="parents-section-heading__icon"><i class="fas fa-user-friends"></i></span>
                        <div>
                            <p class="parents-section-heading__eyebrow"><?php echo htmlspecialchars($boardSection['content']['eyebrow'] ?? ''); ?></p>
                            <h2><?php echo htmlspecialchars($boardSection['title']); ?></h2>
                        </div>
                    </div>

                    <?php if (trim((string)$boardSection['subtitle']) !== ''): ?>
                        <p class="parents-lead"><?php echo parentsPageRenderMultiline($boardSection['subtitle']); ?></p>
                    <?php endif; ?>

                    <?php if (trim((string)($boardSection['content']['current_board_label'] ?? '')) !== ''): ?>
                        <p class="parents-section-heading__eyebrow mb-3"><?php echo htmlspecialchars($boardSection['content']['current_board_label']); ?></p>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table parents-table mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo htmlspecialchars($boardSection['content']['position_label'] ?? 'Θέση'); ?></th>
                                    <th><?php echo htmlspecialchars($boardSection['content']['name_label'] ?? 'Ονοματεπώνυμο'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($boardMembers as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['role'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($member['name'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php foreach ($committeeMembers as $index => $member): ?>
                                    <tr>
                                        <?php if ($index === 0): ?>
                                            <td rowspan="<?php echo count($committeeMembers); ?>">
                                                <?php echo htmlspecialchars($boardSection['content']['committee_label'] ?? 'Μέλη'); ?>
                                            </td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($member); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="parents-card parents-card--board-archive">
                    <div class="parents-section-heading">
                        <span class="parents-section-heading__icon"><i class="fas fa-archive"></i></span>
                        <div>
                            <p class="parents-section-heading__eyebrow"><?php echo htmlspecialchars($boardArchiveSection['content']['eyebrow'] ?? ''); ?></p>
                            <h2><?php echo htmlspecialchars($boardArchiveSection['title']); ?></h2>
                        </div>
                    </div>

                    <?php if (trim((string)$boardArchiveSection['subtitle']) !== ''): ?>
                        <p class="parents-lead"><?php echo parentsPageRenderMultiline($boardArchiveSection['subtitle']); ?></p>
                    <?php endif; ?>

                    <?php if (empty($boardArchiveRows)): ?>
                        <p class="mb-0">Δεν έχουν προστεθεί ακόμη συμβούλια ανά σχολική χρονιά.</p>
                    <?php else: ?>
                        <?php $archiveIndex = 0; ?>
                        <?php foreach ($boardArchiveRows as $schoolYear => $rows): ?>
                            <details class="parents-archive-year mb-3" <?php echo $archiveIndex === 0 ? 'open' : ''; ?>>
                                <summary class="parents-archive-year__summary">
                                    <span class="parents-archive-year__title">Συμβούλιο σχολικής χρονιάς <?php echo htmlspecialchars($schoolYear); ?></span>
                                    <span class="parents-archive-year__icon" aria-hidden="true">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </summary>

                                <div class="parents-archive-year__content">
                                    <div class="table-responsive">
                                        <table class="table parents-table parents-table--compact mb-0">
                                            <thead>
                                                <tr>
                                                    <th><?php echo htmlspecialchars($boardArchiveSection['content']['position_label'] ?? 'Θέση'); ?></th>
                                                    <th><?php echo htmlspecialchars($boardArchiveSection['content']['name_label'] ?? 'Ονοματεπώνυμο'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $row): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['role'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </details>
                            <?php $archiveIndex++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
