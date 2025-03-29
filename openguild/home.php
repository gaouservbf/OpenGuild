<?php
// Start the session to maintain user login state
session_start();

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.htm");
    exit();
}

// Database connection
$host = 'mysql.ct8.pl';
$user = 'm42569_gaouser';
$pass = 'Android 8.1.0';
$db = 'm42569_openguild';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

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
                            <div id="channels-list" style="width: 200px; height: 100%;  background-color: #1e1e1e;">
                                <!-- Channels will be loaded here -->
                            </div>
                                </td>
                              </tr>
                            </table>
                        </td>
                        <td width=100% height=100%>
                            <div id="message-container" style="height: 100%; min-width: 100%;" class="message-container">
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
                    </div>
                </td>
                
                <!-- Right Side: Profile Preview -->
<!-- Right Side: Profile Preview -->
<td width="50%" valign="top">
    <div id="user-profile-preview" style="width: 320px; height: 480px; position: relative; border-radius: 8px; overflow: hidden;">
        <!-- Gradient Background -->
        <div id="preview-gradient" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
        
        <!-- Profile Picture (Positioned at the Top) -->
        <div id="preview-pfp" style="position: absolute; z-index: 2000; top: 40px; left: 20px; width: 80px; height: 80px; border-radius: 50%; background-size: cover; background-position: center; border: 4px solid #1e1e1e;"></div>
        
        <!-- Banner Image -->
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
        // Current guild ID and channel ID
        let currentGuildId = <?php echo $currentGuildId ? $currentGuildId : 'null'; ?>;
        let currentChannelId = null;

        // Function to fetch channels for the current guild
        function fetchChannels() {
            if (!currentGuildId) return;

            fetch(`api/v1/get_channels.php?guild_id=${currentGuildId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        const channelsList = document.getElementById('channels-list');
                        channelsList.innerHTML = '';

                        data.categories.forEach(category => {
                            const categoryDiv = document.createElement('div');
                            categoryDiv.innerHTML = `<center><strong>${category.name}</strong></center>`;
                            channelsList.appendChild(categoryDiv);

                            category.channels.forEach(channel => {
                                const channelItem = document.createElement('div');
                                channelItem.className = 'channel-item';
                                channelItem.innerHTML = `# ${channel.name}`;
                                channelItem.addEventListener('click', () => {
                                    currentChannelId = channel.id;
                                    fetchMessages();
                                });
                                channelsList.appendChild(channelItem);
                            });
                        });
                    }
                })
                .catch(error => console.error('Error fetching channels:', error));
        }

        // Function to fetch messages for the current channel
        function fetchMessages() {
            if (!currentChannelId) return;

            fetch(`api/v1/get_messages.php?channel_id=${currentChannelId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        const messageContainer = document.getElementById('message-container');
                        messageContainer.innerHTML = '';

                        data.messages.forEach(msg => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'message';
                            messageDiv.innerHTML = `<strong>${msg.username}:</strong> ${msg.content}`;
                            messageContainer.appendChild(messageDiv);
                        });

                        // Scroll to bottom
                        messageContainer.scrollTop = messageContainer.scrollHeight;
                    }
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        // Function to send a message
function sendMessage() {
    if (!currentChannelId) return;

    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();

    if (message !== '') {
        const formData = new FormData();
        formData.append('channel_id', currentChannelId);
        formData.append('message', message);

        fetch('api/v1/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                messageInput.value = '';
                fetchMessages();
            } else {
                alert('Error sending message: ' + data.message);
            }
        })
        .catch(error => console.error('Error sending message:', error));
    }
}

// Listen for Enter key press in the message input field
document.getElementById('message-input').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault(); // Prevent the default action (e.g., new line)
        sendMessage();
    }
});
        // Switch guild when clicked
        document.querySelectorAll('.guild-item').forEach(item => {
            item.addEventListener('click', function() {
                const guildId = this.getAttribute('data-guild-id');
                window.location.href = `home.php?guild_id=${guildId}`;
            });
        });

        // Create guild form controls
        document.getElementById('create-guild-btn').addEventListener('click', function() {
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('create-guild-form').style.display = 'block';
        });

        document.getElementById('cancel-guild-btn').addEventListener('click', function() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('create-guild-form').style.display = 'none';
        });

        document.getElementById('submit-guild-btn').addEventListener('click', function() {
            const guildName = document.getElementById('guild-name-input').value.trim();

            if (guildName !== '') {
                const formData = new FormData();
                formData.append('name', guildName);

                fetch('api/v1/create_guild.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        window.location.reload();
                    } else {
                        alert('Error creating guild: ' + data.message);
                    }
                })
                .catch(error => console.error('Error creating guild:', error));
            }
        });

        // Guild Name Context Menu
        const guildName = document.getElementById('guild-name');
        const guildNameContextMenu = document.getElementById('guild-name-context-menu');

        guildName.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            guildNameContextMenu.style.display = 'block';
            guildNameContextMenu.style.left = `${e.pageX}px`;
            guildNameContextMenu.style.top = `${e.pageY}px`;
        });

        // Close Context Menu on Click Outside
        document.addEventListener('click', () => {
            guildNameContextMenu.style.display = 'none';
        });

        // Open Settings Modal
        document.getElementById('settings-guild').addEventListener('click', () => {
            document.getElementById('settings-modal').style.display = 'block';
        });

        // Close Settings Modal
        document.getElementById('close-settings-modal').addEventListener('click', () => {
            document.getElementById('settings-modal').style.display = 'none';
        });

        // Save Guild Icon
        document.getElementById('save-guild-icon').addEventListener('click', () => {
            const guildIconUrl = document.getElementById('guild-icon-url').value.trim();

            if (guildIconUrl !== '') {
                const formData = new FormData();
                formData.append('guild_id', currentGuildId);
                formData.append('guild_icon', guildIconUrl);

                fetch('api/v1/update_guild_icon.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        window.location.reload(); // Reload to reflect changes
                    } else {
                        alert('Error updating guild icon: ' + data.message);
                    }
                })
                .catch(error => console.error('Error updating guild icon:', error));
            }
        });

        // Initial fetch
        if (currentGuildId) {
            fetchChannels();
            setInterval(fetchMessages, 5000); // Fetch messages every 5 seconds
        }
      

// Create and append the context menus and modals to the body
document.body.insertAdjacentHTML('beforeend', `
    <!-- Channel List Context Menu -->
    <div id="channel-list-context-menu" class="context-menu" style="display: none;">
        <ul>
            <li id="create-category-option">Create Category</li>
            <li id="create-channel-option">Create Channel</li>
        </ul>
    </div>

    <!-- Category Context Menu -->
    <div id="category-context-menu" class="context-menu" style="display: none;">
        <ul>
            <li id="create-channel-under-category">Create Channel</li>
        </ul>
    </div>

    <!-- Create Category Modal -->
    <div id="create-category-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Create New Category</h3>
            <input type="text" id="category-name-input" placeholder="Category Name">
            <button id="create-category-btn">Create</button>
            <button id="cancel-category-btn">Cancel</button>
        </div>
    </div>

    <!-- Create Channel Modal -->
    <div id="create-channel-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Create New Channel</h3>
            <input type="text" id="channel-name-input" placeholder="Channel Name">
            <input type="hidden" id="category-id-input" value="">
            <button id="create-channel-btn">Create</button>
            <button id="cancel-channel-btn">Cancel</button>
        </div>
    </div>
`);

// Global variables to store context menu state
let currentCategoryId = null;

// Function to show channel list context menu
function showChannelListContextMenu(event) {
    event.preventDefault();
    const contextMenu = document.getElementById('channel-list-context-menu');
    contextMenu.style.display = 'block';
    contextMenu.style.left = `${event.pageX}px`;
    contextMenu.style.top = `${event.pageY}px`;
}

// Function to show category context menu
function showCategoryContextMenu(event, categoryId) {
    event.preventDefault();
    event.stopPropagation(); // Stop the event from bubbling up
    currentCategoryId = categoryId;
    const contextMenu = document.getElementById('category-context-menu');
    contextMenu.style.display = 'block';
    contextMenu.style.left = `${event.pageX}px`;
    contextMenu.style.top = `${event.pageY}px`;
}

// Close all context menus when clicking elsewhere
document.addEventListener('click', () => {
    document.querySelectorAll('.context-menu').forEach(menu => {
        menu.style.display = 'none';
    });
});

// Create category option handler
document.getElementById('create-category-option').addEventListener('click', () => {
    document.getElementById('create-category-modal').style.display = 'block';
});

// Create channel option handler
document.getElementById('create-channel-option').addEventListener('click', () => {
    document.getElementById('create-channel-modal').style.display = 'block';
    document.getElementById('category-id-input').value = ''; // Will be filled by the API
});

// Create channel under category handler
document.getElementById('create-channel-under-category').addEventListener('click', () => {
    document.getElementById('create-channel-modal').style.display = 'block';
    document.getElementById('category-id-input').value = currentCategoryId;
});

// Cancel category creation
document.getElementById('cancel-category-btn').addEventListener('click', () => {
    document.getElementById('create-category-modal').style.display = 'none';
    document.getElementById('category-name-input').value = '';
});

// Cancel channel creation
document.getElementById('cancel-channel-btn').addEventListener('click', () => {
    document.getElementById('create-channel-modal').style.display = 'none';
    document.getElementById('channel-name-input').value = '';
    document.getElementById('category-id-input').value = '';
});

// Create category submission
document.getElementById('create-category-btn').addEventListener('click', () => {
    const categoryName = document.getElementById('category-name-input').value.trim();
    
    if (categoryName === '') {
        alert('Please enter a category name');
        return;
    }
    
    const formData = new FormData();
    formData.append('guild_id', currentGuildId);
    formData.append('name', categoryName);
    
    fetch('api/v1/create_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            document.getElementById('create-category-modal').style.display = 'none';
            document.getElementById('category-name-input').value = '';
            fetchChannels(); // Refresh the channel list
        } else {
            alert('Error creating category: ' + data.message);
        }
    })
    .catch(error => console.error('Error creating category:', error));
});

// Create channel submission
document.getElementById('create-channel-btn').addEventListener('click', () => {
    const channelName = document.getElementById('channel-name-input').value.trim();
    const categoryId = document.getElementById('category-id-input').value;
    
    if (channelName === '') {
        alert('Please enter a channel name');
        return;
    }
    
    if (categoryId === '') {
        // If no category is specified, create in the first category
        // You might want to modify this to create a default category if none exists
        fetch(`api/v1/get_channels.php?guild_id=${currentGuildId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === "success" && data.categories.length > 0) {
                    createChannelInCategory(data.categories[0].id, channelName);
                } else {
                    alert('No categories found. Please create a category first.');
                }
            })
            .catch(error => console.error('Error fetching categories:', error));
    } else {
        createChannelInCategory(categoryId, channelName);
    }
});

