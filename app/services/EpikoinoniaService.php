<?php
// Arxeio: app\services\EpikoinoniaService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
/**
 * EpikoinoniaService - Xeirizetai ola contact forma messages vasi operations
 */

require_once __DIR__ . '/../config/db.php';

class EpikoinoniaService {
    private $conn;
    private static $tableChecked = false;
    
    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->ensureContactMessagesTable();
    }

    /**
     * Ensure the contact_messages pinakas exists
     */
    private function ensureContactMessagesTable() {
        if (self::$tableChecked) {
            return;
        }

        $result = $this->conn->query("SHOW TABLES LIKE 'contact_messages'");
        if ($result && $result->num_rows === 0) {
            $createTableSQL = "
                CREATE TABLE contact_messages (
                    message_id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    message LONGTEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_read BOOLEAN DEFAULT FALSE,
                    INDEX idx_created_at (created_at),
                    INDEX idx_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->conn->query($createTableSQL);
        }

        self::$tableChecked = true;
    }
    
    /**
     * Pairnei ola contact messages me pagination
     * @param int $limit - Messages per page
     * @param int $offset - Pagination offset
     * @return array - Pinakas me contact messages
     */
    public function getAllMessages($limit = 10, $offset = 0) {
        $sql = "SELECT * FROM contact_messages 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        return $messages;
    }
    
    /**
     * Pairnei synoliko metrisis gia messages
     * @return int - Synoliko message count
     */
    public function getMessageCount() {
        $sql = "SELECT COUNT(*) as count FROM contact_messages";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * Pairnei undiavasma message count
     * @return int - Undiavasma message count
     */
    public function getUnreadMessageCount() {
        $sql = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = FALSE";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * Pairnei mia message apo ID
     * @param int $id - Message ID
     * @return array|null - Message dedomena i null an den vrethei
     */
    public function getMessageById($id) {
        $sql = "SELECT * FROM contact_messages WHERE message_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row;
        }
        
        return null;
    }
    
    /**
     * Dimiourgei neo minima epikoinonias
     * @param string $name Onoma apostolea
     * @param string $email Email apostolea
     * @param string $phone Tilefono apostolea
     * @param string $subject Thema minimatos
     * @param string $message Periexomeno minimatos
     * @return int|false To neo ID minimatos i false se apotyxia
     */
    public function createMessage($name, $email, $phone, $subject, $message) {
        // Afairei kena stin arxi kai sto telos (mazi me allages grammis)
        $name = preg_replace('/^\s+|\s+$/u', '', $name);
        $email = preg_replace('/^\s+|\s+$/u', '', $email);
        $phone = preg_replace('/^\s+|\s+$/u', '', $phone);
        $subject = preg_replace('/^\s+|\s+$/u', '', $subject);
        $message = preg_replace('/^\s+|\s+$/u', '', $message);
        
        $sql = "INSERT INTO contact_messages (name, email, phone, subject, message) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Mark a message as diavasma
     * @param int $id - Message ID
     * @return bool - Success status
     */
    public function markAsRead($id) {
        $sql = "UPDATE contact_messages SET is_read = TRUE WHERE message_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Diagrafei a message
     * @param int $id - Message ID
     * @return bool - Success status
     */
    public function deleteMessage($id) {
        $sql = "DELETE FROM contact_messages WHERE message_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Psaxnei messages apo email i name
     * @param string $searchTerm - Psaxnei term
     * @return array - Pinakas me tairiazouses messages
     */
    public function searchMessages($searchTerm) {
        $searchTerm = '%' . $searchTerm . '%';
        $sql = "SELECT * FROM contact_messages 
                WHERE name LIKE ? OR email LIKE ? OR subject LIKE ?
                ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        return $messages;
    }
}
?>
