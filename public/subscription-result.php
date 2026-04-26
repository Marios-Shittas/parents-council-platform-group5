<?php
// Arxeio: public\subscription-result.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
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

    <script src="assets/js/app-page-config.js" data-config="<?php echo htmlspecialchars(json_encode(['SUBSCRIPTION_RESULT_PARAMS' => $paramsPayload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <script type="text/babel" src="assets/js/subscription-result.jsx"></script>
</body>
</html>
