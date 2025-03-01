// dashboard.js
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js";
import { auth } from "./firebase-config.js";

document.addEventListener('DOMContentLoaded', function() {
    // Update user information in the dashboard
    onAuthStateChanged(auth, (user) => {
        if (user) {
            // User is signed in
            const userEmail = document.getElementById('user-email');
            if (userEmail) {
                userEmail.textContent = user.email;
            }
            
            // Here you would load the user's characters, recent activity, etc.
            // This would typically involve querying Firestore or another database
            loadUserData(user.uid);
        } else {
            // User is signed out, redirect to login
            window.location.href = 'login.html';
        }
    });
    
    // Function to load user data (placeholder for now)
    function loadUserData(userId) {
        console.log("Loading data for user:", userId);
        // This would be where you fetch data from your database
    }
    
    // Event listeners for buttons
    document.getElementById('quick-dice').addEventListener('click', function() {
        console.log("Dice roller clicked");
        // Implement dice roller functionality
    });
    
    document.getElementById('quick-character').addEventListener('click', function() {
        console.log("Character manager clicked");
        // Navigate to character management page
    });
    
    document.getElementById('quick-npc').addEventListener('click', function() {
        console.log("NPC generator clicked");
        // Implement NPC generator functionality
    });
    
    document.getElementById('quick-ship').addEventListener('click', function() {
        console.log("Ship generator clicked");
        // Implement ship generator functionality
    });
    
    document.getElementById('create-character-btn').addEventListener('click', function() {
        console.log("Create character clicked");
        // Navigate to character creation page
    });
});
