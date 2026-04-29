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
    $error = 'Invalid book ID';
} else {
    $query = "SELECT * FROM books WHERE id = ?";
    $book = getRow($conn, $query, [$book_id], 'i');
    
    if (!$book) {
        $error = 'Book not found';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($book)) {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $total_copies = intval($_POST['total_copies'] ?? 0);
    
    if (empty($title) || empty($author) || $total_copies <= 0) {
        $error = 'Please fill in all required fields correctly';
    } else {
        $update_query = "UPDATE books SET title = ?, author = ?, isbn = ?, category = ?, total_copies = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        
        if (!$stmt) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param('sssii', $title, $author, $isbn, $category, $total_copies, $book_id);
            
            if ($stmt->execute()) {
                $success = 'Book updated successfully!';
                $book = getRow($conn, "SELECT * FROM books WHERE id = ?", [$book_id], 'i');
            } else {
                $error = 'Error updating book: ' . $stmt->error;
            }
        }
    }
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

    <div class="page-header">
        <h1><span class="icon icon-books"></span> Edit Book</h1>
    </div>

    <div class="content-section">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($book)): ?>
            <form method="POST" class="form-container">
                <div class="form-group">
                    <label for="title">Book Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="author">Author *</label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($book['category'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="total_copies">Total Copies *</label>
                    <input type="number" id="total_copies" name="total_copies" min="1" value="<?php echo intval($book['total_copies']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Available Copies</label>
                    <input type="text" value="<?php echo intval($book['available_copies']); ?>" disabled style="background-color: #f5f5f5;">
                    <small>This is updated automatically when books are borrowed/returned</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Book</button>
                    <a href="/library/books/index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

<?php require_once '../includes/footer.php'; ?>
