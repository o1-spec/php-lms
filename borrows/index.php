<?php
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/pagination.php';

requireLogin();
$page_title   = 'Borrow Records';
$librarian_id = $_SESSION['librarian_id'];

$records_per_page = 15;
$current_page     = max(1, intval($_GET['page'] ?? 1));

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    if ($_POST['action'] === 'add_borrow') {
        $book_id = intval($_POST['book_id'] ?? 0);
        $student_name = trim($_POST['student_name'] ?? '');
        $matric_number = trim($_POST['matric_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $borrow_date = trim($_POST['borrow_date'] ?? '');
        $due_date = trim($_POST['due_date'] ?? '');
        
        if ($book_id <= 0 || empty($student_name) || empty($matric_number) || empty($borrow_date) || empty($due_date)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
            exit();
        }
        
        $borrow_timestamp = strtotime($borrow_date);
        $due_timestamp = strtotime($due_date);
        
        if ($borrow_timestamp === false || $due_timestamp === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid date format']);
            exit();
        } elseif ($due_timestamp <= $borrow_timestamp) {
            echo json_encode(['success' => false, 'message' => 'Due date must be after borrow date']);
            exit();
        }
        
        $book_query = "SELECT id, available_copies FROM books WHERE id = ?";
        $book = getRow($conn, $book_query, [$book_id], 'i');
        
        if (!$book || $book['available_copies'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Selected book is not available']);
            exit();
        }
        
        $conn->begin_transaction();
        try {
            $insert_query = "INSERT INTO borrow_records (book_id, librarian_id, student_name, matric_number, department, borrow_date, due_date, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, 'borrowed')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param('iisssss', $book_id, $librarian_id, $student_name, $matric_number, $department, $borrow_date, $due_date);
            $stmt->execute();
            
            $new_available = $book['available_copies'] - 1;
            $update_query = "UPDATE books SET available_copies = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('ii', $new_available, $book_id);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Borrow record created successfully!']);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error creating record: ' . $e->getMessage()]);
            exit();
        }
    } elseif ($_POST['action'] === 'return_book') {
        $record_id = intval($_POST['record_id'] ?? 0);
        $return_date = trim($_POST['return_date'] ?? '');
        $fine_amount = floatval($_POST['fine_amount'] ?? 0);
        
        $query = "SELECT br.*, b.available_copies FROM borrow_records br JOIN books b ON br.book_id = b.id WHERE br.id = ?";
        $record = getRow($conn, $query, [$record_id], 'i');
        
        if (!$record || $record['status'] !== 'borrowed') {
            echo json_encode(['success' => false, 'message' => 'Invalid borrow record or already returned.']);
            exit();
        } elseif (empty($return_date)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a return date.']);
            exit();
        }
        
        $conn->begin_transaction();
        try {
            $update_query = "UPDATE borrow_records SET return_date = ?, status = 'returned', fine_amount = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('sdi', $return_date, $fine_amount, $record_id);
            $stmt->execute();
            
            $book_id = $record['book_id'];
            $new_available = $record['available_copies'] + 1;
            $book_update_query = "UPDATE books SET available_copies = ? WHERE id = ?";
            $stmt = $conn->prepare($book_update_query);
            $stmt->bind_param('ii', $new_available, $book_id);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Book returned successfully!']);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error processing return: ' . $e->getMessage()]);
            exit();
        }
    }
}

$books_query = "SELECT * FROM books WHERE available_copies > 0 ORDER BY title";
$books = getRows($conn, $books_query);

$count_result   = getRow($conn, "SELECT COUNT(*) as total FROM borrow_records");
$total_records  = intval($count_result['total']);
$offset         = ($current_page - 1) * $records_per_page;

$query = "SELECT br.*, b.title as book_title, l.name as librarian_name
          FROM borrow_records br
          JOIN books b ON br.book_id = b.id
          JOIN librarians l ON br.librarian_id = l.id
          ORDER BY br.created_at DESC
          LIMIT ? OFFSET ?";
$records = getRows($conn, $query, [$records_per_page, $offset], 'ii');
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

    <div class="page-header">
        <h1><span class="icon icon-history"></span> Borrow Records</h1>
        <a href="/library/borrows/add.php" class="btn btn-primary">+ New Borrow Record</a>
    </div>

    <div class="content-section">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?> Reloading...
            </div>
            <script>
                setTimeout(() => {
                    location.reload();
                }, 1500);
            </script>
        <?php endif; ?>

        <?php if (empty($records)): ?>
            <div class="alert alert-info">
                No borrow records found. <a href="/library/borrows/add.php" class="btn-action btn-edit">Create the first record</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Matric #</th>
                            <th>Book</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Fine</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['matric_number']); ?></td>
                                <td><?php echo htmlspecialchars($record['book_title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($record['borrow_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $record['status'] === 'returned' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td>₦<?php echo number_format(floatval($record['fine_amount']), 2); ?></td>
                                <td>
                                    <?php if ($record['status'] === 'borrowed'): ?>
                                        <a href="/library/borrows/return.php?id=<?php echo intval($record['id']); ?>" class="btn-action btn-success">Return</a>
                                    <?php else: ?>
                                        <span class="text-muted">Returned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php echo build_pagination($total_records, $records_per_page, $current_page, '/library/borrows/index.php?page=%d'); ?>
    </div>

<?php require_once '../includes/footer.php'; ?>