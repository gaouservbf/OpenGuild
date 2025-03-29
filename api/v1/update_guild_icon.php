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
$guildId = isset($_POST['guild_id']) ? (int)$_POST['guild_id'] : 0;
$guildIcon = isset($_POST['guild_icon']) ? trim($_POST['guild_icon']) : '';

// Validate input
if (empty($guildId) || empty($guildIcon)) {
    echo json_encode([
        "status" => "error",
        "message" => "Guild ID and icon URL are required"
    ]);
    exit();
}

// Check if user is a member of the guild
$stmt = $conn->prepare("SELECT 1 FROM guild_members WHERE guild_id = ? AND user_id = ?");
$stmt->bind_param("ii", $guildId, $_SESSION['user_id']);
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

// Update guild icon
$stmt = $conn->prepare("UPDATE guilds SET guild_icon = ? WHERE id = ?");
$stmt->bind_param("si", $guildIcon, $guildId);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Guild icon updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update guild icon: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>