/**
 * Character Sheet Inventory JavaScript
 * Handles inventory interactions and API calls
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const addInventoryBtn = document.getElementById('add-inventory-item-btn');
    const addInventoryModal = document.getElementById('add-inventory-modal');
    const itemDetailsModal = document.getElementById('item-details-modal');
    const removeItemModal = document.getElementById('remove-item-confirm-modal');
    const useItemModal = document.getElementById('use-item-modal');
    const typeFilter = document.getElementById('item-type-filter');
    const searchInput = document.getElementById('item-search');
    const availableItemsList = document.querySelector('.available-items-list');
    const closeBtns = document.querySelectorAll('.close-modal');
    const closeFormBtns = document.querySelectorAll('.close-modal-btn');
    const sendItemUseDiscordBtn = document.getElementById('send-item-use-discord');
    
    // State
    let availableItems = [];
    let currentItemId = null;
    let currentMapId = null;
    let currentItemName = null;
    
    // Character data from PHP
    const characterData = window.character_data || {};
    const characterId = characterData.id;
    
    // Discord authentication status - set by PHP
    const isAuthenticated = window.discord_authenticated || false;
    
    // Initialize
    init();
    
    // Functions
    function init() {
        // Set up event listeners
        if (addInventoryBtn) {
            addInventoryBtn.addEventListener('click', openAddItemModal);
        }
        
        // Close modals when X is clicked
        if (closeBtns) {
            closeBtns.forEach(function(btn) {
                btn.addEventListener('click', closeAllModals);
            });
        }
        
        // Close modals when Cancel button is clicked
        if (closeFormBtns) {
            closeFormBtns.forEach(function(btn) {
                btn.addEventListener('click', closeAllModals);
            });
        }
        
        // Close modals when clicking outside of them
        window.addEventListener('click', function(event) {
            if (event.target === addInventoryModal || 
                event.target === itemDetailsModal || 
                event.target === removeItemModal ||
                event.target === useItemModal) {
                closeAllModals();
            }
        });
        
        // Filter and search
        if (typeFilter) {
            typeFilter.addEventListener('change', filterItems);
        }
        
        if (searchInput) {
            searchInput.addEventListener('input', filterItems);
        }
        
        // Quantity controls
        setupQuantityControls();
        
        // Item info buttons
        setupItemInfoButtons();
        
        // Item use buttons
        setupItemUseButtons();
        
        // Confirm remove item button
        const confirmRemoveBtn = document.getElementById('confirm-remove-item');
        if (confirmRemoveBtn) {
            confirmRemoveBtn.addEventListener('click', removeInventoryItem);
        }
        
        // Send to Discord button for item use
        if (sendItemUseDiscordBtn) {
            sendItemUseDiscordBtn.addEventListener('click', sendItemUseToDiscord);
        }
    }
    
    function setupQuantityControls() {
        // Increase buttons
        const increaseButtons = document.querySelectorAll('.increase-btn');
        increaseButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const mapId = this.getAttribute('data-map-id');
                updateItemQuantity(mapId, 1);
            });
        });
        
        // Decrease buttons
        const decreaseButtons = document.querySelectorAll('.decrease-btn');
        decreaseButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const mapId = this.getAttribute('data-map-id');
                const row = this.closest('.inventory-item');
                const quantityEl = row.querySelector('.quantity-value');
                const currentQty = parseInt(quantityEl.textContent);
                
                if (currentQty <= 1) {
                    // Show confirmation for removing last item
                    showRemoveConfirmation(mapId, row);
                } else {
                    updateItemQuantity(mapId, -1);
                }
            });
        });
    }
    
    function setupItemInfoButtons() {
        const infoButtons = document.querySelectorAll('.item-info-btn');
        infoButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                showItemDetails(itemId);
            });
        });
    }
    
    function setupItemUseButtons() {
        const useButtons = document.querySelectorAll('.item-use-btn');
        useButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                const itemName = this.getAttribute('data-item-name');
                showUseItemModal(itemId, itemName);
            });
        });
    }
    
    function showUseItemModal(itemId, itemName) {
        if (!isAuthenticated) {
            alert('You must connect with Discord to use items and share to Discord.');
            return;
        }
        
        // Update current item ID and name
        currentItemId = itemId;
        currentItemName = itemName;
        
        // Update the modal with item details
        document.getElementById('use-item-name').textContent = itemName;
        
        // Clear any previous notes
        document.getElementById('use-item-notes').value = '';
        
        // Show the modal
        useItemModal.style.display = 'block';
    }
    
    function openAddItemModal() {
        if (!isAuthenticated) {
            alert('You must connect with Discord to manage inventory.');
            return;
        }
        
        if (!characterId) {
            alert('No character selected.');
            return;
        }
        
        // Reset filters
        if (typeFilter) typeFilter.value = '';
        if (searchInput) searchInput.value = '';
        
        // Show loading state
        if (availableItemsList) {
            availableItemsList.innerHTML = '<div class="loading-items">Loading available items...</div>';
        }
        
        // Show modal
        if (addInventoryModal) {
            addInventoryModal.style.display = 'block';
        }
        
        // Load available items
        loadAvailableItems();
    }
    
    function closeAllModals() {
        if (addInventoryModal) addInventoryModal.style.display = 'none';
        if (itemDetailsModal) itemDetailsModal.style.display = 'none';
        if (removeItemModal) removeItemModal.style.display = 'none';
        if (useItemModal) useItemModal.style.display = 'none';
    }
    
    function loadAvailableItems() {
        // Get base URL from window object or use default
        const baseUrl = window.base_url || './';
        const apiUrl = `${baseUrl}api/get_available_items.php?character_id=${characterId}`;
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    availableItems = data.items || [];
                    renderAvailableItems(availableItems);
                } else {
                    throw new Error(data.message || 'Failed to load items');
                }
            })
            .catch(error => {
                console.error('Error loading available items:', error);
                if (availableItemsList) {
                    availableItemsList.innerHTML = `<div class="error-loading">Error loading items: ${error.message}</div>`;
                }
            });
    }
    
    function renderAvailableItems(items) {
        if (!availableItemsList) return;
        
        if (items.length === 0) {
            availableItemsList.innerHTML = '<div class="empty-items">No items available.</div>';
            return;
        }
        
        // Clear previous items
        availableItemsList.innerHTML = '';
        
        // Add each item
        items.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.className = 'available-item';
            itemElement.setAttribute('data-item-id', item.item_id);
            itemElement.setAttribute('data-item-type', item.item_type || '');
            
            const name = item.item_name || 'Unnamed Item';
            const type = item.item_type || 'Unknown Type';
            const desc = item.item_description || 'No description available.';
            
            itemElement.innerHTML = `
                <div class="available-item-name">${name}</div>
                <div class="available-item-type">${type}</div>
                <div class="available-item-desc">${desc}</div>
            `;
            
            // Add click handler to add item to inventory
            itemElement.addEventListener('click', function() {
                addItemToInventory(item.item_id);
            });
            
            availableItemsList.appendChild(itemElement);
        });
    }
    
    function filterItems() {
        if (!availableItems.length) return;
        
        const typeValue = typeFilter ? typeFilter.value.toLowerCase() : '';
        const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
        
        // Filter items based on type and search text
        const filtered = availableItems.filter(item => {
            // Match type if a type filter is selected
            const typeMatch = !typeValue || (item.item_type && item.item_type.toLowerCase() === typeValue);
            
            // Match search text in name or description
            const searchMatch = !searchValue || 
                (item.item_name && item.item_name.toLowerCase().includes(searchValue)) ||
                (item.item_description && item.item_description.toLowerCase().includes(searchValue));
            
            return typeMatch && searchMatch;
        });
        
        // Render the filtered items
        renderAvailableItems(filtered);
    }
    
    function addItemToInventory(itemId) {
        if (!characterId || !itemId) {
            alert('Missing character ID or item ID');
            return;
        }
        
        // Get base URL from window object or use default
        const baseUrl = window.base_url || './';
        const apiUrl = `${baseUrl}api/add_inventory_item.php`;
        
        // Show loading state
        if (availableItemsList) {
            availableItemsList.innerHTML = '<div class="loading-items">Adding item to inventory...</div>';
        }
        
        // Send request to add item
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                character_id: characterId,
                item_id: itemId
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Close modal and reload page to show updated inventory
                closeAllModals();
                window.location.reload();
            } else {
                throw new Error(data.message || 'Failed to add item');
            }
        })
        .catch(error => {
            console.error('Error adding item to inventory:', error);
            if (availableItemsList) {
                availableItemsList.innerHTML = `<div class="error-loading">Error: ${error.message}</div>`;
                
                // Add button to try again
                const retryBtn = document.createElement('button');
                retryBtn.className = 'btn btn-primary';
                retryBtn.textContent = 'Try Again';
                retryBtn.addEventListener('click', loadAvailableItems);
                
                availableItemsList.appendChild(retryBtn);
            }
        });
    }
    
    function updateItemQuantity(mapId, change) {
        if (!mapId) {
            console.error('Missing map ID for quantity update');
            return;
        }
        
        // Get base URL from window object or use default
        const baseUrl = window.base_url || './';
        const apiUrl = `${baseUrl}api/update_item_quantity.php`;
        
        // Find the quantity element to update
        const row = document.querySelector(`.inventory-item[data-map-id="${mapId}"]`);
        const quantityEl = row ? row.querySelector('.quantity-value') : null;
        
        if (!quantityEl) {
            console.error('Could not find quantity element');
            return;
        }
        
        // Disable quantity buttons during update
        const buttons = row.querySelectorAll('.quantity-btn');
        buttons.forEach(btn => btn.disabled = true);
        
        // Get current quantity
        const currentQty = parseInt(quantityEl.textContent);
        
        // Send request to update quantity
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                map_id: mapId,
                change: change
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Update displayed quantity
                const newQty = currentQty + change;
                quantityEl.textContent = newQty;
                
                // Re-enable buttons
                buttons.forEach(btn => btn.disabled = false);
            } else {
                throw new Error(data.message || 'Failed to update quantity');
            }
        })
        .catch(error => {
            console.error('Error updating quantity:', error);
            alert(`Error updating quantity: ${error.message}`);
            
            // Re-enable buttons
            buttons.forEach(btn => btn.disabled = false);
        });
    }
    
    function showItemDetails(itemId) {
        if (!itemId) return;
        
        // Get base URL from window object or use default
        const baseUrl = window.base_url || './';
        const apiUrl = `${baseUrl}api/get_item_details.php?item_id=${itemId}`;
        
        // Show loading state in modal
        document.getElementById('item-detail-name').textContent = 'Loading...';
        document.getElementById('item-detail-type').textContent = '-';
        document.getElementById('item-detail-source').textContent = '-';
        document.getElementById('item-detail-description').textContent = 'Loading item details...';
        
        // Show modal
        itemDetailsModal.style.display = 'block';
        
        // Fetch item details
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' && data.item) {
                    const item = data.item;
                    
                    // Update modal with item details
                    document.getElementById('item-detail-name').textContent = item.item_name || 'Unnamed Item';
                    document.getElementById('item-detail-type').textContent = item.item_type || 'Unknown';
                    document.getElementById('item-detail-source').textContent = item.item_source || 'Unknown';
                    document.getElementById('item-detail-description').textContent = 
                        item.item_description || 'No description available.';
                } else {
                    throw new Error(data.message || 'Failed to load item details');
                }
            })
            .catch(error => {
                console.error('Error loading item details:', error);
                document.getElementById('item-detail-name').textContent = 'Error';
                document.getElementById('item-detail-description').textContent = 
                    `Error loading item details: ${error.message}`;
            });
    }
    
    function showRemoveConfirmation(mapId, row) {
        if (!mapId || !row) return;
        
        // Store IDs for later use
        currentMapId = mapId;
        
        // Get item name
        const nameElement = row.querySelector('.item-name-text');
        const itemName = nameElement ? nameElement.textContent.trim() : 'this item';
        
        // Update modal
        document.getElementById('remove-item-name').textContent = itemName;
        
        // Show modal
        removeItemModal.style.display = 'block';
    }
    
    function removeInventoryItem() {
        if (!currentMapId) {
            console.error('No map ID set for removal');
            return;
        }
        
        // Get base URL from window object or use default
        const baseUrl = window.base_url || './';
        const apiUrl = `${baseUrl}api/remove_inventory_item.php`;
        
        // Disable confirm button during deletion
        const confirmBtn = document.getElementById('confirm-remove-item');
        if (confirmBtn) confirmBtn.disabled = true;
        
        // Send request to remove item
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                map_id: currentMapId
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Close modal and reload page to show updated inventory
                closeAllModals();
                window.location.reload();
            } else {
                throw new Error(data.message || 'Failed to remove item');
            }
        })
        .catch(error => {
            console.error('Error removing item:', error);
            alert(`Error removing item: ${error.message}`);
            
            // Re-enable button
            if (confirmBtn) confirmBtn.disabled = false;
        });
    }
    
    function sendItemUseToDiscord() {
        if (!isAuthenticated) {
            alert('You must connect with Discord to share to a webhook.');
            return;
        }
        
        if (!currentItemId || !currentItemName) {
            console.error('Missing item information for Discord sharing');
            return;
        }
        
        // Get user notes if any
        const notes = document.getElementById('use-item-notes').value.trim();
        
        // Show loading indicator on the button
        const originalButtonContent = sendItemUseDiscordBtn.innerHTML;
        sendItemUseDiscordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        sendItemUseDiscordBtn.disabled = true;
        
        // Get base URL from window object or use default
        const baseUrl = window.base_url || './';
        const webhookUrl = baseUrl + 'discord/webhooks.php?action=get_default_webhook&format=json';
        
        // Fetch default webhook from the server
        fetch(webhookUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Handle error cases
                if (data.status === 'error' || !data.webhook) {
                    throw new Error(data.message || 'No default webhook found');
                }
                
                // Get the default webhook ID
                const webhookId = data.webhook.id;
                
                // Format content for Discord
                const itemContent = `
                    <div class="item-use">
                        <h3>${characterData.name} uses an item</h3>
                        <div class="item-use-details">
                            <p><strong>Item:</strong> ${currentItemName}</p>
                            ${notes ? `<p><strong>Notes:</strong> ${notes}</p>` : ''}
                        </div>
                    </div>
                `;
                
                // Get send webhook URL
                const sendWebhookUrl = baseUrl + 'discord/send_to_webhook.php';
                
                // Send content to webhook
                return fetch(sendWebhookUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        webhook_id: webhookId,
                        content: itemContent,
                        generator_type: 'item_use'
                    })
                });
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    // Show success feedback
                    sendItemUseDiscordBtn.innerHTML = '<i class="fas fa-check"></i> Sent!';
                    
                    setTimeout(() => {
                        closeAllModals();
                        sendItemUseDiscordBtn.innerHTML = originalButtonContent;
                        sendItemUseDiscordBtn.disabled = false;
                    }, 1500);
                } else {
                    // Show error
                    throw new Error(result.message || 'Failed to send to Discord');
                }
            })
            .catch(error => {
                console.error('Error sending to Discord:', error);
                sendItemUseDiscordBtn.innerHTML = originalButtonContent;
                sendItemUseDiscordBtn.disabled = false;
                alert('Error sending to Discord: ' + error.message);
            });
    }
});