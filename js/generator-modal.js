// generator-modal.js
document.addEventListener('DOMContentLoaded', function() {
    console.log("Generator modal script loaded");
    
    // Modal elements
    const modal = document.getElementById('generator-modal');
    const closeModalBtn = document.querySelector('.close-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalContent = document.getElementById('modal-content');
    const regenerateBtn = document.getElementById('regenerate-btn');
    const copyBtn = document.getElementById('copy-to-clipboard-btn');
    const printBtn = document.getElementById('print-result-btn');
    const sendToLogBtn = document.getElementById('send-to-log-btn');
    
    // Check if modal elements exist
    if (!modal) {
        console.error("Modal element not found!");
        return;
    }
    
    // Current generator function
    let currentGenerator = null;
    
    // Close modal when clicking the X
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            closeModal();
        });
    }
    
    // Close modal when clicking outside the content
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Regenerate button
    if (regenerateBtn) {
        regenerateBtn.addEventListener('click', function() {
            if (currentGenerator) {
                // Call the current generator function again
                currentGenerator();
            }
        });
    }
    
    // Copy to clipboard button
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            // Create a temporary textarea for copying HTML content
            const tempArea = document.createElement('textarea');
            tempArea.value = modalContent.innerHTML;
            document.body.appendChild(tempArea);
            tempArea.select();
            document.execCommand('copy');
            document.body.removeChild(tempArea);
            
            // Show feedback
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
            }, 2000);
        });
    }
    
    // Print button
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            // Create print window
            const printWindow = window.open('', '_blank');
            
            // Get content from modal
            const content = modalContent.innerHTML;
            
            // Create print-friendly HTML
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>The Salty Parrot - ${modalTitle.textContent}</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            color: #333;
                            padding: 20px;
                        }
                        
                        h2, h3 {
                            color: #805d2c;
                        }
                        
                        .loot-card, .ship-details {
                            border: 1px solid #ddd;
                            padding: 15px;
                            margin: 15px 0;
                            border-radius: 8px;
                        }
                        
                        .loot-roll, .loot-name {
                            color: #805d2c;
                        }
                        
                        .loot-category {
                            font-style: italic;
                            color: #666;
                        }
                        
                        .ancient-relic-badge {
                            display: inline-block;
                            padding: 3px 8px;
                            background-color: #f0e6ff;
                            color: #4b0082;
                            border: 1px solid #9d4edd;
                            border-radius: 12px;
                            font-size: 0.8rem;
                            margin-right: 5px;
                        }
                        
                        .thing-of-importance-badge {
                            display: inline-block;
                            padding: 3px 8px;
                            background-color: #e6ffe6;
                            color: #006400;
                            border: 1px solid #2ea44f;
                            border-radius: 12px;
                            font-size: 0.8rem;
                            margin-right: 5px;
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
                    </style>
                </head>
                <body>
                    <h2>${modalTitle.textContent}</h2>
                    ${content}
                    <div style="margin-top: 30px; font-size: 0.8rem; text-align: center; color: #666;">
                        <p>Generated by The Salty Parrot - A Pirate Borg Toolbox</p>
                        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC.<br>
                        It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
                    </div>
                </body>
                </html>
            `);
            
            // Close document for printing
            printWindow.document.close();
            
            // Wait for content to load and then print
            printWindow.addEventListener('load', function() {
                printWindow.print();
            });
        });
    }
    
    // Send to log button
    if (sendToLogBtn) {
        sendToLogBtn.addEventListener('click', function() {
            const gameId = localStorage.getItem('saltySessions_gameId');
            
            if (!gameId) {
                alert("You need to join a game session to send content to the log!");
                return;
            }
            
            // Get user credentials from global variables
            if (typeof currentUserId === 'undefined' || typeof currentUserEmail === 'undefined') {
                console.error("User credentials not available");
                alert("You need to be logged in to send content to the log!");
                return;
            }
            
            // Determine what type of content we're sending
            let entryType = 'custom';
            let contentData = {};
            
            if (modalTitle.textContent.includes('Ship')) {
                entryType = 'ship_generation';
                const shipNameElement = modalContent.querySelector('h2');
                contentData = {
                    ship_name: shipNameElement ? shipNameElement.textContent : "Unknown ship",
                    timestamp: Math.floor(Date.now() / 1000)
                };
            } else if (modalTitle.textContent.includes('Loot')) {
                entryType = 'loot_generation';
                const lootNameElement = modalContent.querySelector('.loot-name');
                contentData = {
                    name: lootNameElement ? lootNameElement.textContent : "Unknown treasure",
                    timestamp: Math.floor(Date.now() / 1000)
                };
            } else {
                // Generic custom message
                contentData = {
                    message: `Shared ${modalTitle.textContent}`,
                    timestamp: Math.floor(Date.now() / 1000)
                };
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('game_id', gameId);
            formData.append('user_id', currentUserId);
            formData.append('user_email', currentUserEmail);
            formData.append('entry_type', entryType);
            formData.append('content', JSON.stringify(contentData));
            
            // Make API request
            fetch('../api/add_log_entry.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show feedback
                    const originalText = sendToLogBtn.innerHTML;
                    sendToLogBtn.innerHTML = '<i class="fas fa-check"></i> Sent!';
                    setTimeout(() => {
                        sendToLogBtn.innerHTML = originalText;
                    }, 2000);
                    
                    // Close modal
                    setTimeout(() => {
                        closeModal();
                    }, 1000);
                } else {
                    console.error("Error adding log entry:", data.message);
                    alert("Failed to add log entry: " + data.message);
                }
            })
            .catch(error => {
                console.error("Error adding log entry:", error);
                alert("Failed to add log entry. Please try again.");
            });
        });
    }
    
    // Function to open modal with content
    function openGeneratorModal(title, content, generatorFunction) {
        console.log("Opening modal with title:", title);
        
        if (!modalTitle || !modalContent) {
            console.error("Modal elements not found");
            return;
        }
        
        modalTitle.textContent = title;
        modalContent.innerHTML = content;
        currentGenerator = generatorFunction;
        
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
        } else {
            console.error("Modal element not found");
        }
    }
    
    // Function to close modal
    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        }
    }
    
    // Make functions available globally
    window.GeneratorModal = {
        open: openGeneratorModal,
        close: closeModal
    };
    
    console.log("Generator modal system initialized");
});
