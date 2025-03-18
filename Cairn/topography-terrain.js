/**
 * Terrain and landmark data tables for Cairn RPG Map Generator
 */

// Terrain tables organized by difficulty
const terrainTables = {
    easy: [
        "Bluffs", "Dells", "Farmlands", "Fells", "Foothills", 
        "Glens", "Grasslands", "Gulleys", "Heaths", "Lowlands", 
        "Meadows", "Moors", "Pampas", "Pastures", "Plains", 
        "Plateaus", "Prairies", "Savannas", "Steppes", "Valleys"
    ],
    tough: [
        "Barrens", "Canyons", "Chaparral", "Coral Reefs", "Deserts", 
        "Dunes", "Estuaries", "Fens", "Forests", "Heathlands", 
        "Hills", "Mangroves", "Marshlands", "Moorlands", "Rainforests", 
        "Scrublands", "Taiga", "Thickets", "Tundra", "Woodlands"
    ],
    perilous: [
        "Alpine Meadows", "Bogs", "Boulders", "Caverns", "Cliffs", 
        "Craters", "Crevasses", "Geysers", "Glaciers", "Gorges", 
        "Hollows", "Ice Fields", "Jungles", "Lava Fields", "Mountains", 
        "Peatlands", "Quagmires", "Ravine", "Swamps", "Wastelands"
    ]
};

// Landmark tables organized by difficulty
const landmarkTables = {
    easy: [
        "Broken Sundial", "Circle of Menhirs", "Circular Maze", "Cloud Stairway", "Dead Aqueduct", 
        "Enormous Footprint", "Fallen Column", "False Oasis", "Giant's Throne", "Glittering Cascade", 
        "Golden Bridge", "Great Stone Face", "Great Waterwheel", "Heart Tree", "Opaque Lake", 
        "Petrified Forest", "Pit of Cold Fire", "Silver Face", "Sinkhole", "Titanic Gate"
    ],
    tough: [
        "Algae Falls", "Basalt Columns", "Behemoth Graveyard", "Canyon Bridge", "Cinder Cones", 
        "Half-Buried Ark", "Flame Pits", "Forest of Arrows", "Frozen Waterfall", "Fungal Forest", 
        "Hanging Valley", "Inverted Lighthouse", "Leviathan Bones", "Massive Crater", "Massive Dung Ball", 
        "Salt Flat Mirrors", "Shrouded Ziggurat", "Stalagmite Forest", "Sunken Colossus", "Titan's Table"
    ],
    perilous: [
        "Active Volcano", "Ammonia Caves", "Bone Mountain", "Crystalline Forest", "Dome of Darkness", 
        "Enormous Hive", "Floating Object", "Inactive Automaton", "Land Scar", "Large Vents", 
        "Magma Sculptures", "Man on the Mountain", "Meteor Garden", "Obsidian Needle", "Reverse Waterfall", 
        "River of Sulfur", "Siren Stones", "Sky-Root", "Titanic Ribcage", "Weeping Bubble"
    ]
};

// Get terrain type based on die value
function getTerrainType(dieValue) {
    if (dieValue <= 3) return "easy";
    if (dieValue <= 5) return "tough";
    return "perilous";
}

// Roll d20
function rollD20() {
    return Math.floor(Math.random() * 20);  // 0-19 for array index
}

// Draw a landmark marker circle
function drawLandmarkTriangle(ctx, x, y, number) {
    // Draw black circle
    ctx.fillStyle = 'black';
    const radius = 15;
    ctx.beginPath();
    ctx.arc(x, y, radius, 0, Math.PI * 2);
    ctx.fill();
    
    // Draw number inside
    ctx.fillStyle = 'white';
    ctx.font = 'bold 14px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(number, x, y);
}

