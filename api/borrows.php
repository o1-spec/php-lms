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

$librarian_id = $_SESSION['librarian_id'];
$action = $_POST['action'] ?? '';

ob_end_clean();

if ($action === 'add_borrow') {
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
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error creating record: ' . $e->getMessage()]);
    }
} elseif ($action === 'return_book') {
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
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error processing return: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
