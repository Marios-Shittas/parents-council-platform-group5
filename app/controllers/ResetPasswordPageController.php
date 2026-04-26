<?php

class ResetPasswordPageController
{
    private mysqli $conn;
// Apothikevei to DB connection dependency pou xrisimopoieitai gia elegxo ownership/lixis reset token.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }
// Ftiaxnei olokliromeno provoli-model payload gia to reset page: raw values, JSON-safe values,
// token validity flag, proepilegmeno minima mi-egkyrou token kai ypiresia endpoint URL.
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
// Epivevaionei to reset token ston pinaka Xristes me email+token kai apaitei mi-ligmeno token_expiry.
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
