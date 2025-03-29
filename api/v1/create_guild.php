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
$userId = $_SESSION['user_id'];
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

// Validate input
if (empty($name)) {
    echo json_encode([
        "status" => "error",
        "message" => "Guild name is required"
    ]);
    exit();
}

// Generate a random guild ID (unique identifier)
function generateRandomGuildId($conn) {
    // Generate a random 8-digit number
    $randomId = mt_rand(10000000, 99999999);
    
    // Check if this ID already exists
    $stmt = $conn->prepare("SELECT 1 FROM guilds WHERE id = ?");
    $stmt->bind_param("i", $randomId);
    $stmt->execute();
    $stmt->store_result();
    
    // If it exists, generate a new one recursively
    if ($stmt->num_rows > 0) {
        $stmt->close();
        return generateRandomGuildId($conn);
    }
    
    $stmt->close();
    return $randomId;
}

// Get a random guild ID
$randomGuildId = generateRandomGuildId($conn);

// Create guild with the random ID
$stmt = $conn->prepare("INSERT INTO guilds (id, name, owner_id, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("isi", $randomGuildId, $name, $userId);

if ($stmt->execute()) {
    $guildId = $randomGuildId;
    $stmt->close();
    
    // Add creator as a member
    $stmt = $conn->prepare("INSERT INTO guild_members (guild_id, user_id, joined_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $guildId, $userId);
    $stmt->execute();
    
    // Generate a unique invite code
    $inviteCode = bin2hex(random_bytes(8)); // Generate a random 16-character hex string
    
    // Create invite folder and file
    $inviteDir = __DIR__ . "/invites";
    if (!file_exists($inviteDir)) {
        mkdir($inviteDir, 0755, true);
    }
    
    $inviteContent = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Guild Invitation</title>
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
            .btn { display: inline-block; padding: 10px 20px; background-color: #5865F2; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class=\"container\">
            <h1>You've been invited to join \"$name\"</h1>
            <p>Click the button below to accept the invitation:</p>
            <a href=\"./join_guild.php?code=$inviteCode\" class=\"btn\">Join Guild</a>
        </div>
    </body>
    </html>";
    
    file_put_contents("$inviteDir/$inviteCode.html", $inviteContent);
    
    // Store invite code in database
    $stmt = $conn->prepare("INSERT INTO guild_invites (guild_id, code, created_by, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("isi", $guildId, $inviteCode, $userId);
    $stmt->execute();
    
    echo json_encode([
        "status" => "success",
        "message" => "Guild created successfully",
        "guild_id" => $guildId,
        "invite_url" => "/invites/$inviteCode.html"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create guild: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>