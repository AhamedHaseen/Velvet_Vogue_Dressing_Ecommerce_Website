<?php
// Get user information if logged in
$userData = null;
$customerOrders = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get user information
    $userQuery = "SELECT first_name, last_name, email FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    // Get customer orders count
    $paymentQuery = "SELECT COUNT(*) as order_count FROM payment_orders WHERE user_id = ?";
    $stmt = $conn->prepare($paymentQuery);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_count = $result->fetch_assoc()['order_count'] ?? 0;
        $stmt->close();
    }
}
?>

<!-- Customer Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold text-primary" href="./index.php">
            Velvet Vogue
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item me-2">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="./index.php">
                        <i class="bx bx-home-alt me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item me-2">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'featureProductView.php' ? 'active' : '' ?>" href="./featureProductView.php">
                        <i class="bx bx-store me-1"></i>Products
                    </a>
                </li>
                <li class="nav-item me-2">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>" href="./about.php">
                        <i class="bx bx-info-circle me-1"></i>About
                    </a>
                </li>
                <li class="nav-item me-2">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>" href="./contact.php">
                        <i class="bx bx-phone me-1"></i>Contact
                    </a>
                </li>
            </ul>

            <!-- Right Side Items -->
            <div class="d-flex align-items-center ms-3">
                <!-- Search -->
                <form class="d-flex me-3" role="search">
                    <div class="input-group input-group-sm search-container">
                        <input class="form-control search-input" type="search" placeholder="Search products..." aria-label="Search" id="searchInput"/>
                        <button class="btn btn-outline-secondary search-btn" type="submit" aria-label="Search">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Favorites -->
                <a href="#" class="btn btn-outline-secondary position-relative me-2" title="Favorites">
                    <i class="bx bx-heart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark wishlist-counter" style="font-size: 0.65rem">
                        0
                    </span>
                </a>

                <!-- Cart -->
                <a href="./cart.php" class="btn btn-outline-primary position-relative me-2" title="Cart">
                    <i class="bx bx-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-counter">
                        0
                    </span>
                </a>

                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Profile">
                        <i class="bx bx-user me-1"></i>
                        <?php 
                        if (isset($_SESSION['user_id'])) {
                            if ($userData && !empty($userData['last_name'])) {
                                echo '<span>' . htmlspecialchars($userData['last_name']) . '</span>';
                            } else if ($userData && !empty($userData['first_name'])) {
                                echo '<span>' . htmlspecialchars($userData['first_name']) . '</span>';
                            } else if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
                                $nameParts = explode(' ', $_SESSION['user_name']);
                                $lastName = end($nameParts);
                                echo '<span>' . htmlspecialchars($lastName) . '</span>';
                            } else {
                                echo '<span>User #' . $_SESSION['user_id'] . '</span>';
                            }
                        } else {
                            echo '<span>Guest</span>';
                        }
                        ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li>
                            <a class="dropdown-item" href="./accountInformation.php">
                                <i class="bx bx-user me-2"></i>Account Information
                            </a>
                        </li>
                        <li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="dropdown-item" href="./orderHistory.php">
                                <i class="bx bx-receipt me-2"></i>Order History 
                                <span class="badge bg-primary ms-1"><?= $order_count ?? 0 ?></span>
                            </a>
                            <?php else: ?>
                            <a class="dropdown-item" href="./signIn.php">
                                <i class="bx bx-receipt me-2"></i>Order History
                            </a>
                            <?php endif; ?>
                        </li>
                        <li>
                            <a class="dropdown-item" href="./setting.php">
                                <i class="bx bx-cog me-2"></i>Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider" /></li>
                        <li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="dropdown-item text-danger" href="./logout.php">
                                <i class="bx bx-log-out me-2"></i>Logout
                            </a>
                            <?php else: ?>
                            <a class="dropdown-item text-success" href="./signIn.php">
                                <i class="bx bx-log-in me-2"></i>Login
                            </a>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>