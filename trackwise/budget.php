<?php
/**
 * TrackWise Budget Management Page
 * Allows users to set and track monthly budgets
 */

// Include authentication and database
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Set page variables
$page_title = "Budget - TrackWise";
$page_heading = "Budget Management";
$page_description = "Set and track your monthly spending limits";

// Get current user ID
$user_id = getCurrentUserId();

// Initialize variables
$selected_month = $_POST['month'] ?? date('n');
$selected_year = $_POST['year'] ?? date('Y');
$budget_amount = "";
$budget_err = $success_message = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_budget'])) {
    
    // Validate budget amount
    if (empty(trim($_POST["budget_amount"]))) {
        $budget_err = "Please enter a budget amount.";
    } elseif (!is_numeric(trim($_POST["budget_amount"])) || trim($_POST["budget_amount"]) <= 0) {
        $budget_err = "Please enter a valid positive amount.";
    } else {
        $budget_amount = trim($_POST["budget_amount"]);
    }
    
    // Validate month and year
    $selected_month = intval($_POST['month']);
    $selected_year = intval($_POST['year']);
    
    if ($selected_month < 1 || $selected_month > 12) {
        $budget_err = "Invalid month selected.";
    } elseif ($selected_year < 2020 || $selected_year > 2030) {
        $budget_err = "Invalid year selected.";
    }
    
    // Check input errors before inserting/updating
    if (empty($budget_err)) {
        
        // Check if budget already exists for this month/year
        $sql = "SELECT id FROM budgets WHERE user_id = ? AND month = ? AND year = ?";
        $result = executeQuery($sql, [$user_id, $selected_month, $selected_year], "iii");
        
        if ($result->num_rows == 1) {
            // Update existing budget
            $sql = "UPDATE budgets SET amount = ? WHERE user_id = ? AND month = ? AND year = ?";
            $update_result = executeQuery($sql, [$budget_amount, $user_id, $selected_month, $selected_year], "diii");
            
            if ($update_result) {
                $success_message = "Budget updated successfully!";
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        } else {
            // Insert new budget
            $sql = "INSERT INTO budgets (user_id, month, year, amount) VALUES (?, ?, ?, ?)";
            $insert_result = executeQuery($sql, [$user_id, $selected_month, $selected_year, $budget_amount], "iiid");
            
            if ($insert_result) {
                $success_message = "Budget set successfully!";
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}

// Get current budget for selected month/year
$current_budget = 0;
$sql = "SELECT amount FROM budgets WHERE user_id = ? AND month = ? AND year = ?";
$result = executeQuery($sql, [$user_id, $selected_month, $selected_year], "iii");
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $current_budget = $row['amount'];
}

// Get expenses for selected month/year
$monthly_expenses = 0;
$sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
        WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
$result = executeQuery($sql, [$user_id, $selected_month, $selected_year], "iii");
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $monthly_expenses = $row['total'];
}

// Calculate remaining budget and percentage
$remaining_budget = $current_budget - $monthly_expenses;
$budget_percentage = $current_budget > 0 ? ($monthly_expenses / $current_budget) * 100 : 0;

// Get budget history (last 12 months)
$budget_history = [];
for ($i = 11; $i >= 0; $i--) {
    $history_month = date('n', strtotime("-$i months"));
    $history_year = date('Y', strtotime("-$i months"));
    $month_name = date('F Y', strtotime("-$i months"));
    
    // Get budget for this month
    $sql = "SELECT COALESCE(amount, 0) as budget_amount FROM budgets 
            WHERE user_id = ? AND month = ? AND year = ?";
    $result = executeQuery($sql, [$user_id, $history_month, $history_year], "iii");
    $budget_row = $result->fetch_assoc();
    $budget_amount = $budget_row['budget_amount'];
    
    // Get expenses for this month
    $sql = "SELECT COALESCE(SUM(amount), 0) as expense_total FROM expenses 
            WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
    $result = executeQuery($sql, [$user_id, $history_month, $history_year], "iii");
    $expense_row = $result->fetch_assoc();
    $expense_total = $expense_row['expense_total'];
    
    $budget_history[] = [
        'month' => $month_name,
        'budget' => $budget_amount,
        'expenses' => $expense_total,
        'remaining' => $budget_amount - $expense_total,
        'percentage' => $budget_amount > 0 ? ($expense_total / $budget_amount) * 100 : 0
    ];
}

// Generate month options for dropdown
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Generate year options (current year ± 3)
$current_year = date('Y');
$years = range($current_year - 3, $current_year + 3);
?>

<!-- Budget Overview Card -->
<div class="budget-overview">
    <div class="overview-header">
        <h3>Budget Overview</h3>
        <div class="period-selector">
            <form method="POST" class="period-form">
                <select name="month" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($months as $value => $label): ?>
                        <option value="<?php echo $value; ?>" 
                                <?php echo ($selected_month == $value) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo $year; ?>" 
                                <?php echo ($selected_year == $year) ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
    
    <div class="budget-stats">
        <div class="stat-item">
            <h4>Monthly Budget</h4>
            <p class="stat-value">$<?php echo number_format($current_budget, 2); ?></p>
        </div>
        <div class="stat-item">
            <h4>Current Expenses</h4>
            <p class="stat-value">$<?php echo number_format($monthly_expenses, 2); ?></p>
        </div>
        <div class="stat-item">
            <h4>Remaining</h4>
            <p class="stat-value <?php echo $remaining_budget < 0 ? 'negative' : ''; ?>">
                $<?php echo number_format($remaining_budget, 2); ?>
            </p>
        </div>
        <div class="stat-item">
            <h4>Budget Used</h4>
            <p class="stat-value <?php echo $budget_percentage > 100 ? 'negative' : ($budget_percentage > 80 ? 'warning' : ''); ?>">
                <?php echo round($budget_percentage); ?>%
            </p>
        </div>
    </div>
    
    <?php if ($current_budget > 0): ?>
    <div class="budget-progress">
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
</div>

<!-- Set Budget Form -->
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Set Budget</h2>
            <p>Define your monthly spending limit for <?php echo $months[$selected_month] . ' ' . $selected_year; ?></p>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <?php showSuccess($success_message); ?>
        <?php endif; ?>
        
        <form method="POST" class="budget-form">
            <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
            <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
            
            <div class="form-group">
                <label for="budget_amount">Budget Amount ($) *</label>
                <input type="number" name="budget_amount" id="budget_amount" class="form-control" 
                       value="<?php echo $current_budget > 0 ? $current_budget : ''; ?>" 
                       placeholder="0.00" step="0.01" min="0.01" required>
                <span class="error-message"><?php echo sanitizeOutput($budget_err); ?></span>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="set_budget" class="btn btn-primary">
                    <span>🎯</span> Set Budget
                </button>
                <button type="button" class="btn btn-outline" onclick="clearBudget()">
                    <span>🗑️</span> Clear Budget
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Budget History -->
<div class="history-container">
    <div class="history-header">
        <h3>Budget History</h3>
        <p>Track your budget performance over the last 12 months</p>
    </div>
    
    <div class="history-table">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Budget</th>
                    <th>Expenses</th>
                    <th>Remaining</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($budget_history as $history): ?>
                    <tr>
                        <td><?php echo $history['month']; ?></td>
                        <td>
                            <?php if ($history['budget'] > 0): ?>
                                $<?php echo number_format($history['budget'], 2); ?>
                            <?php else: ?>
                                <span class="no-budget">No budget</span>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format($history['expenses'], 2); ?></td>
                        <td class="<?php echo $history['remaining'] < 0 ? 'negative' : ''; ?>">
                            $<?php echo number_format($history['remaining'], 2); ?>
                        </td>
                        <td>
                            <?php if ($history['budget'] > 0): ?>
                                <?php if ($history['percentage'] > 100): ?>
                                    <span class="status-badge over-budget">Over Budget</span>
                                <?php elseif ($history['percentage'] > 80): ?>
                                    <span class="status-badge warning">Near Limit</span>
                                <?php else: ?>
                                    <span class="status-badge good">On Track</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="status-badge no-budget">No Budget</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Budget Tips -->
<div class="tips-container">
    <h3>Budget Management Tips</h3>
    <div class="tips-grid">
        <div class="tip-card">
            <div class="tip-icon">💡</div>
            <h4>Set Realistic Goals</h4>
            <p>Base your budget on past spending habits and adjust as needed.</p>
        </div>
        <div class="tip-card">
            <div class="tip-icon">📊</div>
            <h4>Track Regularly</h4>
            <p>Review your budget weekly to stay on top of your spending.</p>
        </div>
        <div class="tip-card">
            <div class="tip-icon">🎯</div>
            <h4>Use Categories</h4>
            <p>Set specific budgets for different expense categories.</p>
        </div>
        <div class="tip-card">
            <div class="tip-icon">🔄</div>
            <h4>Adjust Monthly</h4>
            <p>Review and adjust your budget based on changing needs.</p>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<script>
// Clear budget function
function clearBudget() {
    if (confirm('Are you sure you want to clear the budget for this month?')) {
        document.getElementById('budget_amount').value = '';
        document.forms[0].submit();
    }
}

// Auto-refresh budget stats when period changes
document.addEventListener('DOMContentLoaded', function() {
    const periodForm = document.querySelector('.period-form');
    if (periodForm) {
        periodForm.addEventListener('change', function() {
            this.submit();
        });
    }
});
</script>
