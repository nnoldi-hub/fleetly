/**
 * Vehicles Module
 * Handles vehicle management functionality
 */
const VehiclesModule = {
    init() {
        this.bindEvents();
        this.initDataTable();
        this.initValidation();
        this.initImageUpload();
    },

    bindEvents() {
        // Delete vehicle confirmation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-vehicle')) {
                e.preventDefault();
                const vehicleId = e.target.dataset.vehicleId;
                const vehicleName = e.target.dataset.vehicleName;
                this.confirmDelete(vehicleId, vehicleName);
            }
        });

        // Vehicle form submission
        const vehicleForm = document.querySelector('#vehicleForm');
        if (vehicleForm) {
            vehicleForm.addEventListener('submit', (e) => {
                if (!this.validateVehicleForm(vehicleForm)) {
                    e.preventDefault();
                }
            });
        }

        // VIN validation
        const vinInput = document.querySelector('#vin');
        if (vinInput) {
            vinInput.addEventListener('blur', () => {
                this.validateVIN(vinInput.value);
            });
        }

        // License plate validation
        const plateInput = document.querySelector('#license_plate');
        if (plateInput) {
            plateInput.addEventListener('blur', () => {
                this.validateLicensePlate(plateInput.value);
            });
            plateInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.toUpperCase();
            });
        }

        // Mileage update
        document.addEventListener('click', (e) => {
            if (e.target.matches('.update-mileage')) {
                e.preventDefault();
                const vehicleId = e.target.dataset.vehicleId;
                this.showMileageModal(vehicleId);
            }
        });

        // Quick actions
        document.addEventListener('click', (e) => {
            if (e.target.matches('.quick-action')) {
                const action = e.target.dataset.action;
                const vehicleId = e.target.dataset.vehicleId;
                this.handleQuickAction(action, vehicleId);
            }
        });
    },

    initDataTable() {
        const table = document.querySelector('#vehiclesTable');
        if (table && typeof DataTable !== 'undefined') {
            new DataTable(table, {
                pageLength: 25,
                order: [[1, 'asc']], // Sort by make/model
                columnDefs: [
                    { orderable: false, targets: -1 },
                    { className: 'text-center', targets: [4, 5] } // Status and actions columns
                ],
                language: {
                    url: '/assets/js/dataTables.romanian.json'
                }
            });
        }
    },

    initValidation() {
        // Year validation
        const yearInput = document.querySelector('#year');
        if (yearInput) {
            yearInput.addEventListener('input', () => {
                this.validateYear(yearInput.value);
            });
        }

        // Engine capacity validation
        const engineInput = document.querySelector('#engine_capacity');
        if (engineInput) {
            engineInput.addEventListener('input', () => {
                this.validateEngineCapacity(engineInput.value);
            });
        }
    },

    initImageUpload() {
        const imageInput = document.querySelector('#vehicle_image');
        const imagePreview = document.querySelector('#image-preview');
        
        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', (e) => {
                this.previewImage(e.target.files[0], imagePreview);
            });
        }
    },

    confirmDelete(vehicleId, vehicleName) {
        const message = `Sunteți sigur că doriți să ștergeți vehiculul "${vehicleName}"?<br><small>Această acțiune va șterge și toate înregistrările asociate (combustibil, întreținere, etc.)</small>`;
        
        if (typeof Modal !== 'undefined') {
            Modal.confirm('Confirmare ștergere', message, () => {
                this.deleteVehicle(vehicleId);
            });
        } else {
            if (confirm(`Sunteți sigur că doriți să ștergeți vehiculul "${vehicleName}"?`)) {
                this.deleteVehicle(vehicleId);
            }
        }
    },

    deleteVehicle(vehicleId) {
        fetch('/vehicles/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: vehicleId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-vehicle-id="${vehicleId}"]`);
                if (row) {
                    row.remove();
                }
                
                if (typeof notifications !== 'undefined') {
                    notifications.success('Vehiculul a fost șters cu succes');
                }
            } else {
                throw new Error(data.message || 'Eroare la ștergerea vehiculului');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error(error.message);
            }
        });
    },

    validateVehicleForm(form) {
        let isValid = true;
        const errors = [];

        // Required fields
        const requiredFields = ['make', 'model', 'year', 'vin', 'license_plate'];
        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && !field.value.trim()) {
                errors.push(`Câmpul ${this.getFieldLabel(fieldName)} este obligatoriu`);
                this.highlightError(field);
                isValid = false;
            }
        });

        // VIN validation
        const vinField = form.querySelector('[name="vin"]');
        if (vinField && vinField.value && !this.isValidVIN(vinField.value)) {
            errors.push('Numărul VIN nu este valid');
            this.highlightError(vinField);
            isValid = false;
        }

        // License plate validation
        const plateField = form.querySelector('[name="license_plate"]');
        if (plateField && plateField.value && !this.isValidLicensePlate(plateField.value)) {
            errors.push('Numărul de înmatriculare nu este valid');
            this.highlightError(plateField);
            isValid = false;
        }

        // Year validation
        const yearField = form.querySelector('[name="year"]');
        if (yearField && yearField.value) {
            const year = parseInt(yearField.value);
            const currentYear = new Date().getFullYear();
            if (year < 1900 || year > currentYear + 1) {
                errors.push('Anul fabricației nu este valid');
                this.highlightError(yearField);
                isValid = false;
            }
        }

        if (!isValid && typeof notifications !== 'undefined') {
            notifications.error(errors.join('<br>'));
        }

        return isValid;
    },

    validateVIN(vin) {
        if (!vin) return;

        fetch('/vehicles/validate-vin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ vin: vin })
        })
        .then(response => response.json())
        .then(data => {
            const field = document.querySelector('#vin');
            if (data.exists) {
                this.highlightError(field);
                if (typeof notifications !== 'undefined') {
                    notifications.warning('Acest VIN există deja în sistem');
                }
            } else {
                this.clearError(field);
            }
        })
        .catch(error => {
            console.error('Error validating VIN:', error);
        });
    },

    validateLicensePlate(plate) {
        if (!plate) return;

        fetch('/vehicles/validate-plate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ license_plate: plate })
        })
        .then(response => response.json())
        .then(data => {
            const field = document.querySelector('#license_plate');
            if (data.exists) {
                this.highlightError(field);
                if (typeof notifications !== 'undefined') {
                    notifications.warning('Acest număr de înmatriculare există deja în sistem');
                }
            } else {
                this.clearError(field);
            }
        })
        .catch(error => {
            console.error('Error validating license plate:', error);
        });
    },

    showMileageModal(vehicleId) {
        fetch(`/vehicles/mileage/${vehicleId}`)
            .then(response => response.json())
            .then(data => {
                const modal = document.querySelector('#mileageModal');
                if (modal) {
                    modal.querySelector('#current-mileage').textContent = data.current_mileage;
                    modal.querySelector('#vehicle-id').value = vehicleId;
                    modal.querySelector('#new-mileage').value = '';
                    modal.querySelector('#new-mileage').min = data.current_mileage;
                    
                    if (modal._modalInstance) {
                        modal._modalInstance.show();
                    }
                }
            });
    },

    handleQuickAction(action, vehicleId) {
        switch (action) {
            case 'add-fuel':
                window.location.href = `/fuel/add?vehicle_id=${vehicleId}`;
                break;
            case 'add-maintenance':
                window.location.href = `/maintenance/add?vehicle_id=${vehicleId}`;
                break;
            case 'view-history':
                window.location.href = `/vehicles/view?id=${vehicleId}`;
                break;
        }
    },

    validateYear(year) {
        const currentYear = new Date().getFullYear();
        const yearNum = parseInt(year);
        
        if (year && (yearNum < 1900 || yearNum > currentYear + 1)) {
            const field = document.querySelector('#year');
            this.highlightError(field);
        } else {
            this.clearError(document.querySelector('#year'));
        }
    },

    validateEngineCapacity(capacity) {
        const capacityNum = parseFloat(capacity);
        
        if (capacity && (isNaN(capacityNum) || capacityNum <= 0 || capacityNum > 20)) {
            const field = document.querySelector('#engine_capacity');
            this.highlightError(field);
        } else {
            this.clearError(document.querySelector('#engine_capacity'));
        }
    },

    previewImage(file, previewContainer) {
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            if (typeof notifications !== 'undefined') {
                notifications.error('Vă rugăm să selectați un fișier imagine valid');
            }
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            previewContainer.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`;
        };
        reader.readAsDataURL(file);
    },

    // Validation helpers
    isValidVIN(vin) {
        // VIN should be 17 characters, alphanumeric, no I, O, Q
        return /^[A-HJ-NPR-Z0-9]{17}$/.test(vin.toUpperCase());
    },

    isValidLicensePlate(plate) {
        // Romanian license plate formats
        const patterns = [
            /^[A-Z]{1,2}\d{2,3}[A-Z]{3}$/, // Standard format
            /^B\d{3}[A-Z]{3}$/,            // Bucharest format
            /^\d{1,3}[A-Z]{3}$/            // Old format
        ];
        return patterns.some(pattern => pattern.test(plate.toUpperCase()));
    },

    getFieldLabel(fieldName) {
        const labels = {
            make: 'Marca',
            model: 'Model',
            year: 'Anul fabricației',
            vin: 'Numărul VIN',
            license_plate: 'Numărul de înmatriculare'
        };
        return labels[fieldName] || fieldName;
    },

    highlightError(field) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
    },

    clearError(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.body.classList.contains('vehicles-page')) {
        VehiclesModule.init();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VehiclesModule;
}
