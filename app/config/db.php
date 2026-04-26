<?php
// Arxeio: app\config\db.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
// Sindesi me tin vasi dedomenon gia ola ta app services.
// Kratame ena kentriko include oste ola ta services na pairnoun tin idia mysqli sindesi.
require_once __DIR__ . '/../core/Database.php';

// To Vasi::connect() diavazei ta credentials apo to core config kai epistrefei diavasmay connection.
$conn = Database::connect();
