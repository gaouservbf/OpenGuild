<?php
// Set the Content-Type header to JSON
header("Content-Type: application/json");

// Start the session
session_start();

// Debugging: Log session data
error_log("Session data: " . print_r($_SESSION, true));

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit();
}

// Debugging: Log the user ID from the session
error_log("Session user_id: " . $_SESSION['user_id']);


require_once 'db_connection.php';

// Check for database connection errors
if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Get user profile data
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT banner_image, pfp, gradient_color_1, gradient_color_2 FROM users WHERE id = ?");
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare SQL statement: " . $conn->error
    ]);
    exit();
}

// Debugging: Log the user ID being used in the query
error_log("Fetching profile data for user_id: " . $userId);

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
    echo json_encode([
        "status" => "error",
        "message" => "User not found or no data available"
    ]);
    exit();
}

$stmt->close();
$conn->close();

// Debugging: Log the fetched profile data
error_log("Fetched profile data: " . print_r([
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