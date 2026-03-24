<?php
/**
 * TrackWise Dashboard Page
 * Main analytics dashboard with charts and expense summaries
 */

// Include authentication and database
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Set page variables
$page_title = "Dashboard - TrackWise";
$page_heading = "Dashboard";
$page_description = "Overview of your expenses and budget analytics";

// Get current user ID
$user_id = getCurrentUserId();

// Get current month and year
$current_month = date('n');
$current_year = date('Y');

// Calculate dashboard statistics
$total_expenses = 0;
$monthly_expenses = 0;
$budget_amount = 0;
$remaining_budget = 0;
$budget_percentage = 0;

// Get total expenses for all time
$sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ?";
$result = executeQuery($sql, [$user_id], "i");
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $total_expenses = $row['total'];
}

// Get current month expenses
$sql = "SELECT COALESCE(SUM(amount), 0) as monthly_total FROM expenses 
        WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
$result = executeQuery($sql, [$user_id, $current_month, $current_year], "iii");
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $monthly_expenses = $row['monthly_total'];
}

// Get current month budget
$sql = "SELECT COALESCE(amount, 0) as budget_amount FROM budgets 
        WHERE user_id = ? AND month = ? AND year = ?";
$result = executeQuery($sql, [$user_id, $current_month, $current_year], "iii");
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $budget_amount = $row['budget_amount'];
}

// Calculate remaining budget and percentage
if ($budget_amount > 0) {
    $remaining_budget = $budget_amount - $monthly_expenses;
    $budget_percentage = ($monthly_expenses / $budget_amount) * 100;
}

// Get category-wise expenses for pie chart
$category_expenses = [];
$sql = "SELECT c.category_name, COALESCE(SUM(e.amount), 0) as total 
        FROM categories c 
        LEFT JOIN expenses e ON c.id = e.category_id AND e.user_id = ? 
        WHERE c.user_id = ? 
        GROUP BY c.id, c.category_name 
        HAVING total > 0 
        ORDER BY total DESC";
$result = executeQuery($sql, [$user_id, $user_id], "ii");
while ($row = $result->fetch_assoc()) {
    $category_expenses[] = $row;
}

// Get monthly trend data for bar chart (last 6 months)
$monthly_trend = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('n', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    
    $sql = "SELECT COALESCE(SUM(amount), 0) as monthly_total FROM expenses 
            WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
    $result = executeQuery($sql, [$user_id, $month, $year], "iii");
    $row = $result->fetch_assoc();
    
    $monthly_trend[] = [
        'month' => $month_name,
        'amount' => $row['monthly_total']
    ];
}

// Get recent expenses
$recent_expenses = [];
$sql = "SELECT e.id, e.amount, e.description, e.expense_date, c.category_name 
        FROM expenses e 
        JOIN categories c ON e.category_id = c.id 
        WHERE e.user_id = ? 
        ORDER BY e.expense_date DESC, e.created_at DESC 
        LIMIT 5";
$result = executeQuery($sql, [$user_id], "i");
while ($row = $result->fetch_assoc()) {
    $recent_expenses[] = $row;
}
?>

<!-- Dashboard Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
            <h3>Total Expenses</h3>
            <p class="stat-value">$<?php echo number_format($total_expenses, 2); ?></p>
            <span class="stat-label">All time</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
            <h3>Monthly Expenses</h3>
            <p class="stat-value">$<?php echo number_format($monthly_expenses, 2); ?></p>
            <span class="stat-label"><?php echo date('F Y'); ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🎯</div>
        <div class="stat-content">
            <h3>Budget</h3>
            <p class="stat-value">$<?php echo number_format($budget_amount, 2); ?></p>
            <span class="stat-label"><?php echo date('F Y'); ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
            <h3>Remaining</h3>
            <p class="stat-value <?php echo $remaining_budget < 0 ? 'negative' : ''; ?>">
                $<?php echo number_format($remaining_budget, 2); ?>
            </p>
            <span class="stat-label">
                <?php 
                if ($budget_amount > 0) {
                    echo round($budget_percentage) . '% used';
                } else {
                    echo 'No budget set';
                }
                ?>
            </span>
        </div>
    </div>
</div>

<!-- Budget Progress Bar -->
<?php if ($budget_amount > 0): ?>
<div class="budget-progress-container">
    <h3>Budget Progress</h3>
    <div class="progress-bar">
        <div class="progress-fill <?php echo $budget_percentage > 100 ? 'over-budget' : ($budget_percentage > 80 ? 'warning' : ''); ?>" 
             style="width: <?php echo min($budget_percentage, 100); ?>%;">
            <?php echo round($budget_percentage); ?>%
        </div>
    </div>
    <div class="progress-details">
        <span>$<?php echo number_format($monthly_expenses, 2); ?> spent</span>
        <span>$<?php echo number_format($remaining_budget, 2); ?> remaining</span>
    </div>
</div>
<?php endif; ?>

<!-- Charts Section -->
<div class="charts-container">
    <!-- Category-wise Expenses Pie Chart -->
    <div class="chart-card">
        <h3>Expenses by Category</h3>
        <div class="chart-container">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <!-- Monthly Trend Bar Chart -->
    <div class="chart-card">
        <h3>Monthly Trend (Last 6 Months)</h3>
        <div class="chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Expenses Table -->
<div class="recent-expenses-container">
    <div class="section-header">
        <h3>Recent Expenses</h3>
        <a href="view_expenses.php" class="btn btn-outline">View All</a>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_expenses)): ?>
                    <tr>
                        <td colspan="4" class="no-data">No expenses found. Start by adding your first expense!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_expenses as $expense): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                            <td><?php echo sanitizeOutput($expense['category_name']); ?></td>
                            <td><?php echo sanitizeOutput($expense['description'] ?: 'No description'); ?></td>
                            <td class="amount">$<?php echo number_format($expense['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <h3>Quick Actions</h3>
    <div class="action-buttons">
        <a href="add_expense.php" class="btn btn-primary">
            <span>➕</span> Add Expense
        </a>
        <a href="budget.php" class="btn btn-secondary">
            <span>🎯</span> Set Budget
        </a>
        <a href="categories.php" class="btn btn-secondary">
            <span>🏷️</span> Manage Categories
        </a>
        <a href="view_expenses.php" class="btn btn-outline">
            <span>📋</span> View All Expenses
        </a>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<script>
// Dashboard JavaScript functions
function initializeDashboard() {
    // Initialize Category Pie Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = <?php echo json_encode($category_expenses); ?>;
    
    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: categoryData.map(item => item.category_name),
            datasets: [{
                data: categoryData.map(item => parseFloat(item.total)),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#FF6384',
                    '#C9CBCF'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = '$' + context.parsed.toFixed(2);
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    
    // Initialize Monthly Trend Bar Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendData = <?php echo json_encode($monthly_trend); ?>;
    
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: trendData.map(item => item.month),
            datasets: [{
                label: 'Monthly Expenses',
                data: trendData.map(item => parseFloat(item.amount)),
                backgroundColor: '#36A2EB',
                borderColor: '#36A2EB',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(0);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Expenses: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            }
        }
    });
}
</script>
