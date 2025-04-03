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

// Set active page for sidebar
$current_page = 'sheets';
$page_title = 'Character Sheets';

// Include header
require_once 'components/topbar.php';
?>

<div class="main-wrapper">
    <?php 
    // Include sidebar
    require_once 'components/sidebar.php'; 
    ?>
    
    <main class="content" id="character-sheets-content">
        <div class="sheets-container">
            <!-- Sheet List Sidebar -->
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
                    <!-- Sheets will be loaded here via JavaScript -->
                    <div class="sheets-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading sheets...</span>
                    </div>
                </div>
            </div>
            
            <!-- Sheet Content -->
            <div class="sheet-content">
                <div id="sheet-placeholder" class="sheet-placeholder">
                    <i class="fas fa-scroll"></i>
                    <h3>Select a sheet to view</h3>
                    <p>Or create a new sheet to get started</p>
                </div>
                
                <div id="sheet-display" class="sheet-display" style="display: none;">
                    <!-- Active sheet will be loaded here -->
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Sheet Templates - Hidden -->
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
        <div class="sheet-actions">
            <button class="btn-icon sheet-action-btn" data-action="edit" title="Edit Sheet">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn-icon sheet-action-btn" data-action="delete" title="Delete Sheet">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</template>

<!-- Confirm Delete Modal -->
<div id="delete-confirm-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete this character sheet? This action cannot be undone.</p>
        <div class="modal-actions">
            <button id="cancel-delete" class="btn btn-secondary">Cancel</button>
            <button id="confirm-delete" class="btn btn-danger">Delete</button>
        </div>
    </div>
</div>

<!-- CSS for Character Sheets -->
<style>
    .sheets-container {
        display: flex;
        width: 100%;
        height: calc(100vh - 60px); /* Account for header height */
        background-color: #f5f5f5;
    }
    
    .sheets-sidebar {
        width: 300px;
        background-color: #2d2d2d;
        color: #fff;
        border-right: 1px solid #333;
        display: flex;
        flex-direction: column;
    }
    
    .sheets-header {
        padding: 15px;
        border-bottom: 1px solid #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .sheets-header h2 {
        margin: 0;
        font-size: 1.2rem;
    }
    
    .sheets-list {
        flex-grow: 1;
        overflow-y: auto;
        padding: 10px;
    }
    
    .sheets-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100px;
        color: #888;
    }
    
    .sheets-loading i {
        font-size: 1.5rem;
        margin-bottom: 10px;
    }
    
    .sheet-list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        margin-bottom: 8px;
        background-color: #3c3c3c;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .sheet-list-item:hover {
        background-color: #444;
    }
    
    .sheet-list-item.active {
        background-color: #41C8D4;
        color: #000;
    }
    
    .sheet-list-info {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0;
    }
    
    .sheet-thumbnail {
        width: 40px;
        height: 40px;
        border-radius: 20px;
        object-fit: cover;
        margin-right: 10px;
        background-color: #fff;
    }
    
    .sheet-details {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    
    .sheet-name {
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .sheet-system {
        font-size: 0.8rem;
        color: #41C8D4;
    }
    
    .sheet-list-item.active .sheet-system {
        color: #000;
    }
    
    .sheet-system-badge {
        display: inline-block;
        font-size: 0.9rem;
        background-color: #41C8D4;
        color: #000;
        padding: 3px 8px;
        border-radius: 4px;
        margin-left: 10px;
    }
    
    .sheet-last-edited {
        font-size: 0.8rem;
        color: #aaa;
    }
    
    .sheet-list-item.active .sheet-last-edited {
        color: #000;
    }
    
    .sheet-actions {
        display: flex;
        gap: 8px;
    }
    
    .sheet-content {
        flex: 1;
        overflow-y: auto;
        background-color: white;
        display: flex;
        flex-direction: column;
    }
    
    .sheet-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #888;
        text-align: center;
    }
    
    .sheet-placeholder i {
        font-size: 5rem;
        margin-bottom: 20px;
        color: #ddd;
    }
    
    .sheet-placeholder h3 {
        margin: 10px 0;
    }
    
    .sheet-display {
        height: 100%;
        width: 100%;
        padding: 20px;
        overflow-y: auto;
    }
    
    .btn-icon {
        background: none;
        border: none;
        cursor: pointer;
        color: #fff;
        font-size: 1rem;
        padding: 5px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }
    
    .btn-icon:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .sheet-list-item.active .btn-icon {
        color: #000;
    }
    
    .sheet-list-item.active .btn-icon:hover {
        background-color: rgba(0, 0, 0, 0.1);
    }
    
    /* Confirm Delete Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }
    
    .modal-content {
        background-color: #f5f5f5;
        padding: 20px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        position: relative;
    }
    
    .modal-content h2 {
        margin-top: 0;
    }
    
    .modal .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .sheets-container {
            flex-direction: column;
            height: auto;
        }
        
        .sheets-sidebar {
            width: 100%;
            max-height: 300px;
        }
    }
    
    /* Add styles for the filter */
    .sheets-filter {
        padding: 10px 15px;
        border-bottom: 1px solid #333;
    }
    
    .sheets-filter select {
        width: 100%;
        padding: 8px;
        background-color: #3c3c3c;
        color: #fff;
        border: 1px solid #555;
        border-radius: 4px;
    }
</style>

<!-- JavaScript for Character Sheets -->
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
            
            // Add event listeners for action buttons
            const editBtn = sheetElement.querySelector('[data-action="edit"]');
            const deleteBtn = sheetElement.querySelector('[data-action="delete"]');
            
            editBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                editSheet(sheet.id);
            });
            
            deleteBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                confirmDeleteSheet(sheet.id);
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
    
    // Edit a sheet
    function editSheet(id) {
        window.location.href = `/sheets/edit.php?id=${id}`;
    }
    
    // Print a sheet
    function printSheet(id) {
        window.open(`/sheets/print.php?id=${id}`, '_blank');
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