// Current guild ID and channel ID
        let currentChannelId = null;
function htmlEncode(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '<')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}


                              let profilePictureCache = {}; // Global cache for profile pictures
// Function to fetch messages for the current channel
// Function to fetch messages for the current channel
// Global variable to store the currently selected message ID
let selectedMessageId = null;

// Function to show the message context menu
function showMessageContextMenu(event, messageId) {
    event.preventDefault(); // Prevent the default context menu
    selectedMessageId = messageId; // Store the selected message ID

    const contextMenu = document.getElementById('message-context-menu');
    contextMenu.style.display = 'block';
    contextMenu.style.left = `${event.pageX}px`;
    contextMenu.style.top = `${event.pageY}px`;
}

// Function to hide the message context menu
function hideMessageContextMenu() {
    const contextMenu = document.getElementById('message-context-menu');
    contextMenu.style.display = 'none';
}

// Add event listener to hide the context menu when clicking outside
document.addEventListener('click', hideMessageContextMenu);

// Add event listener to handle the "Delete Message" option
document.getElementById('delete-message-option').addEventListener('click', () => {
    if (selectedMessageId) {
        deleteMessage(selectedMessageId);
    }
});

// Function to delete a message
function deleteMessage(messageId) {
    if (!messageId) return;

    const formData = new FormData();
    formData.append('message_id', messageId);

    fetch('api/v1/delete_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            fetchMessages(); // Refresh the messages
        } else {
            alert('Error deleting message: ' + data.message);
        }
    })
    .catch(error => console.error('Error deleting message:', error));
}

