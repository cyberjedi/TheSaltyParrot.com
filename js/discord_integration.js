/**
 * Discord Integration
 * 
 * Handles Discord authentication and integration
 */

// Discord OAuth configuration
const DISCORD_CLIENT_ID = window.DISCORD_CLIENT_ID || '';
const DISCORD_REDIRECT_URI = `${window.location.origin}/discord/discord-callback.php`;
const DISCORD_SCOPE = 'identify email guilds';

// Simple console logger with improved visibility
function log(message, type = 'log') {
    // Only log in development environment
    if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        return;
    }

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

/**
 * Initialize Discord authentication
 */
export function initDiscordAuth() {
    if (!DISCORD_CLIENT_ID) {
        log('Discord client ID is not configured', 'error');
        return;
    }

    log('Initializing Discord authentication', 'info');

    // Generate random state for CSRF protection
    const state = Math.random().toString(36).substring(7);
    sessionStorage.setItem('discord_state', state);

    // Redirect to our login endpoint with state
    window.location.href = `/discord/discord-login.php?state=${state}`;
}

// Export for use in other files
export default {
    initDiscordAuth
};