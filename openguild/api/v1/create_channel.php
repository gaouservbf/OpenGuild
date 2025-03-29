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
$host = 'mysql.ct8.pl';
$user = 'm42569_gaouser';
$pass = 'Android 8.1.0';
$db = 'm42569_openguild';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Get input data
$categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

// Validate input
if (empty($categoryId) || empty($name)) {
    echo json_encode([
        "status" => "error",
        "message" => "Category ID and channel name are required"
    ]);
    exit();
}

// Check if user is a member of the guild
$stmt = $conn->prepare("SELECT 1 FROM categories c
                       JOIN guild_members gm ON c.guild_id = gm.guild_id
                       WHERE c.id = ? AND gm.user_id = ?");
$stmt->bind_param("ii", $categoryId, $_SESSION['user_id']);
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

// Insert channel
$stmt = $conn->prepare("INSERT INTO channels (category_id, name) VALUES (?, ?)");
$stmt->bind_param("is", $categoryId, $name);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Channel created successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create channel: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>