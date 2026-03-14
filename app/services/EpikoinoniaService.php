<?php
/**
 * EpikoinoniaService - Handles all contact form messages database operations
 */

require_once __DIR__ . '/../config/db.php';

class EpikoinoniaService {
    private $conn;
    private static $tableChecked = false;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->ensureContactMessagesTable();
    }

    /**
     * Ensure the contact_messages table exists
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
     * Get all contact messages with pagination
     * @param int $limit Messages per page
     * @param int $offset Pagination offset
     * @return array Array of contact messages
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
     * Get total count of messages
     * @return int Total message count
     */
    public function getMessageCount() {
        $sql = "SELECT COUNT(*) as count FROM contact_messages";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * Get unread message count
     * @return int Unread message count
     */
    public function getUnreadMessageCount() {
        $sql = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = FALSE";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * Get a single message by ID
     * @param int $id Message ID
     * @return array|null Message data or null if not found
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
     * Create a new contact message
     * @param string $name Sender name
     * @param string $email Sender email
     * @param string $phone Sender phone
     * @param string $subject Message subject
     * @param string $message Message content
     * @return int|false The new message ID or false on failure
     */
    public function createMessage($name, $email, $phone, $subject, $message) {
        // Remove all leading and trailing whitespace (including newlines)
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
     * Mark a message as read
     * @param int $id Message ID
     * @return bool Success status
     */
    public function markAsRead($id) {
        $sql = "UPDATE contact_messages SET is_read = TRUE WHERE message_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a message
     * @param int $id Message ID
     * @return bool Success status
     */
    public function deleteMessage($id) {
        $sql = "DELETE FROM contact_messages WHERE message_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Search messages by email or name
     * @param string $searchTerm Search term
     * @return array Array of matching messages
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
