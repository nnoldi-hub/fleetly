/**
 * Insurance Module
 * Handles insurance management functionality
 */
const InsuranceModule = {
    init() {
        this.bindEvents();
        this.initDataTable();
        this.checkExpirations();
        this.initValidation();
    },

    bindEvents() {
        // Delete insurance record confirmation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-insurance')) {
                e.preventDefault();
                const insuranceId = e.target.dataset.insuranceId;
                this.confirmDelete(insuranceId);
            }
        });

        // Insurance form submission
        const insuranceForm = document.querySelector('#insuranceForm');
        if (insuranceForm) {
            insuranceForm.addEventListener('submit', (e) => {
                if (!this.validateInsuranceForm(insuranceForm)) {
                    e.preventDefault();
                }
            });
        }

        // Expiry date validation
        const expiryInput = document.querySelector('#expiry_date');
        if (expiryInput) {
            expiryInput.addEventListener('change', () => {
                this.checkExpiryDate(expiryInput);
            });
        }

        // Premium calculation
        const premiumInput = document.querySelector('#premium_amount');
        const frequencySelect = document.querySelector('#payment_frequency');
        if (premiumInput && frequencySelect) {
            [premiumInput, frequencySelect].forEach(input => {
                input.addEventListener('change', () => {
                    this.calculateAnnualPremium();
                });
            });
        }

        // Policy number validation
        const policyInput = document.querySelector('#policy_number');
        if (policyInput) {
            policyInput.addEventListener('blur', () => {
                this.validatePolicyNumber(policyInput.value);
            });
        }
    },

    initDataTable() {
        const table = document.querySelector('#insuranceTable');
        if (table && typeof DataTable !== 'undefined') {
            new DataTable(table, {
                pageLength: 25,
                order: [[3, 'asc']], // Sort by expiry date
                columnDefs: [
                    { orderable: false, targets: -1 },
                    { type: 'date', targets: [2, 3] }
                ],
                language: {
                    url: '/assets/js/dataTables.romanian.json'
                },
                rowCallback: function(row, data) {
                    // Highlight expiring insurance
                    const expiryCell = row.cells[3];
                    const expiryDate = new Date(expiryCell.textContent);
                    const today = new Date();
                    const warningDate = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000));

                    if (expiryDate < today) {
                        row.classList.add('table-danger');
                    } else if (expiryDate < warningDate) {
                        row.classList.add('table-warning');
                    }
                }
            });
        }
    },

    checkExpirations() {
        const expiryElements = document.querySelectorAll('.expiry-date');
        const today = new Date();
        const warningDate = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000));

        expiryElements.forEach(element => {
            const expiryDate = new Date(element.textContent);
            const row = element.closest('tr');

            if (expiryDate < today) {
                row.classList.add('table-danger');
                element.innerHTML += ' <span class="badge bg-danger">Expirat</span>';
            } else if (expiryDate < warningDate) {
                row.classList.add('table-warning');
                element.innerHTML += ' <span class="badge bg-warning">Expiră curând</span>';
            }
        });
    },

    initValidation() {
        // Real-time validation for dates
        const startDateInput = document.querySelector('#start_date');
        const expiryDateInput = document.querySelector('#expiry_date');

        if (startDateInput && expiryDateInput) {
            startDateInput.addEventListener('change', () => {
                if (expiryDateInput.value) {
                    this.validateDateRange(startDateInput.value, expiryDateInput.value);
                }
            });

            expiryDateInput.addEventListener('change', () => {
                if (startDateInput.value) {
                    this.validateDateRange(startDateInput.value, expiryDateInput.value);
                }
            });
        }
    },

    confirmDelete(insuranceId) {
        const message = 'Sunteți sigur că doriți să ștergeți această poliță de asigurare?';
        
        if (typeof Modal !== 'undefined') {
            Modal.confirm('Confirmare ștergere', message, () => {
                this.deleteInsurance(insuranceId);
            });
        } else {
            if (confirm(message)) {
                this.deleteInsurance(insuranceId);
            }
        }
    },

    deleteInsurance(insuranceId) {
        fetch('/insurance/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: insuranceId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-insurance-id="${insuranceId}"]`);
                if (row) {
                    row.remove();
                }
                
                if (typeof notifications !== 'undefined') {
                    notifications.success('Polița de asigurare a fost ștearsă cu succes');
                }
            } else {
                throw new Error(data.message || 'Eroare la ștergerea poliței');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error(error.message);
            }
        });
    },

    validateInsuranceForm(form) {
        let isValid = true;
        const errors = [];

        // Required fields
        const requiredFields = ['vehicle_id', 'insurance_company', 'policy_number', 'start_date', 'expiry_date', 'premium_amount'];
        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && !field.value.trim()) {
                errors.push(`Câmpul ${this.getFieldLabel(fieldName)} este obligatoriu`);
                this.highlightError(field);
                isValid = false;
            }
        });

        // Date validation
        const startDate = new Date(form.querySelector('[name="start_date"]')?.value);
        const expiryDate = new Date(form.querySelector('[name="expiry_date"]')?.value);

        if (startDate >= expiryDate) {
            errors.push('Data de expirare trebuie să fie după data de început');
            isValid = false;
        }

        // Premium amount validation
        const premium = parseFloat(form.querySelector('[name="premium_amount"]')?.value);
        if (isNaN(premium) || premium <= 0) {
            errors.push('Suma primei trebuie să fie un număr pozitiv');
            isValid = false;
        }

        if (!isValid && typeof notifications !== 'undefined') {
            notifications.error(errors.join('<br>'));
        }

        return isValid;
    },

    validatePolicyNumber(policyNumber) {
        if (!policyNumber) return;

        fetch('/insurance/validate-policy', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ policy_number: policyNumber })
        })
        .then(response => response.json())
        .then(data => {
            const field = document.querySelector('#policy_number');
            if (data.exists) {
                this.highlightError(field);
                if (typeof notifications !== 'undefined') {
                    notifications.warning('Acest număr de poliță există deja în sistem');
                }
            } else {
                this.clearError(field);
            }
        })
        .catch(error => {
            console.error('Error validating policy:', error);
        });
    },

    checkExpiryDate(input) {
        const expiryDate = new Date(input.value);
        const today = new Date();
        const warningDate = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000));

        if (expiryDate < today) {
            this.highlightError(input);
            if (typeof notifications !== 'undefined') {
                notifications.error('Data de expirare nu poate fi în trecut!');
            }
        } else if (expiryDate < warningDate) {
            this.highlightWarning(input);
            if (typeof notifications !== 'undefined') {
                notifications.warning('Polița va expira în curând!');
            }
        } else {
            this.clearError(input);
        }
    },

    validateDateRange(startDate, expiryDate) {
        const start = new Date(startDate);
        const expiry = new Date(expiryDate);

        if (start >= expiry) {
            const expiryField = document.querySelector('#expiry_date');
            this.highlightError(expiryField);
            if (typeof notifications !== 'undefined') {
                notifications.error('Data de expirare trebuie să fie după data de început');
            }
        }
    },

    calculateAnnualPremium() {
        const premiumAmount = parseFloat(document.querySelector('#premium_amount')?.value) || 0;
        const frequency = document.querySelector('#payment_frequency')?.value;

        let annualPremium = 0;
        switch (frequency) {
            case 'monthly':
                annualPremium = premiumAmount * 12;
                break;
            case 'quarterly':
                annualPremium = premiumAmount * 4;
                break;
            case 'semi-annual':
                annualPremium = premiumAmount * 2;
                break;
            case 'annual':
                annualPremium = premiumAmount;
                break;
        }

        const annualDisplay = document.querySelector('#annual-premium-display');
        if (annualDisplay) {
            annualDisplay.textContent = `${annualPremium.toFixed(2)} RON/an`;
        }
    },

    getFieldLabel(fieldName) {
        const labels = {
            vehicle_id: 'Vehicul',
            insurance_company: 'Compania de asigurări',
            policy_number: 'Numărul poliței',
            start_date: 'Data de început',
            expiry_date: 'Data de expirare',
            premium_amount: 'Suma primei'
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
    if (document.body.classList.contains('insurance-page')) {
        InsuranceModule.init();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = InsuranceModule;
}
