-- TrackWise Database Schema
-- Database: trackwise_db

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS trackwise_db;
USE trackwise_db;

-- Drop tables if they exist (for fresh installation)
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create expenses table
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Create budgets table
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month_year (user_id, month, year)
);

-- Insert default categories for new users (these will be copied for each user during registration)
-- Note: These are template categories that will be inserted per user during registration

-- Create indexes for better performance
CREATE INDEX idx_expenses_user_date ON expenses(user_id, expense_date);
CREATE INDEX idx_expenses_category ON expenses(category_id);
CREATE INDEX idx_budgets_user_month ON budgets(user_id, month, year);
CREATE INDEX idx_categories_user ON categories(user_id);

-- Sample data for testing (optional)
-- You can uncomment these lines to add sample data after creating a user

-- INSERT INTO users (name, email, password) VALUES 
-- ('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- INSERT INTO categories (user_id, category_name) VALUES 
-- (1, 'Food & Dining'),
-- (1, 'Groceries'),
-- (1, 'Restaurants'),
-- (1, 'Coffee & Snacks'),
-- (1, 'Transportation'),
-- (1, 'Gas & Fuel'),
-- (1, 'Public Transit'),
-- (1, 'Shopping'),
-- (1, 'Clothing'),
-- (1, 'Electronics'),
-- (1, 'Home & Garden'),
-- (1, 'Entertainment'),
-- (1, 'Movies & Theater'),
-- (1, 'Games & Hobbies'),
-- (1, 'Bills & Utilities'),
-- (1, 'Rent/Mortgage'),
-- (1, 'Electricity'),
-- (1, 'Water'),
-- (1, 'Internet'),
-- (1, 'Phone'),
-- (1, 'Healthcare'),
-- (1, 'Doctor Visits'),
-- (1, 'Pharmacy'),
-- (1, 'Insurance'),
-- (1, 'Education'),
-- (1, 'Books & Supplies'),
-- (1, 'Courses'),
-- (1, 'Personal Care'),
-- (1, 'Fitness'),
-- (1, 'Travel'),
-- (1, 'Business Expenses'),
-- (1, 'Gifts & Donations'),
-- (1, 'Savings & Investments'),
-- (1, 'Other');

-- INSERT INTO expenses (user_id, category_id, amount, description, expense_date) VALUES 
-- (1, 1, 25.50, 'Lunch at restaurant', '2024-01-15'),
-- (1, 2, 15.00, 'Gas for car', '2024-01-16'),
-- (1, 3, 120.00, 'New shoes', '2024-01-17'),
-- (1, 4, 45.00, 'Movie tickets', '2024-01-18');

-- INSERT INTO budgets (user_id, month, year, amount) VALUES 
-- (1, 1, 2024, 1000.00),
-- (1, 2, 2024, 1000.00);
