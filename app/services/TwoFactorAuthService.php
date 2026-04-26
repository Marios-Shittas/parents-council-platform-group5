<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/ApprovalMailer.php';

class TwoFactorAuthService
{
    private mysqli $conn;
    private int $tokenExpirationMinutes = 10;
// Arxikopoiei to ypiresia me to koinoxristo MySQL connection pou orizetai sto bootstrap (global $conn).
    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }
// Elegxei email/token, apothikevei 2FA token + lixi ston pinaka Xristes, paragei 8-char kwdiko
// gia ton xristi kai ton apostellei me email meso ApprovalMailer. Epistrefei payload epityxias/apotyxias.
    public function send2FACode(string $email, string $name, string $token): array
    {
        $email = trim($email);
        $name = trim($name);
        $token = trim($token);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Μη έγκυρη διεύθυνση email.'];
        }

        if ($token === '') {
            return ['success' => false, 'message' => 'Μη έγκυρο διακριτικό για δημιουργία κωδικού 2FA.'];
        }

        $code = $this->derive8CharCodeFromToken($token);
        $expiresAt = date('Y-m-d H:i:s', time() + ($this->tokenExpirationMinutes * 60));

        $stmt = $this->conn->prepare('UPDATE Users SET token = ?, token_expiry = ? WHERE email = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Αδυναμία αποθήκευσης διακριτικού 2FA.'];
        }

        $stmt->bind_param('sss', $token, $expiresAt, $email);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Αδυναμία αποθήκευσης διακριτικού 2FA.'];
        }

        $stmt->close();

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

            $safeName = $name !== '' ? $name : 'Χρήστη';
            $body = "Γεια σας {$safeName},<br><br>Ο κωδικός ελέγχου ταυτότητας δύο παραγόντων είναι: <strong>{$code}</strong><br><br>Ο κωδικός λήγει σε {$this->tokenExpirationMinutes} λεπτά.";
            $mailer->sendHtmlEmail($email, 'Κωδικός Ελέγχου Ταυτότητας Δύο Παραγόντων', $body);

            return ['success' => true, 'message' => 'Ο κωδικός 2FA στάλθηκε επιτυχώς.'];
        } catch (Throwable $e) {
            error_log('2FA email send failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Αποτυχία αποστολής κωδικού 2FA.'];
        }
    }
// Fortonei token/lixi xristi, aporriptei missing i expired eggrafes, sygkrinei ton kwdiko xristi
// me ton paragogomeno kwdiko (xwris diafora pezon/kefalaiwn) kai katharizei ta 2FA dedomena meta to success.
    public function verify2FACode(string $email, string $code): array
    {
        $stmt = $this->conn->prepare('SELECT token, token_expiry FROM Users WHERE email = ? LIMIT 1');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Αδυναμία επαλήθευσης κωδικού 2FA.'];
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$user || empty($user['token']) || empty($user['token_expiry'])) {
            return ['success' => false, 'message' => 'Δεν βρέθηκε κωδικός 2FA.'];
        }

        if (strtotime((string) $user['token_expiry']) < time()) {
            $this->clear2FAData($email);
            return ['success' => false, 'message' => 'Ο κωδικός 2FA έχει λήξει.'];
        }

        $expectedCode = $this->derive8CharCodeFromToken((string) $user['token']);
        if (strtolower(trim($code)) !== strtolower($expectedCode)) {
            return ['success' => false, 'message' => 'Μη έγκυρος κωδικός 2FA.'];
        }

        $this->clear2FAData($email);
        return ['success' => true, 'message' => 'Η επαλήθευση 2FA ολοκληρώθηκε επιτυχώς.'];
    }
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function clear2FAData(string $email): void
    {
        $stmt = $this->conn->prepare('UPDATE Users SET token = NULL, token_expiry = NULL WHERE email = ?');
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }
// Dimiourgei me stathero tropo 8-char kwdiko apo to token: pairnei to index 0,
// meta kathe 4o xarakthra kai telos symplirwnei tis theseis se seira.
    private function derive8CharCodeFromToken(string $token): string
    {
        $token = trim($token);

        if ($token === '') {
            return '';
        }

        $chars = str_split($token);
        $length = count($chars);

        $picked = [];
        $pickedIndexes = [];

        $picked[] = $chars[0];
        $pickedIndexes[0] = true;

        for ($i = 4; $i < $length && count($picked) < 8; $i += 4) {
            $picked[] = $chars[$i];
            $pickedIndexes[$i] = true;
        }

        for ($i = 1; $i < $length && count($picked) < 8; $i++) {
            if (!isset($pickedIndexes[$i])) {
                $picked[] = $chars[$i];
            }
        }

        return substr(implode('', $picked), 0, 8);
    }
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    $service = new TwoFactorAuthService();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $data = json_decode(file_get_contents('php://input'), true);

    if ($method === 'POST') {
        if (!isset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['temp_token'], $_SESSION['pending_2fa'])) {
            echo json_encode(['success' => false, 'message' => 'Απαιτείται σύνδεση πριν από το 2FA.']);
            exit;
        }

        $email = (string) $_SESSION['temp_email'];
        $name = trim((string) ($_SESSION['temp_name'] ?? 'User'));
        $token = (string) $_SESSION['temp_token'];

        echo json_encode($service->send2FACode($email, $name, $token));
        exit;
    }

    if ($method === 'PUT') {
        $code = trim((string) ($data['code'] ?? ''));

        if ($code === '') {
            echo json_encode(['success' => false, 'message' => 'Ο κωδικός είναι υποχρεωτικός.']);
            exit;
        }

        if (!isset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['pending_2fa'])) {
            echo json_encode(['success' => false, 'message' => 'Η συνεδρία 2FA δεν βρέθηκε.']);
            exit;
        }

        $email = (string) $_SESSION['temp_email'];
        $result = $service->verify2FACode($email, $code);

        if ($result['success']) {
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['email'] = $_SESSION['temp_email'];
            $_SESSION['role'] = $_SESSION['temp_role'];
            $_SESSION['2fa_verified'] = true;

            unset($_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['temp_name'], $_SESSION['temp_token'], $_SESSION['pending_2fa']);
        }

        echo json_encode($result);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Η μέθοδος δεν επιτρέπεται.']);
}
