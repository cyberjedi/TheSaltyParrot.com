// game-session.js - Game session management
document.addEventListener('DOMContentLoaded', function() {
    // Session variables
    let currentGameId = null;
    let lastLogTimestamp = 0; // For polling
    let pollingInterval = null;
    let crewPollingInterval = null;
    
    // Track displayed entries to prevent duplicates
    let displayedEntryIds = new Set();
    
    // Session Management Buttons
    const createSessionBtn = document.getElementById('create-session-btn');
    const joinSessionBtn = document.getElementById('join-session-btn');
    const leaveSessionBtn = document.getElementById('leave-session-btn');
    const customLogInput = document.getElementById('custom-log-input');
    const addLogBtn = document.getElementById('add-log-btn');
    
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
    
    // Make checkActiveSession function globally available
    window.checkActiveSession = checkActiveSession;
    
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
                        
                        // Fetch current crew members
                        fetchCrewMembers(data.session.id);
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
                
                // Create initial crew list with just the GM
                updateCrewList([{
                    user_id: currentUserId,
                    user_email: currentUserEmail,
                    role: 'gm',
                    isCurrentUser: true
                }]);
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
                
                // Fetch current crew members after joining
                fetchCrewMembers(data.game_id);
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
        displayedEntryIds = new Set(); // Reset the entry tracking set
        
        // Store in local storage
        localStorage.setItem('saltySessions_gameId', gameId);
        
        // Update UI
        const sessionNameElem = document.getElementById('session-name');
        const joinCodeElem = document.getElementById('join-code');
        
        if (sessionNameElem) sessionNameElem.textContent = sessionName;
        if (joinCodeElem) joinCodeElem.textContent = joinCode;
        
        // Show active session view
        showActiveSessionView();
        
        // Clear and initialize log display
        const logDisplay = document.getElementById('log-display');
        if (logDisplay) {
            logDisplay.innerHTML = `
                <div style="text-align: center; padding: 30px 0;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--secondary); margin-bottom: 15px;"></i>
                    <p>Loading game log...</p>
                </div>
            `;
        }
        
        // Start polling for log updates
        startPolling();
        
        // Connect generators to the game log
        connectGeneratorsToGameLog();
    }
    
    // Fetch crew members for current session
    function fetchCrewMembers(gameId) {
        fetch(`../api/get_session_members.php?session_id=${gameId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.members) {
                    // Mark current user
                    data.members.forEach(member => {
                        member.isCurrentUser = (member.user_id === currentUserId);
                    });
                    
                    // Update crew list
                    updateCrewList(data.members);
                }
            })
            .catch(error => {
                console.error("Error fetching crew members:", error);
            });
    }
    
    // Update crew list in UI
    function updateCrewList(members) {
        const crewList = document.getElementById('crew-members-list');
        if (!crewList) return;
        
        // Clear existing list
        crewList.innerHTML = '';
        
        // Add each member
        members.forEach(member => {
            const memberElement = document.createElement('div');
            memberElement.className = `crew-member ${member.role === 'gm' ? 'gm' : ''}`;
            
            let icon = member.role === 'gm' ? 'fa-crown' : 'fa-user';
            let displayName = shortenEmail(member.user_email);
            
            if (member.isCurrentUser) {
                displayName += ' (You)';
            }
            
            memberElement.innerHTML = `<i class="fas ${icon}"></i> ${displayName}`;
            crewList.appendChild(memberElement);
        });
    }
    
    // Shorten email for display
    function shortenEmail(email) {
        // Just use the part before @ symbol
        const parts = email.split('@');
        if (parts.length > 1) {
            return parts[0];
        }
        return email;
    }
    
    // Leave current game session
    function leaveGameSession() {
        // Stop polling
        stopPolling();
        
        // Clear local storage
        localStorage.removeItem('saltySessions_gameId');
        
        // Clear session data
        currentGameId = null;
        displayedEntryIds = new Set(); // Clear entry tracking
        
        // Reset log display
        const logDisplay = document.getElementById('log-display');
        if (logDisplay) {
            logDisplay.innerHTML = `
                <p style="text-align: center; padding: 30px 0;">
                    <i class="fas fa-scroll" style="font-size: 2rem; color: var(--secondary); opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                    Game events will appear here once you join a crew
                </p>
            `;
        }
        
        // Show no session view
        showNoSessionView();
        
        // TODO: Add an API call to formally leave the session in the database
    }
    
    // Start polling for log updates
    function startPolling() {
        // First, get initial log entries
        fetchGameLog();
        
        // Then set up polling interval (every 5 seconds)
        pollingInterval = setInterval(fetchGameLog, 5000);
        
        // Also poll for crew members
        crewPollingInterval = setInterval(() => {
            if (currentGameId) {
                fetchCrewMembers(currentGameId);
            }
        }, 10000);
    }
    
    // Stop polling
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
        if (crewPollingInterval) {
            clearInterval(crewPollingInterval);
            crewPollingInterval = null;
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
    
    // Update log display
    function updateLogDisplay(entries) {
        const logDisplay = document.getElementById('log-display');
        if (!logDisplay) return;
        
        // If this is the first update, clear the placeholder
        if (logDisplay.innerHTML.includes('fa-spinner') || logDisplay.innerHTML.includes('fa-scroll')) {
            logDisplay.innerHTML = '';
        }
        
        // Add each new entry
        entries.forEach(entry => {
            // Generate a consistent ID for entries
            const entryId = entry.id || 
                          `${entry.entry_type}_${entry.user_id}_${entry.timestamp}`;
            
            // Skip if this entry ID has already been displayed
            if (displayedEntryIds.has(entryId)) {
                return;
            }
            
            // Add the entry ID to our tracking set
            displayedEntryIds.add(entryId);
            
            // Handle cases where content might be a string instead of an object
            if (typeof entry.content === 'string') {
                try {
                    entry.content = JSON.parse(entry.content);
                } catch (e) {
                    console.log("Content is not JSON, using as is");
                }
            }
            
            // Create and append the entry HTML
            try {
                const entryHtml = createLogEntryHtml(entry);
                logDisplay.innerHTML += entryHtml;
            } catch (error) {
                console.error("Error creating log entry HTML:", error, entry);
            }
        });
        
        // Scroll to bottom
        logDisplay.scrollTop = logDisplay.scrollHeight;
    }
    
    // Create HTML for a log entry
    function createLogEntryHtml(entry) {
        let entryHtml = '';
        const timestamp = formatTimestamp(entry.timestamp);
        const userEmail = entry.user_email || 'Anonymous Pirate';
        
        // Handle different content formats
        let content;
        try {
            if (typeof entry.content === 'string') {
                content = JSON.parse(entry.content);
            } else {
                content = entry.content;
            }
        } catch (e) {
            console.error("Error parsing content:", e);
            content = { message: "Error displaying content" };
        }
        
        switch(entry.entry_type) {
            case 'system':
                entryHtml = `
                    <div class="log-entry system-entry">
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">${content.message || "System message"}</div>
                    </div>
                `;
                break;
                
            case 'ship_generation':
                entryHtml = `
                    <div class="log-entry">
                        <div class="user">${shortenEmail(userEmail)}</div>
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">Generated a new ship: <strong>${content.ship_name || "Unknown ship"}</strong></div>
                    </div>
                `;
                break;
                
            case 'loot_generation':
                entryHtml = `
                    <div class="log-entry">
                        <div class="user">${shortenEmail(userEmail)}</div>
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">Found treasure: <strong>${content.name || "Unknown treasure"}</strong></div>
                    </div>
                `;
                break;
                
            case 'custom':
                entryHtml = `
                    <div class="log-entry">
                        <div class="user">${shortenEmail(userEmail)}</div>
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">${content.message || "Custom message"}</div>
                    </div>
                `;
                break;
                
            default:
                const contentText = typeof content === 'object' ? JSON.stringify(content) : content;
                entryHtml = `
                    <div class="log-entry">
                        <div class="user">${shortenEmail(userEmail)}</div>
                        <div class="timestamp">${timestamp}</div>
                        <div class="content">${contentText}</div>
                    </div>
                `;
        }
        
        return entryHtml;
    }
    
    // Format timestamp
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Show no session view
    function showNoSessionView() {
        const noSessionSection = document.getElementById('no-session');
        const activeSessionSection = document.getElementById('active-session');
        const logControls = document.getElementById('log-controls');
        
        if (noSessionSection) noSessionSection.style.display = 'block';
        if (activeSessionSection) activeSessionSection.style.display = 'none';
        if (logControls) logControls.style.display = 'none';
    }
    
    // Show active session view
    function showActiveSessionView() {
        const noSessionSection = document.getElementById('no-session');
        const activeSessionSection = document.getElementById('active-session');
        const logControls = document.getElementById('log-controls');
        
        if (noSessionSection) noSessionSection.style.display = 'none';
        if (activeSessionSection) activeSessionSection.style.display = 'flex';
        if (logControls) logControls.style.display = 'block';
    }
    
    // Add a custom log entry
    function addCustomLogEntry() {
        if (!currentGameId || !customLogInput || !customLogInput.value.trim()) return;
        
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
                // Add the new entry ID to our tracking set to prevent duplication
                if (data.entry && data.entry.id) {
                    displayedEntryIds.add(data.entry.id);
                    
                    // Immediately add the entry to the log
                    const entry = data.entry;
                    
                    // Handle content format conversion
                    if (typeof entry.content === 'string') {
                        try {
                            entry.content = JSON.parse(entry.content);
                        } catch (e) {
                            console.error("Error parsing content:", e);
                        }
                    }
                    
                    const logDisplay = document.getElementById('log-display');
                    if (logDisplay) {
                        const entryHtml = createLogEntryHtml(entry);
                        logDisplay.innerHTML += entryHtml;
                        logDisplay.scrollTop = logDisplay.scrollHeight;
                    }
                }
            } else {
                console.error("Error adding log entry:", data.message);
                alert("Failed to add log entry: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error adding log entry:", error);
        });
    }
    
    // UPDATED: Connect game generators to the game log with the new modal system
    function connectGeneratorsToGameLog() {
        // With the new modal system, we don't need to override the generator functions
        // The "Send to Log" button in the modal handles sending the content to the game log
        
        // Instead, we'll just make sure the send-to-log button is properly connected
        const sendToLogBtn = document.getElementById('send-to-log-btn');
        if (sendToLogBtn) {
            // The button event is handled in generator-modal.js
            console.log("Generator to game log connection ready");
        }
        
        // If we need to do any special connection for new generators, we'd add it here
        
        // We'll leave this function in place for backward compatibility and future enhancements
    }

    // Clear log button
    const clearLogBtn = document.getElementById('clear-log-btn');
    
    if (clearLogBtn) {
        clearLogBtn.addEventListener('click', function() {
            const logDisplay = document.getElementById('log-display');
            if (logDisplay) {
                // Confirm before clearing
                if (confirm("Are you sure you want to clear the game log? This will affect everyone in the crew.")) {
                    logDisplay.innerHTML = `
                        <p style="text-align: center; padding: 30px 0;">
                            <i class="fas fa-scroll" style="font-size: 2rem; color: var(--secondary); opacity: 0.4; display: block; margin-bottom: 15px;"></i>
                            Game log cleared
                        </p>
                    `;
                    // Reset entry tracking
                    displayedEntryIds = new Set();
                }
            }
        });
    }
    
    // Save log button
    const saveLogBtn = document.getElementById('save-log-btn');
    
    if (saveLogBtn) {
        saveLogBtn.addEventListener('click', function() {
            const logDisplay = document.getElementById('log-display');
            if (logDisplay) {
                // Create a download link
                const logContent = logDisplay.innerHTML;
                const blob = new Blob([logContent], {type: 'text/html'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                
                // Generate a filename with date
                const now = new Date();
                const filename = `salty-parrot-log-${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}.html`;
                
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                
                // Clean up
                setTimeout(() => {
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                }, 0);
            }
        });
    }

    // NEW: Initialize the ship box features
    const selectShipBtn = document.getElementById('select-ship-btn');
    
    if (selectShipBtn) {
        selectShipBtn.addEventListener('click', function() {
            // For now, just run the ship generator
            if (window.Generators && window.Generators.generateShip) {
                window.Generators.generateShip();
            }
        });
    }
    
    // Hook into the edit-ship-btn if present 
    const editShipBtn = document.getElementById('edit-ship-btn');
    
    if (editShipBtn) {
        editShipBtn.addEventListener('click', function() {
            alert("Ship editor coming soon!");
        });
    }
});
