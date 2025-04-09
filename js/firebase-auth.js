// Firebase Authentication
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js';
import { 
    getAuth, 
    signInWithEmailAndPassword,
    createUserWithEmailAndPassword,
    signInWithPopup,
    GoogleAuthProvider,
    signOut,
    onAuthStateChanged,
    connectAuthEmulator,
    updatePassword // Ensure updatePassword is imported if needed directly
} from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js';

// Your web app's Firebase configuration
// It's often better to load this from a separate config file or environment variables,
// but keeping it here as it was originally.
const firebaseConfig = {
    apiKey: "YOUR_API_KEY", // Replace with your actual API key if not done
    authDomain: "YOUR_AUTH_DOMAIN",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_STORAGE_BUCKET",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

// Connect to Firebase Auth Emulator if running locally
if (window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1") {
    // console.warn("WARNING: You are using the Auth Emulator, which is intended for local testing only. Do not use with production credentials."); // Commented out for less console noise
    // console.log("Connecting to Firebase Auth Emulator at http://localhost:9099"); // Commented out for less console noise
    try {
      connectAuthEmulator(auth, "http://localhost:9099");
    } catch(e) {
      console.error("Error connecting to Auth Emulator: ", e);
    }
}

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
        // Handle specific errors like popup closed by user
        if (error.code === 'auth/popup-closed-by-user') {
            console.log('Google Sign-in popup closed by user.');
            return { success: false, error: 'Popup closed by user.' };
        }
        return { success: false, error: error.message };
    }
}

// Sign out
async function signOutUser() {
    try {
        await signOut(auth);
        // Session clearing should be handled by onAuthStateChanged listener
        console.log("User signed out locally.");
        return { success: true }; 
    } catch (error) {
        console.error('Error signing out:', error);
        return { success: false, error: error.message };
    }
}

// Update user session in PHP
async function updateUserSession(user) {
    if (!user) {
        console.error("updateUserSession called with null user.");
        return { success: false, error: "Invalid user data." };
    }
    try {
        const idToken = await user.getIdToken(/* forceRefresh */ true);
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
             const errorText = await response.text();
             console.error('Failed to update session. Status:', response.status, 'Response:', errorText);
             throw new Error(`Failed to update session: ${response.statusText}`);
        }
        const result = await response.json();
        if (result.success) {
            // console.log("PHP session updated successfully."); // Commented out for less console noise
            return { success: true };
        } else {
            console.error("Server reported failure updating session:", result.message);
            throw new Error(result.message || 'Server failed to update session.');
        }
    } catch (error) {
        console.error('Error updating PHP session:', error);
        return { success: false, error: error.message };
    }
}

// Clear user session in PHP
async function clearUserSession() {
    try {
        console.log("Attempting to clear PHP session...");
        const response = await fetch('/api/clear_session.php', {
            method: 'POST'
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Failed to clear session. Status:', response.status, 'Response:', errorText);
            throw new Error(`Failed to clear session: ${response.statusText}`);
        }
        const result = await response.json();
        if (result.success) {
             console.log("PHP session cleared successfully.");
             return { success: true };
        } else {
             console.error("Server reported failure clearing session:", result.message);
             throw new Error(result.message || 'Server failed to clear session.');
        }
       
    } catch (error) {
        console.error('Error clearing PHP session:', error);
    }
}

// Listen for auth state changes
onAuthStateChanged(auth, async (user) => {
    const isLoginPage = window.location.pathname.includes('index.php') || window.location.pathname === '/';
    const isAccountPage = window.location.pathname.includes('account.php');

    if (user) {
        // console.log('Auth state changed: User is signed in', user.uid); // Commented out for less console noise
        // REMOVED: Redundant redirect causing loops. Navigation to account page
        // should happen via user interaction with the topbar link/button,
        // which appears based on the server-side session state.
        // if (isLoginPage) {
        //      // console.log('User logged in, redirecting from login page to account...'); // Commented out for less console noise
        //      window.location.href = '/account.php';
        //      return;
        // }
        // console.log('User is signed in on a non-login page. Session should be valid or updating.'); // Commented out for less console noise

    } else {
        // console.log('Auth state changed: User is signed out.'); // Commented out for less console noise
        if (!isLoginPage) {
            // console.log('User logged out, clearing session and redirecting to login page...'); // Commented out for less console noise
            await clearUserSession();
            window.location.href = '/index.php';
        }
    }
});

// Export functions and the auth instance for other modules to use
export {
    auth, // Export the initialized auth instance
    signInWithEmail,
    signUpWithEmail,
    signInWithGoogle,
    signOutUser,
    updatePassword, // Export updatePassword if needed elsewhere
    onAuthStateChanged // Export if needed elsewhere
}; 