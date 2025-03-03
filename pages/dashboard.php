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
            grid-template-columns: 1fr 600px; 
            grid-template-rows: auto 1fr;
            gap: 20px;
            height: calc(100vh - 180px);
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
            left: 0;
            right: 0;
            height: 1px;
            background-color: rgba(191, 157, 97, 0.3);
            z-index: 0;
        }

        .extra-roll-divider span {
            position: relative;
            background-color: var(--dark);
            padding: 0 15px;
            z-index: 1;
        }

        /* Game Log Styles */
        .game-session-controls {
            background-color: var(--dark);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(191, 157, 97, 0.3);
        }
        
        .session-info {
            margin-bottom: 10px;
        }
        
        .session-info h4 {
            color: var(--secondary);
            margin: 0 0 5px 0;
        }
        
        #session-code {
            font-family: monospace;
            background-color: rgba(0, 0, 0, 0.2);
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .log-entry {
            margin-bottom: 15px;
            padding: 10px 12px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
            border-left: 3px solid var(--secondary);
        }
        
        .log-entry .user {
            font-weight: bold;
            color: var(--secondary);
            margin-bottom: 2px;
        }
        
        .log-entry .timestamp {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 5px;
        }
        
        .log-entry .content {
            word-break: break-word;
        }
        
        .log-entry.system-entry {
            background-color: rgba(191, 157, 97, 0.1);
            border-left-color: rgba(191, 157, 97, 0.6);
            font-style: italic;
            color: rgba(255, 255, 255, 0.8);
        }
        
        #custom-log-input {
            width: 100%;
            padding: 8px 12px;
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(191, 157, 97, 0.3);
            color: var(--light);
            border-radius: 4px;
        }
        
        /* For small screens, stack the grid */
        @media (max-width: 1200px) { /* Changed from 768px to 1200px */
            .dashboard-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto;
                height: auto;
            }
            
            .character-box {
                grid-column: 1;
                grid-row: 1;
            }
            
            .output-box {
                grid-column: 1;
                grid-row: 2;
            }
            
            .game-log {
                grid-column: 1;
                grid-row: 3;
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include '../components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <div class="logo">
                    <i class="fas fa-skull-crossbones"></i>
                    <h1>Dashboard</h1>
                </div>
                <div class="user-controls">
                    <span id="user-email" class="user-email">Loading...</span>
                    <button id="logout-btn-top" class="btn btn-secondary btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
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
                    
                    <!-- Game Session Controls -->
                    <div class="game-session-controls">
                        <div id="no-session" style="display: block;">
                            <button id="create-session-btn" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Game Session
                            </button>
                            <button id="join-session-btn" class="btn btn-secondary">
                                <i class="fas fa-sign-in-alt"></i> Join Game Session
                            </button>
                        </div>
                        
                        <div id="active-session" style="display: none;">
                            <div class="session-info">
                                <h4 id="session-name">Session Name</h4>
                                <div id="session-code">Join Code: <span id="join-code">ABC123</span></div>
                            </div>
                            <button id="leave-session-btn" class="btn btn-outline btn-sm">
                                <i class="fas fa-sign-out-alt"></i> Leave Session
                            </button>
                        </div>
                    </div>
                    
                    <!-- Log entries container -->
                    <div id="log-display" style="max-height: calc(100% - 150px); overflow-y: auto;">
                        <p style="text-align: center; padding: 30px 0;">
                            <i class="fas fa-scroll" style="font-size: 2rem; color: var(--secondary); opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                            Game logging coming soon!
                        </p>
                    </div>
                    
                    <!-- Custom log entry input -->
                    <div id="log-controls" style="display: none; margin-top: 15px;">
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="custom-log-input" placeholder="Add to the ship's log...">
                            <button id="add-log-btn" class="btn btn-outline btn-sm">
                                <i class="fas fa-plus"></i> Add
                            </button>
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

    <!-- Firebase scripts -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    
    <script>
        // Initialize Firebase
        const firebaseConfig = {
            apiKey: "AIzaSyDzSPll8gZKWBhmD6o-QAAnT89TWucFkr0",
            authDomain: "salty-parrot.firebaseapp.com",
            projectId: "salty-parrot",
            storageBucket: "salty-parrot.appspot.com",
            messagingSenderId: "598113689428",
            appId: "1:598113689428:web:fb57b75af8efc6e051f2c1"
        };
        
        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Dashboard loaded");
            
            const outputDisplay = document.getElementById('output-display');
            const clearOutputBtn = document.getElementById('clear-output-btn');
            const printOutputBtn = document.getElementById('print-output-btn');
            const createCharacterBtn = document.getElementById('create-character-btn');
            
            // Update user information in the dashboard
            firebase.auth().onAuthStateChanged((user) => {
                if (user) {
                    // User is signed in
                    console.log("Dashboard: User is signed in:", user.email);
                    const userEmail = document.getElementById('user-email');
                    if (userEmail) {
                        userEmail.textContent = user.email;
                    }
                    
                    // Register user in database
                    const formData = new FormData();
                    formData.append('user_id', user.uid);
                    formData.append('user_email', user.email);
                    
                    fetch('../api/register_user.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status !== 'success') {
                            console.error("Error registering user:", data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error registering user:", error);
                    });
                    
                    // Load user data
                    loadUserData(user.uid);
                    
                    // Add event listener to logout buttons
                    const logoutBtn = document.getElementById('logout-btn');
                    if (logoutBtn) {
                        logoutBtn.addEventListener('click', logoutUser);
                    }
                    
                    const logoutBtnTop = document.getElementById('logout-btn-top');
                    if (logoutBtnTop) {
                        logoutBtnTop.addEventListener('click', logoutUser);
                    }
                } else {
                    // User is signed out, redirect to login
                    console.log("Dashboard: User is not signed in, redirecting to login");
                    window.location.href = '../index.php';
                }
            });
            
            function logoutUser() {
                firebase.auth().signOut().then(() => {
                    console.log('User signed out');
                    window.location.href = '../index.php';
                }).catch((error) => {
                    console.error('Logout Error:', error);
                });
            }
            
            // Function to load user data
            function loadUserData(userId) {
                console.log("Loading data for user:", userId);
                // This is where you'd fetch the user's characters and recent activity
                // from your database and update the UI
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
    </script>

    <!-- Game Log Script -->
    <script src="../js/game-log.js"></script>
</body>
</html>
