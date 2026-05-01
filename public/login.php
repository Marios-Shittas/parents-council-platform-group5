<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0");
header("Pragma: no-cache");
header("Expires: 0");

$successMessage = '';
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $successMessage = 'Η επαναφορά κωδικού ολοκληρώθηκε επιτυχώς! Παρακαλώ συνδεθείτε με τον νέο σας κωδικό.';
}

require_once __DIR__ . '/../app/includes/site_context.php';
?>

<!DOCTYPE html>
<html lang="el">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    
        <!-- Google fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <!-- Costom CSS -->
        <link rel="stylesheet" href="assets/css/main.css">
        <link rel="stylesheet" href="assets/css/login.css">
        <script src="assets/js/site-favicon.js" data-favicon-href="assets/img/logo-icon.png" data-favicon-shape="circle" defer></script>
    </head>
    
    <body class="body">
    <script src="assets/js/login-page.js" defer></script>
        <div class="page-content">
            <a href="<?php echo site_public_url('home.php'); ?>">
                <button id="back-button"><i class="fas fa-arrow-left"></i></button>
            </a>
            <h1 id="login-title">Σύνδεση</h1>
            <span id="error-message"></span>
            <span id="success-message"><?php echo htmlspecialchars($successMessage); ?></span>
            <div class="login-container">
                <p id="email-label">Παρακαλώ εισάγετε το email σας:</p>
                <input type="email" id="email-input" placeholder="Email">
                <p id="password-label">Παρακαλώ εισάγετε τον κωδικό σας:</p>
                <div class="password-wrapper">
                    <input type="password" id="password-input" placeholder="Κωδικός πρόσβασης">
                    <span id="password-toggle-root"></span>
                </div>
                <button id="login-button">Σύνδεση</button>
                <p class="forgot-password">
                    <a href="forgot-password.php" id="forgot-password-link">Ξεχάσατε τον κωδικό;</a>
                </p>
            </div>
            <div class="divider">
                <div class="line"></div>
                <span class="or-text">or</span>
                <div class="line"></div>
            </div>
            <div class="register-container">
                <p id="register-prompt">Δεν έχετε λογαριασμό; 
                    <a href="register.php" id="register-link">Εγγραφείτε εδώ</a>
                </p>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
        <script src="assets/js/app-page-config.js" data-config="<?php echo htmlspecialchars(json_encode(['APP_PROJECT_URL' => site_project_url(), 'APP_PUBLIC_URL' => site_base_url()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"></script>
        <script type="text/babel" src="assets/js/login.jsx"></script>

    </body>
</html>
