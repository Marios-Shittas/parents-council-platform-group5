<?php
require_once __DIR__ . '/../../app/includes/auth.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

AuthHelper::requireRole('parent');

$siteContext = 'parent';
require __DIR__ . '/../../app/views/pages/epikoinonia.php';
