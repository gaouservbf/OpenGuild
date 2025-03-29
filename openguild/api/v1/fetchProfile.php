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

// Get user ID from request
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (empty($userId)) {
    echo json_encode([
        "status" => "error",
        "message" => "User ID is required"
    ]);
    exit();
}

// Fetch profile data
$stmt = $conn->prepare("
    SELECT username, banner_image, pfp, gradient_color_1, gradient_color_2
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username, $banner_image, $pfp, $gradient_color_1, $gradient_color_2);
$stmt->fetch();
$stmt->close();
$conn->close();

// Return profile data
echo json_encode([
    "status" => "success",
    "profile" => [
        "username" => $username,
        "banner_image" => $banner_image,
        "pfp" => $pfp,
        "gradient_color_1" => $gradient_color_1 ?? "#212121",
        "gradient_color_2" => $gradient_color_2 ?? "#212121"
    ]
]);
?>