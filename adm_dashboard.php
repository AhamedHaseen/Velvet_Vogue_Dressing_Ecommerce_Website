<?php
// Include common admin authentication
include 'includes/auth_admin.php';

// Get analytics data
$totalUsers = executeQuery($conn, "SELECT COUNT(*) FROM users WHERE 1");
$totalProducts = executeQuery($conn, "SELECT COUNT(*) FROM products WHERE 1");
$totalOrders = executeQuery($conn, "SELECT COUNT(*) FROM orders WHERE 1");
if ($totalOrders == 0) {
    $totalOrders = executeQuery($conn, "SELECT COUNT(*) FROM payment_orders WHERE 1");
}

$totalRevenue = 0;
try {
    $result = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $totalRevenue = $row['revenue'] ?? 0;
    }
    if ($totalRevenue == 0) {
        $result = $conn->query("SELECT SUM(total) as revenue FROM payment_orders WHERE 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalRevenue = $row['revenue'] ?? 0;
        }
    }
} catch (Exception $e) {
    $totalRevenue = 0;
}

// Get recent activity and analytics
$recentUsers = executeQuery($conn, "SELECT COUNT(*) FROM users WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)");

// Get inquiry notifications count (new/unread contact inquiries)
$newInquiries = executeQuery($conn, "SELECT COUNT(*) FROM contacts WHERE status = 'new' OR status = 'in_progress'");

// Get recent inquiry details
$recentInquiries = [];
try {
    $result = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as customer_name, email, subject, category, priority, status, created_at FROM contacts WHERE status IN ('new', 'in_progress') ORDER BY created_at DESC LIMIT 10");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $recentInquiries[] = $row;
        }
    }
} catch (Exception $e) {
    // Fallback - empty array
    $recentInquiries = [];
}

// Get recent orders data - try multiple table structures
$recentOrders = 0;
$recentOrdersDetails = [];

