<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/controllers/SubscriptionPageController.php';

$subscriptionController = new SubscriptionPageController($conn);
$subscriptionData = $subscriptionController->viewData();
$isValidToken = $subscriptionData['is_valid_token'];
$tokenMessage = $subscriptionData['token_message'];
$subscriptionConfig = ['APPROVAL_TOKEN' => $subscriptionData['token']];
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
    <link rel="stylesheet" href="assets/css/subscription.css">

    <title>Συνδρομή</title>
</head>
<body>
    <?php if (!$isValidToken): ?>
        <div class="container mt-5">
            <div class="alert alert-danger text-center" role="alert">
                <?php echo htmlspecialchars($tokenMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="container">
            <div id="root"></div>
        </div>
    <?php endif; ?>

    <?php if ($isValidToken): ?>
        <!-- React / ReactDOM -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>

        <!-- Babel -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>

        <script src="assets/js/app-page-config.js" data-config="<?php echo htmlspecialchars(json_encode($subscriptionConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"></script>

        <script type="text/babel" src="assets/js/subscription.jsx"></script>
    <?php endif; ?>
</body>
</html>
<?php
$conn->close();
