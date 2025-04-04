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
    <link rel="stylesheet" href="css/character-sheet.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                <div class="placeholder-icon">
                    <i class="fas fa-scroll fa-4x"></i>
                </div>
                <h3>Select a Character Sheet</h3>
                <p>Choose an existing character sheet from the sidebar or create a new one to get started.</p>
                <button id="create-sheet-btn-alt" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Sheet
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
    const createSheetBtnAlt = document.getElementById('create-sheet-btn-alt');
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
        // Build the sheet HTML based on its system
        let contentHTML = '';
        
        // Common header
        contentHTML += `
            <div class="sheet-content-header">
                <div class="sheet-title">
                    <h2>${sheet.name}</h2>
                    <span class="sheet-system-badge">${getSystemDisplayName(sheet.system)}</span>
                </div>
                <div class="sheet-actions">
                    <button class="btn btn-secondary edit-sheet-btn" data-sheet-id="${sheet.id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-secondary print-sheet-btn" data-sheet-id="${sheet.id}">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="btn btn-danger delete-sheet-btn" data-sheet-id="${sheet.id}">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `;
        
        // System-specific content
        if (sheet.system === 'pirate_borg') {
            contentHTML += renderPirateBorgSheet(sheet);
        } else {
            contentHTML += `<div class="error-message">Unsupported system: ${sheet.system}</div>`;
        }
        
        // Update the display
        sheetDisplay.innerHTML = contentHTML;
        
        // Add event listeners to action buttons
        const editBtn = sheetDisplay.querySelector('.edit-sheet-btn');
        const printBtn = sheetDisplay.querySelector('.print-sheet-btn');
        const deleteBtn = sheetDisplay.querySelector('.delete-sheet-btn');
        
        editBtn.addEventListener('click', function() {
            window.location.href = `/sheets/edit.php?id=${sheet.id}`;
        });
        
        printBtn.addEventListener('click', function() {
            window.open(`/sheets/print.php?id=${sheet.id}`, '_blank');
        });
        
        deleteBtn.addEventListener('click', function() {
            openDeleteModal(sheet.id);
        });
    }
    
    // Render a Pirate Borg character sheet
    function renderPirateBorgSheet(sheet) {
        return `
            <div class="character-header">
                <div class="character-image">
                    <img src="${sheet.image_path || 'assets/TSP_default_character.jpg'}" 
                         alt="${sheet.name}" 
                         onerror="this.src='assets/TSP_default_character.jpg'">
                </div>
                <div class="character-info">
                    <h3>${sheet.name}</h3>
                    <p class="character-class">${sheet.character_type || 'Unknown Class'}</p>
                    <p class="character-background">${sheet.background || 'No background information'}</p>
                </div>
            </div>
            
            <div class="section">
                <h4 class="section-title">Attributes</h4>
                <div class="attributes-grid">
                    <div class="attribute">
                        <div class="attribute-label">Strength</div>
                        <div class="attribute-value">${sheet.strength !== undefined ? sheet.strength : '?'}</div>
                    </div>
                    <div class="attribute">
                        <div class="attribute-label">Agility</div>
                        <div class="attribute-value">${sheet.agility !== undefined ? sheet.agility : '?'}</div>
                    </div>
                    <div class="attribute">
                        <div class="attribute-label">Presence</div>
                        <div class="attribute-value">${sheet.presence !== undefined ? sheet.presence : '?'}</div>
                    </div>
                    <div class="attribute">
                        <div class="attribute-label">Toughness</div>
                        <div class="attribute-value">${sheet.toughness !== undefined ? sheet.toughness : '?'}</div>
                    </div>
                    <div class="attribute">
                        <div class="attribute-label">Spirit</div>
                        <div class="attribute-value">${sheet.spirit !== undefined ? sheet.spirit : '?'}</div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h4 class="section-title">Equipment</h4>
                <div class="equipment-list">
                    ${sheet.equipment ? sheet.equipment.split('\n').map(item => `<div class="equipment-item">${item}</div>`).join('') : 'No equipment'}
                </div>
            </div>
            
            <div class="section">
                <h4 class="section-title">Notes</h4>
                <div class="sheet-notes">
                    ${sheet.notes || 'No notes available.'}
                </div>
            </div>
        `;
    }
    
    // Get a display name for a system ID
    function getSystemDisplayName(systemId) {
        const systemMap = {
            'pirate_borg': 'Pirate Borg'
            // Add more systems here as they are supported
        };
        
        return systemMap[systemId] || systemId;
    }
    
    // Open the delete confirmation modal
    function openDeleteModal(sheetId) {
        pendingDeleteId = sheetId;
        deleteConfirmModal.style.display = 'flex';
    }
    
    // Close the delete confirmation modal
    function closeDeleteModal() {
        deleteConfirmModal.style.display = 'none';
        pendingDeleteId = null;
    }
    
    // Delete a sheet
    function deleteSheet(id) {
        fetch(`/sheets/api/delete_sheet.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // If the deleted sheet was the current one, show the placeholder
                if (id === currentSheetId) {
                    currentSheetId = null;
                    sheetDisplay.style.display = 'none';
                    sheetPlaceholder.style.display = 'flex';
                }
                
                // Reload the sheets list
                loadSheets();
            } else {
                alert(`Error deleting sheet: ${data.error || 'Unknown error'}`);
            }
        })
        .catch(error => {
            console.error('Error deleting sheet:', error);
            alert('Failed to delete sheet. Please try again.');
        });
    }
    
    // Event Listeners
    createSheetBtn.addEventListener('click', function() {
        window.location.href = '/sheets/edit.php';
    });
    
    createSheetBtnAlt.addEventListener('click', function() {
        window.location.href = '/sheets/edit.php';
    });
    
    confirmDeleteBtn.addEventListener('click', function() {
        if (pendingDeleteId) {
            deleteSheet(pendingDeleteId);
            closeDeleteModal();
        }
    });
    
    cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    closeModalBtn.addEventListener('click', closeDeleteModal);
    
    systemFilter.addEventListener('change', function() {
        currentSystemFilter = this.value;
        loadSheets();
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === deleteConfirmModal) {
            closeDeleteModal();
        }
    });
    
    // Initialize
    loadSheets();
    
    // Check URL for sheet ID parameter to auto-select a sheet
    function checkUrlForSheetId() {
        const urlParams = new URLSearchParams(window.location.search);
        const sheetId = urlParams.get('id');
        
        if (sheetId) {
            // Wait for sheets to load, then select the specified sheet
            const checkInterval = setInterval(() => {
                const sheetElement = document.querySelector(`.sheet-list-item[data-sheet-id="${sheetId}"]`);
                if (sheetElement) {
                    clearInterval(checkInterval);
                    selectSheet(parseInt(sheetId));
                    
                    // Scroll the sidebar to show the selected sheet
                    sheetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
            
            // Clear interval after 3 seconds if sheet not found
            setTimeout(() => clearInterval(checkInterval), 3000);
        }
    }
    
    // Run auto-select check after sheets are loaded
    setTimeout(checkUrlForSheetId, 300);
});
</script>
</body>
</html> 