<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';

requireLogin();
$page_title = 'Edit Book';

$error = '';
$success = '';
$book = null;

$book_id = intval($_GET['id'] ?? 0);

if ($book_id <= 0) {
    header('Location: /library/books/index.php');
    exit();
}

$query = "SELECT * FROM books WHERE id = ?";
$book = getRow($conn, $query, [$book_id], 'i');

if (!$book) {
    header('Location: /library/books/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $total_copies = intval($_POST['total_copies'] ?? 0);
    
    if (empty($title) || empty($author) || $total_copies <= 0) {
        $error = 'Please fill in all required fields correctly';
    } else {
        $check_query = "SELECT total_copies, available_copies FROM books WHERE id = ?";
        $old_book = getRow($conn, $check_query, [$book_id], 'i');
        
        if ($old_book) {
            $diff = $total_copies - $old_book['total_copies'];
            $new_available = $old_book['available_copies'] + $diff;
            
            if ($new_available < 0) {
                $error = 'Cannot reduce total copies below currently borrowed copies.';
            } else {
                $update_query = "UPDATE books SET title=?, author=?, isbn=?, category=?, total_copies=?, available_copies=? WHERE id=?";
                $stmt = $conn->prepare($update_query);
                if ($stmt) {
                    $stmt->bind_param('ssssiii', $title, $author, $isbn, $category, $total_copies, $new_available, $book_id);
                    if ($stmt->execute()) {
                        $success = 'Book updated successfully!';
                        header("refresh:2;url=/library/books/index.php");
                    } else {
                        $error = 'Error updating book: ' . $stmt->error;
                    }
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
        } else {
            $error = 'Book not found';
        }
    }
    
    if (empty($error)) {
        $book = array_merge($book, [
            'title' => $title,
            'author' => $author,
            'isbn' => $isbn,
            'category' => $category,
            'total_copies' => $total_copies
        ]);
    }
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

    <div class="page-header">
        <h1><span class="icon icon-books"></span> Edit Book</h1>
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
                    <?php echo htmlspecialchars($success); ?> Redirecting to books list...
                </div>
            <?php endif; ?>

            <form method="POST" class="form">
                <div class="form-group">
                    <label for="title">Book Title *</label>
                    <input type="text" id="title" name="title" required placeholder="Enter book title" value="<?php echo htmlspecialchars($book['title'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="author">Author *</label>
                    <input type="text" id="author" name="author" required placeholder="Enter author name" value="<?php echo htmlspecialchars($book['author'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn" placeholder="Enter ISBN" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" placeholder="Enter category" value="<?php echo htmlspecialchars($book['category'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="total_copies">Total Copies *</label>
                    <input type="number" id="total_copies" name="total_copies" required min="1" placeholder="Enter number of copies" value="<?php echo htmlspecialchars($book['total_copies'] ?? ''); ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Book</button>
                    <a href="/library/books/index.php" class="btn btn-secondary">Cancel</a>
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

<?php require_once '../includes/footer.php'; ?>
