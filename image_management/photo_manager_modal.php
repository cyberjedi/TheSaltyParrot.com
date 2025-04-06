<?php
/**
 * Reusable Photo Management Modal Component
 */
?>
<!-- Photo Management Modal -->
<div id="photo-management-modal" class="photo-management-modal" style="display: none;">
    <div class="photo-management-container">
        <div class="photo-management-header">
            <h3>Manage Your Photos</h3>
            <button class="photo-management-close" id="close-photo-management">&times;</button>
        </div>
        
        <div class="upload-section">
            <h4>Upload New Photo</h4>
            <div id="photo-dropzone" class="upload-dropzone">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Drag and drop image here, or click to select a file</p>
            </div>
            <form id="upload-photo-form" class="upload-form" style="display: none;">
                <input type="file" id="photo-upload" name="image" accept="image/*">
            </form>
        </div>
        
        <h4>Your Photos</h4>
        <div id="user-photos" class="photo-gallery">
            <div class="loading-photos">
                <i class="fas fa-spinner fa-spin"></i> Loading your photos...
            </div>
        </div>
        
        <div class="photo-management-actions">
             <div id="photo-manager-error" class="error-message" style="display: none; margin-bottom: 10px;"></div>
             <div id="photo-manager-success" class="success-message" style="display: none; margin-bottom: 10px;"></div>
            <button id="apply-selected-photo" class="btn" disabled>Use Selected Photo</button>
        </div>
    </div>
</div> 