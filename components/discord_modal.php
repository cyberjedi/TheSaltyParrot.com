<\!-- Discord webhook modal -->
<div id="discord-webhook-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Send to Discord</h3>
        
        <div id="discord-modal-content">
            <div id="webhook-content-preview">
                <p>Content will be sent to your Discord channel.</p>
            </div>
            
            <div id="webhook-loading" style="text-align: center; display: none;">
                <p><i class="fas fa-spinner fa-spin"></i> Loading webhooks...</p>
            </div>
            
            <div id="webhook-error" style="display: none; color: #d33; margin: 10px 0;">
                <p><i class="fas fa-exclamation-triangle"></i> <span id="webhook-error-message"></span></p>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
            <button type="button" class="btn btn-discord" id="send-to-discord-btn" disabled>
                <i class="fab fa-discord"></i> Send to Discord
            </button>
        </div>
    </div>
</div>
