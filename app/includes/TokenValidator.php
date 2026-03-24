<?php
declare(strict_types=1);

class TokenValidator
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function isTokenValid(
        string $token,
        ?string $role = null,
        ?string $accountStatus = null,
        bool $requireNotExpired = true
    ): bool {
        return $this->getUserIdByToken($token, $role, $accountStatus, $requireNotExpired) !== null;
    }

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
}
