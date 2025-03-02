<?php
// Set the current page
$current_page = 'index';
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
        <?php include './components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <header>
                <div class="pirate-flag">
                    <i class="fas fa-skull-crossbones"></i>
                </div>
                <h1>The Salty Parrot</h1>
                <p class="tagline">A Pirate Borg Toolbox</p>
            </header>
            
            <div class="coming-soon">
                <h2>Setting Sail Soon!</h2>
                <p>We're currently building a comprehensive web toolbox for Pirate Borg game masters and players. Check back soon for character sheets, random generators, combat tools, and more pirate-themed RPG goodness!</p>
                
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p class="progress-text">Website Progress: 25% - Building the ship...</p>
                </div>
                
                <div class="auth-buttons">
                    <a href="pages/login.php" class="btn btn-primary">Login</a>
                    <a href="pages/signup.php" class="btn btn-outline">Sign Up</a>
                </div>
            </div>
            
            <div class="features">
                <div class="feature">
                    <i class="fas fa-dice-d20"></i>
                    <h3>Game Mechanics</h3>
                    <p>Easy-to-use dice rollers, combat calculators, and other tools to streamline your pirate adventures.</p>
                </div>
                <div class="feature">
                    <i class="fas fa-user-friends"></i>
                    <h3>Character Management</h3>
                    <p>Create, save, and manage your Pirate Borg characters with our intuitive character sheets.</p>
                </div>
                <div class="feature">
                    <i class="fas fa-compass"></i>
                    <h3>Random Generators</h3>
                    <p>Generate NPCs, ships, treasures, and more with the click of a button!</p>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Firebase with compatibility mode -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    
    <script>
        // Initialize Firebase with compatibility version
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
            console.log("Homepage DOM loaded");
            
            const authSection = document.getElementById('auth-section');
            const loginBtn = document.getElementById('login-btn');
            
            // Check auth state
            firebase.auth().onAuthStateChanged((user) => {
                if (user) {
                    // User is signed in
                    console.log("User is signed in:", user.email);
                    
                    // Update sidebar auth section
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
                                    window.location.reload();
                                }).catch((error) => {
                                    console.error('Logout Error:', error);
                                });
                            });
                        }
                    }
                } else {
                    // User is signed out
                    console.log("User is signed out");
                    
                    // Update sidebar auth section
                    if (authSection) {
                        authSection.innerHTML = `
                            <button id="login-btn" class="sidebar-btn">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        `;
                        
                        // Add event listener to the login button
                        const loginBtnUpdated = document.getElementById('login-btn');
                        if (loginBtnUpdated) {
                            loginBtnUpdated.addEventListener('click', function() {
                                console.log("Login button clicked");
                                window.location.href = 'pages/login.php';
                            });
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
