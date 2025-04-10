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
    <link rel="stylesheet" href="css/size-adjustments.css">
    <link rel="stylesheet" href="inventory_system/inventory_system.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0v4LLanw2qksYuRlEzO+tcaEPQogQ0KaoGN26/zrn20ImR1DfuLWnOo7aBA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=IM+Fell+English&family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <!-- Site Styles -->
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
                <div class="active-star">
                    <i class="fas fa-star"></i>
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

    <!-- Inventory Modals (needed for inventory_system/inventory.js) -->
    <!-- Add Inventory Item Modal -->
    <div id="add-inventory-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" title="Close">&times;</span>
            <h3>Add Inventory Item</h3>
            
            <div class="item-search-controls">
                <div class="form-group">
                    <label for="item-type-filter">Filter by Type:</label>
                    <select id="item-type-filter">
                        <option value="">All Types</option>
                        <?php 
                        // We need to fetch item types here or make the JS fetch them
                        // Simpler approach: JS fetches types when modal opens. Remove PHP loop.
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="item-search">Search by Name:</label>
                    <input type="text" id="item-search" placeholder="Search items...">
                </div>
            </div>
            
            <div class="available-items-container">
                <h4>Available Items</h4>
                <div class="available-items-list">
                    <!-- Items will be loaded here via AJAX -->
                    <div class="loading-items">Loading available items...</div>
                </div>
            </div>
            <div class="modal-actions">
                 <button type="button" class="btn btn-secondary close-modal-button">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Item Details Modal -->
    <div id="item-details-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" title="Close">&times;</span>
            <h3 id="item-detail-name">Item Details</h3>
            
            <div class="item-details-content">
                <!-- Details will be loaded here via AJAX -->
                <p>Loading details...</p>
            </div>
             <div class="modal-actions">
                 <button type="button" class="btn btn-secondary close-modal-button">Close</button>
            </div>
        </div>
    </div>
    <!-- End Inventory Modals -->

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
    
    // Photo management state
    let currentPhotoManagementSheetId = null;
    let selectedPhotoPath = null;
    let photoToDelete = null;
    
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
        
        // Track if we found an active sheet
        let foundActive = false;
        let activeSheetId = null;
        
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
            
            // Show/hide active star based on is_active field
            const activeStar = sheetElement.querySelector('.active-star');
            activeStar.style.display = 'none'; // Hide by default
            
            if (sheet.is_active == 1) { // Explicitly check for 1
                activeStar.style.display = 'flex';
                foundActive = true;
                activeSheetId = sheet.id;
            }
            
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
        
        // If we found an active sheet and no specific sheet is selected, display the active one
        if (foundActive && activeSheetId && !currentSheetId) {
            selectSheet(activeSheetId);
        }
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
        
        // Load the sheet content using the globally accessible function
        window.fetchSheetDetails(id);
    }
    
    // Load a sheet's content
    // Make this function globally accessible for inventory updates
    window.fetchSheetDetails = function(id) {
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
        
        // Check if this sheet is active
        const isActive = sheet.is_active == 1;
        const activeButtonClass = isActive ? 'btn-submit' : 'btn-secondary';
        const activeButtonText = isActive ? 'Active Character' : 'Make Active';
        
        // Common header
        contentHTML += `
            <div class="sheet-content-header">
                <div class="sheet-title">
                    <h2>${sheet.name}</h2>
                    <span class="sheet-system-badge">${getSystemDisplayName(sheet.system)}</span>
                </div>
                <div class="sheet-actions">
                    <button class="btn ${activeButtonClass} make-active-btn" data-sheet-id="${sheet.id}">
                        <i class="fas fa-star"></i> ${activeButtonText}
                    </button>
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

        // *** Initialize Inventory System JS now that HTML is loaded ***
        if (window.initializeInventorySystem) {
            // Defer initialization slightly to ensure DOM is fully updated after innerHTML assignment
            setTimeout(() => {
                console.log("Deferred call to initializeInventorySystem for sheet:", sheet.id);
                window.initializeInventorySystem(sheet.id);
            }, 0); // Timeout 0 pushes execution after current stack clears
        } else {
            console.error("Inventory system script (initializeInventorySystem) not found!");
        }
        // *** End Inventory System Init ***
        
        // Add event listeners to action buttons
        const makeActiveBtn = sheetDisplay.querySelector('.make-active-btn');
        const editBtn = sheetDisplay.querySelector('.edit-sheet-btn');
        const printBtn = sheetDisplay.querySelector('.print-sheet-btn');
        const deleteBtn = sheetDisplay.querySelector('.delete-sheet-btn');
        
        makeActiveBtn.addEventListener('click', function() {
            setActiveSheet(sheet.id);
        });
        
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
        const currentHp = sheet.hp_current !== undefined ? parseInt(sheet.hp_current) : 0;
        const maxHp = sheet.hp_max !== undefined ? parseInt(sheet.hp_max) : 1; // Ensure maxHp is at least 1
        
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
                ${sheet.system === 'pirate_borg' ? `
                <div class="pirate-borg-logo">
                    <img src="assets/Pirate_Borg_Compatible_Vert_White.png" alt="Pirate Borg Compatible">
                </div>` : ''}
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
                    <div class="attribute hp-attribute" data-sheet-id="${sheet.id}">
                        <div class="attribute-label">Hit Points</div>
                        <div class="hp-value-controls-wrapper">
                            <div class="hp-display">
                                <span class="hp-value current-hp" id="hp-current-val-${sheet.id}">${currentHp}</span>
                                <span class="hp-separator">/</span>
                                <span class="hp-value max-hp" id="hp-max-val-${sheet.id}">${maxHp}</span>
                            </div>
                            <div class="hp-controls">
                                <button class="hp-adjust-btn" data-change="-1" title="Decrease HP">-</button>
                                <button class="hp-adjust-btn" data-change="1" title="Increase HP">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h4 class="section-title">Inventory</h4>
                <div class="inventory-section-wrapper">
                    ${sheet.inventory_html || '<div class="alert alert-warning">Inventory data missing.</div>'}
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
        fetch(`/sheets/api/delete_sheet.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
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

    // Set a sheet as active
    function setActiveSheet(id) {
        fetch(`/sheets/api/set_active_sheet.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the button immediately
                    const makeActiveBtn = sheetDisplay.querySelector('.make-active-btn');
                    makeActiveBtn.classList.remove('btn-secondary');
                    makeActiveBtn.classList.add('btn-submit');
                    makeActiveBtn.innerHTML = '<i class="fas fa-star"></i> Active Character';
                    
                    // Reload sheets to update active status in the sidebar
                    loadSheets();
                    
                    // Show success message
                    const sheetTitle = sheetDisplay.querySelector('.sheet-title h2').textContent;
                    alert(`${sheetTitle} has been set as your active character.`);
                } else {
                    alert(`Error setting active sheet: ${data.error || 'Unknown error'}`);
                }
            })
            .catch(error => {
                console.error('Error setting active sheet:', error);
                alert('Failed to set active sheet. Please try again.');
            });
    }

    // Add event listeners using event delegation on sheetDisplay for HP buttons
    sheetDisplay.addEventListener('click', function(event) {
        if (event.target.classList.contains('hp-adjust-btn')) {
            const button = event.target;
            const attributeBox = button.closest('.hp-attribute'); // Find parent HP box
            const sheetId = attributeBox.dataset.sheetId;
            const change = parseInt(button.dataset.change);
            
            if(sheetId && !isNaN(change)) {
                 updateHp(sheetId, change);
            }
        }
    });

    // Function to update HP via API
    function updateHp(sheetId, change) {
        fetch('/sheets/api/update_hp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ sheet_id: parseInt(sheetId), change: change })
        })
        .then(response => {
            if (!response.ok) {
                // Try to parse error message from response body
                 return response.json().then(err => {
                    throw new Error(err.error || `HTTP error! status: ${response.status}`);
                }).catch(() => {
                    // If parsing error fails, throw generic HTTP error
                    throw new Error(`HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const currentHpElement = document.getElementById(`hp-current-val-${sheetId}`);
                const maxHpElement = document.getElementById(`hp-max-val-${sheetId}`);
                if (currentHpElement) {
                    currentHpElement.textContent = data.hp_current;
                }
                // Optionally update max HP display if it could change (though not via +/- buttons)
                // if (maxHpElement && data.hp_max !== undefined) {
                //     maxHpElement.textContent = data.hp_max;
                // }
            } else {
                console.error('Error updating HP:', data.error);
                alert(`Failed to update HP: ${data.error || 'Unknown error'}`);
            }
        })
        .catch(error => {
            console.error('Error during fetch:', error);
            alert(`Error updating HP: ${error.message}. Please check the console for details.`);
        });
    }
});
</script>

    <!-- Add the Inventory System Javascript -->
    <!-- Note: Core sheets page logic is in the script block below -->
    <script src="inventory_system/inventory.js" defer></script>

</body>
</html> 