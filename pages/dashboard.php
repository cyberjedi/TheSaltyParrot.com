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
        .tool-button {
            padding: 25px 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .tool-button i {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .tool-button span {
            display: block;
            font-size: 16px;
        }
        
        .tool-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .tools-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .welcome-message {
            margin-bottom: 30px;
            padding: 20px;
            background-color: rgba(191, 157, 97, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--secondary);
        }
        
        .welcome-message h3 {
            color: var(--secondary);
            margin-bottom: 10px;
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
            
            <div class="dashboard-content">
                <div class="welcome-message">
                    <h3>Welcome, Captain!</h3>
                    <p>Your pirate adventure awaits. Use the tools below to create characters, generate NPCs, ships, and more for your Pirate Borg campaign.</p>
                </div>
                
                <div class="dashboard-card">
                    <h3>Game Tools</h3>
                    <div class="tools-grid">
                        <a href="ship_generator.php" class="tool-button">
                            <i class="fas fa-ship"></i>
                            <span>Ship Generator</span>
                        </a>
                        <a href="loot_generator.php" class="tool-button">
                            <i class="fas fa-coins"></i>
                            <span>Loot Generator</span>
                        </a>
                        <a href="#" class="tool-button" id="dice-roller-btn">
                            <i class="fas fa-dice-d20"></i>
                            <span>Dice Roller</span>
                        </a>
                        <a href="#" class="tool-button" id="char-creator-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Character Creator</span>
                        </a>
                        <a href="#" class="tool-button" id="npc-generator-btn">
                            <i class="fas fa-user-friends"></i>
                            <span>NPC Generator</span>
                        </a>
                        <a href="#" class="tool-button" id="combat-tracker-btn">
                            <i class="fas fa-fist-raised"></i>
                            <span>Combat Tracker</span>
                        </a>
                        <a href="#" class="tool-button" id="treasure-generator-btn">
                            <i class="fas fa-gem"></i>
                            <span>Treasure Generator</span>
                        </a>
                        <a href="#" class="tool-button" id="rules-reference-btn">
                            <i class="fas fa-book"></i>
                            <span>Rules Reference</span>
                        </a>
                    </div>
                </div>
                
                <div class="dashboard-grid" style="margin-top: 20px;">
                    <div class="dashboard-card">
                        <h3>Your Characters</h3>
                        <div id="characters-list">
                            <p>You haven't created any characters yet.</p>
                            <button class="btn btn-outline" id="create-character-btn">
                                <i class="fas fa-plus"></i> Create Character
                            </button>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3>Recent Activity</h3>
                        <div id="recent-activity">
                            <p>No recent activity to display.</p>
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
            console.log("Dashboard.js loaded");
            
            // Update user information in the dashboard
            firebase.auth().onAuthStateChanged((user) => {
                if (user) {
                    // User is signed in
                    console.log("Dashboard: User is signed in:", user.email);
                    const userEmail = document.getElementById('user-email');
                    if (userEmail) {
                        userEmail.textContent = user.email;
                    }
                    
                    // Update sidebar auth section
                    const authSection = document.getElementById('auth-section');
                    if (authSection) {
                        authSection.innerHTML = `
                            <div class="user-info">
                                <div class="username">${user.email}</div>
                            </div>
                            <button id="logout-btn" class="sidebar-btn logout-btn">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        `;
                        
                        // Add event listener to the logout button
                        const logoutBtn = document.getElementById('logout-btn');
                        if (logoutBtn) {
                            logoutBtn.addEventListener('click', logoutUser);
                        }
                    }
                    
                    // Add event listener to top logout button
                    const logoutBtnTop = document.getElementById('logout-btn-top');
                    if (logoutBtnTop) {
                        logoutBtnTop.addEventListener('click', logoutUser);
                    }
                    
                    // Load user data
                    loadUserData(user.uid);
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
            
            // Event listeners for buttons
            const createCharacterBtn = document.getElementById('create-character-btn');
            if (createCharacterBtn) {
                createCharacterBtn.addEventListener('click', function() {
                    console.log("Create character clicked");
                    // Navigate to character creation page (not yet implemented)
                    alert("Character creation is coming soon!");
                });
            }
            
            // Set up event listeners for tool buttons
            document.getElementById('dice-roller-btn').addEventListener('click', function(e) {
                e.preventDefault();
                alert("Dice roller coming soon!");
            });
            
            document.getElementById('char-creator-btn').addEventListener('click', function(e) {
                e.preventDefault();
                alert("Character creator coming soon!");
            });
            
            document.getElementById('npc-generator-btn').addEventListener('click', function(e) {
                e.preventDefault();
                alert("NPC generator coming soon!");
            });
            
            document.getElementById('combat-tracker-btn').addEventListener('click', function(e) {
                e.preventDefault();
                alert("Combat tracker coming soon!");
            });
            
            document.getElementById('treasure-generator-btn').addEventListener('click', function(e) {
                e.preventDefault();
                alert("Treasure generator coming soon!");
            });
            
            document.getElementById('rules-reference-btn').addEventListener('click', function(e) {
                e.preventDefault();
                alert("Rules reference coming soon!");
            });
        });
    </script>
</body>
</html>
