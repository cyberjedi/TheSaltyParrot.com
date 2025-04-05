/**
 * Shared utility functions for The Salty Parrot website
 */

/**
 * Displays a notification message to the user.
 * Assumes a div with id="notification-area" exists in the HTML.
 *
 * @param {string} message The message to display.
 * @param {string} type The type of notification ('success', 'error', 'info', 'warning'). Defaults to 'info'.
 * @param {number} duration Time in milliseconds to display the notification. Defaults to 5000ms. Set to 0 for permanent.
 */
function showNotification(message, type = 'info', duration = 5000) {
    const notificationArea = document.getElementById('notification-area');

    if (!notificationArea) {
        console.warn('Notification area (#notification-area) not found. Falling back to alert().');
        alert(`${type.toUpperCase()}: ${message}`);
        return;
    }

    // Create the alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`; // Using Bootstrap-like classes
    alertDiv.setAttribute('role', 'alert');
    alertDiv.style.margin = '5px 0'; // Add some spacing between multiple alerts

    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Add the alert to the notification area
    notificationArea.appendChild(alertDiv);

    // Auto-dismiss if duration is set
    if (duration > 0) {
        setTimeout(() => {
            // Use Bootstrap's dismiss method if available, otherwise just remove
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getInstance(alertDiv);
                if (bsAlert) {
                    bsAlert.close();
                } else {
                    alertDiv.remove();
                }
            } else {
                 alertDiv.remove();
            }
        }, duration);
    }

     // Ensure the close button works even without full Bootstrap JS
    const closeButton = alertDiv.querySelector('.btn-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            alertDiv.remove();
        });
    }
}

// You can add other utility functions here in the future. 