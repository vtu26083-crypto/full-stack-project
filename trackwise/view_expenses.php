<?php
/**
 * TrackWise View Expenses Page
 * Displays, filters, and manages user expenses
 */

// Include authentication and database
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Set page variables
$page_title = "View Expenses - TrackWise";
$page_heading = "View Expenses";
$page_description = "Browse and manage your expense records";

// Get current user ID
$user_id = getCurrentUserId();

// Initialize filter variables
$filter_category = $_GET['category'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_search = $_GET['search'] ?? '';
$current_page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = 20;
$offset = ($current_page - 1) * $items_per_page;

// Get user's categories for filter dropdown
$categories = [];
$sql = "SELECT id, category_name FROM categories WHERE user_id = ? ORDER BY category_name";
$result = executeQuery($sql, [$user_id], "i");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Build WHERE clause for filtering
$where_conditions = ["e.user_id = ?"];
$params = [$user_id];
$types = "i";

// Add category filter
if (!empty($filter_category)) {
    $where_conditions[] = "e.category_id = ?";
    $params[] = $filter_category;
    $types .= "i";
}

// Add date range filter
if (!empty($filter_date_from)) {
    $where_conditions[] = "e.expense_date >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "e.expense_date <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

// Add search filter
if (!empty($filter_search)) {
    $where_conditions[] = "(e.description LIKE ? OR c.category_name LIKE ?)";
    $search_param = "%" . $filter_search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM expenses e JOIN categories c ON e.category_id = c.id $where_clause";
$count_result = executeQuery($count_sql, $params, $types);
$total_row = $count_result->fetch_assoc();
$total_expenses = $total_row['total'];
$total_pages = ceil($total_expenses / $items_per_page);

// Get expenses with pagination
$sql = "SELECT e.id, e.amount, e.description, e.expense_date, e.created_at, 
               c.category_name 
        FROM expenses e 
        JOIN categories c ON e.category_id = c.id 
        $where_clause 
        ORDER BY e.expense_date DESC, e.created_at DESC 
        LIMIT ? OFFSET ?";
$pagination_params = array_merge($params, [$items_per_page, $offset]);
$pagination_types = $types . "ii";
$result = executeQuery($sql, $pagination_params, $pagination_types);

$expenses = [];
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

// Calculate total amount for filtered results
$total_amount = 0;
if (!empty($expenses)) {
    $sum_sql = "SELECT COALESCE(SUM(e.amount), 0) as total FROM expenses e JOIN categories c ON e.category_id = c.id $where_clause";
    $sum_result = executeQuery($sum_sql, $params, $types);
    $sum_row = $sum_result->fetch_assoc();
    $total_amount = $sum_row['total'];
}

// Handle expense deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $expense_id = $_GET['delete'];
    
    if (canAccessExpense($expense_id)) {
        $delete_sql = "DELETE FROM expenses WHERE id = ? AND user_id = ?";
        $delete_result = executeQuery($delete_sql, [$expense_id, $user_id], "ii");
        
        if ($delete_result) {
            // Redirect to prevent resubmission
            $redirect_url = "view_expenses.php?" . http_build_query(array_filter($_GET, function($k) {
                return $k !== 'delete';
            }, ARRAY_FILTER_USE_KEY));
            redirect($redirect_url);
        }
    }
}
?>

<!-- Filters Section -->
<div class="filters-container">
    <div class="filters-header">
        <h3>Filter Expenses</h3>
        <button class="btn btn-outline btn-sm" onclick="clearFilters()">Clear Filters</button>
    </div>
    
    <form method="GET" class="filters-form">
        <div class="filter-row">
            <div class="filter-group">
                <label for="category">Category</label>
                <select name="category" id="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo ($filter_category == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitizeOutput($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date_from">From Date</label>
                <input type="date" name="date_from" id="date_from" class="form-control" 
                       value="<?php echo sanitizeOutput($filter_date_from); ?>">
            </div>
            
            <div class="filter-group">
                <label for="date_to">To Date</label>
                <input type="date" name="date_to" id="date_to" class="form-control" 
                       value="<?php echo sanitizeOutput($filter_date_to); ?>">
            </div>
            
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" name="search" id="search" class="form-control" 
                       placeholder="Search description or category..." 
                       value="<?php echo sanitizeOutput($filter_search); ?>">
            </div>
            
            <div class="filter-group filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
        </div>
    </form>
</div>

<!-- Summary Section -->
<div class="summary-container">
    <div class="summary-stats">
        <div class="summary-item">
            <h4>Total Expenses</h4>
            <p class="summary-value"><?php echo $total_expenses; ?></p>
        </div>
        <div class="summary-item">
            <h4>Total Amount</h4>
            <p class="summary-value">$<?php echo number_format($total_amount, 2); ?></p>
        </div>
        <div class="summary-item">
            <h4>Average Expense</h4>
            <p class="summary-value">
                $<?php echo number_format($total_expenses > 0 ? $total_amount / $total_expenses : 0, 2); ?>
            </p>
        </div>
    </div>
    
    <div class="export-actions">
        <button class="btn btn-outline" onclick="exportExpenses()">
            <span>📥</span> Export to CSV
        </button>
        <a href="add_expense.php" class="btn btn-primary">
            <span>➕</span> Add Expense
        </a>
    </div>
</div>

<!-- Expenses Table -->
<div class="table-container">
    <div class="table-header">
        <h3>Expenses List</h3>
        <div class="table-info">
            Showing <?php echo min($items_per_page, $total_expenses); ?> of <?php echo $total_expenses; ?> expenses
        </div>
    </div>
    
    <?php if (empty($expenses)): ?>
        <div class="empty-state">
            <h3>No Expenses Found</h3>
            <p>
                <?php 
                if (!empty($filter_category) || !empty($filter_date_from) || !empty($filter_date_to) || !empty($filter_search)) {
                    echo "No expenses match your current filters. Try adjusting your search criteria.";
                } else {
                    echo "You haven't recorded any expenses yet. Start by adding your first expense!";
                }
                ?>
            </p>
            <?php if (empty($filter_category) && empty($filter_date_from) && empty($filter_date_to) && empty($filter_search)): ?>
                <a href="add_expense.php" class="btn btn-primary">
                    <span>➕</span> Add Your First Expense
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="data-table expenses-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th class="amount-col">Amount</th>
                    <th class="actions-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                        <td>
                            <span class="category-badge">
                                <?php echo sanitizeOutput($expense['category_name']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $description = $expense['description'] ?: 'No description';
                            echo sanitizeOutput(strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description);
                            ?>
                        </td>
                        <td class="amount">$<?php echo number_format($expense['amount'], 2); ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-outline" onclick="editExpense(<?php echo $expense['id']; ?>)">
                                ✏️ Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $expense['id']; ?>)">
                                🗑️ Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3"><strong>Total</strong></td>
                    <td class="amount"><strong>$<?php echo number_format($total_amount, 2); ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" 
                   class="pagination-link">« Previous</a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                   class="pagination-link <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" 
                   class="pagination-link">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<script>
// Clear filters function
function clearFilters() {
    window.location.href = 'view_expenses.php';
}

// Confirm delete function
function confirmDelete(expenseId) {
    if (confirm('Are you sure you want to delete this expense? This action cannot be undone.')) {
        window.location.href = '?delete=' + expenseId + '&' + new URLSearchParams(window.location.search).toString();
    }
}

// Edit expense function (placeholder for future edit functionality)
function editExpense(expenseId) {
    alert('Edit functionality will be implemented in a future update.');
}

// Export to CSV function
function exportExpenses() {
    // Build current URL parameters
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    // Redirect to export URL
    window.location.href = 'view_expenses.php?' + params.toString();
}

// Handle export request
<?php
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="expenses_' . date('Y-m-d') . '.csv"');
    
    // Output CSV header
    echo "Date,Category,Description,Amount\n";
    
    // Output expense data
    foreach ($expenses as $expense) {
        $date = date('m/d/Y', strtotime($expense['expense_date']));
        $category = str_replace('"', '""', $expense['category_name']);
        $description = str_replace('"', '""', $expense['description'] ?: '');
        $amount = $expense['amount'];
        
        echo "\"$date\",\"$category\",\"$description\",\"$amount\"\n";
    }
    
    exit;
}
?>
</script>
