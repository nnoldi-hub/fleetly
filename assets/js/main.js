// assets/js/main.js - JavaScript principal
document.addEventListener('DOMContentLoaded', function() {
    console.log('Main.js: DOMContentLoaded started');
    
    // Inițializare componente Bootstrap
    try {
        initializeBootstrapComponents();
        console.log('Bootstrap components initialized');
    } catch (e) {
        console.error('Error initializing Bootstrap:', e);
    }
    
    // Inițializare DataTables pentru toate tabelele (temporar dezactivat pentru debugging)
    // initializeDataTables();
    
    // Inițializare validări forme
    try {
        initializeFormValidation();
        console.log('Form validation initialized');
    } catch (e) {
        console.error('Error initializing form validation:', e);
    }
    
    // Inițializare notificări (temporar dezactivat pentru debugging)
    // initializeNotifications();
    
    // Event listeners globali
    try {
        setupGlobalEventListeners();
        console.log('Global event listeners setup');
    } catch (e) {
        console.error('Error setting up event listeners:', e);
    }

    // Aplică temă pentru Chart.js dacă este disponibil
    const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
    try { applyChartTheme(currentTheme); } catch (e) { /* noop */ }

    // Elimină diacriticele din UI (temporar dezactivat pentru debugging)
    // try { if (window.FleetManagement && window.FleetManagement.noDiacritics !== false) removeDiacriticsFromPage(); } catch (e) { /* noop */ }
    
    console.log('Main.js: DOMContentLoaded completed');
});

function initializeBootstrapComponents() {
    // Cleanup existing instances to prevent conflicts
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        const existingTooltip = bootstrap.Tooltip.getInstance(el);
        if (existingTooltip) {
            existingTooltip.dispose();
        }
    });
    
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function(el) {
        const existingPopover = bootstrap.Popover.getInstance(el);
        if (existingPopover) {
            existingPopover.dispose();
        }
    });
    
    // Cleanup existing dropdowns
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(el) {
        const existingDropdown = bootstrap.Dropdown.getInstance(el);
        if (existingDropdown) {
            existingDropdown.dispose();
        }
    });
    
    // Reinitialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Reinitialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Reinitialize dropdowns
    var dropdownTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
    var dropdownList = dropdownTriggerList.map(function (dropdownTriggerEl) {
        return new bootstrap.Dropdown(dropdownTriggerEl);
    });
}

function initializeDataTables() {
    // Configurare DataTables pentru toate tabelele cu clasa .data-table
    $('.data-table').each(function() {
        $(this).DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ro.json'
            },
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Toate"]],
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: -1 } // Ultima coloană (acțiuni) nu se sortează
            ]
        });
    });
}

function initializeFormValidation() {
    // Validare Bootstrap pentru toate formele
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Validare în timp real pentru câmpurile cu atributul data-validate
    document.querySelectorAll('[data-validate]').forEach(function(input) {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
}

function validateField(field) {
    const validationType = field.getAttribute('data-validate');
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    switch(validationType) {
        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isValid = emailRegex.test(value);
            message = 'Vă rugăm să introduceți o adresă de email validă';
            break;
            
        case 'phone':
            const phoneRegex = /^(\+4|4|0)[0-9]{8,9}$/;
            isValid = phoneRegex.test(value.replace(/\s/g, ''));
            message = 'Vă rugăm să introduceți un număr de telefon valid';
            break;
            
        case 'required':
            isValid = value.length > 0;
            message = 'Acest câmp este obligatoriu';
            break;
            
        case 'numeric':
            isValid = !isNaN(value) && value !== '';
            message = 'Vă rugăm să introduceți doar numere';
            break;
            
        case 'date':
            isValid = !isNaN(Date.parse(value));
            message = 'Vă rugăm să introduceți o dată validă';
            break;
    }
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) feedback.style.display = 'none';
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        let feedback = field.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
        feedback.style.display = 'block';
    }
    
    return isValid;
}

