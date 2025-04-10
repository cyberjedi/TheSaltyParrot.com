// Firebase Configuration Loader
// This script loads the Firebase configuration from PHP constants

// Export the Firebase config for use in other modules
export const firebaseConfig = {
    apiKey: document.currentScript?.getAttribute('data-api-key') || '',
    authDomain: document.currentScript?.getAttribute('data-auth-domain') || '',
    projectId: document.currentScript?.getAttribute('data-project-id') || '',
    storageBucket: document.currentScript?.getAttribute('data-storage-bucket') || '',
    messagingSenderId: document.currentScript?.getAttribute('data-messaging-sender-id') || '',
    appId: document.currentScript?.getAttribute('data-app-id') || ''
}; 