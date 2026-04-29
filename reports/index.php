<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/pagination.php';

requireLogin();
$page_title = 'Reports';

$records_per_page    = 10;
$overdue_page        = max(1, intval($_GET['overdue_page'] ?? 1));
$borrowers_page      = max(1, intval($_GET['borrowers_page'] ?? 1));

$daily_fine = 100;

$overdue_count_res  = getRow($conn, "SELECT COUNT(*) as total FROM borrow_records WHERE status = 'borrowed' AND due_date < CURDATE()");
$total_overdue      = intval($overdue_count_res['total']);
$overdue_offset     = ($overdue_page - 1) * $records_per_page;

$overdue_query = "SELECT br.*, b.title as book_title,
                  DATEDIFF(CURDATE(), br.due_date) as days_overdue,
                  (DATEDIFF(CURDATE(), br.due_date) * ?) as fine_amount
                  FROM borrow_records br
                  JOIN books b ON br.book_id = b.id
                  WHERE br.status = 'borrowed' AND br.due_date < CURDATE()
                  ORDER BY br.due_date ASC
                  LIMIT ? OFFSET ?";
$overdue_books = getRows($conn, $overdue_query, [$daily_fine, $records_per_page, $overdue_offset], 'iii');

$borrowed_query = "SELECT b.id, b.title, b.author, COUNT(br.id) as borrow_count
                   FROM books b
                   LEFT JOIN borrow_records br ON b.id = br.book_id
                   GROUP BY b.id
                   ORDER BY borrow_count DESC
                   LIMIT 10";
$most_borrowed = getRows($conn, $borrowed_query);

$borrowers_count_res = getRow($conn, "SELECT COUNT(DISTINCT matric_number) as total FROM borrow_records WHERE status = 'borrowed'");
$total_borrowers     = intval($borrowers_count_res['total']);
$borrowers_offset    = ($borrowers_page - 1) * $records_per_page;

$active_borrowers_query = "SELECT DISTINCT br.student_name, br.matric_number, br.department, 
                           COUNT(br.id) as active_borrows,
                           GROUP_CONCAT(b.title SEPARATOR ', ') as books_borrowed
                           FROM borrow_records br
                           JOIN books b ON br.book_id = b.id
                           WHERE br.status = 'borrowed'
                           GROUP BY br.matric_number
                           ORDER BY active_borrows DESC
                           LIMIT ? OFFSET ?";
$active_borrowers = getRows($conn, $active_borrowers_query, [$records_per_page, $borrowers_offset], 'ii');

$total_fines_query = "SELECT SUM(CASE 
                      WHEN status = 'borrowed' AND due_date < CURDATE() 
                      THEN (DATEDIFF(CURDATE(), due_date) * ?)
                      ELSE fine_amount
                      END) as total_fines
                      FROM borrow_records";
$fines_result = getRow($conn, $total_fines_query, [$daily_fine], 'i');
$total_fines = $fines_result['total_fines'] ?? 0;

$stats_query = "SELECT 
                (SELECT COUNT(*) FROM borrow_records WHERE status = 'borrowed') as active_borrows,
                (SELECT COUNT(DISTINCT matric_number) FROM borrow_records WHERE status = 'borrowed') as active_borrowers_count,
                (SELECT COUNT(*) FROM borrow_records) as total_borrows,
                (SELECT COUNT(*) FROM books) as total_books";
$stats = getRow($conn, $stats_query);
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

    <div class="page-header">
        <h1><span class="icon icon-chart"></span> Reports</h1>
    </div>

    <div class="content-section">
        <!-- Summary Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo intval($stats['active_borrows']); ?></div>
                <div class="stat-label">Active Borrows</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo intval($stats['active_borrowers_count']); ?></div>
                <div class="stat-label">Active Borrowers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₦<?php echo number_format($total_fines, 2); ?></div>
                <div class="stat-label">Total Outstanding Fines</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($overdue_books); ?></div>
                <div class="stat-label">Overdue Books</div>
            </div>
        </div>

        <!-- Overdue Books Section -->
        <div class="report-section">
            <h2><span class="icon icon-alert"></span> Overdue Books (<?php echo count($overdue_books); ?>)</h2>
            
            <?php if (empty($overdue_books)): ?>
                <div class="alert alert-success">
                    No overdue books. Great!
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Matric #</th>
                                <th>Book Title</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Fine (₦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdue_books as $book): ?>
                                <tr style="background-color: #fff3cd;">
                                    <td><?php echo htmlspecialchars($book['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($book['matric_number']); ?></td>
                                    <td><?php echo htmlspecialchars($book['book_title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                                    <td><strong><?php echo intval($book['days_overdue']); ?> days</strong></td>
                                    <td>₦<?php echo number_format(floatval($book['fine_amount']), 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php echo build_pagination($total_overdue, $records_per_page, $overdue_page, '/library/reports/index.php?overdue_page=%d&borrowers_page=' . $borrowers_page); ?>
        </div>

        <!-- Most Borrowed Books Section -->
        <div class="report-section">
            <h2><span class="icon icon-books"></span> Top 10 Most Borrowed Books</h2>
            
            <?php if (empty($most_borrowed)): ?>
                <div class="alert alert-info">
                    No borrow history yet.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Times Borrowed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($most_borrowed as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><span class="badge badge-info"><?php echo intval($book['borrow_count']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Active Borrowers Section -->
        <div class="report-section">
            <h2><span class="icon icon-user"></span> Active Borrowers</h2>
            
            <?php if (empty($active_borrowers)): ?>
                <div class="alert alert-info">
                    No active borrowers.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Matric #</th>
                                <th>Department</th>
                                <th>Active Borrows</th>
                                <th>Books Borrowed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_borrowers as $borrower): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($borrower['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($borrower['matric_number']); ?></td>
                                    <td><?php echo htmlspecialchars($borrower['department'] ?? 'N/A'); ?></td>
                                    <td><span class="badge badge-primary"><?php echo intval($borrower['active_borrows']); ?></span></td>
                                    <td><?php echo htmlspecialchars($borrower['books_borrowed']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php echo build_pagination($total_borrowers, $records_per_page, $borrowers_page, '/library/reports/index.php?borrowers_page=%d&overdue_page=' . $overdue_page); ?>
        </div>
    </div>

    <style>
        .report-section {
            background: var(--color-white);
            padding: 24px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            border: none;
            box-shadow: var(--shadow-lg);
        }

        .report-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--color-black);
            font-size: 18px;
            font-weight: 700;
        }

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
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--color-grey-200);
            transition: var(--transition);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-grey-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-info {
            background-color: var(--color-info);
            color: white;
        }

        .badge-primary {
            background-color: var(--color-black);
            color: white;
        }
    </style>

<?php require_once '../includes/footer.php'; ?>
