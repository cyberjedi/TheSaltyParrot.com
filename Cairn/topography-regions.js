/**
 * Region boundary generation using Voronoi principles
 */

// Generate boundaries using Voronoi principles
function drawRegionBoundaries(ctx, dicePositions, mapData) {
    // Store region information
    const regions = [];
    
    // Assign each pixel to the closest dice center to identify regions
    const pixelData = new Array(mapData.width * mapData.height).fill(-1);
    
    // Calculate region centroids
    const regionCentroids = new Array(dicePositions.length).fill().map(() => ({
        sumX: 0,
        sumY: 0,
        count: 0
    }));
    
    for (let x = 0; x < mapData.width; x++) {
        for (let y = 0; y < mapData.height; y++) {
            let minDist = Infinity;
            let closestRegion = -1;
            
            for (let i = 0; i < dicePositions.length; i++) {
                const pos = dicePositions[i];
                const dist = Math.sqrt(Math.pow(x - pos.x, 2) + Math.pow(y - pos.y, 2));
                
                if (dist < minDist) {
                    minDist = dist;
                    closestRegion = i;
                }
            }
            
            const pixelIndex = y * mapData.width + x;
            pixelData[pixelIndex] = closestRegion;
            
            // Add this pixel to the centroid calculation
            if (closestRegion !== -1) {
                regionCentroids[closestRegion].sumX += x;
                regionCentroids[closestRegion].sumY += y;
                regionCentroids[closestRegion].count++;
            }
        }
    }
    
    // Calculate the actual centroid of each region
    const centroids = regionCentroids.map((data, index) => {
        if (data.count > 0) {
            return {
                x: Math.round(data.sumX / data.count),
                y: Math.round(data.sumY / data.count),
                regionIndex: index
            };
        } else {
            // Fallback to dice position if no pixels
            return {
                x: dicePositions[index].x,
                y: dicePositions[index].y,
                regionIndex: index
            };
        }
    });
    
    // Store centroids for later use with text placement
    mapData.regionCentroids = centroids;
    
    // Find boundary pixels where adjacent pixels belong to different regions
    const boundaryPixels = new Set();
    
    for (let x = 1; x < mapData.width - 1; x++) {
        for (let y = 1; y < mapData.height - 1; y++) {
            const currentRegion = pixelData[y * mapData.width + x];
            
            // Check neighboring pixels (4-connected neighbors)
            const neighbors = [
                pixelData[y * mapData.width + (x - 1)],      // left
                pixelData[y * mapData.width + (x + 1)],      // right
                pixelData[(y - 1) * mapData.width + x],      // top
                pixelData[(y + 1) * mapData.width + x]       // bottom
            ];
            
            // If any neighbor belongs to a different region, this is a boundary
            for (const neighbor of neighbors) {
                if (neighbor !== currentRegion && currentRegion !== -1 && neighbor !== -1) {
                    boundaryPixels.add(`${x},${y}`);
                    break;
                }
            }
        }
    }
    
    // Convert boundary pixels to connected paths
    const boundaryList = [...boundaryPixels].map(coord => {
        const [x, y] = coord.split(',').map(Number);
        return { x, y };
    });
    
    // Simple clustering to group nearby boundary pixels
    const pathSegments = [];
    const visited = new Set();
    
    // Function to find neighboring boundary pixels
    function getNeighbors(point, radius = 2) {
        const neighbors = [];
        
        for (let i = 0; i < boundaryList.length; i++) {
            if (visited.has(i)) continue;
            
            const other = boundaryList[i];
            const dist = Math.sqrt(Math.pow(point.x - other.x, 2) + Math.pow(point.y - other.y, 2));
            
            if (dist <= radius) {
                neighbors.push({ index: i, point: other, distance: dist });
            }
        }
        
        // Sort by distance
        return neighbors.sort((a, b) => a.distance - b.distance);
    }
    
    // Build path segments by following adjacent boundary pixels
    for (let i = 0; i < boundaryList.length; i++) {
        if (visited.has(i)) continue;
        
        const segment = [boundaryList[i]];
        visited.add(i);
        
        // Trace path in both directions
        let current = boundaryList[i];
        let keepGoing = true;
        
        while (keepGoing) {
            const neighbors = getNeighbors(current);
            if (neighbors.length > 0) {
                const next = neighbors[0];
                segment.push(next.point);
                visited.add(next.index);
                current = next.point;
            } else {
                keepGoing = false;
            }
            
            // Prevent endless loops
            if (segment.length > 1000) {
                keepGoing = false;
            }
        }
        
        if (segment.length > 5) {
            pathSegments.push(segment);
        }
    }
    
    // Simplify paths (reduce number of points)
    const simplifiedSegments = pathSegments.map(segment => {
        // Only keep every Nth point for smoother lines
        const simplified = [];
        for (let i = 0; i < segment.length; i += 3) {
            simplified.push(segment[i]);
        }
        return simplified;
    });
    
    // Draw the boundaries as single, solid dark grey lines
    ctx.strokeStyle = '#444'; // Dark grey
    ctx.lineWidth = 1.2;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    
    for (const segment of simplifiedSegments) {
        if (segment.length < 2) continue;
        
        ctx.beginPath();
        ctx.moveTo(segment[0].x, segment[0].y);
        
        // Use curve interpolation for smooth boundaries
        for (let i = 1; i < segment.length; i++) {
            ctx.lineTo(segment[i].x, segment[i].y);
        }
        
        ctx.stroke();
    }
}