function initializeNotifications() {
    // Auto-dismiss pentru alert-uri
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

function setupGlobalEventListeners() {
    // Confirmări pentru butoanele de ștergere
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-delete-confirm') || 
            e.target.closest('.btn-delete-confirm')) {
            e.preventDefault();
            const button = e.target.classList.contains('btn-delete-confirm') ? 
                          e.target : e.target.closest('.btn-delete-confirm');
            
            const message = button.getAttribute('data-message') || 
                           'Ești sigur că vrei să ștergi acest element?';
            
            if (confirm(message)) {
                const form = button.closest('form');
                if (form) {
                    form.submit();
                } else {
                    window.location.href = button.href;
                }
            }
        }
    });
}

// Funcții utilitare
function showAlert(type, message, container = null) {
    const alertTypes = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const alertClass = alertTypes[type] || 'alert-info';
    const iconClass = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    }[type] || 'fas fa-info-circle';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="${iconClass}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const targetContainer = container || document.querySelector('.main-content') || document.body;
    targetContainer.insertBefore(alertDiv, targetContainer.firstChild);
    
    // Auto-dismiss după 5 secunde
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}

function formatCurrency(amount, currency = 'RON') {
    return new Intl.NumberFormat('ro-RO', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

function formatDate(dateString, format = 'short') {
    const date = new Date(dateString);
    const options = format === 'short' ? 
        { day: '2-digit', month: '2-digit', year: 'numeric' } :
        { day: '2-digit', month: 'long', year: 'numeric' };
    
    return date.toLocaleDateString('ro-RO', options);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// AJAX helper functions
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Request failed:', error);
            throw error;
        });
}

// Export funcții pentru utilizare în alte module
window.FleetManagement = {
    showAlert,
    formatCurrency,
    formatDate,
    debounce,
    makeRequest,
    validateField,
    applyChartTheme,
    // Flag global: fără diacritice în UI
    noDiacritics: true,
    reinitialize: function() {
        // Funcție pentru reinițializarea componentelor când se navighează
        initializeBootstrapComponents();
        initializeDataTables();
        initializeFormValidation();
        setupGlobalEventListeners();
        // Reaplică transliterarea UI după randări dinamice
        try { if (window.FleetManagement && window.FleetManagement.noDiacritics !== false) removeDiacriticsFromPage(); } catch (e) { /* noop */ }
    }
};

// Funcție globală pentru reinițializarea componentelor după încărcare dinamică
window.reinitializeComponents = function() {
    if (window.FleetManagement && window.FleetManagement.reinitialize) {
        window.FleetManagement.reinitialize();
    }
};

// Theme-aware Chart.js defaults and live update
function applyChartTheme(theme) {
    if (typeof Chart === 'undefined') return;
    const isDark = (theme || '').toLowerCase() === 'dark';
    const cs = getComputedStyle(document.documentElement);
    const color = cs.getPropertyValue('--bs-body-color').trim() || (isDark ? '#e2e8f0' : '#212529');
    const grid = isDark ? 'rgba(226,232,240,0.12)' : 'rgba(33,37,41,0.12)';

    // Global defaults
    Chart.defaults.color = color;
    Chart.defaults.borderColor = grid;
    Chart.defaults.plugins = Chart.defaults.plugins || {};
    Chart.defaults.plugins.legend = Chart.defaults.plugins.legend || {};
    Chart.defaults.plugins.legend.labels = Chart.defaults.plugins.legend.labels || {};
    Chart.defaults.plugins.legend.labels.color = color;
    Chart.defaults.plugins.title = Chart.defaults.plugins.title || {};
    Chart.defaults.plugins.title.color = color;
    Chart.defaults.scales = Chart.defaults.scales || {};
    ['x','y'].forEach(axis => {
        const s = Chart.defaults.scales[axis] = Chart.defaults.scales[axis] || {};
        s.grid = s.grid || {};
        s.grid.color = grid;
        s.ticks = s.ticks || {};
        s.ticks.color = color;
    });

    // Update existing charts if any
    document.querySelectorAll('canvas').forEach((c) => {
        let chart = null;
        if (typeof Chart.getChart === 'function') {
            chart = Chart.getChart(c);
        } else if (c && c.chart) {
            chart = c.chart;
        }
        if (chart) {
            chart.options.plugins = chart.options.plugins || {};
            chart.options.plugins.legend = chart.options.plugins.legend || {};
            chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
            chart.options.plugins.legend.labels.color = color;
            chart.options.plugins.title = chart.options.plugins.title || {};
            chart.options.plugins.title.color = color;
            if (chart.options.scales) {
                Object.values(chart.options.scales).forEach(scale => {
                    if (scale.grid) scale.grid.color = grid;
                    if (scale.ticks) scale.ticks.color = color;
                });
            }
            try { chart.update(); } catch (e) { /* noop */ }
        }
    });
}

