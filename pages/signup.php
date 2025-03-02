<?php
// Set the current page
$current_page = 'signup';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - The Salty Parrot</title>
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
                <h2>Create an Account</h2>
                <div id="error-message" class="error-message" style="display: none;"></div>
                
                <form id="signup-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" required>
                    </div>
                    
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" required>
            </div>
                    
                    <button type="submit" class="btn btn-primary">Sign Up</button>
                </form>
                
                <div class="auth-links">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                    <p><a href="../index.php">Back to Home</a></p>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Direct signup script - no module imports -->
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
            console.log("Signup page loaded");
            
            // Get form and error message elements
            const signupForm = document.getElementById('signup-form');
            const errorMessage = document.getElementById('error-message');
            
            console.log("Signup form found:", signupForm ? true : false);
            
            // Check if user is already signed in
            firebase.auth().onAuthStateChanged((user) => {
                if (user) {
                    // User is already signed in, redirect to home
                    console.log("User is already signed in:", user.email);
                    window.location.href = "../index.php";
                }
            });
            
            // Handle signup form submission
            if (signupForm) {
                signupForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log("Signup form submitted");
                    
                    const email = document.getElementById('email').value;
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm-password').value;
                    
                    // Clear previous error message
                    if (errorMessage) {
                        errorMessage.textContent = "";
                        errorMessage.style.display = 'none';
                    }
                    
                    // Check if passwords match
                    if (password !== confirmPassword) {
                        errorMessage.textContent = "Passwords do not match";
                        errorMessage.style.display = 'block';
                        return;
                    }
                    
                    console.log("Attempting signup with:", email);
                    
                    // Create account with Firebase
                    firebase.auth().createUserWithEmailAndPassword(email, password)
                        .then((userCredential) => {
                            // Signed up
                            console.log("Signup successful for:", userCredential.user.email);
                            window.location.href = "../index.php";
                        })
                        .catch((error) => {
                            console.error("Signup error:", error);
                            if (errorMessage) {
                                errorMessage.textContent = error.message;
                                errorMessage.style.display = 'block';
                            }
                        });
                });
            }
        });
    </script>
</body>
</html>
