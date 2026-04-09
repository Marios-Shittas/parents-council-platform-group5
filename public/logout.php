<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/config/db.php';

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

// Get the user role before destroying the session
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
    <script>
        // Clear all possible ways to go back to protected pages
        (function() {
            // Clear session and local storage
            try {
                sessionStorage.clear();
                localStorage.clear();
            } catch(e) {}
            
            // Replace current state multiple times to bury history
            for (let i = 0; i < 10; i++) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Push many forward states
            for (let i = 0; i < 50; i++) {
                window.history.pushState({state: i}, null, window.location.href);
            }
            
            // Redirect without adding to history
            window.location.replace('<?php echo $redirectUrl; ?>');
        })();
    </script>
    <noscript>
        Redirecting...
        <meta http-equiv="refresh" content="0;url=<?php echo $redirectUrl; ?>">
    </noscript>
</body>
</html>