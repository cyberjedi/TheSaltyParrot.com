<?php
// Set the current page
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Salty Parrot - A Pirate Borg Toolbox</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        /* Updated styles for the new layout */
        .dashboard-container {
            display: grid;
            grid-template-columns: 1fr 600px; 
            grid-template-rows: auto auto 1fr;
            gap: 20px;
            height: calc(100vh - 180px);
        }

        .discord-box {
            grid-column: 1 / span 2;
            grid-row: 1;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow: auto;
        }

        .character-box {
            grid-column: 1;
            grid-row: 2;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow: auto;
            min-height: 250px;
        }

        .ship-box {
            grid-column: 1;
            grid-row: 3;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow: auto;
            min-height: 250px;
        }

        .output-box {
            grid-column: 2;
            grid-row: 2 / span 2;
            background-color: var(--dark);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
            overflow: auto;
            height: 100%;
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

        /* Improved styling for output content */
        #output-display h2 {
            margin-top: 0;
            color: var(--secondary);
            text-align: left;
        }

        .ship-details p, .loot-description {
            text-align: left;
            margin-bottom: 10px;
        }

        .ship-details h3 {
            text-align: left;
            color: var(--secondary);
            margin-top: 20px;
            margin-bottom: 10px;
        }

        #cargo-list {
            text-align: left;
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

        .loot-card {
            text-align: left;
        }

        .loot-roll, .loot-name {
            text-align: left;
        }

        /* Small screens adjustment */
        @media (max-width: 1200px) {
            .dashboard-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto auto;
                height: auto;
            }
            
            .discord-box {
                grid-column: 1;
                grid-row: 1;
            }
            
            .character-box {
                grid-column: 1;
                grid-row: 2;
            }
            
            .ship-box {
                grid-column: 1;
                grid-row: 3;
            }
            
            .output-box {
                grid-column: 1;
                grid-row: 4;
                height: 500px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-container">
                <!-- Discord Connection Box -->
                <div class="discord-box">
                    <h3 class="box-title">Discord Connection</h3>
                    
                    <div id="discord-status" style="text-align: center; padding: 15px 0;">
                        <i class="fab fa-discord" style="font-size: 2rem; color: #bf9d61; opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                        <p>Discord integration coming soon</p>
                    </div>
                </div>
                
                <!-- Character Display Box -->
                <div class="character-box">
                    <h3 class="box-title">
                        Current Character
                        <div class="actions">
                            <button id="edit-character-btn" title="Edit Character"><i class="fas fa-edit"></i></button>
                            <button id="character-menu-btn" title="Character Menu"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </h3>
                    
                    <div id="character-display" style="text-align: center; padding: 30px 0;">
                        <i class="fas fa-user-slash" style="font-size: 3rem; color: #bf9d61; opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                        <p>No active character selected</p>
                        <button class="btn btn-outline" id="create-character-btn" style="margin-top: 15px; background-color: transparent; color: #bf9d61; border: 1px solid #bf9d61; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-plus"></i> Create Character
                        </button>
                    </div>
                </div>
                
                <!-- Ship Display Box -->
                <div class="ship-box">
                    <h3 class="box-title">
                        Current Ship
                        <div class="actions">
                            <button id="edit-ship-btn" title="Edit Ship"><i class="fas fa-edit"></i></button>
                            <button id="ship-menu-btn" title="Ship Menu"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </h3>
                    
                    <div id="ship-display" style="text-align: center; padding: 30px 0;">
                        <i class="fas fa-ship" style="font-size: 3rem; color: #bf9d61; opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                        <p>No active ship selected</p>
                        <button class="btn btn-outline" id="create-ship-btn" style="margin-top: 15px; background-color: transparent; color: #bf9d61; border: 1px solid #bf9d61; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-plus"></i> Create Ship
                        </button>
                    </div>
                </div>
                
                <!-- Output Box (Right Side) -->
                <div class="output-box">
                    <h3 class="box-title">
                        Output
                        <div class="actions">
                            <button id="print-output-btn" title="Print Output"><i class="fas fa-print"></i></button>
                            <button id="clear-output-btn" title="Clear Output"><i class="fas fa-trash"></i></button>
                        </div>
                    </h3>
                    
                    <div id="output-display" style="text-align: left; padding: 10px;">
                        <div style="text-align: center; padding: 30px 0;">
                            <i class="fas fa-dice" style="font-size: 3rem; color: #bf9d61; opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                            <p>Use the sidebar tools to generate content<br>Results will appear here</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Include the print helper component -->
    <?php include 'components/print_helper.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Dashboard loaded");
            
            const outputDisplay = document.getElementById('output-display');
            const clearOutputBtn = document.getElementById('clear-output-btn');
            const printOutputBtn = document.getElementById('print-output-btn');
            const createCharacterBtn = document.getElementById('create-character-btn');
            const createShipBtn = document.getElementById('create-ship-btn');
            
            // Clear output button
            if (clearOutputBtn) {
                clearOutputBtn.addEventListener('click', function() {
                    outputDisplay.innerHTML = `
                        <div style="text-align: center; padding: 30px 0;">
                            <i class="fas fa-dice" style="font-size: 3rem; color: #bf9d61; opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                            <p>Use the sidebar tools to generate content<br>Results will appear here</p>
                        </div>
                    `;
                });
            }
            
            // Print output button
            if (printOutputBtn) {
                printOutputBtn.addEventListener('click', function() {
                    // Check if there's content to print
                    if (outputDisplay.querySelector('.output-placeholder')) {
                        alert("Nothing to print yet. Generate some content first!");
                        return;
                    }
                    
                    // Get content from output box
                    const content = outputDisplay.innerHTML;
                    
                    // Call the print helper function
                    <?php echo "generatePrintableContent(content);"; ?>
                });
            }
            
            // Create Character button
            if (createCharacterBtn) {
                createCharacterBtn.addEventListener('click', function() {
                    alert("Character creation is coming soon!");
                });
            }
            
            // Create Ship button
            if (createShipBtn) {
                createShipBtn.addEventListener('click', function() {
                    alert("Ship creation is coming soon!");
                });
            }
            
            // Set up Generator API functions
            window.Generators = {
                // Ship Generator
                generateShip: function() {
                    // Show loading state
                    outputDisplay.innerHTML = `
                        <div style="text-align: center; padding: 30px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--secondary); margin-bottom: 15px;"></i>
                            <p>Generating ship...</p>
                        </div>
                    `;
                    
                    // Make API request to generate_ship.php
                    fetch('api/generate_ship.php')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                const ship = data.ship;
                                
                                // Create HTML for ship display
                                let shipHtml = `
                                    <h2 id="ship-name">${ship.ship_name}</h2>
                                    <div class="ship-details">
                                        <p><strong>Vessel Class:</strong> ${ship.vessel_class}</p>
                                        <p><strong>Armament:</strong> ${ship.armament}</p>
                                        <p><strong>Crew:</strong> The crew are ${ship.crew_quantity} and are ${ship.crew_quality}.</p>
                                        
                                        <h3>Cargo:</h3>
                                        <ul id="cargo-list">
                                `;
                                
                                // Add cargo items
                                if (ship.cargo && ship.cargo.length > 0) {
                                    ship.cargo.forEach(item => {
                                        shipHtml += `<li>${item}</li>`;
                                    });
                                } else {
                                    shipHtml += `<li>None</li>`;
                                }
                                
                                shipHtml += `
                                        </ul>
                                        
                                        <h3>Plot Twist (Optional):</h3>
                                        <p>${ship.plot_twist}</p>
                                    </div>
                                `;
                                
                                // Update output display
                                outputDisplay.innerHTML = shipHtml;
                                
                                // Log to console
                                console.log("Generated ship:", ship);
                            } else {
                                // Handle error
                                outputDisplay.innerHTML = `
                                    <div style="color: #dc3545; padding: 20px; text-align: center;">
                                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                                        <p>Error generating ship: ${data.message}</p>
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            
                            // Show error in output display
                            outputDisplay.innerHTML = `
                                <div style="color: #dc3545; padding: 20px; text-align: center;">
                                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                                    <p>Error generating ship: ${error.message}</p>
                                    <p>Check the console for more details.</p>
                                </div>
                            `;
                        });
                },
                
                // Loot Generator
                generateLoot: function() {
                    // Show loading state
                    outputDisplay.innerHTML = `
                        <div style="text-align: center; padding: 30px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--secondary); margin-bottom: 15px;"></i>
                            <p>Generating loot...</p>
                        </div>
                    `;
                    
                    // Make API request to generate_loot.php
                    fetch('api/generate_loot.php')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                // Start building loot HTML
                                let lootHtml = `<h2>Loot Results</h2>`;
                                
                                // Add primary loot
                                data.loot.forEach(item => {
                                    lootHtml += `
                                        <div class="loot-card">
                                            <div class="loot-roll">Roll: ${item.roll}</div>
                                            <div class="loot-name">${item.name}</div>
                                    `;
                                    
                                    // Add badges if applicable
                                    if (item.is_ancient_relic) {
                                        lootHtml += `<span class="ancient-relic-badge">Ancient Relic</span>`;
                                    }
                                    
                                    if (item.is_thing_of_importance) {
                                        lootHtml += `<span class="thing-of-importance-badge">Thing of Importance</span>`;
                                    }
                                    
                                    // Add description and category
                                    lootHtml += `
                                            <div class="loot-description">${item.description}</div>
                                            <div class="loot-category">Category: ${item.category}</div>
                                        </div>
                                    `;
                                });
                                
                                // Add extra rolls if any
                                if (data.extra_rolls && data.extra_rolls.length > 0) {
                                    lootHtml += `
                                        <div class="extra-roll-divider">
                                            <span>Additional Rolls</span>
                                        </div>
                                    `;
                                    
                                    data.extra_rolls.forEach(item => {
                                        lootHtml += `
                                            <div class="loot-card">
                                                <div class="loot-roll">Roll: ${item.roll}</div>
                                                <div class="loot-name">${item.name}</div>
                                        `;
                                        
                                        // Add badges if applicable
                                        if (item.is_ancient_relic) {
                                            lootHtml += `<span class="ancient-relic-badge">Ancient Relic</span>`;
                                        }
                                        
                                        if (item.is_thing_of_importance) {
                                            lootHtml += `<span class="thing-of-importance-badge">Thing of Importance</span>`;
                                        }
                                        
                                        // Add description and category
                                        lootHtml += `
                                                <div class="loot-description">${item.description}</div>
                                                <div class="loot-category">Category: ${item.category}</div>
                                            </div>
                                        `;
                                    });
                                }
                                
                                // Update output display
                                outputDisplay.innerHTML = lootHtml;
                                
                                // Log to console
                                console.log("Generated loot:", data);
                            } else {
                                // Handle error
                                outputDisplay.innerHTML = `
                                    <div style="color: #dc3545; padding: 20px; text-align: center;">
                                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                                        <p>Error generating loot: ${data.message}</p>
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            
                            // Show error in output display
                            outputDisplay.innerHTML = `
                                <div style="color: #dc3545; padding: 20px; text-align: center;">
                                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                                    <p>Error generating loot: ${error.message}</p>
                                    <p>Check the console for more details.</p>
                                </div>
                            `;
                        });
                },
                
                // Placeholder for future generators
                diceRoller: function() {
                    alert("Dice roller coming soon!");
                },
                
                npcGenerator: function() {
                    alert("NPC generator coming soon!");
                },
                
                treasureGenerator: function() {
                    alert("Treasure generator coming soon!");
                }
            };
        });
  
        // Check for URL parameters to run generators on page load
        const urlParams = new URLSearchParams(window.location.search);
        const generator = urlParams.get('generator');
        
        if (generator) {
            // Wait a moment for the page to fully load
            setTimeout(() => {
                // Run the appropriate generator based on the URL parameter
                switch(generator) {
                    case 'ship':
                        window.Generators.generateShip();
                        break;
                    case 'loot':
                        window.Generators.generateLoot();
                        break;
                    case 'dice':
                        window.Generators.diceRoller();
                        break;
                    case 'npc':
                        window.Generators.npcGenerator();
                        break;
                    case 'treasure':
                        window.Generators.treasureGenerator();
                        break;
                }
                
                // Clear the URL parameter to prevent re-running on refresh
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 500);
        }
    </script>
</body>
</html>
