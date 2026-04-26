<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/ApprovalMailer.php';
require_once __DIR__ . '/EmailRejection.php';

class UsersService
{
    private mysqli $conn;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM Users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $user ?: null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getUserById(int $userId)
    {
        $sql = "SELECT * FROM Users WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $user ?: null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function login($inputEmail, $inputPassword)
    {
        $email = trim((string)$inputEmail);
        $this->resetAllExpiredWaitingPaymentUsersToPending();

        $user = $this->getUserByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'Μη έγκυρο email ή κωδικός πρόσβασης.'];
        }

        if ((string)($user['account_status'] ?? '') !== 'active') {
            return ['success' => false, 'message' => 'Ο λογαριασμός σας δεν είναι ακόμα ενεργός.'];
        }

        if (!password_verify($inputPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Μη έγκυρο email ή κωδικός πρόσβασης.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60));

        $stmt = $this->conn->prepare("
            UPDATE Users
            SET token = ?, token_expiry = ?
            WHERE user_id = ?
        ");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία δημιουργίας διακριτικού.'];
        }
        $stmt->bind_param("ssi", $token, $expiresAt, $user['user_id']);
        $stmt->execute();
        $stmt->close();

        $role = strtolower((string)($user['role'] ?? ''));
        if ($role === 'parent') {
            $loginDescription = sprintf(
                'Successful login for parent user #%d (%s).',
                (int)$user['user_id'],
                (string)($user['email'] ?? '')
            );
            $this->insertAdminLog((int)$user['user_id'], 'PARENT_LOGIN', $loginDescription);
        }

