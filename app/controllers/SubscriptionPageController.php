<?php

require_once __DIR__ . '/../includes/TokenValidator.php';

class SubscriptionPageController
{
    private mysqli $conn;
    private TokenValidator $tokenValidator;
    private string $tokenMessage = 'Ο σύνδεσμος δεν είναι έγκυρος ή έχει λήξει.';

    // Pairnei DB connection kai etoimazei ton token validator.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->tokenValidator = new TokenValidator($conn);
    }

    // Ftiaxnei ta view data gia ti selida syndromis.
    public function viewData(): array
    {
        $token = trim((string) ($_GET['token'] ?? ''));

        return [
            'token' => $token,
            'token_json' => json_encode($token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_valid_token' => $this->isTokenValid($token),
            'token_message' => $this->tokenMessage,
        ];
    }

    // Elegxei an to token borei na plirosei tin syndromi tou parent.
    private function isTokenValid(string $token): bool
    {
        return $this->tokenValidator->isTokenValid($token, 'parent', 'waiting_payment', true);
    }
}
