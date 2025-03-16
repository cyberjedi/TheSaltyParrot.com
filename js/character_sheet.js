/**
 * Character Sheet JavaScript
 * Handles interactions for the character sheet component
 * Version: 2.0 - Improved with robust event handling and debug logging
 */

// Create a global debug flag - set to true for verbose logging
window.CS_DEBUG = true;

// Add a custom debug logger for character sheet
function csDebug(message, type = 'log') {
    if (window.CS_DEBUG || type === 'error' || type === 'warn') {
        console[type](`[Character Sheet] ${message}`);
    }
}

// Add extra tracing for click events throughout the page
if (window.CS_DEBUG) {
    // This will help us see ALL clicks and which elements they're targeting
    document.addEventListener('click', function(event) {
        console.log('========== CLICK EVENT DETECTED ==========');
        console.log('Click target:', event.target);
        console.log('Click target ID:', event.target.id);
        console.log('Click target tag:', event.target.tagName);
        console.log('Click target classes:', event.target.className);
        console.log('Click event was cancelled:', event.defaultPrevented);
        console.log('Click event phase:', 
            event.eventPhase === 1 ? 'CAPTURING' : 
            event.eventPhase === 2 ? 'AT_TARGET' : 
            event.eventPhase === 3 ? 'BUBBLING' : 'UNKNOWN');
        console.log('=========================================');
    }, true); // Capture phase to detect even if event is stopped in bubbling
}

// Track script loads to detect duplicates
if (typeof window.characterSheetLoadCount === 'undefined') {
    window.characterSheetLoadCount = 1;
} else {
    window.characterSheetLoadCount++;
}
csDebug(`Script load count: ${window.characterSheetLoadCount}`);

// Store references to important buttons to monitor their state
window.characterSheetButtonState = {};

// Make sure DOM is fully loaded before attaching any event handlers
if (document.readyState === 'loading') {
    csDebug('Document still loading, adding DOMContentLoaded listener');
    document.addEventListener('DOMContentLoaded', initializeCharacterSheet);
} else {
    csDebug('Document already loaded, calling init directly');
    // Small timeout to ensure everything else has loaded
    setTimeout(initializeCharacterSheet, 50);
}

function initializeCharacterSheet() {
    csDebug('Initializing character sheet');
    
    // Get all relevant DOM elements first
    const elements = getCharacterSheetElements();
    
    // Log found elements for debugging
    logElementStatus(elements);
    
    // Only proceed if we have the necessary elements
    if (!hasRequiredElements(elements)) {
        csDebug('Missing required elements, aborting initialization', 'error');
        return;
    }
    
    // Attach event handlers to buttons
    attachEventHandlers(elements);
    
    // Setup other functionality
    setupImagePreview(elements);
    setupAlertDismissal();
    setupImageErrorHandling();
    
    // Success!
    csDebug('Character sheet initialized successfully');
}

function getCharacterSheetElements() {
    return {
        // Modal elements
        editModal: document.getElementById('edit-character-modal'),
        switcherModal: document.getElementById('character-switcher-modal'),
        diceRollModal: document.getElementById('dice-roll-modal'),
        
        // Button elements
        editBtn: document.getElementById('edit-character-btn'),
        switchBtn: document.getElementById('switch-character-btn'),
        printBtn: document.getElementById('print-character-btn'),
        newCharacterBtn: document.getElementById('new-character-btn'),
        createNewFromSwitcherBtn: document.getElementById('create-new-from-switcher'),
        copyRollBtn: document.getElementById('copy-roll-btn'),
        sendRollDiscordBtn: document.getElementById('send-roll-discord-btn'),
        
        // Close buttons
        closeBtns: document.querySelectorAll('.close-modal'),
        closeFormBtns: document.querySelectorAll('.close-modal-btn'),
        
        // Other elements
        imageInput: document.getElementById('character_image'),
        imagePreview: document.getElementById('image-preview'),
        statRows: document.querySelectorAll('.stat-row'),
        diceButtons: document.querySelectorAll('.stat-roll-btn'),
        alerts: document.querySelectorAll('.alert')
    };
}

