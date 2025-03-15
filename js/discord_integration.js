/**
 * Discord Integration for Character Sheet
 * This file handles the Discord webhook integration specifically
 */

// Create a namespace for Discord integration
window.DiscordIntegration = {
    // Track initialization status
    initialized: false,
    
    // Initialize the Discord integration
    init: function() {
        if (this.initialized) {
            console.log('Discord integration already initialized, skipping');
            return;
        }
        
        console.log('Discord integration initializing');
        this.setupEventHandlers();
        this.initialized = true;
    },
    
    // Set up event handlers for Discord integration
    setupEventHandlers: function() {
        // CRITICAL FIX: Only attach to specific Discord buttons, not the entire document
        // This avoids interfering with all other buttons on the page
        
        // Find the specific Discord send button
        const sendToDiscordBtn = document.getElementById('send-roll-discord-btn');
        if (sendToDiscordBtn) {
            console.log('Found Discord send button in dice roll modal');
            
            // Attach handler directly to only this specific button
            sendToDiscordBtn.addEventListener('click', function(event) {
                console.log('Discord send button clicked directly');
                
                // Don't call preventDefault() on the entire document!
                // Only stop propagation for this specific event
                event.stopPropagation();
                
                // Close the dice roll modal
                const diceRollModal = document.getElementById('dice-roll-modal');
                if (diceRollModal) {
                    diceRollModal.style.display = 'none';
                }
                
                // Use a timeout to ensure clean event handling
                setTimeout(function() {
                    // Get the Discord webhook modal button and click it
                    const webhookModalBtn = document.getElementById('open-discord-modal');
                    if (webhookModalBtn) {
                        console.log('Triggering webhook modal');
                        webhookModalBtn.click();
                    } else {
                        console.error('Discord webhook modal button not found');
                        alert('Discord webhook not properly configured. Please refresh the page and try again.');
                    }
                }, 50);
            });
        } else {
            console.warn('Discord send button not found in dice roll modal during init');
            
            // Only if the button isn't found yet, set up a one-time check for it
            // This is safer than a permanent document-level handler
            setTimeout(function() {
                const lateLoadedBtn = document.getElementById('send-roll-discord-btn');
                if (lateLoadedBtn) {
                    console.log('Found late-loaded Discord send button');
                    window.DiscordIntegration.setupEventHandlers();
                }
            }, 1000);
        }
    }
};

// Initialize Discord integration when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit to ensure all other scripts have run
    setTimeout(function() {
        window.DiscordIntegration.init();
    }, 100);
});

// Global function to update roll data for Discord
window.updateCurrentRoll = function(rollData) {
    console.log('Updating current roll data for Discord:', rollData);
    
    // Store the roll data globally
    window.currentRollData = rollData;
    
    // Format the content for Discord
    const rollContent = `
        <div class="attribute-roll">
            <h3>${rollData.characterName} - ${rollData.attributeName} Check</h3>
            <div class="roll-details">
                <p>Dice Roll: ${rollData.diceValue}</p>
                <p>${rollData.attributeName} Bonus: ${rollData.attributeValue}</p>
                <p>Total: ${rollData.totalValue}</p>
            </div>
        </div>
    `;
    
    // Update the attribute roll content element
    const contentElement = document.getElementById('attribute-roll-content');
    if (contentElement) {
        contentElement.innerHTML = rollContent;
        console.log('Updated attribute roll content for Discord');
    } else {
        console.warn('attribute-roll-content element not found');
    }
};