<?php
// Arxeio: public\parent\announcements.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
// Goneas route gia anakoinoseis, me goneas context.
// Fortonei auth helpers giati i goneas ekdosi prepei na einai mono gia syndedemenous goneis.
require_once __DIR__ . '/../../app/includes/auth.php';

// No-cache headers: den afinnoume browser back button na deixnei private goneas content meta logout.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

auth_require_role('parent');

// To goneas context allazei navigation/links se sxesi me tin public ekdosi tou idiou view.
$siteContext = 'parent';
require __DIR__ . '/../../app/views/pages/announcements.php';
