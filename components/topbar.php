<?php
/**
 * Topbar Navigation Component
 * 
 * A minimalist topbar with authentication and navigation
 */

// Include Firebase configuration
require_once __DIR__ . '/../config/firebase-config.php';
?>
<div class="topbar">
    <div class="topbar-container">
        <!-- Authentication buttons -->
        <div class="topbar-auth">
            <?php if (is_firebase_authenticated()): ?>
                <a href="account.php" class="btn btn-primary">
                    <i class="fas fa-user"></i> Account
                </a>
                <button id="signout-btn" class="btn btn-danger">
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
        
        <!-- Navigation buttons -->
        <div class="topbar-nav">
            <a href="generators.php" class="btn btn-primary">
                <i class="fas fa-dice"></i> Generators
            </a>
            <a href="character_sheet.php" class="btn btn-primary">
                <i class="fas fa-scroll"></i> Character Sheet
            </a>
        </div>
        
        <!-- Hamburger menu icon -->
        <div class="hamburger-menu">
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
            <a href="character_sheet.php" class="menu-item">
                <i class="fas fa-scroll"></i> Character Sheet
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

<script type="module">
import { signOutUser } from '../js/firebase-auth.js';

document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const dropdownMenu = document.getElementById('dropdown-menu');
    const signoutBtn = document.getElementById('signout-btn');
    const mobileSignoutBtn = document.getElementById('mobile-signout-btn');
    
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

    // Handle sign out
    async function handleSignOut() {
        try {
            const result = await signOutUser();
            if (result.success) {
                window.location.href = 'index.php';
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