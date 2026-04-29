# Library Management System

A simple, beginner-friendly Library Management System built with plain PHP, MySQL, HTML, CSS, and minimal JavaScript. Perfect for schools and small educational institutions.

## Features

### 1. **Librarian Authentication**
- Register new librarians
- Secure login with password hashing (password_hash/password_verify)
- Session-based authentication (no JWT)
- Logout functionality
- Redirect non-authenticated users to login page

### 2. **Dashboard**
- Quick overview of library statistics
- Total books count
- Total librarians count
- Active borrow records
- Overdue books count
- Recent borrow activity
- Low stock alerts

### 3. **Book Management**
- Add new books with details (title, author, ISBN, category, copies)
- View all books with availability status
- Edit book information
- Delete books
- Track available and total copies

### 4. **Borrow Records**
- Create borrow records without student accounts
- Student details (name, matric number, department) entered directly
- Set borrow and due dates
- Automatic adjustment of available book copies
- Return books and calculate fines for overdue items
- Track borrow status (borrowed/returned)

### 5. **Reports**
- **Overdue Books**: View all overdue books with fine amounts
- **Most Borrowed Books**: See which books are most popular
- **Active Borrowers**: Track current borrowers and their books
- **Fine Management**: Automatic fine calculation (₦100 per day overdue)
- Summary statistics for quick insights

### 6. **User Interface**
- Clean, responsive dashboard layout
- Sidebar navigation
- Data tables for listing records
- Forms for adding/editing data
- Success and error messages
- Mobile-friendly design
- Emoji-enhanced navigation

## System Requirements

- XAMPP (or any Apache + MySQL + PHP setup)
- PHP 7.0+
- MySQL 5.7+
- Modern web browser

## Installation & Setup

### Step 1: Database Setup

1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. Create a new database named `library_db`
3. Go to the **SQL** tab in phpMyAdmin
4. Copy the contents of `database.sql` from the project folder
5. Paste it into the SQL query box and execute

**OR** import the file directly:
- In phpMyAdmin, select the `library_db` database
- Click **Import** tab
- Choose the `database.sql` file from the project
- Click **Import**

### Step 2: Verify Project Location

The project should be located at:
```
/Applications/XAMPP/xamppfiles/htdocs/library
```

This is the default XAMPP location for macOS. If your XAMPP is installed elsewhere, adjust accordingly.

### Step 3: Start XAMPP

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services

### Step 4: Access the Application

Open your browser and go to:
```
http://localhost/library
```

### Step 5: Default Login Credentials

After database setup, you can log in with:
- **Email**: `admin@library.local`
- **Password**: `password123`

**Important**: Change this password after first login for security.

## File Structure

```
library/
├── config/
│   └── database.php              # Database connection and helper functions
├── includes/
│   ├── auth.php                  # Authentication helper functions
│   ├── header.php                # Header template (included in all pages)
│   ├── sidebar.php               # Sidebar navigation (included in all pages)
│   └── footer.php                # Footer template (included in all pages)
├── auth/
│   ├── login.php                 # Librarian login page
│   ├── register.php              # Register new librarian
│   └── logout.php                # Logout and destroy session
├── books/
│   ├── index.php                 # View all books
│   ├── add.php                   # Add new book
│   ├── edit.php                  # Edit book details
│   └── delete.php                # Delete book (handled via AJAX)
├── borrows/
│   ├── index.php                 # View all borrow records
│   ├── add.php                   # Create new borrow record
│   └── return.php                # Return a book and calculate fine
├── reports/
│   └── index.php                 # Comprehensive reporting dashboard
├── assets/
│   ├── css/
│   │   └── style.css             # Main stylesheet (responsive design)
│   └── js/
│       └── script.js             # Minimal JavaScript (form validation, etc.)
├── dashboard.php                 # Main dashboard
├── index.php                     # Entry point (redirects to login or dashboard)
├── database.sql                  # Database schema and sample data
└── README.md                     # This file
```

## Database Schema

### librarians table
- `id` - Primary key
- `name` - Librarian name
- `email` - Unique email address
- `password` - Hashed password
- `phone` - Contact number
- `created_at` - Registration date

### books table
- `id` - Primary key
- `title` - Book title
- `author` - Author name
- `isbn` - ISBN (unique)
- `category` - Book category
- `total_copies` - Total copies in library
- `available_copies` - Copies available for borrowing
- `created_at` - When book was added

### borrow_records table
- `id` - Primary key
- `book_id` - Foreign key to books
- `librarian_id` - Foreign key to librarians (who processed the borrow)
- `student_name` - Student's full name
- `matric_number` - Student's matric/ID number
- `department` - Student's department
- `borrow_date` - Date book was borrowed
- `due_date` - Expected return date
- `return_date` - Actual return date (NULL if not returned)
- `status` - 'borrowed' or 'returned'
- `fine_amount` - Calculated fine for overdue books
- `created_at` - Record creation date

