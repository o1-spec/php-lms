<?php
// Index/Entry Point - Redirect to login or dashboard based on session

session_start();

if (isset($_SESSION['librarian_id'])) {
    // If already logged in, redirect to dashboard
    header('Location: /library/dashboard.php');
    exit();
} else {
    // If not logged in, redirect to login page
    header('Location: /library/auth/login.php');
    exit();
}
?>
