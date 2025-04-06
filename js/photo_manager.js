/**
 * js/photo_manager.js
 * Shared JavaScript module for photo management modal
 */

let photoManagementModal = null;
let closePhotoManagement = null;
let photoDropzone = null;
let photoUploadInput = null;
let userPhotosContainer = null;
let applySelectedPhotoBtn = null;
let photoManagerError = null;
let photoManagerSuccess = null;

let selectedPhotoUrl = null;
let applyCallback = null; // Function to call when photo is selected
let currentContext = null; // 'profile' or 'sheet'
let currentSheetId = null; // Only used when context is 'sheet'

function initPhotoManager(callback) {
    applyCallback = callback;

    // Get DOM elements
    photoManagementModal = document.getElementById('photo-management-modal');
    closePhotoManagement = document.getElementById('close-photo-management');
    photoDropzone = document.getElementById('photo-dropzone');
    photoUploadInput = document.getElementById('photo-upload');
    userPhotosContainer = document.getElementById('user-photos');
    applySelectedPhotoBtn = document.getElementById('apply-selected-photo');
    photoManagerError = document.getElementById('photo-manager-error');
    photoManagerSuccess = document.getElementById('photo-manager-success');

    if (!photoManagementModal || !closePhotoManagement || !photoDropzone || !photoUploadInput || !userPhotosContainer || !applySelectedPhotoBtn || !photoManagerError || !photoManagerSuccess) {
        console.error('Photo Manager Error: Could not find all required modal elements. Make sure components/photo_manager_modal.php is included.');
        return;
    }

    // --- Event Listeners --- 

    // Close button
    closePhotoManagement.addEventListener('click', () => hidePhotoManager());

    // Click outside modal
    window.addEventListener('click', (event) => {
        if (event.target === photoManagementModal) {
            hidePhotoManager();
        }
    });

    // Drag and drop / file selection
    photoDropzone.addEventListener('click', () => photoUploadInput.click());
    photoDropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        photoDropzone.classList.add('drag-over');
    });
    photoDropzone.addEventListener('dragleave', () => photoDropzone.classList.remove('drag-over'));
    photoDropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        photoDropzone.classList.remove('drag-over');
        if (e.dataTransfer.files.length) {
            photoUploadInput.files = e.dataTransfer.files;
            uploadPhoto(e.dataTransfer.files[0]);
        }
    });
    photoUploadInput.addEventListener('change', () => {
        if (photoUploadInput.files.length) {
            uploadPhoto(photoUploadInput.files[0]);
        }
    });

    // Apply button
    applySelectedPhotoBtn.addEventListener('click', () => {
        if (selectedPhotoUrl && applyCallback) {
            applyCallback(selectedPhotoUrl); // Call the specific callback 
            hidePhotoManager();
        }
    });
}

function showPhotoManager(context = 'profile', sheetId = null) {
    if (!photoManagementModal) {
        console.error('Photo Manager not initialized or modal missing.');
        return;
    }
    currentContext = context;
    currentSheetId = sheetId; // Store sheetId if context is 'sheet'
    selectedPhotoUrl = null;
    applySelectedPhotoBtn.disabled = true;
    photoManagerError.style.display = 'none';
    photoManagerSuccess.style.display = 'none';
    photoDropzone.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><p>Drag and drop image here, or click to select a file</p>'; // Reset dropzone
    photoManagementModal.style.display = 'flex';
    loadUserPhotos();
}

function hidePhotoManager() {
    if (photoManagementModal) {
        photoManagementModal.style.display = 'none';
    }
}

function displayPhotoManagerMessage(message, isError = true) {
    const el = isError ? photoManagerError : photoManagerSuccess;
    const otherEl = isError ? photoManagerSuccess : photoManagerError;
    el.textContent = message;
    el.style.display = 'block';
    otherEl.style.display = 'none';
    // Auto-hide after a few seconds
    setTimeout(() => {
         el.style.display = 'none';
    }, 4000);
}

// --- API Interaction Functions --- 

