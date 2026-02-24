/**
 * Inspire Shoes Billing System - JavaScript
 * 
 * Contains common JavaScript functions for the application
 * 
 * @package InspireShoes
 * @version 1.0
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.flash-message, .alert');
    flashMessages.forEach(function(msg) {
        setTimeout(function() {
            msg.style.opacity = '0';
            msg.style.transition = 'opacity 0.5s';
            setTimeout(function() {
                msg.remove();
            }, 500);
        }, 5000);
    });
    
    // Confirm delete dialogs
    const deleteLinks = document.querySelectorAll('a[href*="delete.php"]');
    deleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Search input clear button
    const searchInputs = document.querySelectorAll('input[type="search"], input[name="q"]');
    searchInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            // Enable/disable clear button based on input value
            const clearBtn = input.parentElement.querySelector('.clear-search');
            if (clearBtn) {
                clearBtn.style.display = input.value ? 'inline-block' : 'none';
            }
        });
    });
    
    // Price formatting
    const priceInputs = document.querySelectorAll('input[type="price"], input[name="price"]');
    priceInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
    });
    
    // Quantity input validation
    const quantityInputs = document.querySelectorAll('input[type="number"][name*="quantity"]');
    quantityInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const min = parseInt(this.min) || 1;
            const max = parseInt(this.max);
            let value = parseInt(this.value);
            
            if (value < min) {
                this.value = min;
            } else if (max && value > max) {
                this.value = max;
            }
        });
    });
});

/**
 * Show confirmation dialog
 * 
 * @param string message The confirmation message
 * @return boolean User's choice
 */
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to proceed?');
}

/**
 * Format currency for display
 * 
 * @param number amount The amount to format
 * @return string Formatted currency string
 */
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

/**
 * Calculate line total
 * 
 * @param number quantity The quantity
 * @param number price The unit price
 * @return number The line total
 */
function calculateLineTotal(quantity, price) {
    return (parseFloat(quantity) || 0) * (parseFloat(price) || 0);
}

/**
 * Update cart totals
 * 
 * Updates the subtotal, tax, discount, and grand total based on cart items
 */
function updateCartTotals() {
    const cartRows = document.querySelectorAll('#cartBody tr:not(:first-child)');
    let subtotal = 0;
    
    cartRows.forEach(function(row) {
        const quantityInput = row.querySelector('input[name="quantity[]"]');
        const priceCell = row.querySelector('.price');
        const totalCell = row.querySelector('.total');
        
        if (quantityInput && priceCell && totalCell) {
            const quantity = parseInt(quantityInput.value) || 0;
            const price = parseFloat(priceCell.dataset.price) || 0;
            const total = quantity * price;
            
            totalCell.textContent = formatCurrency(total);
            subtotal += total;
        }
    });
    
    // Update subtotal display
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    if (subtotalDisplay) {
        subtotalDisplay.textContent = formatCurrency(subtotal);
    }
    
    // Calculate tax
    const taxRateInput = document.getElementById('tax_rate') || document.querySelector('input[name="tax_rate"]');
    const taxDisplay = document.getElementById('taxDisplay');
    if (taxRateInput && taxDisplay) {
        const taxRate = parseFloat(taxRateInput.value) || 0;
        const tax = subtotal * (taxRate / 100);
        taxDisplay.textContent = formatCurrency(tax);
        
        // Update grand total
        const discountInput = document.getElementById('discount') || document.querySelector('input[name="discount"]');
        const grandTotalDisplay = document.getElementById('grandTotalDisplay');
        
        if (grandTotalDisplay) {
            const discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
            const grandTotal = subtotal + tax - discount;
            grandTotalDisplay.textContent = formatCurrency(grandTotal);
        }
    }
}

/**
 * Add product to cart
 * 
 * @param object product The product object
 */
function addProductToCart(product) {
    const cartBody = document.getElementById('cartBody');
    const emptyCartRow = document.getElementById('emptyCart');
    
    // Remove empty cart message
    if (emptyCartRow) {
        emptyCartRow.remove();
    }
    
    // Check if product already in cart
    const existingRows = cartBody.querySelectorAll('tr');
    let found = false;
    
    existingRows.forEach(function(row) {
        const productId = row.dataset.productId;
        const size = row.dataset.size;
        
        if (productId == product.id && size == product.size) {
            // Update quantity
            const qtyInput = row.querySelector('input[type="number"]');
            const newQty = parseInt(qtyInput.value) + 1;
            
            if (newQty <= product.stock) {
                qtyInput.value = newQty;
                updateCartTotals();
            } else {
                alert('Maximum stock reached');
            }
            found = true;
        }
    });
    
    if (!found) {
        // Add new row
        const row = document.createElement('tr');
        row.dataset.productId = product.id;
        row.dataset.size = product.size;
        row.dataset.price = product.price;
        
        row.innerHTML = `
            <td>${product.name}</td>
            <td>${product.size}</td>
            <td class="price" data-price="${product.price}">${formatCurrency(product.price)}</td>
            <td>
                <input type="number" name="quantity[]" value="1" min="1" max="${product.stock}" 
                       onchange="updateCartTotals()" style="width: 60px;">
            </td>
            <td class="total">${formatCurrency(product.price)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeCartItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        cartBody.appendChild(row);
        updateCartTotals();
    }
}

/**
 * Remove item from cart
 * 
 * @param element button The remove button
 */
function removeCartItem(button) {
    const row = button.closest('tr');
    row.remove();
    
    const cartBody = document.getElementById('cartBody');
    if (cartBody.children.length === 0) {
        cartBody.innerHTML = '<tr id="emptyCart"><td colspan="6" class="text-center">No items in cart</td></tr>';
    }
    
    updateCartTotals();
}

/**
 * Search products via AJAX
 * 
 * @param string query The search query
 */
function searchProducts(query) {
    if (query.length < 2) return;
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '../products/search.php?q=' + encodeURIComponent(query), true);
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            const results = JSON.parse(this.responseText);
            displaySearchResults(results);
        }
    };
    xhr.send();
}

/**
 * Display search results
 * 
 * @param array results The search results
 */
function displaySearchResults(results) {
    const resultsContainer = document.getElementById('searchResults');
    if (!resultsContainer) return;
    
    resultsContainer.innerHTML = '';
    
    if (results.length === 0) {
        resultsContainer.innerHTML = '<p class="text-muted">No products found</p>';
        return;
    }
    
    results.forEach(function(product) {
        const item = document.createElement('div');
        item.className = 'search-result-item';
        item.innerHTML = `
            <div>
                <strong>${product.name}</strong>
                <br><small>${product.brand} - Size: ${product.size}</small>
            </div>
            <div>
                <strong>${formatCurrency(product.price)}</strong>
                <br><small>Stock: ${product.stock}</small>
            </div>
        `;
        item.addEventListener('click', function() {
            addProductToCart(product);
            resultsContainer.innerHTML = '';
        });
        resultsContainer.appendChild(item);
    });
}
