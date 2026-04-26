<?php
// Arxeio: public\two-factor-authentication.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora authentication/security flow, ara den allazoume validation i redirects xoris elegxo.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0");
header("Pragma: no-cache");
header("Expires: 0");

// Elegxei an xristis has pending 2FA
if (!isset($_SESSION['pending_2fa']) || !isset($_SESSION['temp_email'])) {
    header('Location: login.php');
    exit;
}

$successMessage = '';
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $successMessage = 'Η επαναφορά κωδικού ολοκληρώθηκε επιτυχώς! Παρακαλώ συνδεθείτε με τον νέο σας κωδικό.';
}
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
        <link rel="stylesheet" href="assets/css/two-factor-authentication.css">
    </head>
    
    <body class="body">
    <script src="assets/js/app-page-config.js" data-config="<?php echo htmlspecialchars(json_encode(['initialTwoFactorSuccess' => $successMessage], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"></script>
        <div class="page-content">
            <a href="index.php">
                <button id="back-button"><i class="fas fa-arrow-left"></i></button>
            </a>
            <h1 id="two-factor-title">Έλεγχος Ταυτότητας Δύο Παραγόντων</h1>
            <div id="two-factor-root"></div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
        <script type="text/babel" src="assets/js/two-factor-authentication.jsx"></script>

    </body>
</html>
