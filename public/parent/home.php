<?php
// Arxeio: public\parent\home.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
// Goneas arxiki selida gia sindedemenous goneis.
// Fortonei auth helpers prin apo otidipote private content.
require_once __DIR__ . '/../../app/includes/auth.php';

// No-cache headers gia na min faneronetai goneas dashboard apo browser cache meta logout.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

auth_require_role('parent');

// To goneas context dinei sto shared home view goneas navigation kai katallila buttons.
$siteContext = 'parent';
require __DIR__ . '/../../app/views/pages/home.php';
