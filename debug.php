<?php
// debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Environment Debug Information</h1>";

// PHP Version
echo "<h2>PHP Version</h2>";
echo "<p>" . phpversion() . "</p>";

// Check include paths
echo "<h2>Current Path Information</h2>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>PHP_SELF: " . $_SERVER['PHP_SELF'] . "</p>";

// Check file permissions
echo "<h2>File Permissions</h2>";
$file_list = ['index.php', 'components/sidebar.php', 'pages/login.php', 'pages/dashboard.php'];
echo "<ul>";
foreach ($file_list as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $info = stat($file);
        echo "<li>$file - Exists, Permissions: " . substr(sprintf('%o', $perms), -4) . "</li>";
    } else {
        echo "<li>$file - File not found</li>";
    }
}
echo "</ul>";

// Test PHP include functionality
echo "<h2>Testing PHP include functionality</h2>";
try {
    include 'components/sidebar.php';
    echo "<p>Sidebar included successfully</p>";
} catch (Exception $e) {
    echo "<p>Error including sidebar: " . $e->getMessage() . "</p>";
}

// Check for PHP errors
echo "<h2>PHP Error Log (last 10 lines if accessible)</h2>";
if (function_exists('shell_exec') && is_readable(ini_get('error_log'))) {
    $error_log = ini_get('error_log');
    echo "<p>Error log path: $error_log</p>";
    if (file_exists($error_log)) {
        $last_errors = shell_exec("tail -10 $error_log");
        echo "<pre>$last_errors</pre>";
    } else {
        echo "<p>Error log file not found</p>";
    }
} else {
    echo "<p>Cannot access error log</p>";
}
?>
