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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

// Validate input
if (empty($guildId) || empty($name)) {
    echo json_encode([
        "status" => "error",
        "message" => "Guild ID and category name are required"
    ]);
    exit();
}

// Check if user is a member of the guild
$stmt = $conn->prepare("SELECT 1 FROM guild_members WHERE user_id = ? AND guild_id = ?");
$stmt->bind_param("ii", $_SESSION['user_id'], $guildId);
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

// Insert category
$stmt = $conn->prepare("INSERT INTO categories (guild_id, name) VALUES (?, ?)");
$stmt->bind_param("is", $guildId, $name);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Category created successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create category: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>