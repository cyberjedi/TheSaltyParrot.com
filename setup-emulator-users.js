// setup-emulator-users.js
const fs = require('fs');
const https = require('https');
const http = require('http');

// Read the exported users file
const userData = JSON.parse(fs.readFileSync('users.json'));

// Function to make HTTP requests to the emulator
function makeRequest(user) {
  const data = JSON.stringify({
    localId: user.localId,
    email: user.email,
    password: "emulator-test-password" // Set a known password for testing
  });

  const options = {
    hostname: 'localhost',
    port: 9099,
    path: '/identitytoolkit.googleapis.com/v1/accounts:signUp?key=fake-api-key',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Content-Length': data.length
    }
  };

  const req = http.request(options, (res) => {
    let responseData = '';
    
    res.on('data', (chunk) => {
      responseData += chunk;
    });
    
    res.on('end', () => {
      console.log(`User ${user.email} created with UID ${user.localId}`);
    });
  });
  
  req.on('error', (e) => {
    console.error(`Error creating user ${user.email}: ${e.message}`);
  });
  
  req.write(data);
  req.end();
}

// Process each user
if (userData.users && Array.isArray(userData.users)) {
  userData.users.forEach(user => {
    makeRequest(user);
  });
} else {
  console.error('Invalid users data format');
}
