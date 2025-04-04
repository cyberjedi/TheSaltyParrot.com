document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const saveProfileBtn = document.getElementById('save-profile');
    const displayNameInput = document.getElementById('displayName');
    const profileAlert = document.getElementById('profile-alert');
    
    // Save profile data
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', async function() {
            // Hide any previous alerts
            profileAlert.textContent = '';
            profileAlert.style.display = 'none';
            
            // Get values
            const displayName = displayNameInput.value.trim();
            
            // Validate
            if (!displayName) {
                showAlert('Please enter your display name', 'error');
                return;
            }
            
            // Prepare data
            const profileData = {
                displayName: displayName
            };
            
            try {
                // Save data
                const response = await fetch('/api/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(profileData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('Profile updated successfully!', 'success');
                    
                    // Update session storage
                    window.sessionStorage.setItem('displayName', displayName);
                    
                    // Update UI
                    const profileName = document.querySelector('.profile-info h1');
                    if (profileName) {
                        profileName.textContent = displayName;
                    }
                } else {
                    showAlert(data.error || 'Failed to update profile', 'error');
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                showAlert('Failed to update profile', 'error');
            }
        });
    }
    
    // Helper function to show alerts
    function showAlert(message, type) {
        profileAlert.textContent = message;
        profileAlert.className = 'alert';
        
        if (type === 'error') {
            profileAlert.classList.add('alert-error');
        } else if (type === 'success') {
            profileAlert.classList.add('alert-success');
        }
        
        profileAlert.style.display = 'block';
    }
}); 