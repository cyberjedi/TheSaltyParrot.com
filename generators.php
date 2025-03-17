<?php
/**
 * The Salty Parrot - Generators Page
 * 
 * A page with generator buttons on the left and output display on the right
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include Discord webhook modal component
require_once 'components/discord_webhook_modal.php';

// Set the current page for active navigation highlighting
$current_page = 'generators';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generators - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/discord.css">
    <link rel="stylesheet" href="css/discord_components.css">
    <style>
        /* Generators layout styles */
        .generators-container {
            display: flex;
            height: calc(100vh - 120px);
            margin-top: 60px;
            padding: 20px;
            gap: 20px;
        }
        
        .generator-buttons {
            width: 250px;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 15px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow-y: auto;
        }
        
        .output-window {
            flex: 1;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow-y: auto;
            min-height: 500px;
        }
        
        .generator-btn {
            display: flex;
            align-items: center;
            width: 100%;
            text-align: left;
            padding: 12px 15px;
            background-color: transparent;
            border: none;
            color: var(--light);
            cursor: pointer;
            border-radius: 5px;
            margin-bottom: 8px;
            transition: background-color 0.3s;
        }
        
        .generator-btn i {
            margin-right: 10px;
            color: var(--secondary);
        }
        
        .generator-btn:hover {
            background-color: rgba(191, 157, 97, 0.1);
        }
        
        .generator-btn.active {
            background-color: rgba(191, 157, 97, 0.2);
            border-left: 3px solid var(--secondary);
        }
        
        .generator-section {
            margin-bottom: 20px;
        }
        
        .generator-section h3 {
            color: var(--secondary);
            font-size: 1rem;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid rgba(191, 157, 97, 0.3);
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
            display: block;
        }
        
        .output-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .output-actions button {
            padding: 8px 12px;
            background-color: var(--dark);
            border: 1px solid var(--secondary);
            color: var(--light);
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .output-actions button:hover {
            background-color: rgba(191, 157, 97, 0.1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .generators-container {
                flex-direction: column;
                height: auto;
            }
            
            .generator-buttons {
                width: 100%;
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Include the topbar -->
    <?php include 'components/topbar.php'; ?>
    
    <!-- Generators Container -->
    <div class="generators-container">
        <!-- Generator Buttons Section -->
        <div class="generator-buttons">
            <div class="generator-section">
                <h3>Generators</h3>
                <button id="gen-ship" class="generator-btn">
                    <i class="fas fa-ship"></i> Random Ship
                </button>
                <button id="gen-loot" class="generator-btn">
                    <i class="fas fa-gem"></i> Random Loot
                </button>
                <!-- Additional generators can be added here -->
            </div>
        </div>
        
        <!-- Output Window -->
        <div class="output-window">
            <div class="output-actions" id="output-actions" style="display: none;">
                <button id="clear-output">
                    <i class="fas fa-trash-alt"></i> Clear
                </button>
                <button id="print-output">
                    <i class="fas fa-print"></i> Print
                </button>
                <?php 
                // Render Discord webhook modal and button
                if (function_exists('render_discord_webhook_modal')) {
                    $sourceType = ''; // Will be set dynamically based on generator used
                    render_discord_webhook_modal('#output-display', $sourceType, false, '', [
                        'button_text' => 'Share to Discord',
                        'button_icon' => 'fa-discord',
                        'button_class' => '',
                        'button_id' => 'share-discord'
                    ]);
                } else {
                    // Fallback button if component is not available
                    echo '<button id="share-discord" class="discord-share disabled">
                        <i class="fab fa-discord"></i> Share to Discord
                    </button>';
                }
                ?>
            </div>
            
            <div id="output-display">
                <div class="output-placeholder">
                    <i class="fas fa-dice-d20"></i>
                    <p>Select a generator from the left to begin</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get elements
            const shipButton = document.getElementById('gen-ship');
            const lootButton = document.getElementById('gen-loot');
            const outputDisplay = document.getElementById('output-display');
            const outputActions = document.getElementById('output-actions');
            const clearOutputBtn = document.getElementById('clear-output');
            const printOutputBtn = document.getElementById('print-output');
            const shareDiscordBtn = document.getElementById('share-discord');
            
            // Add active class to the topbar button
            const topbarButtons = document.querySelectorAll('.topbar-nav-btn');
            topbarButtons.forEach(button => {
                if (button.textContent.trim().includes('Generators')) {
                    button.classList.add('active');
                }
            });
            
            // Generate Ship Button
            shipButton.addEventListener('click', function() {
                // Show loading state
                outputDisplay.innerHTML = '<div class="output-placeholder"><i class="fas fa-spinner fa-spin"></i><p>Generating random ship...</p></div>';
                
                // Set source type for Discord sharing
                document.querySelector('#discord-webhook-modal')?.setAttribute('data-source', 'ship');
                
                // Fetch data from the API
                fetch('api/generate_ship.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const ship = data.ship;
                            
                            // Format cargo items as list
                            let cargoItems = '';
                            if (ship.cargo && ship.cargo.length > 0) {
                                cargoItems = '<ul id="cargo-list">';
                                ship.cargo.forEach(item => {
                                    cargoItems += `<li>${item}</li>`;
                                });
                                cargoItems += '</ul>';
                            }
                            
                            // Build HTML for ship details
                            const shipHTML = `
                                <div class="ship-details">
                                    <h3>Ship Name</h3>
                                    <p id="ship-name">${ship.ship_name}</p>
                                    
                                    <h3>Vessel Class</h3>
                                    <p>${ship.vessel_class}</p>
                                    
                                    <h3>Armament</h3>
                                    <p>${ship.armament}</p>
                                    
                                    <h3>Crew</h3>
                                    <p>${ship.crew_quantity}, ${ship.crew_quality}</p>
                                    
                                    <h3>Cargo</h3>
                                    ${cargoItems}
                                    
                                    <h3>Plot Twist</h3>
                                    <p>${ship.plot_twist}</p>
                                </div>
                            `;
                            
                            // Update the output display and show actions
                            outputDisplay.innerHTML = shipHTML;
                            outputActions.style.display = 'flex';
                        } else {
                            outputDisplay.innerHTML = `<div class="error-message">Error: ${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        outputDisplay.innerHTML = `<div class="error-message">Error: ${error.message}</div>`;
                    });
            });
            
            // Generate Loot Button
            lootButton.addEventListener('click', function() {
                // Show loading state
                outputDisplay.innerHTML = '<div class="output-placeholder"><i class="fas fa-spinner fa-spin"></i><p>Generating random loot...</p></div>';
                
                // Set source type for Discord sharing
                document.querySelector('#discord-webhook-modal')?.setAttribute('data-source', 'loot');
                
                // Fetch data from the API
                fetch('api/generate_loot.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            let lootHTML = '';
                            
                            // Process main loot item
                            data.loot.forEach(item => {
                                lootHTML += generateLootItemHTML(item);
                            });
                            
                            // Process extra rolls if any
                            if (data.extra_rolls && data.extra_rolls.length > 0) {
                                lootHTML += '<div class="extra-roll-divider"><span>Extra Rolls</span></div>';
                                data.extra_rolls.forEach(item => {
                                    lootHTML += generateLootItemHTML(item);
                                });
                            }
                            
                            // Update the output display and show actions
                            outputDisplay.innerHTML = lootHTML;
                            outputActions.style.display = 'flex';
                        } else {
                            outputDisplay.innerHTML = `<div class="error-message">Error: ${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        outputDisplay.innerHTML = `<div class="error-message">Error: ${error.message}</div>`;
                    });
            });
            
            // Function to generate HTML for a loot item
            function generateLootItemHTML(item) {
                // Create badges HTML if needed
                let badgesHTML = '';
                if (item.is_ancient_relic) {
                    badgesHTML += '<span class="ancient-relic-badge">Ancient Relic</span>';
                }
                if (item.is_thing_of_importance) {
                    badgesHTML += '<span class="thing-of-importance-badge">Thing of Importance</span>';
                }
                
                return `
                    <div class="loot-card">
                        <div class="loot-roll">Roll: ${item.roll}</div>
                        <div class="loot-name">${item.name}</div>
                        ${badgesHTML}
                        <div class="loot-description">${item.description}</div>
                        <div class="loot-category">Category: ${item.category}</div>
                    </div>
                `;
            }
            
            // Clear Output Button
            clearOutputBtn.addEventListener('click', function() {
                outputDisplay.innerHTML = `
                    <div class="output-placeholder">
                        <i class="fas fa-dice-d20"></i>
                        <p>Select a generator from the left to begin</p>
                    </div>
                `;
                outputActions.style.display = 'none';
            });
            
            // Print Output Button
            printOutputBtn.addEventListener('click', function() {
                window.print();
            });
            
            // Share to Discord Button - integrated with the webhook modal
            shareDiscordBtn.addEventListener('click', function() {
                // The discord_webhook_modal component handles everything:
                // - Checking if authenticated
                // - Showing webhook selection
                // - Previewing content
                // - Sending to the API
                
                // The actual functionality is implemented by the modal component
            });
        });
    </script>
</body>
</html>