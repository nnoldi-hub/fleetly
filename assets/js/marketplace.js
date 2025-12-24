/**
 * Marketplace JavaScript
 * Handles cart operations, AJAX requests, and UI interactions
 */

const Marketplace = {
    /**
     * Add product to cart via AJAX
     */
    addToCart: function(productId, quantity = 1) {
        const button = event.target.closest('button');
        if (!button) return;
        
        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adăugare...';
        
        fetch(BASE_URL + 'modules/marketplace/?action=cart-add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('success', 'Produs adăugat în coș!', 
                    `<a href="${BASE_URL}modules/marketplace/?action=cart" class="alert-link">Vezi coșul</a>`);
                
                // Update cart count if element exists
                const cartCount = document.querySelector('.cart-count, .cart-badge');
                if (cartCount) {
                    cartCount.textContent = parseInt(cartCount.textContent) + parseInt(quantity);
                }
                
                // Update button
                button.innerHTML = '<i class="fas fa-check me-2"></i>Adăugat!';
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-success');
                
                setTimeout(() => {
                    button.innerHTML = originalHtml;
                    button.classList.remove('btn-outline-success');
                    button.classList.add('btn-success');
                    button.disabled = false;
                }, 2000);
            } else {
                this.showNotification('danger', 'Eroare', data.message);
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('danger', 'Eroare', 'Eroare la adăugare în coș');
            button.disabled = false;
            button.innerHTML = originalHtml;
        });
    },
    
    /**
     * Update cart item quantity
     */
    updateCartItem: function(cartItemId, quantity) {
        if (quantity < 1) return;
        
        fetch(BASE_URL + 'modules/marketplace/?action=cart-update', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `cart_item_id=${cartItemId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                this.showNotification('danger', 'Eroare', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('danger', 'Eroare', 'Eroare la actualizare');
        });
    },
    
    /**
     * Remove item from cart
     */
    removeCartItem: function(cartItemId) {
        if (!confirm('Ștergi acest produs din coș?')) return;
        
        fetch(BASE_URL + 'modules/marketplace/?action=cart-remove', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `cart_item_id=${cartItemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                this.showNotification('danger', 'Eroare', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('danger', 'Eroare', 'Eroare la ștergere');
        });
    },
    
    /**
     * Update order status (admin only)
     */
    updateOrderStatus: function(orderId, newStatus) {
        return fetch(BASE_URL + 'modules/marketplace/?action=admin-order-status', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `order_id=${orderId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('success', 'Success', 'Status actualizat cu succes!');
                return true;
            } else {
                this.showNotification('danger', 'Eroare', data.message);
                return false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('danger', 'Eroare', 'Eroare la actualizare status');
            return false;
        });
    },
    
    /**
     * Show Bootstrap alert notification
     */
    showNotification: function(type, title, message = '') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';
        alert.innerHTML = `
            <strong>${title}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    },
    
    /**
     * Initialize quantity controls
     */
    initQuantityControls: function() {
        document.querySelectorAll('.cart-item').forEach(item => {
            const cartId = item.dataset.cartId;
            const input = item.querySelector('.quantity-input');
            const minusBtn = item.querySelector('.qty-minus');
            const plusBtn = item.querySelector('.qty-plus');
            const removeBtn = item.querySelector('.remove-item');
            
            if (minusBtn) {
                minusBtn.addEventListener('click', () => {
                    if (input.value > 1) {
                        input.value = parseInt(input.value) - 1;
                        this.updateCartItem(cartId, input.value);
                    }
                });
            }
            
            if (plusBtn) {
                plusBtn.addEventListener('click', () => {
                    input.value = parseInt(input.value) + 1;
                    this.updateCartItem(cartId, input.value);
                });
            }
            
            if (input) {
                input.addEventListener('change', () => {
                    if (input.value < 1) input.value = 1;
                    this.updateCartItem(cartId, input.value);
                });
            }
            
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    this.removeCartItem(cartId);
                });
            }
        });
    },
    
    /**
     * Initialize admin order status controls
     */
    initAdminOrderControls: function() {
        document.querySelectorAll('.status-select').forEach(select => {
            select.dataset.originalValue = select.value;
            
            select.addEventListener('change', async function() {
                const orderId = this.dataset.orderId;
                const newStatus = this.value;
                const originalValue = this.dataset.originalValue;
                
                const confirmText = `Schimbi statusul comenzii la "${this.options[this.selectedIndex].text}"?`;
                if (!confirm(confirmText)) {
                    this.value = originalValue;
                    return;
                }
                
                const success = await Marketplace.updateOrderStatus(orderId, newStatus);
                if (success) {
                    this.dataset.originalValue = newStatus;
                } else {
                    this.value = originalValue;
                }
            });
        });
    }
};

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize quantity controls if cart items exist
    if (document.querySelector('.cart-item')) {
        Marketplace.initQuantityControls();
    }
    
    // Initialize admin controls if on admin page
    if (document.querySelector('.status-select')) {
        Marketplace.initAdminOrderControls();
    }
});

// Make Marketplace object globally available
window.Marketplace = Marketplace;