// Listen to theme changes
window.addEventListener('theme:change', (e) => {
    const theme = (e && e.detail && e.detail.theme) || (document.documentElement.getAttribute('data-bs-theme') || 'light');
    try { applyChartTheme(theme); } catch (err) { /* noop */ }
});

// --------------------------
// Diacritics removal utilities
// --------------------------
function transliterateRO(text) {
    if (!text || typeof text !== 'string') return text;
    const map = {
        'Ă':'A','Â':'A','Î':'I','Ș':'S','Ş':'S','Ț':'T','Ţ':'T',
        'ă':'a','â':'a','î':'i','ș':'s','ş':'s','ț':'t','ţ':'t'
    };
    // Quick check to skip if no characters to replace
    if (!/[ĂÂÎȘŞȚŢăâîșşțţ]/.test(text)) return text;
    return text.replace(/[ĂÂÎȘŞȚŢăâîșşțţ]/g, ch => map[ch] || ch);
}

function removeDiacriticsFromNode(node) {
    if (!node) return;
    if (node.nodeType === Node.TEXT_NODE) {
        node.nodeValue = transliterateRO(node.nodeValue);
    }
}

function removeDiacriticsFromPage() {
    try {
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function(n) {
                    // Skip inside script/style and elements explicitly opting-out
                    if (!n.parentNode) return NodeFilter.FILTER_REJECT;
                    const tag = n.parentNode.tagName;
                    if (tag === 'SCRIPT' || tag === 'STYLE') return NodeFilter.FILTER_REJECT;
                    if (n.parentNode.closest('[data-keep-diacritics]')) return NodeFilter.FILTER_REJECT;
                    return NodeFilter.FILTER_ACCEPT;
                }
            },
            false
        );
        const toProcess = [];
        let current;
        while ((current = walker.nextNode())) { toProcess.push(current); }
        toProcess.forEach(removeDiacriticsFromNode);

        // Observe future DOM mutations to keep UI normalized
        if (!window.__noDiacriticsObserver) {
            window.__noDiacriticsObserver = new MutationObserver((mutations) => {
                for (const m of mutations) {
                    if (m.type === 'childList') {
                        m.addedNodes && m.addedNodes.forEach(node => {
                            if (node.nodeType === Node.TEXT_NODE) {
                                removeDiacriticsFromNode(node);
                            } else if (node.nodeType === Node.ELEMENT_NODE && !node.matches('[data-keep-diacritics], script, style')) {
                                const w = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, null, false);
                                let t; const batch = [];
                                while ((t = w.nextNode())) { batch.push(t); }
                                batch.forEach(removeDiacriticsFromNode);
                            }
                        });
                    } else if (m.type === 'characterData' && m.target) {
                        removeDiacriticsFromNode(m.target);
                    }
                }
            });
            window.__noDiacriticsObserver.observe(document.body, { childList: true, characterData: true, subtree: true });
        }
    } catch (e) {
        // fail silently
    }
}