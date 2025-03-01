// dashboard.js | Last Updated March 1, 2025 at 9:07pm MST
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js";
import { auth } from "./firebase-config.js";

document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard.js loaded");
    
    // Update user information in the dashboard
    onAuthStateChanged(auth, (user) => {
        if (user) {
            // User is signed in
            console.log("Dashboard: User is signed in:", user.email);
            const userEmail = document.getElementById('user-email');
            if (userEmail) {
                userEmail.textContent = user.email;
            }
            
            // Here you would load the user's characters, recent activity, etc.
            // This would typically involve querying Firestore or another database
            loadUserData(user.uid);
        } else {
            // User is signed out, redirect to login
            console.log("Dashboard: User is not signed in, redirecting to login");
            window.location.href = 'login.html';
        }
    });
    
    // Function to load user data (placeholder for now)
    function loadUserData(userId) {
        console.log("Loading data for user:", userId);
        // This would be where you fetch data from your database
    }
    
    // Event listeners for buttons
    const quickDice = document.getElementById('quick-dice');
    if (quickDice) {
        quickDice.addEventListener('click', function() {
            console.log("Dice roller clicked");
            // Implement dice roller functionality
        });
    }
    
    const quickCharacter = document.getElementById('quick-character');
    if (quickCharacter) {
        quickCharacter.addEventListener('click', function() {
            console.log("Character manager clicked");
            // Navigate to character management page
        });
    }
    
    const quickNpc = document.getElementById('quick-npc');
    if (quickNpc) {
        quickNpc.addEventListener('click', function() {
            console.log("NPC generator clicked");
            // Implement NPC generator functionality
        });
    }
    
    const quickShip = document.getElementById('quick-ship');
    if (quickShip) {
        quickShip.addEventListener('click', function() {
            console.log("Ship generator clicked");
            // Implement ship generator functionality
        });
    }
    
    const createCharacterBtn = document.getElementById('create-character-btn');
    if (createCharacterBtn) {
        createCharacterBtn.addEventListener('click', function() {
            console.log("Create character clicked");
            // Navigate to character creation page
        });
    }
});
