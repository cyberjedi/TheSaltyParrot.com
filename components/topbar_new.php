<?php
/**
 * Topbar Navigation Component (New Design)
 * 
 * A minimalist topbar with hamburger menu for The Salty Parrot
 */
?>
<div class="topbar">
    <div class="topbar-container">
        <!-- Logo could go here later -->
        <div class="topbar-logo">
            <!-- Placeholder for logo -->
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
    
    <!-- Dropdown menu (empty for now) -->
    <div id="dropdown-menu" class="dropdown-menu">
        <!-- Menu items will be added later -->
    </div>
</div>

<script>
// Simple toggle for the dropdown menu
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const dropdownMenu = document.getElementById('dropdown-menu');
    
    if (menuToggle && dropdownMenu) {
        menuToggle.addEventListener('click', function() {
            dropdownMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
});
</script>