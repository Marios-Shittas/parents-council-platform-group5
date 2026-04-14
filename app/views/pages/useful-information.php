<?php
require_once __DIR__ . '/../../services/UsefulInformationService.php';
require_once __DIR__ . '/../../includes/site_context.php';

$usefulInformationService = new UsefulInformationService();
$sections = $usefulInformationService->getAllSections();

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
    <link rel="stylesheet" href="<?php echo site_asset_url('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/public-page-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/useful-information.css'); ?>">
    <title>Χρήσιμοι Σύνδεσμοι & Πληροφορίες - Γυμνάσιο Αγίου Αθανασίου</title>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php
$pageHeaderTitle = $pageHeader['title'];
$pageHeaderSubtitle = $pageHeader['subtitle'];
$pageHeaderIcon = 'fas fa-info-circle';
$pageHeaderEyebrow = $pageHeader['content']['eyebrow'] ?? '';
include __DIR__ . '/../../includes/public_page_header.php';
?>

<main class="useful-info-page pb-5">
    <div class="container">
        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-link"></i></span>
                <div>
                    <h2><?php echo htmlspecialchars($quickLinks['title']); ?></h2>
                    <p><?php echo htmlspecialchars($quickLinks['subtitle']); ?></p>
                </div>
            </div>

            <div class="row">
                <?php foreach (($quickLinks['content']['items'] ?? []) as $item): ?>
                    <?php if (trim((string) ($item['title'] ?? '')) === '' && trim((string) ($item['url'] ?? '')) === '') { continue; } ?>
                    <div class="col-lg-4 mb-4">
                        <a class="info-link-card" href="<?php echo htmlspecialchars($item['url'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">
                            <span class="info-link-card__icon"><i class="<?php echo htmlspecialchars($item['icon'] ?? 'fas fa-link'); ?>"></i></span>
                            <h3><?php echo htmlspecialchars($item['title'] ?? ''); ?></h3>
                            <p><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-calendar-check"></i></span>
                <div>
                    <h2><?php echo htmlspecialchars($schoolYear['title']); ?></h2>
                    <p><?php echo htmlspecialchars($schoolYear['subtitle']); ?></p>
                </div>
            </div>

            <div class="row">
                <?php foreach (($schoolYear['content']['items'] ?? []) as $item): ?>
                    <div class="col-md-4 mb-4">
                        <div class="highlight-card">
                            <span class="highlight-card__label"><?php echo htmlspecialchars($item['label'] ?? ''); ?></span>
                            <strong><?php echo htmlspecialchars($item['date'] ?? ''); ?></strong>
                            <p><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="note-card">
                <i class="fas fa-info-circle"></i>
                <p><?php echo htmlspecialchars($schoolYear['content']['note'] ?? ''); ?></p>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-calendar-alt"></i></span>
                <div>
                    <h2><?php echo htmlspecialchars($holidays['title']); ?></h2>
                    <p><?php echo htmlspecialchars($holidays['subtitle']); ?></p>
                </div>
            </div>

            <div class="table-responsive holiday-table-wrap">
                <table class="table holiday-table mb-0">
                    <thead>
                        <tr>
                            <th>Ημερομηνία</th>
                            <th>Αργία</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($holidays['content']['rows'] ?? []) as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['date'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-user-shield"></i></span>
                <div>
                    <h2><?php echo htmlspecialchars($safety['title']); ?></h2>
                    <p><?php echo htmlspecialchars($safety['subtitle']); ?></p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="content-card h-100">
                        <ul class="feature-list">
                            <?php foreach (($safety['content']['bullets'] ?? []) as $bullet): ?>
                                <li><?php echo htmlspecialchars($bullet); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-5 mb-4">
                    <div class="content-card h-100">
                        <h3>Χρήσιμες Λήψεις</h3>
                        <div class="stack-links">
                            <?php foreach (($safety['content']['downloads'] ?? []) as $download): ?>
                                <?php if (trim((string) ($download['title'] ?? '')) === '' && trim((string) ($download['url'] ?? '')) === '') { continue; } ?>
                                <a class="action-link" href="<?php echo htmlspecialchars($download['url'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="<?php echo htmlspecialchars($download['icon'] ?? 'fas fa-download'); ?>"></i>
                                    <?php echo htmlspecialchars($download['title'] ?? ''); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section__heading">
                <span class="info-section__badge"><i class="fas fa-tshirt"></i></span>
                <div>
                    <h2><?php echo htmlspecialchars($uniform['title']); ?></h2>
                    <p><?php echo htmlspecialchars($uniform['subtitle']); ?></p>
                </div>
            </div>

            <div class="row">
                <?php foreach (($uniform['content']['cards'] ?? []) as $card): ?>
                    <div class="col-lg-4 mb-4">
                        <div class="content-card h-100">
                            <h3><?php echo htmlspecialchars($card['title'] ?? ''); ?></h3>
                            <ul class="feature-list compact">
                                <?php foreach (($card['items'] ?? []) as $item): ?>
                                    <li><?php echo htmlspecialchars($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="note-card">
                <i class="fas fa-external-link-alt"></i>
                <p><?php echo htmlspecialchars($uniform['content']['note'] ?? ''); ?></p>
                <?php if (!empty($uniform['content']['button_url'])): ?>
                    <a class="btn btn-primary btn-sm ml-sm-3 mt-3 mt-sm-0" href="<?php echo htmlspecialchars($uniform['content']['button_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($uniform['content']['button_text'] ?? 'Προβολή'); ?></a>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
