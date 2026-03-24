<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id'])) {
    header('Location: /parents-council-platform-group5/public/login.php');
    exit;
}

$siteContext = 'parent';
require __DIR__ . '/../../app/views/pages/announcements.php';
