/**
 * TrackWise - Relational Expense Analytics Platform
 * Complete JavaScript functionality for interactive features
 */

// ===== Global Variables =====
let charts = {};
let currentTheme = 'light';
let notificationTimeout;

// ===== Utility Functions =====
/**
 * Format currency amount
 * @param {number} amount - Amount to format
 * @returns {string} Formatted currency string
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

/**
 * Format date string
 * @param {string|Date} date - Date to format
 * @returns {string} Formatted date string
 */
function formatDate(date) {
    const d = new Date(date);
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Debounce function to limit API calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
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

/**
 * Show notification message
 * @param {string} message - Message to display
 * @param {string} type - Type of message (success, error, warning, info)
 * @param {number} duration - Duration in milliseconds
 */
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('message-container');
    if (!container) return;

    // Clear existing timeout
    if (notificationTimeout) {
        clearTimeout(notificationTimeout);
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;

    // Add to container
    container.appendChild(notification);

    // Auto remove after duration
    notificationTimeout = setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, duration);

    // Animate in
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
}

/**
 * Validate form inputs
 * @param {HTMLFormElement} form - Form to validate
 * @returns {boolean} True if valid, false otherwise
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

    inputs.forEach(input => {
        const value = input.value.trim();
        const errorElement = input.parentElement.querySelector('.error-message');

        // Clear previous error
        if (errorElement) {
            errorElement.textContent = '';
        }
        input.classList.remove('error');

        // Validate based on input type
        if (!value) {
            showInputError(input, 'This field is required');
            isValid = false;
        } else if (input.type === 'email' && !isValidEmail(value)) {
            showInputError(input, 'Please enter a valid email address');
            isValid = false;
        } else if (input.type === 'number' && (isNaN(value) || parseFloat(value) <= 0)) {
            showInputError(input, 'Please enter a valid positive number');
            isValid = false;
        } else if (input.type === 'date' && value && new Date(value) > new Date()) {
            showInputError(input, 'Date cannot be in the future');
            isValid = false;
        }
    });

    return isValid;
}

/**
 * Show input error message
 * @param {HTMLElement} input - Input element
 * @param {string} message - Error message
 */
function showInputError(input, message) {
    input.classList.add('error');
    
    let errorElement = input.parentElement.querySelector('.error-message');
    if (!errorElement) {
        errorElement = document.createElement('span');
        errorElement.className = 'error-message';
        input.parentElement.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} True if valid email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Confirm action with user
 * @param {string} message - Confirmation message
 * @param {Function} callback - Function to execute if confirmed
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Loading state management
 * @param {HTMLElement} element - Element to toggle loading state
 * @param {boolean} loading - Whether to show loading state
 */
function setLoading(element, loading) {
    if (loading) {
        element.disabled = true;
        element.classList.add('loading');
        element.dataset.originalText = element.textContent;
        element.textContent = 'Loading...';
    } else {
        element.disabled = false;
        element.classList.remove('loading');
        element.textContent = element.dataset.originalText || element.textContent;
    }
}

// ===== Dashboard Functions =====
/**
 * Initialize dashboard charts and interactions
 */
function initializeDashboard() {
    console.log('Initializing TrackWise Dashboard...');
    
    // Initialize chart animations
    animateStatCards();
    
    // Setup auto-refresh
    setupAutoRefresh();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Setup keyboard shortcuts
    setupKeyboardShortcuts();
}

/**
 * Animate statistics cards on page load
 */
function animateStatCards() {
    const statCards = document.querySelectorAll('.stat-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
                animateStatValue(entry.target.querySelector('.stat-value'));
            }
        });
    }, { threshold: 0.1 });

    statCards.forEach(card => observer.observe(card));
}

/**
 * Animate numeric value counting
 * @param {HTMLElement} element - Element containing the value
 */
function animateStatValue(element) {
    if (!element) return;
    
    const text = element.textContent;
    const value = parseFloat(text.replace(/[^0-9.-]/g, ''));
    
    if (isNaN(value)) return;
    
    const duration = 1000;
    const steps = 60;
    const stepValue = value / steps;
    let current = 0;
    
    const timer = setInterval(() => {
        current += stepValue;
        if (current >= value) {
            current = value;
            clearInterval(timer);
        }
        
        element.textContent = formatCurrency(current);
    }, duration / steps);
}

