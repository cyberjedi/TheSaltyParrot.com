/**
 * Inventory System JavaScript
 * Handles interactions within the inventory display component.
 * Initialization is triggered manually by calling window.initializeInventorySystem()
 * after the inventory HTML has been loaded into the DOM.
 */

// --- Utility Functions (Defined Globally within this script's scope) ---

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Show feedback
function showFeedback(message, type = 'info') {
    console.log(`Feedback (${type}): ${message}`);
    // TODO: Implement a proper notification system (e.g., toast notifications)
}

// Fetch API Helper
async function fetchInventoryAPI(endpoint, method = 'GET', body = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
    };
    if (body) {
        options.body = JSON.stringify(body);
    }
    try {
        const response = await fetch(`/inventory_system/${endpoint}`, options);
        if (!response.ok) {
            let errorMsg = `HTTP error! Status: ${response.status} ${response.statusText}`;
            try {
                // Try to get more info from the response body
                const errorBody = await response.text(); // Read as text first
                try {
                     // Attempt to parse as JSON, maybe it is JSON after all
                     const errorData = JSON.parse(errorBody);
                     errorMsg = errorData.message || errorMsg;
                } catch (jsonError) {
                     // If JSON parsing fails, use the first part of the text body (if any)
                     errorMsg = errorBody.substring(0, 100) || errorMsg; 
                }
            } catch (e) { 
                // If reading response body text fails, stick with the status message
                console.warn("Could not read error response body", e);
            }
            throw new Error(errorMsg);
        }
        // If response.ok is true, expect valid JSON
        return await response.json();
    } catch (error) {
        console.error(`API call failed (${endpoint}):`, error);
        showFeedback(`Error interacting with inventory: ${error.message}`, 'error');
        throw error; // Re-throw for potential handling by caller
    }
}

// Modal Helpers
function openModal(modal) {
    if (modal) {
        // Directly set position using absolute values rather than flex
        modal.style.display = 'block';
        
        // Explicitly position the modal content at the top
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            // Force the content to appear at the top with fixed positioning
            modalContent.style.position = 'relative';
            modalContent.style.margin = '80px auto';  // 80px from top, auto left/right for centering
            modalContent.style.maxHeight = '75vh';    // Slightly smaller to ensure it fits
        }
        
        // Set modal to take the full screen but with proper z-index
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.overflow = 'auto';
    } else {
        console.error("Attempted to open a null modal");
    }
}

function closeModal(modal) {
    if (modal) {
        modal.style.display = 'none';
        // console.log('Closed modal:', modal.id);
    } else {
        console.error("Attempted to close a null modal");
    }
}

// Load Available Items for the Add modal
async function loadAvailableItems() {
    const addModal = document.getElementById('add-inventory-modal');
    const addModalAvailableList = addModal ? addModal.querySelector('.available-items-list') : null;
    const addModalTypeFilter = document.getElementById('item-type-filter');
    const addModalSearchInput = document.getElementById('item-search');

    if (!addModal || !addModalAvailableList || !addModalTypeFilter || !addModalSearchInput) {
        console.error("Missing elements for loadAvailableItems function.");
        if (addModalAvailableList) addModalAvailableList.innerHTML = '<div class="error-message">Error: Could not load UI elements.</div>';
        return;
    }

    const type = addModalTypeFilter.value;
    const search = addModalSearchInput.value;
    // console.log(`Loading available items (Type: '${type}', Search: '${search}')...`);
    addModalAvailableList.innerHTML = '<div class="loading-items"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

    try {
        const data = await fetchInventoryAPI(`get_available_items.php?type=${encodeURIComponent(type)}&search=${encodeURIComponent(search)}`);
        if (data.status === 'success') {
            renderAvailableItems(data.items);
        } else {
            addModalAvailableList.innerHTML = `<div class="error-message">${data.message || 'Failed to load items'}</div>`;
        }
    } catch (error) {
         addModalAvailableList.innerHTML = `<div class="error-message">Error loading items.</div>`;
    }
}
// Create a debounced version for filter inputs
const debouncedLoadItems = debounce(loadAvailableItems, 300);

