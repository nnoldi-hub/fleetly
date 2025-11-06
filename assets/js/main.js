// assets/js/main.js - JavaScript principal (simplified for production stability)
document.addEventListener('DOMContentLoaded', function() {
    console.log('Main.js: Loading minimal production version');
    
    // Bootstrap tooltips/popovers
    try {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
        
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
            new bootstrap.Popover(el);
        });
        
        console.log('Bootstrap components initialized');
    } catch (e) {
        console.error('Bootstrap error:', e);
    }
    
    // Form validation
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Delete confirmation
    document.querySelectorAll('.btn-delete, .delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Sigur doriti sa stergeti?')) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    console.log('Main.js: Ready');
});

// Global namespace for compatibility
window.FleetManagement = {
    noDiacritics: false,
    reinitialize: function() {
        console.log('Reinitialize (no-op)');
    }
};
