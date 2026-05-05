<?php
header('Content-Type: application/json');
ob_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/library/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';

ob_end_clean();

if ($action === 'add_book') {
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
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding book: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} elseif ($action === 'edit_book') {
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
        } else {
            $update_query = "UPDATE books SET title=?, author=?, isbn=?, category=?, total_copies=?, available_copies=? WHERE id=?";
            $stmt = $conn->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param('ssssiii', $title, $author, $isbn, $category, $total_copies, $new_available, $book_id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Book updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating book: ' . $stmt->error]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
    }
} elseif ($action === 'delete') {
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
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
