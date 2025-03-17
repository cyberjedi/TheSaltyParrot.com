<?php
/**
 * Webhooks Configuration Page (New UI)
 * 
 * Allows users to configure Discord webhooks for the application
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'discord-config.php';
require_once '../config/db_connect.php';
require_once 'discord_service_new.php';

// Redirect to login if not authenticated
if (!is_discord_authenticated()) {
    $_SESSION['discord_error'] = 'You must be logged in with Discord to manage webhooks.';
    header('Location: ../index_new.php');
    exit;
}

// Get current user
$user = get_discord_user_new();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Webhooks - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/topbar_new.css">
    <link rel="stylesheet" href="../css/discord_new.css">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <style>
        body {
            background-color: var(--dark);
            color: var(--light);
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .webhook-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .webhook-title {
            color: var(--secondary);
            font-size: 2rem;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--secondary);
            padding-bottom: 10px;
        }
        
        .webhook-description {
            margin-bottom: 30px;
        }
        
        .webhook-card {
            background-color: var(--primary);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--secondary);
        }
        
        .webhook-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: var(--secondary);
        }
        
        input, select {
            width: 100%;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #444;
            background-color: #2a2a2a;
            color: var(--light);
        }
        
        button {
            background-color: var(--secondary);
            color: var(--dark);
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        button:hover {
            opacity: 0.9;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--secondary);
            text-decoration: none;
        }
        
        .back-link i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Include the topbar with user's Discord profile -->
    <?php include '../components/topbar_new.php'; ?>
    
    <!-- Main Content Area -->
    <main class="main-content-new">
        <div class="webhook-container">
            <h1 class="webhook-title">Discord Webhook Configuration</h1>
            
            <div class="webhook-description">
                <p>Configure your Discord webhooks to send game content directly to your Discord server. Select your default server and channel for content sharing.</p>
            </div>
            
            <div class="webhook-card">
                <h2>Manage Webhooks</h2>
                <p>This feature will be implemented soon!</p>
                <p>You'll be able to add, edit, and delete webhooks to different servers and channels.</p>
            </div>
            
            <a href="../index_new.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </main>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>
</body>
</html>