// Modify the fetchMessages function to add context menu functionality
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
                    messageDiv.dataset.messageId = msg.id; // Add message ID to the div

                    // Format timestamp
                    const timestamp = new Date(msg.timestamp);
                    const formattedTime = timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                    // Convert URLs to clickable links
                    const linkifiedContent = linkifyText(msg.content);

                    // Get the profile picture from the cache (or use a default if not cached)
                    const pfpUrl = profilePictureCache[msg.user_id] || 'default-avatar.png';

                    // Create message HTML with PFP
                    messageDiv.innerHTML = `
                        <div class="message-header">
                            <img src="${pfpUrl}" alt="${msg.username}" class="message-pfp" width=40 height=40>
                            <strong>${htmlEncode(msg.username)}</strong>
                            <span class="timestamp">${formattedTime}</span>
                        </div>
                        <div class="message-content">${linkifiedContent}</div>
                    `;

                    // Add right-click event listener to show the context menu
                    messageDiv.addEventListener('contextmenu', (e) => {
                        showMessageContextMenu(e, msg.id);
                    });

                    messageContainer.appendChild(messageDiv);
                });

                // Scroll to bottom
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        })
        .catch(error => console.error('Error fetching messages:', error));
}
// Function to fetch user profile picture
function fetchUserProfilePicture(userId) {
    return fetch(`api/v1/get_id_profile.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                return {
                    pfp: data.pfp || 'default-avatar.png',
                    status: data.user_status || 0 // Default to offline if status is not available
                };
            } else {
                console.error('Error fetching profile picture:', data.message);
                return { pfp: 'default-avatar.png', status: 0 };
            }
        })
        .catch(error => {
            console.error('Error fetching profile picture:', error);
            return { pfp: 'default-avatar.png', status: 0 };
        });
}
                      
                      // Function to fetch all members' profile pictures and cache them
function cacheAllProfilePictures() {
    if (!currentGuildId) return;
    fetch(`api/v1/guild_members.php?guild_id=${currentGuildId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                // Fetch profile pictures for all members
                const fetchPromises = data.members.map(member => {
                    return fetchUserProfilePicture(member.id)
                        .then(pfpUrl => {
                            profilePictureCache[member.id] = pfpUrl; // Cache the profile picture
                        });
                });

                // Wait for all profile pictures to be fetched
                Promise.all(fetchPromises)
                    .then(() => {
                        console.log('All profile pictures cached successfully.');
                    })
                    .catch(error => console.error('Error caching profile pictures:', error));
            } else {
                console.error('Error fetching members:', data.message);
            }
        })
        .catch(error => console.error('Error fetching members:', error));
}
// Function to convert URLs in text to clickable links
function linkifyText(text) {
    // Enhanced regular expression to match URLs
    // This will match URLs with or without protocol (http/https)
    // It handles www. prefixes and common TLDs
    const urlRegex = /(https?:\/\/|www\.)[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+([\/?#][\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.%]+)?/gi;
    
    // Replace URLs with anchor tags
    return text.replace(urlRegex, function(url) {
        // Trim trailing punctuation that might be part of the sentence but not the URL
        const trimmedUrl = url.replace(/[.,;:!?)]+$/, '');
        const punctuation = url.substring(trimmedUrl.length);
        
        // Add protocol if it's missing (for www. links)
        const href = trimmedUrl.startsWith('http') ? trimmedUrl : 'http://' + trimmedUrl;
        
        return `<a href="${href}" target="_blank" rel="noopener noreferrer">${trimmedUrl}</a>${punctuation}`;
    });
}

        // Function to send a message
function sendMessage() {
    if (!currentChannelId) return;

    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();

    if (message !== '') {
        const formData = new FormData();
        formData.append('channel_id', currentChannelId);
        formData.append('message', htmlEncode(message));

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
                    categoryDiv.innerHTML = `<strong>${htmlEncode(category.name)}</strong>`;
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
                        channelItem.innerHTML = `# ${htmlEncode(channel.name)}`;
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
          
        cacheAllProfilePictures(); // Cache all profile pictures at the start
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
    if (!userId) {
        console.error('currentUserId is not defined');
        return;
    }

    console.log('Fetching profile for user ID:', userId);

    fetch(`api/v1/get_id_profile.php?user_id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('API response data:', data);

            if (data.status === "success") {
                console.log('Profile data fetched successfully:', data);

                // Populate input fields with fetched data
                const bannerImageInput = document.getElementById('banner-image-url');
                const pfpInput = document.getElementById('pfp-url');
                const gradientColor1Input = document.getElementById('gradient-color-1');
                const gradientColor2Input = document.getElementById('gradient-color-2');
                const statusDropdown = document.getElementById('user-status');

                if (bannerImageInput) bannerImageInput.value = data.banner_image || '';
                if (pfpInput) pfpInput.value = data.pfp || '';
                if (gradientColor1Input) gradientColor1Input.value = data.gradient_color_1 || '#089C44';
                if (gradientColor2Input) gradientColor2Input.value = data.gradient_color_2 || '#065C28';
                if (statusDropdown) statusDropdown.value = data.user_status || 0; // Set the dropdown value

                // Update the color pickers
                const gradientColor1Picker = document.getElementById('gradient-color-1-picker');
                const gradientColor2Picker = document.getElementById('gradient-color-2-picker');

                if (gradientColor1Picker) gradientColor1Picker.value = data.gradient_color_1 || '#089C44';
                if (gradientColor2Picker) gradientColor2Picker.value = data.gradient_color_2 || '#065C28';

                // Update the profile preview
                updateProfilePreview();
            } else {
                console.error('Error fetching profile:', data.message);
                alert('Error fetching profile: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching profile:', error);
            alert('Error fetching profile: ' + error.message);
        });
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
    const status = document.getElementById('user-status').value;

    console.log('Updating profile preview with:', { bannerUrl, pfpUrl, color1, color2, status });

    // Update gradient background
    const gradientDiv = document.getElementById('preview-gradient');
    if (gradientDiv) {
        gradientDiv.style.background = `linear-gradient(to bottom, ${color1 || '#089C44'}, ${color2 || '#065C28'})`;
    }

    // Update banner image
    const bannerDiv = document.getElementById('preview-banner');
    if (bannerDiv) {
        if (bannerUrl) {
            bannerDiv.style.backgroundImage = `url('${bannerUrl}')`;
        } else {
            bannerDiv.style.backgroundImage = 'none';
        }
    }

    // Update profile picture
    const pfpDiv = document.getElementById('preview-pfp');
    if (pfpDiv) {
        if (pfpUrl) {
            pfpDiv.style.backgroundImage = `url('${pfpUrl}')`;
        } else {
            pfpDiv.style.backgroundImage = 'none';
        }

        // Update status indicator
        const statusIndicator = pfpDiv.querySelector('.status-indicator');
        if (statusIndicator) {
            statusIndicator.className = `status-indicator status-${status}`;
        }
    }
}
// Function to save user settings - updated to use user_id parameter
function saveUserSettings() {
    // Get profile data
    const bannerImage = document.getElementById('banner-image-url').value;
    const pfp = document.getElementById('pfp-url').value;
    const gradientColor1 = document.getElementById('gradient-color-1').value;
    const gradientColor2 = document.getElementById('gradient-color-2').value;

    // Get status data
    const status = document.getElementById('user-status').value;

    // Create FormData for profile update
    const profileFormData = new FormData();
    profileFormData.append('user_id', userId);
    profileFormData.append('banner_image', bannerImage);
    profileFormData.append('pfp', pfp);
    profileFormData.append('gradient_color_1', gradientColor1);
    profileFormData.append('gradient_color_2', gradientColor2);

    // Create FormData for status update
    const statusFormData = new FormData();
    statusFormData.append('user_id', userId);
    statusFormData.append('status', status);

    // Send profile update request
    fetch('api/v1/updateProfile.php', {
        method: 'POST',
        body: profileFormData
    })
    .then(response => response.json())
    .then(profileData => {
        if (profileData.status === "success") {
            console.log('Profile updated successfully:', profileData);

            // Send status update request
            return fetch('api/v1/setUserStatus.php', {
                method: 'POST',
                body: statusFormData
            });
        } else {
            throw new Error('Profile update failed: ' + profileData.message);
        }
    })
    .then(response => response.json())
    .then(statusData => {
        if (statusData.status === "success") {
            console.log('Status updated successfully:', statusData);
            alert('Profile and status updated successfully!');
            // Optionally, close the modal or refresh the preview
            document.getElementById('user-settings-modal').style.display = 'none';
        } else {
            throw new Error('Status update failed: ' + statusData.message);
        }
    })
    .catch(error => {
        console.error('Error updating profile or status:', error);
        alert('Error updating profile or status: ' + error.message);
    });
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
                            usernameSpan.textContent = htmlEncode(member.username);
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
            <h3>Loading ${htmlEncode(username)}'s profile...</h3>
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
                // Fetch user status
                fetch(`api/v1/getUserStatus.php?user_id=${userId}`)
                    .then(response => response.json())
                    .then(statusData => {
                        const userStatus = statusData.user_status || 0;

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
                                            background-image: url('${htmlEncode(data.banner_image)}'); background-size: cover; background-position: center;">
                                        </div>` : ''}
                                    
                                    <!-- Profile Picture -->
                                    <div style="position: absolute; z-index: 2000; top: 40px; left: 20px; width: 84px; height: 84px;
                                          ">
                                        <img src="${htmlEncode(data.pfp || 'default-avatar.png')}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 4px solid #1e1e1e;">
                                        <div class="status-indicator status-${userStatus}" style="z-index:10000";></div>
                                    </div>
                                    
                                    <!-- Username -->
                                    <div style="position: absolute; top: 140px; left: 20px; color: white; font-weight: bold; font-size: 18px;">
                                        ${htmlEncode(username)}
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
      
      // Initialize the page
function initializePage() {
    console.log("Current Guild ID:", currentGuildId);
    if (currentGuildId) {
        cacheAllProfilePictures(); // Cache all profile pictures at the start
        fetchChannels();
        setInterval(fetchMessages, 5000); // Fetch messages every 5 seconds
    }
}

// Call initializePage when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', initializePage);
      
      
      document.addEventListener('DOMContentLoaded', function() {
    const statusDropdown = document.getElementById('user-status');
    if (statusDropdown) {
        statusDropdown.addEventListener('change', function() {
            const newStatus = this.value;

            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('status', newStatus);

            fetch('api/v1/setUserStatus.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    console.log('Status updated successfully:', data);
                    alert('Status updated successfully!');
                } else {
                    console.error('Error updating status:', data.message);
                    alert('Error updating status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error updating status:', error);
                alert('Error updating status: ' + error.message);
            });
        });
    }
});
      
    }
});