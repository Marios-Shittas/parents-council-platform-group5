<?php
define('DB_HOST', 'localhost');        // Server
define('DB_NAME', 'parents_council');  // Όνομα βάσης
define('DB_USER', 'root');             // XAMPP default user
define('DB_PASS', '');                  // XAMPP default password
define('DB_CHARSET', 'utf8mb4');       // Κωδικοποίηση
define('APP_BASE_URL', getenv('APP_BASE_URL') ?: 'http://localhost/parents-council-platform-group5');

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_USER', getenv('SMTP_USER') ?: 'nigkaleta@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'dyjs vehc oyiy dvmv');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: SMTP_USER);
define('APPROVAL_LINK_EXPIRY_HOURS', (int) (getenv('APPROVAL_LINK_EXPIRY_HOURS') ?: 168));
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Parents Council');