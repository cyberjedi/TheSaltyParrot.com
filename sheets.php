<?php
/**
 * Character Sheets Page
 * 
 * Main interface for managing multiple character sheets
 * Displays a sidebar with sheet list and main content with the selected sheet
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}

// Set page title
$page_title = 'Character Sheets';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Sheets - The Salty Parrot</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sheets.css">
    <link rel="stylesheet" href="css/topbar.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Include the topbar -->
    <?php include 'components/topbar.php'; ?>
    
    <div class="sheets-container">
        <div class="sheets-sidebar">
            <div class="sheets-header">
                <h2>My Sheets</h2>
                <button id="create-sheet-btn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Sheet
                </button>
            </div>
            
            <div class="sheets-filter">
                <select id="system-filter" class="form-control">
                    <option value="">All Systems</option>
                    <option value="pirate_borg">Pirate Borg</option>
                    <!-- More game systems can be added here in the future -->
                </select>
            </div>
            
            <div class="sheets-list" id="sheets-list">
                <div class="sheets-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Loading sheets...</span>
                </div>
            </div>
        </div>

        <div class="sheets-content">
            <div id="sheet-placeholder" class="sheet-placeholder">
                <h3>Select a sheet to view</h3>
                <p>Or create a new character sheet</p>
                <button id="create-sheet-btn-alt" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Sheet
                </button>
            </div>
            
            <div id="sheet-display" class="sheet-display" style="display: none;"></div>
        </div>
    </div>

    <!-- Sheet List Item Template -->
    <template id="sheet-list-item-template">
        <div class="sheet-list-item" data-sheet-id="">
            <div class="sheet-list-info">
                <img class="sheet-thumbnail" src="" alt="Character Portrait">
                <div class="sheet-details">
                    <span class="sheet-name"></span>
                    <span class="sheet-system"></span>
                    <span class="sheet-last-edited"></span>
                </div>
            </div>
        </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <div id="delete-confirm-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Character Sheet</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this character sheet? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button id="cancel-delete" class="btn btn-secondary">Cancel</button>
                <button id="confirm-delete" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const sheetsList = document.getElementById('sheets-list');
    const sheetDisplay = document.getElementById('sheet-display');
    const sheetPlaceholder = document.getElementById('sheet-placeholder');
    const createSheetBtn = document.getElementById('create-sheet-btn');
    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    const closeModalBtn = deleteConfirmModal.querySelector('.close');
    const systemFilter = document.getElementById('system-filter');
    
    // Template
    const sheetItemTemplate = document.getElementById('sheet-list-item-template');
    
    // State
    let currentSheetId = null;
    let pendingDeleteId = null;
    let currentSystemFilter = '';
    
    // Load all sheets for the current user
    function loadSheets() {
        // Show loading state
        sheetsList.innerHTML = `<div class="sheets-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading sheets...</span>
        </div>`;
        
        // Build the URL with filter if needed
        let url = '/sheets/api/get_sheets.php';
        if (currentSystemFilter) {
            url += `?system=${encodeURIComponent(currentSystemFilter)}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderSheetsList(data.sheets);
                } else {
                    sheetsList.innerHTML = `<div class="error-message">${data.error || 'Failed to load sheets'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error loading sheets:', error);
                sheetsList.innerHTML = '<div class="error-message">Failed to load sheets. Please try again.</div>';
            });
    }
    
    // Render the sheets list
    function renderSheetsList(sheets) {
        // Clear loading indicator
        sheetsList.innerHTML = '';
        
        if (!sheets || sheets.length === 0) {
            sheetsList.innerHTML = `
                <div class="no-sheets">
                    <p>You don't have any character sheets yet.</p>
                    <p>Click "New Sheet" to create one!</p>
                </div>
            `;
            return;
        }
        
        // Add each sheet to the list
        sheets.forEach(sheet => {
            const sheetItem = document.importNode(sheetItemTemplate.content, true);
            const sheetElement = sheetItem.querySelector('.sheet-list-item');
            
            // Set data attributes and content
            sheetElement.dataset.sheetId = sheet.id;
            sheetElement.querySelector('.sheet-name').textContent = sheet.name;
            
            // Add system name
            const systemName = getSystemDisplayName(sheet.system);
            sheetElement.querySelector('.sheet-system').textContent = systemName;
            
            // Format the last edited date
            const lastEdited = new Date(sheet.updated_at);
            const formattedDate = lastEdited.toLocaleDateString();
            sheetElement.querySelector('.sheet-last-edited').textContent = `Last edited: ${formattedDate}`;
            
            // Set thumbnail
            const thumbnail = sheetElement.querySelector('.sheet-thumbnail');
            thumbnail.src = sheet.image_path || 'assets/TSP_default_character.jpg';
            thumbnail.onerror = function() {
                this.src = 'assets/TSP_default_character.jpg';
            };
            
            // Mark as active if this is the current sheet
            if (sheet.id === currentSheetId) {
                sheetElement.classList.add('active');
            }
            
            // Add event listener for selecting the sheet
            sheetElement.addEventListener('click', function(e) {
                // Don't select the sheet if clicking on an action button
                if (e.target.closest('.sheet-action-btn')) {
                    return;
                }
                
                selectSheet(sheet.id);
            });
            
            sheetsList.appendChild(sheetItem);
        });
    }
    
    // Select a sheet
    function selectSheet(id) {
        // Update the current sheet ID
        currentSheetId = id;
        
        // Update the active state in the list
        const sheetItems = sheetsList.querySelectorAll('.sheet-list-item');
        sheetItems.forEach(item => {
            if (item.dataset.sheetId === id.toString()) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
        // Load the sheet content
        loadSheetContent(id);
    }
    
    // Load a sheet's content
    function loadSheetContent(id) {
        // Show loading state
        sheetDisplay.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading sheet...</div>';
        sheetPlaceholder.style.display = 'none';
        sheetDisplay.style.display = 'block';
        
        // Fetch the sheet data
        fetch(`/sheets/api/get_sheet.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderSheetContent(data.sheet);
                } else {
                    sheetDisplay.innerHTML = `<div class="error-message">${data.error || 'Failed to load sheet'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error loading sheet:', error);
                sheetDisplay.innerHTML = '<div class="error-message">Failed to load sheet. Please try again.</div>';
            });
    }
    
    // Render a sheet's content
    function renderSheetContent(sheet) {
        // Create sheet content HTML
        const html = `
            <div class="sheet-header">
                <div class="sheet-title">
                    <h2>${sheet.name}</h2>
                    <div class="sheet-system-badge">${getSystemDisplayName(sheet.system)}</div>
                </div>
                <div class="sheet-actions">
                    <button class="btn btn-primary edit-sheet-btn">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-secondary print-sheet-btn">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <div class="sheet-content">
                <div class="character-header">
                    <div class="character-image">
                        <img src="${sheet.image_path}" 
                            alt="${sheet.name}" 
                            onerror="this.src='assets/TSP_default_character.jpg'">
                    </div>
                </div>
                
                ${renderSystemContent(sheet)}
            </div>
        `;
        
        sheetDisplay.innerHTML = html;
        sheetPlaceholder.style.display = 'none';
        sheetDisplay.style.display = 'block';
        
        // Add event listeners to the buttons
        sheetDisplay.querySelector('.edit-sheet-btn').addEventListener('click', () => {
            window.location.href = `/sheets/edit.php?id=${sheet.id}`;
        });
        
        sheetDisplay.querySelector('.print-sheet-btn').addEventListener('click', () => {
            window.open(`/sheets/print.php?id=${sheet.id}`, '_blank');
        });
    }
    
    // Create a new sheet
    function createSheet() {
        window.location.href = '/sheets/edit.php';
    }
    
    // Confirm sheet deletion
    function confirmDeleteSheet(id) {
        pendingDeleteId = id;
        deleteConfirmModal.style.display = 'flex';
    }
    
    // Delete a sheet
    function deleteSheet(id) {
        fetch('/sheets/api/delete_sheet.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadSheets();
                
                // If the deleted sheet was the current one, clear the display
                if (currentSheetId === id) {
                    currentSheetId = null;
                    sheetDisplay.style.display = 'none';
                    sheetPlaceholder.style.display = 'flex';
                }
            } else {
                alert(data.error || 'Failed to delete sheet');
            }
        })
        .catch(error => {
            console.error('Error deleting sheet:', error);
            alert('Failed to delete sheet. Please try again.');
        });
    }
    
    // Event listeners
    createSheetBtn.addEventListener('click', createSheet);
    
    // Add event listener for the alternative create button in the empty state
    document.getElementById('create-sheet-btn-alt').addEventListener('click', createSheet);
    
    // Add system filter event listener
    systemFilter.addEventListener('change', function() {
        currentSystemFilter = this.value;
        loadSheets();
    });
    
    confirmDeleteBtn.addEventListener('click', function() {
        if (pendingDeleteId) {
            deleteSheet(pendingDeleteId);
            pendingDeleteId = null;
            deleteConfirmModal.style.display = 'none';
        }
    });
    
    cancelDeleteBtn.addEventListener('click', function() {
        pendingDeleteId = null;
        deleteConfirmModal.style.display = 'none';
    });
    
    closeModalBtn.addEventListener('click', function() {
        pendingDeleteId = null;
        deleteConfirmModal.style.display = 'none';
    });
    
    // Close the modal if clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === deleteConfirmModal) {
            pendingDeleteId = null;
            deleteConfirmModal.style.display = 'none';
        }
    });
    
    // Helper function to get display name for game system
    function getSystemDisplayName(systemCode) {
        const systems = {
            'pirate_borg': 'Pirate Borg'
            // Add more systems here as they become available
        };
        
        return systems[systemCode] || systemCode;
    }
    
    // Function to render different system content based on system type
    function renderSystemContent(sheet) {
        if (sheet.system === 'pirate_borg') {
            return `
                <div class="section">
                    <div class="section-title">Attributes</div>
                    <div class="attributes-grid">
                        <div class="attribute">
                            <div class="attribute-label">Strength</div>
                            <div class="attribute-value">${sheet.strength}</div>
                        </div>
                        <div class="attribute">
                            <div class="attribute-label">Agility</div>
                            <div class="attribute-value">${sheet.agility}</div>
                        </div>
                        <div class="attribute">
                            <div class="attribute-label">Presence</div>
                            <div class="attribute-value">${sheet.presence}</div>
                        </div>
                        <div class="attribute">
                            <div class="attribute-label">Toughness</div>
                            <div class="attribute-value">${sheet.toughness}</div>
                        </div>
                        <div class="attribute">
                            <div class="attribute-label">Spirit</div>
                            <div class="attribute-value">${sheet.spirit}</div>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">Notes</div>
                    <div class="sheet-notes">${sheet.notes || 'No notes available.'}</div>
                </div>
            `;
        } 
        
        // Default for unknown systems
        return `<div class="no-system-data">No data available for this system type.</div>`;
    }
    
    // Initial load
    loadSheets();
});
</script>
</body>
</html> 