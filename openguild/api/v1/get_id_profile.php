<?php
// Set the Content-Type header to JSON
header("Content-Type: application/json");

// Check if user_id was provided as a GET parameter
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "User ID is required"
    ]);
    exit();
}

// Get the user ID from GET parameter
$userId = $_GET['user_id'];

// Debugging: Log the requested user ID
error_log("Requested profile data for user_id: " . $userId);

// Database connection
$host = 'mysql.ct8.pl';
$user = 'm42569_gaouser';
$pass = 'Android 8.1.0';
$db = 'm42569_openguild';
$conn = new mysqli($host, $user, $pass, $db);

// Check for database connection errors
if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Get user profile data
$stmt = $conn->prepare("SELECT banner_image, pfp, gradient_color_1, gradient_color_2 FROM users WHERE id = ?");
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare SQL statement: " . $conn->error
    ]);
    exit();
}

$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to execute SQL statement: " . $stmt->error
    ]);
    exit();
}

$stmt->bind_result($bannerImage, $pfp, $gradientColor1, $gradientColor2);

// Fetch the result
if (!$stmt->fetch()) {
}

$stmt->close();
$conn->close();

// Debugging: Log the fetched profile data
error_log("Fetched profile data for user_id " . $userId . ": " . print_r([
    "banner_image" => $bannerImage,
    "pfp" => $pfp,
    "gradient_color_1" => $gradientColor1,
    "gradient_color_2" => $gradientColor2
], true));

// Return the user profile data
echo json_encode([
    "status" => "success",
    "banner_image" => $bannerImage,
    "pfp" => $pfp,
    "gradient_color_1" => $gradientColor1,
    "gradient_color_2" => $gradientColor2
]);
?>