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
                </div>
            </div>
            
            <div class="dashboard-content">
                <h2>Welcome, Pirate!</h2>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <h3>Quick Tools</h3>
                        <div class="tools-grid">
                            <button class="tool-button" id="quick-dice">
                                <i class="fas fa-dice-d20"></i>
                                <span>Roll Dice</span>
                            </button>
                            <button class="tool-button" id="quick-character">
                                <i class="fas fa-user"></i>
                                <span>Characters</span>
                            </button>
                            <button class="tool-button" id="quick-npc">
                                <i class="fas fa-user-friends"></i>
                                <span>Generate NPC</span>
                            </button>
                            <button class="tool-button" id="quick-ship">
                                <i class="fas fa-ship"></i>
                                <span>Generate Ship</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3>Your Characters</h3>
                        <div id="characters-list">
                            <p>You haven't created any characters yet.</p>
                            <button class="btn btn-outline" id="create-character-btn">
                                <i class="fas fa-plus"></i> Create Character
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-grid" style="margin-top: 20px;">
                    <div class="dashboard-card">
                        <h3>Recent Activity</h3>
                        <div id="recent-activity">
                            <p>No recent activity to display.</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3>Pirate Resources</h3>
                        <ul style="list-style: none; padding-left: 10px;">
                            <li style="margin-bottom: 10px;">
                                <i class="fas fa-book" style="color: var(--secondary); margin-right: 8px;"></i>
                                <a href="#" style="color: var(--light); text-decoration: none;">Pirate Borg Rules Reference</a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <i class="fas fa-compass" style="color: var(--secondary); margin-right: 8px;"></i>
                                <a href="#" style="color: var(--light); text-decoration: none;">Navigation Guide</a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <i class="fas fa-map" style="color: var(--secondary); margin-right: 8px;"></i>
                                <a href="#" style="color: var(--light); text-decoration: none;">Caribbean Maps</a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <i class="fas fa-users" style="color: var(--secondary); margin-right: 8px;"></i>
                                <a href="#" style="color: var(--light); text-decoration: none;">Crew Management</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Firebase with v9 SDK (using modules) -->
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
                            logoutBtn.addEventListener('click', function() {
                                firebase.auth().signOut().then(() => {
                                    console.log('User signed out');
                                    window.location.href = '../index.php';
                                }).catch((error) => {
                                    console.error('Logout Error:', error);
                                });
                            });
                        }
                    }
                    
                    // Enable navigation buttons
                    const disabledButtons = document.querySelectorAll('.sidebar-btn.disabled');
                    disabledButtons.forEach(button => {
                        button.classList.remove('disabled');
                    });
                    
                    // Here you would load the user's characters, recent activity, etc.
                    loadUserData(user.uid);
                } else {
                    // User is signed out, redirect to login
                    console.log("Dashboard: User is not signed in, redirecting to login");
                    window.location.href = 'login.php';
                }
            });
            
            // Function to load user data (placeholder for now)
            function loadUserData(userId) {
                console.log("Loading data for user:", userId);
                // This would be where you fetch data from your database
            }
            
            // Event listeners for buttons
            const quickDice = document.getElementById('quick-dice');
            if (quickDice) {
                quickDice.addEventListener('click', function() {
                    console.log("Dice roller clicked");
                    // Implement dice roller functionality
                });
            }
            
            const quickCharacter = document.getElementById('quick-character');
            if (quickCharacter) {
                quickCharacter.addEventListener('click', function() {
                    console.log("Character manager clicked");
                    // Navigate to character management page
                });
            }
            
            const quickNpc = document.getElementById('quick-npc');
            if (quickNpc) {
                quickNpc.addEventListener('click', function() {
                    console.log("NPC generator clicked");
                    // Implement NPC generator functionality
                });
            }
            
            const quickShip = document.getElementById('quick-ship');
            if (quickShip) {
                quickShip.addEventListener('click', function() {
                    console.log("Ship generator clicked");
                    window.location.href = 'ship_generator.php';
                });
            }
            
            const createCharacterBtn = document.getElementById('create-character-btn');
            if (createCharacterBtn) {
                createCharacterBtn.addEventListener('click', function() {
                    console.log("Create character clicked");
                    // Navigate to character creation page
                });
            }
        });
    </script>
</body>
</html>
