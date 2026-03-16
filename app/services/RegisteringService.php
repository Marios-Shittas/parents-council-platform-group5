<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name     = trim($data['first_name']);
$surname  = trim($data['last_name']);
$email    = trim($data['email']);
$phone    = trim($data['phone']);
$children = $data['children'];

// Check if email already exists
$check = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Το email χρησιμοποιείται ήδη."]);
    $check->close();
    exit;
}
$check->close();

$placeholderPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

$conn->begin_transaction();

try {
    $stmtUser = $conn->prepare("
        INSERT INTO Users (name, surname, email, password, phone_number, role, account_status)
        VALUES (?, ?, ?, ?, ?, 'parent', 'pending')
    ");
    $stmtUser->bind_param("sssss", $name, $surname, $email, $placeholderPassword, $phone);
    $stmtUser->execute();
    $userId = $conn->insert_id;
    $stmtUser->close();

    $stmtChild = $conn->prepare("
        INSERT INTO Children (user_id, name, surname, date_of_birth, school_class)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($children as $child) {
        $childName    = trim($child['child_name']);
        $childSurname = trim($child['child_last_name']);
        $childDob     = $child['child_dob'];
        $childClass   = $child['child_class'];

        $stmtChild->bind_param("issss", $userId, $childName, $childSurname, $childDob, $childClass);
        $stmtChild->execute();
    }
    $stmtChild->close();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Η αίτησή σας υποβλήθηκε και βρίσκεται σε αναμονή έγκρισης."]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Σφάλμα: " . $e->getMessage()]);
}

$conn->close();
?>