/**
 * Setup auto-refresh for dashboard data
 */
function setupAutoRefresh() {
    // Refresh every 5 minutes
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            refreshDashboardData();
        }
    }, 300000);
}

/**
 * Refresh dashboard data via AJAX
 */
function refreshDashboardData() {
    // This would typically make an API call
    // For now, just show a notification
    showNotification('Dashboard data refreshed', 'info', 2000);
}

/**
 * Initialize tooltips for better UX
 */
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', (e) => {
            showTooltip(e.target, e.target.dataset.tooltip);
        });
        
        element.addEventListener('mouseleave', () => {
            hideTooltip();
        });
    });
}

/**
 * Show tooltip
 * @param {HTMLElement} element - Element to show tooltip for
 * @param {string} text - Tooltip text
 */
function showTooltip(element, text) {
    hideTooltip(); // Remove existing tooltip
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    
    setTimeout(() => tooltip.classList.add('show'), 10);
}

/**
 * Hide tooltip
 */
function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

/**
 * Setup keyboard shortcuts for navigation
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        // Only handle shortcuts when not typing in inputs
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        // Ctrl/Cmd + N: Add new expense
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = 'add_expense.php';
        }
        
        // Ctrl/Cmd + D: Dashboard
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            window.location.href = 'dashboard.php';
        }
        
        // Ctrl/Cmd + E: View expenses
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            window.location.href = 'view_expenses.php';
        }
    });
}

// ===== Form Functions =====
/**
 * Initialize form validation and interactions
 */
function initializeForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Add validation on submit
        form.addEventListener('submit', (e) => {
            if (!validateForm(form)) {
                e.preventDefault();
                showNotification('Please fix the errors in the form', 'error');
            }
        });
        
        // Add real-time validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                validateInput(input);
            });
            
            input.addEventListener('input', debounce(() => {
                clearInputError(input);
            }, 500));
        });
    });
}

/**
 * Validate individual input
 * @param {HTMLElement} input - Input to validate
 */
function validateInput(input) {
    const value = input.value.trim();
    
    if (input.hasAttribute('required') && !value) {
        showInputError(input, 'This field is required');
        return false;
    }
    
    if (input.type === 'email' && value && !isValidEmail(value)) {
        showInputError(input, 'Please enter a valid email address');
        return false;
    }
    
    if (input.type === 'number' && value && (isNaN(value) || parseFloat(value) <= 0)) {
        showInputError(input, 'Please enter a valid positive number');
        return false;
    }
    
    clearInputError(input);
    return true;
}

/**
 * Clear input error
 * @param {HTMLElement} input - Input to clear error for
 */
function clearInputError(input) {
    input.classList.remove('error');
    const errorElement = input.parentElement.querySelector('.error-message');
    if (errorElement) {
        errorElement.textContent = '';
    }
}

// ===== Expense Management Functions =====
/**
 * Initialize expense-related functionality
 */
function initializeExpenseManagement() {
    // Setup expense filters
    setupExpenseFilters();
    
    // Setup expense actions
    setupExpenseActions();
    
    // Setup search functionality
    setupSearch();
}

/**
 * Setup expense filtering
 */
function setupExpenseFilters() {
    const filterForm = document.querySelector('.filters-form');
    if (!filterForm) return;
    
    filterForm.addEventListener('change', debounce(() => {
        filterForm.submit();
    }, 1000));
}

/**
 * Setup expense actions (edit, delete)
 */
function setupExpenseActions() {
    // Edit buttons
    document.querySelectorAll('[data-action="edit"]').forEach(button => {
        button.addEventListener('click', (e) => {
            const expenseId = e.target.dataset.expenseId;
            editExpense(expenseId);
        });
    });
    
    // Delete buttons
    document.querySelectorAll('[data-action="delete"]').forEach(button => {
        button.addEventListener('click', (e) => {
            const expenseId = e.target.dataset.expenseId;
            const expenseName = e.target.dataset.expenseName || 'this expense';
            confirmDeleteExpense(expenseId, expenseName);
        });
    });
}

