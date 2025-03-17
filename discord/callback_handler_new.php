<?php
/**
 * Callback Handler for New UI
 * 
 * This script handles the post-authentication flow for the new UI.
 * It's meant to be run after the user has been authenticated via the existing callback.
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check for errors first
if (isset($_SESSION['discord_error'])) {
    $error = $_SESSION['discord_error'];
    unset($_SESSION['discord_error']);
} else {
    $error = null;
}

// Check for the cookie that indicates we should return to the new UI
$should_redirect_to_new_ui = isset($_COOKIE['tsp_new_ui_redirect']) && $_COOKIE['tsp_new_ui_redirect'] === 'true';

// Get the return path from the session
$return_path = isset($_SESSION['discord_new_ui_return']) ? $_SESSION['discord_new_ui_return'] : '/index_new.php';

// Clean up the cookie and session variable
setcookie('tsp_new_ui_redirect', '', time() - 3600, '/');
unset($_SESSION['discord_new_ui_return']);

// Check if we're authenticated
$is_authenticated = isset($_SESSION['discord_user']) && isset($_SESSION['discord_access_token']);

// Log what's happening
error_log('New UI callback handler: Redirect to new UI: ' . ($should_redirect_to_new_ui ? 'Yes' : 'No'));
error_log('New UI callback handler: Authenticated: ' . ($is_authenticated ? 'Yes' : 'No'));
error_log('New UI callback handler: Return path: ' . $return_path);
if ($error) {
    error_log('New UI callback handler: Error: ' . $error);
}

// Render nice response for popup
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
            color: <?php echo $error ? '#f04747' : '#7289da'; ?>;
            margin-bottom: 20px;
        }
        .icon {
            font-size: 48px;
            color: <?php echo $error ? '#f04747' : '#43b581'; ?>;
            margin: 20px 0;
        }
        .message {
            margin: 20px 0;
            line-height: 1.5;
        }
    </style>
    <script>
        // Close popup after a short delay
        window.onload = function() {
            setTimeout(function() {
                if (window.opener && !window.opener.closed) {
                    // Try to redirect the parent
                    try {
                        window.opener.location.href = '<?php echo $return_path; ?>';
                    } catch(e) {
                        console.error("Could not redirect parent:", e);
                    }
                    // Close this popup
                    window.close();
                } else {
                    // Not a popup, redirect directly
                    window.location.href = '<?php echo $return_path; ?>';
                }
            }, 1500);
        };
    </script>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <h2>Authentication Failed</h2>
            <div class="icon">✕</div>
            <div class="message">
                <p><?php echo htmlspecialchars($error); ?></p>
                <p>Redirecting you back...</p>
            </div>
        <?php elseif ($is_authenticated): ?>
            <h2>Authentication Successful!</h2>
            <div class="icon">✓</div>
            <div class="message">
                <p>You've successfully connected to Discord.</p>
                <p>Redirecting you back to The Salty Parrot...</p>
            </div>
        <?php else: ?>
            <h2>Authentication Status Unknown</h2>
            <div class="icon">?</div>
            <div class="message">
                <p>We couldn't determine if you've been authenticated.</p>
                <p>Redirecting you back to The Salty Parrot...</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>