// Render Available Items list in the Add modal
function renderAvailableItems(items) {
    const addModal = document.getElementById('add-inventory-modal');
    const addModalAvailableList = addModal ? addModal.querySelector('.available-items-list') : null;
    if (!addModalAvailableList) return; // Should not happen if loadAvailableItems checked

    addModalAvailableList.innerHTML = ''; // Clear previous list/loading
    if (!items || items.length === 0) {
        addModalAvailableList.innerHTML = '<p>No matching items found.</p>';
        return;
    }

    items.forEach(item => {
        const div = document.createElement('div');
        div.classList.add('available-item');
        div.dataset.itemId = item.item_id;
        
        // Determine tag class (similar logic to inventory_display.php)
        const itemType = item.item_type || '';
        let tagColorClass = 'item-tag-default';
        if (itemType === 'Relic') {
            tagColorClass = 'item-tag-magic';
        }
        // Add more conditions here if other tag colors are defined
        
        div.innerHTML = `
            <span class="available-item-name" title="${item.item_description || ''}">
                 <span class="item-name-value">${item.item_name}</span> 
                 ${itemType ? `<span class="item-type-tag ${tagColorClass}">${itemType}</span>` : ''}
            </span>
            <button class="btn btn-sm btn-add-this-item" title="Add ${item.item_name}">
                <i class="fas fa-plus"></i> Add
            </button>
        `;
        // Add event listener directly to the button for this specific item
        div.querySelector('.btn-add-this-item').addEventListener('click', () => handleAddItem(item.item_id));
        addModalAvailableList.appendChild(div);
    });
 }

// Show Item Details in the Details modal
async function showItemDetails(itemId) {
     const detailsModal = document.getElementById('item-details-modal');
     const detailsContent = detailsModal ? detailsModal.querySelector('.item-details-content') : null;
     const detailsName = document.getElementById('item-detail-name');

     if(!detailsModal || !detailsContent || !detailsName) {
         console.error("Missing elements required for showItemDetails.");
         return;
     }

     detailsContent.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Loading details...</p>';
     detailsName.textContent = 'Item Details';
     openModal(detailsModal);

     try {
         // TODO: Ensure /api/get_item_details.php exists and works
         const response = await fetch(`/inventory_system/get_item_details.php?item_id=${itemId}`);
         if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
         const data = await response.json();

         if (data.status === 'success' && data.item) {
             const item = data.item;
             detailsName.textContent = item.item_name || 'Item Details';
             // Use descriptive text if description is long
             const description = item.item_description ? item.item_description.replace(/\n/g, '<br>') : 'No description available.';
             detailsContent.innerHTML = `
                 <div class="item-detail-row">
                     <span class="item-detail-label">Type:</span>
                     <span class="item-detail-value">${item.item_type || '-'}</span>
                 </div>
                 <div class="item-detail-row">
                     <span class="item-detail-label">Source:</span>
                     <span class="item-detail-value">${item.source || '-'}</span>
                 </div>
                 <div class="item-description-container">
                     <h4>Description</h4>
                     <div class="item-description">${description}</div>
                 </div>
                 <!-- Add more fields here if needed -->
             `;
         } else {
              detailsContent.innerHTML = `<p class="error-message">${data.message || 'Failed to load item details.'}</p>`;
         }
     } catch (error) {
         console.error('Error fetching item details:', error);
         detailsContent.innerHTML = `<p class="error-message">Error loading details.</p>`;
     }
 }

