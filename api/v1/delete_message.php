<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit();
}


require_once 'db_connection.php';

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Get input data (message ID to delete)
$messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;

// Validate input
if (empty($messageId)) {
    echo json_encode([
        "status" => "error",
        "message" => "Message ID is required"
    ]);
    exit();
}

// Check if the user is the owner of the message or has permission to delete it
$stmt = $conn->prepare("SELECT user_id FROM messages WHERE id = ?");
$stmt->bind_param("i", $messageId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($messageUserId);
$stmt->fetch();

if ($stmt->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Message not found"
    ]);
    $stmt->close();
    $conn->close();
    exit();
}

// Check if the logged-in user is the owner of the message or has admin privileges
if ($_SESSION['user_id'] !== $messageUserId) {
    // Optional: Add additional checks for admin or moderator roles here
    echo json_encode([
        "status" => "error",
        "message" => "You do not have permission to delete this message"
    ]);
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close();

// Delete the message
$stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
$stmt->bind_param("i", $messageId);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Message deleted successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete message: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>