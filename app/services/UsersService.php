<?php
// Arxeio: app\services\UsersService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
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
            return ['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ¿ email Î® ÎºÏ‰Î´Î¹ÎºÏŒÏ‚ Ï€ÏÏŒÏƒÎ²Î±ÏƒÎ·Ï‚.'];
        }

        if ((string)($user['account_status'] ?? '') !== 'active') {
            return ['success' => false, 'message' => 'ÎŸ Î»Î¿Î³Î±ÏÎ¹Î±ÏƒÎ¼ÏŒÏ‚ ÏƒÎ±Ï‚ Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î±ÎºÏŒÎ¼Î± ÎµÎ½ÎµÏÎ³ÏŒÏ‚.'];
        }

        if (!password_verify($inputPassword, $user['password'])) {
            return ['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ¿ email Î® ÎºÏ‰Î´Î¹ÎºÏŒÏ‚ Ï€ÏÏŒÏƒÎ²Î±ÏƒÎ·Ï‚.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60));

        $stmt = $this->conn->prepare("
            UPDATE Users
            SET token = ?, token_expiry = ?
            WHERE user_id = ?
        ");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î´Î·Î¼Î¹Î¿Ï…ÏÎ³Î¯Î±Ï‚ Î´Î¹Î±ÎºÏÎ¹Ï„Î¹ÎºÎ¿Ï.'];
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
            'message' => 'Î— ÏƒÏÎ½Î´ÎµÏƒÎ· Î¿Î»Î¿ÎºÎ»Î·ÏÏŽÎ¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'
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
            return ['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ¿ email.'];
        }

        if ((string)($user['account_status'] ?? '') !== 'active') {
            return ['success' => false, 'message' => 'ÎŸ Î»Î¿Î³Î±ÏÎ¹Î±ÏƒÎ¼ÏŒÏ‚ Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ ÎµÎ½ÎµÏÎ³ÏŒÏ‚.'];
        }

        return ['success' => true, 'message' => 'ÎŸ ÏƒÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ ÏƒÏ„Î¬Î»Î¸Î·ÎºÎµ.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function resetPassword($email, $newPassword) 
    {
        if ($newPassword === '') {
            return ['success' => false, 'message' => 'ÎŸ ÎºÏ‰Î´Î¹ÎºÏŒÏ‚ Î´ÎµÎ½ Î¼Ï€Î¿ÏÎµÎ¯ Î½Î± ÎµÎ¯Î½Î±Î¹ ÎºÎµÎ½ÏŒÏ‚.'];
        }

        if (preg_match('/\s/', $newPassword)) {
            return ['success' => false, 'message' => 'ÎŸ ÎºÏ‰Î´Î¹ÎºÏŒÏ‚ Î´ÎµÎ½ Î¼Ï€Î¿ÏÎµÎ¯ Î½Î± Ï€ÎµÏÎ¹Î­Ï‡ÎµÎ¹ ÎºÎµÎ½Î¬.'];
        }

        // At least 8 chars, me letters, numbers, kai a special character.
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $newPassword)) {
            return [
                'success' => false,
                'message' => 'ÎŸ ÎºÏ‰Î´Î¹ÎºÏŒÏ‚ Ï€ÏÎ­Ï€ÎµÎ¹ Î½Î± Î­Ï‡ÎµÎ¹ Ï„Î¿Ï…Î»Î¬Ï‡Î¹ÏƒÏ„Î¿Î½ 8 Ï‡Î±ÏÎ±ÎºÏ„Î®ÏÎµÏ‚ ÎºÎ±Î¹ Î½Î± Ï€ÎµÏÎ¹Î»Î±Î¼Î²Î¬Î½ÎµÎ¹ Î³ÏÎ¬Î¼Î¼Î±Ï„Î±, Î±ÏÎ¹Î¸Î¼Î¿ÏÏ‚ ÎºÎ±Î¹ 1 ÎµÎ¹Î´Î¹ÎºÏŒ Ï‡Î±ÏÎ±ÎºÏ„Î®ÏÎ±.'
            ];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $this->conn->prepare ("UPDATE Users SET password = ? WHERE email = ?");

        if (!$stmt) {
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ ÎµÏ€Î±Î½Î±Ï†Î¿ÏÎ¬Ï‚ ÎºÏ‰Î´Î¹ÎºÎ¿Ï.'];
        }

        $stmt->bind_param("ss", $hashedPassword, $email);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎµÏ€Î±Î½Î±Ï†Î¿ÏÎ¬Ï‚ ÎºÏ‰Î´Î¹ÎºÎ¿Ï.'];
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected <= 0) {
            return ['success' => false, 'message' => 'Î”ÎµÎ½ ÎµÏ€Î±Î½Î±Ï†Î­ÏÎ¸Î·ÎºÎµ Î¿ ÎºÏ‰Î´Î¹ÎºÏŒÏ‚.'];
        }
        
        return ['success' => true, 'message' => 'ÎŸ ÎºÏ‰Î´Î¹ÎºÏŒÏ‚ ÎµÏ€Î±Î½Î±Ï†Î­ÏÎ¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'];

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
                'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ· Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³Î¯Î± Ï€ÏÎ¿Î³ÏÎ±Î¼Î¼Î±Ï„Î¹ÏƒÎ¼Î¿Ï.',
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
                'message' => 'Î— Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³Î¯Î± Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î´Î¹Î±Î¸Î­ÏƒÎ¹Î¼Î· Î±Ï…Ï„Î® Ï„Î· ÏƒÏ„Î¹Î³Î¼Î®.',
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
                'message' => 'Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡ÎµÎ¹ Î¿ÏÎ¹ÏƒÎ¼Î­Î½Î· Ï€ÎµÏÎ¯Î¿Î´Î¿Ï‚ Î³Î¹Î± Ï„Î· Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³Î¯Î± Î±Ï…Ï„Î®.',
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
                'message' => 'Î— Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³Î¯Î± ÎµÎ¯Î½Î±Î¹ Î±Î½ÎµÎ½ÎµÏÎ³Î®. Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ ÎµÎ½ÎµÏÎ³Î­Ï‚ Ï€ÎµÏÎ¯Î¿Î´Î¿Î¹.',
            ];
        }

        return [
            'is_open' => false,
            'message' => 'Î— Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³Î¯Î± ÎµÎ¯Î½Î±Î¹ ÎºÎ»ÎµÎ¹ÏƒÏ„Î® Î±Ï…Ï„Î® Ï„Î· ÏƒÏ„Î¹Î³Î¼Î®. Î•Î½ÎµÏÎ³Î­Ï‚ Ï€ÎµÏÎ¯Î¿Î´Î¿Î¹: ' . implode(' | ', $activePeriods),
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function updateSystemSchedule(int $ssId, string $feature, string $startDate, string $endDate, string $status = 'active', ?int $actorUserId = null): array
    {
        if ($ssId <= 0) {
            return ['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ¿ Ï€ÏÏŒÎ³ÏÎ±Î¼Î¼Î±.'];
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
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ·Ï‚ Ï‡ÏÎ¿Î½Î¿Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚.'];
        }

        $stmt->bind_param('ssssi', $normalizedFeature, $normalizedStart, $normalizedEnd, $normalizedStatus, $ssId);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ·Ï‚ Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚.'];
        }

        if ($stmt->affected_rows <= 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î¤Î¿ Ï€ÏÏŒÎ³ÏÎ±Î¼Î¼Î± Î´ÎµÎ½ Î²ÏÎ­Î¸Î·ÎºÎµ Î® Î´ÎµÎ½ Î¬Î»Î»Î±Î¾Îµ.'];
        }

        $stmt->close();

        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_SYSTEM_SCHEDULE_UPDATED',
            "Updated system schedule #{$ssId}: {$normalizedFeature}, {$normalizedStart} - {$normalizedEnd} ({$normalizedStatus})."
        );

        return ['success' => true, 'message' => 'Î¤Î¿ Ï€ÏÏŒÎ³ÏÎ±Î¼Î¼Î± ÎµÎ½Î·Î¼ÎµÏÏŽÎ¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'];
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
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ Î´Î·Î¼Î¹Î¿Ï…ÏÎ³Î¯Î±Ï‚ Ï‡ÏÎ¿Î½Î¿Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚.'];
        }

        $stmt->bind_param('ssss', $normalizedFeature, $normalizedStart, $normalizedEnd, $normalizedStatus);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î±Ï€Î¿Î¸Î®ÎºÎµÏ…ÏƒÎ·Ï‚ Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚.'];
        }

        $newScheduleId = (int)$stmt->insert_id;
        $stmt->close();

        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_SYSTEM_SCHEDULE_CREATED',
            "Created system schedule #{$newScheduleId}: {$normalizedFeature}, {$normalizedStart} - {$normalizedEnd} ({$normalizedStatus})."
        );

        return ['success' => true, 'message' => 'Î ÏÎ¿ÏƒÏ„Î­Î¸Î·ÎºÎµ Î½Î­Î¿ Ï€ÏÏŒÎ³ÏÎ±Î¼Î¼Î± ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteSystemSchedule(int $ssId, ?int $actorUserId = null): array
    {
        if ($ssId <= 0) {
            return ['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ¿ Ï€ÏÏŒÎ³ÏÎ±Î¼Î¼Î±.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM SystemSchedule WHERE ss_id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ Î´Î¹Î±Î³ÏÎ±Ï†Î®Ï‚ Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚.'];
        }

        $stmt->bind_param('i', $ssId);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î´Î¹Î±Î³ÏÎ±Ï†Î®Ï‚ Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚.'];
        }

        if ($stmt->affected_rows <= 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î¤Î¿ Ï€ÏÏŒÎ³ÏÎ±Î¼Î¼Î± Î´ÎµÎ½ Î²ÏÎ­Î¸Î·ÎºÎµ.'];
        }

        $stmt->close();

        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_SYSTEM_SCHEDULE_DELETED',
            "Deleted system schedule #{$ssId}."
        );

        return ['success' => true, 'message' => 'Î¤Î¿ Ï€ÏÏŒÎ³ÏÎ±Î¼Î¼Î± Î´Î¹Î±Î³ÏÎ¬Ï†Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'];
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
            return ['success' => false, 'message' => 'Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎµ ÏŒÎ»Î± Ï„Î± Ï…Ï€Î¿Ï‡ÏÎµÏ‰Ï„Î¹ÎºÎ¬ Ï€ÎµÎ´Î¯Î±.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Î¤Î¿ email Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î­Î³ÎºÏ…ÏÎ¿.'];
        }

        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Î¥Ï€Î¬ÏÏ‡ÎµÎ¹ Î®Î´Î· Ï‡ÏÎ®ÏƒÏ„Î·Ï‚ Î¼Îµ Î±Ï…Ï„ÏŒ Ï„Î¿ email.'];
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
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ Î´Î·Î¼Î¹Î¿Ï…ÏÎ³Î¯Î±Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·.'];
        }

        $stmt->bind_param("sssssss", $name, $surname, $email, $hashedPassword, $phone, $role, $status);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î´Î·Î¼Î¹Î¿Ï…ÏÎ³Î¯Î±Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·.'];
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
            'message' => 'ÎŸ Ï‡ÏÎ®ÏƒÏ„Î·Ï‚ Î´Î·Î¼Î¹Î¿Ï…ÏÎ³Î®Î¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.',
            'user_id' => $newUserId,
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function updateUserByAdmin(int $userId, array $data, ?int $actorUserId = null): array
    {
        $existingUser = $this->getUserById($userId);
        if (!$existingUser) {
            return ['success' => false, 'message' => 'ÎŸ Ï‡ÏÎ®ÏƒÏ„Î·Ï‚ Î´ÎµÎ½ Î²ÏÎ­Î¸Î·ÎºÎµ.'];
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
            return ['success' => false, 'message' => 'Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎµ ÏŒÎ»Î± Ï„Î± Ï…Ï€Î¿Ï‡ÏÎµÏ‰Ï„Î¹ÎºÎ¬ Ï€ÎµÎ´Î¯Î±.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Î¤Î¿ email Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î­Î³ÎºÏ…ÏÎ¿.'];
        }

        if ($this->emailExists($email, $userId)) {
            return ['success' => false, 'message' => 'Î¥Ï€Î¬ÏÏ‡ÎµÎ¹ Î®Î´Î· Î¬Î»Î»Î¿Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·Ï‚ Î¼Îµ Î±Ï…Ï„ÏŒ Ï„Î¿ email.'];
        }

        if ($role === 'admin') {
            $status = 'active';
        }

        $shouldTriggerRejectionFlow = ($role === 'parent' && $status === 'rejected');
        if ($shouldTriggerRejectionFlow && $rejectionMessage === '') {
            return ['success' => false, 'message' => 'Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎµ Ï„Î¿ Î¼Î®Î½Ï…Î¼Î± Î±Ï€ÏŒÏÏÎ¹ÏˆÎ·Ï‚ Î³Î¹Î± Î½Î± ÏƒÏ„Î±Î»ÎµÎ¯ email ÏƒÏ„Î¿Î½ Î³Î¿Î½Î­Î±.'];
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
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ·Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·.'];
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
                throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ·Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·.');
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
                    'message' => "ÎŸ Ï‡ÏÎ®ÏƒÏ„Î·Ï‚ ÎµÎ³ÎºÏÎ¯Î¸Î·ÎºÎµ, Î´Î·Î¼Î¹Î¿Ï…ÏÎ³Î®Î¸Î·ÎºÎµ ÏƒÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Ï€Î»Î·ÏÏ‰Î¼Î®Ï‚ ÎºÎ±Î¹ ÏƒÏ„Î¬Î»Î¸Î·ÎºÎµ email ÎµÏ€Î¹Î²ÎµÎ²Î±Î¯Ï‰ÏƒÎ·Ï‚ ÏƒÏ„Î¿ {$email}.",
                    'approval_email_sent' => true,
                    'approval_email' => $email,
                    'subscription_link' => $approvalLink,
                ];
            }

            if ($shouldTriggerRejectionFlow) {
                return [
                    'success' => true,
                    'message' => "ÎŸ Ï‡ÏÎ®ÏƒÏ„Î·Ï‚ Î±Ï€Î¿ÏÏÎ¯Ï†Î¸Î·ÎºÎµ ÎºÎ±Î¹ ÏƒÏ„Î¬Î»Î¸Î·ÎºÎµ email ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ·Ï‚ ÏƒÏ„Î¿ {$email}.",
                    'rejection_email_sent' => true,
                    'rejection_email' => $email,
                ];
            }

            return ['success' => true, 'message' => 'ÎŸ Ï‡ÏÎ®ÏƒÏ„Î·Ï‚ ÎµÎ½Î·Î¼ÎµÏÏŽÎ¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'];
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
                    ? 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î­Î³ÎºÏÎ¹ÏƒÎ·Ï‚ Ï‡ÏÎ®ÏƒÏ„Î· ÎºÎ±Î¹ Î±Ï€Î¿ÏƒÏ„Î¿Î»Î®Ï‚ email: ' . $e->getMessage()
                    : 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ·Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·: ' . $e->getMessage(),
            ];
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteUserByAdmin(int $userId, ?int $actorUserId = null): array
    {
        $existingUser = $this->getUserById($userId);
        if (!$existingUser) {
            return ['success' => false, 'message' => 'ÎŸ Ï‡ÏÎ®ÏƒÏ„Î·Ï‚ Î´ÎµÎ½ Î²ÏÎ­Î¸Î·ÎºÎµ.'];
        }

        try {
            $this->conn->begin_transaction();

            $filesToDelete = $this->collectUserSubmissionFilePaths($userId);
            $this->deleteUserCommerceHistory($userId);

            $stmt = $this->conn->prepare("DELETE FROM Users WHERE user_id = ?");
            if (!$stmt) {
                throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ Î´Î¹Î±Î³ÏÎ±Ï†Î®Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·.');
            }

            $stmt->bind_param("i", $userId);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î´Î¹Î±Î³ÏÎ±Ï†Î®Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·.');
            }

            if ($stmt->affected_rows <= 0) {
                $stmt->close();
                throw new RuntimeException('Î”ÎµÎ½ Î´Î¹Î±Î³ÏÎ¬Ï†Î·ÎºÎµ ÎºÎ¬Ï€Î¿Î¹Î¿Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·Ï‚.');
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

        return ['success' => true, 'message' => 'ÎŸ Ï‡ÏÎ®ÏƒÏ„Î·Ï‚ Î´Î¹Î±Î³ÏÎ¬Ï†Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'];
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
            return ['success' => false, 'message' => 'ÎŸ Î³Î¿Î½Î­Î±Ï‚ Î´ÎµÎ½ Î²ÏÎ­Î¸Î·ÎºÎµ.'];
        }

        $name = trim((string)($data['name'] ?? ''));
        $surname = trim((string)($data['surname'] ?? ''));
        $dateOfBirth = trim((string)($data['date_of_birth'] ?? ''));
        $schoolClass = trim((string)($data['school_class'] ?? ''));

        if ($name === '' || $surname === '' || $dateOfBirth === '' || $schoolClass === '') {
            return ['success' => false, 'message' => 'Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎµ ÏŒÎ»Î± Ï„Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Ï„Î¿Ï… Ï€Î±Î¹Î´Î¹Î¿Ï.'];
        }

        if (!$this->isValidDate($dateOfBirth)) {
            return ['success' => false, 'message' => 'Î— Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î³Î­Î½Î½Î·ÏƒÎ·Ï‚ Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î­Î³ÎºÏ…ÏÎ·.'];
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO Children (user_id, name, surname, date_of_birth, school_class)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ Ï€ÏÎ¿ÏƒÎ¸Î®ÎºÎ·Ï‚ Ï€Î±Î¹Î´Î¹Î¿Ï.'];
        }

        $stmt->bind_param("issss", $parentUserId, $name, $surname, $dateOfBirth, $schoolClass);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÏƒÎ¸Î®ÎºÎ·Ï‚ Ï€Î±Î¹Î´Î¹Î¿Ï.'];
        }

        $newChildId = (int)$stmt->insert_id;
        $stmt->close();

        $this->syncUserChildrenCount($parentUserId);
        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_CHILD_CREATED',
            "Created child #{$newChildId} for parent #{$parentUserId}."
        );

        return ['success' => true, 'message' => 'Î¤Î¿ Ï€Î±Î¹Î´Î¯ Ï€ÏÎ¿ÏƒÏ„Î­Î¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function updateChildForParent(int $childId, int $parentUserId, array $data, ?int $actorUserId = null): array
    {
        $child = $this->getChildByIdForParent($childId, $parentUserId);
        if (!$child) {
            return ['success' => false, 'message' => 'Î¤Î¿ Ï€Î±Î¹Î´Î¯ Î´ÎµÎ½ Î²ÏÎ­Î¸Î·ÎºÎµ.'];
        }

        $name = trim((string)($data['name'] ?? ''));
        $surname = trim((string)($data['surname'] ?? ''));
        $dateOfBirth = trim((string)($data['date_of_birth'] ?? ''));
        $schoolClass = trim((string)($data['school_class'] ?? ''));

        if ($name === '' || $surname === '' || $dateOfBirth === '' || $schoolClass === '') {
            return ['success' => false, 'message' => 'Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎµ ÏŒÎ»Î± Ï„Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Ï„Î¿Ï… Ï€Î±Î¹Î´Î¹Î¿Ï.'];
        }

        if (!$this->isValidDate($dateOfBirth)) {
            return ['success' => false, 'message' => 'Î— Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î³Î­Î½Î½Î·ÏƒÎ·Ï‚ Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î­Î³ÎºÏ…ÏÎ·.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE Children
             SET name = ?, surname = ?, date_of_birth = ?, school_class = ?
             WHERE child_id = ? AND user_id = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ·Ï‚ Ï€Î±Î¹Î´Î¹Î¿Ï.'];
        }

        $stmt->bind_param("ssssii", $name, $surname, $dateOfBirth, $schoolClass, $childId, $parentUserId);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ·Ï‚ Ï€Î±Î¹Î´Î¹Î¿Ï.'];
        }

        $stmt->close();

        $this->syncUserChildrenCount($parentUserId);
        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_CHILD_UPDATED',
            "Updated child #{$childId} for parent #{$parentUserId}."
        );

        return ['success' => true, 'message' => 'Î¤Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Ï„Î¿Ï… Ï€Î±Î¹Î´Î¹Î¿Ï ÎµÎ½Î·Î¼ÎµÏÏŽÎ¸Î·ÎºÎ±Î½ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteChildForParent(int $childId, int $parentUserId, ?int $actorUserId = null): array
    {
        $child = $this->getChildByIdForParent($childId, $parentUserId);
        if (!$child) {
            return ['success' => false, 'message' => 'Î¤Î¿ Ï€Î±Î¹Î´Î¯ Î´ÎµÎ½ Î²ÏÎ­Î¸Î·ÎºÎµ.'];
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM Children
             WHERE child_id = ? AND user_id = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Ï€ÏÎ¿ÎµÏ„Î¿Î¹Î¼Î±ÏƒÎ¯Î±Ï‚ Î´Î¹Î±Î³ÏÎ±Ï†Î®Ï‚ Ï€Î±Î¹Î´Î¹Î¿Ï.'];
        }

        $stmt->bind_param("ii", $childId, $parentUserId);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î´Î¹Î±Î³ÏÎ±Ï†Î®Ï‚ Ï€Î±Î¹Î´Î¹Î¿Ï.'];
        }

        if ($stmt->affected_rows <= 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Î”ÎµÎ½ Î´Î¹Î±Î³ÏÎ¬Ï†Î·ÎºÎµ ÎºÎ¬Ï€Î¿Î¹Î¿ Ï€Î±Î¹Î´Î¯.'];
        }

        $stmt->close();

        $this->syncUserChildrenCount($parentUserId);
        $this->insertAdminLog(
            $actorUserId,
            'ADMIN_CHILD_DELETED',
            "Deleted child #{$childId} for parent #{$parentUserId}."
        );

        return ['success' => true, 'message' => 'Î¤Î¿ Ï€Î±Î¹Î´Î¯ Î´Î¹Î±Î³ÏÎ¬Ï†Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚.'];
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
            return ['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ· Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³Î¯Î± Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚.'];
        }

        $normalizedStart = trim($startDate);
        $normalizedEnd = trim($endDate);
        $normalizedStatus = in_array($status, ['active', 'inactive'], true) ? $status : 'inactive';

        if (!$this->isValidDateTime($normalizedStart) || !$this->isValidDateTime($normalizedEnd)) {
            return ['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎµÏ‚ Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯ÎµÏ‚ Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚ ÎµÎ³Î³ÏÎ±Ï†ÏŽÎ½.'];
        }

        $startTs = strtotime($normalizedStart);
        $endTs = strtotime($normalizedEnd);

        if ($startTs === false || $endTs === false) {
            return ['success' => false, 'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎµÏ‚ Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯ÎµÏ‚ Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚ ÎµÎ³Î³ÏÎ±Ï†ÏŽÎ½.'];
        }

        if ($this->isSingleMomentScheduleFeature($normalizedFeature) && $endTs <= $startTs) {
            $normalizedEnd = date('Y-m-d H:i:s', $startTs + 600);
            $endTs = strtotime($normalizedEnd);
        }

        if ($startTs > $endTs) {
            return ['success' => false, 'message' => 'Î— Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î­Î½Î±ÏÎ¾Î·Ï‚ Ï€ÏÎ­Ï€ÎµÎ¹ Î½Î± ÎµÎ¯Î½Î±Î¹ Ï€ÏÎ¹Î½ Î® Î¯Î´Î¹Î± Î¼Îµ Ï„Î·Î½ Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î»Î®Î¾Î·Ï‚.'];
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
        // Allow re-running to approval email/token flow gia goneas accounts
        // unless they are aldiavasmay fully active.
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
            throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î±Ï€Î¿ÏƒÏ„Î¿Î»Î®Ï‚ email Î­Î³ÎºÏÎ¹ÏƒÎ·Ï‚.' . $suffix);
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

        $subject = 'Î•Î½Î·Î¼Î­ÏÏ‰ÏƒÎ· Î³Î¹Î± Ï„Î·Î½ Î±Î¯Ï„Î·ÏƒÎ® ÏƒÎ±Ï‚';
        $message =
            "Î— Î±Î¯Ï„Î·ÏƒÎ® ÏƒÎ±Ï‚ Î±Ï€Î¿ÏÏÎ¯Ï†Î¸Î·ÎºÎµ Î±Ï€ÏŒ Ï„Î¿Î½ Î´Î¹Î±Ï‡ÎµÎ¹ÏÎ¹ÏƒÏ„Î®.\n\n" .
            "ÎœÎ®Î½Ï…Î¼Î± Î´Î¹Î±Ï‡ÎµÎ¹ÏÎ¹ÏƒÏ„Î®:\n" .
            $rejectionMessage . "\n\n" .
            "Î‘Î½ Ï‡ÏÎµÎ¹Î¬Î¶ÎµÏƒÏ„Îµ Î´Î¹ÎµÏ…ÎºÏÎ¹Î½Î¯ÏƒÎµÎ¹Ï‚, ÎµÏ€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î®ÏƒÏ„Îµ Î¼Îµ Ï„Î¿Î½ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿ Î“Î¿Î½Î­Ï‰Î½.";
        $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';

        if (!mail($email, $subject, $message, $headers)) {
            $suffix = $smtpFailureMessage !== '' ? ' SMTP: ' . $smtpFailureMessage : '';
            throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î±Ï€Î¿ÏƒÏ„Î¿Î»Î®Ï‚ email Î±Ï€ÏŒÏÏÎ¹ÏˆÎ·Ï‚.' . $suffix);
        }
    }
}
