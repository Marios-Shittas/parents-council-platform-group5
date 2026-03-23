<?php
declare(strict_types=1);

class RegisterPage
{
    public function render(): void
    {
        ?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/user_css/public-page-header.css">
    <link rel="stylesheet" href="assets/css/register.css">

    <title>Εγγραφή</title>
</head>
<body>

    <!-- Header -->
    <?php include '../app/includes/header.php'; ?>

    <!-- React will render here -->
    <div class="container">
        <div id="root"></div>
    </div>

    <!-- React / ReactDOM -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>

    <!-- Babel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>

    <!-- Register JSX -->
    <script type="text/babel" src="assets/js/register.jsx"></script>

    <!-- Footer -->
    <?php include '../app/includes/footer.php'; ?>
</body>
</html>
        <?php
    }
}

$page = new RegisterPage();
$page->render();