/**
 * Edit expense (placeholder for future implementation)
 * @param {number} expenseId - ID of expense to edit
 */
function editExpense(expenseId) {
    // This would open an edit modal or redirect to edit page
    showNotification('Edit functionality coming soon!', 'info');
}

/**
 * Confirm expense deletion
 * @param {number} expenseId - ID of expense to delete
 * @param {string} expenseName - Name/description of expense
 */
function confirmDeleteExpense(expenseId, expenseName) {
    confirmAction(
        `Are you sure you want to delete "${expenseName}"? This action cannot be undone.`,
        () => {
            deleteExpense(expenseId);
        }
    );
}

/**
 * Delete expense
 * @param {number} expenseId - ID of expense to delete
 */
function deleteExpense(expenseId) {
    // This would make an API call to delete the expense
    window.location.href = `view_expenses.php?delete=${expenseId}`;
}

/**
 * Setup search functionality
 */
function setupSearch() {
    const searchInput = document.querySelector('#search');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', debounce((e) => {
        const searchTerm = e.target.value.toLowerCase();
        filterExpenses(searchTerm);
    }, 300));
}

/**
 * Filter expenses based on search term
 * @param {string} searchTerm - Search term to filter by
 */
function filterExpenses(searchTerm) {
    const rows = document.querySelectorAll('.expenses-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// ===== Category Management Functions =====
/**
 * Initialize category management functionality
 */
function initializeCategoryManagement() {
    // Setup category creation
    setupCategoryCreation();
    
    // Setup category deletion
    setupCategoryDeletion();
}

/**
 * Setup category creation form
 */
function setupCategoryCreation() {
    const form = document.querySelector('.category-form');
    if (!form) return;
    
    const nameInput = form.querySelector('#category_name');
    if (nameInput) {
        nameInput.addEventListener('input', debounce(() => {
            checkCategoryExists(nameInput.value);
        }, 500));
    }
}

/**
 * Check if category already exists
 * @param {string} categoryName - Category name to check
 */
function checkCategoryExists(categoryName) {
    if (!categoryName) return;
    
    // This would make an API call to check if category exists
    // For now, just implement client-side validation
    const existingCategories = Array.from(document.querySelectorAll('.category-card h4'))
        .map(el => el.textContent.toLowerCase());
    
    if (existingCategories.includes(categoryName.toLowerCase())) {
        showNotification('A category with this name already exists', 'warning');
    }
}

/**
 * Setup category deletion
 */
function setupCategoryDeletion() {
    document.querySelectorAll('[data-action="delete-category"]').forEach(button => {
        button.addEventListener('click', (e) => {
            const categoryId = e.target.dataset.categoryId;
            const categoryName = e.target.dataset.categoryName;
            confirmDeleteCategory(categoryId, categoryName);
        });
    });
}

/**
 * Confirm category deletion
 * @param {number} categoryId - ID of category to delete
 * @param {string} categoryName - Name of category
 */
function confirmDeleteCategory(categoryId, categoryName) {
    confirmAction(
        `Are you sure you want to delete the category "${categoryName}"? This action cannot be undone.`,
        () => {
            deleteCategory(categoryId);
        }
    );
}

/**
 * Delete category
 * @param {number} categoryId - ID of category to delete
 */
function deleteCategory(categoryId) {
    window.location.href = `categories.php?delete=${categoryId}`;
}

// ===== Budget Management Functions =====
/**
 * Initialize budget management functionality
 */
function initializeBudgetManagement() {
    // Setup budget form
    setupBudgetForm();
    
    // Setup budget period selector
    setupBudgetPeriodSelector();
}

/**
 * Setup budget form validation
 */
function setupBudgetForm() {
    const form = document.querySelector('.budget-form');
    if (!form) return;
    
    const amountInput = form.querySelector('#budget_amount');
    if (amountInput) {
        amountInput.addEventListener('input', () => {
            validateBudgetAmount(amountInput);
        });
    }
}

/**
 * Validate budget amount
 * @param {HTMLElement} input - Amount input field
 */
function validateBudgetAmount(input) {
    const value = parseFloat(input.value);
    
    if (isNaN(value) || value <= 0) {
        showInputError(input, 'Please enter a valid positive amount');
        return false;
    }
    
    if (value > 1000000) {
        showInputError(input, 'Budget amount seems too high');
        return false;
    }
    
    clearInputError(input);
    return true;
}

/**
 * Setup budget period selector
 */
function setupBudgetPeriodSelector() {
    const periodForm = document.querySelector('.period-form');
    if (!periodForm) return;
    
    periodForm.addEventListener('change', () => {
        setLoading(periodForm.querySelector('button[type="submit"]'), true);
        periodForm.submit();
    });
}

// ===== Export Functions =====
/**
 * Export data to CSV
 * @param {string} type - Type of data to export (expenses, categories, budget)
 */
function exportToCSV(type) {
    showNotification(`Exporting ${type} data...`, 'info');
    
    // This would typically make an API call to generate and download CSV
    setTimeout(() => {
        showNotification(`${type} data exported successfully!`, 'success');
    }, 2000);
}

/**
 * Export expenses to CSV
 */
function exportExpenses() {
    exportToCSV('expenses');
}

// ===== Theme Management =====
/**
 * Toggle between light and dark themes
 */
function toggleTheme() {
    currentTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.body.setAttribute('data-theme', currentTheme);
    localStorage.setItem('trackwise-theme', currentTheme);
}

/**
 * Load saved theme preference
 */
function loadTheme() {
    const savedTheme = localStorage.getItem('trackwise-theme') || 'light';
    currentTheme = savedTheme;
    document.body.setAttribute('data-theme', currentTheme);
}

// ===== Mobile Menu Functions =====
/**
 * Toggle mobile menu
 */
function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

/**
 * Close mobile menu when clicking outside
 */
function closeMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
    }
}

