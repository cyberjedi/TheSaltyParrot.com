/**
 * Core functionality for Cairn RPG Map Generator - Topography Module
 */

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('mapCanvas');
    const ctx = canvas.getContext('2d');
    const generateBtn = document.getElementById('generateBtn');
    const clearBtn = document.getElementById('clearBtn');
    
    // Map data structure
    let mapData = {
        width: canvas.width,
        height: canvas.height,
        dice: [],
        regionCentroids: []
    };
    
    // Initialize
    clearMap(ctx, mapData);
    
    // Set up event listeners
    generateBtn.addEventListener('click', () => {
        generateTerrain(ctx, mapData);
        showRollTablesButton();
    });
    
    clearBtn.addEventListener('click', () => {
        clearMap(ctx, mapData);
        resetUIState();
    });
    
    // UI management functions
    function resetUIState() {
        // Reset buttons
        document.getElementById('generateBtn').style.display = 'inline-block';
        
        const rollTablesBtn = document.getElementById('rollTablesBtn');
        if (rollTablesBtn) {
            rollTablesBtn.style.display = 'none';
        }
        
        const addWaterBtn = document.getElementById('addWaterBtn');
        if (addWaterBtn) {
            addWaterBtn.style.display = 'none';
        }
    }
    
    function showRollTablesButton() {
        // Hide generate button
        document.getElementById('generateBtn').style.display = 'none';
        
        // Create and add the "Roll on Terrain Tables" button if it doesn't exist
        if (!document.getElementById('rollTablesBtn')) {
            const rollTablesBtn = document.createElement('button');
            rollTablesBtn.id = 'rollTablesBtn';
            rollTablesBtn.textContent = 'Next Step: Roll on Terrain Type Tables';
            rollTablesBtn.classList.add('next-step');
            rollTablesBtn.addEventListener('click', () => {
                rollOnTerrainTables(ctx, mapData);
                hideRollTablesButton();
                showAddWaterButton();
            });
            document.querySelector('.controls').appendChild(rollTablesBtn);
        } else {
            document.getElementById('rollTablesBtn').style.display = 'inline-block';
        }
    }
    
    function hideRollTablesButton() {
        const rollTablesBtn = document.getElementById('rollTablesBtn');
        if (rollTablesBtn) {
            rollTablesBtn.style.display = 'none';
        }
    }
    
    function showAddWaterButton() {
        // Create and add the "Add Water Sources" button if it doesn't exist
        if (!document.getElementById('addWaterBtn')) {
            const addWaterBtn = document.createElement('button');
            addWaterBtn.id = 'addWaterBtn';
            addWaterBtn.textContent = 'Next Step: Add Water Sources';
            addWaterBtn.classList.add('next-step');
            addWaterBtn.addEventListener('click', () => {
                addWaterSources(ctx, mapData);
                hideAddWaterButton();
            });
            document.querySelector('.controls').appendChild(addWaterBtn);
        } else {
            document.getElementById('addWaterBtn').style.display = 'inline-block';
        }
    }
    
    function hideAddWaterButton() {
        const addWaterBtn = document.getElementById('addWaterBtn');
        if (addWaterBtn) {
            addWaterBtn.style.display = 'none';
        }
    }
});