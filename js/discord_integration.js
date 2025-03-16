/**
 * Discord Integration for Character Sheet
 * This file handles the Discord webhook integration specifically
 * Version: 3.0 - Complete rewrite with strict isolation and scoped event handling
 */

// Use an immediately invoked function expression (IIFE) for proper isolation
(function() {
    'use strict';
    
    // Private variables - not accessible from outside this module
    let _initialized = false;
    let _debugMode = false;
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
        },
        
        // Enable debug logging
        enableDebug: function() {
            _debugMode = true;
            _log('Debug mode enabled');
            return this;
        },
        
        // Initialize the Discord integration
        init: function() {
            if (_initialized) {
                _log('Already initialized, skipping initialization');
                return this;
            }
            
            _log('Initializing Discord integration');
            
            // Only set up handlers for Discord-specific buttons without affecting other functionality
            this.setupDiscordEventHandlers();
            
            // Set initialization flag
            _initialized = true;
            _log('Discord integration initialized successfully');
            
            return this;
        },
        
        // Set up event handlers ONLY for Discord-specific buttons
        setupDiscordEventHandlers: function() {
            _log('Setting up Discord-specific event handlers');
            
            // Look for Send to Discord button in the dice roll modal
            const sendRollDiscordBtn = document.getElementById(this.config.rollButtonId);
            
            if (sendRollDiscordBtn) {
                _log(`Found Discord send button (#${this.config.rollButtonId})`);
                
                // Store reference to the current instance for use in event handlers
                const self = this;
                
                // Handle click to send roll to Discord
                if (!sendRollDiscordBtn.hasAttribute('data-discord-handler-attached')) {
                    sendRollDiscordBtn.addEventListener('click', function() {
                        _log('Discord send roll button clicked');
                        
                        // Handle sending roll to Discord
                        self.handleSendRollToDiscord();
                    });
                    
                    // Mark this button as having a handler already attached
                    sendRollDiscordBtn.setAttribute('data-discord-handler-attached', 'true');
                    _log('Attached event handler to Discord send roll button');
                } else {
                    _log('Discord send roll button already has handler attached');
                }
            } else {
                _log('Discord send roll button not found in DOM', 'warn');
            }
            
            return this;
        },
        
        // Handle sending roll to Discord - scoped specifically to this functionality
        handleSendRollToDiscord: function() {
            _log('Handling send roll to Discord action');
            
            // Get the Discord webhook modal button
            const webhookButton = document.getElementById(this.config.webhookButtonId);
            
            if (webhookButton) {
                _log('Opening webhook modal by clicking webhook button');
                
                // First close the dice roll modal if it's open
                const diceRollModal = document.getElementById(this.config.diceRollModalId);
                if (diceRollModal) {
                    diceRollModal.style.display = 'none';
                    diceRollModal.classList.remove('active');
                }
                
                // Now open the webhook modal
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
    
    // Backward compatibility for existing code
    window.updateCurrentRoll = function(rollData) {
        _log('Legacy updateCurrentRoll called - using new API');
        window.DiscordIntegration.updateRollData(rollData);
    };
    
    // Initialize after DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Discord integration with a slight delay
        // to ensure other scripts have completed their initialization
        setTimeout(function() {
            DiscordIntegration.init();
        }, 100); // Reduced delay to minimize any user-visible lag
    });
})();
