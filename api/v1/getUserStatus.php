<?php
header("Content-Type: application/json");

// Include the database connection
require_once 'db_connection.php';

// Get user_id from the request
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID is required"]);
    exit;
}

// Fetch the user's status from the database
$stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();

if ($status === null) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
} else {
    echo json_encode(["status" => "success", "user_status" => $status]);
}

$conn->close();
?>