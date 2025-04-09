import { auth, updatePassword } from './firebase-auth.js'; // Import necessary functions

document.addEventListener('DOMContentLoaded', () => {
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

    // Add modal functionality
    const modals = {
        createParty: document.getElementById('create-party-modal'), 
        joinParty: document.getElementById('join-party-modal'), 
        addWebhook: document.getElementById('add-webhook-modal'),
        editWebhook: document.getElementById('edit-webhook-modal'),
        
        show(modalId) {
            const modal = this[modalId];
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        },
        
        hide(modalId) {
            const modal = this[modalId];
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    };
    // Make modals globally available if needed by inline onclick handlers etc.
    window.modals = modals;

    // --- Helper Function --- 
    function hyphenToCamelCase(str) {
        return str.replace(/-([a-z])/g, (match, letter) => letter.toUpperCase());
    }

    // --- General Modal Close Logic --- 
    document.querySelectorAll('.modal').forEach(modal => {
        // Close on clicking the 'x' span
        const closeButton = modal.querySelector('.close-modal');
        if (closeButton) {
             // console.log(`Adding close listener for modal: #${modal.id}`); 
            closeButton.addEventListener('click', () => {
                // console.log(`Close button clicked for modal: #${modal.id}`); 
                const baseId = modal.id.replace('-modal', ''); 
                const modalId = hyphenToCamelCase(baseId); // <<< CONVERT TO CAMELCASE
                 // console.log(`Extracted modalId: ${modalId}`); 
                 if (modals[modalId] && typeof modals.hide === 'function') {
                     // console.log(`Calling modals.hide('${modalId}')`); 
                     modals.hide(modalId);
                 } else {
                     console.error(`Modal hide failed: baseId='${baseId}', camelCaseId='${modalId}', modals[modalId]=`, modals[modalId]); 
                 }
            });
        } else {
            // console.warn(`No .close-modal found in modal: #${modal.id}`); 
        }
        
        // Close on clicking the modal background (outside content)
         // console.log(`Adding backdrop listener for modal: #${modal.id}`); 
        modal.addEventListener('click', (e) => {
            if (e.target === modal) { 
                 // console.log(`Backdrop clicked for modal: #${modal.id}`); 
                 const baseId = modal.id.replace('-modal', '');
                 const modalId = hyphenToCamelCase(baseId); // <<< CONVERT TO CAMELCASE
                  // console.log(`Extracted modalId: ${modalId}`); 
                 if (modals[modalId] && typeof modals.hide === 'function') {
                      // console.log(`Calling modals.hide('${modalId}')`); 
                     modals.hide(modalId);
                 } else {
                     console.error(`Modal hide (backdrop) failed: baseId='${baseId}', camelCaseId='${modalId}', modals[modalId]=`, modals[modalId]); 
                 }
            }
        });
    });
    // --- End General Modal Close Logic ---

    // --- Webhook Management --- 
    const webhookApiUrl = '/discord/webhook_api.php';
    const webhookList = document.getElementById('webhook-list');
    const webhookListLoading = document.getElementById('webhook-list-loading');
    const webhookListEmpty = document.getElementById('webhook-list-empty');
    const addWebhookForm = document.getElementById('add-webhook-form');
    const testWebhookBtn = document.getElementById('test-webhook-btn');
    const webhookAlert = document.getElementById('webhook-alert');
    const showAddWebhookModalBtn = document.getElementById('show-add-webhook-modal-btn');
    const modalWebhookAlert = document.getElementById('modal-webhook-alert');
    const editWebhookForm = document.getElementById('edit-webhook-form');
    const modalEditWebhookAlert = document.getElementById('modal-edit-webhook-alert');
    let currentWebhooks = [];

    // Function to show webhook alerts
    function showWebhookAlert(message, type = 'error', target = 'page') {
        const alertElement = target === 'modal-add' ? modalWebhookAlert : 
                             target === 'modal-edit' ? modalEditWebhookAlert : webhookAlert;
        alertElement.textContent = message;
        alertElement.className = `alert alert-${type}`;
        alertElement.style.display = 'block';
    }
    
    function hideWebhookAlert(target = 'both') {
         if(target === 'page' || target === 'both') webhookAlert.style.display = 'none';
         if(target === 'modal-add' || target === 'both') modalWebhookAlert.style.display = 'none';
         if(target === 'modal-edit' || target === 'both') modalEditWebhookAlert.style.display = 'none';
    }

    // Function to render the webhook list
    function renderWebhookList(webhooks) {
        currentWebhooks = webhooks;
        webhookList.innerHTML = ''; // Clear existing list
        if (!webhooks || webhooks.length === 0) {
            webhookListEmpty.style.display = 'block';
            testWebhookBtn.disabled = true; // Disable test button if no webhooks
            return;
        }
        webhookListEmpty.style.display = 'none';
        testWebhookBtn.disabled = false; // Enable test button

        webhooks.forEach(hook => {
            const li = document.createElement('li');
            li.className = 'webhook-item';
            li.dataset.webhookId = hook.id;
            li.dataset.webhookUrl = hook.full_url; // Store full URL for copying
            li.dataset.isDefault = hook.is_default;
            
            // Conditionally create the indicator and set default button HTML
            const indicatorIconHtml = hook.is_default == 1 ? 
                `<i class="fas fa-star webhook-default-icon" title="Default Webhook"></i>` : '';
            
            const setDefaultButtonHtml = hook.is_default != 1 ? `
                <button class="set-default-btn" title="Set as Default">
                    <i class="far fa-star"></i>
                </button>
            ` : '';

            li.innerHTML = `
                <div class="webhook-item-info">
                    <div class="webhook-item-name">
                        ${indicatorIconHtml} <!-- Only show icon if default -->
                        ${hook.server_name || 'Unnamed Server'}
                    </div>
                    <div class="webhook-item-channel">${hook.discord_channel_name || 'Unknown Channel'}</div>
                </div>
                <div class="webhook-actions">
                    ${setDefaultButtonHtml} <!-- Insert the button HTML here -->
                    <button class="edit-webhook-btn" title="Edit Webhook">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="copy-url-btn" title="Copy Webhook URL">
                        <i class="fas fa-copy"></i>
                    </button>
                     <button class="delete-webhook-btn" title="Delete Webhook">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            webhookList.appendChild(li);
        });
    }

    // Function to fetch webhooks
    async function fetchWebhooks() {
        webhookListLoading.style.display = 'block';
        webhookListEmpty.style.display = 'none';
        webhookList.innerHTML = '';
        testWebhookBtn.disabled = true; 
        hideWebhookAlert();

        try {
            const response = await fetch(`${webhookApiUrl}?action=get_webhooks`);
            if (!response.ok) {
                 const errorData = await response.json().catch(() => ({ message: `HTTP error ${response.status}` }));
                 throw new Error(errorData.message || `HTTP error ${response.status}`);
            }
            const data = await response.json();
            if (data.status === 'success') {
                renderWebhookList(data.webhooks);
            } else {
                 throw new Error(data.message || 'Failed to load webhooks');
            }
        } catch (error) {
            console.error('Error fetching webhooks:', error);
            showWebhookAlert('Error loading webhooks: ' + error.message);
            webhookListEmpty.style.display = 'block';
        } finally {
            webhookListLoading.style.display = 'none';
        }
    }

    // Show Add Webhook Modal Button Listener
    if (showAddWebhookModalBtn) {
        showAddWebhookModalBtn.addEventListener('click', () => {
            hideWebhookAlert('modal-add');
            addWebhookForm.reset();
            modals.show('addWebhook');
        });
    }

    // Handle Add Webhook Form Submission (now inside modal)
    if (addWebhookForm) {
        addWebhookForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideWebhookAlert('modal-add');
            const formData = new FormData();
            formData.append('action', 'add_webhook');
            formData.append('webhookUrl', document.getElementById('webhookUrl').value);
            formData.append('serverName', document.getElementById('serverName').value);
            formData.append('discordChannelName', document.getElementById('discordChannelName').value);

            const submitButton = addWebhookForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                 const response = await fetch(webhookApiUrl, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    showNotification(data.message || 'Webhook added successfully!', 'success');
                    addWebhookForm.reset();
                    modals.hide('addWebhook');
                    await fetchWebhooks();
                } else {
                    showWebhookAlert(data.message || 'Failed to add webhook.', 'error', 'modal-add');
                }
            } catch (error) {
                console.error('Error adding webhook:', error);
                showWebhookAlert('An error occurred while adding the webhook.', 'error', 'modal-add');
            } finally {
                 submitButton.disabled = false;
                 submitButton.innerHTML = '<i class="fas fa-plus"></i> Add Webhook';
            }
        });
    }

    // Handle Actions within the Webhook List (using event delegation)
    if (webhookList) {
        webhookList.addEventListener('click', async (e) => {
            const targetButton = e.target.closest('button');
            if (!targetButton) return;
            
            const listItem = targetButton.closest('.webhook-item');
            const webhookId = listItem?.dataset.webhookId;
            
            if (!webhookId) return;
            hideWebhookAlert();

            try {
                let action = '';
                let confirmMsg = '';
                let successMsg = '';
                let body = new FormData();
                body.append('webhookId', webhookId);

                if (targetButton.classList.contains('set-default-btn')) {
                     action = 'set_default';
                     successMsg = 'Webhook set as default.';
                     body.append('action', action);
                } else if (targetButton.classList.contains('edit-webhook-btn')) {
                    const webhookToEdit = currentWebhooks.find(h => h.id == webhookId);
                    if (webhookToEdit) {
                        document.getElementById('editWebhookId').value = webhookToEdit.id;
                        document.getElementById('editWebhookUrl').value = webhookToEdit.full_url;
                        document.getElementById('editServerName').value = webhookToEdit.server_name || '';
                        document.getElementById('editDiscordChannelName').value = webhookToEdit.discord_channel_name || '';
                        hideWebhookAlert('modal-edit');
                        modals.show('editWebhook');
                    } else {
                         showWebhookAlert('Could not find webhook data to edit.', 'error', 'page');
                    }
                    return;
                } else if (targetButton.classList.contains('copy-url-btn')) {
                    const urlToCopy = listItem.dataset.webhookUrl;
                    if (urlToCopy && navigator.clipboard) {
                        await navigator.clipboard.writeText(urlToCopy);
                        showNotification('Webhook URL copied to clipboard!', 'success');
                    } else {
                        showWebhookAlert('Failed to copy URL. Browser may not support clipboard API.');
                    }
                    return;
                } else if (targetButton.classList.contains('delete-webhook-btn')) {
                    action = 'delete_webhook';
                    confirmMsg = 'Are you sure you want to delete this webhook? This cannot be undone.';
                     successMsg = 'Webhook deleted successfully.';
                     body.append('action', action);
                }

                if (action && (!confirmMsg || confirm(confirmMsg))) {
                   targetButton.disabled = true;
                   targetButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                   
                    const response = await fetch(webhookApiUrl, {
                        method: 'POST',
                        body: body
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        showNotification(data.message || successMsg, 'success');
                        await fetchWebhooks();
                    } else {
                        showWebhookAlert(data.message || 'Action failed.');
                        targetButton.disabled = false; 
                        if(action === 'set_default') targetButton.innerHTML = '<i class="fas fa-star"></i>';
                        if(action === 'delete_webhook') targetButton.innerHTML = '<i class="fas fa-trash"></i>'; 
                    }
                } 
            } catch (error) {
                console.error('Error performing webhook action:', error);
                showWebhookAlert('An error occurred.');
                 targetButton.disabled = false;
                 if(targetButton.classList.contains('set-default-btn')) targetButton.innerHTML = '<i class="fas fa-star"></i>';
                 if(targetButton.classList.contains('delete-webhook-btn')) targetButton.innerHTML = '<i class="fas fa-trash"></i>';
            }
        });
    }

    // Handle Test Default Webhook Button
    if (testWebhookBtn) {
        testWebhookBtn.addEventListener('click', async () => {
             hideWebhookAlert();
             testWebhookBtn.disabled = true;
             testWebhookBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';

             try {
                 let body = new FormData();
                 body.append('action', 'test_webhook');
                 body.append('webhookId', '-1');

                 const response = await fetch(webhookApiUrl, {
                    method: 'POST',
                    body: body
                });
                const data = await response.json();

                if (data.status === 'success') {
                    showNotification(data.message || 'Test message sent successfully!', 'success');
                } else {
                     showWebhookAlert(data.message || 'Failed to send test message.');
                }
             } catch (error) {
                  console.error('Error testing webhook:', error);
                  showWebhookAlert('An error occurred while testing the webhook.');
             } finally {
                  testWebhookBtn.disabled = false;
                  testWebhookBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Test Default Webhook';
             }
        });
    }
    
    // Initial fetch of webhooks if the container exists
    if(webhookList) {
        fetchWebhooks();
    }
    // --- End Webhook Management ---

    // --- Photo Manager Integration --- 
    function handleProfilePhotoUpdate(photoUrl) {
        const profileApiUrl = '/image_management/update_profile_photo.php';
        
        fetch(profileApiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ photoUrl: photoUrl })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                throw new Error(data.message || `Update failed with status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const wrapperElement = document.querySelector('.profile-image-wrapper');
                if (wrapperElement) {
                    let profileImageElement = wrapperElement.querySelector('.profile-image');
                    if (profileImageElement) {
                        profileImageElement.src = '/' + photoUrl;
                    } else {
                        const placeholderElement = wrapperElement.querySelector('.profile-image-placeholder');
                        if (placeholderElement) {
                            placeholderElement.remove();
                        }
                        const newImg = document.createElement('img');
                        newImg.src = '/' + photoUrl;
                        newImg.alt = 'Profile Photo';
                        newImg.className = 'profile-image';
                        wrapperElement.appendChild(newImg);
                    }
                } else {
                    console.error('Could not find profile image wrapper to update photo.');
                }
                showNotification('Profile photo updated successfully!', 'success');
            } else {
                throw new Error(data.message || 'Update failed');
            }
        })
        .catch(error => {
            console.error('Error updating profile photo via manager:', error);
            showNotification('Failed to apply profile photo: ' + error.message, 'error');
        });
    }

    if (window.photoManager) {
        window.photoManager.init(handleProfilePhotoUpdate);
    }

    const profileImageBtn = document.getElementById('profile-image-btn');
    if (profileImageBtn && window.photoManager) {
        profileImageBtn.addEventListener('click', () => {
            window.photoManager.show('profile');
        });
    }
    // --- End Photo Manager Integration ---

    // --- Party Management ---
    const partySection = {
        partyLoading: document.getElementById('party-loading'),
        partyInfo: document.getElementById('party-info'),
        partyForms: document.getElementById('party-forms'),

        async init() {
             this.partyLoading = document.getElementById('party-loading');
             this.partyInfo = document.getElementById('party-info');
             this.partyForms = document.getElementById('party-forms');
            await this.loadPartyInfo();
            this.setupEventListeners();
        },

        async loadPartyInfo() {
             this.partyLoading.style.display = 'block';
             try {
                const response = await fetch('/party/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_party'
                });

                const data = await response.json();
                 if (data.success) {
                    if (data.party) {
                        await this.displayPartyInfo(data.party);
                    } else {
                        this.showPartyForms();
                    }
                } else {
                    throw new Error(data.error || 'Failed to get party data');
                }
            } catch (error) {
                console.error('Error loading party info:', error);
                this.showError('Failed to load party information: ' + error.message);
                 this.showPartyForms();
            } finally {
                if(this.partyLoading) this.partyLoading.style.display = 'none';
            }
        },

        async displayPartyInfo(party) {
             const members = await this.getPartyMembers(party.id);
             
             if(!this.partyInfo) {
                  console.error("Party info container not found!");
                  return;
             }

             const currentUserUid = document.body.dataset.userUid;
             const isCreator = party.creator_id === currentUserUid;
             const isGM = party.game_master_id === currentUserUid;
             const isMember = members.some(m => m.uid === currentUserUid);

            let membersHtml = members.map(member => `
                <div class="party-member">
                    <div class="party-member-avatar-wrapper">
                        <img src="${member.photo_url || '/img/default-avatar.png'}" alt="${member.display_name}" class="party-member-avatar">
                    </div>
                    <div class="party-member-info">
                        <p class="party-member-name">
                            ${member.display_name} 
                            <span class="member-role-icons">
                                ${member.uid === party.creator_id ? '<i class="fas fa-shield-alt owner-icon" title="Party Owner"></i>' : ''}
                                ${member.uid === party.game_master_id ? '<i class="fas fa-crown gm-icon" title="Game Master"></i>' : ''}
                            </span>
                        </p>
                        ${member.activeCharacterName ? `<p class="party-member-character">Playing as: ${member.activeCharacterName}</p>` : '<p class="party-member-character" style="opacity: 0.6;">No active character</p>'}
                   </div>
                    ${isCreator ? 
                        `<div class="party-member-actions-container">
                            ${member.uid !== currentUserUid ? 
                                `<button class="btn-kick-member" data-member-id="${member.uid}" title="Remove Member"><i class="fas fa-times"></i></button>` 
                                : ''}
                            ${member.uid !== party.game_master_id ? 
                                `<button class="btn-set-gm" data-member-id="${member.uid}" title="Set as Game Master"><i class="fas fa-crown"></i></button>` 
                                : ''}
                         </div>` 
                        : ''}
                </div>
            `).join('');

            // Find the specific containers
            const detailsContainer = this.partyInfo.querySelector('#party-details-content');
            const membersContainer = this.partyInfo.querySelector('#party-members-list');

            if (!detailsContainer || !membersContainer) {
                console.error("Could not find party details or members list containers!");
                return;
            }

            // Update party details section
            detailsContainer.innerHTML = ` 
                <h3>${party.name}</h3>
                <p>Party Code: <span class="party-code">${party.code}</span> 
                   <button id="copy-party-code" class="btn-icon" title="Copy Code"><i class="fas fa-copy"></i></button>
                </p>
                ${isCreator || isGM ? 
                    `<div class="user-roles">
                        <p>Your Role${isCreator && isGM ? 's' : ''}:
                            ${isCreator ? '<span class="role-badge owner-badge"><i class="fas fa-shield-alt"></i> Owner</span>' : ''}
                            ${isGM ? '<span class="role-badge gm-badge"><i class="fas fa-crown"></i> Game Master</span>' : ''}
                        </p>
                    </div>` 
                : ''}
            `;
            
            // Update members list section
            membersContainer.innerHTML = membersHtml;
             
            // Show the main party info container
            this.partyInfo.style.display = 'block';
            if(this.partyForms) this.partyForms.style.display = 'none';

            // Show the member action buttons div if the user is a member
            const memberActionsDiv = this.partyInfo.querySelector('.party-member-actions');
            if (isMember && memberActionsDiv) {
                 memberActionsDiv.style.display = 'flex'; // Use flex to space buttons
            }

            // Add event listeners to buttons WITHIN the specific containers/divs
            detailsContainer.querySelector('#copy-party-code')?.addEventListener('click', () => {
                navigator.clipboard.writeText(party.code).then(() => {
                    showNotification('Party code copied!', 'success');
                }).catch(err => {
                     showNotification('Failed to copy code', 'error');
                });
            });

            // Add listeners for Rename/Leave buttons
            memberActionsDiv?.querySelector('#leave-party-btn')?.addEventListener('click', () => this.leaveParty(party.id));
            memberActionsDiv?.querySelector('#rename-party-btn')?.addEventListener('click', () => {
                this.showRenameModal(party.id, party.name); // Call function to show rename modal
            });

            // Add listeners for GM actions (kick/set GM) within the members list
            membersContainer.querySelectorAll('.btn-kick-member').forEach(btn => {
                btn.addEventListener('click', (e) => this.removeMember(party.id, e.currentTarget.dataset.memberId));
            });
            membersContainer.querySelectorAll('.btn-set-gm').forEach(btn => {
                btn.addEventListener('click', (e) => this.setGameMaster(party.id, e.currentTarget.dataset.memberId));
            });
        },

        async getPartyMembers(partyId) {
            try {
                const response = await fetch('/party/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_members&party_id=${partyId}`
                });

                const data = await response.json();
                if (data.success) {
                    const members = await Promise.all(data.members.map(async (member) => {
                        try {
                            const charResponse = await fetch(`/api/get_active_character.php?user_id=${member.uid}`); 
                            const charData = await charResponse.json();
                            
                            if (charData.success && charData.character) {
                                return {
                                    ...member,
                                    activeCharacterName: charData.character.name,
                                };
                            }
                        } catch (err) {
                            console.error(`Error getting active character for ${member.uid}:`, err);
                        }
                        return member;
                    }));
                    return members;
                }
                return [];
            } catch (error) {
                console.error('Error getting party members:', error);
                return [];
            }
        },

        showPartyForms() {
             if(this.partyInfo) this.partyInfo.style.display = 'none';
             if(this.partyForms) this.partyForms.style.display = 'block';
        },

        setupEventListeners() {
            const createBtn = document.getElementById('create-party-btn');
             if(createBtn) {
                 createBtn.addEventListener('click', () => {
                    modals.show('createParty');
                });
             }

            const joinBtn = document.getElementById('join-party-btn');
              if(joinBtn) {
                joinBtn.addEventListener('click', () => {
                    modals.show('joinParty');
                });
             }

            const createForm = document.getElementById('create-party-form');
             if(createForm) {
                 createForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const name = document.getElementById('party-name').value;
                    const submitBtn = createForm.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    
                    try {
                        const response = await fetch('/party/api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=create&name=${encodeURIComponent(name)}`
                        });

                        const data = await response.json();
                        if (data.success) {
                            modals.hide('createParty');
                            await this.loadPartyInfo();
                        } else {
                            throw new Error(data.error || 'Unknown error creating party');
                        }
                    } catch (error) {
                        console.error('Error creating party:', error);
                        this.showError('Failed to create party: ' + error.message);
                    } finally {
                         submitBtn.disabled = false;
                    }
                });
            }

            const joinForm = document.getElementById('join-party-form');
              if(joinForm) {
                joinForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const code = document.getElementById('party-code').value;
                    const submitBtn = joinForm.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    
                    try {
                        const response = await fetch('/party/api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=join&code=${encodeURIComponent(code)}`
                        });

                        const data = await response.json();
                        if (data.success) {
                            modals.hide('joinParty');
                            await this.loadPartyInfo();
                        } else {
                            throw new Error(data.error || 'Unknown error joining party');
                        }
                    } catch (error) {
                        console.error('Error joining party:', error);
                        this.showError('Failed to join party: ' + error.message);
                    } finally {
                         submitBtn.disabled = false;
                    }
                });
             }

            // Add listener for rename form
            document.getElementById('rename-party-form')?.addEventListener('submit', (e) => this.handleRenamePartySubmit(e));
        },

        async removeMember(partyId, memberId) {
            if (!confirm('Are you sure you want to remove this member?')) {
                return;
            }

            try {
                const response = await fetch('/party/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove_member&party_id=${partyId}&member_id=${memberId}`
                });

                const data = await response.json();
                if (data.success) {
                    showNotification('Member removed.', 'success');
                    await this.loadPartyInfo();
                } else {
                    throw new Error(data.error || 'Failed to remove member');
                }
            } catch (error) {
                console.error('Error removing member:', error);
                this.showError('Failed to remove member: ' + error.message);
            }
        },

        async leaveParty(partyId) {
            if (!confirm('Are you sure you want to leave this party?')) {
                return;
            }

            try {
                const response = await fetch('/party/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=leave_party&party_id=${partyId}`
                });

                const data = await response.json();
                if (data.success) {
                    showNotification('You have left the party.', 'success');
                    await this.loadPartyInfo();
                } else {
                    throw new Error(data.error || 'Failed to leave party');
                }
            } catch (error) {
                console.error('Error leaving party:', error);
                this.showError('Failed to leave party: ' + error.message);
            }
        },

        async setGameMaster(partyId, gmUserId) {
            if (!confirm('Are you sure you want to set this member as Game Master?')) {
                return;
            }

            try {
                const response = await fetch('/party/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=set_game_master&party_id=${partyId}&gm_user_id=${gmUserId}`
                });

                const data = await response.json();
                if (data.success) {
                     showNotification('Game Master updated.', 'success');
                    await this.loadPartyInfo();
                } else {
                    throw new Error(data.error || 'Failed to set GM');
                }
            } catch (error) {
                console.error('Error setting game master:', error);
                this.showError('Failed to set game master: ' + error.message);
            }
        },

        showError(message) {
            if(typeof showNotification === 'function') {
                showNotification(message, 'error');
            } else {
                 alert(message);
            }
             console.error('Party Error Displayed:', message); 
        },

        // Function to show the rename party modal
        showRenameModal(partyId, partyName) {
            const modal = document.getElementById('rename-party-modal');
            const form = document.getElementById('rename-party-form');
            const partyIdInput = document.getElementById('rename-party-id');
            const partyNameInput = document.getElementById('rename-party-name');
            const alertDiv = document.getElementById('rename-party-alert');

            if (!modal || !form || !partyIdInput || !partyNameInput || !alertDiv) {
                console.error('Could not find all elements for rename party modal.');
                return;
            }

            partyIdInput.value = partyId;
            partyNameInput.value = partyName; // Pre-fill with current name
            alertDiv.style.display = 'none'; // Hide previous alerts

            modal.style.display = 'flex'; // Show the modal (using flex as per other modals)
        },

        // Function to handle party rename form submission
        async handleRenamePartySubmit(event) {
            event.preventDefault();
            const form = event.target;
            const partyId = form.elements['rename-party-id'].value;
            const newName = form.elements['rename-party-name'].value.trim();
            const submitButton = form.querySelector('#save-party-name-btn');
            const alertDiv = document.getElementById('rename-party-alert');

            if (!newName) {
                alertDiv.textContent = 'Party name cannot be empty.';
                alertDiv.className = 'alert alert-error';
                alertDiv.style.display = 'block';
                return;
            }

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            alertDiv.style.display = 'none';

            try {
                const response = await fetch('/party/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded', 
                    },
                    body: `action=rename_party&partyId=${encodeURIComponent(partyId)}&newName=${encodeURIComponent(newName)}`
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Party renamed successfully!', 'success');
                    document.getElementById('rename-party-modal').style.display = 'none';
                    // Reload party info to show the new name
                    await this.loadPartyInfo(); 
                } else {
                    throw new Error(data.error || 'Failed to rename party.');
                }
            } catch (error) {
                console.error('Error renaming party:', error);
                alertDiv.textContent = `Error: ${error.message}`;
                alertDiv.className = 'alert alert-error';
                alertDiv.style.display = 'block';
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Save Name';
            }
        },

    };

    partySection.init();

    // Handle Edit Webhook Form Submission
    if (editWebhookForm) {
        editWebhookForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideWebhookAlert('modal-edit');
            const formData = new FormData(editWebhookForm);
            formData.append('action', 'edit_webhook');

            const submitButton = editWebhookForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                const response = await fetch(webhookApiUrl, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    showNotification(data.message || 'Webhook updated successfully!', 'success');
                    modals.hide('editWebhook');
                    await fetchWebhooks();
                } else {
                    showWebhookAlert(data.message || 'Failed to update webhook.', 'error', 'modal-edit');
                }
            } catch (error) {
                console.error('Error updating webhook:', error);
                showWebhookAlert('An error occurred while saving the webhook.', 'error', 'modal-edit');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save"></i> Save Changes';
            }
        });
    }

    const changePasswordButton = document.getElementById('change-password-btn');
    const changePasswordModal = document.getElementById('change-password-modal');
    const changePasswordForm = document.getElementById('change-password-form');
    const modalPasswordInput = document.getElementById('modal-password');
    const modalConfirmPasswordInput = document.getElementById('modal-confirm-password');
    const updatePasswordBtn = document.getElementById('update-password-btn');
    const changePasswordAlert = document.getElementById('change-password-alert');

    // Function to display alerts in the modal
    function showPasswordAlert(message, type = 'danger') {
        changePasswordAlert.textContent = message;
        changePasswordAlert.className = `alert alert-${type}`;
        changePasswordAlert.style.display = 'block';
    }

    // Function to clear alerts
    function clearPasswordAlert() {
        changePasswordAlert.textContent = '';
        changePasswordAlert.style.display = 'none';
    }

    // Show the change password modal
    if (changePasswordButton && changePasswordModal) {
        changePasswordButton.addEventListener('click', () => {
            clearPasswordAlert();
            modalPasswordInput.value = '';
            modalConfirmPasswordInput.value = '';
            changePasswordModal.style.display = 'block';
        });
    }

    // Handle password change form submission using imported functions
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearPasswordAlert();
            updatePasswordBtn.disabled = true;
            updatePasswordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            const newPassword = modalPasswordInput.value;
            const confirmPassword = modalConfirmPasswordInput.value;

            if (newPassword !== confirmPassword) {
                showPasswordAlert('Passwords do not match.');
                updatePasswordBtn.disabled = false;
                updatePasswordBtn.innerHTML = 'Update Password';
                return;
            }

            if (newPassword.length < 6) {
                showPasswordAlert('Password must be at least 6 characters long.');
                 updatePasswordBtn.disabled = false;
                updatePasswordBtn.innerHTML = 'Update Password';
                return;
            }

            const user = auth.currentUser;

            if (user) {
                try {
                    await updatePassword(user, newPassword);
                    showPasswordAlert('Password updated successfully!', 'success');
                    
                    setTimeout(() => {
                         if (changePasswordModal) {
                            changePasswordModal.style.display = 'none'; 
                         }
                         clearPasswordAlert();
                    }, 2000);
                } catch (error) {
                    console.error('Error updating password:', error);
                    let errorMessage = `Error updating password: ${error.message}`;
                    if (error.code === 'auth/requires-recent-login') {
                        errorMessage = 'For security, please log out and log back in before changing your password.';
                    }
                    showPasswordAlert(errorMessage);
                }
            } else {
                showPasswordAlert('No user is currently signed in. Please refresh the page.');
            }

            updatePasswordBtn.disabled = false;
            updatePasswordBtn.innerHTML = 'Update Password';
        });
    }

}); 