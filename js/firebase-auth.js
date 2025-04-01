// Firebase Authentication
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js';
import { 
    getAuth, 
    signInWithEmailAndPassword,
    createUserWithEmailAndPassword,
    signInWithPopup,
    GoogleAuthProvider,
    signOut,
    onAuthStateChanged
} from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js';

// Your web app's Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyDzSPll8gZKWBhmD6o-QAAnT89TWucFkr0",
    authDomain: "salty-parrot.firebaseapp.com",
    projectId: "salty-parrot",
    storageBucket: "salty-parrot.firebasestorage.app",
    messagingSenderId: "598113689428",
    appId: "1:598113689428:web:fb57b75af8efc6e051f2c1"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

// Google Auth Provider
const googleProvider = new GoogleAuthProvider();

// Sign in with email and password
async function signInWithEmail(email, password) {
    try {
        const userCredential = await signInWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;
        await updateUserSession(user);
        return { success: true, user };
    } catch (error) {
        console.error('Error signing in:', error);
        return { success: false, error: error.message };
    }
}

// Sign up with email and password
async function signUpWithEmail(email, password) {
    try {
        const userCredential = await createUserWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;
        await updateUserSession(user);
        return { success: true, user };
    } catch (error) {
        console.error('Error signing up:', error);
        return { success: false, error: error.message };
    }
}

// Sign in with Google
async function signInWithGoogle() {
    try {
        const result = await signInWithPopup(auth, googleProvider);
        const user = result.user;
        await updateUserSession(user);
        return { success: true, user };
    } catch (error) {
        console.error('Error signing in with Google:', error);
        return { success: false, error: error.message };
    }
}

// Sign out
async function signOutUser() {
    try {
        await signOut(auth);
        await clearUserSession();
        return { success: true };
    } catch (error) {
        console.error('Error signing out:', error);
        return { success: false, error: error.message };
    }
}

// Update user session in PHP
async function updateUserSession(user) {
    try {
        const idToken = await user.getIdToken();
        const response = await fetch('/api/update_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                uid: user.uid,
                email: user.email,
                displayName: user.displayName,
                photoURL: user.photoURL,
                token: idToken
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to update session');
        }
        
        return { success: true };
    } catch (error) {
        console.error('Error updating session:', error);
        return { success: false, error: error.message };
    }
}

// Clear user session in PHP
async function clearUserSession() {
    try {
        const response = await fetch('/api/clear_session.php', {
            method: 'POST'
        });
        
        if (!response.ok) {
            throw new Error('Failed to clear session');
        }
        
        return { success: true };
    } catch (error) {
        console.error('Error clearing session:', error);
        return { success: false, error: error.message };
    }
}

// Listen for auth state changes
onAuthStateChanged(auth, (user) => {
    if (user) {
        // User is signed in
        updateUserSession(user);
    } else {
        // User is signed out
        clearUserSession();
    }
});

// Export functions
export {
    signInWithEmail,
    signUpWithEmail,
    signInWithGoogle,
    signOutUser,
    auth
}; 