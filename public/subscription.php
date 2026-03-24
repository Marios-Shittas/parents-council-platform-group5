<?php
require_once __DIR__ . '/../app/config/db.php';

$token = trim((string)($_GET['token'] ?? ''));
$isValidToken = false;
$tokenMessage = 'Ο σύνδεσμος δεν είναι έγκυρος ή έχει λήξει.';

if ($token !== '') {
    $stmt = $conn->prepare(
        "SELECT user_id
         FROM Users
         WHERE token = ?
           AND role = 'parent'
           AND account_status = 'waiting_payment'
           AND token_expiry IS NOT NULL
           AND token_expiry >= NOW()
         LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $isValidToken = $result && $result->num_rows === 1;
        $stmt->close();
    }
}

$tokenJson = json_encode($token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

    <title>Συνδρωμή</title>
</head>
<body>
    <?php if (!$isValidToken): ?>
        <div class="container mt-5">
            <div class="alert alert-danger text-center" role="alert">
                <?php echo htmlspecialchars($tokenMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    <?php else: ?>
        <!-- React will render here -->
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

        <script>
            window.APPROVAL_TOKEN = <?php echo $tokenJson ?: '""'; ?>;
        </script>

        <!-- Your React JSX -->
        <script type="text/babel" src="assets/js/subscription.jsx"></script>
    <?php endif; ?>