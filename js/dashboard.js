// dashboard.js - Core dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard loaded");
    
    const outputDisplay = document.getElementById('output-display');
    const clearOutputBtn = document.getElementById('clear-output-btn');
    const printOutputBtn = document.getElementById('print-output-btn');
    const createCharacterBtn = document.getElementById('create-character-btn');
    const copyCodeBtn = document.getElementById('copy-code-btn');
    
    // Initialize user data
    function loadUserData(userId) {
        console.log("Loading data for user:", userId);
        // This is where you'd fetch the user's characters and recent activity
        // from your database and update the UI
    }
    
    // Copy join code button
    if (copyCodeBtn) {
        copyCodeBtn.addEventListener('click', function() {
            const joinCodeSpan = document.getElementById('join-code');
            if (joinCodeSpan) {
                const joinCode = joinCodeSpan.textContent;
                navigator.clipboard.writeText(joinCode).then(() => {
                    // Change button text temporarily to show success
                    const originalText = copyCodeBtn.innerHTML;
                    copyCodeBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    setTimeout(() => {
                        copyCodeBtn.innerHTML = originalText;
                    }, 2000);
                });
            }
        });
    }
    
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
});
