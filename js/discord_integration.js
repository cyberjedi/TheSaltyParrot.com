/**
 * Simple Discord Integration
 * A minimal, non-invasive Discord integration for character sheets
 * Version 3.1 - Fixed to prevent interference with button clicks
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
    
    // Store the dice roll data for sharing
    let currentRollData = null;
    
    // Function to initialize only once DOM is completely ready
    function initDiscordIntegration() {
        log('Initializing Discord integration');
        
        // Find Discord button in dice roll modal
        let discordButton = document.getElementById('send-roll-discord-btn');
        
        // Listen for the custom dice roll event from character sheet
        // Use a namespaced event handler to avoid conflicts
        if (!window._discord_event_attached) {
            // Only attach once
            document.addEventListener('characterDiceRoll', function(event) {
                // Store roll data for Discord
                currentRollData = event.detail;
                log('Received dice roll event with data');
            });
            window._discord_event_attached = true;
        }
        
        // Set up the Discord button handler ONLY if it exists
        if (discordButton) {
            log('Found Discord button, setting up handler', 'info');
            
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
            
            // Critical: Use event delegation pattern to avoid capturing unrelated clicks
            discordButton.addEventListener('click', function discordButtonHandler(event) {
                // Stop event from bubbling to avoid triggering document handlers
                event.stopPropagation();
                
                console.group('🎮 DISCORD BUTTON CLICKED');
                console.log('Button:', this);
                console.log('Event:', event);
                
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
                        diceRollModal.style.display = 'none';
                        diceRollModal.classList.remove('active');
                    }
                    
                    // Prepare Discord webhook content
                    const content = formatRollForDiscord(currentRollData);
                    
                    // Update the content in the Discord modal
                    const contentElement = document.getElementById('attribute-roll-content');
                    if (contentElement) {
                        contentElement.innerHTML = content;
                    } else {
                        log('Attribute roll content element not found', 'warn');
                    }
                    
                    // Open Discord webhook modal
                    const webhookButton = document.getElementById('open-discord-modal');
                    if (webhookButton) {
                        // First, update the content that will be sent
                        const contentElement = document.getElementById('attribute-roll-content');
                        if (contentElement) {
                            contentElement.innerHTML = content;
                        }
                        
                        // Then trigger the discord webhook modal
                        webhookButton.click();
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
    }
    
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
    
    // Wait for DOM to be ready - critical to ensure we don't start too early
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDiscordIntegration);
    } else {
        // Small timeout to ensure everything else has loaded first
        setTimeout(initDiscordIntegration, 100);
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
    
    // Also run initialization when page has completely loaded
    window.addEventListener('load', function() {
        // Give a little extra time for any other scripts to finish
        setTimeout(function() {
            log('Running post-load initialization', 'info');
            initDiscordIntegration();
        }, 500);
    });
    
    // Flag for integration verification
    window.discord_integration_version = '3.1';
})();

/**
 * Discord Integration
 * 
 * Handles Discord authentication and integration
 */

// Discord OAuth configuration
const DISCORD_CLIENT_ID = window.DISCORD_CLIENT_ID || '';
const DISCORD_REDIRECT_URI = `${window.location.origin}/discord/discord-callback.php`;
const DISCORD_SCOPE = 'identify email guilds';

/**
 * Initialize Discord authentication
 */
export function initDiscordAuth() {
    if (!DISCORD_CLIENT_ID) {
        console.error('Discord client ID is not configured');
        return;
    }

    // Generate random state for CSRF protection
    const state = Math.random().toString(36).substring(7);
    sessionStorage.setItem('discord_state', state);

    // Redirect to our login endpoint with state
    window.location.href = `/discord/discord-login.php?state=${state}`;
}

/**
 * Handle Discord callback
 * 
 * @param {string} code Discord authorization code
 * @param {string} state State parameter for CSRF protection
 */
export async function handleDiscordCallback(code, state) {
    try {
        // Verify state
        const savedState = sessionStorage.getItem('discord_state');
        if (state !== savedState) {
            throw new Error('Invalid state parameter');
        }

        // Exchange code for token through our server endpoint
        const response = await fetch('/discord/discord-callback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ code, state })
        });

        if (!response.ok) {
            throw new Error('Failed to exchange code for token');
        }

        const data = await response.json();
        
        // Clear state
        sessionStorage.removeItem('discord_state');

        // Close popup and refresh page
        window.close();
        window.opener.location.reload();

        return { success: true, data };
    } catch (error) {
        console.error('Error handling Discord callback:', error);
        return { success: false, error: error.message };
    }
}