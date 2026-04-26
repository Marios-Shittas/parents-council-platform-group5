<?php
// Arxeio: app\controllers\SubscriptionPageController.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.

require_once __DIR__ . '/../includes/TokenValidator.php';

class SubscriptionPageController
{
    private mysqli $conn;
    private TokenValidator $tokenValidator;
    private string $tokenMessage = 'ÎŸ ÏƒÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î´ÎµÎ½ ÎµÎ¯Î½Î±Î¹ Î­Î³ÎºÏ…ÏÎ¿Ï‚ Î® Î­Ï‡ÎµÎ¹ Î»Î®Î¾ÎµÎ¹.';
// Kanei inject to DB connection kai arxikopoiei to TokenValidator dependency gia elegxous token syndromis.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->tokenValidator = new TokenValidator($conn);
    }
// Ftiaxnei olo to provoli payload tis selidas syndromis, me raw token,
// JSON-safe token, validity flag kai minima gia mi-egkyro token.
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
// Elegxei oti to token antistoixei se parent account me status waiting_payment
// kai oti to token den exei lixeis.
    private function isTokenValid(string $token): bool
    {
        return $this->tokenValidator->isTokenValid($token, 'parent', 'waiting_payment', true);
    }
}