## Usage Guide

### Adding a Librarian
1. Click **Register** on the login page
2. Fill in the form with name, email, phone (optional), and password
3. Click **Register** to create the account
4. Use the new credentials to log in

### Adding a Book
1. Log in to the system
2. Click **📖 Books** in the sidebar
3. Click **+ Add New Book**
4. Fill in book details (Title, Author are required)
5. Enter total copies to add
6. Click **Add Book**

### Creating a Borrow Record
1. Click **📋 Borrow Records** in the sidebar
2. Click **+ New Borrow Record**
3. Enter student details (name, matric number, department)
4. Select a book from the dropdown
5. Set borrow date and due date
6. Click **Create Borrow Record**
7. The available copies for that book will automatically decrease

### Returning a Book
1. Go to **📋 Borrow Records**
2. Find the record with status "borrowed"
3. Click **Return** button
4. The system automatically calculates fine if book is overdue (₦100 per day)
5. Adjust fine amount if needed
6. Click **Process Return**
7. The available copies will automatically increase

### Viewing Reports
1. Click **📈 Reports** in the sidebar
2. View:
   - Overdue books with fine amounts
   - Most borrowed books
   - Active borrowers
   - Summary statistics

## Security Features

- **Password Hashing**: Passwords are hashed using PHP's `password_hash()` function
- **Prepared Statements**: All database queries use prepared statements to prevent SQL injection
- **Session Management**: Uses PHP sessions to maintain authenticated state
- **Server-Side Validation**: All user inputs are validated on the server
- **Redirect to Login**: Non-authenticated users are automatically redirected to login page

## Code Highlights

### Database Helper Functions (config/database.php)
```php
// Execute prepared statement
executeQuery($conn, $query, $params, $types);

// Get single row
getRow($conn, $query, $params, $types);

// Get all rows
getRows($conn, $query, $params, $types);
```

### Session Management (includes/auth.php)
```php
// Check if user is logged in
isLoggedIn();

// Require login (redirects if not authenticated)
requireLogin();

// Get current librarian data
getCurrentLibrarian($conn);
```

### Password Security
```php
// Hashing password on registration
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Verifying password on login
password_verify($password, $stored_hash);
```

## Customization

### Change Fine Rate
To modify the daily fine amount:
1. Open `borrows/return.php`
2. Find: `$daily_fine = 100;`
3. Change `100` to your desired amount

### Change Colors
To customize the color scheme:
1. Open `assets/css/style.css`
2. Modify the CSS variables at the top:
```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --danger-color: #d9534f;
    /* ... etc ... */
}
```

### Add Book Categories
Currently, categories are free-text fields. To create a dropdown:
1. Create a `categories` table in the database
2. Update the form to use a SELECT dropdown instead of text input

## Troubleshooting

### Database Connection Error
- Check that MySQL is running
- Verify database credentials in `config/database.php`
- Ensure `library_db` database exists

### Login Not Working
- Check that database was imported correctly
- Verify password using phpMyAdmin (should be hashed)
- Clear browser cookies and try again

### Pages Not Loading
- Verify XAMPP is running
- Check that files are in `/Applications/XAMPP/xamppfiles/htdocs/library`
- Check browser console for JavaScript errors

### CSS/JS Not Loading
- Verify the URL is `http://localhost/library`
- Check browser cache (Ctrl+Shift+Delete or Cmd+Shift+Delete)
- Verify CSS/JS file paths in HTML (should start with `/library/`)

## Tips for Beginners

1. **Understanding the Code**: Read through `config/database.php` to understand how database operations work
2. **Session Management**: Check `includes/auth.php` to see how authentication is handled
3. **Form Processing**: Look at `books/add.php` to see a complete form submission example
4. **Database Updates**: See `borrows/add.php` to understand transactions for multiple queries
5. **HTML Structure**: Check `includes/header.php` and `includes/sidebar.php` to see template includes

## Future Enhancements

Possible features to add:
- Book search and filtering
- Librarian management (view/delete librarians)
- Book reservations
- Email notifications for overdue books
- Fine payment tracking
- Book renewal functionality
- Export reports to PDF/Excel
- Bulk import of books via CSV
- Student accounts (optional)

## License

This project is free to use for educational purposes.

## Support

This is a beginner-friendly project suitable for SIWES and school projects. All code includes comments for learning purposes.

For questions, refer to the code comments or review the included files.

---

**Created**: April 2026  
**Last Updated**: April 2026  
**Version**: 1.0
