<?php
// Arxeio: public\admin\index.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: einai gia admin, opote kratame elegxous rolou kai feedback kathara gia ton diaxeiristi.
// Admin landing page: elegxei session kai deixnei tin arxiki selida tou panel.
// Den exei diko tou UI, leitourgei san syntomeusi pros to admin dashboard.
require_once __DIR__ . '/../../app/includes/site_context.php';

header('Location: ' . site_public_url('admin/home.php'));
exit;
