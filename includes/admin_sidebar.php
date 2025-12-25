<!-- Admin Sidebar Navigation -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <h3>Velvet Vogue</h3>
        <small style="color: rgba(255, 255, 255, 0.7)">Admin Panel</small>
    </div>
    <nav class="nav-menu">
        <div class="nav-item">
            <a href="adm_dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'adm_dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                <i class="bi bi-bag-check"></i>
                Products
            </a>
        </div>
        <div class="nav-item">
            <a href="orders.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                Orders
            </a>
        </div>
        <div class="nav-item">
            <a href="view_inquiry.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'view_inquiry.php' ? 'active' : '' ?>">
                <i class="bi bi-envelope"></i>
                View Inquiry
            </a>
        </div>
        <div class="nav-item">
            <a href="analytics.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart"></i>
                Analytics
            </a>
        </div>
        <div class="nav-item">
            <a href="categories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
                <i class="bi bi-tags"></i>
                Categories
            </a>
        </div>
        <div class="nav-item">
            <a href="logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </div>
    </nav>
</div>