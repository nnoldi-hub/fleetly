/ assets/js/modules/documents.js - JavaScript specific pentru modulul documente
document.addEventListener('DOMContentLoaded', function() {
    initializeDocumentsModule();
});

function initializeDocumentsModule() {
    const renewModal = new bootstrap.Modal(document.getElementById('renewDocumentModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    let currentDocumentId = null;
    
    // Event listeners pentru reînnoire documente
    document.querySelectorAll('.renew-document-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentDocumentId = this.dataset.documentId;
            
            // Setează data minimă la data curentă
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('newExpiryDate').min = today;
            
            renewModal.show();
        });
    });
    
    // Salvare reînnoire
    document.getElementById('saveRenewalBtn').addEventListener('click', function() {
        const form = document.getElementById('renewDocumentForm');
        const formData = new FormData(form);
        
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Se procesează...';
        
        fetch(BASE_URL + 'documents/renew', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renewModal.hide();
                
                // Actualizează rândul din tabel
                const row = document.querySelector(`tr[data-document-id="${currentDocumentId}"]`);
                if (row) {
                    const expiryCell = row.cells[4]; // Coloana cu data expirării
                    const newDate = new Date(data.new_expiry_date);
                    expiryCell.innerHTML = `<span class="text-success">${newDate.toLocaleDateString('ro-RO')}</span>`;
                    
                    // Actualizează badge-ul de status
                    const statusCell = row.cells[6];
                    statusCell.innerHTML = '<span class="badge bg-success">Activ</span>';
                }
                
                showAlert('success', data.message);
                
                // Reset form
                form.reset();
                form.classList.remove('was-validated');
            } else {
                showAlert('error', data.error);
            }
        })
        .catch(error => {
            showAlert('error', 'Eroare la reînnoirea documentului');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = 'Reînnoiește';
        });
    });
    
    // Event listeners pentru ștergere documente
    document.querySelectorAll('.delete-document-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentDocumentId = this.dataset.documentId;
            const documentType = this.dataset.documentType;
            
            document.getElementById('documentToDelete').textContent = documentType;
            deleteModal.show();
        });
    });
    
    // Confirmare ștergere
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (!currentDocumentId) return;
        
        const formData = new FormData();
        formData.append('id', currentDocumentId);
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Se șterge...';
        
        fetch(BASE_URL + 'documents/delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                deleteModal.hide();
                
                // Șterge rândul din tabel
                const row = document.querySelector(`tr[data-document-id="${currentDocumentId}"]`);
                if (row) {
                    row.remove();
                }
                
                showAlert('success', data.message);
            } else {
                showAlert('error', data.error);
            }
        })
        .catch(error => {
            showAlert('error', 'Eroare la ștergerea documentului');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = 'Șterge';
            currentDocumentId = null;
        });
    });
    
    // Validare date pentru formulare documente
    const expiryDateInputs = document.querySelectorAll('input[type="date"][name$="expiry_date"]');
    expiryDateInputs.forEach(input => {
        input.addEventListener('change', function() {
            const issueDate = document.querySelector('input[name$="issue_date"]');
            if (issueDate && issueDate.value && this.value) {
                if (new Date(this.value) <= new Date(issueDate.value)) {
                    this.setCustomValidity('Data expirării trebuie să fie ulterioară datei emiterii');
                } else {
                    this.setCustomValidity('');
                }
            }
        });
    });
    
    // Auto-calculare zile până la expirare
    const reminderDaysInput = document.querySelector('input[name="reminder_days"]');
    if (reminderDaysInput) {
        const expiryInput = document.querySelector('input[name="expiry_date"]');
        if (expiryInput) {
            expiryInput.addEventListener('change', function() {
                if (this.value) {
                    const expiryDate = new Date(this.value);
                    const today = new Date();
                    const diffTime = expiryDate - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    if (diffDays > 0 && diffDays <= 365) {
                        const suggestedDays = Math.min(30, Math.floor(diffDays / 4));
                        if (!reminderDaysInput.value || reminderDaysInput.value == 30) {
                            reminderDaysInput.value = suggestedDays;
                        }
                    }
                }
            });
        }
    }
    
    // Filtrare dinamică în tabel
    const searchInput = document.querySelector('#documentSearch');
    if (searchInput) {
        const debouncedSearch = debounce(function(searchTerm) {
            filterDocumentsTable(searchTerm);
        }, 300);
        
        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value);
        });
    }
    
    // Upload și preview pentru fișiere
    const fileInputs = document.querySelectorAll('input[type="file"][accept*="pdf"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Validare dimensiune fișier (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('error', 'Fișierul este prea mare. Dimensiunea maximă permisă este 5MB.');
                    this.value = '';
                    return;
                }
                
                // Validare tip fișier
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    showAlert('error', 'Tip de fișier nepermis. Sunt permise doar PDF, JPG și PNG.');
                    this.value = '';
                    return;
                }
                
                // Afișare nume fișier
                const label = this.nextElementSibling;
                if (label && label.classList.contains('form-label')) {
                    label.textContent = `Fișier selectat: ${file.name}`;
                    label.classList.add('text-success');
                }
            }
        });
    });
}

function filterDocumentsTable(searchTerm) {
    const table = document.getElementById('documentsTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let found = false;
        
        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(term)) {
                found = true;
            }
        });
        
        row.style.display = found ? '' : 'none';
    });
}

// Funcții helper pentru modulul documente
function calculateExpiryStatus(expiryDate) {
    if (!expiryDate) return null;
    
    const today = new Date();
    const expiry = new Date(expiryDate);
    const diffTime = expiry - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) {
        return { status: 'expired', days: Math.abs(diffDays), class: 'text-danger' };
    } else if (diffDays <= 30) {
        return { status: 'expiring', days: diffDays, class: 'text-warning' };
    } else {
        return { status: 'valid', days: diffDays, class: 'text-success' };
    }
}

function updateDocumentStatusBadge(documentId, newStatus) {
    const row = document.querySelector(`tr[data-document-id="${documentId}"]`);
    if (!row) return;
    
    const statusCell = row.cells[6]; // Coloana status
    const badgeClasses = {
        'active': 'bg-success',
        'expired': 'bg-danger',
        'cancelled': 'bg-secondary'
    };
    
    const statusTexts = {
        'active': 'Activ',
        'expired': 'Expirat',
        'cancelled': 'Anulat'
    };
    
    statusCell.innerHTML = `<span class="badge ${badgeClasses[newStatus] || 'bg-secondary'}">
                               ${statusTexts[newStatus] || newStatus}
                           </span>`;
}

// Export funcții pentru utilizare globală
window.DocumentsModule = {
    calculateExpiryStatus,
    updateDocumentStatusBadge,
    filterDocumentsTable
};

// Mută codul CSS într-un fișier separat, de exemplu: assets/css/modules/documents.css