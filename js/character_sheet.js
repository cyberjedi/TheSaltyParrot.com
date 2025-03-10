/**
 * Character Sheet JavaScript
 * Handles interactions for the character sheet component
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const editModal = document.getElementById('edit-character-modal');
    const switcherModal = document.getElementById('character-switcher-modal');
    const diceRollModal = document.getElementById('dice-roll-modal');
    const editBtn = document.getElementById('edit-character-btn');
    const switchBtn = document.getElementById('switch-character-btn');
    const closeBtns = document.querySelectorAll('.close-modal');
    const closeFormBtns = document.querySelectorAll('.close-modal-btn');
    const newCharacterBtn = document.getElementById('new-character-btn');
    const createNewFromSwitcherBtn = document.getElementById('create-new-from-switcher');
    const printBtn = document.getElementById('print-character-btn');
    const imageInput = document.getElementById('character_image');
    const imagePreview = document.getElementById('image-preview');
    const copyRollBtn = document.getElementById('copy-roll-btn');
    const sendRollDiscordBtn = document.getElementById('send-roll-discord-btn');
    const statBoxes = document.querySelectorAll('.stat-box');
    
    // Character data from PHP
    const characterData = window.character_data || {};
    
    // Discord authentication status - set by PHP
    const isAuthenticated = window.discord_authenticated || false;
    
    // Track the current roll result
    let currentRoll = {
        attributeName: '',
        attributeValue: 0,
        diceValue: 0,
        totalValue: 0
    };
    
    // Open edit modal when edit button is clicked
    if (editBtn) {
        editBtn.addEventListener('click', function() {
            if (isAuthenticated) {
                editModal.style.display = 'block';
            } else {
                alert('You must connect with Discord to edit characters.');
            }
        });
    }
    
    // Open switcher modal when switch button is clicked
    if (switchBtn) {
        switchBtn.addEventListener('click', function() {
            switcherModal.style.display = 'block';
        });
    }
    
    // Close modals when X is clicked
    if (closeBtns) {
        closeBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                editModal.style.display = 'none';
                switcherModal.style.display = 'none';
            });
        });
    }
    
    // Close modals when Cancel button is clicked
    if (closeFormBtns) {
        closeFormBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                editModal.style.display = 'none';
                switcherModal.style.display = 'none';
            });
        });
    }
    
    // Close modals when clicking outside of them
    window.addEventListener('click', function(event) {
        if (event.target == editModal) {
            editModal.style.display = 'none';
        }
        if (event.target == switcherModal) {
            switcherModal.style.display = 'none';
        }
    });
    
    // New Character button functionality
    if (newCharacterBtn) {
        newCharacterBtn.addEventListener('click', function() {
            if (!isAuthenticated) {
                alert('You must connect with Discord to create characters.');
                return;
            }
            
            // Reset the form for a new character
            document.getElementById('edit-character-form').reset();
            document.querySelector('input[name="character_id"]').value = '';
            document.getElementById('name').value = 'New Pirate';
            document.getElementById('strength').value = '0';
            document.getElementById('agility').value = '0';
            document.getElementById('presence').value = '0';
            document.getElementById('toughness').value = '0';
            document.getElementById('spirit').value = '0';
            
            // Reset image preview to default
            imagePreview.src = 'assets/TSP_default_character.jpg';
            
            // Show the modal
            editModal.style.display = 'block';
        });
    }
    
    // Create New from switcher button
    if (createNewFromSwitcherBtn) {
        createNewFromSwitcherBtn.addEventListener('click', function() {
            switcherModal.style.display = 'none';
            if (newCharacterBtn) {
                newCharacterBtn.click();
            }
        });
    }
    
    // Print button functionality
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
    
    // Image preview functionality
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Create a temporary image to check dimensions
                    const tempImg = new Image();
                    tempImg.src = e.target.result;
                    
                    tempImg.onload = function() {
                        // Update the image preview
                        imagePreview.src = e.target.result;
                    };
                };
                
                reader.readAsDataURL(this.files[0]);
                
                // Check file size
                const fileSize = this.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 2) {
                    alert('File size exceeds 2MB. Please choose a smaller image.');
                    this.value = ''; // Clear the input
                    imagePreview.src = imagePreview.getAttribute('data-original') || 'assets/TSP_default_character.jpg';
                }
            }
        });
        
        // Store original image path for reset
        if (imagePreview) {
            imagePreview.setAttribute('data-original', imagePreview.src);
        }
    }
    
    // Hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    }

    // Fix character images that fail to load
    const characterImages = document.querySelectorAll('.character-list-avatar img');
    characterImages.forEach(img => {
        img.onerror = function() {
            this.src = 'assets/TSP_default_character.jpg';
        };
    });

    // Properly constrain character list item dimensions
    const characterListItems = document.querySelectorAll('.character-list-item');
    characterListItems.forEach(item => {
        item.style.maxWidth = '100%';
        item.style.boxSizing = 'border-box';
    });
    
    // Add click event listeners for dice rolls
    // First try the dice icons specifically
    const diceIcons = document.querySelectorAll('.stat-roll-icon');
    
    if (diceIcons && diceIcons.length > 0) {
        console.log('Found dice icons:', diceIcons.length);
        
        diceIcons.forEach(icon => {
            icon.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent the event from bubbling to the stat box
                const parentBox = this.closest('.stat-box');
                handleDiceRoll(parentBox);
            });
        });
    }
    
    // Also attach to stat boxes as a fallback
    if (statBoxes && statBoxes.length > 0) {
        console.log('Found stat boxes:', statBoxes.length);
        
        statBoxes.forEach(box => {
            box.addEventListener('click', function(event) {
                // Only handle clicks directly on the box, not on children
                if (event.target === this) {
                    console.log('Stat box clicked:', this);
                    handleDiceRoll(this);
                }
            });
        });
    } else {
        console.error('No stat boxes found for dice rolling');
    }
    
    // Function to handle dice rolling
    function handleDiceRoll(statBox) {
        if (!statBox) {
            console.error('No stat box provided');
            return;
        }
        
        // Get attribute data from the stat box
        const attributeName = statBox.dataset.attribute;
        const attributeValue = parseInt(statBox.dataset.value || 0);
        
        console.log('Handling dice roll for:', attributeName, attributeValue);
        
        if (!attributeName) {
            console.error('No attribute name found in data-attribute');
            return;
        }
        
        // Generate random d20 roll (1-20)
        const diceValue = Math.floor(Math.random() * 20) + 1;
        const totalValue = diceValue + attributeValue;
        
        // Store current roll data
        currentRoll = {
            attributeName: attributeName.charAt(0).toUpperCase() + attributeName.slice(1),
            attributeValue: attributeValue,
            diceValue: diceValue,
            totalValue: totalValue
        };
        
        console.log('Generated roll:', currentRoll);
        
        // Check if modal elements exist
        const rollTitle = document.getElementById('roll-title');
        const diceValueEl = document.getElementById('dice-value');
        const attributeValueEl = document.getElementById('attribute-value');
        const totalValueEl = document.getElementById('total-value');
        
        if (!rollTitle || !diceValueEl || !attributeValueEl || !totalValueEl) {
            console.error('Missing modal elements');
            alert(`You rolled a ${currentRoll.attributeName} check: ${currentRoll.diceValue} (d20) + ${currentRoll.attributeValue} = ${currentRoll.totalValue}`);
            return;
        }
        
        // Update modal with roll results
        rollTitle.textContent = `${currentRoll.attributeName} Check`;
        diceValueEl.textContent = currentRoll.diceValue;
        attributeValueEl.textContent = currentRoll.attributeValue >= 0 ? `+${currentRoll.attributeValue}` : currentRoll.attributeValue;
        totalValueEl.textContent = currentRoll.totalValue;
        
        // Show the dice roll modal if it exists
        if (diceRollModal) {
            diceRollModal.style.display = 'block';
        } else {
            console.error('Dice roll modal not found');
            alert(`You rolled a ${currentRoll.attributeName} check: ${currentRoll.diceValue} (d20) + ${currentRoll.attributeValue} = ${currentRoll.totalValue}`);
        }
    }
    
    // Copy roll result to clipboard
    if (copyRollBtn) {
        copyRollBtn.addEventListener('click', function() {
            const rollText = `${characterData.name} rolled a ${currentRoll.attributeName} check: ${currentRoll.diceValue} (d20) + ${currentRoll.attributeValue} (${currentRoll.attributeName}) = ${currentRoll.totalValue}`;
            
            // Create a temporary textarea to copy text
            const textarea = document.createElement('textarea');
            textarea.value = rollText;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Show feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    }
    
    // Send roll result to Discord
    if (sendRollDiscordBtn) {
        console.log('Send to Discord button found:', sendRollDiscordBtn);
        
        sendRollDiscordBtn.addEventListener('click', function(event) {
            console.log('Send to Discord button clicked');
            event.preventDefault();
            
            if (!isAuthenticated) {
                console.log('User not authenticated with Discord');
                alert('You must connect with Discord to send rolls to a webhook.');
                return;
            }
            
            console.log('Preparing to send roll to Discord:', currentRoll);
            
            // Format content for Discord
            const rollContent = `
                <div class="attribute-roll">
                    <h3>${characterData.name} - ${currentRoll.attributeName} Check</h3>
                    <div class="roll-details">
                        <p>Dice Roll: ${currentRoll.diceValue}</p>
                        <p>${currentRoll.attributeName} Bonus: ${currentRoll.attributeValue}</p>
                        <p>Total: ${currentRoll.totalValue}</p>
                    </div>
                </div>
            `;
            
            // Show loading indicator on the button
            const originalButtonContent = sendRollDiscordBtn.innerHTML;
            sendRollDiscordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            sendRollDiscordBtn.disabled = true;
            
            // Get base URL from window object or use default
            const baseUrl = window.base_url || './';
            const webhookUrl = baseUrl + 'discord/webhooks.php?action=get_default_webhook&format=json';
            
            // Fetch default webhook from the server
            console.log('Fetching default webhook');
            
            fetch(webhookUrl)
                .then(response => {
                    console.log('Webhook response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`Server error: ${response.status}`);
                    }
                    return response.json().catch(error => {
                        console.error('Error parsing JSON response:', error);
                        throw new Error('Invalid response from server. Try again later.');
                    });
                })
                .then(data => {
                    console.log('Webhook response data:', data);
                    
                    // Handle error cases
                    if (data.status === 'error' || !data.webhook) {
                        console.error('Error fetching default webhook:', data.message || 'No default webhook found');
                        sendRollDiscordBtn.innerHTML = originalButtonContent;
                        sendRollDiscordBtn.disabled = false;
                        
                        // Show error alert
                        alert('Error: ' + (data.message || 'No default webhook found. Please configure a webhook in Discord settings.'));
                        return;
                    }
                    
                    // Get the default webhook ID
                    const webhookId = data.webhook.id;
                                
                    console.log('Sending to webhook ID:', webhookId);
                    
                    // Get send webhook URL
                    const sendWebhookUrl = baseUrl + 'discord/send_to_webhook.php';
                    
                    console.log('Sending webhook to:', sendWebhookUrl);
                    
                    // Send content to webhook with proper path
                    fetch(sendWebhookUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            webhook_id: webhookId,
                            content: rollContent,
                            generator_type: 'attribute_roll'
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.status === 'success') {
                            // Show success feedback
                            sendRollDiscordBtn.innerHTML = '<i class="fas fa-check"></i> Sent!';
                            sendRollDiscordBtn.disabled = false;
                            
                            setTimeout(() => {
                                sendRollDiscordBtn.innerHTML = originalButtonContent;
                            }, 2000);
                        } else {
                            // Show error
                            console.error('Error sending to Discord:', result.message);
                            sendRollDiscordBtn.innerHTML = originalButtonContent;
                            sendRollDiscordBtn.disabled = false;
                            alert('Error sending to Discord: ' + result.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        sendRollDiscordBtn.innerHTML = originalButtonContent;
                        sendRollDiscordBtn.disabled = false;
                        alert('Error sending to Discord. Check console for details.');
                    });
                })
                .catch(error => {
                    console.error('Error fetching default webhook:', error);
                    sendRollDiscordBtn.innerHTML = originalButtonContent;
                    sendRollDiscordBtn.disabled = false;
                    alert('Could not retrieve your Discord webhook configuration. Please check your Discord settings and try again.');
                });
        });
    }
    
    // Close dice roll modal when the X is clicked or when clicking outside
    if (diceRollModal) {
        // Close when X is clicked
        const closeRollModal = diceRollModal.querySelector('.close-modal');
        if (closeRollModal) {
            closeRollModal.addEventListener('click', function() {
                diceRollModal.style.display = 'none';
            });
        }
        
        // Close when clicking outside of the modal
        window.addEventListener('click', function(event) {
            if (event.target == diceRollModal) {
                diceRollModal.style.display = 'none';
            }
        });
    }
});
