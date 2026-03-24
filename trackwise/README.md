# TrackWise - Relational Expense Analytics Platform

A complete full-stack expense tracking and analytics platform built with PHP, MySQL, and modern web technologies. TrackWise helps users manage their expenses, set budgets, and gain insights into their spending patterns through beautiful dashboards and analytics.

## 🚀 Features

### Core Functionality
- **User Authentication**: Secure registration and login system with password hashing
- **Expense Management**: Add, view, filter, and delete expenses with detailed tracking
- **Category Management**: Create and organize expense categories for better classification
- **Budget Tracking**: Set monthly budgets and monitor spending against limits
- **Analytics Dashboard**: Interactive charts and visualizations using Chart.js
- **Data Export**: Export expense data to CSV for external analysis

### Technical Features
- **Secure Database Operations**: MySQLi prepared statements prevent SQL injection
- **Session Management**: Secure user sessions with timeout protection
- **Responsive Design**: Mobile-friendly interface that works on all devices
- **Modern UI**: Clean, professional design with smooth animations
- **Form Validation**: Client-side and server-side validation for data integrity
- **Search & Filtering**: Advanced filtering by date, category, and search terms

## 🛠 Tech Stack

### Backend
- **PHP 7.4+**: Core backend logic with procedural and modular structure
- **MySQL 5.7+**: Relational database with proper indexing and foreign keys
- **MySQLi**: Database connectivity with prepared statements

### Frontend
- **HTML5**: Semantic markup for accessibility
- **CSS3**: Modern styling with CSS variables and flexbox/grid
- **JavaScript ES6**: Interactive features and form validation
- **Chart.js**: Beautiful data visualization charts

### Server Environment
- **XAMPP**: Apache + MySQL development environment
- **No .htaccess Required**: Simple setup without complex routing

## 📋 Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server
- XAMPP (recommended for local development)

### Browser Requirements
- Modern browser with ES6 support
- JavaScript enabled
- Cookies enabled for session management

## 🗂 Project Structure

```
trackwise/
├── assets/
│   ├── css/
│   │   └── style.css          # Complete styling with responsive design
│   └── js/
│       └── script.js         # Interactive JavaScript functionality
├── includes/
│   ├── db.php                # Database connection and helper functions
│   ├── header.php            # Navigation and page header
│   ├── footer.php            # Page footer and scripts
│   └── auth.php              # Authentication and session protection
├── dashboard.php             # Main analytics dashboard
├── register.php              # User registration
├── login.php                 # User login
├── logout.php                # User logout
├── add_expense.php           # Add new expenses
├── view_expenses.php         # View and manage expenses
├── budget.php                # Budget management
├── categories.php            # Category management
├── index.php                 # Entry point with redirects
├── database.sql              # Complete database schema
└── README.md                 # This file
```

## 🚀 Installation Guide

### Step 1: Setup XAMPP
1. Download and install XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
2. Start Apache and MySQL services from XAMPP Control Panel
3. Verify both services are running (green indicators)

### Step 2: Create Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "New" to create a new database
3. Enter database name: `trackwise_db`
4. Click "Create"

### Step 3: Import Database Schema
1. Select the `trackwise_db` database
2. Click "Import" tab
3. Choose the `database.sql` file from the trackwise folder
4. Click "Go" to import the schema

### Step 4: Deploy Project Files
1. Copy the entire `trackwise` folder to:
   ```
   C:\xampp\htdocs\trackwise\
   ```
2. Verify the folder structure is correct

### Step 5: Configure Database Connection
The database connection is pre-configured for XAMPP:
- Host: `localhost`
- Username: `root`
- Password: (empty)
- Database: `trackwise_db`

No additional configuration needed!

### Step 6: Access the Application
Open your web browser and navigate to:
```
http://localhost/trackwise/
```

## 🎯 Quick Start

### 1. Create Your Account
1. Visit http://localhost/trackwise/
2. Click "Register here"
3. Fill in your details:
   - Full Name
   - Email Address
   - Password (minimum 6 characters)
4. Click "Register"

### 2. Login and Setup
1. After registration, you'll be redirected to login
2. Enter your credentials
3. You'll see the dashboard with default categories

### 3. Add Your First Expense
1. Click "Add Expense" in the sidebar
2. Select a category
3. Enter amount and description
4. Choose date
5. Click "Add Expense"

### 4. Set Monthly Budget
1. Go to "Budget" in the sidebar
2. Select month and year
3. Enter your budget amount
4. Click "Set Budget"

### 5. Explore Analytics
1. View your dashboard for expense insights
2. Check category-wise spending pie chart
3. Analyze monthly trends with bar chart
4. Monitor budget progress

## 📊 Database Schema

### Tables Overview

