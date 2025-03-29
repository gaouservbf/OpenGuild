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


require_once 'db_connection.php';

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Get input data
$guildId = isset($_GET['guild_id']) ? (int)$_GET['guild_id'] : 0;

// Validate input
if (empty($guildId)) {
    echo json_encode([
        "status" => "error",
        "message" => "Guild ID is required"
    ]);
    exit();
}

// Check if user is a member of the guild
$stmt = $conn->prepare("SELECT 1 FROM guild_members WHERE user_id = ? AND guild_id = ?");
$stmt->bind_param("ii", $_SESSION['user_id'], $guildId);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "You are not a member of this guild"
    ]);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Fetch categories and channels
$stmt = $conn->prepare("SELECT c.id AS category_id, c.name AS category_name, ch.id AS channel_id, ch.name AS channel_name
                       FROM categories c
                       LEFT JOIN channels ch ON c.id = ch.category_id
                       WHERE c.guild_id = ?
                       ORDER BY c.created_at, ch.created_at");
$stmt->bind_param("i", $guildId);
$stmt->execute();
$result = $stmt->get_result();

// New approach to build categories
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categoryId = $row['category_id'];
    $categoryFound = false;
    
    // Check if we've already added this category
    foreach ($categories as $key => $category) {
        if (isset($category['id']) && $category['id'] === (int)$categoryId) {
            $categoryFound = true;
            // Add the channel if it exists
            if ($row['channel_id'] !== null) {
                $categories[$key]['channels'][] = [
                    "id" => (int)$row['channel_id'],
                    "name" => $row['channel_name']
                ];
            }
            break;
        }
    }
    
    // If category wasn't found, add it
    if (!$categoryFound) {
        $newCategory = [
            "id" => (int)$categoryId,  // Explicitly add the ID
            "name" => $row['category_name'],
            "channels" => []
        ];
        
        // Add the channel if it exists
        if ($row['channel_id'] !== null) {
            $newCategory['channels'][] = [
                "id" => (int)$row['channel_id'],
                "name" => $row['channel_name']
            ];
        }
        
        $categories[] = $newCategory;
    }
}

// Final JSON output
echo json_encode([
    "status" => "success",
    "categories" => $categories
]);

$stmt->close();
$conn->close();
?>