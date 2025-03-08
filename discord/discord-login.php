<?php
// File: discord/discord-login.php
require_once 'discord-config.php';

// Store the referring page to redirect back after authentication
$_SESSION['discord_return_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';

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

// Open in a popup window instead of redirecting
echo '<!DOCTYPE html>
<html>
<head>
    <title>Discord Authentication</title>
</head>
<body>
    <script>
        var popup = window.open("' . $auth_url . '", "discord-oauth", "width=600,height=800");
        if (!popup || popup.closed || typeof popup.closed==\'undefined\') {
            // Popup blocked, try direct redirect
            window.location.href = "' . $auth_url . '";
        } else {
            // Wait for the popup to be closed, then redirect
            var timer = setInterval(function() {
                if(popup.closed) {
                    clearInterval(timer);
                    window.location.href = "../index.php";
                }
            }, 1000);
        }
    </script>
    <p>Authenticating with Discord... If this page doesn\'t close automatically, <a href="../index.php">click here</a> to return.</p>
</body>
</html>';
exit;
?>
