<?php
require_once __DIR__ . '/../../app/services/UsersService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0, private");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /parents-council-platform-group5/public/login.php');
    exit;
}

function normalizeScheduleStatus(string $status): string
{
    return in_array($status, ['active', 'inactive'], true) ? $status : 'inactive';
}

function normalizeScheduleFeature(string $feature): string
{
    $normalized = trim((string)$feature);
    if ($normalized === 'cleanup_applications' || $normalized === 'cleanuo_submissions') {
        $normalized = 'cleanup_submissions';
    }

    $allowed = ['registration', 'delete_users', 'cleanup_submissions'];
    return in_array($normalized, $allowed, true) ? $normalized : 'registration';
}

function scheduleFeatureLabel(string $feature): string
{
    $map = [
        'registration' => 'Εγγραφές',
        'delete_users' => 'Διαγραφή Χρηστών',
        'cleanup_submissions' => 'Καθαρισμός Υποβολών',
    ];

    return $map[$feature] ?? $feature;
}

function normalizeDateTimeLocalInput(string $value): ?string
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return null;
    }

    $dateTime = DateTime::createFromFormat('d/m/Y H:i', $trimmed);

    if (!$dateTime instanceof DateTime) {
        $dateTime = DateTime::createFromFormat('Y-m-d\\TH:i', $trimmed);
        if (!$dateTime instanceof DateTime) {
            return null;
        }
    }

    return $dateTime->format('Y-m-d H:i:s');
}

function toDateTimeLocalValue(?string $value): string
{
    if (!is_string($value) || trim($value) === '') {
        return '';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d\\TH:i', $timestamp) : '';
}

function redirectWithFlash(string $message, string $type = 'info', string $email = ''): void
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_message_type'] = $type;

    $redirectUrl = 'programatismo-litourgion.php';
    if ($email !== '') {
        $redirectUrl .= '?email=' . urlencode($email);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$usersService = new UsersService();
$currentAdminId = (int)($_SESSION['user_id'] ?? 0);
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_message_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

$searchEmail = trim((string)($_GET['email'] ?? ''));
$formAction = 'programatismo-litourgion.php';
if ($searchEmail !== '') {
    $formAction .= '?email=' . urlencode($searchEmail);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_registration_schedule') {
        $scheduleId = (int)($_POST['registration_schedule_id'] ?? 0);
        $feature = normalizeScheduleFeature((string)($_POST['schedule_feature'] ?? 'registration'));
        $startDate = normalizeDateTimeLocalInput((string)($_POST['registration_start_date'] ?? ''));
        $endDate = normalizeDateTimeLocalInput((string)($_POST['registration_end_date'] ?? ''));
        $status = normalizeScheduleStatus((string)($_POST['registration_status'] ?? 'active'));

        if ($scheduleId <= 0 || $startDate === null || $endDate === null) {
            redirectWithFlash('Συμπλήρωσε έγκυρες ημερομηνίες για το πρόγραμμα λειτουργιών.', 'danger', $searchEmail);
        }

        $result = $usersService->updateSystemSchedule($scheduleId, $feature, $startDate, $endDate, $status, $currentAdminId);
        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger',
            $searchEmail
        );
    }

    if ($action === 'add_registration_schedule') {
        $feature = normalizeScheduleFeature((string)($_POST['schedule_feature'] ?? 'registration'));
        $startDate = normalizeDateTimeLocalInput((string)($_POST['registration_start_date'] ?? ''));
        $endDate = normalizeDateTimeLocalInput((string)($_POST['registration_end_date'] ?? ''));
        $status = normalizeScheduleStatus((string)($_POST['registration_status'] ?? 'active'));

        if ($startDate === null || $endDate === null) {
            redirectWithFlash('Συμπλήρωσε έγκυρες ημερομηνίες για τη νέα περίοδο λειτουργιών.', 'danger', $searchEmail);
        }

        $result = $usersService->createSystemSchedule($feature, $startDate, $endDate, $status, $currentAdminId);
        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger',
            $searchEmail
        );
    }

    if ($action === 'delete_registration_schedule') {
        $scheduleId = (int)($_POST['registration_schedule_id'] ?? 0);
        if ($scheduleId <= 0) {
            redirectWithFlash('Μη έγκυρο πρόγραμμα.', 'danger', $searchEmail);
        }

        $result = $usersService->deleteSystemSchedule($scheduleId, $currentAdminId);
        redirectWithFlash(
            $result['message'] ?? 'Η ενέργεια ολοκληρώθηκε.',
            !empty($result['success']) ? 'success' : 'danger',
            $searchEmail
        );
    }
}

