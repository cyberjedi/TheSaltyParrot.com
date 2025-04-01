<?php
/**
 * The Salty Parrot - New Index Page
 * 
 * A clean, minimal design with only essential functionality
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include Firebase configuration
require_once 'config/firebase-config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/discord.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        body {
            background-color: var(--dark);
            color: var(--light);
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <!-- Include the topbar -->
    <?php include 'components/topbar.php'; ?>
    
    <!-- Main Content Area -->
    <main class="main-content-new">
        <div class="welcome-container">
            <img src="assets/TSP_Logo_3inch.svg" alt="The Salty Parrot Logo" class="welcome-logo">
            <h1 class="welcome-title">Welcome to The Salty Parrot</h1>
            <p class="welcome-message">
                Your PIRATE BORG companion for nautical adventures
            </p>
            <?php if (!is_firebase_authenticated()): ?>
                <div class="auth-buttons">
                    <button id="login-btn" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                    <button id="signup-btn" class="btn btn-secondary">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Auth Modal -->
    <div id="auth-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="auth-modal-title">Login</h2>
            <form id="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="button" id="google-auth-btn" class="btn btn-google">
                    <i class="fab fa-google"></i> Continue with Google
                </button>
            </form>
            <div id="auth-error" class="error-message"></div>
        </div>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Firebase Auth Script -->
    <script type="module">
        import { signInWithEmail, signUpWithEmail, signInWithGoogle } from './js/firebase-auth.js';

        // Get DOM elements
        const loginBtn = document.getElementById('login-btn');
        const signupBtn = document.getElementById('signup-btn');
        const authModal = document.getElementById('auth-modal');
        const closeBtn = document.querySelector('.close');
        const authForm = document.getElementById('auth-form');
        const authModalTitle = document.getElementById('auth-modal-title');
        const googleAuthBtn = document.getElementById('google-auth-btn');
        const authError = document.getElementById('auth-error');

        // Show modal
        function showModal(isSignup = false) {
            authModal.style.display = 'block';
            authModalTitle.textContent = isSignup ? 'Sign Up' : 'Login';
            authForm.dataset.mode = isSignup ? 'signup' : 'login';
        }

        // Hide modal
        function hideModal() {
            authModal.style.display = 'none';
            authForm.reset();
            authError.textContent = '';
        }

        // Event listeners
        loginBtn?.addEventListener('click', () => showModal(false));
        signupBtn?.addEventListener('click', () => showModal(true));
        closeBtn?.addEventListener('click', hideModal);
        window.addEventListener('click', (e) => {
            if (e.target === authModal) hideModal();
        });

        // Form submission
        authForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const isSignup = authForm.dataset.mode === 'signup';

            try {
                const result = isSignup 
                    ? await signUpWithEmail(email, password)
                    : await signInWithEmail(email, password);

                if (result.success) {
                    hideModal();
                    window.location.reload();
                } else {
                    authError.textContent = result.error;
                }
            } catch (error) {
                authError.textContent = error.message;
            }
        });

        // Google auth
        googleAuthBtn?.addEventListener('click', async () => {
            try {
                const result = await signInWithGoogle();
                if (result.success) {
                    hideModal();
                    window.location.reload();
                } else {
                    authError.textContent = result.error;
                }
            } catch (error) {
                authError.textContent = error.message;
            }
        });
    </script>
</body>
</html>