<?php

require_once __DIR__ . '/../includes/db.php';

class EshopSettingsService
{
    private mysqli $conn;

    public function __construct(?mysqli $conn = null)
    {
        if ($conn instanceof mysqli) {
            $this->conn = $conn;
            return;
        }

        global $conn;
        $this->conn = $conn;
    }

    public function isShopVisible(): bool
    {
        $this->ensureSettingsTable();

        $result = $this->conn->query("
            SELECT is_visible
            FROM EshopSettings
            WHERE setting_id = 1
            LIMIT 1
        ");

        if ($result && ($row = $result->fetch_assoc())) {
            return ((int) ($row['is_visible'] ?? 1)) === 1;
        }

        return true;
    }

    public function setShopVisibility(bool $isVisible): bool
    {
        $this->ensureSettingsTable();

        $value = $isVisible ? 1 : 0;
        $stmt = $this->conn->prepare("
            UPDATE EshopSettings
            SET is_visible = ?, updated_at = CURRENT_TIMESTAMP
            WHERE setting_id = 1
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $value);
        $updated = $stmt->execute();
        $stmt->close();

        return $updated;
    }

    private function ensureSettingsTable(): void
    {
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS EshopSettings (
                setting_id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
                is_visible TINYINT(1) NOT NULL DEFAULT 1,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query("
            INSERT INTO EshopSettings (setting_id, is_visible)
            VALUES (1, 1)
            ON DUPLICATE KEY UPDATE setting_id = setting_id
        ");
    }
}