// Helper function to create a channel in a specific category
function createChannelInCategory(categoryId, channelName) {
    const formData = new FormData();
    formData.append('category_id', categoryId);
    formData.append('name', channelName);
    fetch('api/v1/create_channel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            document.getElementById('create-channel-modal').style.display = 'none';
            document.getElementById('channel-name-input').value = '';
            document.getElementById('category-id-input').value = '';
            fetchChannels(); // Refresh the channel list
        } else {
            alert('Error creating channel: ' + data.message);
        }
    })
    .catch(error => console.error('Error creating channel:', error));
}

// Modify the existing fetchChannels function to add context menu functionality
function fetchChannels() {
    if (!currentGuildId) return;

    fetch(`api/v1/get_channels.php?guild_id=${currentGuildId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                const channelsList = document.getElementById('channels-list');
                channelsList.innerHTML = '';
                
                // Add context menu to channel list
                channelsList.addEventListener('contextmenu', showChannelListContextMenu);
                
                data.categories.forEach(category => {
                    const categoryDiv = document.createElement('div');
          
categoryDiv.classList.add('container');
                    categoryDiv.innerHTML = `<strong>${category.name}</strong>`;
                    categoryDiv.dataset.categoryId = category.id;
                    categoryDiv.style.cursor = 'pointer';
                    
                    // Add context menu for category
                    categoryDiv.addEventListener('contextmenu', (e) => {
                        showCategoryContextMenu(e, category.id);
                    });
                    
                    channelsList.appendChild(categoryDiv);

                    category.channels.forEach(channel => {
                        const channelItem = document.createElement('div');
                        channelItem.className = 'channel-item';
                        channelItem.innerHTML = `# ${channel.name}`;
                        channelItem.addEventListener('click', () => {
                            currentChannelId = channel.id;
                            fetchMessages();
                        });
                        channelsList.appendChild(channelItem);
                    });
                });
            }
        })
        .catch(error => console.error('Error fetching channels:', error));
}
          
   

