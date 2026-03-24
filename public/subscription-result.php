<?php
declare(strict_types=1);

$orderId = trim((string) ($_GET['orderId'] ?? $_GET['mdOrder'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));
$membershipPaymentId = (int) ($_GET['mp'] ?? 0);
$insurancePaymentId = (int) ($_GET['ip'] ?? 0);
$statusHint = trim((string) ($_GET['status'] ?? ''));

$paramsPayload = [
    'orderId' => $orderId,
    'token' => $token,
    'mp' => (string) $membershipPaymentId,
    'ip' => (string) $insurancePaymentId,
    'status' => $statusHint,
];

$paramsJson = json_encode($paramsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Αποτέλεσμα Πληρωμής</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/subscription-result.css">
</head>
<body>
    <div id="subscription-result-root"></div>

    <script>
        window.SUBSCRIPTION_RESULT_PARAMS = <?php echo $paramsJson ?: '{}'; ?>;
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <script type="text/babel" src="assets/js/subscription-result.jsx"></script>
</body>
</html>
