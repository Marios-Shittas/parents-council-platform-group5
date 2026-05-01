<?php
// Arxeio: public\eshop.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora agora/paraggelies, ara ta data prepei na menoun synced me cart/orders services.
// Public route gia to eshop.
// To public eshop den anoigei apefthias, giati oi agores theloun login goneas.
require_once __DIR__ . '/../app/includes/site_context.php';

header('Location: ' . site_login_url());
// Meta to redirect stamataei i ektelesi gia na min fortothei kati allo.
exit;