function logElementStatus(elements) {
    csDebug('--- Element Status Report ---');
    for (const [key, element] of Object.entries(elements)) {
        if (element === null) {
            csDebug(`${key}: NOT FOUND`, 'warn');
        } else if (element instanceof NodeList) {
            csDebug(`${key}: Found ${element.length} elements`);
        } else {
            csDebug(`${key}: Found`);
            
            // Store button references globally for debugging
            if (key.toLowerCase().includes('btn')) {
                window.characterSheetButtonState[key] = {
                    element: element,
                    hasListener: false
                };
            }
        }
    }
    csDebug('---------------------------');
}

function hasRequiredElements(elements) {
    // Return true even if some elements are missing
    // We'll handle individual functionality based on what's available
    return true;
}

function attachEventHandlers(elements) {
    // Track the current roll result
    let currentRoll = {
        attributeName: '',
        attributeValue: 0,
        diceValue: 0,
        totalValue: 0
    };
    
    // Character data from PHP
    const characterData = window.character_data || {};
    
    // Discord authentication status - set by PHP
    const isAuthenticated = window.discord_authenticated || false;
    
    // --- Button Event Handlers ---
    
    // Edit button
    if (elements.editBtn) {
        csDebug('Attaching handler to edit button');
        attachSafeClickHandler(elements.editBtn, function() {
            csDebug('Edit button clicked');
            if (isAuthenticated) {
                // Remove active class from all modals first
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('active');
                });
                
                // Show the modal and make it active
                if (elements.editModal) {
                    elements.editModal.style.display = 'block';
                    elements.editModal.classList.add('active');
                    csDebug('Edit modal displayed');
                }
            } else {
                alert('You must connect with Discord to edit characters.');
                csDebug('Not authenticated for editing');
            }
        });
    }
    
    // Switch character button
    if (elements.switchBtn) {
        csDebug('Attaching handler to switch button');
        attachSafeClickHandler(elements.switchBtn, function() {
            csDebug('Switch button clicked');
            // Remove active class from all modals first
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            
            // Show the modal and make it active
            if (elements.switcherModal) {
                elements.switcherModal.style.display = 'block';
                elements.switcherModal.classList.add('active');
                csDebug('Switcher modal displayed');
            }
        });
    }
    
    // Print button
    if (elements.printBtn) {
        csDebug('Attaching handler to print button');
        attachSafeClickHandler(elements.printBtn, function() {
            csDebug('Print button clicked');
            window.print();
        });
    }
    
    // New character button
    if (elements.newCharacterBtn) {
        csDebug('Attaching handler to new character button');
        attachSafeClickHandler(elements.newCharacterBtn, function() {
            csDebug('New character button clicked');
            if (!isAuthenticated) {
                alert('You must connect with Discord to create characters.');
                return;
            }
            
            // Get form elements
            const form = document.getElementById('edit-character-form');
            if (!form) {
                csDebug('Form not found', 'error');
                return;
            }
            
            // Reset the form for a new character
            form.reset();
            document.querySelector('input[name="character_id"]').value = '';
            document.getElementById('name').value = 'New Pirate';
            document.getElementById('strength').value = '0';
            document.getElementById('agility').value = '0';
            document.getElementById('presence').value = '0';
            document.getElementById('toughness').value = '0';
            document.getElementById('spirit').value = '0';
            
            // Reset image preview to default
            if (elements.imagePreview) {
                elements.imagePreview.src = 'assets/TSP_default_character.jpg';
            }
            
            // Show the modal
            if (elements.editModal) {
                elements.editModal.style.display = 'block';
                elements.editModal.classList.add('active');
            }
        });
    }
    
    // Create new from switcher button
    if (elements.createNewFromSwitcherBtn) {
        csDebug('Attaching handler to create new from switcher button');
        attachSafeClickHandler(elements.createNewFromSwitcherBtn, function() {
            csDebug('Create new from switcher button clicked');
            if (elements.switcherModal) {
                elements.switcherModal.style.display = 'none';
            }
            if (elements.newCharacterBtn) {
                elements.newCharacterBtn.click();
            }
        });
    }
    
    // Close buttons for modals
    if (elements.closeBtns && elements.closeBtns.length > 0) {
        csDebug('Attaching handlers to close buttons');
        elements.closeBtns.forEach(function(btn) {
            attachSafeClickHandler(btn, function() {
                csDebug('Close button clicked');
                // Hide all modals and remove active class
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                    modal.classList.remove('active');
                });
            });
        });
    }
    
    // Close form buttons
    if (elements.closeFormBtns && elements.closeFormBtns.length > 0) {
        csDebug('Attaching handlers to close form buttons');
        elements.closeFormBtns.forEach(function(btn) {
            attachSafeClickHandler(btn, function() {
                csDebug('Close form button clicked');
                // Hide all modals and remove active class
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                    modal.classList.remove('active');
                });
            });
        });
    }
    
    // Copy roll button
    if (elements.copyRollBtn) {
        csDebug('Attaching handler to copy roll button');
        attachSafeClickHandler(elements.copyRollBtn, function() {
            csDebug('Copy roll button clicked');
            if (!currentRoll || !characterData.name) {
                csDebug('No roll data available', 'warn');
                return;
            }
            
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
    
    // Add click event listeners for dice rolls in table layout
    if (elements.diceButtons && elements.diceButtons.length > 0) {
        csDebug('Setting up dice roll buttons');
        
        elements.diceButtons.forEach(button => {
            attachSafeClickHandler(button, function(event) {
                csDebug('Dice button clicked');
                event.stopPropagation(); // Prevent the event from bubbling up
                const parentRow = this.closest('.stat-row');
                handleDiceRoll(parentRow);
            });
        });
    }
    
    // Also attach to stat rows as a fallback
    if (elements.statRows && elements.statRows.length > 0) {
        csDebug('Setting up stat row click handlers');
        
        elements.statRows.forEach(row => {
            attachSafeClickHandler(row, function(event) {
                // Only handle clicks directly on the row, not on buttons or other interactive elements
                if (event.target.tagName !== 'BUTTON' && !event.target.closest('button')) {
                    csDebug('Stat row clicked');
                    handleDiceRoll(this);
                }
            });
        });
    }
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        // Check if the clicked target is a modal background
        const clickedModal = event.target.closest('.modal');
        if (clickedModal && event.target === clickedModal) {
            // If clicked on the modal background (not content), close it
            clickedModal.style.display = 'none';
            clickedModal.classList.remove('active');
            csDebug('Modal closed via outside click');
        }
    });
    
    // Function to handle dice rolling
    function handleDiceRoll(statBox) {
        if (!statBox) {
            csDebug('No stat box provided', 'error');
            return;
        }
        
        // Get attribute data from the stat box
        const attributeName = statBox.dataset.attribute;
        const attributeValue = parseInt(statBox.dataset.value || 0);
        
        csDebug(`Rolling dice for ${attributeName} (${attributeValue})`);
        
        if (!attributeName) {
            csDebug('No attribute name found in data-attribute', 'error');
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
        
        // Emit a custom event for dice rolls that other modules (like Discord) can listen for
        const rollEventData = {
            characterName: characterData.name,
            attributeName: formattedAttributeName,
            attributeValue: attributeValue,
            diceValue: diceValue,
            totalValue: totalValue
        };
        
        // Dispatch event for other modules to listen for
        const rollEvent = new CustomEvent('characterDiceRoll', {
            detail: rollEventData,
            bubbles: true
        });
        document.dispatchEvent(rollEvent);
        csDebug('Dispatched characterDiceRoll event');
        
        // Legacy support
        if (window.updateCurrentRoll) {
            window.updateCurrentRoll(rollEventData);
        }
        
        csDebug(`Generated roll: ${JSON.stringify(currentRoll)}`);
        
        // Check if modal elements exist
        const rollTitle = document.getElementById('roll-title');
        const diceValueEl = document.getElementById('dice-value');
        const attributeValueEl = document.getElementById('attribute-value');
        const totalValueEl = document.getElementById('total-value');
        
        if (!rollTitle || !diceValueEl || !attributeValueEl || !totalValueEl) {
            csDebug('Missing modal elements', 'error');
            alert(`You rolled a ${currentRoll.attributeName} check: ${currentRoll.diceValue} (d20) + ${currentRoll.attributeValue} = ${currentRoll.totalValue}`);
            return;
        }
        
        // Update modal with roll results
        rollTitle.textContent = `${currentRoll.attributeName} Check`;
        diceValueEl.textContent = currentRoll.diceValue;
        attributeValueEl.textContent = currentRoll.attributeValue >= 0 ? `+${currentRoll.attributeValue}` : currentRoll.attributeValue;
        totalValueEl.textContent = currentRoll.totalValue;
        
        // Show the dice roll modal if it exists
        if (elements.diceRollModal) {
            // Remove active class from all modals first
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            
            // Show the modal and make it active
            elements.diceRollModal.style.display = 'block';
            elements.diceRollModal.classList.add('active');
            csDebug('Dice roll modal displayed');
        } else {
            csDebug('Dice roll modal not found', 'error');
            alert(`You rolled a ${currentRoll.attributeName} check: ${currentRoll.diceValue} (d20) + ${currentRoll.attributeValue} = ${currentRoll.totalValue}`);
        }
    }
}

function setupImagePreview(elements) {
    // Image preview functionality
    if (elements.imageInput && elements.imagePreview) {
        csDebug('Setting up image preview');
        
        elements.imageInput.addEventListener('change', function() {
            csDebug('Image input changed');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Create a temporary image to check dimensions
                    const tempImg = new Image();
                    tempImg.src = e.target.result;
                    
                    tempImg.onload = function() {
                        // Update the image preview
                        elements.imagePreview.src = e.target.result;
                    };
                };
                
                reader.readAsDataURL(this.files[0]);
                
                // Check file size
                const fileSize = this.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 2) {
                    alert('File size exceeds 2MB. Please choose a smaller image.');
                    this.value = ''; // Clear the input
                    elements.imagePreview.src = elements.imagePreview.getAttribute('data-original') || 'assets/TSP_default_character.jpg';
                }
            }
        });
        
        // Store original image path for reset
        elements.imagePreview.setAttribute('data-original', elements.imagePreview.src);
    }
}

