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
    // Skip test or debug events that may be fired programmatically
    if (event.target.id === 'test-event-propagation-btn') {
        return;
    }
    
    const target = event.target;
    const closestButton = target.closest('button');
    const closestLink = target.closest('a');
    
    // Only log if the target is an interactive element or has a handler
    if (!closestButton && !closestLink && !target.matches('button, a, [onclick], [data-has-handler="true"]')) {
        return;
    }
    
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
    console.log('Stop propagation called:', false); // Simplified to avoid modifying prototype
    console.groupEnd();
};

/**
 * Detect potential issues in the page - simplified version that doesn't trigger clicks
 */
TSP_DEBUG.detectIssues = function() {
    console.group('🔍 Issue Detection');
    
    // Simply log buttons without testing them
    const criticalButtons = [
        'edit-character-btn',
        'switch-character-btn', 
        'print-character-btn',
        'new-character-btn',
        'send-roll-discord-btn'
    ];
    
    console.log('Checking critical buttons (no clicking):');
    criticalButtons.forEach(function(id) {
        const button = document.getElementById(id);
        if (!button) {
            console.warn(`⚠️ Button #${id} not found!`);
            return;
        }
        
        console.log(`Button #${id} found: ${button.id}`);
        console.log(`- Visible: ${button.offsetParent !== null}`);
        console.log(`- Disabled: ${button.disabled}`);
        console.log(`- Has inline onclick: ${button.hasAttribute('onclick')}`);
        console.log(`- Has data-has-handler: ${button.getAttribute('data-has-handler')}`);
    });
    
    // Check for event handler issues without actually firing events
    console.log('Checking for event handler issues:');
    
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
});