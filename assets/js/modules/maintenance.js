/**
 * Maintenance Module
 * Handles maintenance management functionality
 */
const MaintenanceModule = {
    init() {
        this.bindEvents();
        this.initDataTable();
        this.initCalendar();
        this.checkDueMaintenance();
        this.initValidation();
    },

    bindEvents() {
        // Delete maintenance record confirmation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-maintenance')) {
                e.preventDefault();
                const maintenanceId = e.target.dataset.maintenanceId;
                this.confirmDelete(maintenanceId);
            }
        });

        // Maintenance form submission
        const maintenanceForm = document.querySelector('#maintenanceForm');
        if (maintenanceForm) {
            maintenanceForm.addEventListener('submit', (e) => {
                if (!this.validateMaintenanceForm(maintenanceForm)) {
                    e.preventDefault();
                }
            });
        }

        // Maintenance type change
        const typeSelect = document.querySelector('#maintenance_type');
        if (typeSelect) {
            typeSelect.addEventListener('change', () => {
                this.updateMaintenanceFields(typeSelect.value);
            });
        }

        // Cost calculation
        const costInputs = document.querySelectorAll('.cost-input');
        costInputs.forEach(input => {
            input.addEventListener('input', () => {
                this.calculateTotalCost();
            });
        });

        // Schedule maintenance
        document.addEventListener('click', (e) => {
            if (e.target.matches('.schedule-maintenance')) {
                e.preventDefault();
                const vehicleId = e.target.dataset.vehicleId;
                this.openScheduleModal(vehicleId);
            }
        });

        // Mark as completed
        document.addEventListener('click', (e) => {
            if (e.target.matches('.complete-maintenance')) {
                e.preventDefault();
                const maintenanceId = e.target.dataset.maintenanceId;
                this.markAsCompleted(maintenanceId);
            }
        });
    },

    initDataTable() {
        const table = document.querySelector('#maintenanceTable');
        if (table && typeof DataTable !== 'undefined') {
            new DataTable(table, {
                pageLength: 25,
                order: [[2, 'desc']], // Sort by date desc
                columnDefs: [
                    { orderable: false, targets: -1 },
                    { type: 'date', targets: [2, 3] }
                ],
                language: {
                    url: '/assets/js/dataTables.romanian.json'
                },
                rowCallback: function(row, data) {
                    // Highlight overdue maintenance
                    const dueDateCell = row.cells[3];
                    const status = row.cells[4].textContent.trim();
                    
                    if (status !== 'Finalizat') {
                        const dueDate = new Date(dueDateCell.textContent);
                        const today = new Date();
                        
                        if (dueDate < today) {
                            row.classList.add('table-danger');
                        } else if (dueDate < new Date(today.getTime() + (7 * 24 * 60 * 60 * 1000))) {
                            row.classList.add('table-warning');
                        }
                    }
                }
            });
        }
    },

    initCalendar() {
        const calendarEl = document.querySelector('#maintenanceCalendar');
        if (calendarEl && typeof FullCalendar !== 'undefined') {
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'ro',
                events: '/maintenance/calendar-data',
                eventClick: function(info) {
                    this.showMaintenanceDetails(info.event);
                }.bind(this)
            });
            calendar.render();
        }
    },

    checkDueMaintenance() {
        const dueElements = document.querySelectorAll('.maintenance-due');
        const today = new Date();
        const warningDate = new Date(today.getTime() + (7 * 24 * 60 * 60 * 1000));

        dueElements.forEach(element => {
            const dueDate = new Date(element.textContent);
            const row = element.closest('tr');
            const status = row.querySelector('.maintenance-status').textContent.trim();

            if (status !== 'Finalizat') {
                if (dueDate < today) {
                    row.classList.add('table-danger');
                    element.innerHTML += ' <span class="badge bg-danger">Întârziat</span>';
                } else if (dueDate < warningDate) {
                    row.classList.add('table-warning');
                    element.innerHTML += ' <span class="badge bg-warning">Urgent</span>';
                }
            }
        });
    },

    initValidation() {
        // Mileage validation
        const mileageInput = document.querySelector('#mileage');
        const vehicleSelect = document.querySelector('#vehicle_id');
        
        if (mileageInput && vehicleSelect) {
            vehicleSelect.addEventListener('change', () => {
                this.loadVehicleMileage(vehicleSelect.value);
            });
        }
    },

    confirmDelete(maintenanceId) {
        const message = 'Sunteți sigur că doriți să ștergeți această înregistrare de întreținere?';
        
        if (typeof Modal !== 'undefined') {
            Modal.confirm('Confirmare ștergere', message, () => {
                this.deleteMaintenance(maintenanceId);
            });
        } else {
            if (confirm(message)) {
                this.deleteMaintenance(maintenanceId);
            }
        }
    },

    deleteMaintenance(maintenanceId) {
        fetch('/maintenance/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: maintenanceId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-maintenance-id="${maintenanceId}"]`);
                if (row) {
                    row.remove();
                }
                
                if (typeof notifications !== 'undefined') {
                    notifications.success('Înregistrarea a fost ștearsă cu succes');
                }
            } else {
                throw new Error(data.message || 'Eroare la ștergerea înregistrării');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error(error.message);
            }
        });
    },

    validateMaintenanceForm(form) {
        let isValid = true;
        const errors = [];

        // Required fields
        const requiredFields = ['vehicle_id', 'maintenance_type', 'scheduled_date', 'description'];
        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && !field.value.trim()) {
                errors.push(`Câmpul ${this.getFieldLabel(fieldName)} este obligatoriu`);
                this.highlightError(field);
                isValid = false;
            }
        });

        // Date validation
        const scheduledDate = new Date(form.querySelector('[name="scheduled_date"]')?.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (scheduledDate < today) {
            errors.push('Data programată nu poate fi în trecut');
            isValid = false;
        }

        // Cost validation
        const cost = parseFloat(form.querySelector('[name="cost"]')?.value);
        if (cost && (isNaN(cost) || cost < 0)) {
            errors.push('Costul trebuie să fie un număr pozitiv');
            isValid = false;
        }

        if (!isValid && typeof notifications !== 'undefined') {
            notifications.error(errors.join('<br>'));
        }

        return isValid;
    },

    updateMaintenanceFields(type) {
        const fieldsContainer = document.querySelector('#maintenance-fields');
        if (!fieldsContainer) return;

        // Show/hide specific fields based on maintenance type
        const mileageField = fieldsContainer.querySelector('#mileage-field');
        const costField = fieldsContainer.querySelector('#cost-field');
        const partsField = fieldsContainer.querySelector('#parts-field');

        if (type === 'revizie' || type === 'reparatie') {
            if (mileageField) mileageField.style.display = 'block';
            if (costField) costField.style.display = 'block';
            if (partsField) partsField.style.display = 'block';
        } else if (type === 'spalare' || type === 'curatenie') {
            if (mileageField) mileageField.style.display = 'none';
            if (costField) costField.style.display = 'block';
            if (partsField) partsField.style.display = 'none';
        }
    },

    calculateTotalCost() {
        const laborCost = parseFloat(document.querySelector('#labor_cost')?.value) || 0;
        const partsCost = parseFloat(document.querySelector('#parts_cost')?.value) || 0;
        const totalCost = laborCost + partsCost;

        const totalField = document.querySelector('#total_cost');
        if (totalField) {
            totalField.value = totalCost.toFixed(2);
        }

        const totalDisplay = document.querySelector('#total-cost-display');
        if (totalDisplay) {
            totalDisplay.textContent = `${totalCost.toFixed(2)} RON`;
        }
    },

    loadVehicleMileage(vehicleId) {
        if (!vehicleId) return;

        fetch(`/vehicles/data/${vehicleId}`)
            .then(response => response.json())
            .then(data => {
                const mileageInfo = document.querySelector('#current-mileage-info');
                if (mileageInfo && data.current_mileage) {
                    mileageInfo.textContent = `Kilometraj actual: ${data.current_mileage} km`;
                    mileageInfo.style.display = 'block';
                }

                const mileageInput = document.querySelector('#mileage');
                if (mileageInput && data.current_mileage) {
                    mileageInput.value = data.current_mileage;
                }
            })
            .catch(error => {
                console.error('Error loading vehicle data:', error);
            });
    },

    markAsCompleted(maintenanceId) {
        fetch('/maintenance/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: maintenanceId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const statusCell = document.querySelector(`tr[data-maintenance-id="${maintenanceId}"] .maintenance-status`);
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-success">Finalizat</span>';
                }
                
                const row = document.querySelector(`tr[data-maintenance-id="${maintenanceId}"]`);
                if (row) {
                    row.classList.remove('table-danger', 'table-warning');
                }
                
                if (typeof notifications !== 'undefined') {
                    notifications.success('Întreținerea a fost marcată ca finalizată');
                }
            } else {
                throw new Error(data.message || 'Eroare la actualizarea statusului');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error(error.message);
            }
        });
    },

    getFieldLabel(fieldName) {
        const labels = {
            vehicle_id: 'Vehicul',
            maintenance_type: 'Tipul întreținerii',
            scheduled_date: 'Data programată',
            description: 'Descriere',
            cost: 'Cost'
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
    if (document.body.classList.contains('maintenance-page')) {
        MaintenanceModule.init();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MaintenanceModule;
}
