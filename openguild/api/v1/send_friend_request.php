<?php
session_start();
require_once 'db_connection.php'; // Include your database connection file

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senderId = $_SESSION['user_id'];
    $receiverId = $_POST['receiver_id'];

    // Check if the receiver exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $receiverId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Receiver not found']);
        exit();
    }

    // Check if a request already exists
    $stmt = $conn->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $senderId, $receiverId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Friend request already sent']);
        exit();
    }

    // Insert the friend request
    $stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $senderId, $receiverId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Friend request sent']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send friend request']);
    }
}
?>