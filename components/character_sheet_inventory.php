<?php
/**
 * Character Sheet Inventory Component
 * Displays and manages character inventory items
 */

// Get character inventory items if we have a character ID
$inventory_items = [];
$container_items = []; // Items organized by container
if (isset($character) && isset($character['id'])) {
    try {
        if (!isset($conn)) {
            require_once dirname(__DIR__) . '/config/db_connect.php';
        }
        
        // Get inventory items for this character
        $stmt = $conn->prepare("
            SELECT im.map_id, im.map_quantity, im.container_id, i.* 
            FROM inventory_map im 
            JOIN inventory_items i ON im.item_id = i.item_id 
            WHERE im.character_id = :character_id
            ORDER BY i.item_type, i.item_name
        ");
        $stmt->bindParam(':character_id', $character['id']);
        $stmt->execute();
        
        $inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize items by container for hierarchical display
        foreach ($inventory_items as $item) {
            $container_id = $item['container_id'] ?? null;
            
            if ($container_id === null) {
                // Root level items (not in a container)
                if (!isset($container_items['root'])) {
                    $container_items['root'] = [];
                }
                $container_items['root'][] = $item;
            } else {
                // Items inside a container
                if (!isset($container_items[$container_id])) {
                    $container_items[$container_id] = [];
                }
                $container_items[$container_id][] = $item;
            }
        }
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
    
    <div class="inventory-container" data-character-id="<?php echo isset($character['id']) ? $character['id'] : '0'; ?>">
        <?php if (empty($inventory_items)): ?>
        <div class="empty-inventory">
            <p>No items in inventory. Click the "+" button to add items.</p>
        </div>
        <?php else: ?>
        <div class="inventory-dropzone" data-container-id="root">
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
                    <?php 
                    // Display root level items (not in any container)
                    $rootItems = $container_items['root'] ?? [];
                    foreach ($rootItems as $item): 
                        $isContainer = ($item['item_type'] === 'Container');
                        $containerId = $item['map_id'];
                        $hasContents = isset($container_items[$containerId]) && !empty($container_items[$containerId]);
                    ?>
                    <tr class="inventory-item <?php echo $isContainer ? 'container-item' : ''; ?> <?php echo $hasContents ? 'has-contents' : ''; ?>" 
                        data-item-id="<?php echo $item['item_id']; ?>" 
                        data-map-id="<?php echo $item['map_id']; ?>"
                        data-item-type="<?php echo htmlspecialchars($item['item_type']); ?>"
                        data-container-id="<?php echo $item['container_id'] ?? 'root'; ?>"
                        draggable="true">
                        <td class="item-name">
                            <?php if ($isContainer): ?>
                            <button class="container-toggle" title="Toggle Container Contents">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <?php endif; ?>
                            <span class="item-name-text" title="<?php echo htmlspecialchars($item['item_description'] ?? ''); ?>">
                                <?php if ($isContainer): ?>
                                <i class="fas fa-box" style="margin-right: 5px;"></i>
                                <?php endif; ?>
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
                            <div class="item-action-buttons">
                                <button class="item-info-btn" title="View Details" data-item-id="<?php echo $item['item_id']; ?>">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <button class="item-use-btn" title="Use Item" data-item-id="<?php echo $item['item_id']; ?>" data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>">
                                    <i class="fas fa-hand-paper" style="color: #7289da;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php if ($isContainer): ?>
                    <!-- Container contents section - initially hidden -->
                    <tr class="container-contents" data-container-id="<?php echo $containerId; ?>">
                        <td colspan="4" class="container-contents-cell">
                            <div class="container-items-dropzone" data-container-id="<?php echo $containerId; ?>">
                                <?php if ($hasContents): ?>
                                <table class="container-items-table">
                                    <tbody>
                                        <?php foreach ($container_items[$containerId] as $containerItem): 
                                            $isNestedContainer = ($containerItem['item_type'] === 'Container');
                                            $nestedContainerId = $containerItem['map_id'];
                                            $hasNestedContents = isset($container_items[$nestedContainerId]) && !empty($container_items[$nestedContainerId]);
                                        ?>
                                        <tr class="inventory-item <?php echo $isNestedContainer ? 'container-item' : ''; ?> <?php echo $hasNestedContents ? 'has-contents' : ''; ?>" 
                                            data-item-id="<?php echo $containerItem['item_id']; ?>" 
                                            data-map-id="<?php echo $containerItem['map_id']; ?>"
                                            data-item-type="<?php echo htmlspecialchars($containerItem['item_type']); ?>"
                                            data-container-id="<?php echo $containerId; ?>"
                                            draggable="true">
                                            <td class="item-name">
                                                <?php if ($isNestedContainer): ?>
                                                <button class="container-toggle" title="Toggle Container Contents">
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                                <?php endif; ?>
                                                <span class="item-name-text" title="<?php echo htmlspecialchars($containerItem['item_description'] ?? ''); ?>">
                                                    <?php if ($isNestedContainer): ?>
                                                    <i class="fas fa-box" style="margin-right: 5px;"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($containerItem['item_name']); ?>
                                                </span>
                                            </td>
                                            <td class="item-type"><?php echo htmlspecialchars($containerItem['item_type']); ?></td>
                                            <td class="item-quantity">
                                                <div class="quantity-control">
                                                    <button class="quantity-btn decrease-btn" data-map-id="<?php echo $containerItem['map_id']; ?>">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <span class="quantity-value"><?php echo (int)$containerItem['map_quantity']; ?></span>
                                                    <button class="quantity-btn increase-btn" data-map-id="<?php echo $containerItem['map_id']; ?>">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="item-actions">
                                                <div class="item-action-buttons">
                                                    <button class="item-info-btn" title="View Details" data-item-id="<?php echo $containerItem['item_id']; ?>">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                    <button class="item-use-btn" title="Use Item" data-item-id="<?php echo $containerItem['item_id']; ?>" data-item-name="<?php echo htmlspecialchars($containerItem['item_name']); ?>">
                                                        <i class="fas fa-hand-paper" style="color: #7289da;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php if ($isNestedContainer): ?>
                                        <!-- Recursively render nested container contents -->
                                        <tr class="container-contents" data-container-id="<?php echo $nestedContainerId; ?>">
                                            <td colspan="4" class="container-contents-cell">
                                                <div class="container-items-dropzone" data-container-id="<?php echo $nestedContainerId; ?>">
                                                    <?php if ($hasNestedContents): ?>
                                                    <!-- Nested container contents would go here - we limit to 2 levels for simplicity -->
                                                    <div class="nested-container-notice">
                                                        <i class="fas fa-box-open"></i> Contains <?php echo count($container_items[$nestedContainerId]); ?> items
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="empty-container">
                                                        <i class="fas fa-box-open"></i> Empty container
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <div class="empty-container">
                                    <i class="fas fa-box-open"></i> Empty container
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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

<!-- Use Item Modal -->
<div id="use-item-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Use Item</h3>
        
        <div class="use-item-content">
            <p class="use-item-info">Your character is using <span id="use-item-name" class="item-highlight">-</span></p>
            
            <div class="form-group">
                <label for="use-item-notes">Add notes about how you're using this item:</label>
                <textarea id="use-item-notes" rows="3" placeholder="Optional notes about how you're using this item..."></textarea>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
            <button type="button" class="btn btn-discord" id="send-item-use-discord">
                <i class="fab fa-discord"></i> Share to Discord
            </button>
        </div>
    </div>
</div>