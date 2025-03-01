// auth.js - Updated for Firebase v9
import { 
  onAuthStateChanged, 
  signInWithEmailAndPassword, 
  createUserWithEmailAndPassword, 
  signOut 
} from "https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js";
import { auth } from "./firebase-config.js";

document.addEventListener('DOMContentLoaded', function() {
    const authSection = document.getElementById('auth-section');
    const loginBtn = document.getElementById('login-btn');
    
    // Handle login button click
    if (loginBtn) {
        loginBtn.addEventListener('click', function() {
            window.location.href = window.location.pathname.includes('/pages/') 
                ? 'login.html' 
                : 'pages/login.html';
        });
    }
    
    // Check authentication state
    onAuthStateChanged(auth, (user) => {
        if (user) {
            // User is signed in
            
            // Update sidebar auth section
            if (authSection) {
                authSection.innerHTML = `
                    <div class="user-info">
                        <div class="username">${user.email}</div>
                    </div>
                    <button id="logout-btn" class="sidebar-btn logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                `;
                
                // Add event listener to the logout button
                document.getElementById('logout-btn').addEventListener('click', function() {
                    signOut(auth).then(() => {
                        console.log('User signed out');
                    }).catch((error) => {
                        console.error('Logout Error:', error);
                    });
                });
            }
            
            // Handle redirects for login/signup pages
            if (window.location.pathname.includes('login.html') || 
                window.location.pathname.includes('signup.html')) {
                window.location.href = window.location.pathname.includes('/pages/') 
                    ? '../index.html' 
                    : 'index.html';
            }
            
        } else {
            // User is signed out
            
            // Update sidebar auth section
            if (authSection) {
                authSection.innerHTML = `
                    <button id="login-btn" class="sidebar-btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                `;
                
                // Add event listener to the login button
                document.getElementById('login-btn').addEventListener('click', function() {
                    window.location.href = window.location.pathname.includes('/pages/') 
                        ? 'login.html' 
                        : 'pages/login.html';
                });
            }
            
            // Handle protected pages
            if (window.location.pathname.includes('dashboard.html')) {
                window.location.href = window.location.pathname.includes('/pages/') 
                    ? 'login.html' 
                    : 'pages/login.html';
            }
        }
    });
    
    // Set up login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('error-message');
            
            signInWithEmailAndPassword(auth, email, password)
                .then((userCredential) => {
                    // Login successful, redirect to home
                    window.location.href = '../index.html';
                })
                .catch((error) => {
                    // Show error message
                    errorMessage.textContent = error.message;
                    errorMessage.style.display = 'block';
                });
        });
    }
    
    // Set up signup form
    const signupForm = document.getElementById('signup-form');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const errorMessage = document.getElementById('error-message');
            
            // Check if passwords match
            if (password !== confirmPassword) {
                errorMessage.textContent = "Passwords do not match";
                errorMessage.style.display = 'block';
                return;
            }
            
            createUserWithEmailAndPassword(auth, email, password)
                .then((userCredential) => {
                    // Signup successful, redirect to home
                    window.location.href = '../index.html';
                })
                .catch((error) => {
                    // Show error message
                    errorMessage.textContent = error.message;
                    errorMessage.style.display = 'block';
                });
        });
    }
});
