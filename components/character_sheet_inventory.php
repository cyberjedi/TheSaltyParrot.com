<?php
/**
 * Character Sheet Inventory Component
 * Displays and manages character inventory items
 */

// Get character inventory items if we have a character ID
$inventory_items = [];
if (isset($character) && isset($character['id'])) {
    try {
        if (!isset($conn)) {
            require_once dirname(__DIR__) . '/config/db_connect.php';
        }
        
        // Get inventory items for this character
        $stmt = $conn->prepare("
            SELECT im.map_id, im.map_quantity, i.* 
            FROM inventory_map im 
            JOIN inventory_items i ON im.item_id = i.item_id 
            WHERE im.character_id = :character_id
            ORDER BY i.item_type, i.item_name
        ");
        $stmt->bindParam(':character_id', $character['id']);
        $stmt->execute();
        
        $inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error loading inventory: " . $e->getMessage());
    }
}

// Get item types for the filter
$item_types = [];
try {
    if (!isset($conn)) {
        require_once dirname(__DIR__) . '/config/db_connect.php';
    }
    
    $stmt = $conn->prepare("SELECT DISTINCT item_type FROM inventory_items ORDER BY item_type");
    $stmt->execute();
    $types_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($types_result as $type) {
        if (!empty($type['item_type'])) {
            $item_types[] = $type['item_type'];
        }
    }
} catch (PDOException $e) {
    error_log("Error loading item types: " . $e->getMessage());
}
?>

<!-- Character Inventory Section -->
<div class="character-inventory">
    <div class="section-header">
        <h3>Inventory</h3>
        <button id="add-inventory-item-btn" class="btn-icon" title="Add Item">
            <i class="fas fa-plus"></i>
        </button>
    </div>
    
    <div class="inventory-container">
        <?php if (empty($inventory_items)): ?>
        <div class="empty-inventory">
            <p>No items in inventory. Click the "+" button to add items.</p>
        </div>
        <?php else: ?>
        <table class="inventory-table">
            <thead>
                <tr>
                    <th class="item-name-col">Item</th>
                    <th class="item-type-col">Type</th>
                    <th class="item-qty-col">Qty</th>
                    <th class="item-actions-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventory_items as $item): ?>
                <tr class="inventory-item" data-item-id="<?php echo $item['item_id']; ?>" data-map-id="<?php echo $item['map_id']; ?>">
                    <td class="item-name">
                        <span class="item-name-text" title="<?php echo htmlspecialchars($item['item_description'] ?? ''); ?>">
                            <?php echo htmlspecialchars($item['item_name']); ?>
                        </span>
                    </td>
                    <td class="item-type"><?php echo htmlspecialchars($item['item_type']); ?></td>
                    <td class="item-quantity">
                        <div class="quantity-control">
                            <button class="quantity-btn decrease-btn" data-map-id="<?php echo $item['map_id']; ?>">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="quantity-value"><?php echo (int)$item['map_quantity']; ?></span>
                            <button class="quantity-btn increase-btn" data-map-id="<?php echo $item['map_id']; ?>">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </td>
                    <td class="item-actions">
                        <button class="item-info-btn" title="View Details" data-item-id="<?php echo $item['item_id']; ?>">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Inventory Item Modal -->
<div id="add-inventory-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Add Inventory Item</h3>
        
        <div class="item-search-controls">
            <div class="form-group">
                <label for="item-type-filter">Item Type:</label>
                <select id="item-type-filter">
                    <option value="">All Types</option>
                    <?php foreach ($item_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="item-search">Search:</label>
                <input type="text" id="item-search" placeholder="Search items...">
            </div>
        </div>
        
        <div class="available-items-container">
            <div class="available-items-list">
                <!-- Items will be loaded here via AJAX -->
                <div class="loading-items">Loading available items...</div>
            </div>
        </div>
    </div>
</div>

<!-- Item Details Modal -->
<div id="item-details-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 id="item-detail-name">Item Details</h3>
        
        <div class="item-details-content">
            <div class="item-detail-row">
                <span class="item-detail-label">Type:</span>
                <span id="item-detail-type" class="item-detail-value">-</span>
            </div>
            <div class="item-detail-row">
                <span class="item-detail-label">Source:</span>
                <span id="item-detail-source" class="item-detail-value">-</span>
            </div>
            <div class="item-description-container">
                <h4>Description</h4>
                <div id="item-detail-description" class="item-description">
                    No description available.
                </div>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="button" class="btn btn-secondary close-modal-btn">Close</button>
        </div>
    </div>
</div>

<!-- Remove Item Confirmation Modal -->
<div id="remove-item-confirm-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Remove Item</h3>
        
        <p>Are you sure you want to remove this item from your inventory?</p>
        <p class="remove-item-name">Item: <span id="remove-item-name">-</span></p>
        
        <div class="form-buttons">
            <button type="button" class="btn btn-danger" id="confirm-remove-item">Remove</button>
            <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
        </div>
    </div>
</div>