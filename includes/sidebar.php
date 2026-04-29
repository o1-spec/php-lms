        <div class="sidebar">
            <nav class="nav-menu">
                <a href="/library/dashboard.php" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) ? 'active' : ''; ?>">
                    <span class="icon icon-dashboard"></span> Dashboard
                </a>
                <a href="/library/books/index.php" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/books/') !== false) ? 'active' : ''; ?>">
                    <span class="icon icon-books"></span> Books
                </a>
                <a href="/library/borrows/index.php" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/borrows/') !== false) ? 'active' : ''; ?>">
                    <span class="icon icon-history"></span> Borrow Records
                </a>
                <a href="/library/reports/index.php" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/reports/') !== false) ? 'active' : ''; ?>">
                    <span class="icon icon-chart"></span> Reports
                </a>
            </nav>
        </div>

        <main class="main-content">
