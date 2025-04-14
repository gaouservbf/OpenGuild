<?php
// Start the session to maintain user login state
session_start();

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.htm");
    exit();
}
// gaouser dont make the mistake again
require 'api/v1/db_connection.php';

// Get user's guilds
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT g.id, g.name, g.guild_icon FROM guilds g
                       JOIN guild_members gm ON g.id = gm.guild_id
                       WHERE gm.user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$guilds = [];
while ($row = $result->fetch_assoc()) {
    $guilds[] = $row;
}
$stmt->close();

// Get current guild info if specified
$currentGuildId = isset($_GET['guild_id']) ? (int)$_GET['guild_id'] : (count($guilds) > 0 ? $guilds[0]['id'] : null);
$currentGuildName = "";
$currentGuildIcon = "";

if ($currentGuildId) {
    $stmt = $conn->prepare("SELECT name, guild_icon FROM guilds WHERE id = ?");
    $stmt->bind_param("i", $currentGuildId);
    $stmt->execute();
    $stmt->bind_result($currentGuildName, $currentGuildIcon);
    $stmt->fetch();
    $stmt->close();
}

$conn->close();
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenGuild</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
      <link href="styles.css" rel="stylesheet">
 
</head>
<body bgcolor="#212121">
    <table width="100%" height="100%" cellpadding="0" cellspacing="0">
        <!-- Guild Name -->
        <tr width="100%" valign="top" height=50>
          <td><img src="openguildappoff.png" id="openguild-icon" style="margin: 8px; cursor: pointer;"></td>
            <td height=50 width="100%" id="guild-name" valign=middle align=middle>
                <?php echo htmlspecialchars($currentGuildName); ?>
            </td>
        </tr>
        <!-- Categories and Channels -->
        <tr valign="top" height=100%>
            <td>
                <table width="100%" height="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align=center>
                            <div id="guild-list">
                                <?php foreach ($guilds as $guild): ?>
                                <div class="guild-item <?php echo ($guild['id'] == $currentGuildId) ? 'active-guild' : ''; ?>"
                                     data-guild-id="<?php echo $guild['id']; ?>">
                                    <?php if (!empty($guild['guild_icon'])): ?>
                                        <img src="<?php echo htmlspecialchars($guild['guild_icon']); ?>" alt="Guild Icon" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background-color: #444; display: flex; align-items: center; justify-content: center;">
                                            <?php echo htmlspecialchars($guild['name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                              <img src="newserver.png" id="create-guild-btn" style="cursor: pointer;">
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="100%">
                <table height=100% cellspacing=0 cellpadding=0>
                    <tr height=100%>
                        <td height=100%>
                          <table width=100% cellspacing=0 cellpadding=12 height=100% bgcolor=#1e1e1e>
                            <tr>
                              <td valign=middle>
                            <div id="channels-list" style="width: 200px; height: 100%;  background-color: #1e1e1e;" class="scrollable-container">
                                <!-- Channels will be loaded here -->
                            </div>
                                </td>
                              </tr>
                            </table>
                        </td>
                        <td width=100% height=100%>
                            <div id="message-container" style="height: 100%; min-width: 100%;" class="message-container" class="scrollable-container">
                                <!-- Messages will be loaded here -->
                            </div>
                        </td>
<td>
    <div id="members-list" style="width: 200px; height: 100%; background-color: #1e1e1e; padding: 10px;">
        <h4 style="color: rgba(8,156,68,1); margin-bottom: 10px;">Members</h4>
        <div id="members-container"></div>
    </div>
</td>
                    </tr>
                  <tr>
                    <td>
                      
                      <table cellpadding=5>
                        <tr>
                          <td width=100% align=center><?php echo($_SESSION['username']);?></td>
                      <td><img src=cog.png id="cog-icon" style="cursor: pointer;"></td>
                          </tr>
                        </table>
                      </td>
                    <td>
                                      <input id="message-input" class="message-box" placeholder="Type your message..."></input>
                <!-- <button id="send-message-btn">Send</button> -->
                      </td>
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Message Input -->
    </table>
<!-- User Settings Modal -->
<div id="user-settings-modal" class="user-set-modal" style="display: none;">
    <div class="modal-content">
        <h3>User Settings</h3>
        <table width="100%" cellpadding="10" cellspacing="0">
            <tr>
                <!-- Left Side: Settings Form -->
                <td width="50%" valign="top">
                    <div class="settings-form">
                        <div class="form-group">
                            <label for="banner-image-url">Banner Image URL:</label>
                            <input type="text" id="banner-image-url" placeholder="Enter banner image URL" class="settings-input">
                        </div>
                        
                        <div class="form-group">
                            <label for="pfp-url">Profile Picture URL:</label>
                            <input type="text" id="pfp-url" placeholder="Enter profile picture URL" class="settings-input">
                        </div>
                        
                        <div class="form-group">
                            <label for="gradient-color-1">Gradient Color 1:</label>
                            <div class="color-input-wrapper">
                                <input type="text" id="gradient-color-1" placeholder="#RRGGBB" class="settings-input color-code">
                                <input type="color" id="gradient-color-1-picker" class="color-picker">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="gradient-color-2">Gradient Color 2:</label>
                            <div class="color-input-wrapper">
                                <input type="text" id="gradient-color-2" placeholder="#RRGGBB" class="settings-input color-code">
                                <input type="color" id="gradient-color-2-picker" class="color-picker">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button id="save-user-settings" class="settings-button">Save</button>
                            <button id="close-user-settings" class="settings-button">Cancel</button>
                        </div>
                      
                      <div class="form-group">
    <label for="user-status">Status:</label>
    <select id="user-status" class="settings-input">
        <option value="0">Offline (Invisible)</option>
        <option value="1">Online</option>
        <option value="2">Do Not Disturb</option>
        <option value="3">Away</option>
        <option value="4">Developing</option>
        <option value="5">Gaming</option>
    </select>
</div>
                    </div>
                </td>
                
                <!-- Right Side: Profile Preview -->
<!-- Right Side: Profile Preview -->
<td width="50%" valign="top">
    <div id="user-profile-preview" style="width: 320px; height: 480px; position: relative; border-radius: 8px; overflow: hidden;">
        <!-- Gradient Background -->
        <div id="preview-gradient" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
        
        <!-- Profile Picture (Positioned at the Top) -->
     <div id="preview-pfp" style="position: absolute; z-index: 2000; top: 40px; left: 20px; width: 80px; height: 80px; border-radius: 50%; background-size: cover; background-position: center; border: 4px solid #1e1e1e;">
    <div class="status-indicator status-<?php echo $user_status; ?>"></div>
</div>        <!-- Banner Image -->
        <div id="preview-banner" style="position: absolute; top: 0; left: 0; width: 100%; height: 100px; background-size: cover; background-position: center;"></div>
    </div>
</td>
            </tr>
        </table>
    </div>
</div>
    <!-- Create Guild Form -->
    <div class="overlay" id="overlay"></div>
    <div class="create-guild-form" id="create-guild-form">
        <h3>Create New Guild</h3>
        <input type="text" id="guild-name-input" placeholder="Guild Name">
        <button id="submit-guild-btn">Create</button>
        <button id="cancel-guild-btn">Cancel</button>
    </div>

    <!-- Guild Name Context Menu -->
    <div id="guild-name-context-menu" class="context-menu" style="display: none;">
        <ul>
            <li id="invite-guild">Invite</li>
            <li id="settings-guild">Settings</li>
        </ul>
    </div>

    <!-- Full-Screen Settings Modal -->
    <div id="settings-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Guild Settings</h3>
            <label for="guild-icon-url">Guild Icon URL:</label>
            <input type="text" id="guild-icon-url" placeholder="Enter image URL">
            <button id="save-guild-icon">Save</button>
            <button id="close-settings-modal">Close</button>
        </div>
    </div>
    <script>
        const currentGuildId = <?php echo $currentGuildId ? $currentGuildId : 'null'; ?>;
        const currentGuildName = "<?php echo htmlspecialchars($currentGuildName); ?>";
        const currentGuildIcon = "<?php echo htmlspecialchars($currentGuildIcon); ?>";
        const guilds = <?php echo json_encode($guilds); ?>;
        const userId = <?php echo $_SESSION['user_id']; ?>;
        const username = "<?php echo $_SESSION['username']; ?>";
    </script>
<!-- Message Context Menu -->
<div id="message-context-menu" class="context-menu" style="display: none;">
    <ul>
        <li id="delete-message-option">Delete Message</li>
    </ul>
</div>

  
    <script src="home.js">


    </script>
</body>
</html>