try {
    // First try orders table
    $recentOrders = executeQuery($conn, "SELECT COUNT(*) FROM orders WHERE 1");
    
    if ($recentOrders > 0) {
        // Check if created_at column exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM orders LIKE 'created_at'");
        if ($checkColumn && $checkColumn->num_rows > 0) {
            $recentOrders = executeQuery($conn, "SELECT COUNT(*) FROM orders WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)");
        } else {
            // Try order_date instead
            $checkOrderDate = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_date'");
            if ($checkOrderDate && $checkOrderDate->num_rows > 0) {
                $recentOrders = executeQuery($conn, "SELECT COUNT(*) FROM orders WHERE DATE(order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)");
            } else {
                // Just get recent orders without date filter
                $recentOrders = executeQuery($conn, "SELECT COUNT(*) FROM orders LIMIT 7");
            }
        }
        
        // First check what columns exist in orders table
        $columnsResult = $conn->query("SHOW COLUMNS FROM orders");
        $columns = [];
        if ($columnsResult) {
            while ($col = $columnsResult->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
        }
        
        // Build query based on available columns
        $selectFields = ['id'];
        $customerField = 'Unknown Customer';
        $emailField = null;
        $productField = 'Product';
        $amountField = '0';
        $dateField = 'CURDATE()';
        $statusField = 'pending';
        
        // Check for customer name variations
        if (in_array('customer_name', $columns)) {
            $customerField = 'customer_name';
        } elseif (in_array('name', $columns)) {
            $customerField = 'name';
        } elseif (in_array('customer', $columns)) {
            $customerField = 'customer';
        } elseif (in_array('full_name', $columns)) {
            $customerField = 'full_name';
        }
        
        // Check for email variations
        if (in_array('customer_email', $columns)) {
            $emailField = 'customer_email';
        } elseif (in_array('email', $columns)) {
            $emailField = 'email';
        }
        
        // Check for product variations
        if (in_array('product_name', $columns)) {
            $productField = 'product_name';
        } elseif (in_array('product', $columns)) {
            $productField = 'product';
        } elseif (in_array('item_name', $columns)) {
            $productField = 'item_name';
        }
        
        // Check for amount variations
        if (in_array('total_amount', $columns)) {
            $amountField = 'total_amount';
        } elseif (in_array('total', $columns)) {
            $amountField = 'total';
        } elseif (in_array('amount', $columns)) {
            $amountField = 'amount';
        } elseif (in_array('price', $columns)) {
            $amountField = 'price';
        }
        
        // Check for date variations
        if (in_array('created_at', $columns)) {
            $dateField = 'created_at';
        } elseif (in_array('order_date', $columns)) {
            $dateField = 'order_date';
        } elseif (in_array('date', $columns)) {
            $dateField = 'date';
        }
        
        // Check for status
        if (in_array('status', $columns)) {
            $statusField = 'status';
        } elseif (in_array('order_status', $columns)) {
            $statusField = 'order_status';
        }
        
        // Build and execute the dynamic query
        $detailsQuery = "SELECT id, 
                               {$customerField} as customer_name,
                               " . ($emailField ? $emailField : "''" ) . " as customer_email,
                               {$productField} as product_name,
                               {$amountField} as total_amount,
                               {$dateField} as order_date,
                               '{$statusField}' as status
                        FROM orders ORDER BY id DESC LIMIT 5";
        
        $result = $conn->query($detailsQuery);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $recentOrdersDetails[] = [
                    'id' => $row['id'],
                    'customer' => $row['customer_name'] ?: 'Unknown Customer',
                    'email' => $row['customer_email'] ?: null,
                    'product' => $row['product_name'] ?: 'Product',
                    'amount' => (float)$row['total_amount'],
                    'date' => $row['order_date'] ?: date('Y-m-d'),
                    'status' => $row['status'] ?: 'pending'
                ];
            }
        }
    }
    
    // If still no orders, try payment_orders table
    if ($recentOrders == 0) {
        $recentOrders = executeQuery($conn, "SELECT COUNT(*) FROM payment_orders WHERE 1");
        
        if ($recentOrders > 0) {
            $checkColumn = $conn->query("SHOW COLUMNS FROM payment_orders LIKE 'created_at'");
            if ($checkColumn && $checkColumn->num_rows > 0) {
                $recentOrders = executeQuery($conn, "SELECT COUNT(*) FROM payment_orders WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)");
            }
            
            // Check payment_orders table columns
            $paymentColumnsResult = $conn->query("SHOW COLUMNS FROM payment_orders");
            $paymentColumns = [];
            if ($paymentColumnsResult) {
                while ($col = $paymentColumnsResult->fetch_assoc()) {
                    $paymentColumns[] = $col['Field'];
                }
            }
            
            // Build dynamic query for payment_orders
            $paymentCustomerField = 'Unknown Customer';
            $paymentEmailField = null;
            $paymentProductField = 'Product';
            $paymentAmountField = '0';
            $paymentDateField = 'CURDATE()';
            
            // Check customer field variations
            if (in_array('customer_name', $paymentColumns)) {
                $paymentCustomerField = 'customer_name';
            } elseif (in_array('name', $paymentColumns)) {
                $paymentCustomerField = 'name';
            } elseif (in_array('customer', $paymentColumns)) {
                $paymentCustomerField = 'customer';
            } elseif (in_array('full_name', $paymentColumns)) {
                $paymentCustomerField = 'full_name';
            }
            
            // Check other fields
            if (in_array('email', $paymentColumns)) {
                $paymentEmailField = 'email';
            }
            
            if (in_array('product_name', $paymentColumns)) {
                $paymentProductField = 'product_name';
            } elseif (in_array('product', $paymentColumns)) {
                $paymentProductField = 'product';
            }
            
            if (in_array('total_amount', $paymentColumns)) {
                $paymentAmountField = 'total_amount';
            } elseif (in_array('amount', $paymentColumns)) {
                $paymentAmountField = 'amount';
            } elseif (in_array('total', $paymentColumns)) {
                $paymentAmountField = 'total';
            }
            
            if (in_array('created_at', $paymentColumns)) {
                $paymentDateField = 'created_at';
            } elseif (in_array('order_date', $paymentColumns)) {
                $paymentDateField = 'order_date';
            }
            
            $paymentDetailsQuery = "SELECT id,
                                          {$paymentCustomerField} as customer_name,
                                          " . ($paymentEmailField ? $paymentEmailField : "''" ) . " as customer_email,
                                          {$paymentProductField} as product_name,
                                          {$paymentAmountField} as total_amount,
                                          {$paymentDateField} as order_date
                                   FROM payment_orders ORDER BY id DESC LIMIT 5";
            
            $result = $conn->query($paymentDetailsQuery);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $recentOrdersDetails[] = [
                        'id' => $row['id'],
                        'customer' => $row['customer_name'] ?: 'Unknown Customer',
                        'email' => $row['customer_email'] ?: null,
                        'product' => $row['product_name'] ?: 'Product',
                        'amount' => (float)$row['total_amount'],
                        'date' => $row['order_date'] ?: date('Y-m-d'),
                        'status' => 'completed'
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    // No fallback data - show only real orders
    $recentOrders = 0;
    $recentOrdersDetails = [];
}

$lowStockProducts = executeQuery($conn, "SELECT COUNT(*) FROM products WHERE stock_quantity <= 10");
$totalCategories = executeQuery($conn, "SELECT COUNT(*) FROM categories WHERE 1");

// Calculate growth percentages
$userGrowth = $totalUsers > 0 ? round(($recentUsers / $totalUsers) * 100, 1) : 0;
$orderGrowth = $totalOrders > 0 ? round(($recentOrders / $totalOrders) * 100, 1) : 0;

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: adm_login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Velvet Vogue</title>

    <!-- Bootstrap 5.3.8 CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <!-- Bootstrap Icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />

    <!-- Bootstrap Icons for enhanced analytics -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Sweet Alert 2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Fonts -->
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <style>
      * {
        font-family: "Inter", sans-serif;
      }

      :root {
        --primary-color: #219ebc;
        --secondary-color: #8ecae6;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --dark-color: #2c3e50;
        --light-bg: #f8f9fa;
        --white: #ffffff;
        --sidebar-width: 280px;
        --header-height: 70px;
      }

      body {
        background: #f8f9fa;
        min-height: 100vh;
        overflow-x: hidden;
      }

      /* Sidebar Styles */
      .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: var(--sidebar-width);
        background: linear-gradient(
          180deg,
          #001d3d 0%,
          #012a5f 100%
        );
        z-index: 1000;
        transition: all 0.3s ease;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
      }

      .sidebar .logo {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }

      .sidebar .logo h3 {
        color: var(--white);
        font-weight: 700;
        margin: 0;
        font-size: 1.5rem;
      }

      .sidebar .nav-menu {
        padding: 20px 0;
      }

      .sidebar .nav-item {
        margin: 5px 15px;
      }

      .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 15px 20px;
        border-radius: 12px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        text-decoration: none;
        font-weight: 500;
      }

      .sidebar .nav-link:hover,
      .sidebar .nav-link.active {
        background: rgba(255, 255, 255, 0.15);
        color: var(--white);
        transform: translateX(5px);
      }

      .sidebar .nav-link i {
        margin-right: 12px;
        font-size: 1.1rem;
        width: 20px;
      }

      /* Main Content */
      .main-content {
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        padding: 0;
        background: #f8f9fa;
      }

      /* Header */
      .header {
        background: #ffffff;
        height: var(--header-height);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        padding: 0 30px;
        position: sticky;
        top: 0;
        z-index: 999;
      }

      .header .search-box {
        position: relative;
        max-width: 400px;
        flex: 1;
      }

      .header .search-box input {
        border-radius: 25px;
        border: 1px solid #e0e0e0;
        padding: 10px 20px 10px 45px;
        width: 100%;
      }

      .header .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
      }

      .header .user-menu {
        display: flex;
        align-items: center;
        gap: 20px;
      }

      .header .notification-btn {
        position: relative;
        background: none;
        border: none;
        font-size: 1.3rem;
        color: var(--dark-color);
      }

      .header .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: var(--danger-color);
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .header .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 8px 15px;
        border-radius: 25px;
        transition: background 0.3s ease;
      }

      .header .user-profile:hover {
        background: var(--light-bg);
      }

      .header .avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: linear-gradient(
          45deg,
          var(--primary-color),
          var(--secondary-color)
        );
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
      }

      /* User Dropdown Menu */
      .user-dropdown {
        position: relative;
      }

      .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: var(--white);
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border: 1px solid #e0e0e0;
        min-width: 200px;
        padding: 10px 0;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
      }

      .dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
      }

      .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        color: var(--dark-color);
        text-decoration: none;
        transition: background 0.3s ease;
        font-size: 0.9rem;
      }

      .dropdown-item:hover {
        background: var(--light-bg);
        color: var(--primary-color);
      }

      .dropdown-divider {
        height: 1px;
        background: #e0e0e0;
        margin: 5px 0;
      }

      /* Dashboard Content */
      .dashboard-content {
        padding: 30px;
        background: transparent;
      }

      .page-title {
        margin-bottom: 30px;
      }

      .page-title h2 {
        color: var(--dark-color);
        font-weight: 700;
        margin: 0;
      }

      .page-title .breadcrumb {
        background: none;
        padding: 0;
        margin: 0;
        font-size: 0.9rem;
      }

      /* Stats Cards */
      .stats-card {
        background: #ffffff;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #e9ecef;
        position: relative;
        overflow: hidden;
      }

      .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
      }

      .stats-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(
          90deg,
          var(--primary-color),
          var(--secondary-color)
        );
      }

      .stats-card .icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        margin-bottom: 15px;
      }

      .stats-card .icon.primary {
        background: var(--primary-color);
      }

      .stats-card .icon.success {
        background: var(--success-color);
      }

      .stats-card .icon.warning {
        background: var(--warning-color);
      }

      .stats-card .icon.danger {
        background: var(--danger-color);
      }

      .stats-card .stats-info h3 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark-color);
        margin: 0;
      }

      .stats-card .stats-info p {
        color: #6c757d;
        margin: 0;
        font-weight: 500;
      }

      .stats-card .stats-change {
        font-size: 0.8rem;
        font-weight: 600;
        margin-top: 10px;
      }

      .stats-change.positive {
        color: var(--success-color);
      }

      .stats-change.negative {
        color: var(--danger-color);
      }

      /* Charts and Tables */
      .content-card {
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        margin-bottom: 30px;
      }

      .content-card .card-header {
        background: transparent;
        border-bottom: 1px solid #f0f0f0;
        padding: 20px 25px;
      }

      .content-card .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--dark-color);
        margin: 0;
      }

      .content-card .card-body {
        padding: 25px;
      }

      /* Table Styles */
      .table {
        margin: 0;
      }

      .table th {
        border-top: none;
        border-bottom: 2px solid #f0f0f0;
        font-weight: 600;
        color: var(--dark-color);
        padding: 15px;
      }

      .table td {
        padding: 15px;
        vertical-align: middle;
        border-top: 1px solid #f0f0f0;
      }

      .table tbody tr:hover {
        background-color: #f8f9fa;
      }

      /* Status Badges */
      .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
      }

      .status-completed {
        background: rgba(40, 167, 69, 0.1);
        color: var(--success-color);
      }

      .status-pending {
        background: rgba(255, 193, 7, 0.1);
        color: #e6a800;
      }

      .status-cancelled {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger-color);
      }

      /* Quick Actions */
      .quick-action-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px 20px;
        border-radius: 12px;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        color: var(--dark-color);
        background: var(--light-bg);
        margin-bottom: 10px;
        width: 100%;
      }

      .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        color: var(--primary-color);
      }

      /* Responsive Design */
      @media (max-width: 991.98px) {
        .sidebar {
          margin-left: -var(--sidebar-width);
        }

        .sidebar.show {
          margin-left: 0;
        }

        .main-content {
          margin-left: 0;
        }

        .dashboard-content {
          padding: 20px 15px;
        }

        .header {
          padding: 0 15px;
        }

        .header .search-box {
          display: none;
        }
      }

      .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--dark-color);
      }

      @media (max-width: 991.98px) {
        .mobile-menu-btn {
          display: block;
        }
      }

      .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
      }

      @media (max-width: 991.98px) {
        .sidebar-overlay.show {
          display: block;
        }
      }
    </style>
  </head>
  <body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="logo">
        <h3>Velvet Vogue</h3>
        <small style="color: rgba(255, 255, 255, 0.7)">Admin Panel</small>
      </div>
      <nav class="nav-menu">
        <div class="nav-item">
          <a href="#" class="nav-link active">
            <i class="bi bi-speedometer2"></i>
            Dashboard
          </a>
        </div>
        <div class="nav-item">
          <a href="./products.php" class="nav-link">
            <i class="bi bi-bag-check"></i>
            Products
          </a>
        </div>
        <div class="nav-item">
          <a href="./orders.php" class="nav-link">
            <i class="bi bi-receipt"></i>
            Orders
          </a>
        </div>
        <div class="nav-item">
          <a href="./view_inquiry.php" class="nav-link">
            <i class="bi bi-envelope"></i>
            View Inquiry
          </a>
        </div>
        <div class="nav-item">
          <a href="./analytics.php" class="nav-link">
            <i class="bi bi-bar-chart"></i>
            Analytics
          </a>
        </div>
        <div class="nav-item">
          <a href="./categories.php" class="nav-link">
            <i class="bi bi-tags"></i>
            Categories
          </a>
        </div>
      </nav>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Header -->
      <header class="header">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
          <i class="bi bi-list"></i>
        </button>

        <div class="search-box ms-4">
          <i class="bi bi-search"></i>
          <input type="text" class="form-control" placeholder="Search..." />
        </div>

        <div class="user-menu ms-auto">
          <button class="notification-btn" onclick="window.location.href='view_inquiry.php'" title="View Customer Inquiries (<?= $newInquiries ?> new)">
            <i class="bi bi-bell"></i>
            <?php if ($newInquiries > 0): ?>
            <span class="notification-badge"><?= $newInquiries ?></span>
            <?php endif; ?>
          </button>

          <!-- User Profile Display -->
          <div class="user-info d-flex align-items-center mx-3">
            <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
            <div class="d-none d-md-block ms-2">
              <div style="font-weight: 600; font-size: 0.9rem; color: var(--dark-color);">
                <?= htmlspecialchars($admin_name) ?>
              </div>
              <div style="font-size: 0.7rem; color: #6c757d">
                <?= htmlspecialchars($admin_role) ?>
              </div>
            </div>
          </div>

          <!-- Direct Logout Button -->
          <button class="btn btn-outline-danger btn-sm" onclick="Swal.fire({title: 'Logout Confirmation', text: 'Are you sure you want to logout?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, Logout', cancelButtonText: 'Cancel'}).then((result) => { if (result.isConfirmed) { window.location.href = 'adm_dashboard.php?logout=1'; } });">
            <i class="bi bi-box-arrow-right"></i>
            <span class="d-none d-md-inline ms-1">Logout</span>
          </button>
        </div>
      </header>

      <!-- Dashboard Content -->
      <div class="dashboard-content">
        <!-- Page Title -->
        <div class="page-title">
          <h2>Dashboard Overview</h2>
          <nav class="breadcrumb">
            <span class="breadcrumb-item active">Dashboard</span>
          </nav>
        </div>

        <!-- Welcome Message -->
        <div class="alert alert-info" style="background: linear-gradient(45deg, #e3f2fd, #f0f8ff); border: 1px solid #2196f3; border-radius: 10px;">
          <h5 style="margin: 0; color: #1976d2;">
            <i class="bi bi-person-circle"></i> Welcome back, <?= htmlspecialchars($admin_name) ?>!
          </h5>
          <p style="margin: 5px 0 0; color: #666; font-size: 0.9rem;">
            Logged in as: <?= htmlspecialchars($admin_email) ?> | Role: <?= htmlspecialchars($admin_role) ?>
          </p>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
          <div class="col-xl-3 col-md-6">
            <div class="stats-card">
              <div class="icon primary">
                <i class="bi bi-cash-stack"></i>
              </div>
              <div class="stats-info">
                <h3>$<?= number_format($totalRevenue, 2) ?></h3>
                <p>Total Revenue</p>
              </div>
              <div class="stats-change positive">
                <i class="bi bi-info-circle"></i> From all orders
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6">
            <div class="stats-card">
              <div class="icon success">
                <i class="bi bi-cart-check"></i>
              </div>
              <div class="stats-info">
                <h3><?= number_format($totalOrders) ?></h3>
                <p>Total Orders</p>
              </div>
              <div class="stats-change positive">
                <i class="bi bi-info-circle"></i> All time orders
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6">
            <div class="stats-card">
              <div class="icon warning">
                <i class="bi bi-people-fill"></i>
              </div>
              <div class="stats-info">
                <h3><?= number_format($totalUsers) ?></h3>
                <p>Total Users</p>
              </div>
              <div class="stats-change positive">
                <i class="bi bi-info-circle"></i> Registered users
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6">
            <div class="stats-card">
              <div class="icon danger">
                <i class="bi bi-box-seam"></i>
              </div>
              <div class="stats-info">
                <h3><?= number_format($totalProducts) ?></h3>
                <p>Total Products</p>
              </div>
              <div class="stats-change">
                <i class="bi bi-info-circle"></i> Available products
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content Row -->
        <div class="row g-4">
          <!-- Recent Orders -->
          <div class="col-lg-8">
            <div class="content-card">
              <div
                class="card-header d-flex justify-content-between align-items-center"
              >
                <h5 class="card-title">Recent Orders</h5>
                <a href="orders.php" class="btn btn-outline-primary btn-sm">View All</a>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($recentOrdersDetails)): ?>
                        <?php foreach (array_slice($recentOrdersDetails, 0, 4) as $index => $order): ?>
                        <tr>
                          <td>#VV<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></td>
                          <td>
                            <div class="d-flex align-items-center">
                              <div
                                class="avatar me-2"
                                style="
                                  width: 30px;
                                  height: 30px;
                                  font-size: 0.8rem;
                                "
                              >
                                <?= strtoupper(substr($order['customer'], 0, 2)) ?>
                              </div>
                              <div>
                                <div><?= htmlspecialchars($order['customer']) ?></div>
                                <?php if (isset($order['email']) && $order['email']): ?>
                                <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                                <?php endif; ?>
                              </div>
                            </div>
                          </td>
                          <td><?= htmlspecialchars($order['product']) ?></td>
                          <td>$<?= number_format($order['amount'], 2) ?></td>
                          <td>
                            <span class="status-badge status-<?= $order['status'] ?>">
                              <?= ucfirst($order['status']) ?>
                            </span>
                          </td>
                          <td><?= date('M j, Y', strtotime($order['date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                              <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                              <h5>No recent orders</h5>
                              <p>Orders will appear here when customers place them.</p>
                            </div>
                          </td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="col-lg-4">
            <div class="content-card mb-4">
              <div class="card-header">
                <h5 class="card-title">Quick Actions</h5>
              </div>
              <div class="card-body">
                <a href="add_product.php" class="quick-action-btn">
                  <i class="bi bi-plus-circle"></i>
                  Add New Product
                </a>
                <a href="view_inquiry.php" class="quick-action-btn">
                  <i class="bi bi-envelope"></i>
                  Inquiry Details
                </a>
                <a href="view_reports.php" class="quick-action-btn">
                  <i class="bi bi-bar-chart-line"></i>
                  View Reports
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Business Analytics - Full Width Below -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="content-card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Business Analytics</h5>
                <small class="text-muted">Last 7 Days</small>
              </div>
              <div class="card-body">
                <!-- Key Metrics Row -->
                <div class="row g-4 mb-4">
                  <div class="col-6 col-md-3">
                    <div class="metric-card text-center p-4" style="background: #f8f9fa; border-radius: 12px; border-left: 5px solid #219ebc; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                      <h3 class="mb-2 text-primary"><?= $recentOrders ?></h3>
                      <small class="text-muted d-block mb-2">Recent Orders</small>
                      <span class="badge bg-success">+<?= $orderGrowth ?>% (7 days)</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="metric-card text-center p-4" style="background: #f8f9fa; border-radius: 12px; border-left: 5px solid #28a745; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                      <h3 class="mb-2 text-success"><?= $recentOrders ?></h3>
                      <small class="text-muted d-block mb-2">New Orders</small>
                      <span class="badge bg-success">+<?= $orderGrowth ?>%</span>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="metric-card text-center p-4" style="background: #f8f9fa; border-radius: 12px; border-left: 5px solid #ffc107; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                      <h3 class="mb-2 <?= $lowStockProducts > 0 ? 'text-warning' : 'text-success' ?>"><?= $lowStockProducts ?></h3>
                      <small class="text-muted d-block mb-2">Low Stock</small>
                      <span class="badge <?= $lowStockProducts > 0 ? 'bg-warning' : 'bg-success' ?>">
                        <?= $lowStockProducts > 0 ? 'Alert' : 'Good' ?>
                      </span>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="metric-card text-center p-4" style="background: #f8f9fa; border-radius: 12px; border-left: 5px solid #6f42c1; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                      <h3 class="mb-2 text-info"><?= $totalCategories ?></h3>
                      <small class="text-muted d-block mb-2">Categories</small>
                      <span class="badge bg-info">Active</span>
                    </div>
                  </div>
                </div>

                <!-- Performance Summary -->
                <div class="row g-4">
                  <div class="col-md-6">
                    <div class="performance-summary p-4" style="background: #dd2d4a; border-radius: 12px; color: white; min-height: 120px; box-shadow: 0 4px 15px rgba(221, 45, 74, 0.3);">
                      <div class="d-flex justify-content-between align-items-center h-100">
                        <div>
                          <h4 class="mb-2">$<?= number_format($totalRevenue / max($totalOrders, 1), 2) ?></h4>
                          <small class="opacity-75 fs-6">Average Order Value</small>
                        </div>
                        <i class="bi bi-currency-dollar" style="font-size: 3rem; opacity: 0.3;"></i>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="performance-summary p-4" style="background: #dd2d4a; border-radius: 12px; color: white; min-height: 120px; box-shadow: 0 4px 15px rgba(221, 45, 74, 0.3);">
                      <div class="d-flex justify-content-between align-items-center h-100">
                        <div>
                          <h4 class="mb-2"><?= $totalUsers > 0 ? number_format(($totalOrders / $totalUsers) * 100, 1) : 0 ?>%</h4>
                          <small class="opacity-75 fs-6">Conversion Rate</small>
                        </div>
                        <i class="bi bi-graph-up" style="font-size: 3rem; opacity: 0.3;"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Orders Modal -->
    <div class="modal fade" id="recentOrdersModal" tabindex="-1" aria-labelledby="recentOrdersModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="recentOrdersModalLabel">Recent Orders (Last 7 Days)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if (!empty($recentOrdersDetails)): ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Order ID</th>
                      <th>Customer</th>
                      <th>Product</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recentOrdersDetails as $order): ?>
                    <tr>
                      <td>
                        <strong>#VV<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></strong>
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="avatar me-2" style="width: 32px; height: 32px; background: #219ebc; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.8rem; font-weight: bold;">
                            <?= strtoupper(substr($order['customer'], 0, 2)) ?>
                          </div>
                          <div>
                            <div class="fw-medium"><?= htmlspecialchars($order['customer']) ?></div>
                            <?php if (isset($order['email'])): ?>
                            <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td><?= isset($order['product']) ? htmlspecialchars($order['product']) : 'N/A' ?></td>
                      <td><strong>$<?= number_format($order['amount'], 2) ?></strong></td>
                      <td>
                        <span class="badge <?= $order['status'] === 'completed' ? 'bg-success' : ($order['status'] === 'pending' ? 'bg-warning text-dark' : ($order['status'] === 'cancelled' ? 'bg-danger' : 'bg-info')) ?>">
                          <?= ucfirst($order['status']) ?>
                        </span>
                      </td>
                      <td><?= date('M j, Y', strtotime($order['date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-2">No recent orders found in the last 7 days.</p>
              </div>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <a href="orders.php" class="btn btn-primary">View All Orders</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Inquiries Modal -->
    <div class="modal fade" id="inquiriesModal" tabindex="-1" aria-labelledby="inquiriesModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="inquiriesModalLabel">
              <i class="bi bi-bell"></i> Recent Customer Inquiries
              <?php if ($newInquiries > 0): ?>
              <span class="badge bg-primary ms-2"><?= $newInquiries ?> New</span>
              <?php endif; ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if (!empty($recentInquiries)): ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>ID</th>
                      <th>Customer</th>
                      <th>Subject</th>
                      <th>Category</th>
                      <th>Priority</th>
                      <th>Status</th>
                      <th>Date</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recentInquiries as $inquiry): ?>
                    <tr>
                      <td><strong>#<?= str_pad($inquiry['id'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="avatar me-2" style="width: 32px; height: 32px; background: #28a745; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.8rem; font-weight: bold;">
                            <?= strtoupper(substr($inquiry['customer_name'], 0, 2)) ?>
                          </div>
                          <div>
                            <div class="fw-medium"><?= htmlspecialchars($inquiry['customer_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($inquiry['email']) ?></small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($inquiry['subject']) ?>">
                          <?= htmlspecialchars($inquiry['subject']) ?>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-info"><?= ucfirst($inquiry['category']) ?></span>
                      </td>
                      <td>
                        <span class="badge <?= $inquiry['priority'] === 'urgent' ? 'bg-danger' : ($inquiry['priority'] === 'high' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                          <?= ucfirst($inquiry['priority']) ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge <?= $inquiry['status'] === 'new' ? 'bg-success' : 'bg-primary' ?>">
                          <?= ucfirst(str_replace('_', ' ', $inquiry['status'])) ?>
                        </span>
                      </td>
                      <td>
                        <small><?= date('M j, Y H:i', strtotime($inquiry['created_at'])) ?></small>
                      </td>
                      <td>
                        <a href="view_inquiry.php?search=<?= $inquiry['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                          <i class="bi bi-eye"></i>
                        </a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="bi bi-envelope-check fs-1 text-success"></i>
                <p class="text-muted mt-2">No pending inquiries at the moment!</p>
                <p class="text-muted">All customer inquiries have been resolved.</p>
              </div>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <a href="view_inquiry.php" class="btn btn-primary">
              <i class="bi bi-envelope"></i> View All Inquiries
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script>
      // Mobile menu toggle
      const mobileMenuBtn = document.getElementById("mobileMenuBtn");
      const sidebar = document.getElementById("sidebar");
      const sidebarOverlay = document.getElementById("sidebarOverlay");

      mobileMenuBtn.addEventListener("click", function () {
        sidebar.classList.toggle("show");
        sidebarOverlay.classList.toggle("show");
      });

      sidebarOverlay.addEventListener("click", function () {
        sidebar.classList.remove("show");
        sidebarOverlay.classList.remove("show");
      });

      // Close sidebar when window is resized to desktop
      window.addEventListener("resize", function () {
        if (window.innerWidth >= 992) {
          sidebar.classList.remove("show");
          sidebarOverlay.classList.remove("show");
        }
      });

      // Active navigation highlighting
      document.querySelectorAll(".nav-link").forEach((link) => {
        link.addEventListener("click", function (e) {
          // Only prevent default for dashboard link (current page)
          if (this.getAttribute("href") === "#") {
            e.preventDefault();
          }

          // Update active state
          document
            .querySelectorAll(".nav-link")
            .forEach((l) => l.classList.remove("active"));
          this.classList.add("active");
        });
      });

      // Simple Logout Function
      function handleLogout(event) {
        event.preventDefault();
        
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Logout?',
            text: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = 'adm_login.php?logout=success';
            }
          });
        } else {
          if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'adm_login.php?logout=success';
          }
        }
        return false;
      }

      // Logout Button - Fixed
      document.addEventListener('DOMContentLoaded', function() {
        const logoutBtn = document.getElementById("logoutBtn");

        if (logoutBtn) {
          logoutBtn.onclick = function(e) {
            e.preventDefault();
            
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                title: 'Logout Confirmation',
                text: 'Are you sure you want to logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel'
              }).then((result) => {
                if (result.isConfirmed) {
                  window.location.href = 'adm_dashboard.php?logout=1';
                }
              });
            } else {
              if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'adm_dashboard.php?logout=1';
              }
            }
            return false;
          };
        }
      });

      // Show Recent Orders Modal
      function showRecentOrdersModal() {
        const modal = new bootstrap.Modal(document.getElementById('recentOrdersModal'));
        modal.show();
      }

      // Show Inquiries Modal
      function showInquiriesModal() {
        const modal = new bootstrap.Modal(document.getElementById('inquiriesModal'));
        modal.show();
      }

      // Analytics Dashboard Interactivity
      document.addEventListener('DOMContentLoaded', function() {
        console.log('Analytics dashboard loaded successfully');\n        \n        // Add hover effects to analytics cards (visual only)
        const analyticsCards = document.querySelectorAll('[style*="background: #f8f9fa"]');
        analyticsCards.forEach(card => {
          card.style.transition = 'all 0.2s ease';
          
          card.addEventListener('mouseenter', function() {
            this.style.background = '#e9ecef !important';
            this.style.transform = 'translateY(-1px)';
          });
          
          card.addEventListener('mouseleave', function() {
            this.style.background = '#f8f9fa';
            this.style.transform = 'translateY(0)';
          });
        });

        // Add interactive effects to performance metrics (only stats cards)
        const metricCards = document.querySelectorAll('.stats-card');
        metricCards.forEach((card, index) => {
          card.style.transition = 'all 0.3s ease';
          
          card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05) translateY(-3px)';
            this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.2)';
          });
          
          card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) translateY(0)';
            this.style.boxShadow = 'none';
          });
        });

        // Add refresh button
        const refreshBtn = document.createElement('button');
        refreshBtn.className = 'btn btn-primary btn-sm position-fixed shadow';
        refreshBtn.style.cssText = 'bottom: 30px; right: 30px; z-index: 1000; border-radius: 50%; width: 50px; height: 50px; border: none;';
        refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
        refreshBtn.title = 'Refresh Analytics Data';
        refreshBtn.onclick = () => {
          refreshBtn.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
          setTimeout(() => location.reload(), 500);
        };
        document.body.appendChild(refreshBtn);

        // Add real-time clock
        function updateClock() {
          const now = new Date();
          const timeString = now.toLocaleTimeString();
          const clockElement = document.getElementById('currentTime');
          if (clockElement) {
            clockElement.textContent = timeString;
          }
        }
        
        // Add clock to navbar
        const navbar = document.querySelector('.navbar-text');
        if (navbar) {
          navbar.innerHTML += ' | <span id="currentTime"></span>';
          setInterval(updateClock, 1000);
          updateClock();
        }
      });
    </script>
  </body>
</html>