        return [
            'success' => true,
            'user' => $user,
            'role' => $user['role'],
            'token' => $token,
            'message' => 'Η σύνδεση ολοκληρώθηκε επιτυχώς.'
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function resetAllExpiredWaitingPaymentUsersToPending(): int
    {
        $stmt = $this->conn->prepare(
            "UPDATE Users
             SET account_status = 'pending', token = NULL, token_expiry = NULL
             WHERE role = 'parent'
               AND account_status = 'waiting_payment'
               AND token_expiry IS NOT NULL
               AND token_expiry < NOW()"
        );

        if (!$stmt) {
            return 0;
        }

        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return max(0, $affectedRows);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function runScheduledMaintenance(): void
    {
        $this->runScheduledMaintenanceWithReport();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function runScheduledMaintenanceWithReport(): array
    {
        $deletedUsers = $this->runScheduledUsersCleanup();
        $deletedSubmissions = $this->runScheduledSubmissionsCleanup();

        return [
            'deleted_users' => $deletedUsers,
            'deleted_submissions' => $deletedSubmissions,
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function runScheduledUsersCleanup(): int
    {
        // System actions were intentionally disabled.
        return 0;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function runScheduledSubmissionsCleanup(): int
    {
        // System actions were intentionally disabled.
        return 0;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function forgot($email)
    {
        $email = trim((string)$email);
        $user = $this->getUserByEmail($email);
        if (!$user || $email !== $user['email']) {
            return ['success' => false, 'message' => 'Μη έγκυρο email.'];
        }

        if ((string)($user['account_status'] ?? '') !== 'active') {
            return ['success' => false, 'message' => 'Ο λογαριασμός δεν είναι ενεργός.'];
        }

        return ['success' => true, 'message' => 'Ο σύνδεσμος στάλθηκε.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function resetPassword($email, $newPassword) 
    {
        if ($newPassword === '') {
            return ['success' => false, 'message' => 'Ο κωδικός δεν μπορεί να είναι κενός.'];
        }

        if (preg_match('/\s/', $newPassword)) {
            return ['success' => false, 'message' => 'Ο κωδικός δεν μπορεί να περιέχει κενά.'];
        }

        // At least 8 chars, with letters, numbers, and a special character.
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $newPassword)) {
            return [
                'success' => false,
                'message' => 'Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες και να περιλαμβάνει γράμματα, αριθμούς και 1 ειδικό χαρακτήρα.'
            ];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $this->conn->prepare ("UPDATE Users SET password = ? WHERE email = ?");

        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία προετοιμασίας επαναφοράς κωδικού.'];
        }

        $stmt->bind_param("ss", $hashedPassword, $email);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Αποτυχία επαναφοράς κωδικού.'];
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected <= 0) {
            return ['success' => false, 'message' => 'Δεν επαναφέρθηκε ο κωδικός.'];
        }
        
        return ['success' => true, 'message' => 'Ο κωδικός επαναφέρθηκε επιτυχώς.'];

    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getAllUsersForAdmin(string $sort = 'pending_first'): array
    {
        switch ($sort) {
            case 'newest':
                $orderBy = "
                    CASE WHEN u.user_id = 1 THEN 0 ELSE 1 END,
                    CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END,
                    u.created_at DESC,
                    u.user_id DESC
                ";
                break;
            case 'oldest':
                $orderBy = "
                    CASE WHEN u.user_id = 1 THEN 0 ELSE 1 END,
                    CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END,
                    u.created_at ASC,
                    u.user_id ASC
                ";
                break;
            case 'name_az':
                $orderBy = "
                    CASE WHEN u.user_id = 1 THEN 0 ELSE 1 END,
                    CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END,
                    u.surname ASC,
                    u.name ASC,
                    u.user_id ASC
                ";
                break;
            case 'status_az':
                $orderBy = "
                    CASE u.account_status
                        WHEN 'pending' THEN 0
                        WHEN 'approved' THEN 1
                        WHEN 'waiting_payment' THEN 2
                        WHEN 'active' THEN 3
                        WHEN 'rejected' THEN 4
                        ELSE 5
                    END,
                    CASE WHEN u.user_id = 1 THEN 0 ELSE 1 END,
                    CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END,
                    u.created_at DESC,
                    u.user_id DESC
                ";
                break;
            case 'pending_first':
            default:
                $orderBy = "
                    CASE WHEN u.account_status = 'pending' THEN 0 ELSE 1 END,
                    CASE WHEN u.user_id = 1 THEN 0 ELSE 1 END,
                    CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END,
                    u.created_at DESC,
                    u.user_id DESC
                ";
                break;
        }

        $sql = "
            SELECT
                u.user_id,
                u.name,
                u.surname,
                u.email,
                u.phone_number,
                u.number_of_children,
                u.role,
                u.account_status,
                u.created_at,
                COALESCE(children.child_count, 0) AS child_count,
                COALESCE(orders.order_count, 0) AS order_count,
                COALESCE(payments.payment_count, 0) AS payment_count
            FROM Users u
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS child_count
                FROM Children
                GROUP BY user_id
            ) children ON children.user_id = u.user_id
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS order_count
                FROM Orders
                GROUP BY user_id
            ) orders ON orders.user_id = u.user_id
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS payment_count
                FROM Payments
                GROUP BY user_id
            ) payments ON payments.user_id = u.user_id
            WHERE u.email NOT LIKE 'public_guest%@guest.local'
            ORDER BY {$orderBy}
        ";

        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getPendingRegistrationCount(): int
    {
        $sql = "
            SELECT COUNT(*) AS count
            FROM Users
            WHERE role = 'parent'
              AND account_status = 'pending'
        ";

        $result = $this->conn->query($sql);
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return max(0, (int)($row['count'] ?? 0));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getSystemSchedules(): array
    {
        $sql = "
            SELECT ss_id, feature, start_date, end_date, ss_status
            FROM SystemSchedule
            ORDER BY feature ASC, start_date ASC, ss_id ASC
        ";

        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getParentLogsByEmail(string $email): array
    {
        $normalizedEmail = trim($email);
        if ($normalizedEmail === '') {
            return [
                'found' => false,
                'parent' => null,
                'logs' => [],
            ];
        }

        $parentStmt = $this->conn->prepare(
            "SELECT user_id, name, surname, email, phone_number, account_status, created_at
             FROM Users
             WHERE email = ?
               AND role = 'parent'
             LIMIT 1"
        );

        if (!$parentStmt) {
            return [
                'found' => false,
                'parent' => null,
                'logs' => [],
            ];
        }

        $parentStmt->bind_param('s', $normalizedEmail);
        $parentStmt->execute();
        $parentResult = $parentStmt->get_result();
        $parent = $parentResult ? $parentResult->fetch_assoc() : null;
        $parentStmt->close();

        if (!$parent) {
            return [
                'found' => false,
                'parent' => null,
                'logs' => [],
            ];
        }

        $logsStmt = $this->conn->prepare(
            "SELECT log_id, action, description, created_at
             FROM Logs
             WHERE user_id = ?
             ORDER BY created_at DESC, log_id DESC
             LIMIT 200"
        );

        if (!$logsStmt) {
            return [
                'found' => true,
                'parent' => $parent,
                'logs' => [],
            ];
        }

        $parentUserId = (int)($parent['user_id'] ?? 0);
        $logsStmt->bind_param('i', $parentUserId);
        $logsStmt->execute();
        $logsResult = $logsStmt->get_result();
        $logs = $logsResult ? $logsResult->fetch_all(MYSQLI_ASSOC) : [];
        $logsStmt->close();

        return [
            'found' => true,
            'parent' => $parent,
            'logs' => $logs,
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function isSystemFeatureOpen(string $feature): array
    {
        $normalizedFeature = $this->normalizeScheduleFeature($feature);
        if ($normalizedFeature === '') {
            return [
                'is_open' => false,
                'message' => 'Μη έγκυρη λειτουργία προγραμματισμού.',
            ];
        }

        $stmt = $this->conn->prepare(
            "SELECT start_date, end_date, ss_status
             FROM SystemSchedule
             WHERE feature = ?
             ORDER BY start_date ASC, ss_id ASC"
        );

        if (!$stmt) {
            return [
                'is_open' => false,
                'message' => 'Η λειτουργία δεν είναι διαθέσιμη αυτή τη στιγμή.',
            ];
        }

        $stmt->bind_param('s', $normalizedFeature);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedules = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        if (empty($schedules)) {
            return [
                'is_open' => false,
                'message' => 'Δεν υπάρχει ορισμένη περίοδος για τη λειτουργία αυτή.',
            ];
        }

        $activePeriods = [];
        $now = time();

        foreach ($schedules as $schedule) {
            if ((string)($schedule['ss_status'] ?? 'inactive') !== 'active') {
                continue;
            }

            $startTs = !empty($schedule['start_date']) ? strtotime((string)$schedule['start_date']) : false;
            $endTs = !empty($schedule['end_date']) ? strtotime((string)$schedule['end_date']) : $startTs;
            if ($startTs === false || $endTs === false) {
                continue;
            }

            if ($this->isSingleMomentScheduleFeature($normalizedFeature) && $endTs <= $startTs) {
                // Krataei anoiktes tis monostigmes ergasies gia ligo, oste na min xathoun apo xronismo cron/selidas.
                $endTs = $startTs + 600;
            }

            $activePeriods[] = date('d/m/Y H:i', $startTs) . ' - ' . date('d/m/Y H:i', $endTs);

            if ($now >= $startTs && $now <= $endTs) {
                return [
                    'is_open' => true,
                    'message' => '',
                ];
            }
        }

        if (empty($activePeriods)) {
            return [
                'is_open' => false,
                'message' => 'Η λειτουργία είναι ανενεργή. Δεν υπάρχουν ενεργές περίοδοι.',
            ];
        }

        return [
            'is_open' => false,
            'message' => 'Η λειτουργία είναι κλειστή αυτή τη στιγμή. Ενεργές περίοδοι: ' . implode(' | ', $activePeriods),
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function updateSystemSchedule(int $ssId, string $feature, string $startDate, string $endDate, string $status = 'active', ?int $actorUserId = null): array
    {
        if ($ssId <= 0) {
            return ['success' => false, 'message' => 'Μη έγκυρο πρόγραμμα.'];
        }

        $normalized = $this->normalizeScheduleInput($feature, $startDate, $endDate, $status);
        if (!$normalized['success']) {
            return $normalized;
        }

        $normalizedFeature = $normalized['feature'];
        $normalizedStart = $normalized['start_date'];
        $normalizedEnd = $normalized['end_date'];
        $normalizedStatus = $normalized['status'];

        $stmt = $this->conn->prepare(
            "UPDATE SystemSchedule
             SET feature = ?, start_date = ?, end_date = ?, ss_status = ?
             WHERE ss_id = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία προετοιμασίας ενημέρωσης χρονοπρογράμματος.'];
        }

        $stmt->bind_param('ssssi', $normalizedFeature, $normalizedStart, $normalizedEnd, $normalizedStatus, $ssId);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Αποτυχία ενημέρωσης προγράμματος.'];
        }

        if ($stmt->affected_rows <= 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Το πρόγραμμα δεν βρέθηκε ή δεν άλλαξε.'];
        }

        $stmt->close();

        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_SYSTEM_SCHEDULE_UPDATED',
            "Updated system schedule #{$ssId}: {$normalizedFeature}, {$normalizedStart} - {$normalizedEnd} ({$normalizedStatus})."
        );

        return ['success' => true, 'message' => 'Το πρόγραμμα ενημερώθηκε επιτυχώς.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function createSystemSchedule(string $feature, string $startDate, string $endDate, string $status = 'active', ?int $actorUserId = null): array
    {
        $normalized = $this->normalizeScheduleInput($feature, $startDate, $endDate, $status);
        if (!$normalized['success']) {
            return $normalized;
        }

        $normalizedFeature = $normalized['feature'];
        $normalizedStart = $normalized['start_date'];
        $normalizedEnd = $normalized['end_date'];
        $normalizedStatus = $normalized['status'];

        $stmt = $this->conn->prepare(
            "INSERT INTO SystemSchedule (feature, start_date, end_date, ss_status)
             VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία προετοιμασίας δημιουργίας χρονοπρογράμματος.'];
        }

        $stmt->bind_param('ssss', $normalizedFeature, $normalizedStart, $normalizedEnd, $normalizedStatus);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Αποτυχία αποθήκευσης προγράμματος.'];
        }

        $newScheduleId = (int)$stmt->insert_id;
        $stmt->close();

        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_SYSTEM_SCHEDULE_CREATED',
            "Created system schedule #{$newScheduleId}: {$normalizedFeature}, {$normalizedStart} - {$normalizedEnd} ({$normalizedStatus})."
        );

        return ['success' => true, 'message' => 'Προστέθηκε νέο πρόγραμμα επιτυχώς.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteSystemSchedule(int $ssId, ?int $actorUserId = null): array
    {
        if ($ssId <= 0) {
            return ['success' => false, 'message' => 'Μη έγκυρο πρόγραμμα.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM SystemSchedule WHERE ss_id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία προετοιμασίας διαγραφής προγράμματος.'];
        }

        $stmt->bind_param('i', $ssId);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Αποτυχία διαγραφής προγράμματος.'];
        }

        if ($stmt->affected_rows <= 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Το πρόγραμμα δεν βρέθηκε.'];
        }

        $stmt->close();

        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_SYSTEM_SCHEDULE_DELETED',
            "Deleted system schedule #{$ssId}."
        );

        return ['success' => true, 'message' => 'Το πρόγραμμα διαγράφηκε επιτυχώς.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function createUserByAdmin(array $data, ?int $actorUserId = null): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $surname = trim((string)($data['surname'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone_number'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $role = $this->normalizeRole((string)($data['role'] ?? 'parent'));
        $status = $this->normalizeStatus((string)($data['account_status'] ?? 'active'));

        if ($role === 'admin') {
            $status = 'active';
        }

        if ($name === '' || $surname === '' || $email === '' || $password === '') {
            return ['success' => false, 'message' => 'Συμπλήρωσε όλα τα υποχρεωτικά πεδία.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Το email δεν είναι έγκυρο.'];
        }

        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Υπάρχει ήδη χρήστης με αυτό το email.'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "
            INSERT INTO Users (
                name,
                surname,
                email,
                password,
                phone_number,
                number_of_children,
                role,
                account_status
            ) VALUES (?, ?, ?, ?, ?, 0, ?, ?)
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία προετοιμασίας δημιουργίας χρήστη.'];
        }

        $stmt->bind_param("sssssss", $name, $surname, $email, $hashedPassword, $phone, $role, $status);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Αποτυχία δημιουργίας χρήστη.'];
        }

        $newUserId = (int)$stmt->insert_id;
        $stmt->close();

        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_USER_CREATED',
            "Created {$role} user #{$newUserId} ({$email}) with status {$status}."
        );

        return [
            'success' => true,
            'message' => 'Ο χρήστης δημιουργήθηκε επιτυχώς.',
            'user_id' => $newUserId,
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function updateUserByAdmin(int $userId, array $data, ?int $actorUserId = null): array
    {
        $existingUser = $this->getUserById($userId);
        if (!$existingUser) {
            return ['success' => false, 'message' => 'Ο χρήστης δεν βρέθηκε.'];
        }

        $name = trim((string)($data['name'] ?? ''));
        $surname = trim((string)($data['surname'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone_number'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $rejectionMessage = trim((string)($data['rejection_message'] ?? ''));
        $role = $this->normalizeRole((string)($data['role'] ?? $existingUser['role']));
        $status = $this->normalizeStatus((string)($data['account_status'] ?? $existingUser['account_status']));

        if ($name === '' || $surname === '' || $email === '') {
            return ['success' => false, 'message' => 'Συμπλήρωσε όλα τα υποχρεωτικά πεδία.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Το email δεν είναι έγκυρο.'];
        }

        if ($this->emailExists($email, $userId)) {
            return ['success' => false, 'message' => 'Υπάρχει ήδη άλλος χρήστης με αυτό το email.'];
        }

        if ($role === 'admin') {
            $status = 'active';
        }

        $shouldTriggerRejectionFlow = ($role === 'parent' && $status === 'rejected');
        if ($shouldTriggerRejectionFlow && $rejectionMessage === '') {
            return ['success' => false, 'message' => 'Συμπλήρωσε το μήνυμα απόρριψης για να σταλεί email στον γονέα.'];
        }

        $shouldTriggerApprovalFlow = $this->shouldTriggerApprovalFlow($existingUser, $role, $status);
        $approvalToken = null;
        $approvalExpiry = null;
        $approvalLink = null;

        if ($shouldTriggerApprovalFlow) {
            $approvalToken = bin2hex(random_bytes(32));
            $expiryHours = defined('APPROVAL_LINK_EXPIRY_HOURS') ? max(1, APPROVAL_LINK_EXPIRY_HOURS) : 168;
            $approvalExpiry = date('Y-m-d H:i:s', time() + ($expiryHours * 3600));
            $approvalLink = rtrim(APP_BASE_URL, '/') . '/public/subscription.php?token=' . urlencode($approvalToken);
            $status = 'waiting_payment';
        }

        $sql = "
            UPDATE Users
            SET
                name = ?,
                surname = ?,
                email = ?,
                phone_number = ?,
                role = ?,
                account_status = ?
        ";

        $types = "ssssss";
        $params = [$name, $surname, $email, $phone, $role, $status];

        if ($shouldTriggerApprovalFlow) {
            $sql .= ", token = ?, token_expiry = ?";
            $types .= "ss";
            $params[] = $approvalToken;
            $params[] = $approvalExpiry;
        }

        if ($password !== '') {
            $sql .= ", password = ?";
            $types .= "s";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE user_id = ?";
        $types .= "i";
        $params[] = $userId;

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία προετοιμασίας ενημέρωσης χρήστη.'];
        }

        try {
            $this->conn->begin_transaction();

            $bindValues = [];
            foreach ($params as $index => $value) {
                $bindValues[$index] = &$params[$index];
            }

            array_unshift($bindValues, $types);
            call_user_func_array([$stmt, 'bind_param'], $bindValues);

            if (!$stmt->execute()) {
                throw new RuntimeException('Αποτυχία ενημέρωσης χρήστη.');
            }

            $stmt->close();
            $stmt = null;

            if ($shouldTriggerApprovalFlow && $approvalLink !== null) {
                $this->sendApprovalEmail($email, $approvalLink);
                $this->insertAdminLog(
                    $actorUserId,
                    'ADMIN_USER_APPROVAL_EMAIL_SENT',
                    "Approval email sent to user #{$userId} ({$email})."
                );
            }

            if ($shouldTriggerRejectionFlow) {
                $this->sendRejectionEmail($email, $rejectionMessage);
                $this->insertAdminLog(
                    $actorUserId,
                    'ADMIN_USER_REJECTION_EMAIL_SENT',
                    "Rejection email sent to user #{$userId} ({$email})."
                );
            }

            $this->insertAdminLog(
                $actorUserId,
                'ADMIN_USER_UPDATED',
                "Updated user #{$userId} ({$email}); role={$role}, status={$status}."
            );

            $this->conn->commit();

            if ($shouldTriggerApprovalFlow) {
                return [
                    'success' => true,
                    'message' => "Ο χρήστης εγκρίθηκε, δημιουργήθηκε σύνδεσμος πληρωμής και στάλθηκε email επιβεβαίωσης στο {$email}.",
                    'approval_email_sent' => true,
                    'approval_email' => $email,
                    'subscription_link' => $approvalLink,
                ];
            }

            if ($shouldTriggerRejectionFlow) {
                return [
                    'success' => true,
                    'message' => "Ο χρήστης απορρίφθηκε και στάλθηκε email ενημέρωσης στο {$email}.",
                    'rejection_email_sent' => true,
                    'rejection_email' => $email,
                ];
            }

            return ['success' => true, 'message' => 'Ο χρήστης ενημερώθηκε επιτυχώς.'];
        } catch (Throwable $e) {
            if ($stmt instanceof mysqli_stmt) {
                $stmt->close();
            }

            if ($this->conn->errno === 0 || $this->conn->errno !== 2006) {
                $this->conn->rollback();
            }

            return [
                'success' => false,
                'message' => $shouldTriggerApprovalFlow
                    ? 'Αποτυχία έγκρισης χρήστη και αποστολής email: ' . $e->getMessage()
                    : 'Αποτυχία ενημέρωσης χρήστη: ' . $e->getMessage(),
            ];
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteUserByAdmin(int $userId, ?int $actorUserId = null): array
    {
        $existingUser = $this->getUserById($userId);
        if (!$existingUser) {
            return ['success' => false, 'message' => 'Ο χρήστης δεν βρέθηκε.'];
        }

        try {
            $this->conn->begin_transaction();

            $filesToDelete = $this->collectUserSubmissionFilePaths($userId);
            $this->deleteUserCommerceHistory($userId);

            $stmt = $this->conn->prepare("DELETE FROM Users WHERE user_id = ?");
            if (!$stmt) {
                throw new RuntimeException('Αποτυχία προετοιμασίας διαγραφής χρήστη.');
            }

            $stmt->bind_param("i", $userId);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException('Αποτυχία διαγραφής χρήστη.');
            }

            if ($stmt->affected_rows <= 0) {
                $stmt->close();
                throw new RuntimeException('Δεν διαγράφηκε κάποιος χρήστης.');
            }

            $stmt->close();
            $this->conn->commit();

            foreach ($filesToDelete as $filePath) {
                $this->unlinkProjectRelativeFile($filePath);
            }
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_USER_DELETED',
            "Deleted user #{$userId} ({$existingUser['email']})."
        );

        return ['success' => true, 'message' => 'Ο χρήστης διαγράφηκε επιτυχώς.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function collectUserSubmissionFilePaths(int $userId): array
    {
        $paths = [];

        $legacyStmt = $this->conn->prepare("SELECT file_path FROM Submissions WHERE user_id = ? AND file_path IS NOT NULL");
        if ($legacyStmt) {
            $legacyStmt->bind_param("i", $userId);
            $legacyStmt->execute();
            $result = $legacyStmt->get_result();

            while ($result && $row = $result->fetch_assoc()) {
                $path = trim((string)($row['file_path'] ?? ''));
                if ($path !== '') {
                    $paths[] = $path;
                }
            }

            $legacyStmt->close();
        }

        $submissionStmt = $this->conn->prepare("SELECT uploaded_files FROM ApplicationSubmissions WHERE user_id = ?");
        if ($submissionStmt) {
            $submissionStmt->bind_param("i", $userId);
            $submissionStmt->execute();
            $result = $submissionStmt->get_result();

            while ($result && $row = $result->fetch_assoc()) {
                $uploadedFiles = json_decode((string)($row['uploaded_files'] ?? ''), true);
                if (!is_array($uploadedFiles)) {
                    continue;
                }

                foreach ($uploadedFiles as $path) {
                    if (is_string($path)) {
                        $path = trim($path);
                        if ($path !== '') {
                            $paths[] = $path;
                        }
                    }
                }
            }

            $submissionStmt->close();
        }

        return array_values(array_unique($paths));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function deleteUserCommerceHistory(int $userId): void
    {
        $paymentIds = [];
        $paymentStmt = $this->conn->prepare("SELECT payment_id FROM Payments WHERE user_id = ?");
        if ($paymentStmt) {
            $paymentStmt->bind_param("i", $userId);
            $paymentStmt->execute();
            $result = $paymentStmt->get_result();

            while ($result && $row = $result->fetch_assoc()) {
                $paymentId = (int)($row['payment_id'] ?? 0);
                if ($paymentId > 0) {
                    $paymentIds[] = $paymentId;
                }
            }

            $paymentStmt->close();
        }

        $orderIds = [];
        $orderStmt = $this->conn->prepare("SELECT order_id FROM Orders WHERE user_id = ?");
        if ($orderStmt) {
            $orderStmt->bind_param("i", $userId);
            $orderStmt->execute();
            $result = $orderStmt->get_result();

            while ($result && $row = $result->fetch_assoc()) {
                $orderId = (int)($row['order_id'] ?? 0);
                if ($orderId > 0) {
                    $orderIds[] = $orderId;
                }
            }

            $orderStmt->close();
        }

        if (!empty($paymentIds)) {
            $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));
            $types = str_repeat('i', count($paymentIds));

            $deletePaymentDetails = $this->conn->prepare("DELETE FROM PaymentsDetails WHERE payment_id IN ({$placeholders})");
            if ($deletePaymentDetails) {
                $bindValues = [];
                foreach ($paymentIds as $index => $value) {
                    $bindValues[$index] = &$paymentIds[$index];
                }
                array_unshift($bindValues, $types);
                call_user_func_array([$deletePaymentDetails, 'bind_param'], $bindValues);
                $deletePaymentDetails->execute();
                $deletePaymentDetails->close();
            }

            $deletePayments = $this->conn->prepare("DELETE FROM Payments WHERE payment_id IN ({$placeholders})");
            if ($deletePayments) {
                $bindValues = [];
                foreach ($paymentIds as $index => $value) {
                    $bindValues[$index] = &$paymentIds[$index];
                }
                array_unshift($bindValues, $types);
                call_user_func_array([$deletePayments, 'bind_param'], $bindValues);
                $deletePayments->execute();
                $deletePayments->close();
            }
        }

        if (!empty($orderIds)) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $types = str_repeat('i', count($orderIds));

            $deleteOrderItems = $this->conn->prepare("DELETE FROM OrderItems WHERE order_id IN ({$placeholders})");
            if ($deleteOrderItems) {
                $bindValues = [];
                foreach ($orderIds as $index => $value) {
                    $bindValues[$index] = &$orderIds[$index];
                }
                array_unshift($bindValues, $types);
                call_user_func_array([$deleteOrderItems, 'bind_param'], $bindValues);
                $deleteOrderItems->execute();
                $deleteOrderItems->close();
            }

            $deleteOrders = $this->conn->prepare("DELETE FROM Orders WHERE order_id IN ({$placeholders})");
            if ($deleteOrders) {
                $bindValues = [];
                foreach ($orderIds as $index => $value) {
                    $bindValues[$index] = &$orderIds[$index];
                }
                array_unshift($bindValues, $types);
                call_user_func_array([$deleteOrders, 'bind_param'], $bindValues);
                $deleteOrders->execute();
                $deleteOrders->close();
            }
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function unlinkProjectRelativeFile(string $path): void
    {
        $path = trim($path);
        if ($path === '') {
            return;
        }

        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
        $absolutePath = __DIR__ . '/../../' . $normalizedPath;

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getChildrenByUserId(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT child_id, user_id, name, surname, date_of_birth, school_class
             FROM Children
             WHERE user_id = ?
             ORDER BY date_of_birth ASC, child_id ASC"
        );

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $children = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return $children;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getChildrenGroupedByUserIds(array $userIds): array
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds), static function ($id) {
            return $id > 0;
        }));

        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $types = str_repeat('i', count($userIds));

        $sql = "
            SELECT child_id, user_id, name, surname, date_of_birth, school_class
            FROM Children
            WHERE user_id IN ({$placeholders})
            ORDER BY user_id ASC, date_of_birth ASC, child_id ASC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $bindValues = [];
        foreach ($userIds as $index => $value) {
            $bindValues[$index] = &$userIds[$index];
        }

        array_unshift($bindValues, $types);
        call_user_func_array([$stmt, 'bind_param'], $bindValues);

        $stmt->execute();
        $result = $stmt->get_result();
        $groupedChildren = [];

        while ($result && $row = $result->fetch_assoc()) {
            $parentUserId = (int)($row['user_id'] ?? 0);
            if (!isset($groupedChildren[$parentUserId])) {
                $groupedChildren[$parentUserId] = [];
            }

            $groupedChildren[$parentUserId][] = $row;
        }

        $stmt->close();

        return $groupedChildren;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getOrdersGroupedByUserIds(array $userIds): array
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds), static function ($id) {
            return $id > 0;
        }));

        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $types = str_repeat('i', count($userIds));

        $sql = "
            SELECT order_id, user_id, total_price, created_at, order_status
            FROM Orders
            WHERE user_id IN ({$placeholders})
            ORDER BY user_id ASC, created_at DESC, order_id DESC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $bindValues = [];
        foreach ($userIds as $index => $value) {
            $bindValues[$index] = &$userIds[$index];
        }

        array_unshift($bindValues, $types);
        call_user_func_array([$stmt, 'bind_param'], $bindValues);

        $stmt->execute();
        $result = $stmt->get_result();
        $groupedOrders = [];

        while ($result && $row = $result->fetch_assoc()) {
            $historyUserId = (int)($row['user_id'] ?? 0);
            if (!isset($groupedOrders[$historyUserId])) {
                $groupedOrders[$historyUserId] = [];
            }

            $groupedOrders[$historyUserId][] = $row;
        }

        $stmt->close();

        return $groupedOrders;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getOrdersByUserId(int $userId): array
    {
        $groupedOrders = $this->getOrdersGroupedByUserIds([$userId]);
        return $groupedOrders[$userId] ?? [];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getPaymentsGroupedByUserIds(array $userIds): array
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds), static function ($id) {
            return $id > 0;
        }));

        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $types = str_repeat('i', count($userIds));

        $sql = "
            SELECT payment_id, user_id, amount, payment_date, payment_status, payment_type, transaction_id
            FROM Payments
            WHERE user_id IN ({$placeholders})
            ORDER BY user_id ASC, payment_date DESC, payment_id DESC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $bindValues = [];
        foreach ($userIds as $index => $value) {
            $bindValues[$index] = &$userIds[$index];
        }

        array_unshift($bindValues, $types);
        call_user_func_array([$stmt, 'bind_param'], $bindValues);

        $stmt->execute();
        $result = $stmt->get_result();
        $groupedPayments = [];

        while ($result && $row = $result->fetch_assoc()) {
            $historyUserId = (int)($row['user_id'] ?? 0);
            if (!isset($groupedPayments[$historyUserId])) {
                $groupedPayments[$historyUserId] = [];
            }

            $groupedPayments[$historyUserId][] = $row;
        }

        $stmt->close();

        return $groupedPayments;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getPaymentsByUserId(int $userId): array
    {
        $groupedPayments = $this->getPaymentsGroupedByUserIds([$userId]);
        return $groupedPayments[$userId] ?? [];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getCompletedInsuredChildIdsByUserId(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT ip.child_id
             FROM InsurancePayments ip
             INNER JOIN Payments p ON p.payment_id = ip.payment_id
             INNER JOIN Children c ON c.child_id = ip.child_id
             WHERE c.user_id = ?
               AND p.user_id = ?
               AND p.payment_type = 'insurance'
               AND p.payment_status = 'completed'"
        );

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('ii', $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $childIds = [];

        while ($result && $row = $result->fetch_assoc()) {
            $childId = (int)($row['child_id'] ?? 0);
            if ($childId > 0) {
                $childIds[] = $childId;
            }
        }

        $stmt->close();

        return array_values(array_unique($childIds));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getInsurancePriceSetting(): float
    {
        $result = $this->conn->query('SELECT insurance_price FROM PricingSettings LIMIT 1');
        if (!$result) {
            return 0.0;
        }

        $row = $result->fetch_assoc();
        return (float)($row['insurance_price'] ?? 0.0);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getOrderItemsGroupedByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static function ($id) {
            return $id > 0;
        }));

        if (empty($orderIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $types = str_repeat('i', count($orderIds));

        $sql = "
            SELECT
                oi.order_id,
                oi.product_id,
                p.product_name,
                oi.quantity,
                oi.size,
                oi.price_at_purchase,
                (oi.quantity * oi.price_at_purchase) AS line_total
            FROM OrderItems oi
            INNER JOIN Products p ON p.product_id = oi.product_id
            WHERE oi.order_id IN ({$placeholders})
            ORDER BY oi.order_id DESC, p.product_name ASC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $bindValues = [];
        foreach ($orderIds as $index => $value) {
            $bindValues[$index] = &$orderIds[$index];
        }

        array_unshift($bindValues, $types);
        call_user_func_array([$stmt, 'bind_param'], $bindValues);

        $stmt->execute();
        $result = $stmt->get_result();
        $itemsByOrderId = [];

        while ($result && $row = $result->fetch_assoc()) {
            $orderId = (int)($row['order_id'] ?? 0);
            if (!isset($itemsByOrderId[$orderId])) {
                $itemsByOrderId[$orderId] = [];
            }

            $itemsByOrderId[$orderId][] = [
                'product_id' => (int)($row['product_id'] ?? 0),
                'product_name' => (string)($row['product_name'] ?? ''),
                'quantity' => (int)($row['quantity'] ?? 0),
                'size' => $row['size'] === null ? null : (string)$row['size'],
                'price_at_purchase' => (float)($row['price_at_purchase'] ?? 0),
                'line_total' => (float)($row['line_total'] ?? 0),
            ];
        }

        $stmt->close();

        return $itemsByOrderId;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function createChildForParent(int $parentUserId, array $data, ?int $actorUserId = null): array
    {
        $parent = $this->getUserById($parentUserId);
        if (!$parent || ($parent['role'] ?? '') !== 'parent') {
            return ['success' => false, 'message' => 'Ο γονέας δεν βρέθηκε.'];
        }

        $name = trim((string)($data['name'] ?? ''));
        $surname = trim((string)($data['surname'] ?? ''));
        $dateOfBirth = trim((string)($data['date_of_birth'] ?? ''));
        $schoolClass = trim((string)($data['school_class'] ?? ''));

        if ($name === '' || $surname === '' || $dateOfBirth === '' || $schoolClass === '') {
            return ['success' => false, 'message' => 'Συμπλήρωσε όλα τα στοιχεία του παιδιού.'];
        }

        if (!$this->isValidDate($dateOfBirth)) {
            return ['success' => false, 'message' => 'Η ημερομηνία γέννησης δεν είναι έγκυρη.'];
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO Children (user_id, name, surname, date_of_birth, school_class)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία προετοιμασίας προσθήκης παιδιού.'];
        }

        $stmt->bind_param("issss", $parentUserId, $name, $surname, $dateOfBirth, $schoolClass);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Αποτυχία προσθήκης παιδιού.'];
        }

        $newChildId = (int)$stmt->insert_id;
        $stmt->close();

        $this->syncUserChildrenCount($parentUserId);
        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_CHILD_CREATED',
            "Created child #{$newChildId} for parent #{$parentUserId}."
        );

        return ['success' => true, 'message' => 'Το παιδί προστέθηκε επιτυχώς.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function updateChildForParent(int $childId, int $parentUserId, array $data, ?int $actorUserId = null): array
    {
        $child = $this->getChildByIdForParent($childId, $parentUserId);
        if (!$child) {
            return ['success' => false, 'message' => 'Το παιδί δεν βρέθηκε.'];
        }

        $name = trim((string)($data['name'] ?? ''));
        $surname = trim((string)($data['surname'] ?? ''));
        $dateOfBirth = trim((string)($data['date_of_birth'] ?? ''));
        $schoolClass = trim((string)($data['school_class'] ?? ''));

        if ($name === '' || $surname === '' || $dateOfBirth === '' || $schoolClass === '') {
            return ['success' => false, 'message' => 'Συμπλήρωσε όλα τα στοιχεία του παιδιού.'];
        }

        if (!$this->isValidDate($dateOfBirth)) {
            return ['success' => false, 'message' => 'Η ημερομηνία γέννησης δεν είναι έγκυρη.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE Children
             SET name = ?, surname = ?, date_of_birth = ?, school_class = ?
             WHERE child_id = ? AND user_id = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία προετοιμασίας ενημέρωσης παιδιού.'];
        }

        $stmt->bind_param("ssssii", $name, $surname, $dateOfBirth, $schoolClass, $childId, $parentUserId);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Αποτυχία ενημέρωσης παιδιού.'];
        }

        $stmt->close();

        $this->syncUserChildrenCount($parentUserId);
        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_CHILD_UPDATED',
            "Updated child #{$childId} for parent #{$parentUserId}."
        );

        return ['success' => true, 'message' => 'Τα στοιχεία του παιδιού ενημερώθηκαν επιτυχώς.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteChildForParent(int $childId, int $parentUserId, ?int $actorUserId = null): array
    {
        $child = $this->getChildByIdForParent($childId, $parentUserId);
        if (!$child) {
            return ['success' => false, 'message' => 'Το παιδί δεν βρέθηκε.'];
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM Children
             WHERE child_id = ? AND user_id = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Αποτυχία προετοιμασίας διαγραφής παιδιού.'];
        }

        $stmt->bind_param("ii", $childId, $parentUserId);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Αποτυχία διαγραφής παιδιού.'];
        }

        if ($stmt->affected_rows <= 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Δεν διαγράφηκε κάποιο παιδί.'];
        }

        $stmt->close();

        $this->syncUserChildrenCount($parentUserId);
        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_CHILD_DELETED',
            "Deleted child #{$childId} for parent #{$parentUserId}."
        );

        return ['success' => true, 'message' => 'Το παιδί διαγράφηκε επιτυχώς.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function emailExists(string $email, int $excludeUserId = 0): bool
    {
        if ($excludeUserId > 0) {
            $sql = "SELECT 1 FROM Users WHERE email = ? AND user_id <> ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("si", $email, $excludeUserId);
        } else {
            $sql = "SELECT 1 FROM Users WHERE email = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("s", $email);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getDeletionBlockingData(int $userId): array
    {
        $stmtOrders = $this->conn->prepare("SELECT COUNT(*) AS total FROM Orders WHERE user_id = ?");
        $stmtPayments = $this->conn->prepare("SELECT COUNT(*) AS total FROM Payments WHERE user_id = ?");

        $orderCount = 0;
        $paymentCount = 0;

        if ($stmtOrders) {
            $stmtOrders->bind_param("i", $userId);
            $stmtOrders->execute();
            $result = $stmtOrders->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $orderCount = (int)($row['total'] ?? 0);
            $stmtOrders->close();
        }

        if ($stmtPayments) {
            $stmtPayments->bind_param("i", $userId);
            $stmtPayments->execute();
            $result = $stmtPayments->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $paymentCount = (int)($row['total'] ?? 0);
            $stmtPayments->close();
        }

        return [
            'order_count' => $orderCount,
            'payment_count' => $paymentCount,
            'has_blockers' => $orderCount > 0 || $paymentCount > 0,
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function insertAdminLog(?int $actorUserId, string $action, string $description): void
    {
        if ($actorUserId === null || $actorUserId <= 0) {
            return;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO Logs (user_id, action, description) VALUES (?, ?, ?)"
        );

        if (!$stmt) {
            return;
        }

        $stmt->bind_param("iss", $actorUserId, $action, $description);
        $stmt->execute();
        $stmt->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getChildByIdForParent(int $childId, int $parentUserId)
    {
        $stmt = $this->conn->prepare(
            "SELECT child_id, user_id, name, surname, date_of_birth, school_class
             FROM Children
             WHERE child_id = ? AND user_id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("ii", $childId, $parentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $child = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $child ?: null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function syncUserChildrenCount(int $userId): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE Users
             SET number_of_children = (
                SELECT COUNT(*) FROM Children WHERE user_id = ?
             )
             WHERE user_id = ?"
        );

        if (!$stmt) {
            return;
        }

        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function isValidDate(string $date): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTime && $dt->format('Y-m-d') === $date;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function isValidDateTime(string $dateTime): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $dt instanceof DateTime && $dt->format('Y-m-d H:i:s') === $dateTime;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function normalizeScheduleInput(string $feature, string $startDate, string $endDate, string $status): array
    {
        $normalizedFeature = $this->normalizeScheduleFeature($feature);
        if ($normalizedFeature === '') {
            return ['success' => false, 'message' => 'Μη έγκυρη λειτουργία προγράμματος.'];
        }

        $normalizedStart = trim($startDate);
        $normalizedEnd = trim($endDate);
        $normalizedStatus = in_array($status, ['active', 'inactive'], true) ? $status : 'inactive';

        if (!$this->isValidDateTime($normalizedStart) || !$this->isValidDateTime($normalizedEnd)) {
            return ['success' => false, 'message' => 'Μη έγκυρες ημερομηνίες προγράμματος εγγραφών.'];
        }

        $startTs = strtotime($normalizedStart);
        $endTs = strtotime($normalizedEnd);

        if ($startTs === false || $endTs === false) {
            return ['success' => false, 'message' => 'Μη έγκυρες ημερομηνίες προγράμματος εγγραφών.'];
        }

        if ($this->isSingleMomentScheduleFeature($normalizedFeature) && $endTs <= $startTs) {
            $normalizedEnd = date('Y-m-d H:i:s', $startTs + 600);
            $endTs = strtotime($normalizedEnd);
        }

        if ($startTs > $endTs) {
            return ['success' => false, 'message' => 'Η ημερομηνία έναρξης πρέπει να είναι πριν ή ίδια με την ημερομηνία λήξης.'];
        }

        return [
            'success' => true,
            'feature' => $normalizedFeature,
            'start_date' => $normalizedStart,
            'end_date' => $normalizedEnd,
            'status' => $normalizedStatus,
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function normalizeScheduleFeature(string $feature): string
    {
        $normalized = trim((string)$feature);
        if ($normalized === 'cleanup_applications' || $normalized === 'cleanuo_submissions') {
            $normalized = 'cleanup_submissions';
        }

        $allowed = ['registration', 'delete_users', 'cleanup_submissions'];
        return in_array($normalized, $allowed, true) ? $normalized : '';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function isSingleMomentScheduleFeature(string $feature): bool
    {
        return in_array($feature, ['delete_users', 'cleanup_submissions'], true);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function normalizeRole(string $role): string
    {
        return in_array($role, ['admin', 'parent'], true) ? $role : 'parent';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function normalizeStatus(string $status): string
    {
        $allowedStatuses = ['pending', 'approved', 'rejected', 'waiting_payment', 'active'];
        return in_array($status, $allowedStatuses, true) ? $status : 'pending';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function shouldTriggerApprovalFlow(array $existingUser, string $role, string $status): bool
    {
        if ($role !== 'parent' || $status !== 'approved') {
            return false;
        }

        $currentStatus = (string)($existingUser['account_status'] ?? 'pending');
        // Allow re-running the approval email/token flow for parent accounts
        // unless they are already fully active.
        return in_array($currentStatus, ['pending', 'rejected', 'approved', 'waiting_payment'], true);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function sendApprovalEmail(string $email, string $link): void
    {
        $smtpFailureMessage = '';

        try {
            $mailer = new ApprovalMailer([
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
                'encryption' => SMTP_ENCRYPTION,
                'username' => SMTP_USER,
                'password' => SMTP_PASS,
                'from_email' => SMTP_FROM_EMAIL,
                'from_name' => SMTP_FROM_NAME,
            ]);

            $mailer->sendApprovalEmail($email, $link);
            return;
        } catch (Throwable $smtpException) {
            $smtpFailureMessage = $smtpException->getMessage();
            $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
            }

            $emailApprovalPath = __DIR__ . '/EmailApproval.php';
            if (file_exists($emailApprovalPath)) {
                require_once $emailApprovalPath;
            }

            if (
                class_exists('Kozzy\\ParentsCouncilPlatformGroup5\\services\\EmailApproval') &&
                class_exists('PHPMailer\\PHPMailer\\PHPMailer')
            ) {
                $emailService = new \Kozzy\ParentsCouncilPlatformGroup5\services\EmailApproval([
                    'host' => SMTP_HOST,
                    'port' => SMTP_PORT,
                    'encryption' => SMTP_ENCRYPTION,
                    'username' => SMTP_USER,
                    'password' => SMTP_PASS,
                    'from_email' => SMTP_FROM_EMAIL,
                    'from_name' => SMTP_FROM_NAME,
                ]);

                $emailService->sendApprovalEmail($email, $link);
                return;
            }
        }

        $subject = ApprovalMailer::approvalEmailSubject();
        $message = ApprovalMailer::approvalEmailHtmlBody($link);
        $headers =
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . ">\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/html; charset=UTF-8";

        if (!mail($email, $subject, $message, $headers)) {
            $suffix = $smtpFailureMessage !== '' ? ' SMTP: ' . $smtpFailureMessage : '';
            throw new RuntimeException('Αποτυχία αποστολής email έγκρισης.' . $suffix);
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function sendRejectionEmail(string $email, string $rejectionMessage): void
    {
        $smtpFailureMessage = '';

        try {
            $mailer = new EmailRejection([
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
                'encryption' => SMTP_ENCRYPTION,
                'username' => SMTP_USER,
                'password' => SMTP_PASS,
                'from_email' => SMTP_FROM_EMAIL,
                'from_name' => SMTP_FROM_NAME,
            ]);

            $mailer->sendRejectionEmail($email, $rejectionMessage);
            return;
        } catch (Throwable $smtpException) {
            $smtpFailureMessage = $smtpException->getMessage();
        }

        $subject = 'Ενημέρωση για την αίτησή σας';
        $message =
            "Η αίτησή σας απορρίφθηκε από τον διαχειριστή.\n\n" .
            "Μήνυμα διαχειριστή:\n" .
            $rejectionMessage . "\n\n" .
            "Αν χρειάζεστε διευκρινίσεις, επικοινωνήστε με τον Σύνδεσμο Γονέων.";
        $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';

        if (!mail($email, $subject, $message, $headers)) {
            $suffix = $smtpFailureMessage !== '' ? ' SMTP: ' . $smtpFailureMessage : '';
            throw new RuntimeException('Αποτυχία αποστολής email απόρριψης.' . $suffix);
        }
    }
}
