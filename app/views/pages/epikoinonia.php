<?php
require_once __DIR__ . '/../../services/EpikoinoniaService.php';
require_once __DIR__ . '/../../services/EpikoinoniaPageService.php';
require_once __DIR__ . '/../../includes/site_context.php';

$form_submitted = false;
$success_message = false;
$error_message = '';
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => '',
];

$epikoinoniaService = new EpikoinoniaService();
$epikoinoniaPageService = new EpikoinoniaPageService();
$sections = $epikoinoniaPageService->getAllSections();

$pageHeader = $sections['page_header'];
$contactInfo = $sections['contact_info'];
$mapSection = $sections['map_section'];
$formSection = $sections['form_section'];
$socialSection = $sections['social_section'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'subject' => trim($_POST['subject'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
    ];

    $errors = [];

    if (empty($form_data['name'])) {
        $errors[] = 'Το όνομα είναι υποχρεωτικό.';
    }

    if (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Παρακαλώ εισάγετε ένα έγκυρο email.';
    }

    if (empty($form_data['phone'])) {
        $errors[] = 'Το τηλέφωνο είναι υποχρεωτικό.';
    }

    if (empty($form_data['subject'])) {
        $errors[] = 'Το θέμα είναι υποχρεωτικό.';
    }

    if (empty($form_data['message'])) {
        $errors[] = 'Το μήνυμα είναι υποχρεωτικό.';
    }

    if (empty($errors)) {
        $messageId = $epikoinoniaService->createMessage(
            $form_data['name'],
            $form_data['email'],
            $form_data['phone'],
            $form_data['subject'],
            $form_data['message']
        );

        if ($messageId) {
            $form_submitted = true;
            $success_message = true;
            $form_data = [
                'name' => '',
                'email' => '',
                'phone' => '',
                'subject' => '',
                'message' => '',
            ];
        } else {
            $error_message = 'Σφάλμα κατά την αποθήκευση του μηνύματος. Παρακαλώ προσπαθήστε ξανά.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Επικοινωνία - Σύλλογος Γονέων</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/public-page-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_asset_url('css/user_css/epikoinonia.css'); ?>">
</head>
<body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <?php
    $pageHeaderTitle = $pageHeader['title'];
    $pageHeaderSubtitle = $pageHeader['subtitle'];
    $pageHeaderIcon = trim((string)($pageHeader['content']['icon'] ?? '')) ?: 'fas fa-envelope';
    $pageHeaderEyebrow = $pageHeader['content']['eyebrow'] ?? '';
    include __DIR__ . '/../../includes/public_page_header.php';
    ?>

    <main class="main-content epikoinonia-page">
        <div class="container">
            <div class="contact-section">
                <h2 class="section-title"><?php echo htmlspecialchars($contactInfo['title']); ?></h2>
                <?php if (!empty($contactInfo['subtitle'])): ?>
                    <p class="section-subtitle"><?php echo htmlspecialchars($contactInfo['subtitle']); ?></p>
                <?php endif; ?>
                <div class="row contact-cards-grid">
                    <?php foreach (($contactInfo['content']['cards'] ?? []) as $card): ?>
                        <?php
                        $iconClass = trim((string)($card['icon'] ?? '')) ?: 'fas fa-info-circle';
                        $cardClass = 'contact-card';
                        if (strpos($iconClass, 'fa-phone') !== false) {
                            $cardClass .= ' contact-card-phone';
                        }
                        if (strpos($iconClass, 'fa-envelope') !== false) {
                            $cardClass .= ' contact-card-email';
                        }

                        $cardText = trim((string)($card['text'] ?? ''));
                        $linkLabel = trim((string)($card['link_label'] ?? ''));
                        $linkUrl = trim((string)($card['link_url'] ?? ''));
                        ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="<?php echo htmlspecialchars($cardClass); ?>">
                                <div class="contact-card-icon">
                                    <i class="<?php echo htmlspecialchars($iconClass); ?>"></i>
                                </div>
                                <h5><?php echo htmlspecialchars($card['title'] ?? ''); ?></h5>
                                <?php if ($cardText !== ''): ?>
                                    <p><?php echo nl2br(htmlspecialchars($cardText)); ?></p>
                                <?php endif; ?>
                                <?php if ($linkUrl !== '' && $linkLabel !== ''): ?>
                                    <p class="contact-card-link-wrap">
                                        <a href="<?php echo htmlspecialchars($linkUrl); ?>" class="contact-card-link">
                                            <?php echo htmlspecialchars($linkLabel); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="contact-section">
                <h2 class="section-title"><?php echo htmlspecialchars($mapSection['title']); ?></h2>
                <?php if (!empty($mapSection['subtitle'])): ?>
                    <p class="section-subtitle"><?php echo htmlspecialchars($mapSection['subtitle']); ?></p>
                <?php endif; ?>
                <?php
                $mapUrl = trim((string)($mapSection['content']['embed_url'] ?? ''));
                $isEmbedMap = strpos($mapUrl, '/maps/embed') !== false || strpos($mapUrl, 'output=embed') !== false;
                ?>
                <?php if ($mapUrl !== '' && $isEmbedMap): ?>
                    <div class="map-container">
                        <iframe src="<?php echo htmlspecialchars($mapUrl); ?>" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                <?php elseif ($mapUrl !== ''): ?>
                    <p class="text-center">
                        <a href="<?php echo htmlspecialchars($mapUrl); ?>" class="contact-card-link" target="_blank" rel="noopener noreferrer">Άνοιγμα στο Google Maps</a>
                    </p>
                <?php endif; ?>
            </div>

            <div class="contact-section">
                <h2 class="section-title"><?php echo htmlspecialchars($formSection['title']); ?></h2>
                <?php if (!empty($formSection['subtitle'])): ?>
                    <p class="section-subtitle"><?php echo htmlspecialchars($formSection['subtitle']); ?></p>
                <?php endif; ?>
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="bg-white p-4 rounded-3 shadow-sm">
                            <?php if (!empty($formSection['content']['description'])): ?>
                                <p class="form-section-description"><?php echo nl2br(htmlspecialchars($formSection['content']['description'])); ?></p>
                            <?php endif; ?>

                            <?php if ($success_message): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <strong>Επιτυχία!</strong> <?php echo htmlspecialchars(trim((string)($formSection['content']['success_message'] ?? '')) ?: 'Το μήνυμά σας λήφθηκε. Θα σας απαντήσουμε το συντομότερο δυνατό.'); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($error_message) && !$success_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <strong>Σφάλμα!</strong><br>
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name" class="form-label">Όνομα *</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($form_data['name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone" class="form-label">Τηλέφωνο *</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($form_data['phone']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="subject" class="form-label">Θέμα *</label>
                                            <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($form_data['subject']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="message" class="form-label">Μήνυμα *</label>
                                    <textarea class="form-control" id="message" name="message" rows="6" required><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-paper-plane mr-2"></i><?php echo htmlspecialchars(trim((string)($formSection['content']['button_text'] ?? '')) ?: 'Αποστολή Μηνύματος'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-section">
                <h2 class="section-title"><?php echo htmlspecialchars($socialSection['title']); ?></h2>
                <?php if (!empty($socialSection['subtitle'])): ?>
                    <p class="section-subtitle"><?php echo htmlspecialchars($socialSection['subtitle']); ?></p>
                <?php endif; ?>
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="bg-white p-5 rounded-3 shadow-sm text-center">
                            <div class="social-links justify-content-center">
                                <?php foreach (($socialSection['content']['items'] ?? []) as $item): ?>
                                    <?php
                                    $socialUrl = trim((string)($item['url'] ?? ''));
                                    if ($socialUrl === '') {
                                        continue;
                                    }
                                    ?>
                                    <a href="<?php echo htmlspecialchars($socialUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn-social" title="<?php echo htmlspecialchars($item['title'] ?? 'Social'); ?>">
                                        <i class="<?php echo htmlspecialchars(trim((string)($item['icon'] ?? '')) ?: 'fas fa-share-alt'); ?>"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-footer-spacing"></div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <script type="text/babel" src="<?php echo site_asset_url('js/epikoinonia.jsx'); ?>"></script>
</body>
</html>