// Handle Adding an Item via API
async function handleAddItem(itemId, containerId = null) {
    // Fetch sheetId dynamically when needed, directly from the container
    const sheetId = document.querySelector('#sheet-display .character-inventory .inventory-container')?.dataset.sheetId;
    if (!sheetId) {
        console.error("Cannot add item: sheetId missing from inventory container in the DOM.");
        showFeedback("Error: Could not identify the current character sheet.", "error");
        return;
    }
    // console.log(`Attempting to add item ${itemId} to sheet ${sheetId}`);
    try {
         const data = await fetchInventoryAPI('add_item.php', 'POST', {
             sheet_id: sheetId,
             item_id: itemId,
             container_id: containerId // Will be null if not provided
         });
         if (data.status === 'success') {
             showFeedback(`Item added successfully.`, 'success'); // Use generic message, API response might have specifics
             reloadInventorySection(); // Reload inventory section to show the new item
             closeModal(document.getElementById('add-inventory-modal'));
         } else {
             showFeedback(data.message || 'Failed to add item', 'error');
         }
     } catch (error) { /* Error message handled by fetchInventoryAPI */ }
 }

// Handle Removing an Item via API
async function handleRemoveItem(mapId, itemName) {
    // Standardized confirmation message
    const confirmMessage = `Are you sure you want to remove "${itemName}" from your inventory? This action cannot be undone.`;
    if (!confirm(confirmMessage)) return;

    // console.log(`Attempting to remove item with map_id ${mapId}`);
    try {
        const data = await fetchInventoryAPI('remove_item.php', 'POST', { map_id: mapId }); // Using POST for simplicity, DELETE preferred
        if (data.status === 'success') {
            showFeedback(data.message || 'Item removed', 'success');
            // Directly remove the row from the table for immediate feedback
            const inventoryTableBody = document.getElementById('inventory-table-body');
            const rowToRemove = inventoryTableBody ? inventoryTableBody.querySelector(`tr[data-map-id="${mapId}"]`) : null;
            if (rowToRemove) {
                rowToRemove.remove();
                checkIfInventoryEmpty(); // Update empty message if needed
            } else {
                console.warn("Could not find row to remove, requesting full reload.");
                reloadInventorySection(); // Fallback if row not found
            }
        } else {
            showFeedback(data.message || 'Failed to remove item', 'error');
        }
    } catch (error) { /* Handled by fetchInventoryAPI */ }
}

// Handle Updating Quantity via API
async function handleUpdateQuantity(mapId, change) {
    const inventoryTableBody = document.getElementById('inventory-table-body');
    const row = inventoryTableBody ? inventoryTableBody.querySelector(`tr[data-map-id="${mapId}"]`) : null;
    const quantitySpan = row ? row.querySelector('.quantity-value') : null;

    if (!row || !quantitySpan) {
        console.error("Could not find row or quantity span for map_id:", mapId);
        return;
    }

    const currentQuantity = parseInt(quantitySpan.textContent, 10);
    if (isNaN(currentQuantity)) {
        console.error("Invalid current quantity for map_id:", mapId);
        return;
    }

    // If decreasing quantity to 0 or less, trigger remove action instead
    if (currentQuantity + change <= 0) {
        console.log(`Quantity for map_id ${mapId} reached zero, triggering removal.`);
        // Get the name specifically from the item-name-value span
        const itemName = row.querySelector('.item-name-value')?.textContent || 'Item'; 
        handleRemoveItem(mapId, itemName.trim()); 
        return;
    }

    // console.log(`Attempting to update quantity for map_id ${mapId} by ${change}`);
    try {
         const data = await fetchInventoryAPI('update_quantity.php', 'POST', { // Using POST, ideally PUT/PATCH
             map_id: mapId,
             change: change
         });
         if (data.status === 'success') {
             // Server handles both update and deletion cases (though we handle deletion client-side now too)
             if (data.action === 'deleted') {
                 showFeedback(data.message || 'Item removed', 'success');
                 row.remove();
                 checkIfInventoryEmpty();
             } else if (data.action === 'updated') {
                 showFeedback(data.message || 'Quantity updated', 'success');
                 quantitySpan.textContent = data.quantity; // Update quantity in the UI
             } else {
                 // Fallback if action isn't specified, just update quantity if provided
                 if (data.quantity !== undefined) {
                    quantitySpan.textContent = data.quantity;
                 } else {
                    reloadInventorySection(); // Fallback if unclear
                 }
             }
         } else {
             showFeedback(data.message || 'Failed to update quantity', 'error');
         }
    } catch (error) { /* Handled by fetchInventoryAPI */ }
 }

