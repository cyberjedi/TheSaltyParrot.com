<?php
// File: discord/discord-direct-popup.php
// This file handles the Discord authentication flow in a popup window

require_once 'discord-config.php';

// Generate a random state parameter to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['discord_oauth_state'] = $state;

// Build the authorization URL with all necessary scopes
$auth_url = DISCORD_API_URL . '/oauth2/authorize';
$auth_url .= '?client_id=' . DISCORD_CLIENT_ID;
$auth_url .= '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI);
$auth_url .= '&response_type=code';
$auth_url .= '&state=' . $state;
$auth_url .= '&scope=identify%20guilds%20guilds.members.read%20guilds.channels.read';
$auth_url .= '&prompt=none'; // Try to avoid additional prompts
?>
<!DOCTYPE html>
<html>
<head>
    <title>Discord Authentication</title>
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
        h2 {
            color: #7289da;
            margin-bottom: 20px;
        }
        .loading {
            margin: 30px 0;
        }
        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(114,137,218,0.3);
            border-radius: 50%;
            border-top-color: #7289da;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .message {
            margin: 20px 0;
            line-height: 1.5;
        }
        .button {
            display: inline-block;
            background-color: #7289da;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 20px;
        }
        .success-icon {
            font-size: 48px;
            color: #43b581;
            margin: 20px 0;
        }
        .error-icon {
            font-size: 48px;
            color: #f04747;
            margin: 20px 0;
        }
    </style>
    <script>
        // Start the auth process when the page loads
        window.onload = function() {
            // Redirect to Discord auth
            window.location.href = '<?php echo $auth_url; ?>';
        };
    </script>
</head>
<body>
    <div class="container">
        <h2>Discord Authentication</h2>
        <div class="message">
            Connecting to Discord... Please wait.
        </div>
        <div class="loading">
            <div class="spinner"></div>
        </div>
    </div>
</body>
</html>