// Function to fetch a user's profile picture

// Initialize the page
// Initialize the page
function initializePage() {
    console.log("Current Guild ID:", currentGuildId);
    if (currentGuildId) {
        fetchChannels();
        fetchMembers(); // Fetch members when the page loads
        setInterval(fetchMessages, 5000); // Fetch messages every 5 seconds
    }
}

// Call initializePage when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', initializePage);
          
          
          
          function getCurrentDirectory() {
  const path = window.location.pathname;
  const lastSlashIndex = path.lastIndexOf("/");

  if (lastSlashIndex === -1 || lastSlashIndex === path.length - 1) {
    return "/"; // Return root if no slashes or only trailing slash
  }

  return window.location.hostname + path.substring(0, lastSlashIndex + 1);
}
          
// Handle the invite option in the context menu
document.getElementById('invite-guild').addEventListener('click', () => {
    if (!currentGuildId) {
        alert('No guild selected.');
        return;
    }
    
    const formData = new FormData();
    formData.append('guild_id', currentGuildId);
    
    fetch('api/v1/create_invite.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            // Create a modal dialog to show the invite code
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.style.zIndex = '1000';
            
            const modalContent = document.createElement('div');
            modalContent.className = 'modal-content';
            
            const fullUrl = 'http://' + getCurrentDirectory() + 'api/v1/' + data.invite_url;
            
            modalContent.innerHTML = `
                <h3>Guild Invite</h3>
                <p>Share this link or code with others:</p>
                <div style="background-color: #333; padding: 10px; border-radius: 4px; margin: 10px 0;">
                    <input type="text" value="${fullUrl}" readonly
                           style="width: 100%; background: transparent; border: none; color: white;">
                </div>
                <p>Invite Code: <strong>${data.invite_code}</strong></p>
                <button id="copy-invite-btn">Copy to Clipboard</button>
                <button id="close-invite-modal">Close</button>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Copy to clipboard functionality
            document.getElementById('copy-invite-btn').addEventListener('click', () => {
                navigator.clipboard.writeText(fullUrl).then(() => {
                    alert('Invite link copied to clipboard!');
                });
            });
            
            // Close modal functionality
            document.getElementById('close-invite-modal').addEventListener('click', () => {
                document.body.removeChild(modal);
            });
        } else {
            alert('Error creating invite: ' + data.message);
        }
    })
    .catch(error => console.error('Error creating invite:', error));
});
      
      
      // Function to update the profile preview
// Function to update the profile preview
// Function to fetch user profile data



// Add event listeners for input changes
document.getElementById('banner-image-url').addEventListener('input', updateProfilePreview);
document.getElementById('pfp-url').addEventListener('input', updateProfilePreview);
document.getElementById('gradient-color-1').addEventListener('input', updateProfilePreview);
document.getElementById('gradient-color-2').addEventListener('input', updateProfilePreview);

// Sync color picker with text input
document.getElementById('gradient-color-1-picker').addEventListener('input', function() {
    document.getElementById('gradient-color-1').value = this.value;
    updateProfilePreview();
});

document.getElementById('gradient-color-2-picker').addEventListener('input', function() {
    document.getElementById('gradient-color-2').value = this.value;
    updateProfilePreview();
});

// Show modal and fetch user profile data
document.getElementById('cog-icon').addEventListener('click', function() {
    // Display the modal
    document.getElementById('user-settings-modal').style.display = 'block';

    // Fetch and populate user profile data
    fetchUserProfile();
});

// Close modal
document.getElementById('close-user-settings').addEventListener('click', function() {
    document.getElementById('user-settings-modal').style.display = 'none';
});
      
      
      // Updated profile-related functions to use get_id_profile.php instead of get_user_profile.php

// Function to fetch user profile data (for the current user)
function fetchUserProfile() {
    // Get the current user's ID from the session
    const currentUserId = <?php echo $_SESSION['user_id']; ?>;
    fetchUserProfileById(currentUserId);
}

// Function to fetch any user's profile by ID
function fetchUserProfileById(userId) {
    fetch(`api/v1/get_id_profile.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                // Populate input fields with fetched data if in settings modal
                if (document.getElementById('banner-image-url')) {
                    document.getElementById('banner-image-url').value = data.banner_image || '';
                    document.getElementById('pfp-url').value = data.pfp || '';
                    document.getElementById('gradient-color-1').value = data.gradient_color_1 || '#089C44';
                    document.getElementById('gradient-color-2').value = data.gradient_color_2 || '#065C28';

                    // Update the color pickers
                    document.getElementById('gradient-color-1-picker').value = data.gradient_color_1 || '#089C44';
                    document.getElementById('gradient-color-2-picker').value = data.gradient_color_2 || '#065C28';

                    // Update the preview
                    updateProfilePreview();
                }
                
                return data; // Return the profile data for use in promises
            } else {
                console.error('Error fetching user profile:', data.message);
                return null;
            }
        })
        .catch(error => {
            console.error('Error fetching user profile:', error);
            return null;
        });
}

