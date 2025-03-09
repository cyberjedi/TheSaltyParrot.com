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
    
    // Add click event listeners to stat boxes for dice rolls
    if (statBoxes) {
        statBoxes.forEach(box => {
            box.addEventListener('click', function() {
                // Get attribute data
                const attributeName = this.dataset.attribute;
                const attributeValue = parseInt(this.dataset.value);
                
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
                
                // Update modal with roll results
                document.getElementById('roll-title').textContent = `${currentRoll.attributeName} Check`;
                document.getElementById('dice-value').textContent = currentRoll.diceValue;
                document.getElementById('attribute-value').textContent = currentRoll.attributeValue >= 0 ? `+${currentRoll.attributeValue}` : currentRoll.attributeValue;
                document.getElementById('total-value').textContent = currentRoll.totalValue;
                
                // Show the dice roll modal
                diceRollModal.style.display = 'block';
            });
        });
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
        sendRollDiscordBtn.addEventListener('click', function() {
            if (!isAuthenticated) {
                alert('You must connect with Discord to send rolls to a webhook.');
                return;
            }
            
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
            
            // Render webhook selector
            // Open a small modal or dropdown to select from available webhooks
            const selector = document.createElement('div');
            selector.className = 'webhook-selector-overlay';
            selector.innerHTML = '<div class="webhook-selector-content">Loading webhooks...</div>';
            document.body.appendChild(selector);
            
            // Fetch webhooks from the server
            fetch('/discord/webhooks.php?action=get_webhooks&format=json')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.webhooks && data.webhooks.length > 0) {
                        let webhookHTML = '<h4>Select Discord Channel</h4><div class="webhook-options">';
                        data.webhooks.forEach(webhook => {
                            webhookHTML += `
                                <div class="webhook-option" data-webhook-id="${webhook.id}">
                                    <i class="fab fa-discord"></i> #${webhook.channel_name}
                                </div>
                            `;
                        });
                        webhookHTML += '</div>';
                        webhookHTML += '<div class="webhook-actions"><button id="cancel-webhook-btn" class="btn btn-secondary">Cancel</button></div>';
                        
                        selector.querySelector('.webhook-selector-content').innerHTML = webhookHTML;
                        
                        // Add event listeners to webhook options
                        selector.querySelectorAll('.webhook-option').forEach(option => {
                            option.addEventListener('click', function() {
                                const webhookId = this.dataset.webhookId;
                                
                                // Send content to webhook
                                fetch('/discord/send_to_webhook.php', {
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
                                        // Remove selector overlay
                                        document.body.removeChild(selector);
                                        
                                        // Show success feedback
                                        const originalText = sendRollDiscordBtn.innerHTML;
                                        sendRollDiscordBtn.innerHTML = '<i class="fas fa-check"></i> Sent!';
                                        setTimeout(() => {
                                            sendRollDiscordBtn.innerHTML = originalText;
                                        }, 2000);
                                    } else {
                                        alert('Error sending to Discord: ' + result.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Error sending to Discord. Check console for details.');
                                });
                            });
                        });
                        
                        // Add cancel button event listener
                        selector.querySelector('#cancel-webhook-btn').addEventListener('click', function() {
                            document.body.removeChild(selector);
                        });
                    } else {
                        selector.querySelector('.webhook-selector-content').innerHTML = `
                            <div class="webhook-error">
                                <p>You have no Discord webhooks set up.</p>
                                <p><a href="/discord/webhooks.php">Configure webhooks</a> to send rolls to Discord.</p>
                                <button id="cancel-webhook-btn" class="btn btn-secondary">Close</button>
                            </div>
                        `;
                        
                        // Add cancel button event listener
                        selector.querySelector('#cancel-webhook-btn').addEventListener('click', function() {
                            document.body.removeChild(selector);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching webhooks:', error);
                    selector.querySelector('.webhook-selector-content').innerHTML = `
                        <div class="webhook-error">
                            <p>Error fetching webhooks. Please try again.</p>
                            <button id="cancel-webhook-btn" class="btn btn-secondary">Close</button>
                        </div>
                    `;
                    
                    // Add cancel button event listener
                    selector.querySelector('#cancel-webhook-btn').addEventListener('click', function() {
                        document.body.removeChild(selector);
                    });
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
