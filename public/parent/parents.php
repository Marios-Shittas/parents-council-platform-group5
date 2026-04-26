<?php
// Arxeio: public\parent\parents.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
// Goneas route gia tin selida goneon/sindesmou.
// Fortonei auth helpers gia na meinei i goneas ekdosi mesa sto sindedemenos perivallon.
require_once __DIR__ . '/../../app/includes/auth.php';

// No-cache headers gia na min meinei private goneas page sto cache meta logout.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

auth_require_role('parent');

// To goneas context allazei navigation kai links mesa sto shared goneis view.
$siteContext = 'parent';
require __DIR__ . '/../../app/views/pages/parents.php';
