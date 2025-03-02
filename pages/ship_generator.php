<?php
// Set the current page
$current_page = 'ship_generator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ship Generator - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Additional styling fixes */
        #cargo-list {
            list-style-type: none;
            padding-left: 0;
            margin-left: 0;
        }

        #cargo-list li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 25px;
            display: block;
        }

        #cargo-list li:before {
            content: '•';
            color: var(--secondary);
            position: absolute;
            left: 5px;
            top: 0;
            font-size: 1.2em;
        }
        
        .ship-details h3 {
            margin-top: 40px; /* Increased from 30px */
            margin-bottom: 20px; /* Increased from 15px */
            color: var(--secondary);
            border-bottom: 1px solid rgba(191, 157, 97, 0.3);
            padding-bottom: 5px;
        }

        .source-reference {
            margin: 20px 0;
            font-style: italic;
        }
        
        .source-reference i {
            color: var(--secondary);
            margin-right: 10px;
        }
        
        .print-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .print-buttons {
                flex-direction: column;
            }
            
            .print-buttons button {
                margin: 5px 0 !important;
                width: 100%;
            }
        }
        
        @media print {
            .sidebar, .dashboard-header, .generator-card, footer, 
            #generate-ship-btn, .source-reference, .print-buttons {
                display: none !important;
            }
            
            body, .app-container, .main-content, .ship-generator-content {
                background: white;
                color: black;
                margin: 0;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            
            #ship-result {
                display: block !important;
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 1cm;
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
                    <i class="fas fa-ship"></i>
                    <h1>Ship Generator</h1>
                </div>
            </div>
            
            <div class="ship-generator-content">
                <div class="generator-card dashboard-card">
                    <h2>Generate a Random Ship</h2>
                    <p>Click the button below to create a random pirate ship for your game.</p>
                    <p class="source-reference">
                        <i class="fas fa-book"></i> This generator is based on the "Pirate Borg Core Book" page 114-115 "Sails! Ships at sea and their cargo".
                    </p>
                    <div class="print-buttons">
                        <button id="generate-ship-btn" class="btn btn-primary">
                            <i class="fas fa-ship"></i> Generate Ship
                        </button>
                        <button id="print-ship-btn" class="btn btn-outline">
                            <i class="fas fa-print"></i> Print Ship
                        </button>
                        <button id="home-btn" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Back to Home
                        </button>
                    </div>
                </div>
                
                <div id="ship-result" class="dashboard-card" style="display: none; margin-top: 20px;">
                    <h2 id="ship-name">Ship Name</h2>
                    <div class="ship-details">
                        <p><strong>Vessel Class:</strong> <span id="vessel-class">Unknown</span></p>
                        <p><strong>Armament:</strong> <span id="armament">Unknown</span></p>
                        <p><strong>Crew:</strong> The crew are <span id="crew-quantity">unknown</span> and are <span id="crew-quality">unknown</span>.</p>
                        
                        <h3>Cargo:</h3>
                        <ul id="cargo-list">
                            <li>None</li>
                        </ul>
                        
                        <h3>Plot Twist (Optional):</h3>
                        <p id="plot-twist">Unknown</p>
                    </div>
                </div>
                
                <div id="error-container" class="dashboard-card" style="display: none; margin-top: 20px; background-color: rgba(220, 53, 69, 0.1); border-color: rgba(220, 53, 69, 0.3);">
                    <h3 style="color: #dc3545;">Error</h3>
                    <p id="error-message">An error occurred while generating the ship.</p>
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
            console.log("Ship Generator DOM loaded");
            
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
                    
                    // Disable navigation buttons except ship generator
                    const navButtons = document.querySelectorAll('.sidebar-btn:not(#login-btn):not(#ship-generator-btn)');
                    navButtons.forEach(button => {
                        if (!button.classList.contains('disabled')) {
                            button.classList.add('disabled');
                        }
                    });
                }
            });
            
            // Ship Generator code
            const generateButton = document.getElementById('generate-ship-btn');
            const printButton = document.getElementById('print-ship-btn');
            const homeButton = document.getElementById('home-btn');
            const shipResult = document.getElementById('ship-result');
            const shipName = document.getElementById('ship-name');
            const vesselClass = document.getElementById('vessel-class');
            const armament = document.getElementById('armament');
            const crewQuantity = document.getElementById('crew-quantity');
            const crewQuality = document.getElementById('crew-quality');
            const cargoList = document.getElementById('cargo-list');
            const plotTwist = document.getElementById('plot-twist');
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
                generateButton.addEventListener('click', generateShip);
            }
            
            // Add event listener to the print button
            if (printButton) {
                printButton.addEventListener('click', function() {
                    // Check if ship has been generated
                    if (shipResult.style.display === 'none') {
                        alert('Please generate a ship first before printing.');
                        return;
                    }
                    
                    // Create a new window for printing just the ship result
                    const printWindow = window.open('', '_blank');
                    
                    // Create the content for the print window
                    const shipResultClone = shipResult.cloneNode(true);
                    
                    // Set up the HTML content for printing
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>${shipName.textContent} - Ship Details</title>
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
                                h3 {
                                    color: #805d2c;
                                    margin-top: 40px;
                                    margin-bottom: 20px;
                                    border-bottom: 1px solid rgba(128, 93, 44, 0.3);
                                    padding-bottom: 5px;
                                }
                                ul {
                                    list-style-type: none;
                                    padding-left: 0;
                                }
                                li {
                                    margin-bottom: 10px;
                                    position: relative;
                                    padding-left: 20px;
                                }
                                li:before {
                                    content: '•';
                                    color: #805d2c;
                                    position: absolute;
                                    left: 0;
                                    top: 0;
                                }
                                .ship-details {
                                    margin-top: 20px;
                                }
                                .ship-details p {
                                    margin-bottom: 15px;
                                }
                                strong {
                                    color: #555;
                                }
                            </style>
                        </head>
                        <body>
                            ${shipResultClone.outerHTML}
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
            
            // Function to generate a ship
            function generateShip() {
                // Show loading state
                generateButton.disabled = true;
                generateButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
                
                // Hide previous error and ship result
                hideError();
                shipResult.style.display = 'none';
                
                // Make API request to generate_ship.php
                fetch('../api/generate_ship.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            const ship = data.ship;
                            
                            // Update UI with ship details
                            shipName.textContent = ship.ship_name;
                            vesselClass.textContent = ship.vessel_class;
                            armament.textContent = ship.armament;
                            crewQuantity.textContent = ship.crew_quantity;
                            crewQuality.textContent = ship.crew_quality;
                            
                            // Update cargo list
                            cargoList.innerHTML = '';
                            if (ship.cargo && ship.cargo.length > 0) {
                                ship.cargo.forEach(item => {
                                    const li = document.createElement('li');
                                    li.textContent = item;
                                    cargoList.appendChild(li);
                                });
                            } else {
                                const li = document.createElement('li');
                                li.textContent = 'None';
                                cargoList.appendChild(li);
                            }
                            
                            // Update plot twist
                            plotTwist.textContent = ship.plot_twist;
                            
                            // Show the ship result
                            shipResult.style.display = 'block';
                            
                            // Scroll to result
                            shipResult.scrollIntoView({ behavior: 'smooth' });
                        } else {
                            // Handle error
                            showError('Error generating ship: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Show error message
                        showError(`Error generating ship: ${error.message}. Check console for details.`);
                        
                        // If trying to get more debug info
                        fetch('../api/generate_ship.php', { 
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
                        generateButton.innerHTML = '<i class="fas fa-ship"></i> Generate Ship';
                    });
            }
        });
    </script>
</body>
</html>
