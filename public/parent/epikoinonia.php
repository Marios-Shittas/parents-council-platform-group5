<?php
// Arxeio: public\parent\epikoinonia.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
// Goneas route gia epikoinonia, me goneas context.
// Fortonei auth helpers gia na stelnei minimata mono syndedemenos goneas xristis.
require_once __DIR__ . '/../../app/includes/auth.php';

// No-cache headers oste na min meinei private selida sto browser history meta logout.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

auth_require_role('parent');

// To goneas context deixnei tin epikoinonia mesa sto goneas perivallon.
$siteContext = 'parent';
require __DIR__ . '/../../app/views/pages/epikoinonia.php';