function setupAlertDismissal() {
    // Hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        csDebug(`Setting up auto-dismissal for ${alerts.length} alerts`);
        setTimeout(function() {
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    }
}

function setupImageErrorHandling() {
    // Fix character images that fail to load
    const characterImages = document.querySelectorAll('.character-list-avatar img, .character-image img');
    csDebug(`Setting up error handling for ${characterImages.length} character images`);
    
    characterImages.forEach(img => {
        img.onerror = function() {
            csDebug('Image failed to load, using default', 'warn');
            this.src = 'assets/TSP_default_character.jpg';
        };
    });
}

// Helper function to safely attach click handlers and prevent duplicates
function attachSafeClickHandler(element, handler) {
    if (!element) {
        csDebug('Cannot attach handler to null element', 'error');
        return;
    }
    
    // Create a unique ID for the handler if element doesn't have one
    if (!element.id) {
        element.id = 'cs-elem-' + Math.random().toString(36).substr(2, 9);
    }
    
    // Log the element we're attaching to
    console.log(`🔍 ATTACHING HANDLER TO: ${element.id}`, element);
    
    // Check if we already attached a handler to this element
    if (element.getAttribute('data-has-handler') === 'true') {
        console.warn(`⚠️ Element ${element.id} already has a handler, removing first`);
        // Create a clean clone to remove all event listeners
        const clone = element.cloneNode(true);
        element.parentNode.replaceChild(clone, element);
        element = clone; // Update reference to the new element
    }
    
    // Create a wrapper handler that logs before and after execution
    const tracingHandler = function(event) {
        console.log(`🎯 CLICK DETECTED on #${element.id}`);
        
        try {
            // Call the original handler with proper "this" context
            const result = handler.call(this, event);
            console.log(`✅ Handler for #${element.id} completed successfully`);
            return result;
        } catch (error) {
            console.error(`❌ ERROR in handler for #${element.id}:`, error);
            throw error; // Re-throw so it's visible in console
        }
    };
    
    // Attach the tracing handler instead of the original
    element.addEventListener('click', tracingHandler);
    console.log(`✅ Attached traced handler to #${element.id}`);
    
    // Mark this element as having a handler
    element.setAttribute('data-has-handler', 'true');
    
    // Update the button state for debugging
    const buttonId = element.id;
    if (buttonId && window.characterSheetButtonState[buttonId]) {
        window.characterSheetButtonState[buttonId].hasListener = true;
    }
    
    csDebug(`Attached handler to ${element.id || 'element'}`);
}

// Check button status on a regular interval for debugging
if (window.CS_DEBUG) {
    setInterval(function() {
        for (const [key, state] of Object.entries(window.characterSheetButtonState)) {
            if (!state.hasListener) {
                csDebug(`Button ${key} still has no listener!`, 'warn');
            }
        }
    }, 5000);
}

// Debug function to check for script conflicts
function debugCheckScriptConflicts() {
    console.group('🔍 CHECKING FOR SCRIPT CONFLICTS');
    
    // Check for common script conflicts
    console.log('GLOBAL EVENT HANDLERS:');
    
    // Check critical buttons
    const criticalButtons = [
        'edit-character-btn',
        'switch-character-btn',
        'print-character-btn',
        'new-character-btn',
        'send-roll-discord-btn'
    ];
    
    criticalButtons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            console.log(`Button #${btnId} exists and is visible:`, btn.offsetParent !== null);
            console.log(`Button #${btnId} has data-has-handler:`, btn.getAttribute('data-has-handler'));
            
            // Check for any click handlers by cloning and seeing if events still work
            const clone = btn.cloneNode(true);
            const originalParent = btn.parentNode;
            if (originalParent) {
                // Replace with clone temporarily
                originalParent.replaceChild(clone, btn);
                console.log(`Tested #${btnId} by cloning - if handlers were lost, button may have inline script handlers`);
                // Put back the original
                originalParent.replaceChild(btn, clone);
            }
        } else {
            console.warn(`⚠️ Button #${btnId} not found in DOM!`);
        }
    });
    
    // Check for any elements with inline onclick attributes
    const elementsWithInlineHandlers = document.querySelectorAll('[onclick]');
    if (elementsWithInlineHandlers.length > 0) {
        console.warn(`⚠️ Found ${elementsWithInlineHandlers.length} elements with inline onclick handlers!`);
        elementsWithInlineHandlers.forEach(el => {
            console.log(`Element with inline onclick:`, el);
        });
    }
    
    // Check if any custom click events are blocked
    const testButton = document.createElement('button');
    testButton.id = 'test-event-propagation-btn';
    testButton.style.display = 'none';
    document.body.appendChild(testButton);
    
    let testEventWorked = false;
    testButton.addEventListener('click', function() {
        testEventWorked = true;
    });
    
    testButton.click();
    console.log(`Test click event propagation works: ${testEventWorked}`);
    document.body.removeChild(testButton);
    
    console.groupEnd();
}

// Run the script conflict check after a delay to let everything initialize
setTimeout(debugCheckScriptConflicts, 1000);
