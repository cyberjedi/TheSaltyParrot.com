/**
 * Discord Integration for Character Sheet
 * This file handles the Discord webhook integration specifically
 */

// Initialize Discord integration when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Discord integration initialized');
    
    // Connect the "Send to Discord" button in the dice roll modal
    const sendToDiscordBtn = document.getElementById('send-roll-discord-btn');
    if (sendToDiscordBtn) {
        console.log('Found Discord send button in dice roll modal');
        
        // Remove any existing event listeners before adding a new one
        const clonedBtn = sendToDiscordBtn.cloneNode(true);
        sendToDiscordBtn.parentNode.replaceChild(clonedBtn, sendToDiscordBtn);
        
        clonedBtn.addEventListener('click', function(event) {
            console.log('Discord send button clicked');
            event.preventDefault();
            
            // Close the dice roll modal
            const diceRollModal = document.getElementById('dice-roll-modal');
            if (diceRollModal) {
                diceRollModal.style.display = 'none';
            }
            
            // Get the Discord webhook modal button and click it
            const webhookModalBtn = document.getElementById('open-discord-modal');
            if (webhookModalBtn) {
                console.log('Triggering webhook modal');
                // Use a setTimeout to allow other event handlers to complete first
                setTimeout(function() {
                    webhookModalBtn.click();
                }, 10);
            } else {
                console.error('Discord webhook modal button not found');
                alert('Discord webhook not properly configured. Please refresh the page and try again.');
            }
        });
    } else {
        console.warn('Discord send button not found in dice roll modal');
    }
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