// Inside your login form event listener, add more debug logging:
loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    console.log("Login form submitted");
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    console.log("Attempting login with:", email);
    
    // Clear any previous error message
    if (errorMessage) {
        errorMessage.textContent = "";
        errorMessage.style.display = 'none';
    }
    
    // Add more detailed error handling
    firebase.auth().signInWithEmailAndPassword(email, password)
        .then((userCredential) => {
            console.log("Login successful for:", userCredential.user.email);
            console.log("Redirecting to dashboard...");
            window.location.href = "dashboard.php";
        })
        .catch((error) => {
            console.error("Login error:", error);
            console.error("Error code:", error.code);
            console.error("Error message:", error.message);
            
            // Show error message
            if (errorMessage) {
                errorMessage.textContent = error.message;
                errorMessage.style.display = 'block';
            }
        });
});
