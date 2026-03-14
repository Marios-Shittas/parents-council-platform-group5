<?php
// Αρχικοποίηση μεταβλητών
require_once __DIR__ . '/../app/services/EpikoinoniaService.php';

$form_submitted = false;
$success_message = false;
$error_message = '';
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => ''
];

$epikoinoniaService = new EpikoinoniaService();

// Επεξεργασία φόρμας
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Συλλογή δεδομένων από φόρμα
    $form_data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'subject' => trim($_POST['subject'] ?? ''),
        'message' => trim($_POST['message'] ?? '')
    ];

    // Βασική επικύρωση δεδομένων
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
        // Αποθήκευση μηνύματος στη βάση δεδομένων
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
            // Εξάγνωση της φόρμας μετά τη επιτυχή υποβολή
            $form_data = [
                'name' => '',
                'email' => '',
                'phone' => '',
                'subject' => '',
                'message' => ''
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

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/user_css/public-page-header.css">
    <link rel="stylesheet" href="assets/css/user_css/epikoinonia.css">
</head>
<body>
    <!-- Header -->
    <?php include '../app/includes/header.php'; ?>

    <?php
    $pageHeaderTitle = 'Επικοινωνία';
    $pageHeaderSubtitle = 'Επικοινωνήστε μαζί μας για οποιαδήποτε ερώτηση ή πληροφορία.';
    $pageHeaderIcon = 'fas fa-envelope';
    $pageHeaderEyebrow = 'Υποστήριξη Και Στοιχεία';
    include __DIR__ . '/../app/includes/public_page_header.php';
    ?>

    <!-- Main Content -->
    <main class="main-content epikoinonia-page">
        <div class="container">
            <!-- Contact Information Section -->
            <div class="contact-section">
                <h2 class="section-title">Πληροφορίες Επικοινωνίας</h2>
                <div class="row">
                    <div class="col-md-6 col-lg-3">
                        <div class="contact-card">
                            <div class="contact-card-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h5>Διεύθυνση</h5>
                            <p>Χρίστου Παπαδούρη 50<br>4105 Άγιος Αθανάσιος, Λεμεσός</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="contact-card contact-card-phone">
                            <div class="contact-card-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h5>Τηλέφωνο</h5>
                            <p>Τηλέφωνα: 25694750, 25694752<br>Τηλεομοιότυπο: 25694755</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="contact-card contact-card-email">
                            <div class="contact-card-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h5>Email</h5>
                            <p><a href="mailto:info@syllogos.gr" style="color: #2f6ea0; text-decoration: none;">gym-ag-athanasios-lem@schools.ac.cy</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="contact-card">
                            <div class="contact-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h5>Ώρες Λειτουργίας</h5>
                            <p>Δευ-Παρ – 7.30-13.35</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="contact-section">
                <h2 class="section-title">Βρείτε μας στο Χάρτη</h2>
                <div class="map-container">
                    <!-- Google Maps Iframe with marker - Χρίστου Παπαδούρη 50, Λεμεσός -->
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3279.4575341666614!2d33.0611131!3d34.7188599!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14e734bc13013dc9%3A0x9c01ea2ef75a5b4d!2zzpPPhc68zr3OrM-DzrnOvyDOkc6zzq_Ov8-FIM6RzrjOsc69zrHPg86vzr_PhQ!5e0!3m2!1sel!2s!4v1773496500123!5m2!1sel!2s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

            <!-- Contact Form Section -->
            <div class="contact-section">
                <h2 class="section-title">Στείλτε μας Μήνυμα</h2>
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="bg-white p-4 rounded-3 shadow-sm">
                            <?php if ($success_message): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Επιτυχία!</strong> Το μήνυμά σας λήφθηκε. Θα σας απαντήσουμε το συντομότερο δυνατό.
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($error_message) && !$success_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Σφάλμα!</strong><br>
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name" class="form-label">Όνομα *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($form_data['name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone" class="form-label">Τηλέφωνο *</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($form_data['phone']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="subject" class="form-label">Θέμα *</label>
                                            <input type="text" class="form-control" id="subject" name="subject" 
                                                   value="<?php echo htmlspecialchars($form_data['subject']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="message" class="form-label">Μήνυμα *</label>
                                    <textarea class="form-control" id="message" name="message" rows="6" 
                                              required><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-paper-plane me-2"></i>Αποστολή Μηνύματος
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media Section -->
            <div class="contact-section">
                <h2 class="section-title">Βρείτε μας στα social networks</h2>
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="bg-white p-5 rounded-3 shadow-sm text-center">
                            <div class="social-links justify-content-center">
                                <a href="https://www.facebook.com/profile.php?id=100085835704152" target="_blank" rel="noopener noreferrer" class="btn-social" title="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://x.com/cymoec" target="_blank" rel="noopener noreferrer" class="btn-social" title="X">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://www.youtube.com/cymoec" target="_blank" rel="noopener noreferrer" class="btn-social" title="YouTube">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Spacing for footer -->
            <div class="contact-footer-spacing"></div>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../app/includes/footer.php'; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- React & Babel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>

    <!-- Custom React Component -->
    <script type="text/babel" src="assets/js/epikoinonia.jsx"></script>
</body>
</html>
