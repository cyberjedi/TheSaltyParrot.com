<?php
// File: examples/discord_webhook_example.php
// Example showing how to use the Discord webhook service and components

require_once '../discord/discord-config.php';
require_once '../config/db_connect.php';
require_once '../components/discord_webhook_modal.php';

// Set page title
$page_title = 'Discord Webhook Example';

// Define a base path for assets
$base_path = '../';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/styles.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/discord.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/discord_components.css">
    <link rel="icon" href="<?php echo $base_path; ?>favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="app-container">
        <!-- Include the sidebar with proper base path -->
        <?php 
        // Define base_path for the sidebar component
        $GLOBALS['base_path'] = $base_path;
        include '../components/sidebar.php'; 
        ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <div class="logo">
                    <i class="fab fa-discord"></i>
                    <h1><?php echo $page_title; ?></h1>
                </div>
                <a href="../index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            
            <div class="content-section">
                <h2>Discord Webhook Integration Examples</h2>
                
                <div class="example-container">
                    <h3>Example 1: Ship Generator</h3>
                    <p>This example shows how to send ship details to Discord.</p>
                    
                    <div id="ship-example" class="example-output">
                        <h2 id="ship-name">The Salty Parrot</h2>
                        <div class="ship-details">
                            <h3>Ship Type:</h3>
                            <p>Frigate</p>
                            
                            <h3>Ship Description:</h3>
                            <p>A sleek frigate with black sails and a golden parrot figurehead.</p>
                            
                            <h3>Captain:</h3>
                            <p>Captain Stuart "Salty" Greenwell</p>
                        </div>
                        
                        <h3>Cargo:</h3>
                        <ul id="cargo-list">
                            <li>10 barrels of rum</li>
                            <li>5 crates of exotic spices</li>
                            <li>A mysterious locked chest</li>
                        </ul>
                        
                        <h3>Plot Twist (Optional):</h3>
                        <p>The ship's navigator is secretly working for a rival pirate crew.</p>
                    </div>
                    
                    <?php 
                    // Render Discord webhook modal for ship example
                    render_discord_webhook_modal(
                        '#ship-example',  // Content selector
                        'ship',           // Source type
                        false,            // No extra inputs
                        '',               // No extra inputs HTML
                        [
                            'button_text' => 'Send Ship to Discord',
                            'modal_title' => 'Send Ship Details',
                            'button_id' => 'send-ship-discord'
                        ]
                    );
                    ?>
                </div>
                
                <div class="example-container">
                    <h3>Example 2: Character Roll</h3>
                    <p>This example shows how to send a character attribute roll to Discord.</p>
                    
                    <div id="roll-example" class="example-output">
                        <h3>Blackbeard - Strength Check</h3>
                        <div class="roll-details">
                            <p>Dice Roll: 15</p>
                            <p>Strength Bonus: +3</p>
                            <p>Total: 18</p>
                        </div>
                    </div>
                    
                    <?php 
                    // Render Discord webhook modal for roll example
                    render_discord_webhook_modal(
                        '#roll-example',      // Content selector
                        'attribute_roll',     // Source type
                        false,                // No extra inputs
                        '',                   // No extra inputs HTML
                        [
                            'button_text' => 'Send Roll to Discord',
                            'modal_title' => 'Send Attribute Roll',
                            'button_id' => 'send-roll-discord',
                            'show_character_image' => true  // Enable character image
                        ]
                    );
                    ?>
                </div>
                
                <div class="example-container">
                    <h3>Example 3: Custom Message with Extra Inputs</h3>
                    <p>This example shows how to send a custom message with additional inputs.</p>
                    
                    <div id="custom-example" class="example-output">
                        <h3>Message to the Crew</h3>
                        <div class="message-content">
                            <p>Attention crew! We'll be making landfall at Skull Island tomorrow morning.</p>
                            <p>Prepare your equipment and be ready for adventure!</p>
                        </div>
                    </div>
                    
                    <?php 
                    // Define extra inputs HTML
                    $extraInputsHtml = '
                        <label for="message-title">Message Title:</label>
                        <input type="text" id="message-title" name="message_title" value="Crew Announcement" required>
                        
                        <label for="message-color">Message Color:</label>
                        <select id="message-color" name="message_color">
                            <option value="0xbf9d61">Gold</option>
                            <option value="0x43b581">Green</option>
                            <option value="0xf04747">Red</option>
                            <option value="0x7289da">Blue</option>
                        </select>
                        
                        <label for="message-footer">Message Footer (optional):</label>
                        <input type="text" id="message-footer" name="message_footer" placeholder="Optional footer text">
                    ';
                    
                    // Render Discord webhook modal for custom example
                    render_discord_webhook_modal(
                        '#custom-example',     // Content selector
                        'custom',              // Source type
                        true,                  // Include extra inputs
                        $extraInputsHtml,      // Extra inputs HTML
                        [
                            'button_text' => 'Send Custom Message',
                            'modal_title' => 'Send Custom Message',
                            'button_id' => 'send-custom-discord'
                        ]
                    );
                    ?>
                </div>
                
                <div class="api-example">
                    <h3>API Usage Example</h3>
                    <p>Here's how to use the webhook service directly in PHP code:</p>
                    
                    <pre><code>
