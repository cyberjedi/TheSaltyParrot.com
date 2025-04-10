// Firebase Configuration
// This script must be included as a regular script (not a module)
// to capture configuration attributes

// Create a global firebase config object
window.firebaseConfigData = {
    apiKey: document.currentScript.getAttribute('data-api-key'),
    authDomain: document.currentScript.getAttribute('data-auth-domain'),
    projectId: document.currentScript.getAttribute('data-project-id'),
    storageBucket: document.currentScript.getAttribute('data-storage-bucket'),
    messagingSenderId: document.currentScript.getAttribute('data-messaging-sender-id'),
    appId: document.currentScript.getAttribute('data-app-id')
};

// Debug output - only in console, can be removed later
console.log("Firebase Config Loaded:", {
    apiKey: window.firebaseConfigData.apiKey?.substring(0, 5) + "..." || "not set",
    authDomain: window.firebaseConfigData.authDomain || "not set",
    projectId: window.firebaseConfigData.projectId || "not set"
}); 