<?php
// Include common admin authentication
include 'includes/auth_admin.php';

// Handle order status updates
if ($_POST && isset($_POST['action']) && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $action = $_POST['action'];
    
    $new_status = '';
    switch($action) {
        case 'confirm':
            $new_status = 'confirmed';
            break;
        case 'cancel':
            $new_status = 'cancelled';
            break;
        case 'complete':
            $new_status = 'completed';
            break;
        case 'pending':
            $new_status = 'pending';
            break;
    }
    
    if ($new_status) {
        $success = false;
        $error_message = "";
        
        // Update order status in payment_orders table
        try {
            $update_query = "UPDATE payment_orders SET order_status = ? WHERE order_id = ?";
            $stmt = $conn->prepare($update_query);
            
            if ($stmt) {
                $stmt->bind_param("si", $new_status, $order_id);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $success = true;
                } else {
                    $error_message = "Order #$order_id not found in payment_orders table.";
                }
                $stmt->close();
            } else {
                $error_message = "Failed to prepare update statement: " . $conn->error;
            }
        } catch (Exception $e) {
            $error_message = "Error updating order status: " . $e->getMessage();
        }
        
        if ($success) {
            $success_message = "Order #$order_id status updated to " . ucfirst($new_status);
            // Refresh the page to show updated status
            header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
            exit();
        }
    }
}

// Get all orders with customer details from payment_orders table (primary source)
$orders = [];

