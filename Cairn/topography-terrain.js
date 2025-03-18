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
    // Create a new canvas for the table
    const tableCanvas = document.createElement('canvas');
    tableCanvas.width = 600; // Fixed width for the table
    tableCanvas.height = 120; // Height for the table
    tableCanvas.id = 'landmarkTable';
    tableCanvas.style.marginTop = '10px'; // Add margin between map and table
    tableCanvas.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)'; // Add shadow for depth
    tableCanvas.style.borderRadius = '8px'; // Rounded corners
    
    // Remove existing table if it exists
    const existingTable = document.getElementById('landmarkTable');
    if (existingTable) {
        existingTable.remove();
    }
    
    // Create a container div for better positioning
    const tableContainer = document.createElement('div');
    tableContainer.style.width = '100%';
    tableContainer.style.display = 'flex';
    tableContainer.style.justifyContent = 'center';
    tableContainer.style.marginTop = '20px';
    tableContainer.id = 'tableContainer';
    
    // Remove existing container if it exists
    const existingContainer = document.getElementById('tableContainer');
    if (existingContainer) {
        existingContainer.remove();
    }
    
    // Append the table canvas to the container
    tableContainer.appendChild(tableCanvas);
    
    // Append the container after the map container
    const mapContainer = document.querySelector('.map-container');
    mapContainer.parentNode.insertBefore(tableContainer, mapContainer.nextSibling);
    
    // Get table context
    const tableCtx = tableCanvas.getContext('2d');
    
    // Draw modern table with shadow for depth
    tableCtx.fillStyle = 'white';
    tableCtx.fillRect(0, 0, tableCanvas.width, tableCanvas.height);
    
    // Draw table header with background
    tableCtx.fillStyle = '#444444';
    tableCtx.fillRect(0, 0, tableCanvas.width, 30);
    
    // Draw header text
    tableCtx.fillStyle = 'white';
    tableCtx.font = 'bold 16px Arial';
    tableCtx.textAlign = 'center';
    tableCtx.fillText('LANDMARK REFERENCE', tableCanvas.width / 2, 20);
    
    // Draw table content
    tableCtx.fillStyle = '#333333';
    tableCtx.font = '14px Arial';
    tableCtx.textAlign = 'left';
    
    // Layout in columns
    const numLandmarks = mapData.dice.length;
    const columns = 3;
    const landmarksPerColumn = Math.ceil(numLandmarks / columns);
    const cellWidth = tableCanvas.width / columns;
    const rowHeight = 25;
    
    // Draw each landmark item
    for (let i = 0; i < numLandmarks; i++) {
        const die = mapData.dice[i];
        const column = Math.floor(i / landmarksPerColumn);
        const row = i % landmarksPerColumn;
        
        const itemX = 20 + column * cellWidth;
        const itemY = 50 + row * rowHeight;
        
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
    
    // Process all the terrain and landmark information
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
    });
    
    // Process all landmarks and terrain names in a specific order
    mapData.dice.forEach((die, dieIndex) => {
        const centroid = mapData.regionCentroids[dieIndex];
        if (!centroid) return;
        
        // Draw the landmark circle first
        drawLandmarkTriangle(ctx, centroid.x, centroid.y, dieIndex + 1);
        
        // Then draw terrain name to the RIGHT of the landmark
        drawTerrainNameRight(ctx, centroid.x, centroid.y, die.terrain);
    });
    
    // Create landmark reference table at the bottom
    createLandmarkTable(ctx, mapData);
}

// Draw a terrain name to the RIGHT of a landmark point
function drawTerrainNameRight(ctx, x, y, name) {
    ctx.font = 'bold 14px Arial';
    const textWidth = ctx.measureText(name).width;
    
    // Position text box to the right of the landmark with padding
    const textX = x + 25; // 25px gap between landmark and text
    const textY = y;
    
    // Draw background with rounded corners
    const padding = 6;
    const textHeight = 16;
    ctx.fillStyle = 'white';
    
    // Draw rounded rectangle background
    roundRect(ctx, textX - padding, textY - 10, textWidth + (padding * 2), textHeight + (padding * 2), 5);
    ctx.fill();
    
    // Add subtle border
    ctx.strokeStyle = 'rgba(0, 0, 0, 0.1)';
    ctx.lineWidth = 1;
    ctx.stroke();
    
    // Draw text
    ctx.fillStyle = 'black';
    ctx.textAlign = 'left';
    ctx.textBaseline = 'middle';
    ctx.fillText(name, textX, textY);
}
