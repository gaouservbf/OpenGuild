<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Database connection

require_once 'db_connection.php';
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get input data
$userId = $_SESSION['user_id'];
$inviteCode = isset($_GET['code']) ? $_GET['code'] : '';

// Validate input
if (empty($inviteCode)) {
    die("Invalid invite code");
}

// Get guild ID from invite code
$stmt = $conn->prepare("SELECT guild_id FROM guild_invites WHERE code = ?");
$stmt->bind_param("s", $inviteCode);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($guildId);

if ($stmt->num_rows === 0) {
    $stmt->close();
    $conn->close();
    die("Invalid invite code or expired invitation");
}
$stmt->fetch();
$stmt->close();

// Check if user is already a member
$stmt = $conn->prepare("SELECT 1 FROM guild_members WHERE user_id = ? AND guild_id = ?");
$stmt->bind_param("ii", $userId, $guildId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    header("Location: home.php?guild_id=$guildId");
    exit();
}
$stmt->close();

// Add user to guild
$stmt = $conn->prepare("INSERT INTO guild_members (guild_id, user_id, joined_at) VALUES (?, ?, NOW())");
$stmt->bind_param("ii", $guildId, $userId);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: ../../home.php?guild_id=$guildId");
    exit();
} else {
    $stmt->close();
    $conn->close();
    die("Failed to join guild: " . $stmt->error);
}
?>