<?php
require_once __DIR__ . '/../../includes/site_context.php';
require_once __DIR__ . '/../../services/ParentsPageService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function parentsPageRenderMultiline($value)
{
    return nl2br(htmlspecialchars(parentsPageNormalizeText($value), ENT_QUOTES, 'UTF-8'));
}

function parentsPageNormalizeText($value)
{
    return is_array($value) ? '' : trim((string)$value);
}

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

$parentsPageService = new ParentsPageService();
$sections = $parentsPageService->getAllSections();
$galleryImages = $parentsPageService->getGalleryImages();

$pageHeaderSection = $sections['page_header'] ?? ['title' => 'Συνδεσμος Γωνεων', 'subtitle' => '', 'content' => []];
$historySection = $sections['history_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$associationSection = $sections['association_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$scheduleSection = $sections['schedule_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$boardSection = $sections['board_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$boardArchiveSection = $sections['board_archive_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$classResponsiblesSection = $sections['class_responsibles_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$electronicAdminSection = $sections['electronic_admin_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];
$gallerySection = $sections['gallery_section'] ?? ['title' => '', 'subtitle' => '', 'content' => []];

$historyItems = parentsPageSanitizeList($historySection['content']['items'] ?? []);
$scheduleBlocks = parentsPageSanitizeScheduleBlocks($scheduleSection['content']['blocks'] ?? []);
$boardMembers = parentsPageSanitizeRows($boardSection['content']['board_members'] ?? []);
$committeeMembers = parentsPageSanitizeList($boardSection['content']['committee_members'] ?? []);
$boardArchiveRows = parentsPageGroupArchiveRowsByYear($boardArchiveSection['content']['rows'] ?? []);
$classResponsibles = parentsPageSanitizeRows($classResponsiblesSection['content']['rows'] ?? []);
$registrationSteps = parentsPageSanitizeList($electronicAdminSection['content']['registration_steps'] ?? []);
$loginSteps = parentsPageSanitizeList($electronicAdminSection['content']['login_steps'] ?? []);
$edgeSteps = parentsPageSanitizeList($electronicAdminSection['content']['edge_steps'] ?? []);
$chromeSteps = parentsPageSanitizeList($electronicAdminSection['content']['chrome_steps'] ?? []);
$showParentsGallery = site_is_parent() && (($_SESSION['role'] ?? '') === 'parent');

$pageHeaderTitle = $pageHeaderSection['title'];
$pageHeaderSubtitle = $pageHeaderSection['subtitle'];
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

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/public-page-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/parents.css'); ?>">
    <title>Συνδεσμος Γωνεων - Γυμνάσιο Αγίου Αθανασίου</title>
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
                    </div>

                    <?php if (trim((string)($associationSection['content']['contact_value'] ?? '')) !== ''): ?>
                        <div class="note-card mt-2">
                            <i class="fas fa-envelope"></i>
                            <p class="mb-0">
                                <strong><?php echo htmlspecialchars($associationSection['content']['contact_label'] ?? 'Επικοινωνία'); ?>:</strong>
                                <a href="mailto:<?php echo htmlspecialchars($associationSection['content']['contact_value']); ?>">
                                    <?php echo htmlspecialchars($associationSection['content']['contact_value']); ?>
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>
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

                    <?php if (trim((string)($boardSection['content']['contact_email_value'] ?? '')) !== ''): ?>
                        <div class="note-card mt-3">
                            <i class="fas fa-envelope-open-text"></i>
                            <p class="mb-0">
                                <strong><?php echo htmlspecialchars($boardSection['content']['contact_email_label'] ?? 'Email'); ?>:</strong>
                                <a href="mailto:<?php echo htmlspecialchars($boardSection['content']['contact_email_value']); ?>">
                                    <?php echo htmlspecialchars($boardSection['content']['contact_email_value']); ?>
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>
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
                        <?php foreach ($boardArchiveRows as $schoolYear => $rows): ?>
                            <article class="parents-timetable-block mb-3">
                                <h3><?php echo htmlspecialchars($schoolYear); ?></h3>
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
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="parents-sidebar">
                <div class="parents-widget">
                    <h3><i class="fas fa-users-cog"></i> <?php echo htmlspecialchars($classResponsiblesSection['title']); ?></h3>
                    <button type="button" class="btn btn-outline-primary parents-modal-trigger w-100" data-toggle="modal" data-target="#classResponsiblesModal">
                        <?php echo htmlspecialchars($classResponsiblesSection['subtitle']); ?>
                    </button>
                </div>

                <div class="parents-widget">
                    <h3><i class="fas fa-laptop-house"></i> <?php echo htmlspecialchars($electronicAdminSection['title']); ?></h3>
                    <button type="button" class="btn btn-outline-primary parents-modal-trigger w-100" data-toggle="modal" data-target="#electronicAdminModal">
                        <?php echo htmlspecialchars($electronicAdminSection['subtitle']); ?>
                    </button>
                </div>

                <?php if ($showParentsGallery): ?>
                    <div class="parents-widget">
                        <h3><i class="fas fa-camera"></i> <?php echo htmlspecialchars($gallerySection['title']); ?></h3>
                        <p class="parents-widget-note">Το φωτογραφικό υλικό από σχολικές δράσεις και εκδηλώσεις αναρτάται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.</p>
                        <?php if (!empty($galleryImages)): ?>
                            <div class="parents-gallery">
                                <?php foreach ($galleryImages as $image): ?>
                                    <?php
                                    $fullImage = $image['full_image_path'] ?? '';
                                    $thumbImage = $image['thumb_image_path'] ?: $fullImage;
                                    $altText = trim((string)($image['alt_text'] ?? '')) !== '' ? $image['alt_text'] : 'Φωτογραφικό υλικό σχολείου';
                                    ?>
                                    <a href="<?php echo htmlspecialchars($fullImage); ?>" target="_blank" rel="noopener noreferrer">
                                        <img src="<?php echo htmlspecialchars($thumbImage); ?>" alt="<?php echo htmlspecialchars($altText); ?>" loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="mb-0"><?php echo htmlspecialchars($gallerySection['content']['empty_message'] ?? 'Δεν έχουν προστεθεί ακόμη φωτογραφίες.'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>

<div class="modal fade" id="classResponsiblesModal" tabindex="-1" role="dialog" aria-labelledby="classResponsiblesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="classResponsiblesModalLabel"><?php echo htmlspecialchars($classResponsiblesSection['content']['modal_title'] ?? ''); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table parents-table parents-table--compact parents-modal-table mb-0">
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars($classResponsiblesSection['content']['class_label'] ?? 'ΤΜΗΜΑ'); ?></th>
                                <th><?php echo htmlspecialchars($classResponsiblesSection['content']['responsible_label'] ?? 'ΥΠΕΥΘΥΝΟΣ ΤΜΗΜΑΤΟΣ'); ?></th>
                                <th><?php echo htmlspecialchars($classResponsiblesSection['content']['assistant_label'] ?? 'ΥΠΕΥΘΥΝΟΣ ΒΟΗΘΟΣ ΔΙΕΥΘΥΝΤΗΣ'); ?></th>
                                <th><?php echo htmlspecialchars($classResponsiblesSection['content']['room_label'] ?? 'ΑΙΘΟΥΣΑ'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classResponsibles as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['class'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['responsible'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['assistant'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['room'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="electronicAdminModal" tabindex="-1" role="dialog" aria-labelledby="electronicAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="electronicAdminModalLabel"><?php echo htmlspecialchars($electronicAdminSection['content']['modal_title'] ?? ''); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body parents-info-modal-body">
                <?php if (trim((string)($electronicAdminSection['content']['registration_heading'] ?? '')) !== ''): ?>
                    <h6><?php echo htmlspecialchars($electronicAdminSection['content']['registration_heading']); ?></h6>
                <?php endif; ?>

                <?php if (trim((string)($electronicAdminSection['content']['registration_intro'] ?? '')) !== ''): ?>
                    <p><?php echo parentsPageRenderMultiline($electronicAdminSection['content']['registration_intro']); ?></p>
                <?php endif; ?>

                <?php if (!empty($registrationSteps)): ?>
                    <ol>
                        <?php foreach ($registrationSteps as $step): ?>
                            <li><?php echo htmlspecialchars($step); ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <?php if (trim((string)($electronicAdminSection['content']['login_heading'] ?? '')) !== ''): ?>
                    <h6><?php echo htmlspecialchars($electronicAdminSection['content']['login_heading']); ?></h6>
                <?php endif; ?>

                <?php if (!empty($loginSteps)): ?>
                    <ol>
                        <?php foreach ($loginSteps as $step): ?>
                            <li><?php echo htmlspecialchars($step); ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <?php if (trim((string)($electronicAdminSection['content']['edge_heading'] ?? '')) !== ''): ?>
                    <h6><?php echo htmlspecialchars($electronicAdminSection['content']['edge_heading']); ?></h6>
                <?php endif; ?>

                <?php if (!empty($edgeSteps)): ?>
                    <ol>
                        <?php foreach ($edgeSteps as $step): ?>
                            <li><?php echo htmlspecialchars($step); ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <?php if (trim((string)($electronicAdminSection['content']['chrome_heading'] ?? '')) !== ''): ?>
                    <h6><?php echo htmlspecialchars($electronicAdminSection['content']['chrome_heading']); ?></h6>
                <?php endif; ?>

                <?php if (!empty($chromeSteps)): ?>
                    <ol>
                        <?php foreach ($chromeSteps as $step): ?>
                            <li><?php echo htmlspecialchars($step); ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <?php if (trim((string)($electronicAdminSection['content']['link_url'] ?? '')) !== ''): ?>
                    <a class="parents-info-system-link" href="<?php echo htmlspecialchars($electronicAdminSection['content']['link_url']); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo htmlspecialchars($electronicAdminSection['content']['link_label'] ?? 'Μετάβαση στο Σύστημα Ηλεκτρονικής Διοίκησης'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
