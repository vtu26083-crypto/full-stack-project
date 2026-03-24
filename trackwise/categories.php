<?php
/**
 * TrackWise Categories Management Page
 * Allows users to create and manage expense categories
 */

// Include authentication and database
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Set page variables
$page_title = "Categories - TrackWise";
$page_heading = "Categories";
$page_description = "Manage your expense categories for better organization";

// Get current user ID
$user_id = getCurrentUserId();

// Initialize variables
$category_name = "";
$category_err = $success_message = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    
    // Validate category name
    if (empty(trim($_POST["category_name"]))) {
        $category_err = "Please enter a category name.";
    } elseif (strlen(trim($_POST["category_name"])) < 2) {
        $category_err = "Category name must be at least 2 characters.";
    } elseif (strlen(trim($_POST["category_name"])) > 100) {
        $category_err = "Category name must be less than 100 characters.";
    } else {
        $category_name = trim($_POST["category_name"]);
        
        // Check if category already exists for this user
        $sql = "SELECT id FROM categories WHERE user_id = ? AND category_name = ?";
        $result = executeQuery($sql, [$user_id, $category_name], "is");
        
        if ($result->num_rows > 0) {
            $category_err = "This category already exists.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($category_err)) {
        
        // Insert category into database
        $sql = "INSERT INTO categories (user_id, category_name) VALUES (?, ?)";
        $result = executeQuery($sql, [$user_id, $category_name], "is");
        
        if ($result) {
            $success_message = "Category added successfully!";
            $category_name = "";
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
}

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    if (canAccessCategory($category_id)) {
        // Check if category has expenses
        $sql = "SELECT COUNT(*) as expense_count FROM expenses WHERE category_id = ?";
        $result = executeQuery($sql, [$category_id], "i");
        $row = $result->fetch_assoc();
        
        if ($row['expense_count'] > 0) {
            $error_message = "Cannot delete category. It has " . $row['expense_count'] . " expense(s) associated with it.";
        } else {
            $delete_sql = "DELETE FROM categories WHERE id = ? AND user_id = ?";
            $delete_result = executeQuery($delete_sql, [$category_id, $user_id], "ii");
            
            if ($delete_result) {
                $success_message = "Category deleted successfully!";
                // Redirect to prevent resubmission
                redirect('categories.php?success=' . urlencode($success_message));
            }
        }
    }
}

// Display success message from redirect
if (isset($_GET['success'])) {
    $success_message = sanitizeOutput($_GET['success']);
}

// Get all categories for the user
$categories = [];
$sql = "SELECT c.id, c.category_name, COUNT(e.id) as expense_count, COALESCE(SUM(e.amount), 0) as total_spent
        FROM categories c 
        LEFT JOIN expenses e ON c.id = e.category_id 
        WHERE c.user_id = ? 
        GROUP BY c.id, c.category_name 
        ORDER BY c.category_name";
$result = executeQuery($sql, [$user_id], "i");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get category statistics
$total_categories = count($categories);
$categories_with_expenses = 0;
$total_spent_all = 0;

foreach ($categories as $category) {
    if ($category['expense_count'] > 0) {
        $categories_with_expenses++;
        $total_spent_all += $category['total_spent'];
    }
}
?>

<!-- Add Category Form -->
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Add New Category</h2>
            <p>Create a new category to organize your expenses</p>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <?php showSuccess($success_message); ?>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <?php showError($error_message); ?>
        <?php endif; ?>
        
        <form method="POST" class="category-form">
            <div class="form-group">
                <label for="category_name">Category Name *</label>
                <input type="text" name="category_name" id="category_name" class="form-control" 
                       value="<?php echo sanitizeOutput($category_name); ?>" 
                       placeholder="e.g., Food & Dining, Transportation, Shopping" 
                       maxlength="100" required>
                <span class="error-message"><?php echo sanitizeOutput($category_err); ?></span>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="add_category" class="btn btn-primary">
                    <span>🏷️</span> Add Category
                </button>
                <button type="reset" class="btn btn-outline">Clear</button>
            </div>
        </form>
    </div>
</div>

<!-- Categories Overview -->
<div class="overview-container">
    <div class="overview-header">
        <h3>Categories Overview</h3>
        <div class="overview-stats">
            <div class="stat-item">
                <h4>Total Categories</h4>
                <p class="stat-value"><?php echo $total_categories; ?></p>
            </div>
            <div class="stat-item">
                <h4>Used Categories</h4>
                <p class="stat-value"><?php echo $categories_with_expenses; ?></p>
            </div>
            <div class="stat-item">
                <h4>Total Spent</h4>
                <p class="stat-value">$<?php echo number_format($total_spent_all, 2); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Categories List -->
<div class="categories-container">
    <div class="categories-header">
        <h3>Your Categories</h3>
        <div class="categories-info">
            <?php echo $total_categories; ?> categories total
        </div>
    </div>
    
    <?php if (empty($categories)): ?>
        <div class="empty-state">
            <h3>No Categories Found</h3>
            <p>You haven't created any categories yet. Start by adding your first category!</p>
            <button class="btn btn-primary" onclick="document.getElementById('category_name').focus()">
                <span>🏷️</span> Add Your First Category
            </button>
        </div>
    <?php else: ?>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-header">
                        <h4><?php echo sanitizeOutput($category['category_name']); ?></h4>
                        <div class="category-actions">
                            <?php if ($category['expense_count'] == 0): ?>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo sanitizeOutput($category['category_name']); ?>')">
                                    🗑️
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="category-stats">
                        <div class="stat-row">
                            <span class="stat-label">Expenses:</span>
                            <span class="stat-value"><?php echo $category['expense_count']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Total Spent:</span>
                            <span class="stat-value">$<?php echo number_format($category['total_spent'], 2); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($category['expense_count'] > 0): ?>
                        <div class="category-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $total_spent_all > 0 ? ($category['total_spent'] / $total_spent_all) * 100 : 0; ?>%;"></div>
                            </div>
                            <span class="progress-percentage">
                                <?php echo $total_spent_all > 0 ? round(($category['total_spent'] / $total_spent_all) * 100, 1) : 0; ?>% of total
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="category-empty">
                            <span>No expenses yet</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Categories Table View -->
        <div class="table-container">
            <h4>Detailed View</h4>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Number of Expenses</th>
                        <th>Total Spent</th>
                        <th>Average Expense</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <span class="category-badge">
                                    <?php echo sanitizeOutput($category['category_name']); ?>
                                </span>
                            </td>
                            <td><?php echo $category['expense_count']; ?></td>
                            <td class="amount">$<?php echo number_format($category['total_spent'], 2); ?></td>
                            <td class="amount">
                                $<?php echo number_format($category['expense_count'] > 0 ? $category['total_spent'] / $category['expense_count'] : 0, 2); ?>
                            </td>
                            <td class="actions">
                                <?php if ($category['expense_count'] == 0): ?>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo sanitizeOutput($category['category_name']); ?>')">
                                        🗑️ Delete
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">In use</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Category Tips -->
<div class="tips-container">
    <h3>Category Management Tips</h3>
    <div class="tips-grid">
        <div class="tip-card">
            <div class="tip-icon">🎯</div>
            <h4>Be Specific</h4>
            <p>Create specific categories rather than generic ones for better tracking.</p>
        </div>
        <div class="tip-card">
            <div class="tip-icon">📊</div>
            <h4>Review Regularly</h4>
            <p>Review your categories monthly and adjust as your spending habits change.</p>
        </div>
        <div class="tip-card">
            <div class="tip-icon">🏷️</div>
            <h4>Keep it Simple</h4>
            <p>Don't create too many categories. Aim for 8-12 main categories.</p>
        </div>
        <div class="tip-card">
            <div class="tip-icon">🔄</div>
            <h4>Merge Similar</h4>
            <p>Combine similar categories to simplify your expense tracking.</p>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<script>
// Confirm delete function
function confirmDelete(categoryId, categoryName) {
    if (confirm(`Are you sure you want to delete the category "${categoryName}"? This action cannot be undone.`)) {
        window.location.href = '?delete=' + categoryId;
    }
}

// Auto-focus on category name input
document.addEventListener('DOMContentLoaded', function() {
    const categoryInput = document.getElementById('category_name');
    if (categoryInput && !categoryInput.value) {
        categoryInput.focus();
    }
});

// Form validation
document.querySelector('.category-form').addEventListener('submit', function(e) {
    const categoryName = document.getElementById('category_name').value.trim();
    
    if (categoryName.length < 2) {
        e.preventDefault();
        alert('Category name must be at least 2 characters long.');
        return false;
    }
    
    if (categoryName.length > 100) {
        e.preventDefault();
        alert('Category name must be less than 100 characters.');
        return false;
    }
});
</script>
