<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

if ($path === '/') {
    require __DIR__ . '/public/home.php';
    return true;
}

if (strpos($path, '/public/') !== 0 && is_file(__DIR__ . '/public' . $path)) {
    require __DIR__ . '/public' . $path;
    return true;
}

return false;