#### users
- `id` - Primary key, auto-increment
- `name` - User's full name (VARCHAR 100)
- `email` - Unique email address (VARCHAR 150)
- `password` - Hashed password (VARCHAR 255)
- `created_at` - Registration timestamp

#### categories
- `id` - Primary key, auto-increment
- `user_id` - Foreign key to users table
- `category_name` - Category name (VARCHAR 100)

#### expenses
- `id` - Primary key, auto-increment
- `user_id` - Foreign key to users table
- `category_id` - Foreign key to categories table
- `amount` - Expense amount (DECIMAL 10,2)
- `description` - Expense details (TEXT)
- `expense_date` - Date of expense (DATE)
- `created_at` - Record creation timestamp

#### budgets
- `id` - Primary key, auto-increment
- `user_id` - Foreign key to users table
- `month` - Budget month (INT)
- `year` - Budget year (INT)
- `amount` - Budget amount (DECIMAL 10,2)

## 🔧 Configuration

### Database Settings
Edit `includes/db.php` to change database settings:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'trackwise_db');
```

### Session Settings
Session security settings in `includes/db.php`:
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
```

### Session Timeout
Default session timeout is 30 minutes. Change in `includes/auth.php`:
```php
checkSessionTimeout(1800); // 1800 seconds = 30 minutes
```

## 🎨 Customization

### Adding New Categories
Default categories are created automatically during registration. Edit `register.php` to modify:
```php
$default_categories = [
    'Food & Dining',
    'Transportation',
    'Shopping',
    'Entertainment',
    'Bills & Utilities',
    'Healthcare',
    'Education',
    'Other'
];
```

### Custom Styling
Modify `assets/css/style.css` to customize:
- Colors (CSS variables at the top)
- Layout and spacing
- Typography
- Animations and transitions

### Adding New Features
The modular structure makes it easy to add:
- New pages in the root directory
- Additional database tables
- New API endpoints
- Enhanced analytics

## 🔒 Security Features

### Implemented Security Measures
- **SQL Injection Prevention**: All database queries use prepared statements
- **Password Security**: Bcrypt hashing for password storage
- **Session Protection**: Secure session management with regeneration
- **Input Validation**: Server-side validation for all user inputs
- **XSS Prevention**: Output escaping with htmlspecialchars()
- **CSRF Protection**: CSRF tokens for form submissions

### Best Practices
- Never trust user input
- Always validate and sanitize data
- Use parameterized queries
- Implement proper error handling
- Keep dependencies updated

## 🐛 Troubleshooting

### Common Issues

#### "Connection failed" Error
1. Verify MySQL is running in XAMPP
2. Check database name in `includes/db.php`
3. Ensure database was created correctly

#### "Blank White Page"
1. Check PHP error logs in XAMPP
2. Verify file permissions
3. Check for syntax errors in PHP files

#### "Session Not Working"
1. Ensure cookies are enabled in browser
2. Check session.save_path in php.ini
3. Verify server time is correct

#### Charts Not Displaying
1. Check internet connection (Chart.js loads from CDN)
2. Verify browser console for JavaScript errors
3. Ensure data is properly formatted

### Debug Mode
Add this to any PHP file for debugging:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## 📱 Mobile Support

TrackWise is fully responsive and works on:
- Desktop computers
- Tablets (iPad, Android tablets)
- Mobile phones (iPhone, Android)

### Mobile Features
- Touch-friendly interface
- Responsive navigation
- Optimized form inputs
- Mobile-optimized charts

## 🔄 Updates and Maintenance

### Regular Maintenance
- Backup database regularly
- Monitor error logs
- Update dependencies
- Review security practices

### Database Backup
```sql
-- Backup command
mysqldump -u root -p trackwise_db > backup.sql

-- Restore command
mysql -u root -p trackwise_db < backup.sql
```

## 📞 Support

### Getting Help
1. Check this README file first
2. Review error messages in browser console
3. Check XAMPP error logs
4. Test with sample data

### Common Questions
- **Q: Can I run this on a live server?**
  A: Yes, just update database credentials and enable HTTPS
  
- **Q: How do I add more users?**
  A: Use the registration page or add directly to database
  
- **Q: Can I customize the charts?**
  A: Yes, modify the Chart.js configuration in dashboard.php

## 📄 License

This project is provided as-is for educational and personal use. Feel free to modify and distribute according to your needs.

## 🙏 Acknowledgments

- **Chart.js** - For beautiful data visualization
- **XAMPP** - For easy local development environment
- **Bootstrap Inspiration** - For UI/UX design patterns
- **PHP Community** - For excellent documentation and examples

---

**TrackWise** - Your personal expense tracking solution! 🚀

Built with ❤️ using PHP, MySQL, and modern web technologies.