// Create a modern landmark reference table below the map
function createLandmarkTable(ctx, mapData) {
    // Save the map content
    const mapImageData = ctx.getImageData(0, 0, mapData.width, mapData.height);
    
    // Create a new canvas for the table
    const tableCanvas = document.createElement('canvas');
    tableCanvas.width = mapData.width;
    tableCanvas.height = 120; // Height for the table
    tableCanvas.id = 'landmarkTable';
    
    // Remove existing table if it exists
    const existingTable = document.getElementById('landmarkTable');
    if (existingTable) {
        existingTable.remove();
    }
    
    // Append the table canvas to the container
    const mapContainer = document.querySelector('.map-container');
    mapContainer.appendChild(tableCanvas);
    
    // Get table context
    const tableCtx = tableCanvas.getContext('2d');
    
    // Draw modern table with shadow for depth
    tableCtx.shadowColor = 'rgba(0, 0, 0, 0.2)';
    tableCtx.shadowBlur = 10;
    tableCtx.shadowOffsetX = 0;
    tableCtx.shadowOffsetY = 3;
    
    // Draw table background with rounded corners
    tableCtx.fillStyle = 'white';
    const width = tableCanvas.width * 0.9; // 90% of canvas width for centering
    const x = (tableCanvas.width - width) / 2;
    roundRect(tableCtx, x, 10, width, 100, 8);
    tableCtx.fill();
    
    // Reset shadow for text
    tableCtx.shadowColor = 'transparent';
    tableCtx.shadowBlur = 0;
    tableCtx.shadowOffsetX = 0;
    tableCtx.shadowOffsetY = 0;
    
    // Draw table header with background
    tableCtx.fillStyle = '#4a4a4a';
    roundRect(tableCtx, x, 10, width, 30, { tl: 8, tr: 8, bl: 0, br: 0 });
    tableCtx.fill();
    
    // Draw header text
    tableCtx.fillStyle = 'white';
    tableCtx.font = 'bold 16px Arial';
    tableCtx.textAlign = 'center';
    tableCtx.fillText('LANDMARK REFERENCE', tableCanvas.width / 2, 30);
    
    // Draw table content
    tableCtx.fillStyle = '#333333';
    tableCtx.font = '14px Arial';
    tableCtx.textAlign = 'left';
    
    // Layout in columns - get available space after header
    const contentAreaX = x + 20;
    const contentAreaY = 50;
    const contentWidth = width - 40;
    
    const numLandmarks = mapData.dice.length;
    const columns = 3;
    const landmarksPerColumn = Math.ceil(numLandmarks / columns);
    const cellWidth = contentWidth / columns;
    const rowHeight = 25;
    
    // Draw each landmark item
    for (let i = 0; i < numLandmarks; i++) {
        const die = mapData.dice[i];
        const column = Math.floor(i / landmarksPerColumn);
        const row = i % landmarksPerColumn;
        
        const itemX = contentAreaX + column * cellWidth;
        const itemY = contentAreaY + row * rowHeight;
        
        // Draw landmark number with circle
        tableCtx.fillStyle = 'black';
        tableCtx.beginPath();
        tableCtx.arc(itemX + 8, itemY - 5, 9, 0, Math.PI * 2);
        tableCtx.fill();
        
        tableCtx.fillStyle = 'white';
        tableCtx.font = 'bold 12px Arial';
        tableCtx.textAlign = 'center';
        tableCtx.fillText(i+1, itemX + 8, itemY - 2);
        
        // Draw landmark name
        tableCtx.fillStyle = '#333333';
        tableCtx.font = '14px Arial';
        tableCtx.textAlign = 'left';
        tableCtx.fillText(die.landmark, itemX + 20, itemY);
    }
    
    // Restore the map content
    ctx.putImageData(mapImageData, 0, 0);
}

