<?php
header("Content-Type: application/json");

// Include the database connection
require_once 'db_connection.php';

// Get input data from POST
$user_id = $_POST['user_id'];
$status = $_POST['status'];

// Validate input
if (empty($user_id) || !isset($status)) {
    echo json_encode(["status" => "error", "message" => "User ID and status are required"]);
    exit;
}

// Validate status value
$allowed_statuses = [0, 1, 2, 3, 4, 5];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(["status" => "error", "message" => "Invalid status value"]);
    exit;
}

// Update user status
$stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
$stmt->bind_param("ii", $status, $user_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "User status updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update user status"]);
}

$stmt->close();
$conn->close();
?>