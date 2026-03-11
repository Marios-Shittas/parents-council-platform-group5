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
        <link rel="stylesheet" href="assets/css/login.css">
    </head>
    
    <body class="body">
        <div class="page-content">
            <h1 id="login-title">Login</h1>
            <div class="login-container">
                <p id="email-label">Please enter your email:</p>
                <input type="email" id="email-input" placeholder="Email">
                <p id="password-label">Please enter your password:</p>
                <div class="password-wrapper">
                    <input type="password" id="password-input" placeholder="Password">
                    <span id="password-toggle-root"></span>
                </div>
                <button id="login-button">Login</button>
                <a href="index.php">
                    <button id="back-button">Back</button>
                </a>
                <p class="forgot-password">
                    <a href="forgot-password.php" id="forgot-password-link">Forgot password?</a>
                </p>
            </div>
            <div class="divider">
                <div class="line"></div>
                <span class="or-text">or</span>
                <div class="line"></div>
            </div>
            <div class="register-container">
                <p id="register-prompt">Don't have an account? 
                    <a href="register.php" id="register-link">Register here</a>
                </p>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
        <script type="text/babel" src="assets/js/login.jsx"></script>

    </body>
</html>