$registrationSchedules = $usersService->getSystemSchedules();
$scheduleFeatureOptions = ['registration', 'delete_users', 'cleanup_submissions'];
$existingScheduleFeatures = [];
foreach ($registrationSchedules as $schedule) {
    $existingFeature = normalizeScheduleFeature((string)($schedule['feature'] ?? ''));
    if ($existingFeature !== '' && !in_array($existingFeature, $existingScheduleFeatures, true)) {
        $existingScheduleFeatures[] = $existingFeature;
    }
}
$availableScheduleFeatures = array_values(array_filter(
    $scheduleFeatureOptions,
    static fn(string $feature): bool => !in_array($feature, $existingScheduleFeatures, true)
));
$newScheduleFeature = $availableScheduleFeatures[0] ?? '';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ενέργειες Συστήματος - Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_panel.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin_programatismo_litourgion.css">
</head>
<body>
<div class="admin-wrapper">
    <?php include __DIR__ . '/../../app/includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Πίσω στο Dashboard
        </a>

        <div class="admin-header admin-page-header">
            <div>
                <h1><i class="fas fa-cogs me-2"></i>Ενέργειες Συστήματος</h1>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Κλείσιμο"></button>
            </div>
        <?php endif; ?>

        <section class="program-feature-card card-custom mb-4">
            <div class="program-feature-head">
                <div>
                    <span class="program-feature-kicker">Προγραμματισμός Συστήματος</span>
                    <h2>Χρονικά παράθυρα λειτουργιών</h2>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle admin-dashboard-table program-feature-table mb-0">
                    <thead>
                    <tr>
                        <th>Λειτουργία</th>
                        <th>Έναρξη</th>
                        <th>Λήξη</th>
                        <th>Κατάσταση</th>
                        <th class="text-end">Ενέργεια</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($registrationSchedules)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Δεν υπάρχουν περίοδοι ακόμα.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registrationSchedules as $schedule): ?>
                            <?php
                                $scheduleId = (int)($schedule['ss_id'] ?? 0);
                                $scheduleFormId = 'registration-schedule-form-' . $scheduleId;
                                $scheduleDeleteFormId = 'registration-schedule-delete-form-' . $scheduleId;
                                $rowFeature = normalizeScheduleFeature((string)($schedule['feature'] ?? 'registration'));
                                $rowStatus = normalizeScheduleStatus((string)($schedule['ss_status'] ?? 'inactive'));
                            ?>
                            <tr>
                                <td>
                                    <select name="schedule_feature" class="form-select" form="<?php echo htmlspecialchars($scheduleFormId); ?>" required>
                                        <option value="registration" <?php echo $rowFeature === 'registration' ? 'selected' : ''; ?>><?php echo htmlspecialchars(scheduleFeatureLabel('registration')); ?></option>
                                        <option value="delete_users" <?php echo $rowFeature === 'delete_users' ? 'selected' : ''; ?>><?php echo htmlspecialchars(scheduleFeatureLabel('delete_users')); ?></option>
                                        <option value="cleanup_submissions" <?php echo $rowFeature === 'cleanup_submissions' ? 'selected' : ''; ?>><?php echo htmlspecialchars(scheduleFeatureLabel('cleanup_submissions')); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="datetime-local" name="registration_start_date" class="form-control" value="<?php echo htmlspecialchars(toDateTimeLocalValue($schedule['start_date'] ?? null)); ?>" form="<?php echo htmlspecialchars($scheduleFormId); ?>" required>
                                </td>
                                <td>
                                    <input type="datetime-local" name="registration_end_date" class="form-control" value="<?php echo htmlspecialchars(toDateTimeLocalValue($schedule['end_date'] ?? null)); ?>" form="<?php echo htmlspecialchars($scheduleFormId); ?>" required>
                                </td>
                                <td>
                                    <select name="registration_status" class="form-select" form="<?php echo htmlspecialchars($scheduleFormId); ?>" required>
                                        <option value="active" <?php echo $rowStatus === 'active' ? 'selected' : ''; ?>>Ενεργό</option>
                                        <option value="inactive" <?php echo $rowStatus === 'inactive' ? 'selected' : ''; ?>>Ανενεργό</option>
                                    </select>
                                </td>
                                <td class="text-end">
                                    <button type="submit" class="btn btn-primary-custom" form="<?php echo htmlspecialchars($scheduleFormId); ?>">
                                        <i class="fas fa-save me-1"></i>Αποθήκευση
                                    </button>
                                    <button type="button" class="btn btn-outline-danger ms-2" data-bs-toggle="modal" data-bs-target="#deleteScheduleModal" data-schedule-id="<?php echo $scheduleId; ?>" data-schedule-feature="<?php echo htmlspecialchars(scheduleFeatureLabel($rowFeature), ENT_QUOTES, 'UTF-8'); ?>" data-schedule-start="<?php echo htmlspecialchars((string)($schedule['start_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-schedule-end="<?php echo htmlspecialchars((string)($schedule['end_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-schedule-delete-form-id="<?php echo htmlspecialchars($scheduleDeleteFormId, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fas fa-trash-alt me-1"></i>Διαγραφή
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <tr class="program-feature-new-row">
                        <?php $newScheduleFormId = 'registration-schedule-form-new'; ?>
                        <td>
                            <select name="schedule_feature" class="form-select" form="<?php echo $newScheduleFormId; ?>" <?php echo $newScheduleFeature === '' ? 'disabled' : 'required'; ?>>
                                <?php if ($newScheduleFeature === ''): ?>
                                    <option value="" selected>Όλες οι λειτουργίες υπάρχουν ήδη</option>
                                <?php else: ?>
                                    <?php foreach ($scheduleFeatureOptions as $featureOption): ?>
                                        <?php
                                            $featureExists = in_array($featureOption, $existingScheduleFeatures, true);
                                            $isSelectedFeature = $featureOption === $newScheduleFeature;
                                        ?>
                                        <option value="<?php echo htmlspecialchars($featureOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSelectedFeature ? 'selected' : ''; ?> <?php echo $featureExists ? 'disabled' : ''; ?>>
                                            <?php echo htmlspecialchars(scheduleFeatureLabel($featureOption), ENT_QUOTES, 'UTF-8'); ?><?php echo $featureExists ? ' (υπάρχει ήδη)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </td>
                        <td>
                            <input type="datetime-local" name="registration_start_date" class="form-control" form="<?php echo $newScheduleFormId; ?>" <?php echo $newScheduleFeature === '' ? 'disabled' : 'required'; ?>>
                        </td>
                        <td>
                            <input type="datetime-local" name="registration_end_date" class="form-control" form="<?php echo $newScheduleFormId; ?>" <?php echo $newScheduleFeature === '' ? 'disabled' : 'required'; ?>>
                        </td>
                        <td>
                            <select name="registration_status" class="form-select" form="<?php echo $newScheduleFormId; ?>" <?php echo $newScheduleFeature === '' ? 'disabled' : 'required'; ?>>
                                <option value="active" selected>Ενεργό</option>
                                <option value="inactive">Ανενεργό</option>
                            </select>
                        </td>
                        <td class="text-end">
                            <button type="submit" class="btn btn-success" form="<?php echo $newScheduleFormId; ?>" <?php echo $newScheduleFeature === '' ? 'disabled' : ''; ?>>
                                <i class="fas fa-plus me-1"></i>Προσθήκη
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <?php foreach ($registrationSchedules as $schedule): ?>
                <?php
                    $scheduleId = (int)($schedule['ss_id'] ?? 0);
                    $scheduleFormId = 'registration-schedule-form-' . $scheduleId;
                    $scheduleDeleteFormId = 'registration-schedule-delete-form-' . $scheduleId;
                ?>
                <form id="<?php echo htmlspecialchars($scheduleFormId); ?>" method="POST" class="registration-schedule-form" action="<?php echo htmlspecialchars($formAction); ?>">
                    <input type="hidden" name="action" value="update_registration_schedule">
                    <input type="hidden" name="registration_schedule_id" value="<?php echo $scheduleId; ?>">
                </form>
                <form id="<?php echo htmlspecialchars($scheduleDeleteFormId); ?>" method="POST" class="registration-schedule-form" action="<?php echo htmlspecialchars($formAction); ?>">
                    <input type="hidden" name="action" value="delete_registration_schedule">
                    <input type="hidden" name="registration_schedule_id" value="<?php echo $scheduleId; ?>">
                </form>
            <?php endforeach; ?>
            <form id="registration-schedule-form-new" method="POST" class="registration-schedule-form" action="<?php echo htmlspecialchars($formAction); ?>">
                <input type="hidden" name="action" value="add_registration_schedule">
            </form>
        </section>

        <section class="program-log-card card-custom">
            <div class="program-log-head">
                <div>
                    <span class="program-feature-kicker">Log Search</span>
                    <h2>Ενέργειες γονέα με βάση το email</h2>
                    <p>Εισήγαγε το email του γονέα για να δεις τα στοιχεία του και τις καταγεγραμμένες ενέργειές του από τον πίνακα Logs.</p>
                </div>
            </div>

            <div id="admin-programatismo-log-search-root" data-initial-email="<?php echo htmlspecialchars($searchEmail, ENT_QUOTES, 'UTF-8'); ?>"></div>
        </section>
    </main>
</div>

<div class="modal fade" id="deleteScheduleModal" tabindex="-1" aria-labelledby="deleteScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteScheduleModalLabel">Διαγραφή προγράμματος</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="delete_schedule_feature">Λειτουργία: —</p>
                <p class="mb-0" id="delete_schedule_dates">Διάστημα: —</p>
                <input type="hidden" id="delete_schedule_form_id" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteScheduleButton">Διαγραφή</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.development.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.development.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/babel" src="../assets/js/admin-programatismo-litourgion.jsx"></script>
<script>
    (function () {
        var deleteScheduleModal = document.getElementById('deleteScheduleModal');
        if (!deleteScheduleModal) {
            return;
        }

        deleteScheduleModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) {
                return;
            }

            var featureLabel = button.getAttribute('data-schedule-feature') || '—';
            var startDate = button.getAttribute('data-schedule-start') || '';
            var endDate = button.getAttribute('data-schedule-end') || '';
            var formId = button.getAttribute('data-schedule-delete-form-id') || '';

            var featureEl = document.getElementById('delete_schedule_feature');
            var datesEl = document.getElementById('delete_schedule_dates');
            var formIdEl = document.getElementById('delete_schedule_form_id');

            if (featureEl) {
                featureEl.textContent = 'Λειτουργία: ' + featureLabel;
            }

            if (datesEl) {
                datesEl.textContent = 'Διάστημα: ' + (startDate || '—') + ' έως ' + (endDate || '—');
            }

            if (formIdEl) {
                formIdEl.value = formId;
            }
        });

        var confirmDeleteScheduleButton = document.getElementById('confirmDeleteScheduleButton');
        if (confirmDeleteScheduleButton) {
            confirmDeleteScheduleButton.addEventListener('click', function () {
                var formIdEl = document.getElementById('delete_schedule_form_id');
                var formId = formIdEl ? formIdEl.value : '';
                if (!formId) {
                    return;
                }

                var form = document.getElementById(formId);
                if (!form) {
                    return;
                }

                form.submit();
            });
        }
    })();
</script>
</body>
</html>
