<?php
/**
 * Inventory Display Component
 * Displays and manages character inventory items directly on the sheet view.
 * 
 * Expects $sheet_id to be defined in the including scope.
 */

// Ensure sheet_id is provided
if (!isset($sheet_id) || empty($sheet_id)) {
    echo '<div class="alert alert-danger">Error: Sheet ID not provided for inventory display.</div>';
    return; // Stop execution if sheet_id is missing
}

// Get database connection
require_once dirname(__DIR__) . '/config/db_connect.php';

// Initialize variables
$inventory_items = [];
$container_items = []; // Items organized by container
$all_items_flat = []; // Keep a flat list for easier processing if needed

try {
    // Get inventory items for this character sheet
    $stmt = $conn->prepare("
        SELECT im.map_id, im.map_quantity, im.container_id, i.* 
        FROM inventory_map im 
        JOIN inventory_items i ON im.item_id = i.item_id 
        WHERE im.character_id = :sheet_id
        ORDER BY i.item_type, i.item_name
    ");
    $stmt->bindParam(':sheet_id', $sheet_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $all_items_flat = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize items by container for hierarchical display
    foreach ($all_items_flat as $item) {
        $container_id = $item['container_id'] ?? null;
        
        if ($container_id === null) {
            // Root level items (not in a container)
            if (!isset($container_items['root'])) {
                $container_items['root'] = [];
            }
            $container_items['root'][] = $item;
        } else {
            // Items inside a container
            // Ensure the container_id refers to an item also fetched for this character
            $containerExists = false;
            foreach($all_items_flat as $potentialContainer) {
                if ($potentialContainer['map_id'] == $container_id) {
                    $containerExists = true;
                    break;
                }
            }
            
            if ($containerExists) {
                 if (!isset($container_items[$container_id])) {
                     $container_items[$container_id] = [];
                 }
                 $container_items[$container_id][] = $item;
            } else {
                 // Item refers to a container not owned by this character? Treat as root.
                 if (!isset($container_items['root'])) {
                     $container_items['root'] = [];
                 }
                 $container_items['root'][] = $item;
                 // Log this potential inconsistency
                 error_log("Inventory inconsistency: Item (map_id: {$item['map_id']}) refers to container_id {$container_id} which is not found in character (sheet_id: {$sheet_id}) inventory.");
            }
        }
    }
} catch (PDOException $e) {
    error_log("Error loading inventory for sheet_id {$sheet_id}: " . $e->getMessage());
    echo '<div class="alert alert-danger">Error loading inventory data.</div>';
    // Avoid proceeding further if data loading failed
    return; 
}

// Get item types for the filter modal
$item_types = [];
try {
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
    // Non-critical error, modal filter might be incomplete
}
?>

<!-- Character Inventory Section -->
<div class="character-inventory">
    <!-- Add the controls div for the Add button -->
    <div class="inventory-controls">
        <button id="add-inventory-item-btn" class="btn btn-submit" title="Add Item">
            <i class="fas fa-plus"></i> Add Item
        </button>
    </div>

    <div class="inventory-container" data-sheet-id="<?php echo $sheet_id; ?>">
        <?php if (empty($all_items_flat)): ?>
        <div class="empty-inventory">
            <p>No items in inventory. Click the "+" button to add items.</p>
        </div>
        <?php else: ?>
        <div class="inventory-dropzone" data-container-id="root">
            <table class="inventory-table" style="width:100%; table-layout:fixed; border-collapse:collapse;">
                <colgroup>
                    <col style="width:55%;"> <!-- Name + Tag -->
                    <col style="width:25%;"> <!-- Qty Controls -->
                    <col style="width:20%;"> <!-- Actions -->
                </colgroup>
                <thead>
                    <tr>
                        <th class="item-name-col" style="text-align:left;">Item</th>
                        <th class="item-qty-col" style="text-align:center;">Qty</th>
                        <th class="item-actions-col" style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="inventory-table-body">
                    <?php 
                    // Function to render an item row recursively for containers
                    function renderInventoryItemRow($item, $containerLevel = 0, $parent_container_id = 'root', $all_container_items = []) {
                        global $sheet_id; // Make sheet_id accessible
                        $isContainer = ($item['item_type'] === 'Container');
                        $isSource = ($item['item_type'] === 'Source'); // Check if it's a Source type
                        $canContain = $isContainer || $isSource; // Can contain if Container or Source
                        $mapId = $item['map_id']; // This is the unique ID in the inventory_map table
                        $itemId = $item['item_id']; // This is the ID from the inventory_items table
                        $hasContents = isset($all_container_items[$mapId]) && !empty($all_container_items[$mapId]);
                        $indentPixels = $containerLevel * 30; // Increased indentation to 30px per level
                        $indentStyle = ($containerLevel > 0) ? "style='padding-left:{$indentPixels}px;'" : "";
                        // Add 'droppable-container' class if it can contain items
                        $rowClass = "inventory-item" . ($canContain ? ' droppable-container' : '') . ($hasContents ? ' has-contents' : '') . " container-level-" . $containerLevel;
                        $itemType = htmlspecialchars($item['item_type']);
                        
                        // Determine tag color class based on type
                        $tagColorClass = 'item-tag-default'; // Default color
                        if ($itemType === 'Relic') {
                            $tagColorClass = 'item-tag-magic';
                        }
                        // Add more conditions for other types/colors here
                    ?>
                    <tr class="<?php echo $rowClass; ?>" 
                        data-item-id="<?php echo $itemId; ?>" 
                        data-map-id="<?php echo $mapId; ?>"
                        data-item-type="<?php echo $itemType; ?>"
                        data-container-id="<?php echo $parent_container_id; ?>"
                        data-can-contain="<?php echo $canContain ? 'true' : 'false'; ?>"
                        draggable="true">
                        
                        <!-- Item Name (with Type Tag) -->
                        <td class="item-name">
                            <span class="item-name-text" <?php echo $indentStyle; ?> title="<?php echo htmlspecialchars($item['item_description'] ?? ''); ?>">
                                <?php if ($isContainer): ?>
                                    <i class="fas fa-box" style="margin-right: 5px;"></i>
                                <?php elseif ($isSource): ?>
                                    <i class="fas fa-database" style="margin-right: 5px;"></i> <!-- Example icon for Source -->
                                <?php endif; ?>
                                <span class="item-name-value"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                <?php if (!empty($itemType)): ?>
                                    <span class="item-type-tag <?php echo $tagColorClass; ?>"><?php echo $itemType; ?></span>
                                <?php endif; ?>
                            </span>
                        </td>

                        <!-- Quantity Controls -->
                        <td class="item-quantity">
                            <?php if (!$isContainer): /* Don't show qty controls for containers */ ?>
                            <div class="quantity-control">
                                <button class="quantity-btn decrease-btn" data-map-id="<?php echo $mapId; ?>" title="Decrease Quantity">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="quantity-value"><?php echo (int)$item['map_quantity']; ?></span>
                                <button class="quantity-btn increase-btn" data-map-id="<?php echo $mapId; ?>" title="Increase Quantity">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </td>

                        <!-- Action Buttons -->
                        <td class="item-actions">
                            <div class="item-action-buttons">
                                <button class="inventory-action-btn inventory-details-btn" title="View Details" data-item-id="<?php echo $itemId; ?>">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <?php if ($isContainer): ?>
                                     <?php if (!$hasContents): ?>
                                     <!-- Only show trash for EMPTY containers -->
                                     <button class="inventory-action-btn inventory-delete-btn" title="Remove Empty Container" 
                                             data-map-id="<?php echo $mapId; ?>"
                                             data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>">
                                         <i class="fas fa-trash-alt"></i>
                                     </button>
                                     <?php else: ?>
                                     <!-- Show a "locked" trash can for containers with items -->
                                     <button class="container-locked-btn" title="Container must be empty before removal" disabled>
                                         <i class="fas fa-lock"></i>
                                     </button>
                                     <?php endif; ?>
                                <?php else: ?>
                                <!-- Standard item delete button -->
                                <button class="inventory-action-btn inventory-delete-btn" title="Remove Item" 
                                        data-map-id="<?php echo $mapId; ?>"
                                        data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                                <!-- Use button can be added back if needed -->
                                <!-- 
                                <button class="item-use-btn" title="Use Item" data-item-id="<?php echo $itemId; ?>" data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>">
                                    <i class="fab fa-discord" style="color: #7289da;"></i>
                                </button> 
                                -->
                            </div>
                        </td>
                    </tr>
                    <?php
                        // Render container contents recursively if this is a container and has contents
                        if ($isContainer && $hasContents) {
                            foreach ($all_container_items[$mapId] as $containerItem) {
                                renderInventoryItemRow($containerItem, $containerLevel + 1, $mapId, $all_container_items);
                            }
                        }
                    } // End of renderInventoryItemRow function
                    
                    // Display root level items (not in any container)
                    $rootItems = $container_items['root'] ?? [];
                    foreach ($rootItems as $item) {
                        renderInventoryItemRow($item, 0, 'root', $container_items);
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

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
                    <?php foreach ($item_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
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

<?php
// Unset variables to avoid potential conflicts if included multiple times (though unlikely)
unset($inventory_items, $container_items, $all_items_flat, $item_types, $stmt, $types_result); 
?> 