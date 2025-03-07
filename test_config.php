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

echo "<hr>Checking file contents:<br>";
$contents = file_get_contents($path);
echo "File size: " . strlen($contents) . " bytes<br>";
echo "Contains userStyle tag: " . (strpos($contents, '<userStyle>Normal</userStyle>') !== false ? "YES" : "NO") . "<br>";

// Check for common syntax issues
echo "Last character: '" . substr($contents, -1) . "' (ASCII: " . ord(substr($contents, -1)) . ")<br>";
echo "Ends with ?>: " . (substr(trim($contents), -2) == '?>' ? "YES" : "NO") . "<br>";

// Look for other potential issues
echo "<hr>File contents preview (first 200 chars):<br>";
echo htmlspecialchars(substr($contents, 0, 200)) . "...<br>";

echo "<hr>File contents preview (last 200 chars):<br>";
echo htmlspecialchars(substr($contents, -200)) . "<br>";

// Check if file has the correct structure
echo "<hr>Checking file structure:<br>";
$lines = file($path);
$first_line = trim($lines[0]);
$last_line = trim($lines[count($lines)-1]);

echo "First line: " . htmlspecialchars($first_line) . "<br>";
echo "Last line: " . htmlspecialchars($last_line) . "<br>";
echo "First line is '<?php': " . ($first_line == '<?php' ? "YES" : "NO") . "<br>";
echo "Last line is '];': " . ($last_line == '];' ? "YES" : "NO") . "<br>";
?>