// Fix the fetchUserProfilePicture function - it had a bug where userId wasn't being used properly
function fetchUserProfilePicture(userId) {
    return fetch('api/v1/get_id_profile.php?user_id='+userId)
        .then(response => response.json())
      
        .then(data => {
            if (data.status === "success") {
                return data.pfp; // Return the profile picture URL with fallback
            } else {
                console.error(userId + ' Error fetching profile picture:', data.message);
                return 'default-avatar.png';
            }
        })
        .catch(error => {
            console.error('Error fetching profile picture:', error);
            return 'default-avatar.png';
        });
}

// Function to update the profile preview
function updateProfilePreview() {
    const bannerUrl = document.getElementById('banner-image-url').value;
    const pfpUrl = document.getElementById('pfp-url').value;
    const color1 = document.getElementById('gradient-color-1').value;
    const color2 = document.getElementById('gradient-color-2').value;

    // Update gradient background
    const gradientDiv = document.getElementById('preview-gradient');
    gradientDiv.style.background = `linear-gradient(to bottom, ${color1 || '#089C44'}, ${color2 || '#065C28'})`;

    // Update banner image
    const bannerDiv = document.getElementById('preview-banner');
    if (bannerUrl) {
        bannerDiv.style.backgroundImage = `url('${bannerUrl}')`;
    } else {
        bannerDiv.style.backgroundImage = 'none';
    }

    // Update profile picture
    const pfpDiv = document.getElementById('preview-pfp');
    if (pfpUrl) {
        pfpDiv.style.backgroundImage = `url('${pfpUrl}')`;
    } else {
        pfpDiv.style.backgroundImage = 'none';
    }
}

