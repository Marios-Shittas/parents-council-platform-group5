<?php
// Arxeio: app\includes\TokenValidator.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
declare(strict_types=1);

class TokenValidator
{
    private mysqli $conn;
// Kanei inject kai apothikevei to active mysqli connection gia ola ta token validation queries.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }
// Boolean convenience validator pou epistrefei true otan to getUserIdByToken vrei matching xristis.
// Ypostirizei optional role/status filters kai optional enforcement tis lixis.
    public function isTokenValid(
        string $token,
        ?string $role = null,
        ?string $accountStatus = null,
        bool $requireNotExpired = true
    ): bool {
        return $this->getUserIdByToken($token, $role, $accountStatus, $requireNotExpired) !== null;
    }
// Vasikos token resolver pou epistrefei user_id me optional role/status checks
// kai expiry constraints. Episis kanei auto-reset expired waiting_payment parents otan xreiazetai.
    public function getUserIdByToken(
        string $token,
        ?string $role = null,
        ?string $accountStatus = null,
        bool $requireNotExpired = true
    ): ?int {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        if ($requireNotExpired && $role === 'parent' && $accountStatus === 'waiting_payment') {
            $this->resetAllExpiredWaitingPaymentUsersToPending();
        }

        $sql = 'SELECT user_id FROM Users WHERE token = ?';
        $types = 's';
        $params = [$token];

        if ($role !== null) {
            $sql .= ' AND role = ?';
            $types .= 's';
            $params[] = $role;
        }

        if ($accountStatus !== null) {
            $sql .= ' AND account_status = ?';
            $types .= 's';
            $params[] = $accountStatus;
        }

        if ($requireNotExpired) {
            $sql .= ' AND token_expiry IS NOT NULL AND token_expiry >= NOW()';
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Failed to validate token.');
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ? (int) $row['user_id'] : null;
    }
// Maintenance helper pou metatrepei parent xristes apo waiting_payment se pending
// otan exei perasei to token_expiry kai katharizei token fields gia na min meinei stale auth.
    public function resetAllExpiredWaitingPaymentUsersToPending(): int
    {
        $stmt = $this->conn->prepare(
            "UPDATE Users
             SET account_status = 'pending', token = NULL, token_expiry = NULL
             WHERE role = 'parent'
               AND account_status = 'waiting_payment'
               AND token_expiry IS NOT NULL
               AND token_expiry < NOW()"
        );

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare expired token reset.');
        }

        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return max(0, $affectedRows);
    }
}
