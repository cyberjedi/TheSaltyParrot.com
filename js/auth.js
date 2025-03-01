// auth.js | Last Updated March 1, 2025 at 9:17am MST
import { 
  onAuthStateChanged, 
  signInWithEmailAndPassword, 
  createUserWithEmailAndPassword, 
  signOut,
  sendPasswordResetEmail
} from "https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js";
import { auth } from "./firebase-config.js";

// Ensure we only add event listeners once
let listenersInitialized = false;

function initializeAuthListeners() {
  if (listenersInitialized) return;
  
  console.log("Initializing auth listeners");
  
  // Login form handler
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    console.log("Login form found, adding listener");
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      const errorMessage = document.getElementById('error-message');
      
      // Clear previous error message
      if (errorMessage) {
        errorMessage.textContent = "";
        errorMessage.style.display = 'none';
      }
      
      console.log("Attempting login with:", email);
      
      signInWithEmailAndPassword(auth, email, password)
        .then((userCredential) => {
          console.log("Login successful for:", userCredential.user.email);
          // Login successful, redirect to dashboard
          window.location.href = '../pages/dashboard.html';
        })
        .catch((error) => {
          console.error("Login error:", error);
          // Show error message
          if (errorMessage) {
            errorMessage.textContent = error.message;
            errorMessage.style.display = 'block';
          }
        });
    });
  }
  
  // Signup form handler
  const signupForm = document.getElementById('signup-form');
  if (signupForm) {
    console.log("Signup form found, adding listener");
    signupForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm-password').value;
      const errorMessage = document.getElementById('error-message');
      
      // Clear previous error message
      if (errorMessage) {
        errorMessage.textContent = "";
        errorMessage.style.display = 'none';
      }
      
      // Check if passwords match
      if (password !== confirmPassword) {
        errorMessage.textContent = "Passwords do not match";
        errorMessage.style.display = 'block';
        return;
      }
      
      console.log("Attempting signup with:", email);
      
      createUserWithEmailAndPassword(auth, email, password)
        .then((userCredential) => {
          console.log("Signup successful for:", userCredential.user.email);
          // Signup successful, redirect to dashboard
          window.location.href = '../pages/dashboard.html';
        })
        .catch((error) => {
          console.error("Signup error:", error);
          // Show error message
          if (errorMessage) {
            errorMessage.textContent = error.message;
            errorMessage.style.display = 'block';
          }
        });
    });
  }
  
  // Password Reset functionality
  const forgotPasswordLink = document.getElementById('forgot-password');
  if (forgotPasswordLink) {
    console.log("Forgot password link found, adding listener");
    forgotPasswordLink.addEventListener('click', function(e) {
      e.preventDefault();
      const email = document.getElementById('email').value;
      const errorMessage = document.getElementById('error-message');
      
      if (!email) {
        errorMessage.textContent = "Please enter your email address";
        errorMessage.style.display = 'block';
        return;
      }
      
      sendPasswordResetEmail(auth, email)
        .then(() => {
          errorMessage.textContent = "Password reset email sent. Check your inbox.";
          errorMessage.style.display = 'block';
          errorMessage.style.backgroundColor = "rgba(46, 204, 113, 0.2)";
          errorMessage.style.color = '#2ecc71';  // Green color for success
        })
        .catch((error) => {
          errorMessage.textContent = error.message;
          errorMessage.style.display = 'block';
        });
    });
  }
  
  listenersInitialized = true;
}

document.addEventListener('DOMContentLoaded', function() {
  console.log("DOM loaded, setting up auth");
  const authSection = document.getElementById('auth-section');
  const loginBtn = document.getElementById('login-btn');
  
  // Handle login button click
  if (loginBtn) {
    console.log("Login button found, adding click listener");
    loginBtn.addEventListener('click', function() {
      console.log("Login button clicked");
      const isInPagesDir = window.location.pathname.includes('/pages/');
      window.location.href = isInPagesDir ? 'login.html' : 'pages/login.html';
    });
  }
  
  // Function to get base path
  function getBasePath() {
    return window.location.pathname.includes('/pages/') ? '../' : './';
  }
  
  // Check authentication state
  onAuthStateChanged(auth, (user) => {
    if (user) {
      // User is signed in
      console.log("User is signed in:", user.email);
      
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
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
          logoutBtn.addEventListener('click', function() {
            signOut(auth).then(() => {
              console.log('User signed out');
              // Redirect to home page after logout
              window.location.href = getBasePath() + (getBasePath() === '../' ? '' : '');
            }).catch((error) => {
              console.error('Logout Error:', error);
            });
          });
        }
      }
      
      // Handle redirects for login/signup pages
      const currentPath = window.location.pathname;
      if (currentPath.includes('login.html') || currentPath.includes('signup.html')) {
        // Redirect to dashboard
        window.location.href = getBasePath() + (getBasePath() === '../' ? 'dashboard.html' : 'pages/dashboard.html');
      }
      
      // Enable navigation buttons
      const disabledButtons = document.querySelectorAll('.sidebar-btn.disabled');
      disabledButtons.forEach(button => {
        button.classList.remove('disabled');
      });
      
    } else {
      // User is signed out
      console.log("User is signed out");
      
      // Update sidebar auth section
      if (authSection) {
        authSection.innerHTML = `
          <button id="login-btn" class="sidebar-btn">
            <i class="fas fa-sign-in-alt"></i> Login
          </button>
        `;
        
        // Add event listener to the login button
        const loginBtnUpdated = document.getElementById('login-btn');
        if (loginBtnUpdated) {
          loginBtnUpdated.addEventListener('click', function() {
            console.log("Login button clicked");
            const isInPagesDir = window.location.pathname.includes('/pages/');
            window.location.href = isInPagesDir ? 'login.html' : 'pages/login.html';
          });
        }
      }
      
      // Handle protected pages
      if (window.location.pathname.includes('dashboard.html')) {
        const isInPagesDir = window.location.pathname.includes('/pages/');
        window.location.href = isInPagesDir ? 'login.html' : 'pages/login.html';
      }
      
      // Disable navigation buttons
      const navButtons = document.querySelectorAll('.sidebar-btn:not(#login-btn)');
      navButtons.forEach(button => {
        if (!button.classList.contains('disabled')) {
          button.classList.add('disabled');
        }
      });
    }
    
    // Initialize form listeners after auth state is checked
    initializeAuthListeners();
  });
});
