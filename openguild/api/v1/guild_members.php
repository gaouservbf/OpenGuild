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
$guildId = isset($_GET['guild_id']) ? (int)$_GET['guild_id'] : 0;

// Validate input
if (empty($guildId)) {
    echo json_encode([
        "status" => "error",
        "message" => "Guild ID is required"
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

// Get guild owner ID
$ownerId = null;
$stmt = $conn->prepare("SELECT owner_id FROM guilds WHERE id = ?");
$stmt->bind_param("i", $guildId);
$stmt->execute();
$stmt->bind_result($ownerId);
$stmt->fetch();
$stmt->close();

// Fetch all members of the guild with user details
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.banner_image, u.pfp, u.gradient_color_1, u.gradient_color_2, gm.joined_at
    FROM guild_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.guild_id = ?
    ORDER BY gm.joined_at
");
$stmt->bind_param("i", $guildId);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
$members[] = [
    "id" => (int)$row['id'],
    "username" => $row['username'],
    "banner_image" => $row['banner_image'],
    "pfp" => $row['pfp'],
    "gradient_color_1" => $row['gradient_color_1'],
    "gradient_color_2" => $row['gradient_color_2'],
    "joined_at" => $row['joined_at'],
    "is_owner" => ((int)$row['id'] === (int)$ownerId)
];
}

echo json_encode([
    "status" => "success",
    "guild_id" => $guildId,
    "members" => $members,
    "owner_id" => (int)$ownerId
]);

$stmt->close();
$conn->close();
?>