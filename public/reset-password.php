<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0");
header("Pragma: no-cache");
header("Expires: 0");
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
        <link rel="stylesheet" href="assets/css/reset-password.css">
    </head>
    
    <body class="body">
        <div class="page-content">
            <a href="login.php" id="back-to-login">
                <button id="back-button"><i class="fas fa-arrow-left"></i></button>
            </a>
            <h1 id="reset-password-title">Reset Password</h1>
            <span id="error-message"></span>
            <div class="password-container">
                <p id="password-label">Enter new password:</p>
                <input type="password" id="new-password" placeholder="New Password">
                <p id="confirm-password-label">Confirm new password:</p>
                <input type="password" id="confirm-password" placeholder="Confirm Password">
                <button id="confirm-button">Reset Password</button>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
        <script type="text/babel" src="assets/js/reset-password.jsx"></script>
    </body>
</html>