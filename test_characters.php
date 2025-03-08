<?php
// test_characters.php - A simple test file to list characters with minimal code

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

echo "<h1>Character Test Page</h1>";

// Hardcoded characters (as a fallback)
$hardcoded_characters = [
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

// Simple connection (using variables directly rather than including files)
try {
    // Directly use the known database credentials
    $host = 'localhost';
    $dbname = 'theshfmb_SPDB';
    $username = 'theshfmb_spuser';
    $password = 'Password1!';  // This is just a placeholder - you should replace with the actual password
    
    echo "<div style='margin: 20px 0; padding: 10px; border: 1px solid #ccc;'>";
    echo "<h3>Testing database connection...</h3>";
    
    // Create database connection
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $username,
        $password
    );
    
    // Set error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>Database connection successful!</p>";
    
    // Try a simple query to get all characters
    $stmt = $conn->query("SELECT * FROM characters");
    $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($characters) . " characters in the database.</p>";
    
    // Display characters from database
    if (count($characters) > 0) {
        echo "<h3>Characters from Database</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>User ID</th><th>Strength</th><th>Agility</th><th>Presence</th><th>Toughness</th><th>Spirit</th></tr>";
        
        foreach ($characters as $char) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($char['id']) . "</td>";
            echo "<td>" . htmlspecialchars($char['name']) . "</td>";
            echo "<td>" . htmlspecialchars($char['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($char['strength']) . "</td>";
            echo "<td>" . htmlspecialchars($char['agility']) . "</td>";
            echo "<td>" . htmlspecialchars($char['presence']) . "</td>";
            echo "<td>" . htmlspecialchars($char['toughness']) . "</td>";
            echo "<td>" . htmlspecialchars($char['spirit']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='margin: 20px 0; padding: 10px; border: 1px solid red; color: red;'>";
    echo "<h3>Database connection error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

// Always display the hardcoded characters
echo "<div style='margin: 20px 0; padding: 10px; border: 1px solid #ccc;'>";
echo "<h3>Hardcoded Characters</h3>";
echo "<p>These are the fallback characters that should always be displayed.</p>";

echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Name</th><th>User ID</th><th>Strength</th><th>Agility</th><th>Presence</th><th>Toughness</th><th>Spirit</th></tr>";

foreach ($hardcoded_characters as $char) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($char['id']) . "</td>";
    echo "<td>" . htmlspecialchars($char['name']) . "</td>";
    echo "<td>" . htmlspecialchars($char['user_id']) . "</td>";
    echo "<td>" . htmlspecialchars($char['strength']) . "</td>";
    echo "<td>" . htmlspecialchars($char['agility']) . "</td>";
    echo "<td>" . htmlspecialchars($char['presence']) . "</td>";
    echo "<td>" . htmlspecialchars($char['toughness']) . "</td>";
    echo "<td>" . htmlspecialchars($char['spirit']) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Create a button to test loading character_sheet.php with hardcoded flag
echo "<div style='margin: 20px 0;'>";
echo "<h3>Test Character Switch</h3>";
echo "<form action='test_switcher.php' method='get'>";
echo "<button type='submit'>Test Character Switcher</button>";
echo "</form>";
echo "</div>";
?>