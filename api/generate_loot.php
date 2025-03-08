<?php
// Only show errors in development environment
if (getenv('ENVIRONMENT') == 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Enable CORS for specific domains
$allowed_origins = [
    'https://thesaltyparrot.com',
    'https://www.thesaltyparrot.com',
    'http://localhost:8000'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: https://thesaltyparrot.com");
}

header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

// Include database connection
require_once '../config/db_connect.php';

// Initialize response array
$response = array();

try {
    // Roll d100 for loot item
    $roll = rand(1, 100);
    
    // Check for roll value 100 (roll again twice)
    if ($roll == 100) {
        $response = array(
            "status" => "success",
            "loot" => array(
                array(
                    "roll" => $roll,
                    "name" => "Roll again twice",
                    "description" => "Roll again twice.",
                    "is_ancient_relic" => false,
                    "is_thing_of_importance" => false,
                    "category" => "special"
                )
            ),
            "extra_rolls" => array()
        );
        
        // Generate two more rolls
        for ($i = 0; $i < 2; $i++) {
            $extra_roll = rand(1, 99); // Avoid another 100
            $stmt = $conn->prepare("SELECT * FROM loot_generator_items WHERE roll_value = :roll");
            $stmt->execute(array(':roll' => $extra_roll));
            $extra_item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($extra_item) {
                $response["extra_rolls"][] = array(
                    "roll" => $extra_roll,
                    "name" => $extra_item['loot_name'],
                    "description" => $extra_item['loot_description'],
                    "is_ancient_relic" => (bool)$extra_item['is_ancient_relic'],
                    "is_thing_of_importance" => (bool)$extra_item['is_thing_of_importance'],
                    "category" => $extra_item['category']
                );
            }
        }
    } else {
        // Query the database for the rolled item
        $stmt = $conn->prepare("SELECT * FROM loot_generator_items WHERE roll_value = :roll");
        $stmt->execute(array(':roll' => $roll));
        $loot_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($loot_item) {
            // Special handling for random weapon (d10)
            if (strpos($loot_item['loot_description'], 'weapon (d10') !== false) {
                $weapon_roll = rand(1, 10);
                $weapon_stmt = $conn->prepare("SELECT loot_name FROM loot_generator_items WHERE category = 'weapon' AND roll_value = :roll");
                $weapon_stmt->execute(array(':roll' => $weapon_roll));
                $weapon = $weapon_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($weapon) {
                    $loot_item['loot_description'] .= " Result: " . $weapon['loot_name'];
                }
            }
            
            // Special handling for silver rolls
            if (strpos($loot_item['loot_name'], 'silver') !== false && strpos($loot_item['loot_name'], 'd') !== false) {
                preg_match('/(\d+)d(\d+)/', $loot_item['loot_name'], $matches);
                if (count($matches) == 3) {
                    $num_dice = $matches[1];
                    $dice_sides = $matches[2];
                    $silver_amount = 0;
                    
                    for ($i = 0; $i < $num_dice; $i++) {
                        $silver_amount += rand(1, $dice_sides);
                    }
                    
                    $loot_item['loot_description'] .= " ($silver_amount silver)";
                }
            }
            
            $response = array(
                "status" => "success",
                "loot" => array(
                    array(
                        "roll" => $roll,
                        "name" => $loot_item['loot_name'],
                        "description" => $loot_item['loot_description'],
                        "is_ancient_relic" => (bool)$loot_item['is_ancient_relic'],
                        "is_thing_of_importance" => (bool)$loot_item['is_thing_of_importance'],
                        "category" => $loot_item['category']
                    )
                )
            );
        } else {
            $response = array(
                "status" => "error",
                "message" => "No loot found for roll $roll."
            );
        }
    }
    
} catch(PDOException $e) {
    $response = array(
        "status" => "error",
        "message" => "Database Error: " . $e->getMessage()
    );
}

// Return JSON response
echo json_encode($response);
?>