// Initialize webhook service
require_once '../discord/webhook_service.php';
$webhookService = new WebhookService($conn);

// Example 1: Send a simple text message
$result = $webhookService->sendMessage(
    $webhookId,       // Webhook ID from database
    'Hello Discord!', // Message text
    'example'         // Source type for logging
);

// Example 2: Send an embed
$embed = [
    'title' => 'Treasure Map Found!',
    'description' => 'We discovered an ancient treasure map on Skull Island.',
    'color' => 0xbf9d61, // Gold color
    'fields' => [
        ['name' => 'Location', 'value' => 'Skull Island', 'inline' => true],
        ['name' => 'Estimated Value', 'value' => '5,000 gold', 'inline' => true]
    ]
];

$result = $webhookService->sendEmbed(
    $webhookId,  // Webhook ID from database
    $embed,      // Embed data
    'treasure'   // Source type for logging
);

// Example 3: Send HTML content with formatting
$result = $webhookService->sendFormattedContent(
    $webhookId,       // Webhook ID from database
    $htmlContent,     // HTML content to format and send
    'ship',           // Source type (determines formatting)
    $characterImage   // Optional character image URL
);

// Example 4: Send a completely custom payload
$customPayload = [
    'content' => 'Custom message with a custom embed:',
    'embeds' => [$embed],
    'components' => [
        // Discord UI components (buttons, etc.)
    ]
];

$result = $webhookService->sendCustomPayload(
    $webhookId,      // Webhook ID from database
    $customPayload,  // Complete custom payload
    'custom',        // Source type for logging
    'Custom payload' // Summary for logging
);
                    </code></pre>
                </div>
                
                <div class="integration-tips">
                    <h3>Integration Tips</h3>
                    <ul>
                        <li><strong>Use the Modal Component:</strong> For most cases, use the <code>discord_webhook_modal.php</code> component for a complete UI solution.</li>
                        <li><strong>Direct API Access:</strong> For background tasks or automation, use the API endpoint at <code>/api/send_webhook.php</code>.</li>
                        <li><strong>Service Class:</strong> For deep integration in PHP code, use the <code>WebhookService</code> class directly.</li>
                        <li><strong>Content Formatting:</strong> The service automatically formats content based on the source type (ship, loot, attribute_roll, etc.).</li>
                        <li><strong>Custom Payloads:</strong> For complete control, use <code>sendCustomPayload()</code> with your own Discord webhook payload.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    
    <footer>
        <p>The Salty Parrot is an independent production by Stuart Greenwell. It is not affiliated with Limithron LLC. It is published under the PIRATE BORG Third Party License. PIRATE BORG is ©2022 Limithron LLC.</p>
        <p>&copy; 2025 The Salty Parrot</p>
    </footer>
    
    <style>
        .example-container {
            margin-bottom: 40px;
            padding: 20px;
            background-color: #2f3136;
            border-radius: 8px;
        }
        
        .example-output {
            background-color: #36393f;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #dcddde;
        }
        
        .example-output h2, .example-output h3 {
            color: #fff;
            margin-top: 0;
        }
        
        .api-example {
            margin-bottom: 40px;
        }
        
        .api-example pre {
            background-color: #2f3136;
            padding: 20px;
            border-radius: 4px;
            overflow-x: auto;
            color: #dcddde;
        }
        
        .integration-tips {
            background-color: #2f3136;
            padding: 20px;
            border-radius: 8px;
        }
        
        .integration-tips h3 {
            color: #fff;
            margin-top: 0;
        }
        
        .integration-tips ul {
            padding-left: 20px;
        }
        
        .integration-tips li {
            margin-bottom: 10px;
        }
        
        .content-section {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
    </style>
</body>
</html>