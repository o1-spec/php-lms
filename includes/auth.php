<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['librarian_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /library/auth/login.php');
        exit();
    }
}

function getCurrentLibrarian($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $librarian_id = $_SESSION['librarian_id'];
    $query = "SELECT id, name, email FROM librarians WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $librarian_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>
