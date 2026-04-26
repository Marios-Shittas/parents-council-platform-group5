<?php
// Arxeio: public\logout.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/config/db.php';

// Leitourgia clearUserTokenOnLogout: xeirizetai to antistoixo kommati tis selidas i tou service.
function clearUserTokenOnLogout(mysqli $conn, ?int $userId, ?string $email): void
{
    if ($userId !== null && $userId > 0) {
        $stmt = $conn->prepare('UPDATE Users SET token = NULL, token_expiry = NULL WHERE user_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
        return;
    }

    $normalizedEmail = trim((string) $email);
    if ($normalizedEmail !== '') {
        $stmt = $conn->prepare('UPDATE Users SET token = NULL, token_expiry = NULL WHERE email = ?');
        if ($stmt) {
            $stmt->bind_param('s', $normalizedEmail);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Pairnei to xristis role before destroying to session
$role = $_SESSION['role'] ?? 'public';

$logoutUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$logoutEmail = isset($_SESSION['email']) ? (string) $_SESSION['email'] : null;

if (($logoutUserId === null || $logoutUserId <= 0) && isset($_SESSION['temp_user_id'])) {
    $logoutUserId = (int) $_SESSION['temp_user_id'];
}

if (($logoutEmail === null || $logoutEmail === '') && isset($_SESSION['temp_email'])) {
    $logoutEmail = (string) $_SESSION['temp_email'];
}

clearUserTokenOnLogout($conn, $logoutUserId, $logoutEmail);

// Log to logout ekdilosi before destroying to session (only gia goneis, not admins)
if ($logoutUserId !== null && $logoutUserId > 0 && strtolower((string)$role) === 'parent') {
    $logoutDescription = sprintf(
        'Logout for parent user #%d (%s).',
        $logoutUserId,
        (string)$logoutEmail
    );
    $stmtLog = $conn->prepare('INSERT INTO Logs (user_id, action, description) VALUES (?, ?, ?)');
    if ($stmtLog) {
        $logoutAction = 'PARENT_LOGOUT';
        $stmtLog->bind_param('iss', $logoutUserId, $logoutAction, $logoutDescription);
        $stmtLog->execute();
        $stmtLog->close();
    }
}

// Determine redirect URL based on role
switch ($role) {
    case 'admin':
        $redirectUrl = '/parents-council-platform-group5/public/home.php';
        break;
    case 'parent':
        $redirectUrl = '/parents-council-platform-group5/public/home.php';
        break;
    default:
        $redirectUrl = '/parents-council-platform-group5/public/home.php';
        break;
}

session_unset();
session_destroy();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
</head>
<body>
    <script src="assets/js/app-page-config.js" data-config="<?php echo htmlspecialchars(json_encode(['LOGOUT_REDIRECT_URL' => $redirectUrl], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="assets/js/logout-redirect.js" defer></script>
    <noscript>
        Redirecting...
        <meta http-equiv="refresh" content="0;url=<?php echo $redirectUrl; ?>">
    </noscript>
</body>
</html>
