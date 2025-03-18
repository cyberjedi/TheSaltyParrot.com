/**
 * Water features generation for Cairn RPG Map Generator
 */

// Add water sources to the map
function addWaterSources(ctx, mapData) {
    // Keep the landmark reference table intact
    const tableData = ctx.getImageData(20, mapData.height - 110, mapData.width - 40, 110);
    
    // Find the highest and lowest elevation points
    const getElevationRank = (terrain) => {
        if (terrain === 'perilous') return 3;
        if (terrain === 'tough') return 2;
        return 1; // easy terrain
    };
    
    // Sort regions by elevation (highest to lowest)
    const sortedRegions = [...mapData.dice].sort((a, b) => {
        return getElevationRank(b.terrainType) - getElevationRank(a.terrainType);
    });
    
    // Get highest and lowest elevation regions
    const highestRegion = sortedRegions[0];
    const lowestRegion = sortedRegions[sortedRegions.length - 1];
    
    // Get region centroids
    const highestCentroid = mapData.regionCentroids.find(c => c.regionIndex === mapData.dice.indexOf(highestRegion));
    const lowestCentroid = mapData.regionCentroids.find(c => c.regionIndex === mapData.dice.indexOf(lowestRegion));
    
    if (!highestCentroid || !lowestCentroid) return;
    
    // Create river path
    drawRiver(ctx, highestCentroid, lowestCentroid);
    
    // Restore the landmark reference table
    ctx.putImageData(tableData, 20, mapData.height - 110);
}

// Draw a river from highest to lowest elevation
function drawRiver(ctx, start, end) {
    // Set river style
    ctx.strokeStyle = '#4682B4'; // Steel blue
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    // Begin the path
    ctx.beginPath();
    ctx.moveTo(start.x, start.y);
    
    // Calculate distance between points
    const dx = end.x - start.x;
    const dy = end.y - start.y;
    const distance = Math.sqrt(dx * dx + dy * dy);
    
    // Determine number of control points based on distance
    const numControlPoints = Math.max(3, Math.floor(distance / 100));
    
    // Create a natural river path with control points
    let prevX = start.x;
    let prevY = start.y;
    
    // Reference line from start to end
    const refAngle = Math.atan2(dy, dx);
    
    // Create control points
    const controlPoints = [];
    for (let i = 1; i <= numControlPoints; i++) {
        // Calculate position along the line
        const t = i / (numControlPoints + 1);
        const lineX = start.x + dx * t;
        const lineY = start.y + dy * t;
        
        // Add randomness perpendicular to the line
        const perpDist = (Math.random() - 0.5) * (distance * 0.2);
        const perpX = -Math.sin(refAngle) * perpDist;
        const perpY = Math.cos(refAngle) * perpDist;
        
        // Final control point
        controlPoints.push({
            x: lineX + perpX,
            y: lineY + perpY
        });
    }
    
    // Draw the river using quadratic curves through control points
    for (let i = 0; i < controlPoints.length; i++) {
        const cp = controlPoints[i];
        
        if (i === controlPoints.length - 1) {
            // Last segment connects to the end point
            const midX = (cp.x + end.x) / 2;
            const midY = (cp.y + end.y) / 2;
            
            ctx.quadraticCurveTo(cp.x, cp.y, end.x, end.y);
        } else {
            // Middle segments connect to the midpoint between control points
            const next = controlPoints[i + 1];
            const midX = (cp.x + next.x) / 2;
            const midY = (cp.y + next.y) / 2;
            
            ctx.quadraticCurveTo(cp.x, cp.y, midX, midY);
        }
    }
    
    // Stroke the path
    ctx.stroke();
    
    // Randomly decide if river forms a lake in higher elevation
    if (Math.random() < 0.3) { // 30% chance
        // Find a region of higher elevation to place a lake
        const midRegionIndex = Math.floor(controlPoints.length / 2);
        if (midRegionIndex < controlPoints.length) {
            const lakeCenter = controlPoints[midRegionIndex];
            drawLake(ctx, lakeCenter.x, lakeCenter.y);
        }
    }
}

// Draw a lake at the specified location
function drawLake(ctx, x, y) {
    // Set lake style
    ctx.fillStyle = 'rgba(70, 130, 180, 0.6)'; // Semi-transparent steel blue
    ctx.strokeStyle = '#4682B4';
    ctx.lineWidth = 1.5;
    
    // Random lake size
    const radiusX = 20 + Math.random() * 30;
    const radiusY = 20 + Math.random() * 30;
    
    // Draw an irregular oval for the lake
    ctx.beginPath();
    
    // Use multiple bezier curves to create an irregular shape
    const numPoints = 8;
    const angleStep = (Math.PI * 2) / numPoints;
    
    for (let i = 0; i <= numPoints; i++) {
        const angle = i * angleStep;
        const nextAngle = (i + 1) * angleStep;
        
        // Add randomness to the radius
        const radVariance = 0.8 + Math.random() * 0.4;
        const nextRadVariance = 0.8 + Math.random() * 0.4;
        
        const pointX = x + Math.cos(angle) * radiusX * radVariance;
        const pointY = y + Math.sin(angle) * radiusY * radVariance;
        
        if (i === 0) {
            ctx.moveTo(pointX, pointY);
        } else {
            const ctrlX = x + Math.cos(angle - angleStep/2) * radiusX * 1.2;
            const ctrlY = y + Math.sin(angle - angleStep/2) * radiusY * 1.2;
            
            ctx.quadraticCurveTo(ctrlX, ctrlY, pointX, pointY);
        }
    }
    
    ctx.closePath();
    ctx.fill();
    ctx.stroke();
}
