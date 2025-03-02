<?php
// Set the current page
$current_page = 'loot_generator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot Generator - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Additional styling for loot generator */
        .loot-card {
            background-color: var(--dark);
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border: 1px solid rgba(191, 157, 97, 0.3);
        }

        .loot-roll {
            color: var(--secondary);
            font-weight: bold;
            margin-bottom: 5px;
        }

        .loot-name {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--secondary);
            border-bottom: 1px solid rgba(191, 157, 97, 0.2);
            padding-bottom: 5px;
        }

        .loot-description {
            margin-bottom: 5px;
        }

        .loot-category {
            font-style: italic;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .ancient-relic-badge, .thing-of-importance-badge {
            display: inline-block;
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 12px;
            margin-right: 5px;
            margin-bottom: 10px;
        }

        .ancient-relic-badge {
            background-color: rgba(75, 0, 130, 0.5);
            color: #e0c5ff;
            border: 1px solid #9d4edd;
        }

        .thing-of-importance-badge {
            background-color: rgba(0, 100, 0, 0.5);
            color: #c6ffda;
            border: 1px solid #2ea44f;
        }

        .extra-roll-divider {
            margin: 20px 0;
            text-align: center;
            position: relative;
        }

        .extra-roll-divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: rgba(191, 157, 97, 0.3);
            z-index: 0;
        }

        .extra-roll-divider span {
            position: relative;
            background-color: var(--dark);
            padding: 0 15px;
            z-index: 1;
        }
        
        @media print {
            .sidebar, .dashboard-header, .generator-card, footer, 
            #generate-loot-btn, .source-reference, .print-buttons {
                display: none !important;
            }
            
            body, .app-container, .main-content, .loot-generator-content {
                background: white;
                color: black;
                margin: 0;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            
            #loot-result {
                display: block !important;
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 1cm;
            }
            
            .loot-card {
                background-color: white;
                border: 1px solid #ddd;
            }
            
            .loot-roll, .loot-name {
                color: #805d2c;
            }
            
            .ancient-relic-badge {
                background-color: #f0e6ff;
                color: #4b0082;
                border: 1px solid #9d4edd;
            }
            
            .thing-of-importance-badge {
                background-color: #e6ffe6;
                color: #006400;
                border: 1px solid #2ea44f;
            }
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
                    <i class="fas fa-coins"></i>
                    <h1>Loot the Body</h1>
                </div>
            </div>
            
            <div class="loot-generator-content">
                <div class="generator-card dashboard-card">
                    <h2>Generate Random Loot</h2>
                    <p>Click the button below to search a body or open a chest for treasure.</p>
                    <p class="source-reference">
                        <i class="fas fa-book"></i> This generator is based on the "Pirate Borg Core Book" page 3 "Loot the Body" table.
                    </p>
                    <div class="print-buttons">
                        <button id="generate-loot-btn" class="btn btn-primary">
                            <i class="fas fa-coins"></i> Loot the Body
                        </button>
                        <button id="print-loot-btn" class="btn btn-outline">
                            <i class="fas fa-print"></i> Print Loot
                        </button>
                        <button id="home-btn" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Back to Home
                        </button>
                    </div>
                </div>
                
                <div id="loot-result" class="dashboard-card" style="display: none; margin-top: 20px;">
                    <h2>Loot Results</h2>
                    <div id="loot-container">
                        <!-- Loot details will be inserted here -->
                    </div>
                </div>
                
                <div id="error-container" class="dashboard-card" style="display: none; margin-top: 20px; background-color: rgba(220, 53, 69, 0.1); border-color: rgba(220, 53, 69, 0.3);">
                    <h3 style="color: #dc3545;">Error</h3>
                    <p id="error-message">An error occurred while generating loot.</p>
                    <p id="error-details" style="font-family: monospace; background: rgba(0,0,0,0.1); padding: 10px; border-radius: 5px; overflow-x: auto;"></p>
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
            console.log("Loot Generator DOM loaded");
            
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
                    
                    // Enable navigation buttons
                    const disabledButtons = document.querySelectorAll('.sidebar-btn.disabled');
                    disabledButtons.forEach(button => {
                        button.classList.remove('disabled');
                    });
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
                                window.location.href = 'login.php';
                            });
                        }
                    }
                    
                    // Disable navigation buttons except loot generator
                    const navButtons = document.querySelectorAll('.sidebar-btn:not(#login-btn):not(#loot-generator-btn)');
                    navButtons.forEach(button => {
                        if (!button.classList.contains('disabled')) {
                            button.classList.add('disabled');
                        }
                    });
                }
            });
            
            // Loot Generator code
            const generateButton = document.getElementById('generate-loot-btn');
            const printButton = document.getElementById('print-loot-btn');
            const homeButton = document.getElementById('home-btn');
            const lootResult = document.getElementById('loot-result');
            const lootContainer = document.getElementById('loot-container');
            const errorContainer = document.getElementById('error-container');
            const errorMessage = document.getElementById('error-message');
            const errorDetails = document.getElementById('error-details');
            
            // Function to show error
            function showError(message, details = '') {
                errorMessage.textContent = message;
                errorDetails.textContent = details || '';
                errorContainer.style.display = 'block';
                errorContainer.scrollIntoView({ behavior: 'smooth' });
            }
            
            // Function to hide error
            function hideError() {
                errorContainer.style.display = 'none';
            }
            
            // Add event listener to the generate button
            if (generateButton) {
                generateButton.addEventListener('click', generateLoot);
            }
            
            // Add event listener to the print button
            if (printButton) {
                printButton.addEventListener('click', function() {
                    // Check if loot has been generated
                    if (lootResult.style.display === 'none') {
                        alert('Please generate loot first before printing.');
                        return;
                    }
                    
                    // Create a new window for printing just the loot result
                    const printWindow = window.open('', '_blank');
                    
                    // Create the content for the print window
                    const lootResultClone = lootResult.cloneNode(true);
                    
                    // Set up the HTML content for printing
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Loot Results - The Salty Parrot</title>
                            <style>
                                body {
                                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                                    color: #333;
                                    line-height: 1.6;
                                    padding: 20px;
                                }
                                h2 {
                                    color: #805d2c;
                                    border-bottom: 1px solid #805d2c;
                                    padding-bottom: 10px;
                                }
                                .loot-card {
                                    border: 1px solid #ddd;
                                    padding: 15px;
                                    margin: 15px 0;
                                    border-radius: 8px;
                                }
                                .loot-roll {
                                    color: #805d2c;
                                    font-weight: bold;
                                }
                                .loot-name {
                                    font-size: 1.2rem;
                                    color: #805d2c;
                                    border-bottom: 1px solid rgba(128, 93, 44, 0.2);
                                    padding-bottom: 5px;
                                    margin-bottom: 10px;
                                }
                                .loot-category {
                                    font-style: italic;
                                    color: #666;
                                    font-size: 0.9rem;
                                }
                                .ancient-relic-badge {
                                    display: inline-block;
                                    font-size: 0.8rem;
                                    padding: 3px 8px;
                                    border-radius: 12px;
                                    margin-right: 5px;
                                    background-color: #f0e6ff;
                                    color: #4b0082;
                                    border: 1px solid #9d4edd;
                                }
                                .thing-of-importance-badge {
                                    display: inline-block;
                                    font-size: 0.8rem;
                                    padding: 3px 8px;
                                    border-radius: 12px;
                                    margin-right: 5px;
                                    background-color: #e6ffe6;
                                    color: #006400;
                                    border: 1px solid #2ea44f;
                                }
                                .extra-roll-divider {
                                    margin: 20px 0;
                                    text-align: center;
                                    position: relative;
                                }
                                .extra-roll-divider::before {
                                    content: "";
                                    position: absolute;
                                    top: 50%;
                                    left: 0;
                                    right: 0;
                                    height: 1px;
                                    background-color: #ddd;
                                    z-index: 0;
                                }
                                .extra-roll-divider span {
                                    position: relative;
                                    background-color: white;
                                    padding: 0 15px;
                                    z-index: 1;
                                }
                            </style>
                        </head>
                        <body>
                            ${lootResultClone.outerHTML}
                        </body>
                        </html>
                    `);
                    
                    // Wait for content to load and then print
                    printWindow.document.close();
                    printWindow.addEventListener('load', function() {
                        printWindow.print();
                        printWindow.close();
                    });
                });
            }
            
            // Add event listener to the home button
            if (homeButton) {
                homeButton.addEventListener('click', function() {
                    window.location.href = '../index.php';
                });
            }
            
            // Function to generate loot
            function generateLoot() {
                // Show loading state
                generateButton.disabled = true;
                generateButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Looting...';
                
                // Hide previous error and loot result
                hideError();
                lootResult.style.display = 'none';
                
                // Make API request to generate_loot.php
                fetch('../api/generate_loot.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            // Clear previous loot
                            lootContainer.innerHTML = '';
                            
                            // Add primary loot
                            data.loot.forEach(item => {
                                const lootCard = document.createElement('div');
                                lootCard.className = 'loot-card';
                                
                                // Construct the inner HTML for the card
                                let cardContent = `
                                    <div class="loot-roll">Roll: ${item.roll}</div>
                                    <div class="loot-name">${item.name}</div>
                                `;
                                
                                // Add badges if applicable
                                if (item.is_ancient_relic) {
                                    cardContent += `<span class="ancient-relic-badge">Ancient Relic</span>`;
                                }
                                
                                if (item.is_thing_of_importance) {
                                    cardContent += `<span class="thing-of-importance-badge">Thing of Importance</span>`;
                                }
                                
                                // Add description and category
                                cardContent += `
                                    <div class="loot-description">${item.description}</div>
                                    <div class="loot-category">Category: ${item.category}</div>
                                `;
                                
                                lootCard.innerHTML = cardContent;
                                lootContainer.appendChild(lootCard);
                            });
                            
                            // Add extra rolls if any
                            if (data.extra_rolls && data.extra_rolls.length > 0) {
                                const divider = document.createElement('div');
                                divider.className = 'extra-roll-divider';
                                divider.innerHTML = '<span>Additional Rolls</span>';
                                lootContainer.appendChild(divider);
                                
                                data.extra_rolls.forEach(item => {
                                    const lootCard = document.createElement('div');
                                    lootCard.className = 'loot-card';
                                    
                                    // Construct the inner HTML for the card
                                    let cardContent = `
                                        <div class="loot-roll">Roll: ${item.roll}</div>
                                        <div class="loot-name">${item.name}</div>
                                    `;
                                    
                                    // Add badges if applicable
                                    if (item.is_ancient_relic) {
                                        cardContent += `<span class="ancient-relic-badge">Ancient Relic</span>`;
                                    }
                                    
                                    if (item.is_thing_of_importance) {
                                        cardContent += `<span class="thing-of-importance-badge">Thing of Importance</span>`;
                                    }
                                    
                                    // Add description and category
                                    cardContent += `
                                        <div class="loot-description">${item.description}</div>
                                        <div class="loot-category">Category: ${item.category}</div>
                                    `;
                                    
                                    lootCard.innerHTML = cardContent;
                                    lootContainer.appendChild(lootCard);
                                });
                            }
                            
                            // Show the loot result
                            lootResult.style.display = 'block';
                            
                            // Scroll to result
                            lootResult.scrollIntoView({ behavior: 'smooth' });
                        } else {
                            // Handle error
                            showError('Error generating loot: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Show error message
                        showError(`Error generating loot: ${error.message}. Check console for details.`);
                        
                        // If trying to get more debug info
                        fetch('../api/generate_loot.php', { 
                            method: 'GET',
                            headers: { 'Accept': 'text/plain' }
                        })
                        .then(response => response.text())
                        .then(text => {
                            console.log('Raw API response:', text);
                            // Update error details with raw response
                            errorDetails.textContent = text;
                        })
                        .catch(err => console.error('Failed to get raw response:', err));
                    })
                    .finally(() => {
                        // Reset button state
                        generateButton.disabled = false;
                        generateButton.innerHTML = '<i class="fas fa-coins"></i> Loot the Body';
                    });
            }
        });
    </script>
</body>
</html>
