/**
 * Inventory Container System
 * Handles drag-and-drop functionality for inventory containers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const inventoryContainer = document.querySelector('.inventory-container');
    const dropzones = document.querySelectorAll('.inventory-dropzone');
    const draggableItems = document.querySelectorAll('.inventory-item[draggable="true"]');
    const containerToggles = document.querySelectorAll('.container-toggle');
    
    // Character data from PHP
    const characterData = window.character_data || {};
    const characterId = characterData.id;
    
    // Discord authentication status - set by PHP
    const isAuthenticated = window.discord_authenticated || false;
    
    // State
    let draggedItem = null;
    
    // Initialize
    initContainerSystem();
    
    function initContainerSystem() {
        // Only initialize if we have a character and auth
        if (!characterId || !isAuthenticated) {
            return;
        }
        
        // Set up drag and drop
        setupDragAndDrop();
    }
    
    function setupDragAndDrop() {
        // Add drag start event listeners to draggable items
        draggableItems.forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
        });
        
        // Add drop zone event listeners
        dropzones.forEach(dropzone => {
            dropzone.addEventListener('dragover', handleDragOver);
            dropzone.addEventListener('dragenter', handleDragEnter);
            dropzone.addEventListener('dragleave', handleDragLeave);
            dropzone.addEventListener('drop', handleDrop);
        });
    }
    
    function handleDragStart(e) {
        draggedItem = this;
        
        // Set drag data
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.dataset.mapId);
        
        // Add dragging class
        this.classList.add('dragging');
        
        // Create a custom drag image
        const dragImage = this.cloneNode(true);
        dragImage.style.width = this.offsetWidth + 'px';
        dragImage.style.opacity = '0.7';
        document.body.appendChild(dragImage);
        e.dataTransfer.setDragImage(dragImage, 0, 0);
        
        // Remove the temp element after a delay
        setTimeout(() => {
            document.body.removeChild(dragImage);
        }, 0);
    }
    
    function handleDragEnd(e) {
        this.classList.remove('dragging');
        draggedItem = null;
        
        // Remove drag hint classes from all dropzones
        document.querySelectorAll('.drag-over').forEach(el => {
            el.classList.remove('drag-over');
        });
    }
    
    function handleDragOver(e) {
        // Allow dropping
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        return false;
    }
    
    function handleDragEnter(e) {
        // Highlight drop zone
        this.classList.add('drag-over');
    }
    
    function handleDragLeave(e) {
        // Remove highlight
        this.classList.remove('drag-over');
    }
    
    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Remove highlight
        this.classList.remove('drag-over');
        
        if (!draggedItem) return;
        
        // Get item and container IDs
        const itemMapId = draggedItem.dataset.mapId;
        const itemType = draggedItem.dataset.itemType;
        const currentContainerId = draggedItem.dataset.containerId;
        const targetContainerId = this.dataset.containerId;
        
        // Prevent dropping container into itself
        if (itemType === 'Container' && itemMapId === targetContainerId) {
            showErrorNotification('A container cannot be placed inside itself.');
            return;
        }
        
        // Only proceed if actually changing containers
        if (currentContainerId === targetContainerId) {
            return;
        }
        
        // Move the item to the new container
        moveItemToContainer(itemMapId, targetContainerId);
    }
    
    function moveItemToContainer(itemMapId, containerId) {
        // Get base URL from window object or use default
        const baseUrl = window.base_url || './';
        const apiUrl = `${baseUrl}api/update_container.php`;
        
        // Prepare request data
        const requestData = {
            item_map_id: parseInt(itemMapId),
            character_id: characterId,
            container_map_id: containerId === 'root' ? null : parseInt(containerId)
        };
        
        // Show loading indicator
        showLoadingIndicator();
        
        // Send request to update container
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Refresh the page to show updated inventory structure
                // In a more advanced implementation, we could update the DOM without a refresh
                window.location.reload();
            } else {
                throw new Error(data.message || 'Failed to move item');
            }
        })
        .catch(error => {
            console.error('Error moving item to container:', error);
            hideLoadingIndicator();
            showErrorNotification('Error: ' + error.message);
        });
    }
    
    function showLoadingIndicator() {
        // Create or show a loading indicator
        let loadingEl = document.querySelector('.inventory-loading');
        
        if (!loadingEl) {
            loadingEl = document.createElement('div');
            loadingEl.className = 'inventory-loading';
            loadingEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating inventory...';
            
            if (inventoryContainer) {
                inventoryContainer.appendChild(loadingEl);
            } else {
                document.body.appendChild(loadingEl);
            }
        } else {
            loadingEl.style.display = 'block';
        }
    }
    
    function hideLoadingIndicator() {
        const loadingEl = document.querySelector('.inventory-loading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
    }
    
    function showErrorNotification(message) {
        // Create or update error notification
        let errorEl = document.querySelector('.inventory-error');
        
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'inventory-error';
            
            if (inventoryContainer) {
                inventoryContainer.appendChild(errorEl);
            } else {
                document.body.appendChild(errorEl);
            }
        }
        
        errorEl.textContent = message;
        errorEl.style.display = 'block';
        
        // Hide after 5 seconds
        setTimeout(() => {
            errorEl.style.display = 'none';
        }, 5000);
    }
});