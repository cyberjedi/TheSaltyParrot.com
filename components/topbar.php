<?php
/**
 * Topbar Navigation Component
 * 
 * A modern topbar with logo, authentication and navigation
 */

// Include Firebase configuration
require_once __DIR__ . '/../config/firebase-config.php';
?>
<div class="topbar">
    <div class="topbar-container">
        <!-- Logo in the upper left corner -->
        <a href="index.php" class="topbar-logo">
            <img src="assets/TSP_Logo_3inch.svg" alt="The Salty Parrot" height="40">
        </a>

        <!-- Navigation buttons -->
        <div class="topbar-nav">
            <a href="generators.php" class="nav-link">
                <i class="fas fa-dice"></i> Generators
            </a>
            <a href="sheets.php" class="nav-link">
                <i class="fas fa-scroll"></i> Character Sheets
            </a>
        </div>
        
        <!-- Authentication buttons -->
        <div class="topbar-auth">
            <?php if (is_firebase_authenticated()): ?>
                <a href="account.php" class="btn btn-primary">
                    <i class="fas fa-user"></i> Account
                </a>
                <button id="signout-btn" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </button>
            <?php else: ?>
                <button id="login-btn" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <button id="signup-btn" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> Sign Up
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Hamburger menu icon - always visible, never hidden -->
        <div class="hamburger-menu" style="display: block !important; z-index: 1001;">
            <button id="menu-toggle" class="menu-toggle" aria-label="Toggle menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </div>
    
    <!-- Dropdown menu -->
    <div id="dropdown-menu" class="dropdown-menu">
        <!-- Navigation Links -->
        <div class="dropdown-section">
            <h3>Navigation</h3>
            <a href="generators.php" class="menu-item">
                <i class="fas fa-dice"></i> Generators
            </a>
            <a href="sheets.php" class="menu-item">
                <i class="fas fa-scroll"></i> Character Sheets
            </a>
        </div>
        
        <?php if (is_firebase_authenticated()): ?>
            <!-- Account Options -->
            <div class="dropdown-section">
                <h3>Account</h3>
                <a href="account.php" class="menu-item">
                    <i class="fas fa-user"></i> Account Settings
                </a>
                <button id="mobile-signout-btn" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </button>
            </div>
        <?php else: ?>
            <!-- Auth Options -->
            <div class="dropdown-section">
                <h3>Authentication</h3>
                <button id="mobile-login-btn" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <button id="mobile-signup-btn" class="menu-item">
                    <i class="fas fa-user-plus"></i> Sign Up
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Auth Modal -->
<div id="auth-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
    <div class="modal-content" style="background-color: #41C8D4 !important; margin: 15% auto; padding: 30px; border: 1px solid rgba(0, 0, 0, 0.2); border-radius: 8px; width: 90%; max-width: 500px; position: relative;">
        <span class="close" style="position: absolute; right: 20px; top: 20px; color: #000; font-size: 24px; font-weight: bold; cursor: pointer;">&times;</span>
        <h2 id="auth-modal-title" style="color: #000; margin-bottom: 20px; font-size: 1.5rem;">Login</h2>
        <form id="auth-form">
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="email" style="display: block; margin-bottom: 8px; color: #000; font-weight: 500;">Email</label>
                <input type="email" id="email" name="email" required style="width: 100%; padding: 12px; border: 1px solid rgba(0, 0, 0, 0.2); border-radius: 4px; background-color: rgba(255, 255, 255, 0.9); color: #000; font-size: 16px;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="password" style="display: block; margin-bottom: 8px; color: #000; font-weight: 500;">Password</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 12px; border: 1px solid rgba(0, 0, 0, 0.2); border-radius: 4px; background-color: rgba(255, 255, 255, 0.9); color: #000; font-size: 16px;">
            </div>
            <button type="submit" class="btn btn-primary" style="background: #000 !important; color: #fff !important; border: none; padding: 12px; border-radius: 4px; font-weight: 600; cursor: pointer; width: 100%; margin-bottom: 15px; font-size: 16px;">Submit</button>
            <button type="button" id="google-auth-btn" class="btn btn-google" style="background: #4285f4 !important; color: #fff !important; border: none; padding: 12px; border-radius: 4px; font-weight: 600; cursor: pointer; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 16px;">
                <i class="fab fa-google"></i> Continue with Google
            </button>
        </form>
        <div id="auth-error" class="error-message" style="color: #dc3545; margin-top: 15px; text-align: center;"></div>
    </div>
</div>

<script type="module">
import { signOutUser } from '../js/firebase-auth.js';
import { signInWithEmail, signUpWithEmail, signInWithGoogle } from '../js/firebase-auth.js';

document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const dropdownMenu = document.getElementById('dropdown-menu');
    const signoutBtn = document.getElementById('signout-btn');
    const mobileSignoutBtn = document.getElementById('mobile-signout-btn');
    const loginBtn = document.getElementById('login-btn');
    const signupBtn = document.getElementById('signup-btn');
    const mobileLoginBtn = document.getElementById('mobile-login-btn');
    const mobileSignupBtn = document.getElementById('mobile-signup-btn');
    const authModal = document.getElementById('auth-modal');
    const closeBtn = document.querySelector('.close');
    const authForm = document.getElementById('auth-form');
    const authModalTitle = document.getElementById('auth-modal-title');
    const googleAuthBtn = document.getElementById('google-auth-btn');
    const authError = document.getElementById('auth-error');
    
    // Toggle menu
    menuToggle?.addEventListener('click', function() {
        dropdownMenu.classList.toggle('active');
        menuToggle.classList.toggle('active');
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!menuToggle.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.remove('active');
            menuToggle.classList.remove('active');
        }
    });

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

    // Event listeners for login/signup
    loginBtn?.addEventListener('click', () => showModal(false));
    signupBtn?.addEventListener('click', () => showModal(true));
    mobileLoginBtn?.addEventListener('click', () => {
        showModal(false);
        dropdownMenu.classList.remove('active');
        menuToggle.classList.remove('active');
    });
    mobileSignupBtn?.addEventListener('click', () => {
        showModal(true);
        dropdownMenu.classList.remove('active');
        menuToggle.classList.remove('active');
    });
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

    // Handle sign out
    async function handleSignOut() {
        try {
            const result = await signOutUser();
            if (result.success) {
                window.location.reload();
            } else {
                console.error('Error signing out:', result.error);
            }
        } catch (error) {
            console.error('Error signing out:', error);
        }
    }

    signoutBtn?.addEventListener('click', handleSignOut);
    mobileSignoutBtn?.addEventListener('click', handleSignOut);
});
</script>