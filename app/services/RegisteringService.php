<?php
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
                'message' => 'Μη έγκυρα δεδομένα αιτήματος.',
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
                'message' => 'Το email χρησιμοποιείται ήδη.',
            ]);
            return;
        }

        $name = trim((string) $payload['first_name']);
        $surname = trim((string) $payload['last_name']);
        $phone = $this->normalizePhone((string) $payload['phone']) ?? '';
        if ($this->phoneExists($phone)) {
            $this->respond(409, [
                'success' => false,
                'message' => 'Το τηλέφωνο χρησιμοποιείται ήδη.',
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
                'message' => 'Η εγγραφή σας καταχωρήθηκε επιτυχώς. Θα ενημερωθείτε μέσω email όταν εγκριθεί από τον διαχειριστή.',
            ]);
        } catch (Throwable $e) {
            $this->conn->rollback();

            $this->respond(500, [
                'success' => false,
                'message' => 'Σφάλμα: ' . $e->getMessage(),
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
                return 'Λείπουν απαραίτητα πεδία από τη φόρμα.';
            }
        }

        if (!filter_var((string) $payload['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Το email δεν είναι έγκυρο.';
        }

        if ($this->normalizePhone((string) $payload['phone']) === null) {
            return 'Το κινητό πρέπει να δηλωθεί στη μορφή +357 και 8ψήφιος αριθμός.';
        }

        if (filter_var($payload['consent'] ?? false, FILTER_VALIDATE_BOOLEAN) !== true) {
            return 'Πρέπει να αποδεχτείτε την πολιτική απορρήτου.';
        }

        if (filter_var($payload['viber_consent'] ?? false, FILTER_VALIDATE_BOOLEAN) !== true) {
            return 'Πρέπει να αποδεχτείτε και τη συμμετοχή στην ομάδα Viber για να ολοκληρωθεί η εγγραφή.';
        }

        if (!is_array($payload['children']) || count($payload['children']) === 0) {
            return 'Πρέπει να καταχωρηθεί τουλάχιστον ένα παιδί.';
        }

        foreach ($payload['children'] as $child) {
            if (!is_array($child)) {
                return 'Τα στοιχεία παιδιού δεν είναι έγκυρα.';
            }

            $childFields = ['child_name', 'child_last_name', 'child_dob', 'child_class'];
            foreach ($childFields as $field) {
                if (empty(trim((string) ($child[$field] ?? '')))) {
                    return 'Συμπληρώστε όλα τα στοιχεία για κάθε παιδί.';
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
            throw new RuntimeException('Αποτυχία ελέγχου email.');
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
            throw new RuntimeException('Αποτυχία ελέγχου τηλεφώνου.');
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
            throw new RuntimeException('Αποτυχία καταχώρησης χρήστη.');
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
            throw new RuntimeException('Αποτυχία καταχώρησης παιδιών.');
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
            throw new RuntimeException('Αποτυχία καταγραφής log εγγραφής.');
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
            throw new RuntimeException('Αποτυχία συγχρονισμού αριθμού παιδιών.');
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
            'message' => 'Οι εγγραφές είναι κλειστές. Διαθέσιμες περίοδοι: ' . implode(' | ', $activePeriods),
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
