<?php
// test_switcher.php - A minimal character switcher test

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Hardcoded characters
$user_characters = [
    [
        'id' => 1,
        'user_id' => 1,
        'name' => 'Test Pirate',
        'image_path' => 'uploads/characters/character_1741469717_67ccb815a80be.jpg',
        'strength' => 3,
        'agility' => -2,
        'presence' => 1,
        'toughness' => 0,
        'spirit' => 2
    ],
    [
        'id' => 2,
        'user_id' => 1,
        'name' => 'Test Pirate 2',
        'image_path' => 'uploads/characters/character_1741469717_67ccb815a80be.jpg',
        'strength' => 1,
        'agility' => 2,
        'presence' => 3,
        'toughness' => -1,
        'spirit' => -1
    ],
    [
        'id' => 3,
        'user_id' => 1,
        'name' => 'New Pirate 3',
        'image_path' => 'uploads/characters/character_1741469717_67ccb815a80be.jpg',
        'strength' => 1,
        'agility' => 0,
        'presence' => 1,
        'toughness' => 0,
        'spirit' => 0
    ],
    [
        'id' => 4,
        'user_id' => 1,
        'name' => 'New Pirate',
        'image_path' => 'uploads/characters/character_1741469717_67ccb815a80be.jpg',
        'strength' => 0,
        'agility' => 0,
        'presence' => 0,
        'toughness' => 0,
        'spirit' => 0
    ]
];

// Set a character if one is selected
$character = null;
if (isset($_GET['id'])) {
    $char_id = (int)$_GET['id'];
    
    // Find the character with this ID
    foreach ($user_characters as $char) {
        if ($char['id'] == $char_id) {
            $character = $char;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Switcher Test</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .container { background-color: #f5f5f5; border-radius: 8px; padding: 20px; }
        .modal { display: block; margin-bottom: 30px; background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
        .character-list { margin: 20px 0; }
        .character-list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid #e0e0e0; margin-bottom: 10px; border-radius: 8px; background-color: #fff; }
        .character-list-item:hover { border-color: #805d2c; }
        .character-list-item.active { border-color: #805d2c; background-color: #fcf7ee; }
        .character-list-info { display: flex; align-items: center; flex: 1; }
        .character-list-avatar { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; border: 2px solid #805d2c; margin-right: 15px; flex-shrink: 0; }
        .character-list-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .character-list-details { flex: 1; }
        .character-name { font-weight: bold; font-size: 1.1rem; color: #1a2639; display: block; }
        .character-user-id { font-size: 0.8rem; color: #666; display: block; margin-top: 2px; }
        .character-list-actions { margin-left: 10px; }
        .current-badge { display: inline-block; background-color: #e0f0e0; color: #2c8527; font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #c0e0c0; }
        .btn { padding: 8px 15px; border-radius: 4px; cursor: pointer; border: none; font-weight: bold; }
        .btn-primary { background-color: #805d2c; color: white; }
        .btn-secondary { background-color: #e0e0e0; color: #333; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; }
        h1, h2, h3 { color: #805d2c; }
        a { color: #805d2c; text-decoration: none; }
        a:hover { text-decoration: underline; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Character Switcher Test</h1>
    <p>This is a simplified test page for the character switcher functionality.</p>
    
    <div class="container">
        <!-- Character display if selected -->
        <?php if ($character): ?>
            <div style="margin-bottom: 30px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; background-color: white;">
                <h2>Selected Character</h2>
                <div style="display: flex; align-items: flex-start;">
                    <div style="margin-right: 20px;">
                        <img src="<?php echo htmlspecialchars($character['image_path']); ?>" alt="Character Portrait" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #805d2c;" onerror="this.src='assets/TSP_default_character.jpg'">
                    </div>
                    <div>
                        <h3><?php echo htmlspecialchars($character['name']); ?></h3>
                        <p><strong>ID:</strong> <?php echo $character['id']; ?></p>
                        <p><strong>User ID:</strong> <?php echo $character['user_id']; ?></p>
                        <p><strong>Stats:</strong> STR: <?php echo $character['strength']; ?>, 
                           AGI: <?php echo $character['agility']; ?>, 
                           PRE: <?php echo $character['presence']; ?>, 
                           TOU: <?php echo $character['toughness']; ?>, 
                           SPI: <?php echo $character['spirit']; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Character Switcher Modal -->
        <div class="modal">
            <h2>Character Selection</h2>
            
            <?php if (count($user_characters) > 0): ?>
                <div class="character-list">
                    <?php foreach ($user_characters as $char): ?>
                        <div class="character-list-item <?php echo ($character && $character['id'] == $char['id']) ? 'active' : ''; ?>">
                            <div class="character-list-info">
                                <?php
                                // Get character image
                                $charImage = !empty($char['image_path']) ? htmlspecialchars($char['image_path']) : 'assets/TSP_default_character.jpg';
                                ?>
                                <div class="character-list-avatar">
                                    <img src="<?php echo $charImage; ?>" alt="Character Portrait" onerror="this.src='assets/TSP_default_character.jpg'">
                                </div>
                                <div class="character-list-details">
                                    <span class="character-name"><?php echo htmlspecialchars($char['name']); ?></span>
                                    <span class="character-user-id">(User ID: <?php echo isset($char['user_id']) ? $char['user_id'] : 'unknown'; ?>)</span>
                                    
                                    <span style="font-size: 0.7rem; color: #999;">
                                        ID: <?php echo $char['id']; ?>, 
                                        STR: <?php echo $char['strength']; ?>
                                    </span>
                                    
                                    <?php if ($character && $character['id'] == $char['id']): ?>
                                        <span class="current-badge">Current</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="character-list-actions">
                                <a href="test_switcher.php?id=<?php echo $char['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-user"></i> Select
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 30px 0; color: #666;">
                    <p>You don't have any characters yet.</p>
                    <p>Create your first character to get started!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="test_characters.php" class="btn btn-secondary">Back to Test Page</a>
            <a href="character_sheet.php" class="btn btn-primary">Go to Real Character Sheet</a>
        </div>
    </div>
    
    <div style="margin-top: 30px;">
        <h3>Technical Details</h3>
        <p>This test page uses hardcoded character data to ensure it works correctly.</p>
        <pre>
// Debug Info:
Session Data: <?php echo json_encode($_SESSION); ?>

// User Characters:
<?php echo json_encode($user_characters, JSON_PRETTY_PRINT); ?>
        </pre>
    </div>
</body>
</html>