function loadUserPhotos() {
    userPhotosContainer.innerHTML = '<div class="loading-photos"><i class="fas fa-spinner fa-spin"></i> Loading your photos...</div>';
    photoManagerError.style.display = 'none';
    photoManagerSuccess.style.display = 'none';

    fetch('/sheets/api/get_user_photos.php') // Use root-relative path
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load photos list');
            }
            return response.json();
        })
        .then(data => {
            userPhotosContainer.innerHTML = ''; // Clear loading
            if (data.success) {
                if (data.photos.length === 0) {
                    userPhotosContainer.innerHTML = '<div class="no-photos">You haven\'t uploaded any photos yet.</div>';
                } else {
                    data.photos.forEach(photo => {
                        const photoItem = document.createElement('div');
                        photoItem.className = 'photo-item';
                        photoItem.dataset.url = photo.url; // Expecting root-relative URL

                        photoItem.innerHTML = `
                            <img src="/${photo.url}" alt="User photo" onerror="this.style.display='none'; this.parentElement.classList.add('img-error')">
                            <div class="photo-actions">
                                <button class="photo-action-btn photo-action-delete" title="Delete Photo">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="img-error-indicator">X</div>
                        `;

                        // Select photo on click
                        photoItem.addEventListener('click', (e) => {
                            if (!e.target.closest('.photo-action-btn')) {
                                document.querySelectorAll('#user-photos .photo-item').forEach(item => {
                                    item.classList.remove('selected');
                                });
                                photoItem.classList.add('selected');
                                selectedPhotoUrl = photoItem.dataset.url;
                                applySelectedPhotoBtn.disabled = false;
                            }
                        });

                        // Delete button action
                        const deleteBtn = photoItem.querySelector('.photo-action-delete');
                        deleteBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            if (confirm('Are you sure you want to delete this photo? This might remove it from character sheets currently using it.')) {
                                deletePhoto(photo.url); // Pass URL to delete function
                            }
                        });
                        userPhotosContainer.appendChild(photoItem);
                    });
                }
            } else {
                 displayPhotoManagerMessage(data.error || 'Failed to load photos', true);
                userPhotosContainer.innerHTML = '<div class="no-photos error-message">Could not load photos.</div>';
            }
        })
        .catch(error => {
            console.error('Error loading photos:', error);
            displayPhotoManagerMessage('Error loading photos: ' + error.message, true);
            userPhotosContainer.innerHTML = '<div class="no-photos error-message">Could not load photos.</div>';
        });
}

function uploadPhoto(file) {
    if (!file || !file.type.match('image.*')) {
        displayPhotoManagerMessage('Please select an image file (JPEG, PNG, GIF)', true);
        return;
    }

    const formData = new FormData();
    formData.append('image', file);
    // IMPORTANT: If uploading in the context of a specific sheet, add its ID
    if (currentContext === 'sheet' && currentSheetId) {
         formData.append('sheet_id', currentSheetId); 
    }

    photoDropzone.innerHTML = '<i class="fas fa-spinner fa-spin"></i><p>Uploading...</p>';
    displayPhotoManagerMessage('', false); // Clear previous messages

    fetch('/sheets/api/upload_photo.php', { // Use root-relative path
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Try to parse JSON even if response is not ok, to get error message
        return response.json().then(data => {
            if (!response.ok) {
                // Throw an error with the message from JSON if available
                throw new Error(data.error || `Upload failed with status: ${response.status}`);
            }
            return data; // Return parsed JSON data on success
        });
    })
    .then(data => {
        if (data.success) {
            photoDropzone.innerHTML = '<i class="fas fa-check"></i><p>Upload successful!</p>';
            displayPhotoManagerMessage('Upload successful!', false);
            setTimeout(() => {
                photoDropzone.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><p>Drag and drop image here, or click to select a file</p>';
            }, 2000);
            loadUserPhotos(); // Refresh the gallery
        } else {
            // This case might not be reached if response.ok was false, but handle defensively
            throw new Error(data.error || 'Upload failed but server reported success?');
        }
    })
    .catch(error => {
        console.error('Error uploading photo:', error);
        displayPhotoManagerMessage('Upload Error: ' + error.message, true);
        photoDropzone.innerHTML = `<i class="fas fa-exclamation-triangle"></i><p>Upload Error</p>`;
        setTimeout(() => {
            photoDropzone.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><p>Drag and drop image here, or click to select a file</p>';
        }, 3000);
    });
}

// Delete a photo - NOTE: Deleting by URL might be fragile. Needs server-side logic.
// The server-side script sheets/api/delete_photo.php currently doesn't exist.
// We need to create it or adapt this logic.
function deletePhoto(photoUrl) {
    displayPhotoManagerMessage('Deleting...', false);
    
    // *** TODO: Implement sheets/api/delete_photo.php on the server ***
    // It should take photoUrl, find the actual file, delete it, 
    // and potentially update any database records (users.photo_url, character_sheets.image_path)
    // that reference this URL, setting them to null or a default.
    
    /* Example Fetch (needs server endpoint):
    fetch('/sheets/api/delete_photo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ photoUrl: photoUrl })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPhotoManagerMessage('Photo deleted.', false);
            loadUserPhotos(); // Refresh gallery
        } else {
            throw new Error(data.error || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Error deleting photo:', error);
        displayPhotoManagerMessage('Error deleting photo: ' + error.message, true);
    });
    */
    
    // Placeholder until server endpoint exists:
    console.warn('Delete photo functionality requires server-side implementation at /sheets/api/delete_photo.php');
    displayPhotoManagerMessage('Delete function not yet implemented.', true); 
}

// Make functions available globally or export if using modules more formally
window.photoManager = {
    init: initPhotoManager,
    show: showPhotoManager,
    hide: hidePhotoManager
}; 