<?php
define('DB_HOST', 'localhost');        // Server
define('DB_NAME', 'parents_council');  // Όνομα βάσης
define('DB_USER', 'root');             // XAMPP default user
define('DB_PASS', '');                  // XAMPP default password
define('DB_CHARSET', 'utf8mb4');       // Κωδικοποίηση
define('APP_BASE_URL', getenv('APP_BASE_URL') ?: 'http://localhost/parents-council-platform-group5');

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_USER', getenv('SMTP_USER') ?: 'nigkaleta@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'dyjs vehc oyiy dvmv');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: SMTP_USER);
define('APPROVAL_LINK_EXPIRY_HOURS', (int) (getenv('APPROVAL_LINK_EXPIRY_HOURS') ?: 168));
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Parents Council');

define('JCC_GATEWAY_URL', getenv('JCC_GATEWAY_URL') ?: 'https://gateway-test.jcc.com.cy/payment');
define('JCC_API_LOGIN', getenv('JCC_API_LOGIN') ?: 'parents-council-api');
define('JCC_API_PASSWORD', getenv('JCC_API_PASSWORD') ?: 'ogmiu-L3');

$jccBaseUrl = rtrim(JCC_GATEWAY_URL, '/');
define('JCC_REGISTER_URL', getenv('JCC_REGISTER_URL') ?: ($jccBaseUrl . '/rest/register.do'));
define('JCC_ORDER_STATUS_URL', getenv('JCC_ORDER_STATUS_URL') ?: ($jccBaseUrl . '/rest/getOrderStatusExtended.do'));
define('JCC_RETURN_URL', getenv('JCC_RETURN_URL') ?: (APP_BASE_URL . '/public/subscription-result.php'));
define('JCC_FAIL_URL', getenv('JCC_FAIL_URL') ?: (APP_BASE_URL . '/public/subscription-result.php?status=fail'));