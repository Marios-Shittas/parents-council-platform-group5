<?php
// Arxeio: public\reset-password.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora authentication/security flow, ara den allazoume validation i redirects xoris elegxo.
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/controllers/ResetPasswordPageController.php';

global $conn;
$resetPasswordController = new ResetPasswordPageController($conn);
$resetPasswordData = $resetPasswordController->viewData();
$isValidToken = $resetPasswordData['is_valid_token'];
$tokenMessage = $resetPasswordData['token_message'];
$resetConfig = [
    'RESET_EMAIL' => $resetPasswordData['email'],
    'RESET_TOKEN' => $resetPasswordData['token'],
    'RESET_PASSWORD_SERVICE_URL' => $resetPasswordData['service_url'],
];
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
        <link rel="stylesheet" href="assets/css/reset-password.css?v=<?php echo filemtime(__DIR__ . '/assets/css/reset-password.css'); ?>">
        
        <title>Επαναφορά Κωδικού</title>
    </head>
    
    <body class="body">
        <?php if (!$isValidToken): ?>
            <div class="container mt-5">
                <div class="alert alert-danger text-center" role="alert">
                    <?php echo htmlspecialchars($tokenMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="page-content">
                <a href="login.php" id="back-to-login">
                    <button id="back-button"><i class="fas fa-arrow-left"></i></button>
                </a>
                <h1 id="reset-password-title">Επαναφορά Κωδικού</h1>
                <span id="error-message"></span>
                <form id="reset-password-form" class="password-container">
                    <p id="password-label">Εισάγετε νέο κωδικό:</p>
                    <div class="password-input-wrapper">
                        <input type="password" id="new-password" placeholder="Νέος κωδικός" minlength="8" required>
                        <span id="new-password-toggle"></span>
                    </div>

                    <p id="confirm-password-label">Επιβεβαίωση νέου κωδικού:</p>
                    <div class="password-input-wrapper">
                        <input type="password" id="confirm-password" placeholder="Επιβεβαίωση κωδικού" minlength="8" required>
                        <span id="confirm-password-toggle"></span>
                    </div>
                    
                    <button id="confirm-button" type="submit">Επαναφορά Κωδικού</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($isValidToken): ?>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
            
            <script src="assets/js/app-page-config.js" data-config="<?php echo htmlspecialchars(json_encode($resetConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"></script>
            
            <script type="text/javascript" src="assets/js/reset-password.jsx"></script>
        <?php endif; ?>
    </body>
</html>
