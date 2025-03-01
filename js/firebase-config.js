// firebase-config.js | Last Updated March 1, 2025 at 9:16am MST
// Firebase configuration with v9 SDK
import { initializeApp } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-firestore.js";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyDzSPll8gZKWBhmD6o-QAAnT89TWucFkr0",
  authDomain: "salty-parrot.firebaseapp.com",
  projectId: "salty-parrot",
  storageBucket: "salty-parrot.appspot.com", // Fixed storage bucket URL
  messagingSenderId: "598113689428",
  appId: "1:598113689428:web:fb57b75af8efc6e051f2c1"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db = getFirestore(app);

export { auth, db };
