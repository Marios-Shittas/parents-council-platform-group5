<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the user role before destroying the session
$role = $_SESSION['role'] ?? 'public';

$logoutUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$logoutEmail = isset($_SESSION['email']) ? (string) $_SESSION['email'] : null;

// Log the logout event before destroying the session (only for parents, not admins)
if ($logoutUserId !== null && $logoutUserId > 0 && strtolower((string)$role) === 'parent') {
    require_once __DIR__ . '/../app/config/db.php';

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
