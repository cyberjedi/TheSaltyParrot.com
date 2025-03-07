<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1: Basic PHP is working<br>";

// Test session
try {
    session_start();
    echo "Test 2: Session initialized successfully<br>";
} catch (Exception $e) {
    echo "Error with sessions: " . $e->getMessage() . "<br>";
}

// Test secure_variables.php access
$paths_to_check = [
    $_SERVER['DOCUMENT_ROOT'] . '/../../private/secure_variables.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../private/secure_variables.php',
    $_SERVER['DOCUMENT_ROOT'] . '/private/secure_variables.php',
    dirname(__FILE__) . '/../../private/secure_variables.php'
];

echo "Test 3: Checking for secure_variables.php<br>";
foreach ($paths_to_check as $path) {
    echo "Checking: " . $path . " - ";
    if (file_exists($path)) {
        echo "EXISTS<br>";
        
        // Test if we can read it
        try {
            $config = require_once($path);
            echo "Successfully loaded secure_variables.php<br>";
            
            // Only show that we have the right keys, not the values
            if (isset($config['discord'])) {
                echo "Discord config section exists<br>";
                $keys = array_keys($config['discord']);
                echo "Keys found: " . implode(', ', $keys) . "<br>";
            } else {
                echo "Discord section NOT found in config<br>";
            }
        } catch (Exception $e) {
            echo "Error loading file: " . $e->getMessage() . "<br>";
        }
        
        break;
    } else {
        echo "NOT FOUND<br>";
    }
}

// Test discord-config.php file
echo "<br>Test 4: Checking discord-config.php<br>";
if (file_exists('discord/discord-config.php')) {
    echo "File exists, attempting to include...<br>";
    
    try {
        include_once 'discord/discord-config.php';
        echo "Successfully included discord-config.php<br>";
        
        // Test if functions are defined
        if (function_exists('is_discord_authenticated')) {
            echo "Discord functions are defined<br>";
        } else {
            echo "Discord functions NOT defined<br>";
        }
    } catch (Exception $e) {
        echo "Error including file: " . $e->getMessage() . "<br>";
    }
} else {
    echo "discord-config.php NOT FOUND<br>";
}

echo "<br>All tests completed.";
?>