try {
    // Get orders with billing and user information
    $query = "SELECT 
                po.order_id,
                po.user_id,
                po.order_number,
                po.subtotal,
                po.shipping,
                po.tax,
                po.discount,
                po.total,
                po.order_status,
                po.created_at,
                pb.first_name,
                pb.last_name,
                pb.email,
                pb.phone,
                pb.address,
                pb.city,
                pb.state,
                pb.zip_code,
                pb.payment_method,
                pb.card_number_last4,
                pb.card_type,
                u.first_name as user_first_name,
                u.last_name as user_last_name,
                u.email as user_email
              FROM payment_orders po 
              LEFT JOIN payment_billing_info pb ON po.billing_id = pb.id
              LEFT JOIN users u ON po.user_id = u.id 
              ORDER BY po.created_at DESC";
    
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Use billing info as primary, fall back to user info
            $row['customer_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if (empty($row['customer_name']) || $row['customer_name'] == ' ') {
                $row['customer_name'] = trim(($row['user_first_name'] ?? '') . ' ' . ($row['user_last_name'] ?? ''));
            }
            
            $row['customer_email'] = $row['email'] ?? $row['user_email'] ?? 'N/A';
            $row['customer_phone'] = $row['phone'] ?? 'N/A';
            $row['customer_address'] = trim(($row['address'] ?? '') . ', ' . ($row['city'] ?? '') . ', ' . ($row['state'] ?? '') . ' ' . ($row['zip_code'] ?? ''));
            
            // Set default status if not available
            if (empty($row['order_status'])) {
                $row['order_status'] = 'pending';
            }
            
            // Get order items for this order
            $order_items = [];
            
            // Try different table names for order items
            $item_tables = [
                'payment_purchase_items' => "SELECT product_name, product_price, quantity, total_price FROM payment_purchase_items WHERE order_id = ?",
                'payment_order_items' => "SELECT product_name, product_price, quantity, total_price FROM payment_order_items WHERE order_id = ?",
                'order_items' => "SELECT oi.product_id, oi.quantity, oi.price as product_price, oi.total, p.product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?"
            ];
            
            foreach ($item_tables as $table => $query) {
                try {
                    $items_stmt = $conn->prepare($query);
                    if ($items_stmt) {
                        $items_stmt->bind_param("i", $row['order_id']);
                        $items_stmt->execute();
                        $items_result = $items_stmt->get_result();
                        
                        while ($item = $items_result->fetch_assoc()) {
                            // Standardize field names
                            if ($table === 'order_items') {
                                $item['total_price'] = $item['total'];
                            }
                            $order_items[] = $item;
                        }
                        $items_stmt->close();
                        
                        // If we found items, break
                        if (!empty($order_items)) {
                            break;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error fetching from table $table: " . $e->getMessage());
                    continue;
                }
            }
            
            $row['items'] = $order_items;
            $row['item_count'] = count($order_items);
            
            $orders[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    
    // Fallback: Try to get basic order data without joins
    try {
        $query = "SELECT * FROM payment_orders ORDER BY created_at DESC";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Set default values for missing data
                $row['customer_name'] = 'Unknown Customer';
                $row['customer_email'] = 'N/A';
                $row['customer_phone'] = 'N/A';
                $row['customer_address'] = 'N/A';
                $row['items'] = [];
                $row['item_count'] = 0;
                
                if (empty($row['order_status'])) {
                    $row['order_status'] = 'pending';
                }
                
                $orders[] = $row;
            }
        }
    } catch (Exception $e2) {
        error_log("Fallback query also failed: " . $e2->getMessage());
    }
}

// Get order counts for stats with null safety
$totalOrders = count($orders);

// Helper function to get order status from different possible columns
function getOrderStatus($order) {
    return $order['order_status'] ?? 'pending';
}

$pendingOrders = count(array_filter($orders, function($o) { return getOrderStatus($o) == 'pending'; }));
$confirmedOrders = count(array_filter($orders, function($o) { return getOrderStatus($o) == 'confirmed'; }));
$completedOrders = count(array_filter($orders, function($o) { return getOrderStatus($o) == 'completed'; }));
$cancelledOrders = count(array_filter($orders, function($o) { return getOrderStatus($o) == 'cancelled'; }));
?>
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/html_head.php'; ?>
    <title>Order Management - Velvet Vogue Admin</title>
    

</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <h4 class="mb-0">Order Management</h4>
            <div class="ms-auto">
                <span class="text-muted">Welcome, <?= htmlspecialchars($admin_name) ?></span>
            </div>
        </header>

        <!-- Content -->
        <div class="container-fluid p-4">            
            <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> Order status updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message) && !empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h4><?= $totalOrders ?></h4>
                        <p class="mb-0">Total Orders</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);">
                        <h4><?= $pendingOrders ?></h4>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <h4><?= $confirmedOrders ?></h4>
                        <p class="mb-0">Confirmed</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);">
                        <h4><?= $completedOrders ?></h4>
                        <p class="mb-0">Completed</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);">
                        <h4><?= $cancelledOrders ?></h4>
                        <p class="mb-0">Cancelled</p>
                    </div>
                </div>
            </div>

            <!-- Orders List -->
            <div class="content-card">
                <div class="card-header">
                    <h5 class="card-title">All Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($orders)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Order Number</th>
                                    <th>Customer Details</th>
                                    <th>Items</th>
                                    <th>Order Total</th>
                                    <th>Status</th>
                                    <th>Date & Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($order['order_number'] ?? 'VV' . str_pad($order['order_id'] ?? '0', 3, '0', STR_PAD_LEFT)) ?></strong></td>
                                    <td>
                                        <div class="customer-info">
                                            <strong><?= htmlspecialchars($order['customer_name'] ?? 'Unknown Customer') ?></strong><br>
                                            <small class="text-muted">
                                                <i class="bi bi-envelope"></i> <?= htmlspecialchars($order['customer_email']) ?><br>
                                                <?php if (!empty($order['customer_phone']) && $order['customer_phone'] !== 'N/A'): ?>
                                                <i class="bi bi-phone"></i> <?= htmlspecialchars($order['customer_phone']) ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($order['customer_address']) && $order['customer_address'] !== 'N/A'): ?>
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($order['customer_address']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($order['items']) && count($order['items']) > 0): ?>
                                            <?php foreach ($order['items'] as $index => $item): ?>
                                                <div <?= $index > 0 ? 'class="border-top pt-1 mt-1"' : '' ?>>
                                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong><br>
                                                    <small class="text-muted">
                                                        Qty: <?= $item['quantity'] ?> × $<?= number_format($item['product_price'], 2) ?>
                                                    </small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No items found</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>$<?= number_format($order['total'] ?? 0, 2) ?></strong><br>
                                            <small class="text-muted">
                                                Subtotal: $<?= number_format($order['subtotal'] ?? 0, 2) ?><br>
                                                <?php if (!empty($order['shipping']) && $order['shipping'] > 0): ?>
                                                Shipping: $<?= number_format($order['shipping'], 2) ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($order['tax']) && $order['tax'] > 0): ?>
                                                Tax: $<?= number_format($order['tax'], 2) ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($order['discount']) && $order['discount'] > 0): ?>
                                                Discount: -$<?= number_format($order['discount'], 2) ?><br>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge status-badge bg-<?= 
                                            getOrderStatus($order) === 'completed' ? 'success' : 
                                            (getOrderStatus($order) === 'confirmed' ? 'info' :
                                            (getOrderStatus($order) === 'pending' ? 'warning' : 'danger'))
                                        ?>">
                                            <?= ucfirst(getOrderStatus($order)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date('M j, Y g:i A', strtotime($order['created_at'] ?? date('Y-m-d'))) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <div class="order-actions">
                                            <?php 
                                            $current_status = getOrderStatus($order);
                                            if ($current_status === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm">
                                                    <i class="bi bi-check"></i> Confirm
                                                </button>
                                                <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-x"></i> Cancel
                                                </button>
                                            </form>
                                            <?php elseif ($current_status === 'confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                <button type="submit" name="action" value="complete" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-check-all"></i> Complete
                                                </button>
                                                <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-x"></i> Cancel
                                                </button>
                                            </form>
                                            <?php elseif ($current_status === 'cancelled'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                <button type="submit" name="action" value="pending" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-arrow-clockwise"></i> Reopen
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <span class="text-success"><i class="bi bi-check-all"></i> Completed</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <h5 class="text-muted mt-3">No Orders Found</h5>
                        <p class="text-muted">Orders will appear here once customers start placing them.</p>
                        
                        <!-- Raw Database Check -->
                        <div class="alert alert-warning text-start mt-3">
                            <strong>Database Tables Check:</strong><br>
                            <?php
                            // Check if tables exist
                            $tables_check = [];
                            try {
                                $result = $conn->query("SHOW TABLES");
                                while ($row = $result->fetch_array()) {
                                    $tables_check[] = $row[0];
                                }
                                echo "Available tables: " . implode(", ", $tables_check) . "<br>";
                                
                                // Check orders table structure
                                if (in_array('orders', $tables_check)) {
                                    $count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc();
                                    echo "Orders table has " . $count['count'] . " records<br>";
                                    
                                    // Show column structure
                                    $columns = $conn->query("SHOW COLUMNS FROM orders");
                                    $order_columns = [];
                                    while ($col = $columns->fetch_assoc()) {
                                        $order_columns[] = $col['Field'];
                                    }
                                    echo "Orders columns: " . implode(", ", $order_columns) . "<br>";
                                }
                                
                                // Check payment_orders table structure
                                if (in_array('payment_orders', $tables_check)) {
                                    $count = $conn->query("SELECT COUNT(*) as count FROM payment_orders")->fetch_assoc();
                                    echo "Payment_orders table has " . $count['count'] . " records<br>";
                                    
                                    // Show column structure
                                    $columns = $conn->query("SHOW COLUMNS FROM payment_orders");
                                    $payment_columns = [];
                                    while ($col = $columns->fetch_assoc()) {
                                        $payment_columns[] = $col['Field'];
                                    }
                                    echo "Payment_orders columns: " . implode(", ", $payment_columns) . "<br>";
                                }
                                
                            } catch (Exception $e) {
                                echo "Database error: " . $e->getMessage();
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/admin_scripts.php'; ?>
</body>
</html>