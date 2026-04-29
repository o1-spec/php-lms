<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/library/includes/auth.php';

requireLogin();

$current_librarian = getCurrentLibrarian($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Library Management System' : 'Library Management System'; ?></title>
    <link rel="stylesheet" href="/library/assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo"><span class="icon icon-books"></span> Library Management</div>
                <div class="user-section">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($current_librarian['name']); ?></span>
                    <button class="btn-logout" data-modal-target="#logoutModal">Logout</button>
                </div>
            </div>
        </header>

        <!-- Logout Modal -->
        <div id="logoutModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Confirm Logout</h2>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to end your current session?</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-modal-close>Cancel</button>
                    <a href="/library/auth/logout.php" class="btn btn-danger">Yes, Logout</a>
                </div>
            </div>
        </div>