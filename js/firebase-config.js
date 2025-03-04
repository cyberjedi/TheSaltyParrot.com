// firebase-config.js
// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyDzSPll8gZKWBhmD6o-QAAnT89TWucFkr0",
    authDomain: "salty-parrot.firebaseapp.com",
    projectId: "salty-parrot",
    storageBucket: "salty-parrot.appspot.com",
    messagingSenderId: "598113689428",
    appId: "1:598113689428:web:fb57b75af8efc6e051f2c1"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Global authentication variables
let currentUserId = null;
let currentUserEmail = null;

// Set userId and userEmail when user is authenticated
firebase.auth().onAuthStateChanged((user) => {
    if (user) {
        currentUserId = user.uid;
        currentUserEmail = user.email;
        
        // Update user information in UI
        const userEmail = document.getElementById('user-email');
        if (userEmail) {
            userEmail.textContent = user.email;
        }
        
        // Register user in database
        const formData = new FormData();
        formData.append('user_id', user.uid);
        formData.append('user_email', user.email);
        
        fetch('../api/register_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                console.error("Error registering user:", data.message);
            }
        })
        .catch(error => {
            console.error("Error registering user:", error);
        });
        
        // Check if in dashboard, initialize session
        if (typeof checkActiveSession === 'function') {
            checkActiveSession();
        }
    } else {
        // User is signed out, redirect to login
        console.log("User is not signed in, redirecting to login");
        window.location.href = '../index.php';
    }
});

// Logout function
function logoutUser() {
    firebase.auth().signOut().then(() => {
        console.log('User signed out');
        window.location.href = '../index.php';
    }).catch((error) => {
        console.error('Logout Error:', error);
    });
}

// Add event listeners to logout buttons
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logoutUser);
    }
    
    const logoutBtnTop = document.getElementById('logout-btn-top');
    if (logoutBtnTop) {
        logoutBtnTop.addEventListener('click', logoutUser);
    }
});
