<?php
// Arxeio: public\parent\applications.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora aitiseis/templates kai uploads, ara ta paths kai ta validation einai simantika.
// Goneas route gia aitiseis, me goneas context.
// Fortonei auth helpers giati oi aitiseis tou gonea einai private leitourgia.
require_once __DIR__ . '/../../app/includes/auth.php';

// No-cache headers: prostatevoun ta private dedomena apo browser cache meta logout.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

auth_require_role('parent');

// To goneas context kanei to shared view na doulepsei me goneas navbar kai goneas flows.
$siteContext = 'parent';
require __DIR__ . '/../../app/views/pages/applications.php';
?>