// Reload Inventory Section Helper (Targets only the inventory container)
async function reloadInventorySection() {
    // Get sheetId dynamically from the container when needed
    const inventoryContainer = document.querySelector('#sheet-display .character-inventory .inventory-container');
    const sheetId = inventoryContainer?.dataset.sheetId;
    const inventoryWrapper = document.querySelector('#sheet-display .inventory-section-wrapper'); // Target the wrapper div

    if (!sheetId || !inventoryWrapper || !inventoryContainer) {
        console.error("Cannot reload inventory: Missing sheetId, wrapper, or inventory container.");
        showFeedback("Inventory updated. Please refresh the page if changes are not reflected.", "warning");
        return;
    }

    // --- Store current scroll position --- 
    const storedScrollY = window.scrollY;

    // Show a temporary loading state inside the wrapper
    inventoryWrapper.innerHTML = '<div class="loading-items"><i class="fas fa-spinner fa-spin"></i> Reloading Inventory...</div>';

    try {
        const response = await fetch(`/inventory_system/get_inventory_html.php?sheet_id=${sheetId}`);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || `HTTP error ${response.status}`);
        }
        const newInventoryHtml = await response.text();
        
        // Replace the content of the wrapper
        inventoryWrapper.innerHTML = newInventoryHtml;

        // Re-initialize inventory interactions AND restore scroll position, deferred slightly
        setTimeout(() => {
             // console.log("Re-initializing inventory system after partial reload for sheet:", sheetId);
             if (window.initializeInventorySystem) {
                 window.initializeInventorySystem(sheetId);
             } else {
                 console.error("initializeInventorySystem function not found after inventory reload!");
             }
             // --- Restore scroll position --- 
             window.scrollTo(0, storedScrollY);
             // console.log("Restored scroll position to:", storedScrollY);
         }, 0);

    } catch (error) {
        console.error('Error reloading inventory section:', error);
        inventoryWrapper.innerHTML = `<div class="alert alert-danger">Failed to reload inventory: ${error.message || 'Unknown error'}</div>`;
        showFeedback("Failed to update inventory view.", "error");
    }
}

// Check if Inventory is Empty Helper
function checkIfInventoryEmpty() {
    const inventoryTableBody = document.getElementById('inventory-table-body');
    // Find the specific inventory container within the sheet display
    const inventoryContainer = document.querySelector('#sheet-display .character-inventory .inventory-container');

    if (!inventoryTableBody || !inventoryContainer) {
        // Don't proceed if elements aren't loaded correctly
        console.warn("checkIfInventoryEmpty: Could not find table body or the specific inventory container.");
        return;
    }

    const dropzone = inventoryContainer.querySelector('.inventory-dropzone'); // Container holding the table
    let emptyMsgDiv = inventoryContainer.querySelector('.empty-inventory'); // Existing empty message div

    if (inventoryTableBody.children.length === 0) {
        // Inventory is empty
        if (dropzone) dropzone.style.display = 'none'; // Hide table area
         if (!emptyMsgDiv) {
             // Create and append the message if it doesn't exist *inside this specific inventory container*
             emptyMsgDiv = document.createElement('div');
             emptyMsgDiv.classList.add('empty-inventory');
             emptyMsgDiv.innerHTML = '<p>No items in inventory. Click the "+" button to add items.</p>';
             inventoryContainer.appendChild(emptyMsgDiv); // Append inside the specific container
             // console.log("Displayed empty inventory message.");
          } else {
              // Show the existing message if it's there
              emptyMsgDiv.style.display = 'block';
              // console.log("Displayed existing empty inventory message.");
          }
    } else {
        // Inventory has items
         if (dropzone) dropzone.style.display = 'block'; // Or 'table' etc.
         if (emptyMsgDiv) emptyMsgDiv.style.display = 'none'; // Hide empty message
         // console.log("Inventory has items, hiding empty message.");
    }
 }

