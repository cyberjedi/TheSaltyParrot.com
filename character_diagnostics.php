<?php
/**
 * Character Sheet Diagnostics Page
 * Standalone page for troubleshooting character sheet functionality
 */

// Set the current page for sidebar highlighting
$current_page = 'character_sheet';

// Set base path for consistent loading
$base_path = './';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Sheet Diagnostics - The Salty Parrot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .diagnostic-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .test-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .test-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #bf9d61;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        #results {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            min-height: 100px;
        }
        .result-entry {
            margin-bottom: 8px;
            padding: 8px;
            background-color: #fff;
            border-left: 3px solid #bf9d61;
        }
        .success {
            border-left-color: #28a745;
        }
        .error {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="diagnostic-container">
        <h1>Character Sheet Diagnostics</h1>
        <p>This page tests basic JavaScript functionality to diagnose issues with the character sheet.</p>
        
        <div class="test-section">
            <div class="test-title">Test 1: Basic Button Click</div>
            <p>This tests whether a simple button click event works.</p>
            <div class="test-buttons">
                <button id="test1-button" class="btn btn-primary">Click Me</button>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">Test 2: Modal Display</div>
            <p>This tests whether a modal can be opened and closed.</p>
            <div class="test-buttons">
                <button id="test2-open" class="btn btn-primary">Open Modal</button>
            </div>
            <div id="test2-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
                <div style="background: white; padding: 20px; width: 300px; margin: 100px auto; border-radius: 5px;">
                    <h3>Test Modal</h3>
                    <p>This is a test modal window.</p>
                    <button id="test2-close" class="btn btn-secondary">Close Modal</button>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">Test 3: Global Variable Access</div>
            <p>This tests whether JavaScript can access global variables.</p>
            <div class="test-buttons">
                <button id="test3-button" class="btn btn-primary">Check Variables</button>
            </div>
        </div>
        
        <div id="results">
            <div class="result-entry">Results will appear here...</div>
        </div>
    </div>

    <!-- Inline JavaScript for basic functionality -->
    <script type="text/javascript">
        console.log('Diagnostic page script loaded');
        
        function addResult(message, isSuccess = true) {
            const resultsDiv = document.getElementById('results');
            const entry = document.createElement('div');
            entry.className = 'result-entry ' + (isSuccess ? 'success' : 'error');
            entry.textContent = message;
            resultsDiv.appendChild(entry);
            console.log(message);
        }
        
        // Explicitly attach event handlers when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM content loaded event fired');
            addResult('Page loaded successfully');
            
            // Test 1: Basic Button Click
            const test1Button = document.getElementById('test1-button');
            if (test1Button) {
                addResult('Test 1: Button found');
                test1Button.addEventListener('click', function() {
                    addResult('Test 1: Button clicked successfully');
                });
            } else {
                addResult('Test 1: Button not found', false);
            }
            
            // Test 2: Modal Display
            const test2OpenBtn = document.getElementById('test2-open');
            const test2Modal = document.getElementById('test2-modal');
            const test2CloseBtn = document.getElementById('test2-close');
            
            if (test2OpenBtn && test2Modal && test2CloseBtn) {
                addResult('Test 2: Modal elements found');
                
                test2OpenBtn.addEventListener('click', function() {
                    test2Modal.style.display = 'block';
                    addResult('Test 2: Modal opened successfully');
                });
                
                test2CloseBtn.addEventListener('click', function() {
                    test2Modal.style.display = 'none';
                    addResult('Test 2: Modal closed successfully');
                });
            } else {
                addResult('Test 2: Some modal elements not found', false);
            }
            
            // Test 3: Global Variable Access
            const test3Button = document.getElementById('test3-button');
            if (test3Button) {
                test3Button.addEventListener('click', function() {
                    // Define a test variable
                    window.testVar = 'Test variable value';
                    
                    if (window.testVar === 'Test variable value') {
                        addResult('Test 3: Global variable access working');
                    } else {
                        addResult('Test 3: Global variable access failed', false);
                    }
                });
            }
        });
    </script>
</body>
</html>