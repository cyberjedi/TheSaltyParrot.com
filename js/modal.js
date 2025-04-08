document.addEventListener('DOMContentLoaded', () => {
    // Generic modal close logic
    const closeButtons = document.querySelectorAll('.close-modal');
    const modals = document.querySelectorAll('.modal');

    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal-id');
            const modalToClose = document.getElementById(modalId);
            if (modalToClose) {
                modalToClose.style.display = 'none';
            }
        });
    });

    // Close modal if clicked outside the modal content
    modals.forEach(modal => {
        modal.addEventListener('click', (event) => {
            // Check if the click was directly on the modal background (not the content)
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Close modal with the Escape key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    modal.style.display = 'none';
                }
            });
        }
    });
}); 