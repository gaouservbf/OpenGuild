<?php
// Start session
session_start();

// Check if user is logged in
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

// Get input data
$channelId = isset($_POST['channel_id']) ? (int)$_POST['channel_id'] : 0;
$message = isset($_POST['message']) ? $_POST['message'] : '';

// Validate input
if (empty($channelId) || empty($message)) {
    echo json_encode([
        "status" => "error",
        "message" => "Channel ID and message are required"
    ]);
    exit();
}

// Check if user is a member of the guild
$stmt = $conn->prepare("SELECT 1 FROM channels ch
                       JOIN categories cat ON ch.category_id = cat.id
                       JOIN guild_members gm ON cat.guild_id = gm.guild_id
                       WHERE ch.id = ? AND gm.user_id = ?");
$stmt->bind_param("ii", $channelId, $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "You are not a member of this guild"
    ]);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Generate current timestamp
$currentTimestamp = date('Y-m-d H:i:s');

// Insert message with timestamp
$stmt = $conn->prepare("INSERT INTO messages (channel_id, user_id, content, created_at, timestamp) VALUES (?, ?, ?, NOW(), ?)");
$stmt->bind_param("iiss", $channelId, $_SESSION['user_id'], $message, $currentTimestamp);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Message sent successfully",
        "timestamp" => $currentTimestamp
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to send message: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>