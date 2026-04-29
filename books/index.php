<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';

requireLogin();
$page_title = 'Books';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_book') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $total_copies = intval($_POST['total_copies'] ?? 0);
        
        if (empty($title) || empty($author) || $total_copies <= 0) {
            $error = 'Please fill in all required fields correctly';
        } else {
            $insert_query = "INSERT INTO books (title, author, isbn, category, total_copies, available_copies) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            
            if ($stmt) {
                $stmt->bind_param('ssssii', $title, $author, $isbn, $category, $total_copies, $total_copies);
                
                if ($stmt->execute()) {
                    $success = 'Book added successfully!';
                } else {
                    $error = 'Error adding book: ' . $stmt->error;
                }
            } else {
                $error = 'Database error: ' . $conn->error;
            }
        }
    } elseif ($_POST['action'] === 'edit_book') {
        $book_id = intval($_POST['book_id']);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $total_copies = intval($_POST['total_copies'] ?? 0);
        
        if ($book_id <= 0 || empty($title) || empty($author) || $total_copies <= 0) {
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
    } elseif ($_POST['action'] === 'delete') {
        $book_id = intval($_POST['book_id']);
        
        $check_query = "SELECT id FROM books WHERE id = ?";
        $book = getRow($conn, $check_query, [$book_id], 'i');
        
        if ($book) {
            $delete_query = "DELETE FROM books WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param('i', $book_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting book']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Book not found']);
        }
        exit();
    }
}

$query = "SELECT * FROM books ORDER BY created_at DESC";
$books = getRows($conn, $query);

?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

    <div class="page-header">
        <h1><span class="icon icon-books"></span> Books Management</h1>
        <button class="btn btn-primary" data-modal-target="#addBookModal">+ Add New Book</button>
    </div>

    <div class="content-section">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('addBookModal').classList.add('active'));</script>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($books)): ?>
            <div class="alert alert-info">
                No books found. <button class="btn-action btn-edit" data-modal-target="#addBookModal">Add the first book</button>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Category</th>
                            <th>Total Copies</th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($book['category'] ?? 'N/A'); ?></td>
                                <td><?php echo intval($book['total_copies']); ?></td>
                                <td>
                                    <span class="badge <?php echo $book['available_copies'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo intval($book['available_copies']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-action btn-edit" 
                                            data-id="<?php echo $book['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($book['title']); ?>"
                                            data-author="<?php echo htmlspecialchars($book['author']); ?>"
                                            data-isbn="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>"
                                            data-category="<?php echo htmlspecialchars($book['category'] ?? ''); ?>"
                                            data-copies="<?php echo $book['total_copies']; ?>"
                                            onclick="openEditModal(this)">Edit</button>
                                    <button class="btn-action btn-delete" onclick="confirmDeleteBook(<?php echo intval($book['id']); ?>, '<?php echo addslashes(htmlspecialchars($book['title'])); ?>')">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Book Modal -->
    <div id="addBookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Book</h2>
                <button class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addBookForm">
                    <input type="hidden" name="action" value="add_book">
                    <div class="form-group">
                        <label for="title">Book Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="author">Author *</label>
                        <input type="text" id="author" name="author" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="isbn">ISBN</label>
                            <input type="text" id="isbn" name="isbn">
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="total_copies">Total Copies *</label>
                        <input type="number" id="total_copies" name="total_copies" min="1" value="1" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" form="addBookForm" class="btn btn-primary">Add Book</button>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div id="editBookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Book</h2>
                <button class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editBookForm">
                    <input type="hidden" name="action" value="edit_book">
                    <input type="hidden" name="book_id" id="edit_book_id">
                    <div class="form-group">
                        <label for="edit_title">Book Title *</label>
                        <input type="text" id="edit_title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_author">Author *</label>
                        <input type="text" id="edit_author" name="author" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_isbn">ISBN</label>
                            <input type="text" id="edit_isbn" name="isbn">
                        </div>

                        <div class="form-group">
                            <label for="edit_category">Category</label>
                            <input type="text" id="edit_category" name="category">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_total_copies">Total Copies *</label>
                        <input type="number" id="edit_total_copies" name="total_copies" min="1" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" form="editBookForm" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteBookModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <button class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteBookName"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-modal-close>Cancel</button>
                <button id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script>
        let bookToDelete = null;

        function openEditModal(button) {
            document.getElementById('edit_book_id').value = button.dataset.id;
            document.getElementById('edit_title').value = button.dataset.title;
            document.getElementById('edit_author').value = button.dataset.author;
            document.getElementById('edit_isbn').value = button.dataset.isbn;
            document.getElementById('edit_category').value = button.dataset.category;
            document.getElementById('edit_total_copies').value = button.dataset.copies;
            document.getElementById('editBookModal').classList.add('active');
        }

        function confirmDeleteBook(bookId, bookTitle) {
            bookToDelete = bookId;
            document.getElementById('deleteBookName').textContent = bookTitle;
            document.getElementById('deleteBookModal').classList.add('active');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (bookToDelete) {
                fetch('/library/books/index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete&book_id=' + bookToDelete
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        });
    </script>

<?php require_once '../includes/footer.php'; ?>
