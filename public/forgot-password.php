<!DOCTYPE html>
<html lang="el">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    
        <!-- Google fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap" rel="stylesheet">
    
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

        <!-- Costom CSS -->
        <link rel="stylesheet" href="assets/css/main.css">
        <link rel="stylesheet" href="assets/css/forgot-password.css">
    </head>
    
    <body class="body">
        <div class="page-content">
            <h1 id="reset-password-title">Reset Password</h1>
            <div class="reset-password-container">
                <p id="email-text">No worries! Enter your email address below, and we'll send you a code to reset your password.</p>
                <p id="email-label">Please enter your email:</p>
                <input type="email" id="email-input" placeholder="Email">
                <button id="send-email-button">Send Email</button>
                <a href="login.php" id="back-to-login">
                    <button id="cancel-button">Cancel</button>
                </a>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
        <script type="text/babel" src="assets/js/home.jsx"></script>

    </body>
</html>