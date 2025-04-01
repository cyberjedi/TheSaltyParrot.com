<?php
/**
 * Discord Login Handler
 * 
 * Initiates the Discord OAuth flow in a popup window
 */

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include Discord configuration
require_once 'discord-config.php';

// Get state from request if provided
$state = $_GET['state'] ?? bin2hex(random_bytes(16));

// Store state in session
$_SESSION['discord_oauth_state'] = $state;

// Build the authorization URL
$auth_url = DISCORD_API_URL . '/oauth2/authorize';
$auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
$auth_url .= '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI);
$auth_url .= '&response_type=code';
$auth_url .= '&state=' . $state;
$auth_url .= '&scope=identify%20guilds';
$auth_url .= '&prompt=consent';

// Log the authentication attempt
error_log('Discord auth initiated. State: ' . $state);

// Return a page that opens the popup
?>
<!DOCTYPE html>
<html>
<head>
    <title>Connecting to Discord...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 20px;
            background-color: #36393f;
            color: #ffffff;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .loading {
            font-size: 24px;
            margin: 20px 0;
        }
    </style>
    <script>
        window.onload = function() {
            // Open Discord auth in popup
            const width = 600;
            const height = 800;
            const left = (window.innerWidth - width) / 2;
            const top = (window.innerHeight - height) / 2;

            const popup = window.open(
                '<?php echo $auth_url; ?>',
                'Discord Auth',
                `width=${width},height=${height},left=${left},top=${top}`
            );

            // If popup was blocked, redirect instead
            if (!popup || popup.closed || typeof popup.closed === 'undefined') {
                window.location.href = '<?php echo $auth_url; ?>';
            } else {
                // Return to previous page
                history.back();
            }
        };
    </script>
</head>
<body>
    <div class="container">
        <div class="loading">Connecting to Discord...</div>
    </div>
</body>
</html>