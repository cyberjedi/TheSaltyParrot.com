/**
 * Character Sheet JavaScript
 * Handles interactions for the character sheet component
 */

console.log('Character sheet script loaded - before DOM ready');

// Track script loads to detect duplicates
if (typeof window.characterSheetLoadCount === 'undefined') {
    window.characterSheetLoadCount = 1;
} else {
    window.characterSheetLoadCount++;
}
console.log('Character sheet script load count:', window.characterSheetLoadCount);

// Add global error handler to catch and log JavaScript errors
window.onerror = function(message, source, lineno, colno, error) {
    console.error('JavaScript Error:', message);
    console.error('Source:', source);
    console.error('Line:', lineno, 'Column:', colno);
    console.error('Error object:', error);
    return false; // Allow default error handling as well
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Character sheet DOM ready event fired');
    
    // Log initial state
    console.log('Checking for UI elements to attach handlers:');
    // Get modal elements and log results
    const editModal = document.getElementById('edit-character-modal');
    console.log('editModal found:', !!editModal);
    
    const switcherModal = document.getElementById('character-switcher-modal');
    console.log('switcherModal found:', !!switcherModal);
    
    const diceRollModal = document.getElementById('dice-roll-modal');
    console.log('diceRollModal found:', !!diceRollModal);
    
    const editBtn = document.getElementById('edit-character-btn');
    console.log('editBtn found:', !!editBtn);
    
    const switchBtn = document.getElementById('switch-character-btn');
    console.log('switchBtn found:', !!switchBtn);
    
    const closeBtns = document.querySelectorAll('.close-modal');
    console.log('closeBtns found:', closeBtns.length);
    
    const closeFormBtns = document.querySelectorAll('.close-modal-btn');
    console.log('closeFormBtns found:', closeFormBtns.length);
    
    const newCharacterBtn = document.getElementById('new-character-btn');
    console.log('newCharacterBtn found:', !!newCharacterBtn);
    
    const createNewFromSwitcherBtn = document.getElementById('create-new-from-switcher');
    console.log('createNewFromSwitcherBtn found:', !!createNewFromSwitcherBtn);
    
    const printBtn = document.getElementById('print-character-btn');
    console.log('printBtn found:', !!printBtn);
    
    const imageInput = document.getElementById('character_image');
    console.log('imageInput found:', !!imageInput);
    
    const imagePreview = document.getElementById('image-preview');
    console.log('imagePreview found:', !!imagePreview);
    
    const copyRollBtn = document.getElementById('copy-roll-btn');
    console.log('copyRollBtn found:', !!copyRollBtn);
    
    const sendRollDiscordBtn = document.getElementById('send-roll-discord-btn');
    console.log('sendRollDiscordBtn found:', !!sendRollDiscordBtn);
    
    const statBoxes = document.querySelectorAll('.stat-box');
    console.log('statBoxes found:', statBoxes.length);
    
    // Character data from PHP
    const characterData = window.character_data || {};
    console.log('Character data from global scope:', characterData);
    
    // Discord authentication status - set by PHP
    const isAuthenticated = window.discord_authenticated || false;
    console.log('Discord authentication status:', isAuthenticated);
    console.log('Global discord_authenticated value:', window.discord_authenticated);
    
    // Track the current roll result
    let currentRoll = {
        attributeName: '',
        attributeValue: 0,
        diceValue: 0,
        totalValue: 0
    };
    
    // Open edit modal when edit button is clicked
    if (editBtn) {
        console.log('Attaching click handler to edit button');
        editBtn.addEventListener('click', function() {
            console.log('Edit button clicked');
            if (isAuthenticated) {
                // Remove active class from all modals first
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('active');
                });
                
                // Show the modal and make it active
                editModal.style.display = 'block';
                editModal.classList.add('active');
                console.log('Edit modal displayed');
            } else {
                alert('You must connect with Discord to edit characters.');
                console.log('Not authenticated for editing');
            }
        });
    } else {
        console.warn('Could not attach edit button handler - button not found');
    }
    
    // Open switcher modal when switch button is clicked
    if (switchBtn) {
        switchBtn.addEventListener('click', function() {
            // Remove active class from all modals first
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            
            // Show the modal and make it active
            switcherModal.style.display = 'block';
            switcherModal.classList.add('active');
            console.log('Switcher modal displayed');
        });
    }
    
    // Close modals when X is clicked
    if (closeBtns) {
        closeBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Hide all modals and remove active class
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                    modal.classList.remove('active');
                });
                console.log('Modals closed via close button');
            });
        });
    }
    
    // Close modals when Cancel button is clicked
    if (closeFormBtns) {
        closeFormBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Hide all modals and remove active class
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                    modal.classList.remove('active');
                });
                console.log('Modals closed via cancel button');
            });
        });
    }
    
    // Close modals when clicking outside of them
    window.addEventListener('click', function(event) {
        // Check if the clicked target is a modal background
        const clickedModal = event.target.closest('.modal');
        if (clickedModal && event.target === clickedModal) {
            // If clicked on the modal background (not content), close it
            clickedModal.style.display = 'none';
            clickedModal.classList.remove('active');
            console.log('Modal closed via outside click:', clickedModal.id);
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
        console.log('Attaching click handler to print button');
        printBtn.addEventListener('click', function() {
            console.log('Print button clicked');
            window.print();
        });
    } else {
        console.warn('Could not attach print button handler - button not found');
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
            // Remove active class from all modals first
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            
            // Show the modal and make it active
            diceRollModal.style.display = 'block';
            diceRollModal.classList.add('active');
            console.log('Dice roll modal displayed');
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
    
    // Send roll result to Discord - handled by discord_integration.js
    // This is just a stub for backward compatibility and proper initialization
    if (sendRollDiscordBtn) {
        console.log('Discord button: marking as initialized for discord_integration.js');
        // Add a data attribute to mark this as processed by character_sheet.js
        sendRollDiscordBtn.setAttribute('data-character-sheet-init', 'true');
        // Let other scripts know we've seen this button
        if (!window.characterSheetButtons) {
            window.characterSheetButtons = {};
        }
        window.characterSheetButtons.sendRollDiscordBtn = true;
    }
    
    // Close dice roll modal when the X is clicked or when clicking outside
    if (diceRollModal) {
        // Close when X is clicked
        const closeRollModal = diceRollModal.querySelector('.close-modal');
        if (closeRollModal) {
            closeRollModal.addEventListener('click', function() {
                diceRollModal.style.display = 'none';
                diceRollModal.classList.remove('active');
                console.log('Dice roll modal closed via X button');
            });
        }
        
        // Note: we don't need a separate window click handler here,
        // as the one above will handle all modals with the .modal class
    }
});
