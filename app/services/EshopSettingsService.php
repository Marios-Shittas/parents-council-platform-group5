<?php
// Arxeio: app\services\EshopSettingsService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora agora/paraggelies, ara ta data prepei na menoun synced me cart/orders services.

require_once __DIR__ . '/../includes/db.php';

class EshopSettingsService
{
    private mysqli $conn;
// Arxikopoiei to ypiresia me provided mysqli connection i kanei fallback sto global app connection.
    public function __construct(?mysqli $conn = null)
    {
        if ($conn instanceof mysqli) {
            $this->conn = $conn;
            return;
        }

        global $conn;
        $this->conn = $conn;
    }
// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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
// Apothikevei to visibility toggle tou shop kai enimerwnei timestamp gia admin auditability.
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
// Dimiourgei ton EshopSettings pinakas an leipei kai engyatai oti yparxei singleton row (setting_id=1).
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
