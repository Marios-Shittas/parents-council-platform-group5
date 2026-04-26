<?php
// Arxeio: app\services\ResetPasswordService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
declare(strict_types=1);

require_once __DIR__ . '/UsersService.php';
require_once __DIR__ . '/../config/db.php';

class ResetPasswordService {
    private $usersService;
    private $db;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct() {
        $this->usersService = new UsersService();
        global $conn;
        $this->db = $conn;
    }

    /**
     * Elegxei a password reset token
     * @param string $token - To reset token
     * @param string $email - To xristis's email
     * @return bool - True an token is valid kai not expired
     */
    public function validatePasswordResetToken($token, $email) {
        $token = trim($token);
        
        if ($token === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT user_id FROM Users 
            WHERE email = ? AND token = ? AND token_expiry IS NOT NULL AND token_expiry >= NOW()
            LIMIT 1
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $hasRow = $result->num_rows > 0;
        $stmt->close();
        
        return $hasRow;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function handleRequest() {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $token = $data['token'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        // Elegxei token first
        if (!$this->validatePasswordResetToken($token, $email)) {
            return [
                'success' => false,
                'message' => 'Μη έγκυρο ή ληγμένο διακριτικό επαναφοράς'
            ];
        }

        // Reset to password
        $result = $this->usersService->resetPassword($email, $newPassword);

        // Clear to token an reset was successful
        if ($result['success']) {
            $stmt = $this->db->prepare("UPDATE Users SET token = NULL, token_expiry = NULL WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();
        }

        return $result;
    }

    
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = new ResetPasswordService();
    $result = $service->handleRequest();
    echo json_encode($result);
}
?>
