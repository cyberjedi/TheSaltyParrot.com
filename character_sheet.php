<?php
/**
 * Character Sheet Page
 * 
 * This is the main entry point for the character sheet feature.
 * It loads the character controller which handles data and logic,
 * then includes the view to display the character information.
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set the current page for sidebar highlighting
$current_page = 'character_sheet';

// Set base path for consistent loading
$base_path = './';

// Discord integration - safely load if available
$discord_enabled = false;
if (file_exists('discord/discord-config.php')) {
    // Try to include the Discord configuration
    try {
        require_once 'discord/discord-config.php';
        $discord_enabled = true;
    } catch (Exception $e) {
        error_log('Discord integration error: ' . $e->getMessage());
        $discord_enabled = false;
    }
}

// Set Discord authentication status
$discord_authenticated = function_exists('is_discord_authenticated') && is_discord_authenticated();

// Load the character controller - handles data and logic
require_once 'components/character_controller.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Sheet - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/character_sheet.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/inventory.css?v=<?php echo time(); ?>">
    <?php if ($discord_enabled && file_exists('css/discord.css')): ?>
    <link rel="stylesheet" href="css/discord.css">
    <?php endif; ?>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <?php 
            // Include the character sheet view component
            include 'components/character_sheet_view.php'; 
            ?>
 
            <!-- Emergency Button Fix for ensuring critical buttons work -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Wait for all other scripts to initialize
                setTimeout(function() {
                    console.log('[Emergency Fix] Checking for buttons without handlers');
                    
                    // Critical buttons that must work
                    const criticalButtons = {
                        'edit-character-btn': function() {
                            const modal = document.getElementById('edit-character-modal');
                            if (modal) {
                                document.querySelectorAll('.modal').forEach(m => {
                                    m.style.display = 'none';
                                    m.classList.remove('active');
                                });
                                modal.style.display = 'block';
                                modal.classList.add('active');
                            }
                        },
                        'switch-character-btn': function() {
                            const modal = document.getElementById('character-switcher-modal');
                            if (modal) {
                                document.querySelectorAll('.modal').forEach(m => {
                                    m.style.display = 'none';
                                    m.classList.remove('active');
                                });
                                modal.style.display = 'block';
                                modal.classList.add('active');
                            }
                        },
                        'print-character-btn': function() {
                            window.print();
                        },
                        'new-character-btn': function() {
                            const form = document.getElementById('edit-character-form');
                            if (form) form.reset();
                            
                            const idField = document.querySelector('input[name="character_id"]');
                            if (idField) idField.value = '';
                            
                            document.getElementById('name').value = 'New Pirate';
                            document.getElementById('strength').value = '0';
                            document.getElementById('agility').value = '0';
                            document.getElementById('presence').value = '0';
                            document.getElementById('toughness').value = '0';
                            document.getElementById('spirit').value = '0';
                            
                            const imagePreview = document.getElementById('image-preview');
                            if (imagePreview) {
                                imagePreview.src = 'assets/TSP_default_character.jpg';
                            }
                            
                            const modal = document.getElementById('edit-character-modal');
                            if (modal) {
                                document.querySelectorAll('.modal').forEach(m => {
                                    m.style.display = 'none';
                                    m.classList.remove('active');
                                });
                                modal.style.display = 'block';
                                modal.classList.add('active');
                            }
                        }
                    };
                    
                    // Check each critical button
                    for (const [id, handler] of Object.entries(criticalButtons)) {
                        const button = document.getElementById(id);
                        if (!button) {
                            console.warn(`[Emergency Fix] Button #${id} not found`);
                            continue;
                        }
                        
                        // Apply "nuclear option" - clone and replace with working button
                        const clone = button.cloneNode(true);
                        button.parentNode.replaceChild(clone, button);
                        
                        // Add event listener to the cloned button
                        clone.addEventListener('click', handler);
                        console.log(`[Emergency Fix] Repaired button #${id}`);
                    }
                    
                    // Ensure all close buttons work
                    document.querySelectorAll('.close-modal, .close-modal-btn').forEach(closeBtn => {
                        const clone = closeBtn.cloneNode(true);
                        closeBtn.parentNode.replaceChild(clone, closeBtn);
                        
                        clone.addEventListener('click', function() {
                            document.querySelectorAll('.modal').forEach(modal => {
                                modal.style.display = 'none';
                                modal.classList.remove('active');
                            });
                        });
                    });
                    
                    // Ensure modal background clicks work
                    document.querySelectorAll('.modal').forEach(modal => {
                        modal.addEventListener('click', function(event) {
                            if (event.target === this) {
                                this.style.display = 'none';
                                this.classList.remove('active');
                            }
                        });
                    });
                    
                    console.log('[Emergency Fix] All critical buttons checked and fixed');
                }, 1500); // Wait 1.5 seconds to ensure all other scripts have had time to run
            });
            </script>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>

    <!-- Make character data and authentication status available to JavaScript -->
    <script>
        // Pass character data to JavaScript
        window.character_data = <?php echo json_encode($character ?? []); ?>;
        
        // Pass Discord authentication status to JavaScript
        window.discord_authenticated = <?php echo $discord_authenticated ? 'true' : 'false'; ?>;
        
        // Pass base URL for API calls
        window.base_url = '<?php echo $base_path; ?>';
    </script>
    
    <!-- Load JavaScript files -->
    <!-- Debug utilities - load first to trace everything -->
    <script src="js/debug_utils.js?v=<?php echo time(); ?>"></script>
    
    <!-- Main application scripts -->
    <script src="js/character_sheet.js?v=<?php echo time(); ?>"></script>
    <script src="js/inventory.js?v=<?php echo time(); ?>"></script>
    <script src="js/inventory_containers.js?v=<?php echo time(); ?>"></script>
    
    <!-- Discord integration (only when authenticated) -->
    <?php if ($discord_enabled && $discord_authenticated): ?>
    <script src="js/discord_integration.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>
    
    <!-- Diagnostic script to check button functionality directly -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Direct button diagnostic script executing');
        
        // Test direct event attachment
        const printBtn = document.getElementById('print-character-btn');
        if (printBtn) {
            console.log('Found print button in diagnostic script');
            printBtn.addEventListener('click', function() {
                console.log('Print button clicked via direct handler');
                alert('Print button clicked via diagnostic handler');
                window.print();
            });
        } else {
            console.error('Print button not found in diagnostic script');
        }
        
        // List all buttons in the document
        const allButtons = document.querySelectorAll('button');
        console.log('Total buttons found:', allButtons.length);
        allButtons.forEach((btn, index) => {
            console.log(`Button ${index}:`, btn.id || '(no id)', btn.textContent.trim());
        });
    });
    </script>

    <!-- Auth Transition Fix - addresses issues during authentication transitions -->
    <script>
    (function() {
        'use strict';
        
        // Check if this is a post-authentication page load
        const isPostAuth = document.referrer.includes('discord-callback.php') || 
                           localStorage.getItem('discord_auth_transition') === 'true';
        
        if (isPostAuth) {
            console.log('[Auth Fix] Detected post-authentication page load');
            // Clear the flag if it was set
            localStorage.removeItem('discord_auth_transition');
            
            // Wait for DOM to be fully loaded and all other scripts to initialize
            setTimeout(function() {
                console.log('[Auth Fix] Applying post-auth fixes');
                
                // Check for critical buttons and ensure they have handlers
                const criticalButtons = [
                    'edit-character-btn', 
                    'switch-character-btn', 
                    'print-character-btn',
                    'new-character-btn'
                ];
                
                criticalButtons.forEach(btnId => {
                    const btn = document.getElementById(btnId);
                    if (!btn) return;
                    
                    // Check if the button has event listeners by testing for our marker attribute
                    if (!btn.getAttribute('data-has-handler')) {
                        console.log(`[Auth Fix] Re-attaching handler to ${btnId}`);
                        
                        // Re-attach appropriate handler based on button ID
                        if (btnId === 'edit-character-btn') {
                            btn.addEventListener('click', function() {
                                const editModal = document.getElementById('edit-character-modal');
                                if (editModal) {
                                    // Close all other modals first
                                    document.querySelectorAll('.modal').forEach(m => {
                                        m.style.display = 'none';
                                        m.classList.remove('active');
                                    });
                                    
                                    editModal.style.display = 'block';
                                    editModal.classList.add('active');
                                }
                            });
                        } else if (btnId === 'switch-character-btn') {
                            btn.addEventListener('click', function() {
                                const switcherModal = document.getElementById('character-switcher-modal');
                                if (switcherModal) {
                                    // Close all other modals first
                                    document.querySelectorAll('.modal').forEach(m => {
                                        m.style.display = 'none';
                                        m.classList.remove('active');
                                    });
                                    
                                    switcherModal.style.display = 'block';
                                    switcherModal.classList.add('active');
                                }
                            });
                        } else if (btnId === 'print-character-btn') {
                            btn.addEventListener('click', function() {
                                window.print();
                            });
                        } else if (btnId === 'new-character-btn') {
                            btn.addEventListener('click', function() {
                                // Reset the form for a new character
                                const form = document.getElementById('edit-character-form');
                                if (form) {
                                    form.reset();
                                    const idField = document.querySelector('input[name="character_id"]');
                                    if (idField) idField.value = '';
                                    
                                    // Set default values
                                    const nameField = document.getElementById('name');
                                    if (nameField) nameField.value = 'New Pirate';
                                    
                                    const statFields = ['strength', 'agility', 'presence', 'toughness', 'spirit'];
                                    statFields.forEach(stat => {
                                        const field = document.getElementById(stat);
                                        if (field) field.value = '0';
                                    });
                                    
                                    // Reset image preview to default
                                    const imagePreview = document.getElementById('image-preview');
                                    if (imagePreview) {
                                        imagePreview.src = 'assets/TSP_default_character.jpg';
                                    }
                                }
                                
                                // Show the edit modal
                                const editModal = document.getElementById('edit-character-modal');
                                if (editModal) {
                                    // Close all other modals first
                                    document.querySelectorAll('.modal').forEach(m => {
                                        m.style.display = 'none';
                                        m.classList.remove('active');
                                    });
                                    
                                    editModal.style.display = 'block';
                                    editModal.classList.add('active');
                                }
                            });
                        }
                        
                        // Mark as having a handler now
                        btn.setAttribute('data-has-handler', 'true');
                    }
                });
                
                // Ensure all close buttons work
                document.querySelectorAll('.close-modal, .close-modal-btn').forEach(closeBtn => {
                    if (!closeBtn.getAttribute('data-has-handler')) {
                        closeBtn.addEventListener('click', function() {
                            // Close all modals
                            document.querySelectorAll('.modal').forEach(modal => {
                                modal.style.display = 'none';
                                modal.classList.remove('active');
                            });
                        });
                        closeBtn.setAttribute('data-has-handler', 'true');
                    }
                });
                
                // Reinforce modal outside-click handling
                document.querySelectorAll('.modal').forEach(modal => {
                    if (!modal.getAttribute('data-outside-click-handler')) {
                        modal.addEventListener('click', function(event) {
                            if (event.target === this) {
                                this.style.display = 'none';
                                this.classList.remove('active');
                            }
                        });
                        modal.setAttribute('data-outside-click-handler', 'true');
                    }
                });
                
                console.log('[Auth Fix] Post-auth fixes applied');
            }, 1000); // Wait 1 second to ensure other scripts have loaded
        }
        
        // Set flag when navigating to Discord authentication
        document.addEventListener('click', function(event) {
            // Check if it's a Discord login link
            if (event.target.closest('a[href*="discord-login.php"]')) {
                localStorage.setItem('discord_auth_transition', 'true');
                console.log('[Auth Fix] Discord auth transition detected, setting flag');
            }
        });
    })();
    </script>
</body>
</html>
