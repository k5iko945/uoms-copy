/**
 * Working Scholars Association
 * Main JavaScript File
 */
// Prevent page caching and back button issues
window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.reload();
    }
};
document.addEventListener('DOMContentLoaded', function() {
    // Initialize functionality when DOM is loaded
    initSidebar();
    initAnimations();
    initResponsive();
    initFormValidation();
    initDataTables();
});

/**
 * Initialize sidebar functionality
 */
function initSidebar() {
    // Mobile sidebar toggle
    const mobileToggle = document.getElementById('mobileToggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
            document.body.classList.toggle('sidebar-open');
        });
    }
    
    // Highlight active sidebar link
    const currentPagePath = window.location.pathname;
    const currentPage = currentPagePath.substring(currentPagePath.lastIndexOf('/') + 1);
    
    document.querySelectorAll('.sidebar-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        }
    });
    
    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.getElementById('mobileToggle');
        
        if (sidebar && sidebar.classList.contains('show') && 
            !sidebar.contains(event.target) && 
            sidebarToggle && !sidebarToggle.contains(event.target)) {
            sidebar.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    });
}

/**
 * Initialize animations
 */
function initAnimations() {
    // Add fade-in animations to cards and content
    setTimeout(function() {
        document.querySelectorAll('.card').forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('fade-in');
            }, index * 100);
        });
    }, 300);
    
    // Animated counters
    document.querySelectorAll('.card .text-gray-800').forEach(counter => {
        const countTo = parseInt(counter.textContent);
        
        if (!isNaN(countTo) && countTo > 0) {
            let count = 0;
            const interval = setInterval(() => {
                count += Math.ceil(countTo / 20);
                if (count >= countTo) {
                    counter.textContent = countTo;
                    clearInterval(interval);
                } else {
                    counter.textContent = count;
                }
            }, 50);
        }
    });
    
    // Add hover effects to buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });
}

/**
 * Initialize responsive behavior
 */
function initResponsive() {
    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.querySelector('.sidebar');
        if (window.innerWidth >= 768 && sidebar && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    });
    
    // Handle table responsiveness
    document.querySelectorAll('table.table').forEach(table => {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.classList.add('table-responsive');
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    // Form validation for needs-validation class
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Password strength indicator
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const value = this.value;
            const strength = document.querySelector('.password-strength');
            
            if (strength) {
                if (value.length < 6) {
                    strength.className = 'password-strength weak';
                    strength.textContent = 'Weak';
                } else if (value.length < 10) {
                    strength.className = 'password-strength medium';
                    strength.textContent = 'Medium';
                } else {
                    strength.className = 'password-strength strong';
                    strength.textContent = 'Strong';
                }
            }
        });
    });
}

/**
 * Initialize DataTables
 */
function initDataTables() {
    // Initialize DataTables if jQuery and DataTables are available
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('table.datatable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)"
            }
        });
    }
}

/**
 * Confirm dialog for important actions
 * @param {string} message - The confirmation message
 * @returns {boolean} Whether the action was confirmed
 */
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to perform this action?');
}

/**
 * Toggle visibility of an element
 * @param {string} elementId - The ID of the element to toggle
 */
function toggleVisibility(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.toggle('d-none');
    }
}

/**
 * Show a toast notification
 * @param {string} message - The notification message
 * @param {string} type - The notification type (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast bg-${type} text-white fade-in`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    // Toast content
    toast.innerHTML = `
        <div class="toast-header bg-${type} text-white">
            <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    // Add to container
    toastContainer.appendChild(toast);
    
    // Initialize Bootstrap toast
    if (typeof bootstrap !== 'undefined') {
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 3000
        });
        bsToast.show();
    } else {
        // Fallback if Bootstrap JS is not available
        toast.classList.add('show');
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
} 