<?php
// Set the current page
$current_page = 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include '../components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <header>
                <div class="pirate-flag">
                    <i class="fas fa-skull-crossbones"></i>
                </div>
                <h1>The Salty Parrot</h1>
                <p class="tagline">A Pirate Borg Toolbox</p>
            </header>
            
            <div class="auth-form-container">
                <h2>Login</h2>
                <div id="error-message" class="error-message" style="display: none;"></div>
                
                <form id="login-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                
                <div class="auth-links">
                    <p><a href="#" id="forgot-password">Forgot Password?</a></p>
                    <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
                    <p><a href="../index.php">Back to Home</a></p>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Direct login script - no module imports -->
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
            console.log("Login page loaded");
            
            // Get form and error message elements
            const loginForm = document.getElementById('login-form');
            const errorMessage = document.getElementById('error-message');
            const forgotPasswordLink = document.getElementById('forgot-password');
            
            console.log("Login form found:", loginForm ? true : false);
            console.log("Forgot password link found:", forgotPasswordLink ? true : false);
            
            // Handle login form submission
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log("Login form submitted");
                    
                    const email = document.getElementById('email').value;
                    const password = document.getElementById('password').value;
                    
                    // Clear previous error message
                    if (errorMessage) {
                        errorMessage.textContent = "";
                        errorMessage.style.display = 'none';
                    }
                    
                    console.log("Attempting login with:", email);
                    
                    // Sign in with Firebase
                    firebase.auth().signInWithEmailAndPassword(email, password)
                        .then((userCredential) => {
                            // Signed in
                            console.log("Login successful for:", userCredential.user.email);
                            window.location.href = "../index.php";
                        })
                        .catch((error) => {
                            console.error("Login error:", error);
                            if (errorMessage) {
                                errorMessage.textContent = error.message;
                                errorMessage.style.display = 'block';
                            }
                        });
                });
            }
            
            // Handle forgot password
            if (forgotPasswordLink) {
                forgotPasswordLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log("Forgot password clicked");
                    
                    const email = document.getElementById('email').value;
                    
                    if (!email) {
                        errorMessage.textContent = "Please enter your email address";
                        errorMessage.style.display = 'block';
                        return;
                    }
                    
                    firebase.auth().sendPasswordResetEmail(email)
                        .then(() => {
                            errorMessage.textContent = "Password reset email sent. Check your inbox.";
                            errorMessage.style.display = 'block';
                            errorMessage.style.backgroundColor = "rgba(46, 204, 113, 0.2)";
                            errorMessage.style.color = '#2ecc71';
                        })
                        .catch((error) => {
                            errorMessage.textContent = error.message;
                            errorMessage.style.display = 'block';
                        });
                });
            }
            
            // Check auth state
            firebase.auth().onAuthStateChanged((user) => {
                if (user) {
                    // User is signed in
                    console.log("User is already signed in:", user.email);
                    window.location.href = "../index.php";
                }
            });
        });
    </script>
</body>
</html>
