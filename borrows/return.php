<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';

requireLogin();
$page_title = 'Return Book';
$librarian_id = $_SESSION['librarian_id'];

$error = '';
$success = '';
$record = null;

$record_id = intval($_GET['id'] ?? 0);

if ($record_id <= 0) {
    header('Location: /library/borrows/index.php');
    exit();
}

$query = "SELECT br.*, b.title as book_title FROM borrow_records br JOIN books b ON br.book_id = b.id WHERE br.id = ?";
$record = getRow($conn, $query, [$record_id], 'i');

if (!$record || $record['status'] !== 'borrowed') {
    header('Location: /library/borrows/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return_date = trim($_POST['return_date'] ?? '');
    $fine_amount = floatval($_POST['fine_amount'] ?? 0);
    
    if (empty($return_date)) {
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
            header("refresh:2;url=/library/borrows/index.php");
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error processing return: ' . $e->getMessage();
        }
    }
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

    <div class="page-header">
        <h1><span class="icon icon-history"></span> Return Book</h1>
    </div>

    <div class="content-section">
        <div class="form-container">
            <div class="info-box">
                <p><strong>Book:</strong> <?php echo htmlspecialchars($record['book_title']); ?></p>
                <p><strong>Student:</strong> <?php echo htmlspecialchars($record['student_name']); ?></p>
                <p><strong>Matric Number:</strong> <?php echo htmlspecialchars($record['matric_number']); ?></p>
                <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($record['due_date'])); ?></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?> Redirecting to borrow records...
                </div>
            <?php endif; ?>

            <form method="POST" class="form">
                <div class="form-group">
                    <label for="return_date">Return Date *</label>
                    <input type="date" id="return_date" name="return_date" required value="<?php echo date('Y-m-d'); ?>" onchange="calculateFine()">
                </div>

                <div class="form-group">
                    <label for="fine_amount">Fine Amount (₦) *</label>
                    <input type="number" id="fine_amount" name="fine_amount" step="0.01" min="0" value="0.00" required>
                    <small id="fine_calculation" class="text-muted"></small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Process Return</button>
                    <a href="/library/borrows/index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .info-box {
            background-color: var(--color-grey-50);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--color-black);
        }

        .info-box p {
            margin: 0.5rem 0;
            font-size: 0.95rem;
        }

        .info-box strong {
            color: var(--color-black);
        }

        .form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--color-grey-900);
            font-size: 0.9375rem;
        }

        .form-group input {
            padding: 0.875rem 1rem;
            border: 1px solid var(--color-grey-300);
            border-radius: 8px;
            font-size: 0.9375rem;
            font-family: inherit;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--color-black);
            background-color: var(--color-grey-50);
        }

        .form-group small {
            font-size: 0.8125rem;
            margin-top: 0.25rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--color-black);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--color-grey-900);
        }

        .btn-secondary {
            background-color: var(--color-grey-200);
            color: var(--color-grey-900);
        }

        .btn-secondary:hover {
            background-color: var(--color-grey-300);
        }
    </style>

    <script>
        let currentDueDate = null;
        
        function calculateFine() {
            const dueDateStr = '<?php echo $record['due_date']; ?>';
            const returnDateStr = document.getElementById('return_date').value;
            
            const dueDate = new Date(dueDateStr);
            const returnDate = new Date(returnDateStr);
            const diffTime = returnDate - dueDate;
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
        
        document.addEventListener('DOMContentLoaded', calculateFine);
    </script>

<?php require_once '../includes/footer.php'; ?>
