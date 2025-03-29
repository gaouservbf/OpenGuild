<?php
header("Content-Type: application/json");
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit();
}


require_once 'db_connection.php';

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Get input data
$bannerImage = $_POST['banner_image'];
$pfp = $_POST['pfp'];
$gradientColor1 = $_POST['gradient_color_1'];
$gradientColor2 = $_POST['gradient_color_2'];

// Update profile
$stmt = $conn->prepare("
    UPDATE users
    SET banner_image = ?, pfp = ?, gradient_color_1 = ?, gradient_color_2 = ?
    WHERE id = ?
");
$stmt->bind_param("ssssi", $bannerImage, $pfp, $gradientColor1, $gradientColor2, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode([
    "status" => "success",
    "message" => "Profile updated successfully"
]);
?>