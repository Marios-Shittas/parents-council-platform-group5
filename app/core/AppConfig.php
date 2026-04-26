<?php
// Arxeio: app\core\AppConfig.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.

class AppConfig
{
// Diavazei timi request/server me proxy-awareness, kanei trim kai epistrefei mono to proto entry
// otan yparxoun comma-separated headers (syxno se forwarded headers).
    public static function detectRequestValue(string $primaryKey, string $fallbackKey = ''): string
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
// Ypologizei canonical base URL efarmogis me proteraiotita: APP_BASE_URL env prwta,
// alliws scheme/host apo proxy i server vars kai telika infer path apo to script location.
    public static function detectBaseUrl(): string
    {
        $configuredBaseUrl = trim((string) getenv('APP_BASE_URL'));
        if ($configuredBaseUrl !== '') {
            return rtrim($configuredBaseUrl, '/');
        }

        $scheme = self::detectRequestValue('HTTP_X_FORWARDED_PROTO');
        if ($scheme === '') {
            $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
            $scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
        }

        $host = self::detectRequestValue('HTTP_X_FORWARDED_HOST', 'HTTP_HOST');
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
