<?php
// Arxeio: app\includes\db.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
// Palio include gia sindesi me tin vasi, oste na to xrisimopoioun oi palies selides.
// Yparxei gia compatibility me arxeia pou perimenoun metavliti $conn sto local scope.
require_once __DIR__ . '/../core/Database.php';

// An i selida exei idi connection den tin ksananoigoume, gia na apofevgoume diplasious connections.
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = Database::connect();
}
