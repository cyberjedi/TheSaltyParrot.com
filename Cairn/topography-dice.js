/**
 * Dice generation and manipulation functions for Cairn RPG Map Generator
 */

// Roll a die (d6)
function rollDie() {
    return Math.floor(Math.random() * 6) + 1;
}

// Generate random dice positions on the map
function generateDicePositions(numDice, canvasWidth, canvasHeight) {
    const positions = [];
    const minDistance = 120; // Minimum distance between dice
    const margin = 80;       // Margin from the edges
    
    for (let i = 0; i < numDice; i++) {
        let x, y, tooClose;
        let attempts = 0;
        
        // Try to find a position that's not too close to existing dice
        do {
            tooClose = false;
            x = margin + Math.random() * (canvasWidth - 2 * margin);
            y = margin + Math.random() * (canvasHeight - 2 * margin);
            
            for (const pos of positions) {
                const distance = Math.sqrt(Math.pow(x - pos.x, 2) + Math.pow(y - pos.y, 2));
                if (distance < minDistance) {
                    tooClose = true;
                    break;
                }
            }
            
            attempts++;
            // If we can't find a good position after many attempts, reduce our constraints
            if (attempts > 50) {
                minDistance *= 0.9;
                attempts = 0;
            }
        } while (tooClose);
        
        positions.push({ x, y });
    }
    
    return positions;
}

// Draw a die on the canvas
function drawDie(ctx, x, y, value) {
    // Draw die square
    ctx.fillStyle = 'white';
    ctx.strokeStyle = 'black';
    ctx.lineWidth = 2;
    
    // Apply small random rotation for visual interest
    ctx.save();
    ctx.translate(x, y);
    const rotation = (Math.random() - 0.5) * 0.2;
    ctx.rotate(rotation);
    
    // Draw die
    const dieSize = 40;
    ctx.fillRect(-dieSize/2, -dieSize/2, dieSize, dieSize);
    ctx.strokeRect(-dieSize/2, -dieSize/2, dieSize, dieSize);
    
    // Draw die value
    ctx.fillStyle = 'black';
    ctx.font = '24px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(value, 0, 0);
    
    ctx.restore();
}

// Generate the terrain
function generateTerrain(ctx, mapData) {
    // Clear the map
    clearMap(ctx, mapData);
    
    // Step 1: Roll 1d6 to determine number of dice to place
    const numDice = rollDie();
    
    // Step 2: Generate dice positions
    const dicePositions = generateDicePositions(numDice, mapData.width, mapData.height);
    
    // Step 3: Roll values for each die
    const diceValues = dicePositions.map(() => rollDie());
    
    // Store dice data
    mapData.dice = dicePositions.map((pos, index) => ({
        x: pos.x,
        y: pos.y,
        value: diceValues[index]
    }));
    
    // Step 4: Draw region boundaries
    drawRegionBoundaries(ctx, dicePositions, mapData);
    
    // Step 5: Draw dice on top
    dicePositions.forEach((pos, i) => {
        drawDie(ctx, pos.x, pos.y, diceValues[i]);
    });
}

// Clear the map
function clearMap(ctx, mapData) {
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, mapData.width, mapData.height);
    mapData.dice = [];
    mapData.regionCentroids = [];
}