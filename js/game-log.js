// game-log.js
document.addEventListener('DOMContentLoaded', function() {
    console.log("Game Log module loaded");
    
    // Make sure Firebase is initialized before using it
    if (typeof firebase === 'undefined') {
        console.error("Firebase is not defined. Make sure the Firebase script is loaded before game-log.js");
        return;
    }
    
    // Game session elements
    const noSessionSection = document.getElementById('no-session');
    const activeSessionSection = document.getElementById('active-session');
    const sessionNameElement = document.getElementById('session-name');
    const joinCodeElement = document.getElementById('join-code');
    const createSessionBtn = document.getElementById('create-session-btn');
    const joinSessionBtn = document.getElementById('join-session-btn');
    const leaveSessionBtn = document.getElementById('leave-session-btn');
    
    // Log controls
    const logDisplay = document.getElementById('log-display');
    const logControls = document.getElementById('log-controls');
    const customLogInput = document.getElementById('custom-log-input');
    const addLogBtn = document.getElementById('add-log-btn');
    
    // Current session info
    let currentUserId = null;
    let currentUserEmail = null;
    let currentGameId = null;
    let lastLogTimestamp = 0; // For polling
    let pollingInterval = null;
    
    // Set userId and userEmail when user is authenticated
    firebase.auth().onAuthStateChanged((user) => {
        if (user) {
            currentUserId = user.uid;
            currentUserEmail = user.email;
            
            // Check if user already has an active session
            checkActiveSession();
        }
    });
    
    // Create session button click handler
    if (createSessionBtn) {
        createSessionBtn.addEventListener('click', function() {
            // Prompt for session name
            const sessionName = prompt("Enter a name for your new game session:", "The Salty Voyage");
            if (!sessionName) return; // User cancelled
            
            createGameSession(sessionName);
        });
    }
    
    // Join session button click handler
    if (joinSessionBtn) {
        joinSessionBtn.addEventListener('click', function() {
            // Prompt for join code
            const joinCode = prompt("Enter the game session join code:", "");
            if (!joinCode) return; // User cancelled
            
            joinGameSession(joinCode);
        });
    }
    
    // Leave session button click handler
    if (leaveSessionBtn) {
        leaveSessionBtn.addEventListener('click', function() {
            // Confirm before leaving
            if (!confirm("Are you sure you want to leave this game session?")) return;
            
            leaveGameSession();
        });
    }
    
    // Add log entry button click handler
    if (addLogBtn) {
        addLogBtn.addEventListener('click', addCustomLogEntry);
    }
    
    // Allow pressing Enter to add log entry
    if (customLogInput) {
        customLogInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addCustomLogEntry();
            }
        });
    }
    
    // Function to check if user has an active session
    function checkActiveSession() {
        // Check local storage first for last joined session
        const storedGameId = localStorage.getItem('saltySessions_gameId');
        
        if (storedGameId) {
            // Verify if session is still active
            fetch(`../api/get_game_session.php?game_id=${storedGameId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.session) {
                        // Resume existing session
                        joinExistingSession(data.session.id, data.session.name, data.session.join_code);
                    } else {
                        // Clear invalid stored session
                        localStorage.removeItem('saltySessions_gameId');
                        showNoSessionView();
                    }
                })
                .catch(error => {
                    console.error("Error checking active session:", error);
                    showNoSessionView();
                });
        } else {
            showNoSessionView();
        }
    }
    
    // Create a new game session
    function createGameSession(sessionName) {
        // Show loading state
        createSessionBtn.disabled = true;
        createSessionBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        
        // Create form data
        const formData = new FormData();
        formData.append('user_id', currentUserId);
        formData.append('user_email', currentUserEmail);
        formData.append('session_name', sessionName);
        
        // Make API request
        fetch('../api/create_game_session.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            createSessionBtn.disabled = false;
            createSessionBtn.innerHTML = '<i class="fas fa-plus"></i> Create Game Session';
            
            if (data.status === 'success') {
                joinExistingSession(data.game_id, sessionName, data.join_code);
            } else {
                alert("Error creating game session: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error creating game session:", error);
            createSessionBtn.disabled = false;
            createSessionBtn.innerHTML = '<i class="fas fa-plus"></i> Create Game Session';
            alert("Failed to create game session. Please try again.");
        });
    }
    
    // Join a game session with join code
    function joinGameSession(joinCode) {
        // Show loading state
        joinSessionBtn.disabled = true;
        joinSessionBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Joining...';
        
        // Create form data
        const formData = new FormData();
        formData.append('user_id', currentUserId);
        formData.append('user_email', currentUserEmail);
        formData.append('join_code', joinCode);
        
        // Make API request
        fetch('../api/join_game_session.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            joinSessionBtn.disabled = false;
            joinSessionBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Join Game Session';
            
            if (data.status === 'success') {
                joinExistingSession(data.game_id, data.session_name, joinCode);
            } else {
                alert("Error joining game session: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error joining game session:", error);
            joinSessionBtn.disabled = false;
            joinSessionBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Join Game Session';
            alert("Failed to join game session. Please try again.");
        });
    }
    
    // Setup session after joining
    function joinExistingSession(gameId, sessionName, joinCode) {
        currentGameId = gameId;
        lastLogTimestamp = 0;
        
        // Store in local storage
        localStorage.setItem('saltySessions_gameId', gameId);
        
        // Update UI
        sessionNameElement.textContent = sessionName;
        if (joinCodeElement && joinCode) {
            joinCodeElement.textContent = joinCode;
        }
        
        // Show active session view
        showActiveSessionView();
        
        // Clear and initialize log display
        logDisplay.innerHTML = `
            <div style="text-align: center; padding: 30px 0;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--secondary); margin-bottom: 15px;"></i>
                <p>Loading game log...</p>
            </div>
        `;
        
        // Start polling for log updates
        startLogPolling();
        
        // Connect generators to the game log
        connectGeneratorsToGameLog();
    }
    
    // Leave current game session
    function leaveGameSession() {
        // Stop polling
        stopLogPolling();
        
        // Clear local storage
        localStorage.removeItem('saltySessions_gameId');
        
        // Clear session data
        currentGameId = null;
        
        // Reset log display
        logDisplay.innerHTML = `
            <p style="text-align: center; padding: 30px 0;">
                <i class="fas fa-scroll" style="font-size: 2rem; color: var(--secondary); opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                Game logging coming soon!
            </p>
        `;
        
        // Show no session view
        showNoSessionView();
        
        // TODO: Add an API call to formally leave the session in the database
    }
    
    // Start polling for log updates
    function startLogPolling() {
        // First, get initial log entries
        fetchGameLog();
        
        // Then set up polling interval (every 5 seconds)
        pollingInterval = setInterval(fetchGameLog, 5000);
    }
    
    // Stop polling
    function stopLogPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }
    
// Fetch game log entries
function fetchGameLog() {
    if (!currentGameId) return;
    
    fetch(`../api/get_game_log.php?game_id=${currentGameId}&after=${lastLogTimestamp}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.entries && data.entries.length > 0) {
                    // Update the log display with new entries
                    updateLogDisplay(data.entries);
                    
                    // Update last timestamp
                    const lastEntry = data.entries[data.entries.length - 1];
                    lastLogTimestamp = lastEntry.timestamp;
                }
            } else {
                console.error("Error fetching game log:", data.message);
            }
        })
        .catch(error => {
            console.error("Error fetching game log:", error);
        });
}
    
    // Update the log display with new entries
    function updateLogDisplay(entries) {
        let isFirstUpdate = false;
        
        // If this is the first update, clear placeholder
        if (logDisplay.querySelector('.fas-scroll') || logDisplay.querySelector('.fa-spinner')) {
            logDisplay.innerHTML = '';
            isFirstUpdate = true;
        }
        
        // Add each new entry
        entries.forEach(entry => {
            const entryHtml = createLogEntryHtml(entry);
            logDisplay.innerHTML += entryHtml;
        });
        
        // Scroll to bottom
        logDisplay.scrollTop = logDisplay.scrollHeight;
    }
    
    // Create HTML for a log entry
    function createLogEntryHtml(entry) {
        let entryHtml = '';
        const timestamp = formatTimestamp(entry.timestamp);
        const userEmail = entry.user_email || 'Anonymous Pirate';
        const content = entry.content;
        
        switch(entry.entry_type) {
            case 'system':
                entryHtml = `
                    <div class="log-entry system-entry">
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">${content.message}</div>
                    </div>
                `;
                break;
                
            case 'ship_generation':
                entryHtml = `
                    <div class="log-entry">
                        <div class="user">${userEmail}</div>
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">Generated a new ship: <strong>${content.ship_name}</strong></div>
                    </div>
                `;
                break;
                
            case 'loot_generation':
                entryHtml = `
                    <div class="log-entry">
                        <div class="user">${userEmail}</div>
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">Found treasure: <strong>${content.name}</strong></div>
                    </div>
                `;
                break;
                
            case 'custom':
                entryHtml = `
                    <div class="log-entry">
                        <div class="user">${userEmail}</div>
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">${content.message}</div>
                    </div>
                `;
                break;
                
            default:
                entryHtml = `
                    <div class="log-entry">
                        <div class="user">${userEmail}</div>
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">${JSON.stringify(content)}</div>
                    </div>
                `;
        }
        
        return entryHtml;
    }
    
    // Add a custom log entry
    function addCustomLogEntry() {
        if (!currentGameId || !customLogInput.value.trim()) return;
        
        const message = customLogInput.value.trim();
        
        // Create form data
        const formData = new FormData();
        formData.append('game_id', currentGameId);
        formData.append('user_id', currentUserId);
        formData.append('user_email', currentUserEmail);
        formData.append('entry_type', 'custom');
        formData.append('content', JSON.stringify({
            message: message,
            timestamp: Math.floor(Date.now() / 1000)
        }));
        
        // Clear input
        customLogInput.value = '';
        
        // Make API request
        fetch('../api/add_log_entry.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // The entry will be picked up by the polling
            } else {
                console.error("Error adding log entry:", data.message);
                alert("Failed to add log entry: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error adding log entry:", error);
        });
    }
    
    // Connect game generators to the log
    function connectGeneratorsToGameLog() {
        if (window.Generators) {
            // Store original functions
            const originalGenerateShip = window.Generators.generateShip;
            const originalGenerateLoot = window.Generators.generateLoot;
            
            // Override ship generator to add log entries
            window.Generators.generateShip = function() {
                // Call original function
                originalGenerateShip.apply(this, arguments);
                
                // Wait for ship data
                const checkForShipInterval = setInterval(() => {
                    const shipNameElement = document.getElementById('ship-name');
                    if (shipNameElement) {
                        clearInterval(checkForShipInterval);
                        
                        // Ship was generated, add log entry
                        if (currentGameId) {
                            const shipName = shipNameElement.textContent;
                            
                            // Create form data
                            const formData = new FormData();
                            formData.append('game_id', currentGameId);
                            formData.append('user_id', currentUserId);
                            formData.append('user_email', currentUserEmail);
                            formData.append('entry_type', 'ship_generation');
                            formData.append('content', JSON.stringify({
                                ship_name: shipName,
                                timestamp: Math.floor(Date.now() / 1000)
                            }));
                            
                            // Make API request
                            fetch('../api/add_log_entry.php', {
                                method: 'POST',
                                body: formData
                            });
                        }
                    }
                }, 500);
            };
            
            // Override loot generator to add log entries
            window.Generators.generateLoot = function() {
                // Call original function
                originalGenerateLoot.apply(this, arguments);
                
                // Wait for loot data
                const checkForLootInterval = setInterval(() => {
                    const lootCards = document.querySelectorAll('.loot-name');
                    if (lootCards.length > 0) {
                        clearInterval(checkForLootInterval);
                        
                        // Loot was generated, add log entry
                        if (currentGameId) {
                            const lootName = lootCards[0].textContent;
                            
                            // Create form data
                            const formData = new FormData();
                            formData.append('game_id', currentGameId);
                            formData.append('user_id', currentUserId);
                            formData.append('user_email', currentUserEmail);
                            formData.append('entry_type', 'loot_generation');
                            formData.append('content', JSON.stringify({
                                name: lootName,
                                timestamp: Math.floor(Date.now() / 1000)
                            }));
                            
                            // Make API request
                            fetch('../api/add_log_entry.php', {
                                method: 'POST',
                                body: formData
                            });
                        }
                    }
                }, 500);
            };
        }
    }
    
    // Format timestamp
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Show no session view
    function showNoSessionView() {
        if (noSessionSection) noSessionSection.style.display = 'block';
        if (activeSessionSection) activeSessionSection.style.display = 'none';
        if (logControls) logControls.style.display = 'none';
    }
    
    // Show active session view
    function showActiveSessionView() {
        if (noSessionSection) noSessionSection.style.display = 'none';
        if (activeSessionSection) activeSessionSection.style.display = 'block';
        if (logControls) logControls.style.display = 'block';
    }
});
