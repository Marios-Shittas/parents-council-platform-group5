<?php
require_once __DIR__ . '/config.php';

$host = DB_HOST;
$user = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset(DB_CHARSET);
?>
