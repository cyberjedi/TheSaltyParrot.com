/**
 * Simple Discord Integration
 * A minimal, non-invasive Discord integration for character sheets
 */

// Use an immediately invoked function expression for clean scope
(function() {
    'use strict';
    
    // Simple console logger
    function log(message) {
        console.log(`[Discord Simple] ${message}`);
    }
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        log('Initializing Discord integration');
        
        // Find Discord button in dice roll modal
        const discordButton = document.getElementById('send-roll-discord-btn');
        
        // Store the dice roll data for sharing
        let currentRollData = null;
        
        // Listen for the custom dice roll event from character sheet
        document.addEventListener('characterDiceRoll', function(event) {
            // Store roll data for Discord
            currentRollData = event.detail;
            log('Received dice roll event with data');
        });
        
        // Set up the Discord button handler ONLY if it exists
        if (discordButton) {
            log('Found Discord button, setting up handler');
            
            // Simple click handler - no complex event manipulation
            discordButton.addEventListener('click', function() {
                // Skip if no roll data
                if (!currentRollData) {
                    log('No roll data available');
                    return;
                }
                
                // Close dice roll modal
                const diceRollModal = document.getElementById('dice-roll-modal');
                if (diceRollModal) {
                    diceRollModal.style.display = 'none';
                }
                
                // Prepare Discord webhook content
                const content = formatRollForDiscord(currentRollData);
                
                // Update the content in the Discord modal
                const contentElement = document.getElementById('attribute-roll-content');
                if (contentElement) {
                    contentElement.innerHTML = content;
                }
                
                // Open Discord webhook modal
                const webhookButton = document.getElementById('open-discord-modal');
                if (webhookButton) {
                    webhookButton.click();
                } else {
                    log('Discord webhook button not found');
                    alert('Discord webhook not properly configured');
                }
            });
        } else {
            log('Discord button not found in page');
        }
    });
    
    // Format roll data for Discord
    function formatRollForDiscord(rollData) {
        return `
            <div class="attribute-roll">
                <h3>${rollData.characterName} - ${rollData.attributeName} Check</h3>
                <div class="roll-details">
                    <p>Dice Roll: ${rollData.diceValue}</p>
                    <p>${rollData.attributeName} Bonus: ${rollData.attributeValue}</p>
                    <p>Total: ${rollData.totalValue}</p>
                </div>
            </div>
        `;
    }
    
    // Provide minimal backward compatibility
    window.updateCurrentRoll = function(rollData) {
        // Create and dispatch a custom event with the roll data
        const event = new CustomEvent('characterDiceRoll', { 
            detail: rollData,
            bubbles: true
        });
        document.dispatchEvent(event);
    };
})();