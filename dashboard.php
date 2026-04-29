<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';

requireLogin();
$page_title = 'Dashboard';

$total_books_query = "SELECT COUNT(*) as count FROM books";
$total_books_result = getRow($conn, $total_books_query);
$total_books = $total_books_result['count'] ?? 0;

$total_librarians_query = "SELECT COUNT(*) as count FROM librarians";
$total_librarians_result = getRow($conn, $total_librarians_query);
$total_librarians = $total_librarians_result['count'] ?? 0;

$active_borrows_query = "SELECT COUNT(*) as count FROM borrow_records WHERE status = 'borrowed'";
$active_borrows_result = getRow($conn, $active_borrows_query);
$active_borrows = $active_borrows_result['count'] ?? 0;

$overdue_query = "SELECT COUNT(*) as count FROM borrow_records WHERE status = 'borrowed' AND due_date < CURDATE()";
$overdue_result = getRow($conn, $overdue_query);
$overdue_count = $overdue_result['count'] ?? 0;

$recent_query = "SELECT br.*, b.title as book_title
                 FROM borrow_records br
                 JOIN books b ON br.book_id = b.id
                 ORDER BY br.created_at DESC
                 LIMIT 5";
$recent_records = getRows($conn, $recent_query);

$low_stock_query = "SELECT * FROM books WHERE available_copies <= 2 ORDER BY available_copies ASC LIMIT 5";
$low_stock = getRows($conn, $low_stock_query);
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="page-header">
    <h1><span class="icon icon-dashboard"></span> Dashboard</h1>
</div>

<div class="content-section">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><span class="icon icon-books"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_books; ?></div>
                <div class="stat-label">Total Books</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><span class="icon icon-user"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_librarians; ?></div>
                <div class="stat-label">Librarians</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><span class="icon icon-history"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $active_borrows; ?></div>
                <div class="stat-label">Active Borrows</div>
            </div>
        </div>

        <div class="stat-card stat-card-warning">
            <div class="stat-icon"><span class="icon icon-alert"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $overdue_count; ?></div>
                <div class="stat-label">Overdue Books</div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
        <!-- Recent Borrow Records -->
        <div class="dashboard-card">
            <h2><span class="icon icon-history"></span> Recent Borrow Records</h2>

            <?php if (empty($recent_records)): ?>
                <p class="text-muted">No borrow records yet.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table data-table-compact">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Book</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['book_title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                                    <td>
                                        <span
                                            class="badge <?php echo $record['status'] === 'returned' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div style="margin-top: 15px;">
                <a href="/library/borrows/index.php" class="btn btn-sm btn-primary">View All Records</a>
            </div>
        </div>

        <!-- Low Stock Books -->
        <div class="dashboard-card">
            <h2><span class="icon icon-alert"></span> Low Stock Books</h2>

            <?php if (empty($low_stock)): ?>
                <p class="text-muted">All books have sufficient stock.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table data-table-compact">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Available</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td>
                                        <span class="badge badge-danger">
                                            <?php echo intval($book['available_copies']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo intval($book['total_copies']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div style="margin-top: 15px;">
                <a href="/library/books/index.php" class="btn btn-sm btn-primary">Manage Books</a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-card">
        <h2><span class="icon icon-flash"></span> Quick Actions</h2>
        <div class="quick-actions">
            <a href="/library/books/add.php" class="action-btn">
                <span class="action-icon"><span class="icon icon-books"></span></span>
                <span class="action-text">Add New Book</span>
            </a>
            <a href="/library/borrows/add.php" class="action-btn">
                <span class="action-icon"><span class="icon icon-history"></span></span>
                <span class="action-text">New Borrow Record</span>
            </a>
            <a href="/library/reports/index.php" class="action-btn">
                <span class="action-icon"><span class="icon icon-chart"></span></span>
                <span class="action-text">View Reports</span>
            </a>
        </div>
    </div>
</div>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--color-white);
        color: var(--color-black);
        padding: 20px;
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--color-grey-200);
        transition: var(--transition);
    }

    .stat-card:hover {
        box-shadow: var(--shadow-md);
        border-color: var(--color-grey-300);
    }

    .stat-card-warning {
        border-bottom: 3px solid var(--color-warning);
    }

    .stat-icon {
        font-size: 32px;
        color: var(--color-black);
    }

    .stat-value {
        font-size: 24px;
        font-weight: 800;
    }

    .stat-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--color-grey-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .dashboard-card {
        background: var(--color-white);
        padding: 24px;
        border-radius: var(--border-radius);
        border: none;
        box-shadow: var(--shadow-lg);
    }

    .dashboard-card h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: var(--color-black);
        font-size: 16px;
        font-weight: 700;
    }

    .data-table-compact {
        font-size: 13px;
    }

    .data-table-compact th,
    .data-table-compact td {
        padding: 10px 8px;
    }

    .text-muted {
        color: var(--color-grey-500);
        font-size: 14px;
    }

    .btn-sm {
        padding: 8px 16px;
        font-size: 13px;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: var(--color-black);
        color: var(--color-white);
        text-decoration: none;
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: var(--color-white);
        text-decoration: none;
    }

    .action-icon {
        font-size: 32px;
        margin-bottom: 10px;
        color: var(--color-white);
    }

    .action-text {
        font-size: 14px;
        text-align: center;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>