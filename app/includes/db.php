<?php
require_once __DIR__ . '/../core/Database.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = Database::connect();
}
