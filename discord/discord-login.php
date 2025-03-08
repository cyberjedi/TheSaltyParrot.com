<?php
// File: discord/discord-login.php
require_once 'discord-config.php';

// Store the referrer URL to redirect back after authentication
if (isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['discord_auth_referrer'] = $_SERVER['HTTP_REFERER'];
} else {
    $_SESSION['discord_auth_referrer'] = '../index.php';
}

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
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
            background-color: #36393f;
            color: #ffffff;
        }
        .auth-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            background-color: #2f3136;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        h2 {
            color: #7289da;
        }
        .loading {
            margin: 20px 0;
        }
        .loading-spinner {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid rgba(114,137,218,0.3);
            border-radius: 50%;
            border-top-color: #7289da;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .manual-link {
            margin-top: 30px;
        }
        .manual-link a {
            display: inline-block;
            background-color: #7289da;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
        .manual-link a:hover {
            background-color: #5b73c7;
        }
        .back-link {
            margin-top: 20px;
        }
        .back-link a {
            color: #7289da;
            text-decoration: none;
        }
    </style>
    <script>
        // Open the Discord authentication in the same window instead of a popup
        window.location.href = '<?php echo $auth_url; ?>';
    </script>
</head>
<body>
    <div class="auth-container">
        <h2>Discord Authentication</h2>
        <p>Redirecting to Discord for authentication...</p>
        <div class="loading">
            <div class="loading-spinner"></div>
        </div>
        <div class="manual-link">
            <p>If you're not automatically redirected:</p>
            <a href="<?php echo $auth_url; ?>">Click here to authenticate with Discord</a>
        </div>
        <div class="back-link">
            <a href="../index.php">← Return to The Salty Parrot</a>
        </div>
    </div>
</body>
</html>
