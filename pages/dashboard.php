<?php
// Set the current page
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Dashboard-specific styles */
        .dashboard-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            grid-template-rows: auto 1fr;
            gap: 20px;
            height: calc(100vh - 180px); /* Adjust height to account for header and footer */
        }
        
        .character-box {
            grid-column: 1;
            grid-row: 1;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow: auto;
            min-height: 250px;
        }
        
        .output-box {
            grid-column: 1;
            grid-row: 2;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow: auto;
            max-height: 100%;
        }
        
        .game-log {
            grid-column: 2;
            grid-row: 1 / span 2;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow: auto;
        }
        
        .box-title {
            color: var(--secondary);
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(191, 157, 97, 0.3);
            font-size: 1.3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .box-title .actions {
            display: flex;
            gap: 10px;
        }
        
        .box-title .actions button {
            background: none;
            border: none;
            color: var(--secondary);
            cursor: pointer;
            font-size: 0.9rem;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        
        .box-title .actions button:hover {
            opacity: 1;
        }
        
        .no-character {
            text-align: center;
            padding: 30px 20px;
        }
        
        .no-character i {
            font-size: 3rem;
            color: var(--secondary);
            opacity: 0.4;
            margin-bottom: 15px;
        }
        
        .no-character p {
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .output-placeholder {
            text-align: center;
            padding: 40px 20px;
            opacity: 0.7;
        }
        
        .output-placeholder i {
            font-size: 3rem;
            color: var(--secondary);
            opacity: 0.4;
            margin-bottom: 15px;
        }
        
        /* Ship Generator Results Styling */
        .ship-details h3 {
            color: var(--secondary);
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1.1rem;
            border-bottom: 1px solid rgba(191, 157, 97, 0.2);
            padding-bottom: 5px;
        }
        
        .ship-details p {
            margin-bottom: 10px;
        }
        
        #cargo-list {
            list-style-type: none;
            padding-left: 0;
            margin-left: 0;
        }
        
        #cargo-list li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 25px;
            display: block;
        }
        
        #cargo-list li:before {
            content: '•';
            color: var(--secondary);
            position: absolute;
            left: 5px;
            top: 0;
            font-size: 1.2em;
        }
        
        /* Loot Generator Styling */
        .loot-card {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border: 1px solid rgba(191, 157, 97, 0.3);
        }

        .loot-roll {
            color: var(--secondary);
            font-weight: bold;
            margin-bottom: 5px;
        }

        .loot-name {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: var(--secondary);
            border-bottom: 1px solid rgba(191, 157, 97, 0.2);
            padding-bottom: 5px;
        }

        .loot-description {
            margin-bottom: 5px;
        }

        .loot-category {
            font-style: italic;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .ancient-relic-badge, .thing-of-importance-badge {
            display: inline-block;
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 12px;
            margin-right: 5px;
            margin-bottom: 10px;
        }

        .ancient-relic-badge {
            background-color: rgba(75, 0, 130, 0.5);
            color: #e0c5ff;
            border: 1px solid #9d4edd;
        }

        .thing-of-importance-badge {
            background-color: rgba(0, 100, 0, 0.5);
            color: #c6ffda;
            border: 1px solid #2ea44f;
        }

        .extra-roll-divider {
            margin: 20px 0;
            text-align: center;
            position: relative;
        }

        .extra-roll-divider::before {
            content: "";
            position: absolute;
            top: 50%;
