<?php
// Start the session to maintain user login state
session_start();

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not authenticated'
    ]);
    exit();
}

// Check if guild_id was provided
if (!isset($_POST['guild_id']) || empty($_POST['guild_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Guild ID is required'
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
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

$userId = $_SESSION['user_id'];
$guildId = (int)$_POST['guild_id'];

// Check if user is a member of the guild
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM guild_members WHERE guild_id = ? AND user_id = ?");
$stmt->bind_param("ii", $guildId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You are not a member of this guild'
    ]);
    $stmt->close();
    $conn->close();
    exit();
}

// Generate a random invite code (8 characters alphanumeric)
$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$code = '';
for ($i = 0; $i < 8; $i++) {
    $code .= $characters[rand(0, strlen($characters) - 1)];
}

// Insert the invite code into the database
$stmt = $conn->prepare("INSERT INTO guild_invites (guild_id, code, created_by, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("isi", $guildId, $code, $userId);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'invite_code' => $code,
        'invite_url' => 'join.php?code=' . $code
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create invite: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
