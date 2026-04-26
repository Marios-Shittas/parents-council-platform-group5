<?php
// Arxeio: app\services\RegisteringService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

class RegisteringService
{
    private mysqli $conn;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function handleRequest(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, [
                'success' => false,
                'message' => 'Method not allowed',
            ]);
            return;
        }

        $registrationWindowState = $this->getRegistrationWindowState();
        if (!$registrationWindowState['is_open']) {
            $this->respond(403, [
                'success' => false,
                'message' => $registrationWindowState['message'],
            ]);
            return;
        }

        $payload = $this->getRequestPayload();
        if ($payload === null) {
            $this->respond(400, [
                'success' => false,
                'message' => 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ± Î´ÎµÎ´Î¿Î¼Î­Î½Î± Î±Î¹Ï„Î®Î¼Î±Ï„Î¿Ï‚.',
            ]);
            return;
        }

        $validationError = $this->validatePayload($payload);
        if ($validationError !== null) {
            $this->respond(422, [
                'success' => false,
                'message' => $validationError,
            ]);
            return;
        }

        $email = trim((string) $payload['email']);
        if ($this->emailExists($email)) {
            $this->respond(409, [
                'success' => false,
                'message' => 'Î¤Î¿ email Ï‡ÏÎ·ÏƒÎ¹Î¼Î¿Ï€Î¿Î¹ÎµÎ¯Ï„Î±Î¹ Î®Î´Î·.',
            ]);
            return;
        }

        $name = trim((string) $payload['first_name']);
        $surname = trim((string) $payload['last_name']);
        $phone = $this->normalizePhone((string) $payload['phone']) ?? '';
        if ($this->phoneExists($phone)) {
            $this->respond(409, [
                'success' => false,
                'message' => 'Î¤Î¿ Ï„Î·Î»Î­Ï†Ï‰Î½Î¿ Ï‡ÏÎ·ÏƒÎ¹Î¼Î¿Ï€Î¿Î¹ÎµÎ¯Ï„Î±Î¹ Î®Î´Î·.',
            ]);
            return;
        }
        $children = $payload['children'];
        $childrenCount = count($children);

        try {
            $this->conn->begin_transaction();

            $userId = $this->insertUser($name, $surname, $email, $phone, $childrenCount);
            $this->insertChildren($userId, $children);
            $this->syncUserChildrenCount($userId);
            $this->insertRegistrationLog($userId, $email);

            $this->conn->commit();

            $this->respond(200, [
                'success' => true,
                'message' => 'Î— ÎµÎ³Î³ÏÎ±Ï†Î® ÏƒÎ±Ï‚ ÎºÎ±Ï„Î±Ï‡Ï‰ÏÎ®Î¸Î·ÎºÎµ ÎµÏ€Î¹Ï„Ï…Ï‡ÏŽÏ‚. Î˜Î± ÎµÎ½Î·Î¼ÎµÏÏ‰Î¸ÎµÎ¯Ï„Îµ Î¼Î­ÏƒÏ‰ email ÏŒÏ„Î±Î½ ÎµÎ³ÎºÏÎ¹Î¸ÎµÎ¯ Î±Ï€ÏŒ Ï„Î¿Î½ Î´Î¹Î±Ï‡ÎµÎ¹ÏÎ¹ÏƒÏ„Î®.',
            ]);
        } catch (Throwable $e) {
            $this->conn->rollback();

            $this->respond(500, [
                'success' => false,
                'message' => 'Î£Ï†Î¬Î»Î¼Î±: ' . $e->getMessage(),
            ]);
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getRequestPayload(): ?array
    {
        $rawBody = file_get_contents('php://input');
        if ($rawBody === false || $rawBody === '') {
            return null;
        }

        $decoded = json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function validatePayload(array $payload): ?string
    {
        $requiredFields = ['first_name', 'last_name', 'email', 'phone', 'children'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload)) {
                return 'Î›ÎµÎ¯Ï€Î¿Ï…Î½ Î±Ï€Î±ÏÎ±Î¯Ï„Î·Ï„Î± Ï€ÎµÎ´Î¯Î± Î±Ï€ÏŒ Ï„Î· Ï†ÏŒÏÎ¼Î±.';
            }
        }

        if (!filter_var((string) $payload['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Î¤Î¿ email Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î­Î³ÎºÏ…ÏÎ¿.';
        }

        if ($this->normalizePhone((string) $payload['phone']) === null) {
            return 'Î¤Î¿ ÎºÎ¹Î½Î·Ï„ÏŒ Ï€ÏÎ­Ï€ÎµÎ¹ Î½Î± Î´Î·Î»Ï‰Î¸ÎµÎ¯ ÏƒÏ„Î· Î¼Î¿ÏÏ†Î® +357 ÎºÎ±Î¹ 8ÏˆÎ®Ï†Î¹Î¿Ï‚ Î±ÏÎ¹Î¸Î¼ÏŒÏ‚.';
        }

        if (filter_var($payload['consent'] ?? false, FILTER_VALIDATE_BOOLEAN) !== true) {
            return 'Î ÏÎ­Ï€ÎµÎ¹ Î½Î± Î±Ï€Î¿Î´ÎµÏ‡Ï„ÎµÎ¯Ï„Îµ Ï„Î·Î½ Ï€Î¿Î»Î¹Ï„Î¹ÎºÎ® Î±Ï€Î¿ÏÏÎ®Ï„Î¿Ï….';
        }

        if (filter_var($payload['viber_consent'] ?? false, FILTER_VALIDATE_BOOLEAN) !== true) {
            return 'Î ÏÎ­Ï€ÎµÎ¹ Î½Î± Î±Ï€Î¿Î´ÎµÏ‡Ï„ÎµÎ¯Ï„Îµ ÎºÎ±Î¹ Ï„Î· ÏƒÏ…Î¼Î¼ÎµÏ„Î¿Ï‡Î® ÏƒÏ„Î·Î½ Î¿Î¼Î¬Î´Î± Viber Î³Î¹Î± Î½Î± Î¿Î»Î¿ÎºÎ»Î·ÏÏ‰Î¸ÎµÎ¯ Î· ÎµÎ³Î³ÏÎ±Ï†Î®.';
        }

        if (!is_array($payload['children']) || count($payload['children']) === 0) {
            return 'Î ÏÎ­Ï€ÎµÎ¹ Î½Î± ÎºÎ±Ï„Î±Ï‡Ï‰ÏÎ·Î¸ÎµÎ¯ Ï„Î¿Ï…Î»Î¬Ï‡Î¹ÏƒÏ„Î¿Î½ Î­Î½Î± Ï€Î±Î¹Î´Î¯.';
        }

        foreach ($payload['children'] as $child) {
            if (!is_array($child)) {
                return 'Î¤Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Ï€Î±Î¹Î´Î¹Î¿Ï Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î­Î³ÎºÏ…ÏÎ±.';
            }

            $childFields = ['child_name', 'child_last_name', 'child_dob', 'child_class'];
            foreach ($childFields as $field) {
                if (empty(trim((string) ($child[$field] ?? '')))) {
                    return 'Î£Ï…Î¼Ï€Î»Î·ÏÏŽÏƒÏ„Îµ ÏŒÎ»Î± Ï„Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Î³Î¹Î± ÎºÎ¬Î¸Îµ Ï€Î±Î¹Î´Î¯.';
                }
            }
        }

        return null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function normalizePhone(string $phone): ?string
    {
        $normalizedPhone = preg_replace('/[\s\-]+/', '', trim($phone));
        if (!is_string($normalizedPhone)) {
            $normalizedPhone = trim($phone);
        }

        return preg_match('/^\+357\d{8}$/', $normalizedPhone) === 1 ? $normalizedPhone : null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function emailExists(string $email): bool
    {
        $check = $this->conn->prepare('SELECT user_id FROM Users WHERE email = ?');
        if ($check === false) {
            throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎµÎ»Î­Î³Ï‡Î¿Ï… email.');
        }

        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();

        return $exists;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function phoneExists(string $phone): bool
    {
        $check = $this->conn->prepare('SELECT user_id FROM Users WHERE phone_number = ?');
        if ($check === false) {
            throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎµÎ»Î­Î³Ï‡Î¿Ï… Ï„Î·Î»ÎµÏ†ÏŽÎ½Î¿Ï….');
        }

        $check->bind_param('s', $phone);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();

        return $exists;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function insertUser(string $name, string $surname, string $email, string $phone, int $childrenCount): int
    {
        $placeholderPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

        $stmtUser = $this->conn->prepare(
            "INSERT INTO Users (name, surname, email, password, phone_number, number_of_children, role, account_status)
             VALUES (?, ?, ?, ?, ?, ?, 'parent', 'pending')"
        );

        if ($stmtUser === false) {
            throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎºÎ±Ï„Î±Ï‡ÏŽÏÎ·ÏƒÎ·Ï‚ Ï‡ÏÎ®ÏƒÏ„Î·.');
        }

        $stmtUser->bind_param('sssssi', $name, $surname, $email, $placeholderPassword, $phone, $childrenCount);
        $stmtUser->execute();
        $userId = (int) $this->conn->insert_id;
        $stmtUser->close();

        return $userId;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function insertChildren(int $userId, array $children): void
    {
        $stmtChild = $this->conn->prepare(
            'INSERT INTO Children (user_id, name, surname, date_of_birth, school_class) VALUES (?, ?, ?, ?, ?)'
        );

        if ($stmtChild === false) {
            throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎºÎ±Ï„Î±Ï‡ÏŽÏÎ·ÏƒÎ·Ï‚ Ï€Î±Î¹Î´Î¹ÏŽÎ½.');
        }

        foreach ($children as $child) {
            $childName = trim((string) $child['child_name']);
            $childSurname = trim((string) $child['child_last_name']);
            $childDob = (string) $child['child_dob'];
            $childClass = trim((string) $child['child_class']);

            $stmtChild->bind_param('issss', $userId, $childName, $childSurname, $childDob, $childClass);
            $stmtChild->execute();
        }

        $stmtChild->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function insertRegistrationLog(int $userId, string $email): void
    {
        $action = 'user_registration';
        $description = "New parent registered with email: {$email}";

        $stmtLog = $this->conn->prepare(
            'INSERT INTO Logs (user_id, action, description) VALUES (?, ?, ?)'
        );

        if ($stmtLog === false) {
            throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÎºÎ±Ï„Î±Î³ÏÎ±Ï†Î®Ï‚ log ÎµÎ³Î³ÏÎ±Ï†Î®Ï‚.');
        }

        $stmtLog->bind_param('iss', $userId, $action, $description);
        $stmtLog->execute();
        $stmtLog->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function syncUserChildrenCount(int $userId): void
    {
        $stmt = $this->conn->prepare(
            'UPDATE Users
             SET number_of_children = (SELECT COUNT(*) FROM Children WHERE user_id = ?)
             WHERE user_id = ?'
        );

        if ($stmt === false) {
            throw new RuntimeException('Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± ÏƒÏ…Î³Ï‡ÏÎ¿Î½Î¹ÏƒÎ¼Î¿Ï Î±ÏÎ¹Î¸Î¼Î¿Ï Ï€Î±Î¹Î´Î¹ÏŽÎ½.');
        }

        $stmt->bind_param('ii', $userId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getRegistrationWindowState(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT start_date, end_date, ss_status
             FROM SystemSchedule
             WHERE feature = 'registration'
             ORDER BY start_date ASC, ss_id ASC"
        );

        if ($stmt === false) {
            return [
                'is_open' => true,
                'message' => '',
            ];
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $schedules = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        if (empty($schedules)) {
            return [
                'is_open' => true,
                'message' => '',
            ];
        }
        $now = time();

        $activePeriods = [];
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

            $activePeriods[] = date('d/m/Y H:i', $start) . ' - ' . date('d/m/Y H:i', $end);

            if ($now >= $start && $now <= $end) {
                return [
                    'is_open' => true,
                    'message' => '',
                ];
            }
        }

        if (empty($activePeriods)) {
            // Otan den yparxoun energa grammes programmatismou, i eggrafi paramenei anoikti.
            return [
                'is_open' => true,
                'message' => '',
            ];
        }

        return [
            'is_open' => false,
            'message' => 'ÎŸÎ¹ ÎµÎ³Î³ÏÎ±Ï†Î­Ï‚ ÎµÎ¯Î½Î±Î¹ ÎºÎ»ÎµÎ¹ÏƒÏ„Î­Ï‚. Î”Î¹Î±Î¸Î­ÏƒÎ¹Î¼ÎµÏ‚ Ï€ÎµÏÎ¯Î¿Î´Î¿Î¹: ' . implode(' | ', $activePeriods),
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function respond(int $statusCode, array $body): void
    {
        http_response_code($statusCode);
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
    }
}

$service = new RegisteringService($conn);
$service->handleRequest();
$conn->close();
