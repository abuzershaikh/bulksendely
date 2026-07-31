/**
 * WhatsApp Commerce Module JavaScript
 * Handles AJAX calls, form submissions, and UI interactions
 */
var CommerceApp = {
    baseUrl: '',
    variantIndex: 0,

    init: function() {
        // Guarantee Path fallback specifically for CodeIgniter's subfolder setup
        var originPath = window.location.pathname;
        var subDir = originPath.indexOf('/frontend/') !== -1 ? originPath.substring(0, originPath.indexOf('/frontend/') + 10) : '/';
        
        // Force evaluation from current browser URL since window.PATH is broken globally
        this.baseUrl = window.location.origin + subDir;
        
        console.log('CommerceApp initialized. baseUrl is: ', this.baseUrl);
        
        // Auto-load lists on page
        if (document.getElementById('commerce-products-container')) {
            this.loadProducts();
        }
        if (document.getElementById('commerce-orders-container')) {
            this.loadOrders();
        }
        if (document.getElementById('commerce-customers-container')) {
            this.loadCustomers();
        }

        // Bind form submissions
        this.bindForms();

        // Listen for file manager selection updates on hidden inputs
        this.bindFileManagerListeners();
    },

    bindForms: function() {
        var self = this;

        // Automation Form
        var autoForm = document.getElementById('automation-form');
        if (autoForm) {
            autoForm.addEventListener('submit', function(e) {
                e.preventDefault();
                self.submitForm(this, function(res) {
                    if (res.status === 'success') {
                        window.location.reload();
                    }
                });
            });
        }

        // Settings Forms
        var settingsForm = document.getElementById('commerce-settings-form');
        if (settingsForm) {
            settingsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                self.submitForm(this);
            });
        }

        var payForm = document.getElementById('payment-settings-form');
        if (payForm) {
            payForm.addEventListener('submit', function(e) {
                e.preventDefault();
                self.submitForm(this);
            });
        }
    },

    /**
     * Listen for changes on hidden image inputs (triggered by File Manager popup)
     */
    bindFileManagerListeners: function() {
        var self = this;

        // Product image field
        var productImgField = document.getElementById('product-image-url');
        if (productImgField) {
            // Use MutationObserver to detect value changes set by File Manager
            var observer = new MutationObserver(function() {
                self.updateImagePreview('product-image-url', 'product-image-preview');
            });
            // Also listen for programmatic 'change' events
            productImgField.addEventListener('change', function() {
                self.updateImagePreview('product-image-url', 'product-image-preview');
            });
        }

        // Category image field
        var catImgField = document.getElementById('cat-image');
        if (catImgField) {
            catImgField.addEventListener('change', function() {
                self.updateImagePreview('cat-image', 'cat-image-preview');
            });
        }
    },

    /**
     * Update image preview when file manager selects a file
     */
    updateImagePreview: function(inputId, previewId) {
        var input = document.getElementById(inputId);
        var preview = document.getElementById(previewId);
        if (!input || !preview) return;

        var val = input.value;
        if (val) {
            // Build proper URL for preview
            var imgUrl = val;
            if (val.indexOf('http') !== 0 && val.indexOf('//') !== 0) {
                // Relative path from writable/ directory
                imgUrl = this.baseUrl + 'writable/' + val;
            }
            preview.innerHTML = '<img src="' + imgUrl + '" class="img-fluid rounded" style="max-height:200px;" onerror="this.parentNode.innerHTML=\'<div class=\\\'border rounded p-4 text-muted text-center\\\'><i class=\\\'fad fa-image fs-40 d-block mb-2\\\'></i><small>Image not found</small></div>\'">';
        } else {
            preview.innerHTML = '<div class="border rounded p-4 text-muted text-center"><i class="fad fa-image fs-40 d-block mb-2"></i><small>No image selected</small></div>';
        }
    },

    /**
     * Preview a local file upload (before form submission)
     */
    previewLocalUpload: function(fileInput, hiddenInputId, previewId) {
        var preview = document.getElementById(previewId);
        var hiddenInput = document.getElementById(hiddenInputId);
        
        if (fileInput.files && fileInput.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" class="img-fluid rounded" style="max-height:200px;">';
            };
            reader.readAsDataURL(fileInput.files[0]);
            
            // Clear the hidden URL input since we're uploading a file
            if (hiddenInput) {
                hiddenInput.value = '';
            }
        }
    },

    submitForm: function(form, callback) {
        var formData = new FormData(form);
        var action = form.action || form.getAttribute('action');

        // Add CSRF token if available
        if (typeof window.AJAX_TOKEN !== 'undefined' && typeof window.AJAX_HASH !== 'undefined') {
            formData.append(window.AJAX_TOKEN, window.AJAX_HASH);
        } else if (typeof csrf !== 'undefined') {
            formData.append('csrf', csrf);
        }

        fetch(action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.status === 'error') {
                CommerceApp.toast(res.message, 'error');
            } else {
                CommerceApp.toast(res.message || 'Success', 'success');
            }
            if (callback) callback(res);
        })
        .catch(function(err) {
            CommerceApp.toast('An error occurred', 'error');
            console.error(err);
        });
    },

    // ============================================
    // PRODUCTS
    // ============================================
    loadProducts: function(page) {
        var self = this;
        page = page || 1;
        var container = document.getElementById('commerce-products-container');
        if (!container) return;

        var formData = new FormData();
        formData.append('page', page);
        
        var searchEl = document.getElementById('commerce-product-search');
        var catEl = document.getElementById('commerce-product-category');
        var statusEl = document.getElementById('commerce-product-status');
        
        formData.append('search', searchEl ? searchEl.value : '');
        formData.append('category_id', catEl ? catEl.value : '');
        formData.append('status', statusEl ? statusEl.value : '');

        // Add CSRF token
        if (typeof window.AJAX_TOKEN !== 'undefined' && typeof window.AJAX_HASH !== 'undefined') {
            formData.append(window.AJAX_TOKEN, window.AJAX_HASH);
        } else if (typeof csrf !== 'undefined') {
            formData.append('csrf', csrf);
        }

        fetch(this.baseUrl + 'whatsapp_commerce/ajax_products', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { 
            return r.text().then(function(text) {
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error('Failed to parse JSON:', text.substring(0, 200));
                    return { data: '<div class="alert alert-danger m-3" style="text-align:left; max-width:100%; overflow:auto;"><b>URL Fetched:</b> ' + self.baseUrl + 'whatsapp_commerce/ajax_products<br><b>Server Text:</b><br><pre>' + text.replace(/</g, '&lt;') + '</pre></div>' };
                }
            });
        })
        .then(function(res) {
            container.innerHTML = res.data || '<div class="text-center py-5 text-muted">No products</div>';
        })
        .catch(function(err) {
            console.error('loadProducts error:', err);
            container.innerHTML = '<div class="text-center py-5 text-muted">Error loading products. Please check console.</div>';
        });
    },

    deleteProduct: function(ids) {
        if (!confirm('Are you sure you want to delete this product?')) return;
        var formData = new FormData();
        formData.append('ids', ids);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/catalog/delete', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message, res.status);
            if (res.status === 'success') CommerceApp.loadProducts();
        });
    },

    showSendProduct: function(productIds) {
        var instanceId = prompt('Enter WhatsApp Instance ID:');
        var chatId = prompt('Enter Chat ID (phone@s.whatsapp.net):');
        if (!instanceId || !chatId) return;

        var formData = new FormData();
        formData.append('product_ids', productIds);
        formData.append('instance_id', instanceId);
        formData.append('chat_id', chatId);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/send_product_to_chat', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message, res.status);
        });
    },

    addVariantRow: function() {
        var container = document.getElementById('variants-container');
        var idx = container.querySelectorAll('.variant-row').length;
        var html = '<div class="variant-row border rounded p-3 mb-2">' +
            '<div class="row g-2">' +
            '<div class="col-md-3"><input type="text" class="form-control form-control-sm" name="variants[' + idx + '][name]" placeholder="e.g. Large / Red"></div>' +
            '<div class="col-md-2"><input type="text" class="form-control form-control-sm" name="variants[' + idx + '][sku]" placeholder="SKU"></div>' +
            '<div class="col-md-3"><input type="number" class="form-control form-control-sm" name="variants[' + idx + '][price]" step="0.01" placeholder="Price"></div>' +
            '<div class="col-md-2"><input type="number" class="form-control form-control-sm" name="variants[' + idx + '][stock_qty]" placeholder="Stock"></div>' +
            '<div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest(\'.variant-row\').remove()"><i class="fad fa-trash"></i></button></div>' +
            '</div></div>';
        container.insertAdjacentHTML('beforeend', html);
    },

    // ============================================
    // CATEGORIES
    // ============================================
    resetCategoryForm: function() {
        var el;
        el = document.getElementById('cat-ids'); if (el) el.value = '';
        el = document.getElementById('cat-name'); if (el) el.value = '';
        el = document.getElementById('cat-description'); if (el) el.value = '';
        
        if (typeof File_manager !== 'undefined') {
            File_manager.loadSelectedFiles([]);
        }
    },

    editCategory: function(ids, name, desc, image) {
        var el;
        el = document.getElementById('cat-ids'); if (el) el.value = ids;
        el = document.getElementById('cat-name'); if (el) el.value = name;
        el = document.getElementById('cat-description'); if (el) el.value = desc;

        if (image && typeof File_manager !== 'undefined') {
            // Remove / if it starts with it, or maybe just pass image directly
            File_manager.loadSelectedFiles([image.toString().replace(/^uploads\//, 'uploads/')]);
        } else if (typeof File_manager !== 'undefined') {
            File_manager.loadSelectedFiles([]);
        }

        var modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        modal.show();
    },

    deleteCategory: function(ids) {
        if (!confirm('Delete this category? Products in this category will be moved to "No Category".')) return;
        var formData = new FormData();
        formData.append('ids', ids);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/category_delete', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message, res.status);
            if (res.status === 'success') {
                // Remove the card from DOM for instant feedback
                var card = document.getElementById('cat-card-' + ids);
                if (card) {
                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(function() { card.remove(); }, 300);
                } else {
                    location.reload();
                }
            }
        })
        .catch(function(err) {
            console.error('deleteCategory error:', err);
            CommerceApp.toast('Failed to delete category', 'error');
        });
    },

    // ============================================
    // ORDERS
    // ============================================
    loadOrders: function(page) {
        page = page || 1;
        var container = document.getElementById('commerce-orders-container');
        if (!container) return;

        var formData = new FormData();
        formData.append('page', page);
        
        var el;
        el = document.getElementById('order-search'); formData.append('search', el ? el.value : '');
        el = document.getElementById('order-status-filter'); formData.append('status', el ? el.value : '');
        el = document.getElementById('order-payment-filter'); formData.append('payment_status', el ? el.value : '');
        el = document.getElementById('order-from-date'); formData.append('from', el ? el.value : '');
        el = document.getElementById('order-to-date'); formData.append('to', el ? el.value : '');
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/ajax_orders', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            container.innerHTML = res.data || '<div class="text-center py-5 text-muted">No orders</div>';
        });
    },

    updateOrderStatus: function(ids, status) {
        var note = prompt('Add a note (optional):') || '';
        var formData = new FormData();
        formData.append('ids', ids);
        formData.append('status', status);
        formData.append('note', note);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        if (status === 'shipped') {
            var courier = prompt('Courier name (optional):');
            var tracking = prompt('Tracking ID (optional):');
            if (courier) formData.append('courier_name', courier);
            if (tracking) formData.append('tracking_id', tracking);
        }

        fetch(this.baseUrl + 'whatsapp_commerce/order_update_status', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message, res.status);
            if (res.status === 'success') location.reload();
        });
    },

    sendOrderUpdate: function(ids, type) {
        var formData = new FormData();
        formData.append('order_ids', ids);
        formData.append('message_type', type);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/send_order_update', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message, res.status);
        });
    },

    sendPaymentLink: function(ids) {
        var formData = new FormData();
        formData.append('order_ids', ids);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/send_payment_link', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message, res.status);
        });
    },

    verifyPayment: function(orderIds) {
        var refEl = document.getElementById('payment-ref-input');
        var ref = refEl ? refEl.value : '';
        if (!ref && !confirm('No transaction reference provided. Mark as paid anyway?')) return;

        var formData = new FormData();
        formData.append('order_ids', orderIds);
        formData.append('transaction_ref', ref);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/payment_verify', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message, res.status);
            if (res.status === 'success') location.reload();
        });
    },

    addOrderNote: function(ids) {
        var noteEl = document.getElementById('order-note-input');
        var note = noteEl ? noteEl.value : '';
        if (!note) return;

        var formData = new FormData();
        formData.append('ids', ids);
        formData.append('note', note);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/order_add_note', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message, res.status);
            if (res.status === 'success') location.reload();
        });
    },

    // ============================================
    // CUSTOMERS
    // ============================================
    loadCustomers: function(page) {
        page = page || 1;
        var container = document.getElementById('commerce-customers-container');
        if (!container) return;

        var formData = new FormData();
        formData.append('page', page);
        
        var searchEl = document.getElementById('customer-search');
        var tagEl = document.getElementById('customer-tag-filter');
        
        formData.append('search', searchEl ? searchEl.value : '');
        formData.append('tag', tagEl ? tagEl.value : '');
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/ajax_customers', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            container.innerHTML = res.data || '<div class="text-center py-5 text-muted">No customers</div>';
        });
    },

    addTag: function(customerIds) {
        var tagEl = document.getElementById('new-tag-input');
        var tag = tagEl ? tagEl.value : '';
        if (!tag) return;

        var formData = new FormData();
        formData.append('action', 'add');
        formData.append('customer_ids', customerIds);
        formData.append('tag', tag);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/customer_tag', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.status === 'success') location.reload();
        });
    },

    removeTag: function(customerIds, tag) {
        var formData = new FormData();
        formData.append('action', 'remove');
        formData.append('customer_ids', customerIds);
        formData.append('tag', tag);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/customer_tag', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.status === 'success') location.reload();
        });
    },

    // ============================================
    // AUTOMATIONS
    // ============================================
    resetAutomationForm: function() {
        var fields = ['auto-ids', 'auto-name', 'auto-trigger', 'auto-delay', 'auto-attempts', 'auto-instance', 'auto-message'];
        var defaults = ['', '', 'abandoned_cart', '30', '1', '', ''];
        for (var i = 0; i < fields.length; i++) {
            var el = document.getElementById(fields[i]);
            if (el) el.value = defaults[i];
        }
    },

    toggleAutomation: function(ids) {
        var formData = new FormData();
        formData.append('ids', ids);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/automation_toggle', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message || 'Updated', res.status || 'success');
        });
    },

    deleteAutomation: function(ids) {
        if (!confirm('Delete this automation rule?')) return;
        var formData = new FormData();
        formData.append('ids', ids);
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/automation_delete', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            CommerceApp.toast(res.message, res.status);
            if (res.status === 'success') location.reload();
        });
    },

    // ============================================
    // REPORTS
    // ============================================
    loadReport: function() {
        var fromEl = document.getElementById('report-from');
        var toEl = document.getElementById('report-to');

        var formData = new FormData();
        formData.append('from', fromEl ? fromEl.value : '');
        formData.append('to', toEl ? toEl.value : '');
        if(typeof window.AJAX_TOKEN!=='undefined' && typeof window.AJAX_HASH!=='undefined'){formData.append(window.AJAX_TOKEN, window.AJAX_HASH);} else if(typeof csrf!=='undefined'){formData.append('csrf',csrf);}

        fetch(this.baseUrl + 'whatsapp_commerce/ajax_report_data', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.status === 'success' && res.data) {
                var d = res.data;
                var el;
                el = document.getElementById('rpt-revenue');
                if (el) el.textContent = parseFloat(d.total_revenue || 0).toFixed(2);
                el = document.getElementById('rpt-orders');
                if (el) el.textContent = d.total_orders || 0;
                el = document.getElementById('rpt-customers');
                if (el) el.textContent = d.total_customers || 0;
                el = document.getElementById('rpt-aov');
                if (el) el.textContent = d.total_orders > 0 ? (d.total_revenue / d.total_orders).toFixed(2) : '0.00';
            }
        });
    },

    // ============================================
    // TOAST
    // ============================================
    toast: function(msg, type) {
        type = type || 'success';
        
        // Reuse existing notification system
        if (typeof Core !== 'undefined' && typeof Core.notify === 'function') {
            Core.notify(msg, type === 'error' ? 'error' : 'success');
            return;
        }
        if (typeof show_message === 'function') {
            show_message(msg, type);
            return;
        }
        if (typeof $ !== 'undefined' && typeof $.notify === 'function') {
            $.notify({ message: msg }, { type: type === 'error' ? 'danger' : type });
            return;
        }
        if (typeof toastr !== 'undefined') {
            toastr[type === 'error' ? 'error' : 'success'](msg);
            return;
        }

        // Fallback simple toast
        var toast = document.createElement('div');
        toast.className = 'commerce-toast ' + (type === 'error' ? 'toast-error' : 'toast-success');
        toast.innerHTML = '<i class="fad fa-' + (type === 'error' ? 'exclamation-circle' : 'check-circle') + ' me-2"></i>' + msg;
        document.body.appendChild(toast);

        setTimeout(function() { toast.classList.add('show'); }, 10);
        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    CommerceApp.init();
});