// --- Drag and Drop Functionality ---
function setupDragAndDrop(inventoryTableBody, inventoryContainer) {
    let draggedItem = null;
    let originalContainerId = null;

    inventoryTableBody.addEventListener('dragstart', (e) => {
        // Ensure we are dragging an inventory item row
        if (e.target.classList.contains('inventory-item') && e.target.draggable) {
            draggedItem = e.target;
            originalContainerId = draggedItem.dataset.containerId;
            e.dataTransfer.setData('text/plain', draggedItem.dataset.mapId); // Store map_id
            e.dataTransfer.effectAllowed = 'move';
            draggedItem.classList.add('dragging');
            // console.log(`Drag Start: Item map_id=${draggedItem.dataset.mapId}, Original container: ${originalContainerId}`);
        }
    });

    inventoryTableBody.addEventListener('dragover', (e) => {
        e.preventDefault(); // Necessary to allow dropping
        const targetRow = e.target.closest('tr.inventory-item');

        if (targetRow && targetRow !== draggedItem) {
            const canContain = targetRow.dataset.canContain === 'true';
            const isDroppableContainer = targetRow.classList.contains('droppable-container');
            const targetMapId = targetRow.dataset.mapId;

            // Prevent dropping on itself or into a non-container that isn't the root dropzone area
            if (targetMapId === draggedItem.dataset.mapId) {
                 e.dataTransfer.dropEffect = 'none';
                 removeDragOverStyles();
                 return;
            }

            if (isDroppableContainer && canContain) {
                e.dataTransfer.dropEffect = 'move';
                removeDragOverStyles(); // Clear previous highlights
                targetRow.classList.add('drag-over');
            } else {
                e.dataTransfer.dropEffect = 'none';
                removeDragOverStyles();
            }
        } else {
            // Allow dropping on the main table body/dropzone area (representing root)
            const targetDropzone = e.target.closest('.inventory-dropzone');
            if (targetDropzone && targetDropzone.dataset.containerId === 'root') {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                removeDragOverStyles();
                targetDropzone.classList.add('drag-over');
            } else {
                 e.dataTransfer.dropEffect = 'none';
                 removeDragOverStyles();
            }
        }
    });

    inventoryTableBody.addEventListener('dragleave', (e) => {
        // Remove visual feedback ONLY if leaving the element triggering the event
        // This prevents flickering when moving over child elements within the target
        if (e.target.classList && (e.target.classList.contains('drag-over') || e.target.closest('.drag-over'))) {
             // Check relatedTarget - if it's null or outside the current element, remove highlight
             if (!e.relatedTarget || !e.currentTarget.contains(e.relatedTarget)) {
                 removeDragOverStyles();
             }
         } else if (e.target.closest && !e.target.closest('.inventory-item, .inventory-dropzone')) {
             // If we moved completely out of the table area
             removeDragOverStyles();
         }
    });

    inventoryTableBody.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!draggedItem) return;

        const targetRow = e.target.closest('tr.inventory-item.droppable-container.drag-over');
        const targetDropzone = e.target.closest('.inventory-dropzone.drag-over');
        let targetContainerMapId = 'root'; // Default to root

        removeDragOverStyles(); // Clean up highlights

        // Determine the target container map_id
        if (targetRow && targetRow.dataset.canContain === 'true' && targetRow !== draggedItem) {
            targetContainerMapId = targetRow.dataset.mapId;
            // console.log(`Drop Target: Container Item map_id=${targetContainerMapId}`);
        } else if (targetDropzone && targetDropzone.dataset.containerId === 'root') {
            targetContainerMapId = 'root';
            // console.log(`Drop Target: Root`);
        } else {
            // console.log("Drop: Invalid target.");
            clearDragState();
            return; // Dropped on an invalid area
        }

        // Prevent dropping item into itself (already checked in dragover, but good failsafe)
        if (draggedItem.dataset.mapId === targetContainerMapId) {
            // console.log("Drop: Cannot drop item into itself.");
            clearDragState();
            return;
        }
        // Prevent dropping if it's the same container it came from
        if (String(originalContainerId) === String(targetContainerMapId)) { // Ensure type comparison
            // console.log("Drop: Item is already in this container.");
            clearDragState();
            return;
        }

        const itemMapId = draggedItem.dataset.mapId;
        const sheetId = inventoryContainer.dataset.sheetId;

        // console.log(`Attempting to move item map_id=${itemMapId} to container map_id=${targetContainerMapId} for sheet_id=${sheetId}`);

        try {
            // --- Send Update to Backend --- 
            const data = await fetchInventoryAPI('update_inventory_container.php', 'POST', {
                sheet_id: sheetId,
                item_map_id: itemMapId,
                target_container_map_id: targetContainerMapId
            });

            if (data.success) {
                 console.log("Update successful:", data.message || 'Item moved.');
                 showFeedback(data.message || 'Item moved successfully.', 'success');

                 // --- Update UI on Success ---
                 // Instead of manual DOM manipulation, reload the inventory section 
                 // to ensure the hierarchy is perfectly re-rendered based on the new DB state.
                 // moveItemAndUpdateHierarchy(draggedItem, targetContainerMapId, inventoryTableBody);
                 reloadInventorySection(); 

            } else {
                console.error('Failed to update container:', data.message);
                showFeedback(`Error moving item: ${data.message}`, 'error');
                // Optionally revert visual position if backend fails? Or rely on reload.
            }
        } catch (error) {
            // Error already logged by fetchInventoryAPI
            // showFeedback is also called within fetchInventoryAPI on error
            // Maybe show a generic message if needed
        } finally {
            clearDragState();
        }
    });

    inventoryTableBody.addEventListener('dragend', (e) => {
        // Clean up after drag ends (regardless of success/failure)
        clearDragState();
    });

    function removeDragOverStyles() {
        document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
    }

    function clearDragState() {
        if (draggedItem) {
            draggedItem.classList.remove('dragging');
        }
        removeDragOverStyles(); // Ensure highlights are cleared
        draggedItem = null;
        originalContainerId = null;
    }

     // Function to handle moving the item in the DOM and updating levels
     function moveItemAndUpdateHierarchy(itemRow, targetContainerMapId, tableBody) {
         const targetContainerRow = tableBody.querySelector(`tr[data-map-id="${targetContainerMapId}"]`);
         let newLevel = 0;

         // Determine insertion point and new level
         if (targetContainerMapId === 'root' || !targetContainerRow) {
             // Move to the end of the root level items
             tableBody.appendChild(itemRow); // Appending moves the element
             newLevel = 0;
         } else {
             // Insert after the target container row or the last item already inside it
             let insertAfter = targetContainerRow;
             const existingItemsInContainer = tableBody.querySelectorAll(`tr[data-container-id="${targetContainerMapId}"]`);
             if (existingItemsInContainer.length > 0) {
                 insertAfter = existingItemsInContainer[existingItemsInContainer.length - 1];
             }
             // Use insertAdjacentElement for clarity and potentially better handling
             insertAfter.insertAdjacentElement('afterend', itemRow); // Inserting also moves the element
             
             // Calculate new level based on target container
             const targetLevelMatch = targetContainerRow.className.match(/container-level-(\d+)/);
             const targetLevel = targetLevelMatch ? parseInt(targetLevelMatch[1], 10) : 0;
             newLevel = targetLevel + 1;
         }

         // Update the moved item's level and indentation
         updateItemLevel(itemRow, newLevel);

         // Recursively update levels and indentation for any items *inside* the moved item
         if (itemRow.classList.contains('droppable-container')) {
             const movedItemId = itemRow.dataset.mapId;
             // Find direct children *after* the move
             const children = Array.from(tableBody.children).filter(row => row.dataset.containerId === movedItemId);
             children.forEach(child => {
                 moveItemAndUpdateHierarchy(child, movedItemId, tableBody); // Recursive call to update children
             });
         }

         // TODO: Update visual state of the *target* container (e.g., show/hide expand/collapse icon)
         // This might involve checking if targetContainerRow now has children (elements with data-container-id matching its map-id)
     }

     // Helper to update a single item's class and indentation
     function updateItemLevel(itemRow, level) {
         const nameSpan = itemRow.querySelector('.item-name-text');
         const indentation = level * 30; // Increased indentation to 30px per level
         if (nameSpan) {
             nameSpan.style.paddingLeft = `${indentation}px`;
         }
         // Remove existing level class and add the new one
         itemRow.className = itemRow.className.replace(/\s?container-level-\d+/, '');
         itemRow.classList.add(`container-level-${level}`);
     }
}

