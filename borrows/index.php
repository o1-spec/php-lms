<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';

requireLogin();
$page_title = 'Borrow Records';
$librarian_id = $_SESSION['librarian_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_borrow') {
        $book_id = intval($_POST['book_id'] ?? 0);
        $student_name = trim($_POST['student_name'] ?? '');
        $matric_number = trim($_POST['matric_number'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $borrow_date = trim($_POST['borrow_date'] ?? '');
        $due_date = trim($_POST['due_date'] ?? '');
        
        if ($book_id <= 0 || empty($student_name) || empty($matric_number) || empty($borrow_date) || empty($due_date)) {
            $error = 'Please fill in all required fields';
        } else {
            $borrow_timestamp = strtotime($borrow_date);
            $due_timestamp = strtotime($due_date);
            
            if ($borrow_timestamp === false || $due_timestamp === false) {
                $error = 'Invalid date format';
            } elseif ($due_timestamp <= $borrow_timestamp) {
                $error = 'Due date must be after borrow date';
            } else {
                $book_query = "SELECT id, available_copies FROM books WHERE id = ?";
                $book = getRow($conn, $book_query, [$book_id], 'i');
                
                if (!$book || $book['available_copies'] <= 0) {
                    $error = 'Selected book is not available';
                } else {
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
                        $success = 'Borrow record created successfully!';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = 'Error creating record: ' . $e->getMessage();
                    }
                }
            }
        }
    } elseif ($_POST['action'] === 'return_book') {
        $record_id = intval($_POST['record_id'] ?? 0);
        $return_date = trim($_POST['return_date'] ?? '');
        $fine_amount = floatval($_POST['fine_amount'] ?? 0);
        
        $query = "SELECT br.*, b.available_copies FROM borrow_records br JOIN books b ON br.book_id = b.id WHERE br.id = ?";
        $record = getRow($conn, $query, [$record_id], 'i');
        
        if (!$record || $record['status'] !== 'borrowed') {
            $error = 'Invalid borrow record or already returned.';
        } elseif (empty($return_date)) {
            $error = 'Please enter a return date.';
        } else {
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
                $success = 'Book returned successfully!';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error processing return: ' . $e->getMessage();
            }
        }
    }
}

$books_query = "SELECT * FROM books WHERE available_copies > 0 ORDER BY title";
$books = getRows($conn, $books_query);

$query = "SELECT br.*, b.title as book_title, l.name as librarian_name
          FROM borrow_records br
          JOIN books b ON br.book_id = b.id
          JOIN librarians l ON br.librarian_id = l.id
          ORDER BY br.created_at DESC";
$records = getRows($conn, $query);
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

    <div class="page-header">
        <h1><span class="icon icon-history"></span> Borrow Records</h1>
        <button class="btn btn-primary" data-modal-target="#addBorrowModal">+ New Borrow Record</button>
    </div>

    <div class="content-section">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php if(isset($_POST['action']) && $_POST['action'] === 'add_borrow'): ?>
                <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('addBorrowModal').classList.add('active'));</script>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($records)): ?>
            <div class="alert alert-info">
                No borrow records found. <button class="btn-action btn-edit" data-modal-target="#addBorrowModal">Create the first record</button>
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
                                        <button class="btn-action btn-success" onclick="openReturnModal(<?php echo intval($record['id']); ?>, '<?php echo date('Y-m-d', strtotime($record['due_date'])); ?>')">Return</button>
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
    </div>

    <!-- Add Borrow Modal -->
    <div id="addBorrowModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Borrow Record</h2>
                <button class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <?php if (empty($books)): ?>
                    <div class="alert alert-warning">No books available to borrow. Please add books or return some first.</div>
                <?php else: ?>
                    <form method="POST" id="addBorrowForm">
                        <input type="hidden" name="action" value="add_borrow">
                        
                        <div class="form-group">
                            <label for="student_name">Student Name *</label>
                            <input type="text" id="student_name" name="student_name" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="matric_number">Matric Number *</label>
                                <input type="text" id="matric_number" name="matric_number" required>
                            </div>
                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" id="department" name="department">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="book_id">Select Book *</label>
                            <select id="book_id" name="book_id" required>
                                <option value="">-- Choose a book --</option>
                                <?php foreach ($books as $book): ?>
                                    <option value="<?php echo intval($book['id']); ?>">
                                        <?php echo htmlspecialchars($book['title'] . ' (' . $book['available_copies'] . ' available)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="borrow_date">Borrow Date *</label>
                                <input type="date" id="borrow_date" name="borrow_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="due_date">Due Date *</label>
                                <input type="date" id="due_date" name="due_date" required>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-modal-close>Cancel</button>
                <?php if (!empty($books)): ?>
                    <button type="submit" form="addBorrowForm" class="btn btn-primary">Create Record</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Return Book Modal -->
    <div id="returnBookModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Return Book</h2>
                <button class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="returnBookForm">
                    <input type="hidden" name="action" value="return_book">
                    <input type="hidden" name="record_id" id="return_record_id">
                    
                    <div class="form-group">
                        <label for="return_date">Return Date *</label>
                        <input type="date" id="return_date" name="return_date" value="<?php echo date('Y-m-d'); ?>" required onchange="calculateFine()">
                    </div>

                    <div class="form-group">
                        <label for="fine_amount">Fine Amount (₦) *</label>
                        <input type="number" id="fine_amount" name="fine_amount" step="0.01" min="0" value="0.00" required>
                        <small id="fine_calculation" class="text-muted"></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" form="returnBookForm" class="btn btn-primary">Process Return</button>
            </div>
        </div>
    </div>

    <script>
        let currentDueDate = null;

        function openReturnModal(recordId, dueDate) {
            document.getElementById('return_record_id').value = recordId;
            currentDueDate = new Date(dueDate);
            document.getElementById('return_date').value = new Date().toISOString().split('T')[0];
            calculateFine();
            document.getElementById('returnBookModal').classList.add('active');
        }

        function calculateFine() {
            if(!currentDueDate) return;
            const returnDateStr = document.getElementById('return_date').value;
            const returnDate = new Date(returnDateStr);
            const diffTime = returnDate - currentDueDate;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            const fineInput = document.getElementById('fine_amount');
            const fineCalc = document.getElementById('fine_calculation');
            
            if (diffDays > 0) {
                const fine = diffDays * 100;
                fineInput.value = fine.toFixed(2);
                fineCalc.textContent = `Calculated: ₦${fine.toFixed(2)} (${diffDays} days overdue × ₦100/day)`;
                fineCalc.style.color = 'var(--color-warning)';
            } else {
                fineInput.value = '0.00';
                fineCalc.textContent = 'No overdue days';
                fineCalc.style.color = 'var(--color-success)';
            }
        }
    </script>

<?php require_once '../includes/footer.php'; ?>
