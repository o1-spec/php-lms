<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';

requireLogin();
$page_title = 'New Borrow Record';
$librarian_id = $_SESSION['librarian_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    header("refresh:2;url=/library/borrows/index.php");
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = 'Error creating record: ' . $e->getMessage();
                }
            }
        }
    }
}

$books_query = "SELECT * FROM books WHERE available_copies > 0 ORDER BY title";
$books = getRows($conn, $books_query, [], '');
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

    <div class="page-header">
        <h1><span class="icon icon-history"></span> New Borrow Record</h1>
    </div>

    <div class="content-section">
        <div class="form-container">
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
                    <label for="book_id">Book *</label>
                    <select id="book_id" name="book_id" required>
                        <option value="">Select a book</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?php echo $book['id']; ?>" <?php echo ($_POST['book_id'] ?? 0) == $book['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($book['title']); ?> (<?php echo $book['available_copies']; ?> available)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="student_name">Student Name *</label>
                    <input type="text" id="student_name" name="student_name" required placeholder="Enter student name" value="<?php echo htmlspecialchars($_POST['student_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="matric_number">Matric Number *</label>
                    <input type="text" id="matric_number" name="matric_number" required placeholder="Enter matric number" value="<?php echo htmlspecialchars($_POST['matric_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" placeholder="Enter department" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="borrow_date">Borrow Date *</label>
                    <input type="date" id="borrow_date" name="borrow_date" required value="<?php echo htmlspecialchars($_POST['borrow_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date *</label>
                    <input type="date" id="due_date" name="due_date" required value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Borrow Record</button>
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

        .form-group input,
        .form-group select {
            padding: 0.875rem 1rem;
            border: 1px solid var(--color-grey-300);
            border-radius: 8px;
            font-size: 0.9375rem;
            font-family: inherit;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-black);
            background-color: var(--color-grey-50);
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

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            color: #c62828;
        }

        .alert-success {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
        }
    </style>

<?php require_once '../includes/footer.php'; ?>
