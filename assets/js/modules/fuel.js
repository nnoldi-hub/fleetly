/**
 * Fuel Module
 * Handles fuel management functionality
 */
const FuelModule = {
    init() {
        this.bindEvents();
        this.initDataTable();
        this.initCharts();
        this.calculateConsumption();
    },

    bindEvents() {
        // Delete fuel record confirmation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-fuel')) {
                e.preventDefault();
                const fuelId = e.target.dataset.fuelId;
                this.confirmDelete(fuelId);
            }
        });

        // Fuel form submission
        const fuelForm = document.querySelector('#fuelForm');
        if (fuelForm) {
            fuelForm.addEventListener('submit', (e) => {
                if (!this.validateFuelForm(fuelForm)) {
                    e.preventDefault();
                }
            });
        }

        // Auto-calculate consumption on input change
        const quantityInput = document.querySelector('#quantity');
        const priceInput = document.querySelector('#price_per_liter');
        const mileageInput = document.querySelector('#mileage');

        [quantityInput, priceInput, mileageInput].forEach(input => {
            if (input) {
                input.addEventListener('input', () => {
                    this.updateCalculations();
                });
            }
        });

        // Vehicle selection change
        const vehicleSelect = document.querySelector('#vehicle_id');
        if (vehicleSelect) {
            vehicleSelect.addEventListener('change', () => {
                this.loadVehicleData(vehicleSelect.value);
            });
        }
    },

    initDataTable() {
        const table = document.querySelector('#fuelTable');
        if (table && typeof DataTable !== 'undefined') {
            new DataTable(table, {
                pageLength: 25,
                order: [[1, 'desc']], // Sort by date desc
                columnDefs: [
                    { orderable: false, targets: -1 } // Disable sorting for actions column
                ],
                language: {
                    url: '/assets/js/dataTables.romanian.json'
                }
            });
        }
    },

    initCharts() {
        this.initConsumptionChart();
        this.initCostChart();
    },

    initConsumptionChart() {
        const ctx = document.querySelector('#consumptionChart');
        if (ctx && typeof Chart !== 'undefined') {
            fetch('/fuel/consumption-data')
                .then(response => response.json())
                .then(data => {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Consum (l/100km)',
                                data: data.consumption,
                                borderColor: 'rgb(75, 192, 192)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
        }
    },

    initCostChart() {
        const ctx = document.querySelector('#costChart');
        if (ctx && typeof Chart !== 'undefined') {
            fetch('/fuel/cost-data')
                .then(response => response.json())
                .then(data => {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Cost combustibil (RON)',
                                data: data.costs,
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
        }
    },

    confirmDelete(fuelId) {
        const message = 'Sunteți sigur că doriți să ștergeți această înregistrare de combustibil?';
        
        if (typeof Modal !== 'undefined') {
            Modal.confirm('Confirmare ștergere', message, () => {
                this.deleteFuelRecord(fuelId);
            });
        } else {
            if (confirm(message)) {
                this.deleteFuelRecord(fuelId);
            }
        }
    },

    deleteFuelRecord(fuelId) {
        fetch('/fuel/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: fuelId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-fuel-id="${fuelId}"]`);
                if (row) {
                    row.remove();
                }
                
                if (typeof notifications !== 'undefined') {
                    notifications.success('Înregistrarea a fost ștearsă cu succes');
                }
                
                this.updateTotals();
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

    validateFuelForm(form) {
        let isValid = true;
        const errors = [];

        // Required fields
        const requiredFields = ['vehicle_id', 'date', 'quantity', 'price_per_liter', 'mileage'];
        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && !field.value.trim()) {
                errors.push(`Câmpul ${this.getFieldLabel(fieldName)} este obligatoriu`);
                this.highlightError(field);
                isValid = false;
            }
        });

        // Validate numeric fields
        const quantity = parseFloat(form.querySelector('[name="quantity"]')?.value);
        if (isNaN(quantity) || quantity <= 0) {
            errors.push('Cantitatea trebuie să fie un număr pozitiv');
            isValid = false;
        }

        const price = parseFloat(form.querySelector('[name="price_per_liter"]')?.value);
        if (isNaN(price) || price <= 0) {
            errors.push('Prețul pe litru trebuie să fie un număr pozitiv');
            isValid = false;
        }

        const mileage = parseInt(form.querySelector('[name="mileage"]')?.value);
        if (isNaN(mileage) || mileage < 0) {
            errors.push('Kilometrajul trebuie să fie un număr pozitiv');
            isValid = false;
        }

        if (!isValid && typeof notifications !== 'undefined') {
            notifications.error(errors.join('<br>'));
        }

        return isValid;
    },

    loadVehicleData(vehicleId) {
        if (!vehicleId) return;

        fetch(`/vehicles/data/${vehicleId}`)
            .then(response => response.json())
            .then(data => {
                // Update last mileage info
                const mileageInfo = document.querySelector('#last-mileage-info');
                if (mileageInfo && data.last_mileage) {
                    mileageInfo.textContent = `Ultimul kilometraj: ${data.last_mileage} km`;
                    mileageInfo.style.display = 'block';
                }

                // Set minimum mileage
                const mileageInput = document.querySelector('#mileage');
                if (mileageInput && data.last_mileage) {
                    mileageInput.min = data.last_mileage;
                }
            })
            .catch(error => {
                console.error('Error loading vehicle data:', error);
            });
    },

    updateCalculations() {
        const quantity = parseFloat(document.querySelector('#quantity')?.value) || 0;
        const pricePerLiter = parseFloat(document.querySelector('#price_per_liter')?.value) || 0;
        const totalCost = quantity * pricePerLiter;

        const totalCostField = document.querySelector('#total_cost');
        if (totalCostField) {
            totalCostField.value = totalCost.toFixed(2);
        }

        const totalCostDisplay = document.querySelector('#total-cost-display');
        if (totalCostDisplay) {
            totalCostDisplay.textContent = `${totalCost.toFixed(2)} RON`;
        }
    },

    calculateConsumption() {
        const consumptionElements = document.querySelectorAll('.consumption-calc');
        consumptionElements.forEach(element => {
            const quantity = parseFloat(element.dataset.quantity);
            const distance = parseFloat(element.dataset.distance);
            
            if (distance > 0) {
                const consumption = (quantity / distance * 100).toFixed(2);
                element.textContent = `${consumption} l/100km`;
            } else {
                element.textContent = 'N/A';
            }
        });
    },

    updateTotals() {
        // Recalculate totals after deletion
        const totalQuantity = Array.from(document.querySelectorAll('.fuel-quantity'))
            .reduce((sum, el) => sum + parseFloat(el.textContent), 0);
        
        const totalCost = Array.from(document.querySelectorAll('.fuel-cost'))
            .reduce((sum, el) => sum + parseFloat(el.textContent), 0);

        const totalQuantityEl = document.querySelector('#total-quantity');
        if (totalQuantityEl) {
            totalQuantityEl.textContent = `${totalQuantity.toFixed(2)} L`;
        }

        const totalCostEl = document.querySelector('#total-cost');
        if (totalCostEl) {
            totalCostEl.textContent = `${totalCost.toFixed(2)} RON`;
        }
    },

    getFieldLabel(fieldName) {
        const labels = {
            vehicle_id: 'Vehicul',
            date: 'Data',
            quantity: 'Cantitate',
            price_per_liter: 'Preț pe litru',
            mileage: 'Kilometraj'
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
    if (document.body.classList.contains('fuel-page')) {
        FuelModule.init();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FuelModule;
}
