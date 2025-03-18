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

// Draw a triangle landmark marker
function drawLandmarkTriangle(ctx, x, y, number) {
    const size = 30; // Size of the triangle
    
    // Draw black triangle
    ctx.fillStyle = 'black';
    ctx.beginPath();
    ctx.moveTo(x, y - size/2); // Top
    ctx.lineTo(x - size/2, y + size/2); // Bottom left
    ctx.lineTo(x + size/2, y + size/2); // Bottom right
    ctx.closePath();
    ctx.fill();
    
    // Draw number inside
    ctx.fillStyle = 'white';
    ctx.font = 'bold 16px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(number, x, y);
}

// Create a landmark reference table at the bottom of the map
function createLandmarkTable(ctx, mapData) {
    const tableTop = mapData.height - 110; // Position from the bottom
    const tableWidth = mapData.width - 40;
    const cellWidth = tableWidth / 3;
    const rowHeight = 25;
    
    // Draw table background
    ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
    ctx.fillRect(20, tableTop, tableWidth, 110);
    ctx.strokeStyle = 'black';
    ctx.lineWidth = 1;
    ctx.strokeRect(20, tableTop, tableWidth, 110);
    
    // Draw table header
    ctx.fillStyle = 'black';
    ctx.font = 'bold 16px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('LANDMARK REFERENCE', mapData.width / 2, tableTop + 15);
    
    // Draw table rows
    ctx.font = '14px Arial';
    ctx.textAlign = 'left';
    
    // Layout in columns
    const numLandmarks = mapData.dice.length;
    const landmarksPerColumn = Math.ceil(numLandmarks / 3);
    
    for (let i = 0; i < numLandmarks; i++) {
        const die = mapData.dice[i];
        const column = Math.floor(i / landmarksPerColumn);
        const row = i % landmarksPerColumn;
        
        const x = 30 + column * cellWidth;
        const y = tableTop + 40 + row * rowHeight;
        
        // Draw row
        ctx.fillText(`${i+1}. ${die.landmark}`, x, y);
    }
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
        
        // Draw the landmark triangle (at the centroid position)
        drawLandmarkTriangle(ctx, position.centroid.x, position.centroid.y, dieIndex + 1);
        
        // Register this label to avoid future overlaps
        placedLabels.push({
            x: position.x,
            y: position.y,
            width: textWidth,
            height: textHeight
        });
        
        // Draw semi-transparent background for better readability
        ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
        ctx.fillRect(position.x - 5, position.y - 20, textWidth + 10, textHeight + 10);
        
        // Draw terrain text
        ctx.fillStyle = 'black';
        ctx.font = 'bold 16px Arial';
        ctx.textAlign = 'left';
        ctx.fillText(terrainText, position.x, position.y);
    });
    
    // Create landmark reference table at the bottom
    createLandmarkTable(ctx, mapData);
}
