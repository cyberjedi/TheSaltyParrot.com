<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

// Include database connection
require_once '../config/db_connect.php';

// Initialize response array
$response = array();

try {
    // Generate random ship data
    // 1. Get random ship name
    $stmt = $conn->prepare("SELECT name FROM ship_names ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $ship_name = $stmt->fetch(PDO::FETCH_ASSOC)['name'];
    
    // 2. Get random vessel class
    $stmt = $conn->prepare("SELECT name, cargo_score FROM vessel_classes ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $vessel_class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 3. Get random armament
    $stmt = $conn->prepare("SELECT description FROM armaments ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $armament = $stmt->fetch(PDO::FETCH_ASSOC)['description'];
    
    // 4. Get random crew quantity
    $stmt = $conn->prepare("SELECT description FROM crew_quantities ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $crew_quantity = $stmt->fetch(PDO::FETCH_ASSOC)['description'];
    
    // 5. Get random crew quality
    $stmt = $conn->prepare("SELECT description FROM crew_qualities ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $crew_quality = $stmt->fetch(PDO::FETCH_ASSOC)['description'];
    
    // 6. Generate cargo based on vessel class cargo score
    $cargo = array();
    $cargo_score = $vessel_class['cargo_score'];
    
    if ($cargo_score > 0) {
        for ($i = 0; $i < $cargo_score; $i++) {
            $stmt = $conn->prepare("SELECT description FROM mundane_cargo ORDER BY RAND() LIMIT 1");
            $stmt->execute();
            $cargo_item = $stmt->fetch(PDO::FETCH_ASSOC)['description'];
            
            // Check if cargo is "special cargo"
            if ($cargo_item == "special cargo") {
                $stmt = $conn->prepare("SELECT description FROM special_cargo ORDER BY RAND() LIMIT 1");
                $stmt->execute();
                $cargo_item = $stmt->fetch(PDO::FETCH_ASSOC)['description'];
                
                // Handle special cases for dynamic cargo content
                if (strpos($cargo_item, 'd10 locked chests') !== false) {
                    $num_chests = rand(1, 10);
                    $total_value = 0;
                    for ($j = 0; $j < $num_chests; $j++) {
                        $total_value += rand(2, 16) * 100;
                    }
                    $cargo_item = "$num_chests locked chests, {$total_value}s (total)";
                }
                else if (strpos($cargo_item, 'd20 crates of ASH') !== false) {
                    $num_crates = rand(1, 20);
                    $cargo_item = "$num_crates crates of ASH";
                }
                else if (strpos($cargo_item, 'd4 Arcane Rituals') !== false) {
                    $num_rituals = rand(1, 4);
                    $cargo_item = "a sorcerer with a tome of $num_rituals Arcane Rituals";
                }
            }
            
            $cargo[] = $cargo_item;
        }
    } else {
        $cargo[] = "None";
    }
    
    // 7. Get random plot twist
    $stmt = $conn->prepare("SELECT description FROM plot_twists ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $plot_twist = $stmt->fetch(PDO::FETCH_ASSOC)['description'];
    
    // Prepare response
    $response = array(
        "status" => "success",
        "ship" => array(
            "ship_name" => $ship_name,
            "vessel_class" => $vessel_class['name'],
            "armament" => $armament,
            "crew_quantity" => $crew_quantity,
            "crew_quality" => $crew_quality,
            "cargo" => $cargo,
            "plot_twist" => $plot_twist
        )
    );
    
} catch(PDOException $e) {
    $response = array(
        "status" => "error",
        "message" => "Database Error: " . $e->getMessage()
    );
}

// Return JSON response
echo json_encode($response);
?>
