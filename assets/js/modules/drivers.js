/**
 * Drivers Module
 * Handles driver management functionality
 */
const DriversModule = {
    init() {
        this.bindEvents();
        this.initDataTable();
        this.initValidation();
    },

    bindEvents() {
        // Delete driver confirmation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-driver')) {
                e.preventDefault();
                const driverId = e.target.dataset.driverId;
                const driverName = e.target.dataset.driverName;
                this.confirmDelete(driverId, driverName);
            }
        });

        // Driver form submission
        const driverForm = document.querySelector('#driverForm');
        if (driverForm) {
            driverForm.addEventListener('submit', (e) => {
                if (!this.validateDriverForm(driverForm)) {
                    e.preventDefault();
                }
            });
        }

        // License validation
        const licenseInput = document.querySelector('#license_number');
        if (licenseInput) {
            licenseInput.addEventListener('blur', () => {
                this.validateLicense(licenseInput.value);
            });
        }

        // Phone number formatting
        const phoneInput = document.querySelector('#phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                e.target.value = this.formatPhoneNumber(e.target.value);
            });
        }
    },

    initDataTable() {
        const table = document.querySelector('#driversTable');
        if (table && typeof DataTable !== 'undefined') {
            new DataTable(table, {
                pageLength: 25,
                order: [[1, 'asc']], // Sort by name
                columnDefs: [
                    { orderable: false, targets: -1 } // Disable sorting for actions column
                ],
                language: {
                    url: '/assets/js/dataTables.romanian.json'
                }
            });
        }
    },

    initValidation() {
        // License expiry warning
        const expiryInputs = document.querySelectorAll('.license-expiry');
        expiryInputs.forEach(input => {
            input.addEventListener('change', () => {
                this.checkLicenseExpiry(input);
            });
        });
    },

    confirmDelete(driverId, driverName) {
        const message = `Sunteți sigur că doriți să ștergeți șoferul "${driverName}"?`;
        
        if (typeof Modal !== 'undefined') {
            Modal.confirm('Confirmare ștergere', message, () => {
                this.deleteDriver(driverId);
            });
        } else {
            if (confirm(message)) {
                this.deleteDriver(driverId);
            }
        }
    },

    deleteDriver(driverId) {
        fetch('/drivers/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: driverId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove row from table
                const row = document.querySelector(`tr[data-driver-id="${driverId}"]`);
                if (row) {
                    row.remove();
                }
                
                if (typeof notifications !== 'undefined') {
                    notifications.success('Șoferul a fost șters cu succes');
                }
            } else {
                throw new Error(data.message || 'Eroare la ștergerea șoferului');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error(error.message);
            }
        });
    },

    validateDriverForm(form) {
        let isValid = true;
        const errors = [];

        // Required fields
        const requiredFields = ['name', 'license_number', 'license_expiry'];
        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && !field.value.trim()) {
                errors.push(`Câmpul ${this.getFieldLabel(fieldName)} este obligatoriu`);
                this.highlightError(field);
                isValid = false;
            }
        });

        // License number format
        const licenseField = form.querySelector('[name="license_number"]');
        if (licenseField && licenseField.value && !this.isValidLicense(licenseField.value)) {
            errors.push('Numărul permisului de conducere nu este valid');
            this.highlightError(licenseField);
            isValid = false;
        }

        // Phone number format
        const phoneField = form.querySelector('[name="phone"]');
        if (phoneField && phoneField.value && !this.isValidPhone(phoneField.value)) {
            errors.push('Numărul de telefon nu este valid');
            this.highlightError(phoneField);
            isValid = false;
        }

        // Email format
        const emailField = form.querySelector('[name="email"]');
        if (emailField && emailField.value && !this.isValidEmail(emailField.value)) {
            errors.push('Adresa de email nu este validă');
            this.highlightError(emailField);
            isValid = false;
        }

        if (!isValid && typeof notifications !== 'undefined') {
            notifications.error(errors.join('<br>'));
        }

        return isValid;
    },

    validateLicense(licenseNumber) {
        if (!licenseNumber) return;

        fetch('/drivers/validate-license', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ license_number: licenseNumber })
        })
        .then(response => response.json())
        .then(data => {
            const field = document.querySelector('#license_number');
            if (data.exists) {
                this.highlightError(field);
                if (typeof notifications !== 'undefined') {
                    notifications.warning('Acest număr de permis există deja în sistem');
                }
            } else {
                this.clearError(field);
            }
        })
        .catch(error => {
            console.error('Error validating license:', error);
        });
    },

    checkLicenseExpiry(input) {
        const expiryDate = new Date(input.value);
        const today = new Date();
        const warningDate = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000)); // 30 days

        if (expiryDate < today) {
            this.highlightError(input);
            if (typeof notifications !== 'undefined') {
                notifications.error('Permisul de conducere a expirat!');
            }
        } else if (expiryDate < warningDate) {
            this.highlightWarning(input);
            if (typeof notifications !== 'undefined') {
                notifications.warning('Permisul de conducere va expira în curând!');
            }
        } else {
            this.clearError(input);
        }
    },

    formatPhoneNumber(phone) {
        // Remove non-digits
        const cleaned = phone.replace(/\D/g, '');
        
        // Format Romanian phone number
        if (cleaned.length >= 10) {
            return cleaned.replace(/(\d{4})(\d{3})(\d{3})/, '$1 $2 $3');
        }
        
        return cleaned;
    },

    // Validation helpers
    isValidLicense(license) {
        // Romanian license format: AB123456 or 123456
        return /^[A-Z]{2}\d{6}$|^\d{6}$/.test(license.toUpperCase());
    },

    isValidPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10;
    },

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    getFieldLabel(fieldName) {
        const labels = {
            name: 'Nume',
            license_number: 'Număr permis',
            license_expiry: 'Data expirare permis',
            phone: 'Telefon',
            email: 'Email'
        };
        return labels[fieldName] || fieldName;
    },

    highlightError(field) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
    },

    highlightWarning(field) {
        field.classList.add('is-warning');
        field.classList.remove('is-valid');
    },

    clearError(field) {
        field.classList.remove('is-invalid', 'is-warning');
        field.classList.add('is-valid');
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.body.classList.contains('drivers-page')) {
        DriversModule.init();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DriversModule;
}
