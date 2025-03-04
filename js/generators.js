// generators.js - Game content generators - Updated for modal system
document.addEventListener('DOMContentLoaded', function() {
    // Set up Generator API functions
    window.Generators = {
        // Ship Generator
        generateShip: function() {
            // Check if modal exists before attempting to open it
            if (!window.GeneratorModal) {
                console.error("GeneratorModal is not defined. Make sure generator-modal.js is loaded.");
                return;
            }
            
            // Show loading animation in modal
            window.GeneratorModal.open(
                "Ship Generator", 
                `<div style="text-align: center; padding: 30px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--secondary); margin-bottom: 15px;"></i>
                    <p>Generating ship...</p>
                </div>`,
                window.Generators.generateShip
            );
            
            // Make API request to generate_ship.php
            fetch('../api/generate_ship.php')
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
                        
                        // Update modal content
                        const modalContent = document.getElementById('modal-content');
                        if (modalContent) {
                            modalContent.innerHTML = shipHtml;
                        } else {
                            console.error("modal-content element not found");
                        }
                        
                        // Log to console
                        console.log("Generated ship:", ship);
                    } else {
                        // Handle error
                        const modalContent = document.getElementById('modal-content');
                        if (modalContent) {
                            modalContent.innerHTML = `
                                <div style="color: #dc3545; padding: 20px; text-align: center;">
                                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                                    <p>Error generating ship: ${data.message}</p>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Show error in modal
                    const modalContent = document.getElementById('modal-content');
                    if (modalContent) {
                        modalContent.innerHTML = `
                            <div style="color: #dc3545; padding: 20px; text-align: center;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                                <p>Error generating ship: ${error.message}</p>
                                <p>Check the console for more details.</p>
                            </div>
                        `;
                    }
                });
        },
        
        // Loot Generator
        generateLoot: function() {
            // Check if modal exists before attempting to open it
            if (!window.GeneratorModal) {
                console.error("GeneratorModal is not defined. Make sure generator-modal.js is loaded.");
                return;
            }
            
            // Show loading animation in modal
            window.GeneratorModal.open(
                "Loot Generator", 
                `<div style="text-align: center; padding: 30px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--secondary); margin-bottom: 15px;"></i>
                    <p>Generating loot...</p>
                </div>`,
                window.Generators.generateLoot
            );
            
            // Make API request to generate_loot.php
            fetch('../api/generate_loot.php')
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
                        
                        // Update modal content
                        const modalContent = document.getElementById('modal-content');
                        if (modalContent) {
                            modalContent.innerHTML = lootHtml;
                        } else {
                            console.error("modal-content element not found");
                        }
                        
                        // Log to console
                        console.log("Generated loot:", data);
                    } else {
                        // Handle error
                        const modalContent = document.getElementById('modal-content');
                        if (modalContent) {
                            modalContent.innerHTML = `
                                <div style="color: #dc3545; padding: 20px; text-align: center;">
                                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                                    <p>Error generating loot: ${data.message}</p>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Show error in modal
                    const modalContent = document.getElementById('modal-content');
                    if (modalContent) {
                        modalContent.innerHTML = `
                            <div style="color: #dc3545; padding: 20px; text-align: center;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                                <p>Error generating loot: ${error.message}</p>
                                <p>Check the console for more details.</p>
                            </div>
                        `;
                    }
                });
        },
        
        // Placeholder for future generators
        diceRoller: function() {
            if (window.GeneratorModal) {
                window.GeneratorModal.open(
                    "Dice Roller", 
                    `<div style="text-align: center; padding: 30px;">
                        <i class="fas fa-dice" style="font-size: 3rem; color: var(--secondary); margin-bottom: 15px;"></i>
                        <p>Dice roller coming soon!</p>
                    </div>`,
                    null
                );
            } else {
                alert("Dice roller coming soon!");
            }
        },
        
        npcGenerator: function() {
            if (window.GeneratorModal) {
                window.GeneratorModal.open(
                    "NPC Generator", 
                    `<div style="text-align: center; padding: 30px;">
                        <i class="fas fa-user-friends" style="font-size: 3rem; color: var(--secondary); margin-bottom: 15px;"></i>
                        <p>NPC generator coming soon!</p>
                    </div>`,
                    null
                );
            } else {
                alert("NPC generator coming soon!");
            }
        },
        
        treasureGenerator: function() {
            if (window.GeneratorModal) {
                window.GeneratorModal.open(
                    "Treasure Generator", 
                    `<div style="text-align: center; padding: 30px;">
                        <i class="fas fa-gem" style="font-size: 3rem; color: var(--secondary); margin-bottom: 15px;"></i>
                        <p>Treasure generator coming soon!</p>
                    </div>`,
                    null
                );
            } else {
                alert("Treasure generator coming soon!");
            }
        }
    };
});
