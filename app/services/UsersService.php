<?php
    require_once __DIR__ . '/../config/db.php';
    class UsersService {
        private $conn;
        public function __construct() {
            global $conn;
            $this->conn = $conn;
        }

        public function getUserByEmail($email) {
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        }
    }

?>