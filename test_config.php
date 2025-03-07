<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking configuration file directly:<br>";
$path = '/home/theshfmb/private/secure_variables.php';
echo "Looking for file at: " . $path . "<br>";
echo "File exists: " . (file_exists($path) ? "YES" : "NO") . "<br>";

if (file_exists($path)) {
    echo "Attempting to load it:<br>";
    try {
        // Use a temp variable to see what's returned
        $result = require($path);
        echo "Type returned: " . gettype($result) . "<br>";
        
        if (is_array($result)) {
            echo "Config is an array with keys: " . implode(", ", array_keys($result)) . "<br>";
        } else {
            echo "Config is NOT an array, it's a: " . gettype($result) . "<br>";
            echo "Value: ";
            var_dump($result);
        }
    } catch (Exception $e) {
        echo "Error loading file: " . $e->getMessage();
    }
}
?>
