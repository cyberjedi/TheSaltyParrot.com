/**
 * Discord Integration for Character Sheet
 * This file handles the Discord webhook integration specifically
 * Version: 2.0 - Complete rewrite with better isolation and event handling
 */

// Create a properly isolated namespace for Discord integration
(function() {
    'use strict';
    
    // Private variables - not accessible from outside this module
    let _initialized = false;
    let _debugMode = false; // Set to true to enable verbose logging
    let _currentRollData = null;
    
    // Private logging function that respects debug mode
    function _log(message, type = 'log') {
        if (_debugMode || type === 'error' || type === 'warn') {
            console[type](`[Discord Integration] ${message}`);
        }
    }
    
    // The main Discord Integration object - exposed to window
    const DiscordIntegration = {
        // Public configuration options
        config: {
            rollButtonId: 'send-roll-discord-btn',
            webhookButtonId: 'open-discord-modal',
            diceRollModalId: 'dice-roll-modal',
            attributeRollContentId: 'attribute-roll-content',
            safetyDelay: 50, // Milliseconds to wait between operations
        },
        
        // Enable debug logging
        enableDebug: function() {
            _debugMode = true;
            _log('Debug mode enabled');
            return this;
        },
        
        // Disable debug logging
        disableDebug: function() {
            _log('Debug mode disabled');
            _debugMode = false;
            return this;
        },
        
        // Check if a Discord button exists
        hasDiscordButtons: function() {
            const sendButton = document.getElementById(this.config.rollButtonId);
            return !!sendButton;
        },
        
        // Initialize the Discord integration
        init: function() {
            if (_initialized) {
                _log('Already initialized, skipping initialization');
                return this;
            }
            
            _log('Initializing Discord integration');
            
            // Check if the page has Discord authentication
            if (typeof window.discord_authenticated === 'undefined') {
                _log('Discord authentication status not found in window object', 'warn');
            } else if (!window.discord_authenticated) {
                _log('Discord is not authenticated, some features may be disabled');
            }
            
            // Set up the event handlers for Discord integration
            this.setupEventHandlers();
            
            // Set initialization flag
            _initialized = true;
            _log('Discord integration initialized successfully');
            
            return this;
        },
        
        // Reset the integration (useful for testing)
        reset: function() {
            _log('Resetting Discord integration');
            _initialized = false;
            return this;
        },
        
        // Set up event handlers safely
        setupEventHandlers: function() {
            _log('Setting up event handlers');
            
            // Look for Send to Discord button in the dice roll modal
            const sendButton = document.getElementById(this.config.rollButtonId);
            
            if (sendButton) {
                _log(`Found Discord send button (#${this.config.rollButtonId})`);
                
                // Store a reference to the current instance for use in event handlers
                const self = this;
                
                // Remove any existing event listeners by cloning the button
                // This prevents duplicate handler issues if init() is called multiple times
                const newSendButton = sendButton.cloneNode(true);
                sendButton.parentNode.replaceChild(newSendButton, sendButton);
                
                // Add new event listener
                newSendButton.addEventListener('click', function(event) {
                    _log('Discord send button clicked');
                    
                    // Only stop propagation - don't prevent default
                    // This allows the event to be processed normally
                    event.stopPropagation();
                    
                    // Handle the send to Discord action with proper isolation
                    self.handleSendToDiscord();
                });
                
                _log('Successfully attached event handler to Discord send button');
            } else {
                _log('Discord send button not found in DOM', 'warn');
                
                // Set up a one-time check for late-loaded elements
                // This could happen with dynamic content loading
                setTimeout(() => {
                    if (!_initialized || !this.hasDiscordButtons()) {
                        _log('Checking again for late-loaded Discord buttons');
                        if (document.getElementById(this.config.rollButtonId)) {
                            _log('Found late-loaded Discord button');
                            this.setupEventHandlers();
                        } else {
                            _log('Discord buttons still not found after delay', 'warn');
                        }
                    }
                }, 1500); // Longer timeout for late loading
            }
            
            return this;
        },
        
        // Handle sending to Discord 
        handleSendToDiscord: function() {
            _log('Handling send to Discord action');
            
            // First, safely close the dice roll modal
            this.closeDiceRollModal();
            
            // Then, after a small delay, open the webhook modal
            const self = this;
            setTimeout(function() {
                self.openWebhookModal();
            }, this.config.safetyDelay);
            
            return this;
        },
        
        // Safely close the dice roll modal
        closeDiceRollModal: function() {
            const diceRollModal = document.getElementById(this.config.diceRollModalId);
            if (diceRollModal) {
                _log('Closing dice roll modal');
                diceRollModal.style.display = 'none';
                
                // If modal uses active class for z-index, remove it
                diceRollModal.classList.remove('active');
            } else {
                _log('Dice roll modal not found, nothing to close', 'warn');
            }
            
            return this;
        },
        
        // Safely open the webhook modal
        openWebhookModal: function() {
            const webhookButton = document.getElementById(this.config.webhookButtonId);
            if (webhookButton) {
                _log('Opening webhook modal by clicking webhook button');
                webhookButton.click();
            } else {
                _log('Webhook modal button not found', 'error');
                console.error('Discord webhook modal button not found');
                alert('Discord webhook not properly configured. Please refresh the page and try again.');
            }
            
            return this;
        },
        
        // Update current roll data for Discord
        updateRollData: function(rollData) {
            if (!rollData || typeof rollData !== 'object') {
                _log('Invalid roll data provided', 'error');
                return this;
            }
            
            _log('Updating roll data for Discord', 'log');
            _currentRollData = rollData;
            
            // Format the HTML content for the Discord webhook
            const rollContent = this.formatRollContent(rollData);
            
            // Update the attribute roll content element if it exists
            const contentElement = document.getElementById(this.config.attributeRollContentId);
            if (contentElement) {
                contentElement.innerHTML = rollContent;
                _log('Updated attribute roll content for Discord webhook');
            } else {
                _log(`Attribute roll content element (#${this.config.attributeRollContentId}) not found`, 'warn');
            }
            
            return this;
        },
        
        // Format roll content as HTML
        formatRollContent: function(rollData) {
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
        },
        
        // Get current roll data
        getRollData: function() {
            return _currentRollData;
        }
    };
    
    // Expose the Discord Integration object to the window
    window.DiscordIntegration = DiscordIntegration;
    
    // Set up backward compatibility for existing code
    window.updateCurrentRoll = function(rollData) {
        console.log('Legacy updateCurrentRoll called - using new API');
        window.DiscordIntegration.updateRollData(rollData);
    };
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for a short delay to ensure character sheet JS has initialized
        setTimeout(function() {
            // Initialize Discord integration
            DiscordIntegration.init();
            
            // Check after a short delay if Discord buttons have loaded
            setTimeout(function() {
                if (!DiscordIntegration.hasDiscordButtons()) {
                    console.log('Discord buttons not found after DOM ready, rechecking...');
                    DiscordIntegration.setupEventHandlers();
                }
            }, 800);
        }, 200);
    });
})();