// Function to save user settings - updated to use user_id parameter
function saveUserSettings() {
    const bannerImage = document.getElementById('banner-image-url').value;
    const pfp = document.getElementById('pfp-url').value;
    const gradientColor1 = document.getElementById('gradient-color-1').value;
    const gradientColor2 = document.getElementById('gradient-color-2').value;
    
    const formData = new FormData();
    formData.append('banner_image', bannerImage);
    formData.append('pfp', pfp);
    formData.append('gradient_color_1', gradientColor1);
    formData.append('gradient_color_2', gradientColor2);
    // Don't need to append user_id as the API should use the session user_id
    
    fetch('api/v1/updateProfile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            // Close the modal after successful update
            document.getElementById('user-settings-modal').style.display = 'none';
            // You might want to update the UI to reflect the changes
            alert('Profile updated successfully!');
        } else {
            alert('Error updating profile: ' + data.message);
        }
    })
    .catch(error => console.error('Error updating profile:', error));
}

// Updated function to fetch and display guild members
function fetchMembers() {
    if (!currentGuildId) {
        console.error("No guild ID found.");
        return;
    }

    fetch(`api/v1/guild_members.php?guild_id=${currentGuildId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                const membersContainer = document.getElementById('members-container');
                membersContainer.innerHTML = ''; // Clear existing content

                // Process each member
                const memberPromises = data.members.map(member => {
                    return fetchUserProfilePicture(member.id)
                        .then(pfpUrl => {
                            // Create member item div
                            const memberDiv = document.createElement('div');
                            memberDiv.className = 'member-item';
                            memberDiv.style.display = 'flex';
                            memberDiv.style.alignItems = 'center';
                            memberDiv.style.marginBottom = '10px';
                            memberDiv.setAttribute('data-user-id', member.id);

                            // Member avatar
                            const avatarImg = document.createElement('img');
                            avatarImg.src = pfpUrl; // Using the returned pfp URL or default
                            avatarImg.style.width = '30px';
                            avatarImg.style.height = '30px';
                            avatarImg.style.borderRadius = '50%';
                            avatarImg.style.marginRight = '10px';

                            // Member username
                            const usernameSpan = document.createElement('span');
                            usernameSpan.textContent = member.username;
                            usernameSpan.style.color = '#ffffff';

                            // Owner badge (if applicable)
                            if (member.is_owner) {
                                const ownerBadge = document.createElement('span');
                                ownerBadge.textContent = '👑';
                                ownerBadge.style.marginLeft = '5px';
                                usernameSpan.appendChild(ownerBadge);
                            }

                            // Append avatar and username to the member div
                            memberDiv.appendChild(avatarImg);
                            memberDiv.appendChild(usernameSpan);

                            // Add click event to show user profile
                            memberDiv.addEventListener('click', () => {
                                showUserProfileCard(member.id, member.username);
                            });

                            return memberDiv;
                        });
                });

                // Wait for all promises to resolve, then add members to the container
                Promise.all(memberPromises)
                    .then(memberElements => {
                        memberElements.forEach(element => {
                            membersContainer.appendChild(element);
                        });
                    });
            } else {
                console.error('Error fetching members:', data.message);
            }
        })
        .catch(error => console.error('Error fetching members:', error));
}

// New function to show a user profile card when clicking on a member
function showUserProfileCard(userId, username) {
    // Create modal for user profile
    const modal = document.createElement('div');
    modal.className = 'modal user-profile-modal';
    modal.style.display = 'block';
    
    // Create loading indicator
    modal.innerHTML = `
        <div class="modal-content" style="width: 320px;">
            <h3>Loading ${username}'s profile...</h3>
            <div class="loading-spinner"></div>
            <button class="close-profile-btn">Close</button>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add event listener to close button
    modal.querySelector('.close-profile-btn').addEventListener('click', () => {
        document.body.removeChild(modal);
    });
    
    // Fetch user profile data
    fetch(`api/v1/get_id_profile.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                // Update modal with user profile data
                const profileContent = `
                    <div class="modal-content" style="width: 320px; padding: 0; overflow: hidden; border-radius: 8px;">
                        <div style="position: relative; height: 320px;">
                            <!-- Gradient Background -->
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                                background: linear-gradient(to bottom, ${data.gradient_color_1 || '#089C44'}, ${data.gradient_color_2 || '#065C28'});">
                            </div>
                            
                            <!-- Banner Image (if available) -->
                            ${data.banner_image ?
                                `<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100px;
                                    background-image: url('${data.banner_image}'); background-size: cover; background-position: center;">
                                </div>` : ''}
                            
                            <!-- Profile Picture -->
                            <div style="position: absolute; z-index: 2000; top: 40px; left: 20px; width: 80px; height: 80px;
                                border-radius: 50%; background-color: #1e1e1e; border: 4px solid #1e1e1e; overflow: hidden;">
                                <img src="${data.pfp || 'default-avatar.png'}" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            
                            <!-- Username -->
                            <div style="position: absolute; top: 140px; left: 20px; color: white; font-weight: bold; font-size: 18px;">
                                ${username}
                            </div>
                        </div>
                        <div style="padding: 20px; background-color: #2a2a2a;">
                            <button class="close-profile-btn" style="margin-top: 10px;">Close</button>
                        </div>
                    </div>
                `;
                
                modal.innerHTML = profileContent;
                
                // Re-add event listener to new close button
                modal.querySelector('.close-profile-btn').addEventListener('click', () => {
                    document.body.removeChild(modal);
                });
            } else {
                console.error('Error fetching user profile:', data.message);
                modal.querySelector('.modal-content').innerHTML = `
                    <h3>Error loading profile</h3>
                    <p>Could not load ${username}'s profile.</p>
                    <button class="close-profile-btn">Close</button>
                `;
                
                // Re-add event listener to close button
                modal.querySelector('.close-profile-btn').addEventListener('click', () => {
                    document.body.removeChild(modal);
                });
            }
        })
        .catch(error => {
            console.error('Error fetching user profile:', error);
            modal.querySelector('.modal-content').innerHTML = `
                <h3>Error loading profile</h3>
                <p>Could not load ${username}'s profile.</p>
                <button class="close-profile-btn">Close</button>
            `;
            
            // Re-add event listener to close button
            modal.querySelector('.close-profile-btn').addEventListener('click', () => {
                document.body.removeChild(modal);
            });
        });
}

// Update event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Show modal and fetch user profile data
    if (document.getElementById('cog-icon')) {
        document.getElementById('cog-icon').addEventListener('click', function() {
            // Display the modal
            document.getElementById('user-settings-modal').style.display = 'block';
            
            // Fetch and populate user profile data for current user
            fetchUserProfile();
        });
    }
    
    // Close modal
    if (document.getElementById('close-user-settings')) {
        document.getElementById('close-user-settings').addEventListener('click', function() {
            document.getElementById('user-settings-modal').style.display = 'none';
        });
    }
    
    // Add input change event listeners if in settings modal
    if (document.getElementById('banner-image-url')) {
        document.getElementById('banner-image-url').addEventListener('input', updateProfilePreview);
        document.getElementById('pfp-url').addEventListener('input', updateProfilePreview);
        document.getElementById('gradient-color-1').addEventListener('input', updateProfilePreview);
        document.getElementById('gradient-color-2').addEventListener('input', updateProfilePreview);
        
        // Sync color picker with text input
        document.getElementById('gradient-color-1-picker').addEventListener('input', function() {
            document.getElementById('gradient-color-1').value = this.value;
            updateProfilePreview();
        });
        
        document.getElementById('gradient-color-2-picker').addEventListener('input', function() {
            document.getElementById('gradient-color-2').value = this.value;
            updateProfilePreview();
        });
        
        // Save settings
        document.getElementById('save-user-settings').addEventListener('click', saveUserSettings);
    }
});


    </script>
</body>
</html>


