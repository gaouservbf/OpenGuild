<?php
// Start the session to maintain user login state
session_start();

// Database connection
$host = 'mysql.ct8.pl';
$user = 'm42569_gaouser';
$pass = 'Android 8.1.0';
$db = 'm42569_openguild';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.htm");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$error = false;
$guildId = null;
$guildName = '';

// Check if invite code is provided
if (isset($_GET['code']) && !empty($_GET['code'])) {
    $inviteCode = $_GET['code'];
    
    // Validate the invite code
    $stmt = $conn->prepare("SELECT gi.guild_id, g.name FROM guild_invites gi 
                          JOIN guilds g ON gi.guild_id = g.id 
                          WHERE gi.code = ?");
    $stmt->bind_param("s", $inviteCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $guildId = $row['guild_id'];
        $guildName = $row['name'];
        
        // Check if the user is already a member of this guild
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM guild_members WHERE guild_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $guildId, $userId);
        $stmt->execute();
        $memberResult = $stmt->get_result();
        $memberRow = $memberResult->fetch_assoc();
        
        if ($memberRow['count'] > 0) {
            $message = "You are already a member of " . htmlspecialchars($guildName) . ".";
            $error = true;
        } else {
            // Process the join request if the form is submitted
            if (isset($_POST['join'])) {
                // Add the user to the guild
                $stmt = $conn->prepare("INSERT INTO guild_members (guild_id, user_id, joined_at) VALUES (?, ?, NOW())");
                $stmt->bind_param("ii", $guildId, $userId);
                
                if ($stmt->execute()) {
                    // Redirect to the guild's home page
                    header("Location: home.php?guild_id=" . $guildId);
                    exit();
                } else {
                    $message = "Failed to join the guild: " . $stmt->error;
                    $error = true;
                }
            }
        }
    } else {
        $message = "Invalid invite code.";
        $error = true;
    }
    $stmt->close();
} else {
    $message = "No invite code provided.";
    $error = true;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Guild - OpenGuild</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        body {
            background-color: #212121;
            color: #ffffff;
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .join-container {
            background-color: #1e1e1e;
            border-radius: 8px;
            padding: 30px;
            width: 400px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .join-container h2 {
            margin-top: 0;
            color: #ffffff;
        }
        .join-container p {
            margin-bottom: 20px;
        }
        .join-button {
            background-color: #089C44;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .join-button:hover {
            background-color: #065C28;
        }
        .error-message {
            color: #ff5555;
            margin-bottom: 20px;
        }
        .success-message {
            color: #55ff55;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #089C44;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .guild-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background-color: #333;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="join-container">
        <img src="openguildappoff.png" style="width: 64px; margin-bottom: 20px;">
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $message; ?></div>
            <a href="home.php" class="back-link">Return to Home</a>
        <?php elseif (!isset($_POST['join'])): ?>
            <h2>Join Guild</h2>
            <div class="guild-logo">
                <?php echo substr(htmlspecialchars($guildName), 0, 1); ?>
            </div>
            <p>You've been invited to join <strong><?php echo htmlspecialchars($guildName); ?></strong>!</p>
            
            <form method="post" action="join.php?code=<?php echo htmlspecialchars($inviteCode); ?>">
                <button type="submit" name="join" class="join-button">Join Guild</button>
            </form>
            
            <a href="home.php" class="back-link">Cancel</a>
        <?php endif; ?>
    </div>
</body>
</html>