// --- Global Initialization Function ---
// This function should be called *after* the character sheet HTML (including inventory)
// has been loaded into the #sheet-display container.
window.initializeInventorySystem = function(loadedSheetId) {
    // console.log(">>> Initializing Inventory System for sheet ID:", loadedSheetId);

    // Find the main containers again within the current DOM state
    const sheetDisplay = document.getElementById('sheet-display');
    const inventoryContainer = sheetDisplay ? sheetDisplay.querySelector('.character-inventory .inventory-container') : null;

    // --- Basic Sanity Checks ---
    if (!sheetDisplay) {
        console.error("Inventory JS Init FAIL: #sheet-display container not found.");
        return;
    }
     if (!inventoryContainer) {
        console.error("Inventory JS Init FAIL: .character-inventory .inventory-container not found within #sheet-display.");
        return;
    }
    const sheetId = inventoryContainer.dataset.sheetId;
    if (!sheetId) {
         console.error("Inventory JS Init FAIL: Missing data-sheet-id on .inventory-container.");
         return;
    }
    // Check consistency
     if (loadedSheetId && sheetId !== loadedSheetId.toString()) {
        console.warn(`Inventory JS Init: Mismatch between expected sheet ID (${loadedSheetId}) and found data-sheet-id (${sheetId}). Using found ID.`);
    }
    // console.log(`Inventory JS Init: Found inventory container for sheet ${sheetId}.`);


    // --- Find Modal Elements (Assume they are static in sheets.php) ---
    const addModal = document.getElementById('add-inventory-modal');
    const detailsModal = document.getElementById('item-details-modal');
    const addModalSearchInput = document.getElementById('item-search');
    const addModalTypeFilter = document.getElementById('item-type-filter');

     if (!addModal || !detailsModal) {
         console.error("Inventory JS Init: Add or Details modal element not found in the main DOM.");
     }
     if (!addModalSearchInput || !addModalTypeFilter) {
         console.warn("Inventory JS Init: Add modal filter/search inputs not found.");
     }

    // --- Attach Event Listeners using Event Delegation ---

    // console.log("Inventory JS Init: Attaching delegated listeners...");

    // Listener for Add Item Button (delegated to sheetDisplay)
    // Use named function stored globally to allow removal
    if (window.handleInventoryAddClick) { // Remove previous if exists
        sheetDisplay.removeEventListener('click', window.handleInventoryAddClick);
    }
    window.handleInventoryAddClick = function(event) {
        const addButton = event.target.closest('#add-inventory-item-btn');
        if (addButton) {
            // console.log('Inventory JS: Delegated add button click detected!');
            if (addModal) { // Check modal exists before trying to open
                openModal(addModal);
                loadAvailableItems(); // Fetch items when modal opens
            } else {
                 console.error("Cannot open Add modal - element not found.");
            }
        }
    };
    sheetDisplay.addEventListener('click', window.handleInventoryAddClick);


    // Listener for Table Actions (delegated to inventoryContainer)
     // Use named function stored globally to allow removal
    if (window.handleInventoryTableClick) { // Remove previous if exists
        inventoryContainer.removeEventListener('click', window.handleInventoryTableClick);
    }
    window.handleInventoryTableClick = function(event) {
        const target = event.target;
        const button = target.closest('button');
        // Ignore clicks not on buttons or if it's the main Add button (handled above)
        if (!button || button.id === 'add-inventory-item-btn') return;

        const row = target.closest('tr.inventory-item');
        if (!row) return; // Click was on a button but not within an item row

        const mapId = row.dataset.mapId;
        const itemId = row.dataset.itemId; // For info button

        // console.log(`Inventory action detected: Button class='${button.className}', mapId=${mapId}, itemId=${itemId}`);

        if (button.classList.contains('increase-btn')) {
            handleUpdateQuantity(mapId, 1);
        } else if (button.classList.contains('decrease-btn')) {
            handleUpdateQuantity(mapId, -1);
        } else if (button.classList.contains('inventory-delete-btn')) {
            // Get the name specifically from the item-name-value span
            const itemName = row.querySelector('.item-name-value')?.textContent || 'Item';
            handleRemoveItem(mapId, itemName.trim());
        } else if (button.classList.contains('inventory-details-btn')) {
            if (itemId && detailsModal) { // Check modal exists
                showItemDetails(itemId);
            } else if (!detailsModal) {
                console.error("Cannot show item details - details modal not found.");
            }
        }
    };
    inventoryContainer.addEventListener('click', window.handleInventoryTableClick);

    // --- Attach Listeners for Add Item Modal Filters (if modal elements exist) ---
    if (addModalSearchInput) {
        // Remove previous listener to avoid duplicates if init runs multiple times
        addModalSearchInput.removeEventListener('input', debouncedLoadItems);
        addModalSearchInput.addEventListener('input', debouncedLoadItems);
    }
    if (addModalTypeFilter) {
        // Remove previous listener
        addModalTypeFilter.removeEventListener('change', loadAvailableItems);
        addModalTypeFilter.addEventListener('change', loadAvailableItems);
    }

    // --- Attach Static Modal Close Button Listeners (only once per page load) ---
    if (!window.inventoryModalCloseListenersAttached) {
         document.querySelectorAll('.modal .close-modal, .modal .close-modal-button').forEach(btn => {
            const modal = btn.closest('.modal');
            // Only attach if it's one of *our* inventory modals
            if (modal && (modal.id === 'add-inventory-modal' || modal.id === 'item-details-modal')) {
                 btn.addEventListener('click', () => closeModal(modal));
            }
        });
         // Listener to close modals if clicking outside the content area
         window.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                 // Only close *our* inventory modals this way
                if (event.target.id === 'add-inventory-modal' || event.target.id === 'item-details-modal') {
                    closeModal(event.target);
                }
            }
        });
        window.inventoryModalCloseListenersAttached = true; // Set flag
        // console.log("Inventory JS Init: Attached static modal close listeners.");
    }


    // --- Final Steps ---
    checkIfInventoryEmpty(); // Update empty message display

    // --- Setup Drag and Drop --- 
    if (sheetDisplay && inventoryContainer) {
        setupDragAndDrop(sheetDisplay.querySelector('table.inventory-table'), inventoryContainer);
         console.log("Drag and drop listeners initialized.");
    } else {
        console.warn("Could not initialize drag and drop - table body or container missing.");
    }

    // console.log(`✅ Inventory System JS Initialized successfully for Sheet ID: ${sheetId}`);

}; // End of initializeInventorySystem function

// --- Initial Script Load Message ---
// console.log("Inventory System JS loaded. Call window.initializeInventorySystem(sheetId) to activate.");

// --- Example Call (Remove or comment out - should be called from sheets.php) ---
/*
document.addEventListener('DOMContentLoaded', () => {
    // Example: If sheet data was embedded or loaded synchronously
    const initialSheetId = document.getElementById('sheet-display')?.querySelector('.inventory-container')?.dataset.sheetId;
    if (initialSheetId) {
        window.initializeInventorySystem(initialSheetId);
    }
});
*/