<?php
// Include common admin authentication
include 'includes/auth_admin.php';

// Enable debugging if requested
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// EXACT SAME LOGIC AS ORDERS.PHP - Get all orders
$orders = [];

// First try: orders table with joins
try {
    $query = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone, p.product_name, p.price as product_price, c.category_name 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              LEFT JOIN products p ON o.product_id = p.product_id 
              LEFT JOIN categories c ON p.category_id = c.category_id
              ORDER BY o.created_at DESC";
    
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
} catch (Exception $e) {
    // Continue to next attempt
}

// If no orders found, try payment_orders table
if (empty($orders)) {
    try {
        $query = "SELECT po.*, u.first_name, u.last_name, u.email, u.phone, p.product_name, p.price as product_price, c.category_name
                  FROM payment_orders po 
                  LEFT JOIN users u ON po.user_id = u.id 
                  LEFT JOIN products p ON po.product_id = p.product_id 
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  ORDER BY po.created_at DESC";
        
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
    } catch (Exception $e) {
        // Continue
    }
}

// If still no orders, try just orders table without joins
if (empty($orders)) {
    try {
        $query = "SELECT * FROM orders ORDER BY created_at DESC";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
    } catch (Exception $e) {
        // Continue
    }
}

// If still no orders, try payment_orders without joins
if (empty($orders)) {
    try {
        $query = "SELECT * FROM payment_orders ORDER BY created_at DESC";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['order_id'] = $row['id'] ?? $row['order_id'] ?? 0;
                $orders[] = $row;
            }
        }
    } catch (Exception $e) {
        // Final fallback
    }
}

// Helper function to get order status (EXACT SAME AS ORDERS.PHP)
function getOrderStatus($order) {
    return $order['status'] ?? $order['order_status'] ?? $order['payment_status'] ?? 'pending';
}

// Calculate analytics from actual orders data
$totalOrders = count($orders);
$pendingOrders = count(array_filter($orders, function($o) { return getOrderStatus($o) == 'pending'; }));
$confirmedOrders = count(array_filter($orders, function($o) { return getOrderStatus($o) == 'confirmed'; }));
$completedOrders = count(array_filter($orders, function($o) { return getOrderStatus($o) == 'completed'; }));
$cancelledOrders = count(array_filter($orders, function($o) { return getOrderStatus($o) == 'cancelled'; }));

// Calculate total revenue
$totalRevenue = 0;
foreach ($orders as $order) {
    $amount = $order['total_amount'] ?? $order['amount'] ?? $order['total'] ?? 0;
    if (getOrderStatus($order) != 'cancelled') {
        $totalRevenue += (float)$amount;
    }
}

// Get unique users count
$uniqueUsers = array_unique(array_column($orders, 'user_id'));
$totalUsers = count(array_filter($uniqueUsers));

// Get total products count
$totalProducts = 0;
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalProducts = $row['count'];
    }
} catch (Exception $e) {
    $totalProducts = 0;
}

// Process orders by date for daily trend (last 30 days)
$dailyOrderTrend = [];
$dailyOrderLabels = [];

$ordersByDate = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $ordersByDate[$date] = 0;
}

foreach ($orders as $order) {
    $orderDate = date('Y-m-d', strtotime($order['created_at']));
    if (isset($ordersByDate[$orderDate])) {
        $ordersByDate[$orderDate]++;
    }
}

$dailyOrderLabels = array_keys($ordersByDate);
$dailyOrderTrend = array_values($ordersByDate);

// Order Status Distribution
$statusDistribution = [
    'pending' => $pendingOrders,
    'confirmed' => $confirmedOrders,
    'completed' => $completedOrders,
    'cancelled' => $cancelledOrders
];

// Top Selling Categories (last 30 days) - Only Completed Orders
$categoryCounts = [];
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
foreach ($orders as $order) {
    $orderDate = date('Y-m-d', strtotime($order['created_at']));
    // Only include orders from last 30 days AND only completed orders
    if ($orderDate >= $thirtyDaysAgo && getOrderStatus($order) == 'completed') {
        $categoryName = isset($order['category_name']) && !empty($order['category_name']) ? $order['category_name'] : 'General';
        
        if (!isset($categoryCounts[$categoryName])) {
            $categoryCounts[$categoryName] = 0;
        }
        $categoryCounts[$categoryName]++;
    }
}

arsort($categoryCounts);
$topCategories = array_slice($categoryCounts, 0, 5, true);
$categoryLabels = array_keys($topCategories);
$categorySales = array_values($topCategories);

// Assign colors to categories
$categoryColors = [
    'Boys' => '#36A2EB',
    'Girls' => '#FF6B9D', 
    'Men' => '#4BC0C0',
    'Women' => '#FF6384',
    'Blouses' => '#9966FF',
    'Shirts' => '#FF9F40',
    'Dresses' => '#FFCE56',
    'Pants' => '#45B7D1',
    'Shoes' => '#96CEB4',
    'Accessories' => '#FFA726',
    'General' => '#C9CBCF'
];

$categoryBackgroundColors = [];
$categoryBorderColors = [];
foreach ($categoryLabels as $categoryName) {
    $color = $categoryColors[$categoryName] ?? '#C9CBCF';
    $categoryBackgroundColors[] = $color;
    $categoryBorderColors[] = $color;
}

if (empty($categoryLabels)) {
    $categoryLabels = ['No Categories'];
    $categorySales = [0];
    $categoryBackgroundColors = ['#C9CBCF'];
    $categoryBorderColors = ['#C9CBCF'];
}

// Revenue Trend (last 30 days)
$revenueByDate = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $revenueByDate[$date] = 0;
}

