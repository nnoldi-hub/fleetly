/**
 * Datepicker Component
 * Provides date selection functionality for forms
 */
class DatePicker {
    constructor(selector, options = {}) {
        this.elements = document.querySelectorAll(selector);
        this.options = {
            format: 'Y-m-d',
            locale: 'ro',
            allowInput: true,
            ...options
        };
        
        this.init();
    }
    
    init() {
        this.elements.forEach(element => {
            this.setupDatepicker(element);
        });
    }
    
    setupDatepicker(element) {
        // Check if flatpickr is available
        if (typeof flatpickr !== 'undefined') {
            flatpickr(element, {
                dateFormat: this.options.format,
                locale: this.options.locale,
                allowInput: this.options.allowInput,
                ...this.options
            });
        } else {
            // Fallback to HTML5 date input
            element.type = 'date';
        }
    }
    
    // Utility methods
    static formatDate(date, format = 'Y-m-d') {
        if (!date) return '';
        
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        
        return format
            .replace('Y', year)
            .replace('m', month)
            .replace('d', day);
    }
    
    static parseDate(dateString) {
        return new Date(dateString);
    }
    
    static isValidDate(date) {
        return date instanceof Date && !isNaN(date);
    }
}

// Auto-initialize datepickers on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize basic datepickers
    new DatePicker('.datepicker');
    
    // Initialize date range pickers
    new DatePicker('.daterange-start', {
        onChange: function(selectedDates, dateStr, instance) {
            const endPicker = document.querySelector('.daterange-end');
            if (endPicker && endPicker._flatpickr) {
                endPicker._flatpickr.set('minDate', dateStr);
            }
        }
    });
    
    new DatePicker('.daterange-end', {
        onChange: function(selectedDates, dateStr, instance) {
            const startPicker = document.querySelector('.daterange-start');
            if (startPicker && startPicker._flatpickr) {
                startPicker._flatpickr.set('maxDate', dateStr);
            }
        }
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DatePicker;
}