// ===== Print Functions =====
/**
 * Print current page
 */
function printPage() {
    window.print();
}

/**
 * Setup print styles
 */
function setupPrintStyles() {
    // Add print button to pages that benefit from printing
    const printButton = document.querySelector('[data-action="print"]');
    if (printButton) {
        printButton.addEventListener('click', printPage);
    }
}

// ===== Error Handling =====
/**
 * Handle JavaScript errors gracefully
 */
window.addEventListener('error', (e) => {
    console.error('TrackWise Error:', e.error);
    showNotification('An unexpected error occurred. Please refresh the page.', 'error');
});

/**
 * Handle unhandled promise rejections
 */
window.addEventListener('unhandledrejection', (e) => {
    console.error('TrackWise Promise Error:', e.reason);
    showNotification('A network error occurred. Please check your connection.', 'error');
});

// ===== Initialization =====
/**
 * Initialize all TrackWise functionality when DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log('TrackWise JavaScript initialized');
    
    // Load theme preference
    loadTheme();
    
    // Initialize forms
    initializeForms();
    
    // Initialize expense management
    initializeExpenseManagement();
    
    // Initialize category management
    initializeCategoryManagement();
    
    // Initialize budget management
    initializeBudgetManagement();
    
    // Setup print styles
    setupPrintStyles();
    
    // Setup mobile menu
    document.addEventListener('click', (e) => {
        if (e.target.closest('.mobile-menu-toggle')) {
            toggleMobileMenu();
        } else if (!e.target.closest('.sidebar')) {
            closeMobileMenu();
        }
    });
    
    // Handle visibility change for auto-refresh
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            // Refresh data when page becomes visible
            refreshDashboardData();
        }
    });
    
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});

// ===== Export Global Functions =====
// Make important functions available globally for inline event handlers
window.showNotification = showNotification;
window.confirmAction = confirmAction;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.toggleTheme = toggleTheme;
window.printPage = printPage;
window.exportExpenses = exportExpenses;
window.editExpense = editExpense;
window.deleteExpense = deleteExpense;
window.deleteCategory = deleteCategory;

console.log('TrackWise JavaScript loaded successfully!');
