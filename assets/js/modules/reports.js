/**
 * Reports Module
 * Handles report generation and visualization functionality
 */
const ReportsModule = {
    init() {
        this.bindEvents();
        this.initCharts();
        this.initFilters();
        this.initExport();
    },

    bindEvents() {
        // Report type selection
        const reportTypeSelect = document.querySelector('#report_type');
        if (reportTypeSelect) {
            reportTypeSelect.addEventListener('change', () => {
                this.updateReportFields(reportTypeSelect.value);
            });
        }

        // Date range filters
        const dateFilters = document.querySelectorAll('.date-filter');
        dateFilters.forEach(filter => {
            filter.addEventListener('change', () => {
                this.updateReport();
            });
        });

        // Vehicle filter
        const vehicleFilter = document.querySelector('#vehicle_filter');
        if (vehicleFilter) {
            vehicleFilter.addEventListener('change', () => {
                this.updateReport();
            });
        }

        // Generate report button
        const generateBtn = document.querySelector('#generate-report');
        if (generateBtn) {
            generateBtn.addEventListener('click', () => {
                this.generateReport();
            });
        }

        // Export buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.export-btn')) {
                const format = e.target.dataset.format;
                this.exportReport(format);
            }
        });
    },

    initCharts() {
        this.initFleetOverviewChart();
        this.initCostChart();
        this.initMaintenanceChart();
        this.initFuelChart();
    },

    initFleetOverviewChart() {
        const ctx = document.querySelector('#fleetOverviewChart');
        if (ctx && typeof Chart !== 'undefined') {
            fetch((window.BASE_URL || '') + '/reports/fleet-overview-data')
                .then(response => response.json())
                .then(data => {
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.values,
                                backgroundColor: [
                                    '#28a745',
                                    '#ffc107',
                                    '#dc3545',
                                    '#6c757d'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
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
            fetch((window.BASE_URL || '') + '/reports/cost-data')
                .then(response => response.json())
                .then(data => {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Costuri combustibil',
                                data: data.fuel,
                                backgroundColor: 'rgba(54, 162, 235, 0.8)'
                            }, {
                                label: 'Costuri întreținere',
                                data: data.maintenance,
                                backgroundColor: 'rgba(255, 99, 132, 0.8)'
                            }, {
                                label: 'Alte costuri',
                                data: data.other,
                                backgroundColor: 'rgba(75, 192, 192, 0.8)'
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                x: { stacked: true },
                                y: { stacked: true }
                            }
                        }
                    });
                });
        }
    },

    initMaintenanceChart() {
        const ctx = document.querySelector('#maintenanceChart');
        if (ctx && typeof Chart !== 'undefined') {
            fetch((window.BASE_URL || '') + '/reports/maintenance-data')
                .then(response => response.json())
                .then(data => {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Întrețineri planificate',
                                data: data.planned,
                                borderColor: 'rgb(75, 192, 192)',
                                tension: 0.1
                            }, {
                                label: 'Întrețineri finalizate',
                                data: data.completed,
                                borderColor: 'rgb(54, 162, 235)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                });
        }
    },

    initFuelChart() {
        const ctx = document.querySelector('#fuelChart');
        if (ctx && typeof Chart !== 'undefined') {
            fetch((window.BASE_URL || '') + '/reports/fuel-consumption-data')
                .then(response => response.json())
                .then(data => {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: data.vehicles.map((vehicle, index) => ({
                                label: vehicle.name,
                                data: vehicle.consumption,
                                borderColor: this.getChartColor(index),
                                tension: 0.1
                            }))
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { 
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Consum (l/100km)'
                                    }
                                }
                            }
                        }
                    });
                });
        }
    },

    initFilters() {
        // Initialize date range picker
        const dateRange = document.querySelector('#date_range');
        if (dateRange && typeof flatpickr !== 'undefined') {
            flatpickr(dateRange, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                locale: 'ro'
            });
        }

        // Initialize select2 for vehicle filter
        const vehicleSelect = document.querySelector('#vehicle_filter');
        if (vehicleSelect && typeof $ !== 'undefined' && $.fn.select2) {
            $(vehicleSelect).select2({
                placeholder: 'Selectați vehiculele',
                allowClear: true
            });
        }
    },

    initExport() {
        // Initialize export options
        const exportOptions = document.querySelector('#export_options');
        if (exportOptions) {
            exportOptions.style.display = 'none';
        }
    },

    updateReportFields(reportType) {
        const fieldsContainer = document.querySelector('#report-fields');
        if (!fieldsContainer) return;

        // Show/hide specific fields based on report type
        const vehicleField = fieldsContainer.querySelector('#vehicle-field');
        const driverField = fieldsContainer.querySelector('#driver-field');
        const costTypeField = fieldsContainer.querySelector('#cost-type-field');

        // Reset all fields
        [vehicleField, driverField, costTypeField].forEach(field => {
            if (field) field.style.display = 'none';
        });

        // Show relevant fields
        switch (reportType) {
            case 'vehicle':
                if (vehicleField) vehicleField.style.display = 'block';
                break;
            case 'driver':
                if (driverField) driverField.style.display = 'block';
                break;
            case 'costs':
                if (costTypeField) costTypeField.style.display = 'block';
                break;
        }
    },

    generateReport() {
        const formData = this.getReportFilters();
        
        if (!this.validateReportFilters(formData)) {
            return;
        }

        this.showLoading(true);

    fetch((window.BASE_URL || '') + '/reports/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayReport(data.report);
                this.showExportOptions(true);
            } else {
                throw new Error(data.message || 'Eroare la generarea raportului');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error(error.message);
            }
        })
        .finally(() => {
            this.showLoading(false);
        });
    },

    updateReport() {
        // Auto-update report when filters change
        if (this.updateTimeout) {
            clearTimeout(this.updateTimeout);
        }
        
        this.updateTimeout = setTimeout(() => {
            this.generateReport();
        }, 500);
    },

    exportReport(format) {
        const formData = this.getReportFilters();
        formData.export_format = format;

        const form = document.createElement('form');
        form.method = 'POST';
    form.action = (window.BASE_URL || '') + '/reports/export';
        form.style.display = 'none';

        Object.keys(formData).forEach(key => {
            const input = document.createElement('input');
            input.name = key;
            input.value = formData[key];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        if (typeof notifications !== 'undefined') {
            notifications.success(`Raportul este în curs de descărcare (${format.toUpperCase()})`);
        }
    },

    getReportFilters() {
        const form = document.querySelector('#reportForm');
        if (!form) return {};

        const formData = new FormData(form);
        const filters = {};

        for (let [key, value] of formData.entries()) {
            filters[key] = value;
        }

        return filters;
    },

    validateReportFilters(filters) {
        const errors = [];

        if (!filters.report_type) {
            errors.push('Selectați tipul de raport');
        }

        if (!filters.date_from || !filters.date_to) {
            errors.push('Selectați perioada de raportare');
        }

        if (filters.date_from && filters.date_to && new Date(filters.date_from) > new Date(filters.date_to)) {
            errors.push('Data de început nu poate fi după data de sfârșit');
        }

        if (errors.length > 0 && typeof notifications !== 'undefined') {
            notifications.error(errors.join('<br>'));
            return false;
        }

        return true;
    },

    displayReport(reportData) {
        const container = document.querySelector('#report-results');
        if (!container) return;

        container.innerHTML = reportData.html;
        container.style.display = 'block';

        // Reinitialize datatables if present
        const tables = container.querySelectorAll('table.dataTable');
        tables.forEach(table => {
            if (typeof DataTable !== 'undefined') {
                new DataTable(table, {
                    pageLength: 50,
                    language: {
                        url: '/assets/js/dataTables.romanian.json'
                    }
                });
            }
        });
    },

    showLoading(show) {
        const loadingEl = document.querySelector('#report-loading');
        const resultsEl = document.querySelector('#report-results');

        if (loadingEl) {
            loadingEl.style.display = show ? 'block' : 'none';
        }
        if (resultsEl && show) {
            resultsEl.style.display = 'none';
        }
    },

    showExportOptions(show) {
        const exportOptions = document.querySelector('#export-options');
        if (exportOptions) {
            exportOptions.style.display = show ? 'block' : 'none';
        }
    },

    getChartColor(index) {
        const colors = [
            'rgb(255, 99, 132)',
            'rgb(54, 162, 235)',
            'rgb(255, 205, 86)',
            'rgb(75, 192, 192)',
            'rgb(153, 102, 255)',
            'rgb(255, 159, 64)'
        ];
        return colors[index % colors.length];
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const shouldInit = document.body.classList.contains('reports-page')
        || document.getElementById('reportForm')
        || document.getElementById('fleetOverviewChart')
        || document.getElementById('costChart')
        || document.getElementById('maintenanceChart')
        || document.getElementById('fuelChart');
    if (shouldInit) {
        ReportsModule.init();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReportsModule;
}
