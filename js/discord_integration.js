/**
 * Simple Discord Integration
 * A minimal, non-invasive Discord integration for character sheets
 */

// Use an immediately invoked function expression for clean scope
(function() {
    'use strict';
    
    // Simple console logger with improved visibility
    function log(message, type = 'log') {
        const prefix = '🎮 [Discord]';
        
        switch(type) {
            case 'error':
                console.error(`${prefix} ❌ ${message}`);
                break;
            case 'warn':
                console.warn(`${prefix} ⚠️ ${message}`);
                break;
            case 'info':
                console.info(`${prefix} ℹ️ ${message}`);
                break;
            default:
                console.log(`${prefix} ${message}`);
        }
    }
    
    // For debugging DOM events
    const traceEvent = function(event) {
        console.group('🎮 Discord Event Traced');
        console.log('Event type:', event.type);
        console.log('Target:', event.target);
        console.log('Current target:', event.currentTarget);
        console.log('Event phase:', event.eventPhase);
        console.log('Default prevented:', event.defaultPrevented);
        console.groupEnd();
    };
    
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
            log('Found Discord button, setting up handler', 'info');
            console.log('Discord button details:', discordButton);
            
            // Apply a data attribute to mark this as processed by Discord
            if (discordButton.hasAttribute('data-discord-handler')) {
                log('Discord button already has handler, removing it first', 'warn');
                // Create a clean clone to remove existing handlers
                const clone = discordButton.cloneNode(true);
                discordButton.parentNode.replaceChild(clone, discordButton);
                discordButton = clone;
            }
            
            // Mark this button as having our handler
            discordButton.setAttribute('data-discord-handler', 'true');
            
            // Simple click handler with extensive tracing
            discordButton.addEventListener('click', function(event) {
                console.group('🎮 DISCORD BUTTON CLICKED');
                console.log('Button:', this);
                console.log('Event:', event);
                traceEvent(event);
                
                // Skip if no roll data
                if (!currentRollData) {
                    log('No roll data available', 'warn');
                    console.groupEnd();
                    return;
                }
                
                log('Processing Discord button click');
                
                try {
                    // Close dice roll modal
                    const diceRollModal = document.getElementById('dice-roll-modal');
                    if (diceRollModal) {
                        console.log('Closing dice roll modal');
                        diceRollModal.style.display = 'none';
                    } else {
                        console.warn('Dice roll modal not found, nothing to close');
                    }
                    
                    // Prepare Discord webhook content
                    const content = formatRollForDiscord(currentRollData);
                    console.log('Formatted content for Discord:', content);
                    
                    // Update the content in the Discord modal
                    const contentElement = document.getElementById('attribute-roll-content');
                    if (contentElement) {
                        contentElement.innerHTML = content;
                        console.log('Updated attribute roll content element');
                    } else {
                        console.warn('Attribute roll content element not found');
                    }
                    
                    // Open Discord webhook modal
                    const webhookButton = document.getElementById('open-discord-modal');
                    if (webhookButton) {
                        console.log('Found webhook button, clicking it:', webhookButton);
                        webhookButton.click();
                        console.log('Webhook button clicked');
                    } else {
                        log('Discord webhook button not found', 'error');
                        alert('Discord webhook not properly configured');
                    }
                    
                    log('Discord button handler completed successfully', 'info');
                } catch (error) {
                    console.error('Error in Discord button handler:', error);
                }
                
                console.groupEnd();
            });
            
            log('Discord button handler attached successfully', 'info');
        } else {
            log('Discord button not found in page', 'warn');
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