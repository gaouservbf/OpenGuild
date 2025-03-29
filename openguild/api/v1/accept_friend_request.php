<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_POST['request_id'];
    $receiverId = $_SESSION['user_id'];

    // Check if the request exists and is pending
    $stmt = $conn->prepare("SELECT sender_id FROM friend_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $requestId, $receiverId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit();
    }

    // Update the request status to accepted
    $stmt = $conn->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    if ($stmt->execute()) {
        // Add the friendship to the friendships table
        $stmt = $conn->prepare("INSERT INTO friendships (user1_id, user2_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $senderId, $receiverId);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Friend request accepted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create friendship']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to accept friend request']);
    }
}
?>