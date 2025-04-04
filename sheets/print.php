<?php
/**
 * Character Sheet Print View
 * 
 * Displays a printable version of a character sheet
 */

// Start the session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('Location: /index.php');
    exit;
}

// Check if a sheet ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: /sheets.php');
    exit;
}

// Initialize variables
$sheet_id = (int)$_GET['id'];
$sheet = null;
$error_message = null;
$user_id = $_SESSION['uid'];

// Include database connection
require_once '../config/db_connect.php';

// Load the sheet from the database
try {
    if (isset($conn) && $conn !== null) {
        // First get the main sheet data
        $stmt = $conn->prepare("SELECT * FROM character_sheets WHERE id = ? AND user_id = ?");
        $stmt->execute([$sheet_id, $user_id]);
        $sheet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sheet) {
            // Based on the system, load the appropriate system-specific data
            if ($sheet['system'] === 'pirate_borg') {
                $stmt = $conn->prepare("SELECT * FROM pirate_borg_sheets WHERE sheet_id = ?");
                $stmt->execute([$sheet_id]);
                $system_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Merge system data into the sheet array
                if ($system_data) {
                    $sheet = array_merge($sheet, $system_data);
                }
            }
            // Add elseif blocks for other systems here in the future
        }
    } else {
        $error_message = "Database connection error. Please try again.";
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// If sheet not found, redirect to sheets page
if (!$sheet) {
    header('Location: /sheets.php');
    exit;
}

// Helper function to get display name for game system
function getSystemDisplayName($systemCode) {
    $systems = [
        'pirate_borg' => 'Pirate Borg'
        // Add more systems here as they become available
    ];
    
    return isset($systems[$systemCode]) ? $systems[$systemCode] : $systemCode;
}

// Set the character name as the page title
$page_title = htmlspecialchars($sheet['name']) . ' - Character Sheet';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/character-sheet.css">
    <link rel="stylesheet" href="../css/print.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">
    <div class="print-container">
        <div class="print-header">
            <div class="print-title">
                <span><?php echo htmlspecialchars($sheet['name']); ?></span>
                <span class="system-badge"><?php echo getSystemDisplayName($sheet['system']); ?></span>
            </div>
        </div>
        
        <div class="print-actions">
            <button onclick="window.print()" class="print-button">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="/sheets.php" class="return-button">
                <i class="fas fa-arrow-left"></i> Back to Sheets
            </a>
        </div>
        
        <div class="sheet-content">
            <div class="character-header">
                <div class="character-image">
                    <img src="<?php echo htmlspecialchars($sheet['image_path']); ?>" 
                         alt="Character Portrait" 
                         onerror="this.src='../assets/TSP_default_character.jpg'">
                </div>
                <div class="character-info">
                    <h1><?php echo htmlspecialchars($sheet['name']); ?></h1>
                    <p class="character-class"><?php echo htmlspecialchars($sheet['character_type'] ?? 'Unknown Class'); ?></p>
                    <p class="character-background"><?php echo htmlspecialchars($sheet['background'] ?? ''); ?></p>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Attributes</div>
                <div class="attributes-grid">
                    <div class="attribute">
                        <div class="attribute-label">Strength</div>
                        <div class="attribute-value"><?php echo (int)$sheet['strength']; ?></div>
                    </div>
                    <div class="attribute">
                        <div class="attribute-label">Agility</div>
                        <div class="attribute-value"><?php echo (int)$sheet['agility']; ?></div>
                    </div>
                    <div class="attribute">
                        <div class="attribute-label">Presence</div>
                        <div class="attribute-value"><?php echo (int)$sheet['presence']; ?></div>
                    </div>
                    <div class="attribute">
                        <div class="attribute-label">Toughness</div>
                        <div class="attribute-value"><?php echo (int)$sheet['toughness']; ?></div>
                    </div>
                    <div class="attribute">
                        <div class="attribute-label">Spirit</div>
                        <div class="attribute-value"><?php echo (int)$sheet['spirit']; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Notes</div>
                <div class="notes">
                    <?php echo htmlspecialchars($sheet['notes'] ?? 'No notes recorded.'); ?>
                </div>
            </div>
            
            <div class="footer">
                Printed from The Salty Parrot - <?php echo date('F j, Y'); ?>
            </div>
        </div>
    </div>
</body>
</html> 