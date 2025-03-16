/**
 * Debug Utilities for The Salty Parrot
 * This file contains debugging tools to help identify issues in the application
 */

// Create a global debug namespace
window.TSP_DEBUG = window.TSP_DEBUG || {};

// Initialize debug state
TSP_DEBUG.initialized = false;
TSP_DEBUG.buttonStats = {};
TSP_DEBUG.eventStats = {}; 

/**
 * Initialize the debug utilities
 */
TSP_DEBUG.init = function() {
    if (TSP_DEBUG.initialized) return;
    
    console.log('🔍 TSP Debug Tools initialized');
    
    // Attach global click tracer
    document.addEventListener('click', TSP_DEBUG.traceClickEvent, true);
    
    // Set init flag
    TSP_DEBUG.initialized = true;
    
    // Run an initial detection
    setTimeout(TSP_DEBUG.detectIssues, 1000);
};

/**
 * Trace click events happening in the document
 */
TSP_DEBUG.traceClickEvent = function(event) {
    const target = event.target;
    const closestButton = target.closest('button');
    const closestLink = target.closest('a');
    
    console.group('🔍 Click Event Detected');
    console.log('Target element:', target);
    console.log('Target ID:', target.id);
    console.log('Target Tag:', target.tagName);
    console.log('Target Classes:', target.className);
    
    if (closestButton) {
        console.log('Closest button:', closestButton);
        console.log('Button ID:', closestButton.id);
    }
    
    if (closestLink) {
        console.log('Closest link:', closestLink);
        console.log('Link href:', closestLink.getAttribute('href'));
    }
    
    console.log('Event prevented:', event.defaultPrevented);
    console.log('Stop propagation called:', (function() {
        const originalStopPropagation = Event.prototype.stopPropagation;
        let wasCalled = false;
        
        Event.prototype.stopPropagation = function() {
            wasCalled = true;
            return originalStopPropagation.apply(this, arguments);
        };
        
        setTimeout(function() {
            Event.prototype.stopPropagation = originalStopPropagation;
        }, 0);
        
        return wasCalled;
    })());
    console.groupEnd();
};

/**
 * Detect potential issues in the page
 */
TSP_DEBUG.detectIssues = function() {
    console.group('🔍 Issue Detection');
    
    // Check for critical buttons
    const criticalButtons = [
        'edit-character-btn',
        'switch-character-btn', 
        'print-character-btn',
        'new-character-btn',
        'send-roll-discord-btn'
    ];
    
    console.log('Checking critical buttons:');
    criticalButtons.forEach(function(id) {
        const button = document.getElementById(id);
        if (!button) {
            console.warn(`⚠️ Button #${id} not found!`);
            return;
        }
        
        console.log(`Button #${id} found:`, button);
        console.log(`- Visible:`, button.offsetParent !== null);
        console.log(`- Disabled:`, button.disabled);
        console.log(`- Has inline onclick:`, button.hasAttribute('onclick'));
        
        // Test if clicks actually work
        let clickWorks = false;
        const originalClick = HTMLElement.prototype.click;
        
        HTMLElement.prototype.click = function() {
            if (this === button) {
                clickWorks = true;
            }
            return originalClick.apply(this, arguments);
        };
        
        // Try clicking the button
        try {
            button.click();
        } catch (e) {
            console.error(`Error clicking button #${id}:`, e);
        }
        
        // Restore original click method
        HTMLElement.prototype.click = originalClick;
        
        console.log(`- Click works:`, clickWorks);
    });
    
    // Check for event handler issues
    console.log('Checking for event handler issues:');
    
    // Test event bubbling
    const testDiv = document.createElement('div');
    document.body.appendChild(testDiv);
    
    let eventBubbled = false;
    document.body.addEventListener('customtest', function() {
        eventBubbled = true;
    });
    
    const testEvent = new CustomEvent('customtest', { bubbles: true });
    testDiv.dispatchEvent(testEvent);
    
    console.log('Event bubbling works:', eventBubbled);
    document.body.removeChild(testDiv);
    
    // Check for inline handlers
    const elementsWithHandlers = document.querySelectorAll('[onclick], [onchange], [onsubmit]');
    console.log(`Found ${elementsWithHandlers.length} elements with inline handlers`);
    
    // Look for Discord interference patterns
    const discordScripts = Array.from(document.scripts).filter(script => 
        script.src && script.src.includes('discord')
    );
    
    console.log('Discord script elements found:', discordScripts.length);
    
    if (window.discord_authenticated) {
        console.log('Discord is authenticated according to window.discord_authenticated');
    }
    
    console.groupEnd();
};

/**
 * Inject a test button to check event handling
 */
TSP_DEBUG.injectTestButton = function() {
    const testButton = document.createElement('button');
    testButton.id = 'tsp-debug-test-button';
    testButton.textContent = 'Debug Test Button';
    testButton.style.position = 'fixed';
    testButton.style.top = '10px';
    testButton.style.right = '10px';
    testButton.style.zIndex = '9999';
    testButton.style.background = 'red';
    testButton.style.color = 'white';
    testButton.style.padding = '5px 10px';
    testButton.style.borderRadius = '5px';
    
    testButton.addEventListener('click', function() {
        alert('Test button clicked successfully!');
        console.log('Test button click handler executed');
    });
    
    document.body.appendChild(testButton);
    console.log('Debug test button injected');
};

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    TSP_DEBUG.init();
    
    // Inject a test button if there are issues
    setTimeout(function() {
        const anyButtonWorks = document.querySelectorAll('button').length > 0;
        if (!anyButtonWorks) {
            TSP_DEBUG.injectTestButton();
        }
    }, 2000);
});