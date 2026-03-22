<?php
    require_once __DIR__ . '/../config/db.php';
    class UsersService {
        private $conn;
        public function __construct() {
            global $conn;
            $this->conn = $conn;
        }

        public function getUserByEmail($email) {
            $sql = "SELECT * FROM Users WHERE email = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        }

        public function login($inputEmail, $inputPassword) {
            $user = $this->getUserByEmail($inputEmail);
            $password = password_verify($inputPassword, $user['password']);

            if (!$user || $inputEmail !== $user['email'] || !$password) {
                return ['success' => false, 'message' => 'Invalid email or password.'];
            }
            return ['success' => true, 'role' => $user['role'], 'message' => 'Login successful.'];
        }

        public function forgot($email) {
            $user = $this->getUserByEmail($email);
            if (!$user || $email !== $user['email']) {
                return ['success' => false, 'message' => 'Invalid email.'];
            }
            return ['success' => true, 'message' => 'Link Sent.'];
        }
    }
?>