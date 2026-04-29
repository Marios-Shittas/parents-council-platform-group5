<?php
// Arxeio: public\parent\eshop.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora agora/paraggelies, ara ta data prepei na menoun synced me cart/orders services.
// Goneas route gia eshop, me goneas context.
// Fortonei auth helpers giati mono goneas accounts mporoun na kanoun agores.
require_once __DIR__ . '/../../app/includes/auth.php';

// No-cache headers gia na min meinei kalathi/shop state sto cache meta logout.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

auth_require_role('parent');

// To goneas context epilegei tin goneas ekdosi tou shared eshop view.
$siteContext = 'parent';
require __DIR__ . '/../../app/views/pages/eshop.php';
