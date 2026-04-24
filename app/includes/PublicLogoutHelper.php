<?php

final class PublicLogoutHelper
{
    /**
     * Clears persistent login token data for the current user on logout.
     */
    public static function clearUserTokenOnLogout(mysqli $conn, ?int $userId, ?string $email): void
    {
        if ($userId !== null && $userId > 0) {
            $stmt = $conn->prepare('UPDATE Users SET token = NULL, token_expiry = NULL WHERE user_id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $stmt->close();
            }
            return;
        }

        $normalizedEmail = trim((string)$email);
        if ($normalizedEmail !== '') {
            $stmt = $conn->prepare('UPDATE Users SET token = NULL, token_expiry = NULL WHERE email = ?');
            if ($stmt) {
                $stmt->bind_param('s', $normalizedEmail);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
