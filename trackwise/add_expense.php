<?php
/**
 * TrackWise Add Expense Page
 * Allows users to add new expenses with validation
 */

// Include authentication and database
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Set page variables
$page_title = "Add Expense - TrackWise";
$page_heading = "Add New Expense";
$page_description = "Record your expense details";

// Get current user ID
$user_id = getCurrentUserId();

// Initialize variables
$category_id = $amount = $description = $expense_date = "";
$category_err = $amount_err = $description_err = $date_err = "";
$success_message = "";

// Get user's categories for dropdown
$categories = [];
$sql = "SELECT id, category_name FROM categories WHERE user_id = ? ORDER BY category_name";
$result = executeQuery($sql, [$user_id], "i");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate category
    if (empty($_POST["category_id"])) {
        $category_err = "Please select a category.";
    } else {
        $category_id = $_POST["category_id"];
        
        // Verify user owns this category
        if (!canAccessCategory($category_id)) {
            $category_err = "Invalid category selected.";
        }
    }
    
    // Validate amount
    if (empty(trim($_POST["amount"]))) {
        $amount_err = "Please enter the amount.";
    } elseif (!is_numeric(trim($_POST["amount"])) || trim($_POST["amount"]) <= 0) {
        $amount_err = "Please enter a valid positive amount.";
    } else {
        $amount = trim($_POST["amount"]);
    }
    
    // Validate description (optional)
    $description = trim($_POST["description"]);
    if (strlen($description) > 500) {
        $description_err = "Description must be less than 500 characters.";
    }
    
    // Validate expense date
    if (empty(trim($_POST["expense_date"]))) {
        $date_err = "Please select the expense date.";
    } else {
        $expense_date = trim($_POST["expense_date"]);
        
        // Validate date format and range
        $date_obj = DateTime::createFromFormat('Y-m-d', $expense_date);
        if (!$date_obj || $date_obj->format('Y-m-d') !== $expense_date) {
            $date_err = "Please enter a valid date.";
        } elseif ($date_obj > new DateTime()) {
            $date_err = "Expense date cannot be in the future.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($category_err) && empty($amount_err) && empty($description_err) && empty($date_err)) {
        
        // Insert expense into database
        $sql = "INSERT INTO expenses (user_id, category_id, amount, description, expense_date) VALUES (?, ?, ?, ?, ?)";
        $result = executeQuery($sql, [$user_id, $category_id, $amount, $description, $expense_date], "iisss");
        
        if ($result) {
            $success_message = "Expense added successfully!";
            
            // Reset form variables
            $category_id = $amount = $description = $expense_date = "";
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
}
?>

<!-- Add Expense Form -->
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Add New Expense</h2>
            <p>Fill in the details below to record your expense</p>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <?php showSuccess($success_message); ?>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="expense-form" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select name="category_id" id="category_id" class="form-control" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitizeOutput($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message"><?php echo sanitizeOutput($category_err); ?></span>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount ($) *</label>
                    <input type="number" name="amount" id="amount" class="form-control" 
                           value="<?php echo sanitizeOutput($amount); ?>" 
                           placeholder="0.00" step="0.01" min="0.01" required>
                    <span class="error-message"><?php echo sanitizeOutput($amount_err); ?></span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="expense_date">Date *</label>
                    <input type="date" name="expense_date" id="expense_date" class="form-control" 
                           value="<?php echo sanitizeOutput($expense_date ?: date('Y-m-d')); ?>" 
                           max="<?php echo date('Y-m-d'); ?>" required>
                    <span class="error-message"><?php echo sanitizeOutput($date_err); ?></span>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" 
                              placeholder="Add a note about this expense (optional)" 
                              rows="3" maxlength="500"><?php echo sanitizeOutput($description); ?></textarea>
                    <span class="error-message"><?php echo sanitizeOutput($description_err); ?></span>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>💰</span> Add Expense
                </button>
                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Quick Add Categories -->
<?php if (empty($categories)): ?>
<div class="empty-state">
    <h3>No Categories Found</h3>
    <p>You need to create categories before adding expenses.</p>
    <a href="categories.php" class="btn btn-primary">
        <span>🏷️</span> Create Categories
    </a>
</div>
<?php endif; ?>

<!-- Recent Expenses Preview -->
<?php if (!empty($success_message)): ?>
<div class="recent-preview">
    <h3>Recent Expenses</h3>
    <div class="mini-table">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Get last 3 expenses for preview
                $sql = "SELECT e.expense_date, c.category_name, e.amount 
                        FROM expenses e 
                        JOIN categories c ON e.category_id = c.id 
                        WHERE e.user_id = ? 
                        ORDER BY e.created_at DESC 
                        LIMIT 3";
                $result = executeQuery($sql, [$user_id], "i");
                
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($row['expense_date'])); ?></td>
                        <td><?php echo sanitizeOutput($row['category_name']); ?></td>
                        <td class="amount">$<?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="3" class="no-data">No expenses yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="preview-actions">
        <a href="view_expenses.php" class="btn btn-outline">View All Expenses</a>
        <a href="add_expense.php" class="btn btn-primary">Add Another Expense</a>
    </div>
</div>
<?php endif; ?>

<?php
// Include footer
require_once 'includes/footer.php';
?>
