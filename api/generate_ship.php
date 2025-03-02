<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

// Initialize response array
$response = array();

try {
    // Include database connection
    require_once '../config/db_connect.php';
    
    // Test database connection
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }

    // Generate random ship data
    // 1. Get random ship name
    $ship_names = array(
        "Banshee's Wail", "Revenant", "Void Ripper", "Mermaid's Tear", "Carrion Crow", 
        "Executioner's Hand", "Poseidon's Rage", "Adventure's Ghost", "Widow's Revenge", 
        "Blood Moon", "Devil's Scorn", "Gilded Parrot", "Monolith", "Black Tide"
    );
    $ship_name = $ship_names[array_rand($ship_names)];
    
    // 2. Get vessel class and cargo score
    $vessel_classes = array(
        array("name" => "Raft", "cargo_score" => 0),
        array("name" => "Longboat", "cargo_score" => 0),
        array("name" => "Tartane", "cargo_score" => 1),
        array("name" => "Sloop", "cargo_score" => 2),
        array("name" => "Brigantine", "cargo_score" => 3),
        array("name" => "Fluyt", "cargo_score" => 5),
        array("name" => "Frigate", "cargo_score" => 4),
        array("name" => "Galleon", "cargo_score" => 6),
        array("name" => "Man-of-War", "cargo_score" => 4),
        array("name" => "Ship of the Line", "cargo_score" => 4)
    );
    $vessel_class = $vessel_classes[array_rand($vessel_classes)];
    
    // 3. Get random armament
    $armaments = array(
        "merchant ship (no weapons).",
        "that is lightly armed (reduce damage die size by one).",
        "carrying a normal armament.",
        "carrying a normal armament.",
        "carrying a normal armament.",
        "warship (double broadsides)."
    );
    $armament = $armaments[array_rand($armaments)];
    
    // 4. Get random crew quantity
    $crew_quantities = array(
        "short-handed (half as many)",
        "of standard size",
        "of standard size",
        "ready for war (twice as many)",
        "ready for war (twice as many)",
        "ready to raid (as many as possible)"
    );
    $crew_quantity = $crew_quantities[array_rand($crew_quantities)];
    
    // 5. Get random crew quality
    $crew_qualities = array(
        "near mutiny and/or untrained.",
        "near mutiny and/or untrained.",
        "miserable and/or novices.",
        "miserable and/or novices.",
        "miserable and/or novices.",
        "of average quality.",
        "of average quality.",
        "fresh from shore leave and/or experienced.",
        "fresh from shore leave and/or experienced.",
        "prosperous, loyal, and/or have military training.",
        "prosperous, loyal, and/or have military training."
    );
    $crew_quality = $crew_qualities[array_rand($crew_qualities)];
    
    // 6. Generate cargo based on vessel class cargo score
    $cargo = array();
    $cargo_score = $vessel_class['cargo_score'];
    
    if ($cargo_score > 0) {
        $mundane_cargo = array(
            "food or crops, 250s",
            "spices or oils, 350s",
            "trade goods, 400s",
            "livestock, 400s",
            "sugar, 500s",
            "rum, 1000s",
            "munitions, 2000s",
            "tobacco, 1000s",
            "wine, 2000s",
            "antiques, 2000s",
            "lumber, 1000s",
            "special cargo"
        );
        
        $special_cargo = array(
            "raw silver ore, 5000s",
            "golden coins and treasures, 10000s",
            "religious leader(s)",
            "important prisoner(s)",
            "political or military figure(s)",
            "relics or a rare artifact, 4000s",
            "sea monster bones, 2500s",
            "exotic animals, 2000s",
            "d10 locked chests, 2d8 x 100s each",
            "d20 crates of ASH",
            "imprisoned undead",
            "a sorcerer with a tome of d4 Arcane Rituals"
        );
        
        for ($i = 0; $i < $cargo_score; $i++) {
            $cargo_item = $mundane_cargo[array_rand($mundane_cargo)];
            
            // Check if cargo is "special cargo"
            if ($cargo_item == "special cargo") {
                $cargo_item = $special_cargo[array_rand($special_cargo)];
                
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
    $plot_twists = array(
        "Deadly disease on board.",
        "Crew are impostors.",
        "Crew is mute.",
        "The PCs know this crew.",
        "Everyone on board was thought to be dead.",
        "Ghost ship.",
        "They're all zombies.",
        "Someone on board is related to a PC's backstory."
    );
    $plot_twist = $plot_twists[array_rand($plot_twists)];
    
    // Prepare successful response
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
} catch(Exception $e) {
    $response = array(
        "status" => "error",
        "message" => $e->getMessage()
    );
}

// Return JSON response
echo json_encode($response);
?>
