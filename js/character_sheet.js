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
    
    // Add click event listeners for dice rolls in table layout
    const diceButtons = document.querySelectorAll('.stat-roll-btn');
    const statRows = document.querySelectorAll('.stat-row');
    
    if (diceButtons && diceButtons.length > 0) {
        console.log('Found dice buttons:', diceButtons.length);
        
        diceButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent the event from bubbling
                const parentRow = this.closest('.stat-row');
                handleDiceRoll(parentRow);
            });
        });
    }
    
    // Also attach to stat rows as a fallback
    if (statRows && statRows.length > 0) {
        console.log('Found stat rows:', statRows.length);
        
        statRows.forEach(row => {
            row.addEventListener('click', function(event) {
                // Only handle clicks directly on the row, not on buttons or other interactive elements
                if (event.target.tagName !== 'BUTTON' && !event.target.closest('button')) {
                    console.log('Stat row clicked:', this);
                    handleDiceRoll(this);
                }
            });
        });
    } else {
        console.error('No stat rows found for dice rolling');
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
        
        // Format the attribute name (capitalize first letter)
        const formattedAttributeName = attributeName.charAt(0).toUpperCase() + attributeName.slice(1);
        
        // Store current roll data
        currentRoll = {
            attributeName: formattedAttributeName,
            attributeValue: attributeValue,
            diceValue: diceValue,
            totalValue: totalValue
        };
        
        // Update the roll data for Discord webhook if available
        if (window.updateCurrentRoll) {
            window.updateCurrentRoll({
                characterName: characterData.name,
                attributeName: formattedAttributeName,
                attributeValue: attributeValue,
                diceValue: diceValue,
                totalValue: totalValue
            });
        }
        
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
    
    // Send roll result to Discord - actual implementation is in discord_integration.js
    // This is just a stub for backward compatibility
    if (sendRollDiscordBtn) {
        console.log('Discord button handler will be attached by discord_integration.js');
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
