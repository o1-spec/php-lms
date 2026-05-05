<?php
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/pagination.php';

requireLogin();
$page_title = 'Books';

$records_per_page = 15;
$current_page     = max(1, intval($_GET['page'] ?? 1));

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    if ($_POST['action'] === 'add_book') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $total_copies = intval($_POST['total_copies'] ?? 0);
        
        if (empty($title) || empty($author) || $total_copies <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields correctly']);
            exit();
        }
        
        $insert_query = "INSERT INTO books (title, author, isbn, category, total_copies, available_copies) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        if ($stmt) {
            $stmt->bind_param('ssssii', $title, $author, $isbn, $category, $total_copies, $total_copies);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Book added successfully!']);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding book: ' . $stmt->error]);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit();
        }
    } elseif ($_POST['action'] === 'edit_book') {
        $book_id = intval($_POST['book_id']);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $total_copies = intval($_POST['total_copies'] ?? 0);
        
        if ($book_id <= 0 || empty($title) || empty($author) || $total_copies <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields correctly']);
            exit();
        }
        
        $check_query = "SELECT total_copies, available_copies FROM books WHERE id = ?";
        $old_book = getRow($conn, $check_query, [$book_id], 'i');
        
        if ($old_book) {
            $diff = $total_copies - $old_book['total_copies'];
            $new_available = $old_book['available_copies'] + $diff;
            
            if ($new_available < 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot reduce total copies below currently borrowed copies.']);
                exit();
            } else {
                $update_query = "UPDATE books SET title=?, author=?, isbn=?, category=?, total_copies=?, available_copies=? WHERE id=?";
                $stmt = $conn->prepare($update_query);
                if ($stmt) {
                    $stmt->bind_param('ssssiii', $title, $author, $isbn, $category, $total_copies, $new_available, $book_id);
                    if ($stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Book updated successfully!']);
                        exit();
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error updating book: ' . $stmt->error]);
                        exit();
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                    exit();
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Book not found']);
            exit();
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

$count_result = getRow($conn, "SELECT COUNT(*) as total FROM books");
$total_books  = intval($count_result['total']);
$offset       = ($current_page - 1) * $records_per_page;

$query = "SELECT * FROM books ORDER BY created_at DESC LIMIT ? OFFSET ?";
$books = getRows($conn, $query, [$records_per_page, $offset], 'ii');

?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

    <div class="page-header">
        <h1><span class="icon icon-books"></span> Books Management</h1>
        <a href="/library/books/add.php" class="btn btn-primary">+ Add New Book</a>
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
                <?php echo htmlspecialchars($success); ?> Reloading...
            </div>
            <script>
                setTimeout(() => {
                    location.reload();
                }, 1500);
            </script>
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
                                    <a href="/library/books/edit.php?id=<?php echo intval($book['id']); ?>" class="btn-action btn-edit">Edit</a>
                                    <button class="btn-action btn-delete" onclick="confirmDeleteBook(<?php echo intval($book['id']); ?>, '<?php echo addslashes(htmlspecialchars($book['title'])); ?>')">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php echo build_pagination($total_books, $records_per_page, $current_page, '/library/books/index.php?page=%d'); ?>
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
