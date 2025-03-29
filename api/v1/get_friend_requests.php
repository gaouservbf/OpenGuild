<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch pending friend requests
$stmt = $conn->prepare("SELECT fr.id, u.username, fr.created_at 
                        FROM friend_requests fr 
                        JOIN users u ON fr.sender_id = u.id 
                        WHERE fr.receiver_id = ? AND fr.status = 'pending'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

echo json_encode(['status' => 'success', 'requests' => $requests]);
?>