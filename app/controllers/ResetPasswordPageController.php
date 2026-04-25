<?php

class ResetPasswordPageController
{
    private mysqli $conn;

    // Krataei DB connection gia ton elegxo tou reset token.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    // Ftiaxnei ola ta dedomena pou xreiazetai to reset password view.
    public function viewData(): array
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $email = trim((string) ($_GET['email'] ?? ''));

        return [
            'token' => $token,
            'email' => $email,
            'token_json' => json_encode($token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'email_json' => json_encode($email, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_valid_token' => $this->isTokenValid($email, $token),
            'token_message' => 'Ο σύνδεσμος δεν είναι έγκυρος ή έχει λήξει.',
            'service_url' => rtrim(APP_BASE_URL, '/') . '/app/services/ResetPasswordService.php',
        ];
    }

    // Elegxei sto database an to reset token einai akoma egkyro.
    private function isTokenValid(string $email, string $token): bool
    {
        if ($token === '' || $email === '') {
            return false;
        }

        $stmt = $this->conn->prepare("
            SELECT user_id FROM Users
            WHERE email = ? AND token = ? AND token_expiry IS NOT NULL AND token_expiry >= NOW()
            LIMIT 1
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $email, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $isValid = $result->num_rows > 0;
        $stmt->close();

        return $isValid;
    }
}
