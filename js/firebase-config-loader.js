// Firebase Configuration Loader
// This script loads the Firebase configuration from a global variable set by firebase-config.js

// Export the Firebase config for use in other modules
export const firebaseConfig = window.firebaseConfigData || {
    apiKey: '',
    authDomain: '',
    projectId: '',
    storageBucket: '',
    messagingSenderId: '',
    appId: ''
};

// Debug logging - can be removed after confirming it works
console.log("Firebase Module Loaded Config:", {
    apiKey: firebaseConfig.apiKey?.substring(0, 5) + "..." || "not set",
    isValid: !!window.firebaseConfigData?.apiKey
}); 