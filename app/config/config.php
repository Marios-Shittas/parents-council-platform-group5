<?php
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Athens');

if (!function_exists('app_detect_request_value')) {
    function app_detect_request_value(string $primaryKey, string $fallbackKey = ''): string
    {
        $value = trim((string) ($_SERVER[$primaryKey] ?? ''));
        if ($value !== '') {
            return explode(',', $value)[0];
        }

        if ($fallbackKey !== '') {
            $fallbackValue = trim((string) ($_SERVER[$fallbackKey] ?? ''));
            if ($fallbackValue !== '') {
                return explode(',', $fallbackValue)[0];
            }
        }

        return '';
    }
}

if (!function_exists('app_detect_base_url')) {
    function app_detect_base_url(): string
    {
        $configuredBaseUrl = trim((string) getenv('APP_BASE_URL'));
        if ($configuredBaseUrl !== '') {
            return rtrim($configuredBaseUrl, '/');
        }

        $scheme = app_detect_request_value('HTTP_X_FORWARDED_PROTO');
        if ($scheme === '') {
            $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
            $scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
        }

        $host = app_detect_request_value('HTTP_X_FORWARDED_HOST', 'HTTP_HOST');
        if ($host === '') {
            $host = trim((string) ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = '/parents-council-platform-group5';

        if (preg_match('#^(.*?)/public(?:/|$)#', $scriptName, $matches)) {
            $basePath = $matches[1] !== '' ? $matches[1] : '';
        } elseif (preg_match('#^(.*?)/app(?:/|$)#', $scriptName, $matches)) {
            $basePath = $matches[1] !== '' ? $matches[1] : '';
        }

        return rtrim($scheme . '://' . $host . $basePath, '/');
    }
}

define('DB_HOST', 'localhost');        // Server
define('DB_NAME', 'parents_council_dimotiko');  // Όνομα βάσης - δημοτικό
define('DB_USER', 'root');             // XAMPP default user
define('DB_PASS', '');                  // XAMPP default password
define('DB_CHARSET', 'utf8mb4');       // Κωδικοποίηση
define('APP_BASE_URL', app_detect_base_url());

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_USER', getenv('SMTP_USER') ?: 'sg.ag.athanasiou@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'hxkh dayj kdvi myvn');
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
