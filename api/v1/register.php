<?php
header("Content-Type: application/json");


require_once 'db_connection.php';

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]));
}

// Get input data from POST
$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

// Validate input
if (empty($username) || empty($email) || empty($_POST['password'])) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// Check if user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "User already exists"]);
    exit;
}

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $password);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "User registered successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Registration failed"]);
}

$stmt->close();
$conn->close();
?>