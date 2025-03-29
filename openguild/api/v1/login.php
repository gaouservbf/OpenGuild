<?php
header("Content-Type: application/json");

// Start session
session_start();

// Database connection
$host = 'mysql.ct8.pl';
$user = 'm42569_gaouser';       // Replace with your MySQL username
$pass = 'Android 8.1.0';     // Replace with your MySQL password
$db = 'm42569_openguild';   // Your database name
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode([
        "status" => "error", 
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}

// Get input data from POST
$email = $_POST['email'];
$password = $_POST['password'];

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode([
        "status" => "error", 
        "message" => "Email and password are required"
    ]);
    exit();
}

// Fetch user from database
$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id, $username, $hashed_password);

if ($stmt->fetch() && password_verify($password, $hashed_password)) {
    // Set session variables
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['logged_in'] = true;
    
    echo json_encode([
        "status" => "success", 
        "message" => "Login successful", 
        "user_id" => $id, 
        "username" => $username
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid email or password"
    ]);
}

$stmt->close();
$conn->close();
?>