// Helper function to draw rounded rectangles
function roundRect(ctx, x, y, width, height, radius) {
    if (typeof radius === 'undefined') {
        radius = 5;
    }
    if (typeof radius === 'number') {
        radius = {tl: radius, tr: radius, br: radius, bl: radius};
    } else {
        const defaultRadius = {tl: 0, tr: 0, br: 0, bl: 0};
        for (let side in defaultRadius) {
            radius[side] = radius[side] || defaultRadius[side];
        }
    }
    
    ctx.beginPath();
    ctx.moveTo(x + radius.tl, y);
    ctx.lineTo(x + width - radius.tr, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + radius.tr);
    ctx.lineTo(x + width, y + height - radius.br);
    ctx.quadraticCurveTo(x + width, y + height, x + width - radius.br, y + height);
    ctx.lineTo(x + radius.bl, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - radius.bl);
    ctx.lineTo(x, y + radius.tl);
    ctx.quadraticCurveTo(x, y, x + radius.tl, y);
    ctx.closePath();
}

// Roll on terrain type tables and place terrain and landmark information
function rollOnTerrainTables(ctx, mapData) {
    // Clear the canvas except for region boundaries
    ctx.clearRect(0, 0, mapData.width, mapData.height);
    
    // Redraw region boundaries
    drawRegionBoundaries(ctx, mapData.dice.map(die => ({ x: die.x, y: die.y })), mapData);
    
    // Track placed labels to avoid overlap
    const placedLabels = [];
    
    // Helper function to check if a new label would overlap existing ones
    function wouldOverlap(x, y, width, height) {
        for (const label of placedLabels) {
            // Simple rectangular collision detection
            if (x < label.x + label.width &&
                x + width > label.x &&
                y < label.y + label.height &&
                y + height > label.y) {
                return true;
            }
        }
        return false;
    }
    
    // Helper function to find best position for a label using region centroid
    function findBestPosition(dieIndex, textWidth, textHeight) {
        // Check if we have a calculated centroid for this region
        if (mapData.regionCentroids && mapData.regionCentroids[dieIndex]) {
            const centroid = mapData.regionCentroids[dieIndex];
            
            // Position the text centered at the centroid
            const x = centroid.x - textWidth / 2;
            const y = centroid.y - textHeight / 2;
            
            // Ensure position is within canvas bounds
            const adjustedX = Math.max(10, Math.min(mapData.width - textWidth - 10, x));
            const adjustedY = Math.max(textHeight - 10, Math.min(mapData.height - 120 - textHeight - 10, y));
            
            // If not overlapping with other labels, use this centroid position
            if (!wouldOverlap(adjustedX, adjustedY, textWidth, textHeight)) {
                return { x: adjustedX, y: adjustedY, centroid };
            }
            
            // Try positions around the centroid
            const offsets = [
                { x: 0, y: -textHeight - 30 },  // Above
                { x: 0, y: textHeight + 30 },   // Below
                { x: -textWidth - 30, y: 0 },   // Left
                { x: textWidth + 30, y: 0 },    // Right
                { x: -textWidth - 30, y: -textHeight - 30 }, // Top-left
                { x: textWidth + 30, y: -textHeight - 30 },  // Top-right
                { x: -textWidth - 30, y: textHeight + 30 },  // Bottom-left
                { x: textWidth + 30, y: textHeight + 30 }    // Bottom-right
            ];
            
            for (const offset of offsets) {
                const posX = centroid.x + offset.x;
                const posY = centroid.y + offset.y;
                
                // Ensure position is within canvas bounds and above the table area
                if (posX >= 10 && posX + textWidth <= mapData.width - 10 &&
                    posY >= textHeight - 10 && posY + textHeight <= mapData.height - 120 - 10) {
                        
                    if (!wouldOverlap(posX, posY, textWidth, textHeight)) {
                        return { x: posX, y: posY, centroid };
                    }
                }
            }
        }
        
        // Fallback to using the die position
        const die = mapData.dice[dieIndex];
        
        // Try different offsets from the die position
        const possiblePositions = [
            { x: die.x - textWidth/2, y: die.y + 40 },               // Below triangle
            { x: die.x - textWidth - 10, y: die.y - textHeight/2 },  // Left
            { x: die.x + 10, y: die.y - textHeight/2 },              // Right
            { x: die.x - textWidth - 10, y: die.y + 40 },            // Bottom-left
            { x: die.x + 10, y: die.y + 40 }                         // Bottom-right
        ];
        
        // Ensure position is within canvas bounds and above the table area
        const validPositions = possiblePositions.filter(pos => 
            pos.x >= 10 && 
            pos.x + textWidth <= mapData.width - 10 &&
            pos.y >= textHeight - 10 && 
            pos.y + textHeight <= mapData.height - 120 - 10
        );
        
        // Find first position that doesn't overlap
        for (const pos of validPositions) {
            if (!wouldOverlap(pos.x, pos.y, textWidth, textHeight)) {
                return { x: pos.x, y: pos.y, centroid: { x: die.x, y: die.y } };
            }
        }
        
        // If all positions overlap, return the first valid one anyway
        return validPositions[0] || 
               { x: die.x - textWidth/2, y: die.y + 40, centroid: { x: die.x, y: die.y } };
    }
    
    // For each die, replace with terrain and landmark info
    mapData.dice.forEach((die, dieIndex) => {
        const terrainType = getTerrainType(die.value);
        
        // Separate rolls for terrain and landmark
        const terrainRoll = rollD20();
        const landmarkRoll = rollD20();
        
        // Get terrain and landmark from tables
        const terrainResult = terrainTables[terrainType][terrainRoll];
        const landmarkResult = landmarkTables[terrainType][landmarkRoll];
        
        // Store terrain information for later use
        die.terrainType = terrainType;
        die.terrain = terrainResult;
        die.landmark = landmarkResult;
        
        // Prepare text
        const terrainText = terrainResult;
        
        // Measure text
        ctx.font = 'bold 16px Arial';
        const terrainWidth = ctx.measureText(terrainText).width;
        
        const textWidth = terrainWidth + 20;
        const textHeight = 20; // Approximate height for the line
        
        // Find best position for this label using region centroid
        const position = findBestPosition(dieIndex, textWidth, textHeight);
        
        // Draw the landmark circle (at the centroid position)
        drawLandmarkTriangle(ctx, position.centroid.x, position.centroid.y, dieIndex + 1);
        
        // Register this label to avoid future overlaps
        placedLabels.push({
            x: position.x,
            y: position.y,
            width: textWidth,
            height: textHeight
        });
        
        // Draw text with background and rounded corners for better readability
        // Create background with rounded corners
        ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
        // Draw rounded rectangle
        ctx.beginPath();
        const radius = 5;
        const padding = 5;
        ctx.moveTo(position.x - padding + radius, position.y - 20);
        ctx.lineTo(position.x - padding + textWidth + padding - radius, position.y - 20);
        ctx.quadraticCurveTo(position.x - padding + textWidth + padding, position.y - 20, position.x - padding + textWidth + padding, position.y - 20 + radius);
        ctx.lineTo(position.x - padding + textWidth + padding, position.y - 20 + textHeight + padding - radius);
        ctx.quadraticCurveTo(position.x - padding + textWidth + padding, position.y - 20 + textHeight + padding, position.x - padding + textWidth + padding - radius, position.y - 20 + textHeight + padding);
        ctx.lineTo(position.x - padding + radius, position.y - 20 + textHeight + padding);
        ctx.quadraticCurveTo(position.x - padding, position.y - 20 + textHeight + padding, position.x - padding, position.y - 20 + textHeight + padding - radius);
        ctx.lineTo(position.x - padding, position.y - 20 + radius);
        ctx.quadraticCurveTo(position.x - padding, position.y - 20, position.x - padding + radius, position.y - 20);
        ctx.closePath();
        ctx.fill();
        
        // Add subtle border
        ctx.strokeStyle = 'rgba(0, 0, 0, 0.1)';
        ctx.lineWidth = 1;
        ctx.stroke();
        
        // Draw terrain text
        ctx.fillStyle = 'black';
        ctx.font = 'bold 16px Arial';
        ctx.textAlign = 'left';
        ctx.fillText(terrainText, position.x, position.y);
    });
    
    // Create landmark reference table at the bottom
    createLandmarkTable(ctx, mapData);
}
