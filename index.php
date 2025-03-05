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
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <div class="logo">
                    <i class="fas fa-skull-crossbones"></i>
                    <h1>The Salty Parrot</h1>
                </div>
                <div class="tagline">
                    <p>A Pirate Borg Toolbox</p>
                </div>
            </div>
            
            <div class="dashboard-container">
                <!-- Character Display Box -->
                <div class="character-box">
                    <h3 class="box-title">
                        Current Character
                        <div class="actions">
                            <button id="edit-character-btn" title="Edit Character"><i class="fas fa-edit"></i></button>
                            <button id="character-menu-btn" title="Character Menu"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </h3>
                    <div id="character-display">
                        <div class="no-character">
                            <i class="fas fa-user-slash"></i>
                            <p>No active character selected</p>
                            <button class="btn btn-outline" id="create-character-btn">
                                <i class="fas fa-plus"></i> Create Character
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Output Box for Generated Content -->
                <div class="output-box">
                    <h3 class="box-title">
                        Generator Output
                        <div class="actions">
                            <button id="print-output-btn" title="Print Output"><i class="fas fa-print"></i></button>
                            <button id="clear-output-btn" title="Clear Output"><i class="fas fa-trash"></i></button>
                        </div>
                    </h3>
                    <div id="output-display">
                        <div class="output-placeholder">
                            <i class="fas fa-dice"></i>
                            <p>Use the sidebar tools to generate content<br>Results will appear here</p>
                        </div>
                    </div>
                </div>
                
                <!-- Game Log Box -->
                <div class="game-log">
                    <h3 class="box-title">
                        Game Log
                        <div class="actions">
                            <button id="save-log-btn" title="Save Log"><i class="fas fa-save"></i></button>
                            <button id="clear-log-btn" title="Clear Log"><i class="fas fa-trash"></i></button>
                        </div>
                    </h3>
                    <div id="log-display">
                        <p style="text-align: center; padding: 30px 0;">
                            <i class="fas fa-scroll" style="font-size: 2rem; color: var(--secondary); opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                            Game logging coming soon!
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Dashboard loaded");
            
            const outputDisplay = document.getElementById('output-display');
            const clearOutputBtn = document.getElementById('clear-output-btn');
            const printOutputBtn = document.getElementById('print-output-btn');
            const createCharacterBtn = document.getElementById('create-character-btn');
            
            // Clear output button
            if (clearOutputBtn) {
                clearOutputBtn.addEventListener('click', function() {
                    outputDisplay.innerHTML = `
                        <div class="output-placeholder">
                            <i class="fas fa-dice"></i>
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
                    
                    // Create print window
                    const printWindow = window.open('', '_blank');
                    
                    // Get content from output box
                    const content = outputDisplay.innerHTML;
                    
                    // Create print-friendly HTML
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>The Salty Parrot - Generated Content</title>
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    line-height: 1.6;
                                    color: #333;
                                    padding: 20px;
                                }
                                
                                h2, h3 {
                                    color: #805d2c;
                                }
                                
                                .loot-card, .ship-details {
                                    border: 1px solid #ddd;
                                    padding: 15px;
                                    margin: 15px 0;
                                    border-radius: 8px;
                                }
                                
                                .loot-roll, .loot-name {
                                    color: #805d2c;
                                }
                                
                                .loot-category {
                                    font-style: italic;
                                    color: #666;
                                }
                                
                                .ancient-relic-badge {
                                    display: inline-block;
                                    padding: 3px 8px;
                                    background-color: #f0e6ff;
                                    color: #4b0082;
                                    border: 1px solid #9d4edd;
                                    border-radius: 12px;
                                    font-size: 0.8rem;
                                    margin-right: 5px;
                                }
                                
                                .thing-of-importance-badge {
                                    display: inline-block;
                                    padding: 3px 8px;
                                    background-color: #e6ffe6;
                                    color: #006400;
                                    border: 1px solid #2ea44f;
                                    border-radius: 12px;
                                    font-size: 0.8rem;
                                    margin-right: 5px;
                                }
                                
                                ul {
                                    list-style-type: none;
                                    padding-left: 0;
                                }
                                
                                li {
                                    margin-bottom: 10px;
                                    position: relative;
                                    padding-left: 20px;
                                }
                                
                                li:before {
                                    content: '•';
                                    color: #805d2c;
                                    position: absolute;
                                    left: 0;
                                    top: 0;
                                }
                                
                                .extra-roll-divider {
                                    text-align: center;
                                    margin: 20px 0;
                                    position: relative;
                                }
                                
                                .extra-roll-divider::before {
                                    content: "";
                                    position: absolute;
                                    top: 50%;
                                    left: 0;
                                    right: 0;
                                    height: 1px;
                                    background-color: #ddd;
                                    z-index: 0;
                                }
                                
                                .extra-roll-divider span {
                                    position: relative;
                                    background-color: white;
                                    padding: 0 15px;
                                    z-index: 1;
                                }
                                
                                .output-placeholder {
                                    display: none;
                                }
                            </style>
                        </head>
                        <body>
                            <h2>The Salty Parrot - Generated Content</h2>
                            ${content}
                            <div style="margin-top: 30px; font-size: 0.8rem; text-align: center; color: #666;">
                                <p>Generated by The Salty Parrot - A Pirate Borg Toolbox</p>
                                <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC.<br>
                                It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
                            </div>
                        </body>
                        </html>
                    `);
                    
                    // Close document for printing
                    printWindow.document.close();
                    
                    // Wait for content to load and then print
                    printWindow.addEventListener('load', function() {
                        printWindow.print();
                    });
                });
            }
            
            // Create Character button
            if (createCharacterBtn) {
                createCharacterBtn.addEventListener('click', function() {
                    alert("Character creation is coming soon!");
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