foreach ($orders as $order) {
    $orderDate = date('Y-m-d', strtotime($order['created_at']));
    if (isset($revenueByDate[$orderDate])) {
        $amount = $order['total_amount'] ?? $order['amount'] ?? $order['total'] ?? 0;
        if (getOrderStatus($order) != 'cancelled') {
            $revenueByDate[$orderDate] += (float)$amount;
        }
    }
}

$revenueLabels = array_keys($revenueByDate);
$revenueTrend = array_values($revenueByDate);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Velvet Vogue Admin</title>
    
    <!-- Bootstrap 5.3.8 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
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
            background-color: var(--light-bg);
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

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
            padding: 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .logo h3 {
            color: white;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .nav-menu {
            padding: 0 15px;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link i {
            margin-right: 10px;
            font-size: 18px;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .header {
            background: var(--white);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .chart-container {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            height: 400px;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        canvas {
            max-height: 300px !important;
        }

        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.9rem;
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
                <a href="adm_dashboard.php" class="nav-link">
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
                <a href="./analytics.php" class="nav-link active">
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Analytics Dashboard</h1>
                <div class="d-flex align-items-center">
                    <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</span>
                    <a href="adm_dashboard.php?logout=1" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4">
            <?php if ($debug): ?>
            <div class="debug-info">
                <h5>Debug Information:</h5>
                <p><strong>Total Orders Found:</strong> <?php echo $totalOrders; ?></p>
                <p><strong>Order Sources:</strong> 
                <?php 
                if (!empty($orders)) {
                    echo "Data loaded successfully";
                } else {
                    echo "No orders found - checking database...";
                    
                    // Show table existence check
                    try {
                        $tables = [];
                        $result = $conn->query("SHOW TABLES");
                        if ($result) {
                            while ($row = $result->fetch_array()) {
                                $tables[] = $row[0];
                            }
                            echo "<br>Available tables: " . implode(", ", $tables);
                            
                            if (in_array('orders', $tables)) {
                                $count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc();
                                echo "<br>Orders table records: " . $count['count'];
                            }
                            
                            if (in_array('payment_orders', $tables)) {
                                $count = $conn->query("SELECT COUNT(*) as count FROM payment_orders")->fetch_assoc();
                                echo "<br>Payment_orders table records: " . $count['count'];
                            }
                        }
                    } catch (Exception $e) {
                        echo "<br>Database error: " . $e->getMessage();
                    }
                }
                ?>
                </p>
                <p><strong>Status Distribution:</strong> 
                Pending: <?php echo $pendingOrders; ?>, 
                Confirmed: <?php echo $confirmedOrders; ?>, 
                Completed: <?php echo $completedOrders; ?>, 
                Cancelled: <?php echo $cancelledOrders; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Charts Grid -->
            <div class="row">
                <!-- Daily Order Trend -->
                <div class="col-xl-6 mb-4">
                    <div class="chart-container">
                        <h5 class="chart-title">Daily Order Trend (Last 30 Days)</h5>
                        <canvas id="dailyOrderChart"></canvas>
                    </div>
                </div>

                <!-- Order Status Distribution -->
                <div class="col-xl-6 mb-4">
                    <div class="chart-container">
                        <h5 class="chart-title">Order Status Distribution</h5>
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Top Selling Products -->
                <div class="col-xl-6 mb-4">
                    <div class="chart-container">
                        <h5 class="chart-title">Top Selling Categories - Completed Orders (Last 30 Days)</h5>
                        <canvas id="productChart"></canvas>
                    </div>
                </div>

                <!-- Revenue Trend -->
                <div class="col-xl-6 mb-4">
                    <div class="chart-container">
                        <h5 class="chart-title">Revenue Trend (Last 30 Days)</h5>
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Chart.js configuration
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.color = '#6c757d';

        // Daily Order Trend Chart
        const dailyOrderCtx = document.getElementById('dailyOrderChart').getContext('2d');
        new Chart(dailyOrderCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($date) { return date('M d', strtotime($date)); }, $dailyOrderLabels)); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode($dailyOrderTrend); ?>,
                    borderColor: '#219ebc',
                    backgroundColor: 'rgba(33, 158, 188, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Order Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [
                        <?php echo $statusDistribution['pending']; ?>,
                        <?php echo $statusDistribution['confirmed']; ?>,
                        <?php echo $statusDistribution['completed']; ?>,
                        <?php echo $statusDistribution['cancelled']; ?>
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#219ebc',
                        '#28a745',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Top Selling Categories Chart
        const productCtx = document.getElementById('productChart').getContext('2d');
        new Chart(productCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($categoryLabels); ?>,
                datasets: [{
                    label: 'Number of Orders Sold',
                    data: <?php echo json_encode($categorySales); ?>,
                    backgroundColor: <?php echo json_encode($categoryBackgroundColors); ?>,
                    borderColor: <?php echo json_encode($categoryBorderColors); ?>,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            generateLabels: function() {
                                return [{
                                    text: 'Orders Sold (Last 30 Days)',
                                    fillStyle: '#666',
                                    strokeStyle: '#666'
                                }];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return context[0].label + ' Category';
                            },
                            label: function(context) {
                                return 'Orders Sold: ' + context.parsed.y + ' orders in last 30 days';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Product Categories'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        },
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            callback: function(value) {
                                return value + ' orders';
                            }
                        }
                    }
                }
            }
        });

        // Revenue Trend Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($date) { return date('M d', strtotime($date)); }, $revenueLabels)); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($revenueTrend); ?>,
                    backgroundColor: '#28a745',
                    borderColor: '#20c997',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>