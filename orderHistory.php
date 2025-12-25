<?php
// Include session and database
include 'includes/session_db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signIn.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';

// Get user's orders from database
$ordersQuery = "SELECT po.*, pbi.first_name, pbi.last_name, pbi.email, pbi.address, pbi.city, pbi.state, pbi.zip_code
                FROM payment_orders po 
                LEFT JOIN payment_billing_info pbi ON po.billing_id = pbi.billing_id 
                WHERE po.user_id = ? 
                ORDER BY po.created_at DESC";

$stmt = $conn->prepare($ordersQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ordersResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Velvet Vogue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: transparent;
            box-shadow: none;
        }
        .navbar .nav-link {
            color: #003566;
        }
        .navbar .nav-link.active {
            color: #0d6efd;
            font-weight: 600;
        }
        .order-card {
            border: 1px solid #000;
            border-radius: 10px;
            box-shadow: none;
            margin-bottom: 20px;
        }
        .order-header {
            background: transparent;
            color: #003566;
            border-bottom: 1px solid #000;
            border-radius: 10px 10px 0 0;
            padding: 15px;
        }
        .status-pending { background-color: #008000; color: white; }
        .status-confirmed { background-color: #d1ecf1; color: #0c5460; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link active" href="orderHistory.php">Order History</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-clock-history"></i> Order History</h2>
                    <small class="text-muted">Welcome, <?php echo htmlspecialchars($user_name); ?></small>
                </div>

                <?php if ($ordersResult && $ordersResult->num_rows > 0): ?>
                    <?php while ($order = $ordersResult->fetch_assoc()): ?>
                        <div class="card order-card">
                            <div class="order-header">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-1">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                                        <small><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge fs-6 status-<?php echo strtolower($order['order_status']); ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="bi bi-person"></i> Delivery Details:</h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                        <p class="mb-1"><?php echo htmlspecialchars($order['address']); ?></p>
                                        <p class="mb-0"><?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['zip_code']); ?></p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <h6><i class="bi bi-cash"></i> Order Total:</h6>
                                        <h4 class="text-primary">$<?php echo number_format($order['total'], 2); ?></h4>
                                        <small class="text-muted">
                                            Subtotal: $<?php echo number_format($order['subtotal'], 2); ?><br>
                                            Shipping: $<?php echo number_format($order['shipping'], 2); ?><br>
                                            <?php if ($order['discount'] > 0): ?>
                                                Discount: -$<?php echo number_format($order['discount'], 2); ?><br>
                                            <?php endif; ?>
                                            Tax: $<?php echo number_format($order['tax'], 2); ?>
                                        </small>
                                    </div>
                                </div>

                                <?php
                                // Get order items
                                $itemsQuery = "SELECT * FROM payment_purchase_items WHERE order_id = ?";
                                $itemsStmt = $conn->prepare($itemsQuery);
                                $itemsStmt->bind_param("i", $order['order_id']);
                                $itemsStmt->execute();
                                $itemsResult = $itemsStmt->get_result();
                                ?>

                                <?php if ($itemsResult->num_rows > 0): ?>
                                    <hr>
                                    <h6><i class="bi bi-bag"></i> Items:</h6>
                                    <?php while ($item = $itemsResult->fetch_assoc()): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                 class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    $<?php echo number_format($item['product_price'], 2); ?> × <?php echo $item['quantity']; ?> = 
                                                    $<?php echo number_format($item['total_price'], 2); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-bag-x text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3">No Orders Yet</h4>
                            <p class="text-muted">You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left"></i> Start Shopping
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Clean up
if (isset($stmt)) $stmt->close();
if (isset($conn)) $conn->close();
?>