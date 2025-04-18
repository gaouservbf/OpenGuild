<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $friendId = isset($_POST['friend_id']) ? (int)$_POST['friend_id'] : 0;

    if ($friendId === 0 || $friendId === $_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid friend ID']);
        exit();
    }

    // Check if a DM channel already exists
    $stmt = $conn->prepare("SELECT channel_id FROM direct_message_channels WHERE 
                            (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->bind_param("iiii", $_SESSION['user_id'], $friendId, $friendId, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'DM channel already exists']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Create a new DM channel
    $stmt = $conn->prepare("INSERT INTO direct_message_channels (user1_id, user2_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $_SESSION['user_id'], $friendId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'DM channel created']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create DM channel']);
    }

    $stmt->close();
    $conn->close();
}
?>
