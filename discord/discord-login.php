<?php
// File: discord/discord-login.php
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Discord Login</title>
    <script>
        window.open('<?php echo $auth_url; ?>', 'discord-login', 'width=600,height=800');
        setTimeout(function() {
            window.location.href = '../index.php';
        }, 500);
    </script>
</head>
<body>
    <p>Opening Discord authentication... If nothing happens, <a href="<?php echo $auth_url; ?>" target="_blank">click here</a>.</p>
</body>
</html>
