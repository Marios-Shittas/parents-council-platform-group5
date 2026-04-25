<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/config/db.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0");
header("Pragma: no-cache");
header("Expires: 0");

// Leitourgia registrationWindowState: xeirizetai to antistoixo kommati tis selidas i tou service.
function registrationWindowState(mysqli $conn): array
{
    $stmt = $conn->prepare(
        "SELECT start_date, end_date, ss_status
         FROM SystemSchedule
         WHERE feature = 'registration'
         ORDER BY start_date ASC, ss_id ASC"
    );

    if (!$stmt) {
        return [
            'is_open' => false,
            'message' => 'Η περίοδος εγγραφών είναι κλειστή.',
            'periods' => [],
        ];
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $schedules = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    if (empty($schedules)) {
        return [
            'is_open' => false,
            'message' => 'Η περίοδος εγγραφών είναι κλειστή.',
            'periods' => [],
        ];
    }
    $now = time();

    $formattedPeriods = [];
    foreach ($schedules as $schedule) {
        $status = (string)($schedule['ss_status'] ?? 'inactive');
        if ($status !== 'active') {
            continue;
        }

        $start = !empty($schedule['start_date']) ? strtotime((string)$schedule['start_date']) : false;
        $end = !empty($schedule['end_date']) ? strtotime((string)$schedule['end_date']) : false;
        if ($start === false || $end === false) {
            continue;
        }

        $formattedPeriods[] = date('d/m/Y H:i', $start) . ' - ' . date('d/m/Y H:i', $end);

        if ($now >= $start && $now <= $end) {
            return [
                'is_open' => true,
                'message' => '',
                'periods' => $formattedPeriods,
            ];
        }
    }

    if (empty($formattedPeriods)) {
        return [
            'is_open' => false,
            'message' => 'Η περίοδος εγγραφών είναι κλειστή.',
            'periods' => [],
        ];
    }

    return [
        'is_open' => false,
        'message' => 'Η περίοδος εγγραφών είναι κλειστή.',
        'periods' => $formattedPeriods,
    ];
}

$registrationState = registrationWindowState($conn);
$isRegistrationOpen = (bool)($registrationState['is_open'] ?? false);
$registrationClosedMessage = (string)($registrationState['message'] ?? 'Η περίοδος εγγραφών είναι κλειστή.');
$registrationPeriods = is_array($registrationState['periods'] ?? null) ? $registrationState['periods'] : [];

$registerScriptPath = __DIR__ . '/assets/js/register.jsx';
$registerScriptVersion = is_file($registerScriptPath) ? (string) filemtime($registerScriptPath) : '1';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Lato:wght@300;400&display=swap">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/user_css/public-page-header.css">
    <link rel="stylesheet" href="assets/css/register.css">

    <title>Εγγραφή</title>
</head>
<body>

    <!-- Header -->
    <?php include __DIR__ . '/../app/includes/header.php'; ?>

    <?php if ($isRegistrationOpen): ?>
        <!-- React will render here -->
        <div class="container">
            <div id="root"></div>
        </div>
    <?php else: ?>
        <div class="container py-5">
            <div class="alert alert-warning border-0 shadow-sm alert-rounded-lg" role="alert">
                <h4 class="alert-heading mb-2"><i class="fas fa-calendar-times me-2"></i>Εγγραφές Κλειστές</h4>
                <p class="mb-0"><?php echo htmlspecialchars($registrationClosedMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if (!empty($registrationPeriods)): ?>
                    <hr>
                    <p class="mb-2"><strong>Περίοδοι εγγραφών:</strong></p>
                    <ul class="mb-0">
                        <?php foreach ($registrationPeriods as $periodText): ?>
                            <li><?php echo htmlspecialchars((string)$periodText, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isRegistrationOpen): ?>
        <!-- React / ReactDOM -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>

        <!-- Babel -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>

        <!-- Register JSX -->
        <script type="text/babel" src="assets/js/register.jsx?v=<?php echo urlencode($registerScriptVersion); ?>"></script>
    <?php endif; ?>

    <!-- Footer -->
    <?php include __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
