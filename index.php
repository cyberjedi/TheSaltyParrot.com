<?php
/**
 * The Salty Parrot - New Index Page
 * 
 * A clean, minimal design with only essential functionality
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/discord.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
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
    </style>
</head>
<body>
    <!-- Include the topbar -->
    <?php include 'components/topbar.php'; ?>
    
    <!-- Main Content Area -->
    <main class="main-content-new">
        <div class="welcome-container">
            <img src="assets/TSP_Logo_3inch.svg" alt="The Salty Parrot Logo" class="welcome-logo">
            <h1 class="welcome-title">Welcome to The Salty Parrot</h1>
            <p class="welcome-message">
                Your PIRATE BORG companion for nautical adventures
            </p>
        </div>
    </main>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>
</body>
</html>