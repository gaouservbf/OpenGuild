<?php
header("Content-Type: application/json");
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit();
}

// Database connection

require_once 'db_connection.php';

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Get input data
$channelId = isset($_GET['channel_id']) ? (int)$_GET['channel_id'] : 0;

// Validate input
if (empty($channelId)) {
    echo json_encode([
        "status" => "error",
        "message" => "Channel ID is required"
    ]);
    exit();
}

// Check if the channel is a direct message channel and restrict access
$stmt = $conn->prepare("SELECT user1_id, user2_id FROM direct_message_channels WHERE channel_id = ?");
$stmt->bind_param("i", $channelId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($user1Id, $user2Id);

if ($stmt->num_rows > 0) {
    $stmt->fetch();
    if ($_SESSION['user_id'] !== $user1Id && $_SESSION['user_id'] !== $user2Id) {
        echo json_encode([
            "status" => "error",
            "message" => "You are not authorized to view messages in this channel"
        ]);
        $stmt->close();
        $conn->close();
        exit();
    }
}
$stmt->close();

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

// Fetch messages with timestamp
$stmt = $conn->prepare("SELECT m.id, m.user_id, u.username, m.content, m.created_at, m.timestamp
                       FROM messages m
                       JOIN users u ON m.user_id = u.id
                       WHERE m.channel_id = ?
                       ORDER BY m.created_at DESC
                       LIMIT 50");
$stmt->bind_param("i", $channelId);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        "id" => $row['id'],
        "user_id" => $row['user_id'],
        "username" => $row['username'],
        "content" => htmlspecialchars($row['content']),
        "created_at" => $row['created_at'],
        "timestamp" => isset($row['timestamp']) ? $row['timestamp'] : $row['created_at']
    ];
}

// Reverse messages to show oldest first
$messages = array_reverse($messages);

echo json_encode([
    "status" => "success",
    "messages" => $messages
]);

$stmt->close();
$conn->close();
?>