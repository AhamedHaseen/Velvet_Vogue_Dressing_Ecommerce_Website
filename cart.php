<?php
session_start();
include "db_connection.php";

// Handle AJAX requests for cart operations
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_product_details':
            $product_ids = json_decode($_POST['product_ids'], true);
            $products = getProductsByIds($product_ids, $conn);
            echo json_encode(['success' => true, 'products' => $products]);
            exit;
            
        case 'get_single_product':
            $product_id = intval($_POST['product_id']);
            $product = getProductFromDatabase($product_id, $conn);
            echo json_encode(['success' => true, 'product' => $product]);
            exit;
            
        case 'apply_promo_code':
            $promo_code = trim($_POST['promo_code'] ?? '');
            $cart_total = floatval($_POST['cart_total'] ?? 0);
            $user_id = $_POST['user_id'] ?? session_id(); // Use session ID as user identifier
            
            $result = applyPromoCode($promo_code, $cart_total, $user_id, $conn);
            echo json_encode($result);
            exit;
            
        case 'save_cart':
            $cart_data = json_decode($_POST['cart_data'], true);
            $user_id = $_POST['user_id'] ?? session_id();
            
            $result = saveCartToDatabase($cart_data, $user_id, $conn);
            echo json_encode($result);
            exit;
            
        case 'load_cart':
            $user_id = $_POST['user_id'] ?? session_id();
            
            $result = loadCartFromDatabase($user_id, $conn);
            echo json_encode($result);
            exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Function to get product details from database
function getProductFromDatabase($product_id, $conn) {
    $stmt = $conn->prepare("SELECT p.*, c.category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.category_id 
                           WHERE p.product_id = ? AND p.is_active = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Function to get multiple products by IDs
function getProductsByIds($product_ids, $conn) {
    if (empty($product_ids)) {
        return [];
    }
    
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $sql = "SELECT p.*, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.product_id IN ($placeholders) AND p.is_active = 1 
            ORDER BY p.product_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[$row['product_id']] = $row;
    }
    
    return $products;
}

// Function to apply promo code
function applyPromoCode($promo_code, $cart_total, $user_id, $conn) {
    if (empty($promo_code)) {
        return ['success' => false, 'message' => 'Please enter a promo code'];
    }
    
    // Get promo code details
    $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE promo_code = ? AND is_active = 1");
    $stmt->bind_param("s", $promo_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid promo code'];
    }
    
    $promo = $result->fetch_assoc();
    
    // Check if promo code is within valid date range
    $current_date = date('Y-m-d H:i:s');
    if ($current_date < $promo['start_date'] || $current_date > $promo['end_date']) {
        return ['success' => false, 'message' => 'This promo code has expired'];
    }
    
    // Check minimum order amount
    if ($cart_total < $promo['minimum_order_amount']) {
        return ['success' => false, 'message' => 'Minimum order amount of $' . number_format($promo['minimum_order_amount'], 2) . ' required for this promo code'];
    }
    
    // Check usage limit
    if ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit']) {
        return ['success' => false, 'message' => 'This promo code has reached its usage limit'];
    }
    
    // Check user usage limit
    $stmt = $conn->prepare("SELECT COUNT(*) as usage_count FROM promo_code_usage WHERE promo_id = ? AND user_id = ?");
    $stmt->bind_param("is", $promo['promo_id'], $user_id);
    $stmt->execute();
    $usage_result = $stmt->get_result();
    $usage_count = $usage_result->fetch_assoc()['usage_count'];
    
    if ($usage_count >= $promo['user_limit']) {
        return ['success' => false, 'message' => 'You have already used this promo code'];
    }
    
    // Calculate discount
    $discount_amount = 0;
    if ($promo['discount_type'] === 'percentage') {
        $discount_amount = ($cart_total * $promo['discount_value']) / 100;
        
        // Apply maximum discount limit
        if ($promo['maximum_discount_amount'] && $discount_amount > $promo['maximum_discount_amount']) {
            $discount_amount = $promo['maximum_discount_amount'];
        }
    } else {
        $discount_amount = $promo['discount_value'];
    }
    
    // Ensure discount doesn't exceed cart total
    if ($discount_amount > $cart_total) {
        $discount_amount = $cart_total;
    }
    
    $final_amount = $cart_total - $discount_amount;
    
    return [
        'success' => true,
        'message' => 'Promo code applied successfully!',
        'promo_code' => $promo_code,
        'promo_name' => $promo['promo_name'],
        'description' => $promo['description'],
        'discount_type' => $promo['discount_type'],
        'discount_value' => $promo['discount_value'],
        'discount_amount' => round($discount_amount, 2),
        'original_amount' => round($cart_total, 2),
        'final_amount' => round($final_amount, 2),
        'promo_id' => $promo['promo_id']
    ];
}

// Function to save cart data to database
function saveCartToDatabase($cart_data, $user_id, $conn) {
    if (empty($cart_data) || !is_array($cart_data)) {
        return ['success' => false, 'message' => 'Invalid cart data'];
    }
    
    try {
        // Create user_cart table if it doesn't exist
        $createTableQuery = "
            CREATE TABLE IF NOT EXISTS user_cart (
                cart_id INT AUTO_INCREMENT PRIMARY KEY,
                user_session_id VARCHAR(255) NOT NULL,
                product_id INT NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                product_price DECIMAL(10,2) NOT NULL,
                product_image VARCHAR(500) DEFAULT NULL,
                quantity INT NOT NULL DEFAULT 1,
                subtotal DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_session (user_session_id),
                INDEX idx_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $conn->query($createTableQuery);
        
        // Clear existing cart for this user session
        $deleteQuery = "DELETE FROM user_cart WHERE user_session_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("s", $user_id);
        $deleteStmt->execute();
        
        // Insert new cart items
        $insertQuery = "INSERT INTO user_cart (user_session_id, product_id, product_name, product_price, product_image, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        
        $totalItems = 0;
        $totalAmount = 0;
        
        foreach ($cart_data as $item) {
            $product_id = intval($item['id'] ?? 0);
            $product_name = $item['name'] ?? 'Unknown Product';
            $product_price = floatval($item['price'] ?? 0);
            $product_image = $item['image'] ?? '';
            $quantity = intval($item['quantity'] ?? 1);
            $subtotal = $product_price * $quantity;
            
            $insertStmt->bind_param("sissdid", 
                $user_id, 
                $product_id, 
                $product_name, 
                $product_price, 
                $product_image, 
                $quantity, 
                $subtotal
            );
            
            if ($insertStmt->execute()) {
                $totalItems += $quantity;
                $totalAmount += $subtotal;
            }
        }
        
        return [
            'success' => true, 
            'message' => 'Cart saved successfully',
            'total_items' => $totalItems,
            'total_amount' => $totalAmount
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Function to load cart data from database
function loadCartFromDatabase($user_id, $conn) {
    try {
        $query = "SELECT * FROM user_cart WHERE user_session_id = ? ORDER BY created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cart_items = [];
        $total_amount = 0;
        $total_items = 0;
        
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'price' => floatval($row['product_price']),
                'image' => $row['product_image'],
                'quantity' => intval($row['quantity']),
                'addedAt' => $row['created_at']
            ];
            
            $total_amount += $row['subtotal'];
            $total_items += $row['quantity'];
        }
        
        return [
            'success' => true,
            'cart_items' => $cart_items,
            'total_amount' => $total_amount,
            'total_items' => $total_items
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shopping Cart - Velvet Vogue</title>

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

    <!-- Boxicons -->
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />

    <!-- Google Fonts -->
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="./style.css" />
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
      * {
        font-family: "Inter", sans-serif;
      }

      :root {
        --primary-color: #003049;
        --secondary-color: #8ecae6;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --dark-color: #2c3e50;
        --light-bg: #f8f9fa;
        --white: #ffffff;
      }

      body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 0;
        margin: 0;
      }
      
      /* Enhanced Footer Styling to Match Screenshot */
      footer {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1d4ed8 100%) !important;
        position: relative;
        overflow: hidden;
      }
      
      /* Professional Cart Styling */
      .cart-items-container {
        min-height: 200px;
      }
      
      .cart-item {
        transition: all 0.2s ease;
      }
      
      .cart-item:hover {
        background-color: rgba(33, 158, 188, 0.05);
        border-color: rgba(33, 158, 188, 0.2);
      }
      
      .quantity-selector {
        max-width: 120px;
        background: #fff;
      }
      
      .quantity-selector button {
        color: #6c757d;
        transition: all 0.2s ease;
      }
      
      .quantity-selector button:hover {
        color: #003049;
        background-color: rgba(0, 48, 73, 0.1);
      }
      
      .product-details h6 {
        line-height: 1.4;
      }
      
      .card-header {
        border-bottom: 2px solid #e9ecef !important;
      }
      
      #cartItemCount {
        font-size: 0.875rem;
        font-weight: 500;
      }
      
      .table-responsive {
        border-radius: 0 0 0.375rem 0.375rem;
      }
      
      footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.1);
        z-index: 1;
      }
      
      footer > * {
        position: relative;
        z-index: 2;
      }
      
      /* Enhanced Email Input Styling */
      footer .form-control {
        background: rgba(255, 255, 255, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        color: #ffffff !important;
        transition: all 0.3s ease !important;
        border-radius: 0.375rem !important;
        padding: 0.75rem 1rem !important;
        font-size: 0.9rem !important;
      }

      footer .form-control:focus {
        background: rgba(255, 255, 255, 0.25) !important;
        border-color: rgba(255, 255, 255, 0.6) !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
      }

      footer .form-control::placeholder {
        color: rgba(255, 255, 255, 0.8) !important;
        font-size: 0.9rem !important;
      }
      
      footer .input-group .btn-primary {
        background: rgba(59, 130, 246, 0.8) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
        padding: 0.75rem 1.25rem !important;
        transition: all 0.3s ease !important;
      }
      
      footer .input-group .btn-primary:hover {
        background: rgba(59, 130, 246, 1) !important;
        border-color: rgba(255, 255, 255, 0.5) !important;
        transform: translateY(-1px);
      }
      
      /* Enhanced Social Media Buttons */
      footer .btn-outline-light {
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        color: rgba(255, 255, 255, 0.9) !important;
        transition: all 0.3s ease !important;
      }
      
      footer .btn-outline-light:hover {
        background: rgba(255, 255, 255, 0.2) !important;
        border-color: rgba(255, 255, 255, 0.6) !important;
        color: #ffffff !important;
        transform: translateY(-2px);
      }
      
      /* Enhanced Link Styling */
      footer .text-light-emphasis {
        color: rgba(255, 255, 255, 0.85) !important;
        transition: color 0.3s ease !important;
      }
      
      footer .text-light-emphasis:hover {
        color: rgba(255, 255, 255, 1) !important;
      }
      
      /* Enhanced Badges */
      footer .badge {
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        font-weight: 500 !important;
      }

      .navbar {
        background: var(--white);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 15px 0;
      }

      .navbar-brand {
        font-size: 1.8rem;
        font-weight: 700;
        color: #003049 !important;
        text-decoration: none;
      }

      .cart-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 15px;
      }

      .cart-header {
        background: var(--white);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      }

      .cart-title {
        color: #003049;
        font-weight: 700;
        margin: 0;
      }

      .cart-card {
        background: var(--white);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: none;
        overflow: hidden;
        margin-bottom: 30px;
      }

      .cart-item {
        display: flex;
        align-items: center;
        padding: 25px;
        border-bottom: 1px solid #e9ecef;
        transition: all 0.3s ease;
      }

      .cart-item:last-child {
        border-bottom: none;
      }

      .cart-item:hover {
        background: rgba(0, 48, 73, 0.08);
        transform: none;
        box-shadow: none;
      }

      .product-image {
        width: 120px;
        height: 120px;
        border-radius: 15px;
        object-fit: cover;
        margin-right: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }

      .product-info {
        flex: 1;
        margin-right: 20px;
      }

      .product-name {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 8px;
      }

      .product-details {
        color: #6c757d;
        font-size: 14px;
        margin-bottom: 10px;
      }

      .product-detail-link {
        margin-top: 8px;
      }

      .product-detail-link .btn {
        font-size: 12px;
        padding: 4px 12px;
        border-radius: 15px;
        text-decoration: none;
      }

      .product-detail-link .btn:hover {
        transform: none;
        box-shadow: none;
        background-color: #003049;
        border-color: #003049;
      }

      .product-price {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
      }

      .quantity-controls {
        display: flex;
        align-items: center;
        background: var(--light-bg);
        border-radius: 25px;
        padding: 5px;
        margin: 15px 0;
      }

      .quantity-btn {
        width: 35px;
        height: 35px;
        border: none;
        background: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        color: var(--primary-color);
        font-weight: 600;
      }

      .quantity-btn:hover {
        background: #003049;
        color: white;
        transform: none;
      }

      .quantity-input {
        width: 50px;
        text-align: center;
        border: none;
        background: transparent;
        font-weight: 600;
        color: var(--dark-color);
      }

      .quantity-input:focus {
        outline: none;
      }

      .remove-item {
        color: var(--danger-color);
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 10px;
        border-radius: 50%;
      }

      .cart-summary {
        background: var(--white);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 20px;
      }

      .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #e9ecef;
      }

      .summary-row:last-child {
        border-bottom: none;
        font-weight: 700;
        font-size: 1.2rem;
        color: var(--primary-color);
        margin-top: 10px;
        padding-top: 20px;
        border-top: 2px solid var(--primary-color);
      }

      .btn-primary {
        background: #003049;
        border: none;
        border-radius: 12px;
        padding: 15px 30px;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
      }

      .btn-primary:hover {
        background: #003049 !important;
        transform: none !important;
        box-shadow: none !important;
      }

      .btn-outline-secondary {
        border: 2px solid #6c757d;
        border-radius: 12px;
        padding: 15px 30px;
        font-weight: 600;
        color: #6c757d;
        transition: all 0.3s ease;
      }

      .btn-outline-secondary:hover {
        background: #003049 !important;
        color: white !important;
        transform: none !important;
        border-color: #003049 !important;
      }

      .empty-cart {
        text-align: center;
        padding: 60px 20px;
        background: var(--white);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      }

      .empty-cart i {
        font-size: 4rem;
        color: var(--secondary-color);
        margin-bottom: 20px;
      }

      .empty-cart h3 {
        color: var(--dark-color);
        margin-bottom: 15px;
      }

      .empty-cart p {
        color: #6c757d;
        margin-bottom: 30px;
      }

      .promo-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 20px;
        margin: 20px 0;
        border: 1px solid #dee2e6;
        position: relative;
        overflow: hidden;
      }
      
      .promo-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      }

      .promo-input {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 12px 15px;
        transition: all 0.3s ease;
        font-weight: 500;
      }

      .promo-input:focus {
        border-color: #003049;
        box-shadow: 0 0 0 0.2rem rgba(0, 48, 73, 0.25);
        transform: none;
      }

      .promo-btn {
        background: linear-gradient(135deg, var(--success-color), #20c997);
        border: none;
        color: white;
        border-radius: 10px;
        padding: 12px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
      }

      .promo-btn:hover {
        background: linear-gradient(135deg, var(--success-color), #20c997) !important;
        color: white !important;
        border: none !important;
        transform: none !important;
        box-shadow: none !important;
      }

      .promo-btn:disabled {
        background: #6c757d;
        transform: none;
        box-shadow: none;
      }
      
      .popular-codes .btn-sm {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 6px;
        transition: all 0.3s ease;
      }
      
      .popular-codes .btn-sm:hover {
        transform: none;
        box-shadow: none;
        background-color: #003049;
        border-color: #003049;
        color: white;
      }
      
      #appliedPromoCode {
        border-radius: 10px;
        border: none;
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border-left: 4px solid var(--success-color);
      }
      
      /* Clear Cart Button Styling */
      .btn-clear-cart {
        background: #003049 !important;
        border: 1px solid #003049 !important;
        color: white !important;
        font-weight: 600;
        border-radius: 8px;
        padding: 8px 16px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 48, 73, 0.2);
      }
      
      .btn-clear-cart:hover {
        background: #001d2e !important;
        border-color: #001d2e !important;
        color: white !important;
        transform: none;
        box-shadow: 0 2px 8px rgba(0, 48, 73, 0.2);
      }
      
      .btn-clear-cart:active {
        background: #002a3d !important;
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(0, 48, 73, 0.3);
      }
      
      .btn-clear-cart:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 48, 73, 0.25);
      }
        padding: 12px 20px;
        font-weight: 600;
      }

      .breadcrumb {
        background: transparent;
        padding: 0;
        margin-bottom: 20px;
      }

      .breadcrumb-item a {
        color: #0d6efd;
        text-decoration: none;
      }

      .breadcrumb-item.active {
        color: var(--dark-color);
      }

      .shipping-info {
        background: rgba(40, 167, 69, 0.1);
        color: var(--success-color);
        padding: 15px;
        border-radius: 10px;
        margin: 20px 0;
        text-align: center;
      }

      .shipping-info i {
        margin-right: 8px;
      }

      .recommended-products {
        margin-top: 50px;
      }

      .product-card {
        background: var(--white);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        height: 100%;
      }

      .product-card:hover {
        transform: none;
        box-shadow: 0 6px 20px rgba(0, 48, 73, 0.2);
        border: 1px solid rgba(0, 48, 73, 0.3);
      }

      .product-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
      }

      .product-card-body {
        padding: 20px;
      }

      .product-card-title {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 10px;
      }

      .product-card-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #003049;
      }

      @media (max-width: 768px) {
        .cart-item {
          flex-direction: column;
          text-align: center;
        }

        .product-image {
          margin-right: 0;
          margin-bottom: 15px;
        }

        .product-info {
          margin-right: 0;
          margin-bottom: 15px;
        }

        .cart-container {
          padding: 20px 10px;
        }
      }
    </style>
  </head>
  <body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
      <div class="container">
        <a class="navbar-brand" href="index.php">
          Velvet Vogue
        </a>
        <div class="navbar-nav ms-auto">
          <a class="nav-link" href="index.php">
            <i class="bi bi-arrow-left me-2"></i>Continue Shopping
          </a>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="cart-container">
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Shopping Cart</li>
        </ol>
      </nav>

      <!-- Cart Header -->
      <div class="cart-header">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h2 class="cart-title">
              <i class="bi bi-bag me-3"></i>Your Shopping Cart
            </h2>
          </div>
          <div class="col-md-6 text-md-end">
            <span class="badge bg-primary fs-6 px-3 py-2 me-2">
              <i class="bi bi-cart3 me-2"></i
              ><span id="itemCount">3</span> Items
            </span>
            <button class="btn btn-outline-primary btn-sm" onclick="refreshCartFromDatabase()" title="Refresh cart from database">
              <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Cart Items -->
        <div class="col-lg-8">
          <!-- Professional Cart Header -->
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
              <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-semibold text-dark">
                  <i class="bi bi-bag-check me-2 text-primary"></i>Shopping Cart
                </h4>
                <div class="d-flex align-items-center gap-3">
                  <span class="badge bg-primary rounded-pill px-3 py-2" id="cartItemCount">0 Items</span>
                  <button class="btn btn-clear-cart btn-sm" onclick="clearEntireCart()" title="Clear all items from cart">
                    <i class="bi bi-trash3 me-1"></i>Clear Cart
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Professional Column Headers -->
            <div class="card-body p-0">
              <div class="table-responsive">
                
                <!-- Cart Items Container -->
                <div id="cartItems" class="cart-items-container">
                  <!-- Dynamic cart items will be loaded here -->
                  <div id="emptyCartMessage" class="text-center py-5" style="display: none;">
              <i class="bx bx-cart bx-lg text-muted mb-3"></i>
              <h4 class="text-muted">Your cart is empty</h4>
              <p class="text-muted">Add some products to get started!</p>
              
              <a href="featureProductView.php" class="btn btn-primary">
                <i class="bx bx-shopping-bag me-2"></i>Continue Shopping
              </a>
            </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cart Summary -->
        <div class="col-lg-4">
          <div class="cart-summary">
            <h5 class="mb-4">
              <i class="bi bi-calculator me-2"></i>Cart Summary
            </h5>

            <!-- Cart Totals -->
            <div id="cartTotals" class="mb-4">
              <div class="summary-row">
                <span>Subtotal:</span>
                <span id="cartSubtotal">$0.00</span>
              </div>
              <div class="summary-row">
                <span>Items:</span>
                <span id="cartTotalItems">0</span>
              </div>
              <div class="summary-row border-top pt-3 mt-3">
                <strong>Total:</strong>
                <strong id="cartTotal" class="text-primary">$0.00</strong>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-3">
              <button class="btn btn-primary btn-lg" onclick="proceedToCheckout()">
                <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
              </button>
              <button
                class="btn btn-clear-cart btn-lg w-100"
                onclick="clearEntireCart()"
              >
                <i class="bi bi-trash3 me-2"></i>Clear Entire Cart
              </button>
            </div>

            <!-- Security Badge -->
            <div class="text-center mt-4">
              <small
                class="text-muted d-flex align-items-center justify-content-center"
              >
                <i class="bi bi-shield-check text-success me-2"></i>
                Secure checkout guaranteed
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Footer -->
    <footer class="bg-dark text-light py-5">
      <div class="container">
        <div class="row g-4">
          <!-- Company Info Section -->
          <div class="col-lg-4 col-md-6">
            <div class="mb-4">
              <h4 class="fw-bold text-primary mb-3">
                Velvet Vogue
              </h4>
              <p class="text-light-emphasis mb-3">
                Your premier destination for trendy, expressive, and elegant
                wear. Discover fashion that speaks to your soul and elevates
                your style.
              </p>
              <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-primary rounded-pill">
                  <i class="bx bx-check me-1"></i>Free Shipping
                </span>
                <span class="badge bg-success rounded-pill">
                  <i class="bx bx-shield me-1"></i>Secure Payment
                </span>
                <span class="badge bg-info rounded-pill">
                  <i class="bx bx-refresh me-1"></i>Easy Returns
                </span>
              </div>
            </div>

            <!-- Social Media Links -->
            <div>
              <h6 class="fw-semibold mb-3">Follow Us</h6>
              <div class="d-flex gap-2">
                <a
                  href="#"
                  class="btn btn-outline-light btn-sm rounded-circle"
                  aria-label="Facebook"
                  title="Facebook"
                >
                  <i class="bx bxl-facebook"></i>
                </a>
                <a
                  href="#"
                  class="btn btn-outline-light btn-sm rounded-circle"
                  aria-label="Instagram"
                  title="Instagram"
                >
                  <i class="bx bxl-instagram"></i>
                </a>
                <a
                  href="#"
                  class="btn btn-outline-light btn-sm rounded-circle"
                  aria-label="Twitter"
                  title="Twitter"
                >
                  <i class="bx bxl-twitter"></i>
                </a>
                <a
                  href="#"
                  class="btn btn-outline-light btn-sm rounded-circle"
                  aria-label="Pinterest"
                  title="Pinterest"
                >
                  <i class="bx bxl-pinterest"></i>
                </a>
                <a
                  href="#"
                  class="btn btn-outline-light btn-sm rounded-circle"
                  aria-label="TikTok"
                  title="TikTok"
                >
                  <i class="bx bxl-tiktok"></i>
                </a>
                <a
                  href="#"
                  class="btn btn-outline-light btn-sm rounded-circle"
                  aria-label="YouTube"
                  title="YouTube"
                >
                  <i class="bx bxl-youtube"></i>
                </a>
              </div>
            </div>
          </div>

          <!-- Quick Links Section -->
          <div class="col-lg-2 col-md-6">
            <h6 class="fw-semibold mb-3">Shop</h6>
            <ul class="list-unstyled">
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>New Arrivals
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Girls Collection
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Boys Collection
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Special Offers
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Sale Items
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Gift Cards
                </a>
              </li>
            </ul>
          </div>

          <!-- Customer Service Section -->
          <div class="col-lg-2 col-md-6">
            <h6 class="fw-semibold mb-3">Support</h6>
            <ul class="list-unstyled">
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Help Center
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Size Guide
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Shipping Info
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Returns & Exchanges
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Track Your Order
                </a>
              </li>
              <li class="mb-2">
                <a href="#" class="text-light-emphasis text-decoration-none">
                  <i class="bx bx-chevron-right me-1"></i>Contact Us
                </a>
              </li>
            </ul>
          </div>

          <!-- Newsletter & Contact Section -->
          <div class="col-lg-4 col-md-6">
            <div class="mb-4">
              <h6 class="fw-semibold mb-3">
                <i class="bx bx-envelope me-2"></i>Stay Connected
              </h6>
              <p class="text-light-emphasis mb-3">
                Subscribe to get exclusive offers, style tips, and be the first
                to know about new collections!
              </p>

              <form class="mb-3">
                <div class="input-group">
                  <input
                    type="email"
                    class="form-control"
                    placeholder="Enter your email address"
                    required
                    aria-label="Email address"
                  />
                  <button
                    class="btn btn-primary"
                    type="submit"
                    aria-label="Subscribe"
                  >
                    <i class="bx bx-send"></i>
                  </button>
                </div>
              </form>

              <div class="d-flex align-items-center text-light-emphasis small">
                <i class="bx bx-shield-check me-2"></i>
                We respect your privacy. Unsubscribe anytime.
              </div>
            </div>

            <!-- Contact Information -->
            <div>
              <h6 class="fw-semibold mb-3">Get In Touch</h6>
              <div class="text-light-emphasis">
                <div class="d-flex align-items-center mb-2">
                  <i class="bx bx-phone me-2"></i>
                  <span>+1 (555) 123-4567</span>
                </div>
                <div class="d-flex align-items-center mb-2">
                  <i class="bx bx-envelope me-2"></i>
                  <span>support@velvetvogue.com</span>
                </div>
                <div class="d-flex align-items-center mb-2">
                  <i class="bx bx-map me-2"></i>
                  <span>123 Fashion Street, Style City, SC 12345</span>
                </div>
                <div class="d-flex align-items-center">
                  <i class="bx bx-time me-2"></i>
                  <span>Mon - Fri: 9:00 AM - 8:00 PM EST</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Section -->
        <hr class="border-secondary my-4" />

        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="text-light-emphasis">
              <p class="mb-1">&copy; 2025 Velvet Vogue. All rights reserved.</p>
              <div class="d-flex flex-wrap gap-3">
                <a
                  href="#"
                  class="text-light-emphasis text-decoration-none small"
                  >Privacy Policy</a
                >
                <a
                  href="#"
                  class="text-light-emphasis text-decoration-none small"
                  >Terms of Service</a
                >
                <a
                  href="#"
                  class="text-light-emphasis text-decoration-none small"
                  >Cookie Policy</a
                >
                <a
                  href="#"
                  class="text-light-emphasis text-decoration-none small"
                  >Accessibility</a
                >
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="text-md-end mt-3 mt-md-0">
              <p class="text-light-emphasis mb-2 small">
                Secure Payment Methods
              </p>
              <div class="d-flex justify-content-md-end gap-2">
                <i
                  class="bx bxl-visa fs-4 text-light-emphasis"
                  title="Visa"
                ></i>
                <i
                  class="bx bxl-mastercard fs-4 text-light-emphasis"
                  title="Mastercard"
                ></i>
                <i
                  class="bx bxl-paypal fs-4 text-light-emphasis"
                  title="PayPal"
                ></i>
                <i
                  class="bx bx-credit-card fs-4 text-light-emphasis"
                  title="Credit Cards"
                ></i>
                <i
                  class="bx bxl-apple fs-4 text-light-emphasis"
                  title="Apple Pay"
                ></i>
                <i
                  class="bx bxl-google fs-4 text-light-emphasis"
                  title="Google Pay"
                ></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Main JS for cart functionality -->
    <script src="main.js"></script>
    
    <script>
    // =================== CART PAGE FUNCTIONALITY ===================
    
    $(document).ready(function() {
        console.log('=== CART PAGE LOADING ===');
        console.log('jQuery loaded:', typeof $ !== 'undefined');
        console.log('localStorage available:', typeof localStorage !== 'undefined');
        console.log('Initial cart check:', localStorage.getItem('velvetVogueCart'));
        
        initializeCart();
    });
    
    // Initialize cart when page loads
    function initializeCart() {
        console.log('=== INITIALIZING CART ===');
        const cartData = getCart();
        console.log('Cart data from storage:', cartData);
        console.log('Cart length:', cartData.length);
        
        // Save current cart data to database
        if (cartData && cartData.length > 0) {
            saveCartToDatabase(cartData);
        }
        
        displayCartItems();
        updateCartCounts();
        
        console.log('=== CART INITIALIZATION COMPLETE ===');
    }
    
    // Main function to display cart items with images from database
    function displayCartItems() {
        const cartItems = getCart();
        const cartContainer = $('#cartItems');
        const emptyMessage = $('#emptyCartMessage');
        
        // Clear existing items
        cartContainer.find('.cart-item').remove();
        
        // Check if cart is empty
        if (!cartItems || cartItems.length === 0) {
            emptyMessage.show();
            updateCartCounts(0);
            updateCartTotals(0, 0);
            return;
        }
        
        // Hide empty message
        emptyMessage.hide();
        
        // Get all product IDs from cart
        const productIds = cartItems.map(item => item.id).filter(id => !isNaN(id));
        
        if (productIds.length === 0) {
            emptyMessage.show();
            updateCartCounts(0);
            updateCartTotals(0, 0);
            return;
        }
        
        // Show loading message
        cartContainer.html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin"></i> Loading cart items...</div>');
        
        // Fetch product details from database
        $.post('cart.php', {
            action: 'get_product_details',
            product_ids: JSON.stringify(productIds)
        })
        .done(function(response) {
            if (response.success && response.products) {
                displayCartItemsWithDbData(cartItems, response.products);
            } else {
                // Fallback to localStorage data
                displayCartItemsWithLocalData(cartItems);
            }
        })
        .fail(function(xhr, status, error) {
            // Fallback to localStorage data if AJAX fails
            displayCartItemsWithLocalData(cartItems);
        });
    }
    
    // Display cart items using database product data
    function displayCartItemsWithDbData(cartItems, dbProducts) {
        const cartContainer = $('#cartItems');
        cartContainer.empty();
        
        let totalAmount = 0;
        let totalItems = 0;
        
        cartItems.forEach((cartItem) => {
            const productId = cartItem.id; // This is the ID from localStorage (e.g., "1", "2", etc.)
            const dbProduct = dbProducts[productId];
            
            if (dbProduct) {
                // Use database product data with cart quantity
                const quantity = cartItem.quantity || 1;
                const price = parseFloat(dbProduct.price) || 0;
                const itemSubtotal = price * quantity;
                
                totalAmount += itemSubtotal;
                totalItems += quantity;
                
                // Create cart item HTML using the cartItem.id (not dbProduct.product_id)
                // This ensures consistency with localStorage IDs
                const cartItemElement = createCartItemFromDb(dbProduct, quantity, cartItem.id);
                cartContainer.append(cartItemElement);
            }
        });
        
        updateCartCounts(totalItems);
        updateCartTotals(totalAmount, totalItems);
    }
    
    // Fallback: Display cart items using localStorage data
    function displayCartItemsWithLocalData(cartItems) {
        const cartContainer = $('#cartItems');
        cartContainer.empty();
        
        let totalAmount = 0;
        let totalItems = 0;
        
        cartItems.forEach((item) => {
            const itemSubtotal = (item.price || 0) * (item.quantity || 1);
            totalAmount += itemSubtotal;
            totalItems += (item.quantity || 1);
            
            // Create cart item HTML with localStorage data
            const cartItemElement = createCartItemHTML(item, itemSubtotal);
            cartContainer.append(cartItemElement);
        });
        
        updateCartCounts(totalItems);
        updateCartTotals(totalAmount, totalItems);
    }
    
    // Create HTML for cart item using database product data
    function createCartItemFromDb(dbProduct, quantity, cartItemId) {
        // Use database image path directly as stored
        let imagePath = dbProduct.image_url || dbProduct.image_path || 'Images/default-product.jpg';
        
        // Don't modify the path - use it exactly as stored in database
        // If the database stores full paths like "Images/fe_pro_1.jpg", use directly
        // If it stores just filenames like "fe_pro_1.jpg", add Images/ prefix
        if (imagePath && !imagePath.startsWith('Images/') && !imagePath.startsWith('http') && !imagePath.includes('/')) {
            imagePath = 'Images/' + imagePath;
        }
        
        const price = parseFloat(dbProduct.price) || 0;
        
        return `
            <div class="cart-item border-bottom" data-product-id="${cartItemId}">
                <div class="row align-items-center py-4 px-4">
                    <div class="col-3">
                        <img 
                            src="${imagePath}" 
                            alt="${dbProduct.product_name}" 
                            class="img-fluid rounded shadow-sm"
                            style="width: 200px; height: 200px; object-fit: cover; border: 2px solid #e9ecef;"
                            onerror="
                                console.log('Image failed: ${imagePath}');
                                if(this.src.indexOf('default-product.jpg') === -1) {
                                    this.src='Images/default-product.jpg';
                                } else {
                                    this.style.display='none';
                                    this.parentElement.innerHTML='<div style=\\'width:200px;height:200px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;border-radius:8px;border:2px solid #dee2e6;\\'>No Image</div>';
                                }
                            "
                        />
                    </div>
                    <div class="col-4">
                        <div class="product-details">
                            <h6 class="product-name mb-1 fw-semibold text-dark">${dbProduct.product_name}</h6>
                            <p class="text-muted mb-1 small">Category: ${dbProduct.category_name || 'Uncategorized'}</p>
                            <div class="d-flex gap-3 small text-muted">
                                <span>SKU: ${dbProduct.sku || dbProduct.product_id}</span>
                                <span class="text-success">
                                    <i class="bi bi-check-circle me-1"></i>In Stock: ${dbProduct.stock_quantity || 'N/A'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-2 text-center">
                        <div class="text-center">
                            <span class="fw-bold fs-6 text-dark">$${(price * quantity).toFixed(2)}</span>
                            <br>
                            <small class="text-muted">$${price.toFixed(2)} each</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <div class="quantity-selector d-inline-flex align-items-center border rounded">
                                <button class="btn btn-sm border-0 p-2" onclick="changeQuantity('${cartItemId}', -1)" style="width: 36px;">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input 
                                    type="number" 
                                    class="form-control border-0 text-center p-0" 
                                    style="width: 50px; box-shadow: none;" 
                                    value="${quantity}" 
                                    min="1" 
                                    max="${dbProduct.stock_quantity || 999}"
                                    onchange="setQuantity('${cartItemId}', this.value)"
                                />
                                <button class="btn btn-sm border-0 p-2" onclick="changeQuantity('${cartItemId}', 1)" style="width: 36px;">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <button class="btn btn-outline-danger btn-sm ms-3" onclick="removeFromCart('${cartItemId}')" title="Remove item" style="min-width: 40px;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Create HTML for individual cart item with image (fallback for localStorage data)
    function createCartItemHTML(item, itemSubtotal) {
        // Ensure image path is correct
        let imagePath = item.image || 'Images/default-product.jpg';
        
        // Fix image path if it doesn't start with Images/
        if (imagePath && !imagePath.startsWith('Images/') && !imagePath.startsWith('http')) {
            imagePath = 'Images/' + imagePath.replace(/^\/+/, '');
        }
        
        return `
            <div class="cart-item border-bottom" data-product-id="${item.id}">
                <div class="row align-items-center py-4 px-4">
                    <div class="col-3">
                        <img 
                            src="${imagePath}" 
                            alt="${item.name || 'Product'}" 
                            class="img-fluid rounded shadow-sm"
                            style="width: 200px; height: 200px; object-fit: cover; border: 2px solid #e9ecef;"
                            onerror="this.src='Images/default-product.jpg'; console.log('Image failed to load: ${imagePath}');"
                        />
                    </div>
                    <div class="col-4">
                        <div class="product-details">
                            <h6 class="product-name mb-1 fw-semibold text-dark">${item.name || 'Unknown Product'}</h6>
                            <p class="text-muted mb-1 small">Product ID: ${item.id}</p>
                            <div class="small text-muted">
                                <span>Added: ${formatDateTime(item.addedAt)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-2 text-center">
                        <div class="text-center">
                            <span class="fw-bold fs-6 text-dark">$${itemSubtotal.toFixed(2)}</span>
                            <br>
                            <small class="text-muted">$${(item.price || 0).toFixed(2)} each</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <div class="quantity-selector d-inline-flex align-items-center border rounded">
                                <button class="btn btn-sm border-0 p-2" onclick="changeQuantity('${item.id}', -1)" style="width: 36px;">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input 
                                    type="number" 
                                    class="form-control border-0 text-center p-0" 
                                    style="width: 50px; box-shadow: none;" 
                                    value="${item.quantity || 1}" 
                                    min="1" 
                                    onchange="setQuantity('${item.id}', this.value)"
                                />
                                <button class="btn btn-sm border-0 p-2" onclick="changeQuantity('${item.id}', 1)" style="width: 36px;">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <button class="btn btn-outline-danger btn-sm ms-3" onclick="removeFromCart('${item.id}')" title="Remove item" style="min-width: 40px;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Get cart from localStorage
    function getCart() {
        try {
            const cartData = localStorage.getItem('velvetVogueCart');
            return cartData ? JSON.parse(cartData) : [];
        } catch (error) {
            console.error('Error loading cart:', error);
            return [];
        }
    }
    
    // Save cart to localStorage and database
    function saveCart(cartItems) {
        try {
            // Save to localStorage
            localStorage.setItem('velvetVogueCart', JSON.stringify(cartItems));
            localStorage.setItem('cartLastUpdated', new Date().toISOString());
            console.log('Cart saved to localStorage:', cartItems);
            
            // Also save to database
            saveCartToDatabase(cartItems);
        } catch (error) {
            console.error('Error saving cart:', error);
        }
    }
    
    // Save cart data to database via AJAX
    function saveCartToDatabase(cartItems) {
        if (!cartItems || cartItems.length === 0) {
            console.log('No cart items to save to database');
            return;
        }
        
        $.post('cart.php', {
            action: 'save_cart',
            cart_data: JSON.stringify(cartItems),
            user_id: getSessionId()
        })
        .done(function(response) {
            if (response.success) {
                console.log('Cart saved to database:', response);
            } else {
                console.error('Failed to save cart to database:', response.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Database save failed:', error);
        });
    }
    
    // Get session ID for database storage
    function getSessionId() {
        // Try to get from PHP session, fallback to generating one
        return '<?php echo session_id(); ?>' || 'guest_' + Date.now();
    }
    
    // Enhanced change quantity function with proper ID handling
    function changeQuantity(productId, change) {
        console.log('=== CHANGE QUANTITY DEBUG ===');
        console.log('Function called with productId:', productId, 'change:', change);
        console.log('Type of productId:', typeof productId);
        console.log('Type of change:', typeof change);
        
        const cart = getCart();
        console.log('Current cart:', cart);
        console.log('Cart length:', cart.length);
        
        // Convert productId to string for consistent comparison
        const searchId = String(productId);
        console.log('Searching for ID:', searchId);
        
        const itemIndex = cart.findIndex(item => {
            const itemId = String(item.id);
            console.log('Comparing:', itemId, '===', searchId, '?', itemId === searchId);
            return itemId === searchId;
        });
        
        console.log('Found item at index:', itemIndex);
        
        if (itemIndex !== -1) {
            const item = cart[itemIndex];
            const currentQuantity = parseInt(item.quantity) || 1;
            const newQuantity = currentQuantity + parseInt(change);
            
            console.log('Current quantity:', currentQuantity, 'Change:', change, 'New quantity:', newQuantity);
            
            if (newQuantity <= 0) {
                console.log('Quantity would be <= 0, showing confirmation');
                // Show confirmation before removing item
                showConfirmAlert(
                    'Remove Item', 
                    `Are you sure you want to remove "${item.name || 'this item'}" from your cart?`,
                    () => removeFromCart(productId)
                );
            } else {
                console.log('Updating quantity from', currentQuantity, 'to', newQuantity);
                // Update quantity
                cart[itemIndex].quantity = newQuantity;
                cart[itemIndex].lastUpdated = Date.now();
                saveCart(cart);
                
                console.log('Cart after update:', cart);
                console.log('Calling displayCartItems...');
                displayCartItems();
                console.log('Display completed');
                
                // Success message removed as requested
            }
        } else {
            console.error('Item not found! ProductId:', productId, 'Search ID:', searchId);
            console.error('Available cart IDs:', cart.map(item => String(item.id)));
            showAlert(`Item not found in cart (ID: ${productId})`, 'error', `<i class="bi bi-exclamation-triangle me-2"></i>`);
        }
        
        console.log('=== CHANGE QUANTITY DEBUG END ===');
    }
    
    // Enhanced set specific quantity function with proper ID handling
    function setQuantity(productId, newQuantity) {
        const quantity = parseInt(newQuantity);
        
        if (isNaN(quantity) || quantity < 0) {
            showAlert('Please enter a valid quantity', 'warning', `<i class="bi bi-exclamation-triangle me-2"></i>`);
            displayCartItems(); // Refresh to reset input
            return;
        }
        
        const cart = getCart();
        // Convert productId to string for consistent comparison
        const searchId = String(productId);
        const itemIndex = cart.findIndex(item => String(item.id) === searchId);
        
        console.log('setQuantity called with:', productId, 'quantity:', quantity);
        console.log('Looking for item with ID:', searchId);
        console.log('Found item at index:', itemIndex);
        
        if (quantity === 0) {
            const item = cart.find(item => String(item.id) === searchId);
            showConfirmAlert(
                'Remove Item', 
                `Are you sure you want to remove "${item?.name || 'this item'}" from your cart?`,
                () => removeFromCart(productId)
            );
            return;
        }
        
        if (itemIndex !== -1) {
            const oldQuantity = cart[itemIndex].quantity || 1;
            cart[itemIndex].quantity = quantity;
            cart[itemIndex].lastUpdated = Date.now();
            saveCart(cart);
            displayCartItems();
            
            // Success message removed as requested
        } else {
            console.error('Item not found! ProductId:', productId, 'Cart:', cart);
            showAlert(`Item not found in cart (ID: ${productId})`, 'error', `<i class="bi bi-exclamation-triangle me-2"></i>`);
        }
    }
    
    // Enhanced remove item function with proper ID handling
    function removeFromCart(productId) {
        const cart = getCart();
        // Convert productId to string for consistent comparison
        const searchId = String(productId);
        const item = cart.find(item => String(item.id) === searchId);
        
        console.log('removeFromCart called with:', productId);
        console.log('Looking for item with ID:', searchId);
        console.log('Found item:', item);
        
        if (!item) {
            console.error('Item not found! ProductId:', productId, 'Cart:', cart);
            showAlert(`Item not found in cart (ID: ${productId})`, 'error', `<i class="bi bi-exclamation-triangle me-2"></i>`);
            return;
        }
        
        const updatedCart = cart.filter(item => String(item.id) !== searchId);
        const itemName = item.name || 'Item';
        
        saveCart(updatedCart);
        displayCartItems();
        
        // Show success message with undo option
        showAlert(
            `${itemName} removed from cart`, 
            'success',
            `<i class="bi bi-trash3 me-2"></i>`,
            5000, // Show for 5 seconds
            true, // Show undo button
            () => {
                // Undo function - add item back
                const currentCart = getCart();
                currentCart.push(item);
                saveCart(currentCart);
                displayCartItems();
                showAlert(
                    `${itemName} restored to cart`, 
                    'info',
                    `<i class="bi bi-arrow-counterclockwise me-2"></i>`
                );
            }
        );
    }
    
    // Enhanced clear entire cart function
    function clearEntireCart() {
        console.log('clearEntireCart called');
        const cart = getCart();
        const itemCount = cart.reduce((total, item) => total + (item.quantity || 1), 0);
        
        console.log('Cart item count:', itemCount);
        
        if (itemCount === 0) {
            showAlert('Your cart is already empty', 'info', `<i class="bi bi-info-circle me-2"></i>`);
            return;
        }
        
        showConfirmAlert(
            'Clear Entire Cart',
            `Are you sure you want to remove all ${itemCount} item${itemCount > 1 ? 's' : ''} from your cart? This action cannot be undone.`,
            () => {
                console.log('User confirmed - clearing cart');
                
                // Clear cart data
                localStorage.removeItem('velvetVogueCart');
                localStorage.removeItem('cartLastUpdated');
                
                // Clear applied promo code if any
                if (window.appliedPromoCode) {
                    window.appliedPromoCode = null;
                }
                
                // Refresh display
                displayCartItems();
                
                // Show success message
                showAlert(
                    `Cart cleared successfully! ${itemCount} item${itemCount > 1 ? 's' : ''} removed`, 
                    'success',
                    `<i class="bi bi-check-circle me-2"></i>`,
                    4000
                );
                
                console.log('Cart cleared successfully');
            },
            () => {
                console.log('User cancelled cart clear');
            },
            'Clear All Items',
            'btn-clear-cart'
        );
    }
    
    // Update cart counts in header/badges
    function updateCartCounts(count = null) {
        if (count === null) {
            const cart = getCart();
            count = cart.reduce((total, item) => total + (item.quantity || 1), 0);
        }
        
        $('#itemCount').text(count);
        $('.cart-count').text(count);
        $('.badge-cart-count').text(count);
        $('#cartItemCount').text(count + (count === 1 ? ' Item' : ' Items'));
    }
    
    // Update cart totals in summary section
    function updateCartTotals(totalAmount = 0, totalItems = 0) {
        $('#cartSubtotal').text('$' + totalAmount.toFixed(2));
        $('#cartTotalItems').text(totalItems + (totalItems === 1 ? ' item' : ' items'));
        $('#cartTotal').text('$' + totalAmount.toFixed(2));
        
        // Show or hide cart totals section
        if (totalItems > 0) {
            $('#cartTotals').show();
        } else {
            $('#cartTotals').hide();
        }
    }
    
    // Format date and time
    function formatDateTime(timestamp) {
        if (!timestamp) return 'Unknown';
        
        try {
            return new Date(timestamp).toLocaleDateString('en-IN', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'Unknown';
        }
    }
    
    // Enhanced alert system with Bootstrap styling and undo functionality
    function showAlert(message, type = 'info', icon = '', duration = 4000, showUndo = false, undoCallback = null) {
        const alertTypes = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };
        
        const alertClass = alertTypes[type] || 'alert-info';
        const alertId = 'alert-' + Date.now();
        
        const undoButton = showUndo && undoCallback ? 
            `<button type="button" class="btn btn-sm btn-outline-${type === 'success' ? 'dark' : 'light'} me-2" onclick="undoAction_${alertId}()">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Undo
            </button>` : '';
        
        const alertHTML = `
            <div id="${alertId}" class="alert ${alertClass} alert-dismissible fade show position-fixed shadow-lg" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 350px; max-width: 450px;">
                <div class="d-flex align-items-center">
                    ${icon}
                    <div class="flex-grow-1">
                        <strong>${type.charAt(0).toUpperCase() + type.slice(1)}:</strong> ${message}
                    </div>
                </div>
                ${undoButton ? `<div class="mt-2 d-flex justify-content-end">${undoButton}</div>` : ''}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Add undo function to window if needed
        if (showUndo && undoCallback) {
            window[`undoAction_${alertId}`] = function() {
                undoCallback();
                $(`#${alertId}`).alert('close');
                // Clean up the global function
                delete window[`undoAction_${alertId}`];
            };
        }
        
        $('body').append(alertHTML);
        
        // Auto remove after specified duration
        setTimeout(() => {
            const alertElement = $(`#${alertId}`);
            if (alertElement.length) {
                alertElement.alert('close');
                // Clean up undo function if it exists
                if (window[`undoAction_${alertId}`]) {
                    delete window[`undoAction_${alertId}`];
                }
            }
        }, duration);
    }
    
    // Confirmation alert with custom Bootstrap modal
    function showConfirmAlert(title, message, confirmCallback, cancelCallback = null, confirmText = 'Confirm', confirmButtonClass = 'btn-danger') {
        const modalId = 'confirmModal-' + Date.now();
        
        const modalHTML = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${modalId}Label">
                                <i class="bi bi-question-circle text-warning me-2"></i>${title}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            ${message}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </button>
                            <button type="button" class="btn ${confirmButtonClass}" id="${modalId}Confirm">
                                <i class="bi bi-check-circle me-1"></i>${confirmText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
        
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        
        // Handle confirm button
        $(`#${modalId}Confirm`).on('click', function() {
            if (confirmCallback) confirmCallback();
            modal.hide();
        });
        
        // Handle cancel (optional callback)
        $(`#${modalId}`).on('hidden.bs.modal', function() {
            if (cancelCallback) cancelCallback();
            $(this).remove(); // Clean up modal from DOM
        });
        
        modal.show();
    }
    
    // Make functions available globally
    window.changeQuantity = changeQuantity;
    window.setQuantity = setQuantity;
    window.removeFromCart = removeFromCart;
    window.clearEntireCart = clearEntireCart;
    window.displayCartItems = displayCartItems;
    window.refreshCartFromDatabase = function() {
        console.log('Refreshing cart from database...');
        displayCartItems();
    };
    
    // Debug function to test cart functionality
    window.debugCart = function() {
        console.log('=== CART DEBUG INFO ===');
        const cart = getCart();
        console.log('Cart contents:', cart);
        console.log('Cart length:', cart.length);
        if (cart.length > 0) {
            console.log('First item:', cart[0]);
            console.log('Available for testing: changeQuantity("' + cart[0].id + '", 1)');
            return cart[0];
        } else {
            console.log('Cart is empty - add some items first');
            return null;
        }
    };
    
    // Test function to manually save cart to database
    window.testDatabaseSave = function() {
        const cart = getCart();
        console.log('Testing database save with cart:', cart);
        
        if (cart.length === 0) {
            console.log('Cart is empty - add some items first');
            return;
        }
        
        saveCartToDatabase(cart);
        console.log('Database save initiated - check network tab for response');
    };
    
    // Test function to load cart from database
    window.testDatabaseLoad = function() {
        console.log('Testing database load...');
        
        $.post('cart.php', {
            action: 'load_cart',
            user_id: getSessionId()
        })
        .done(function(response) {
            console.log('Database load response:', response);
            if (response.success && response.cart_items.length > 0) {
                console.log('Found cart items in database:', response.cart_items);
                // Optionally merge with localStorage
                localStorage.setItem('velvetVogueCart', JSON.stringify(response.cart_items));
                displayCartItems();
            } else {
                console.log('No cart items found in database');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Database load failed:', error);
        });
    };
    
    // Test function to add a sample item for testing
    window.addTestItem = function() {
        const testItem = {
            id: '999',
            name: 'Test Product',
            price: 50.00,
            image: 'Images/default-product.jpg',
            quantity: 1,
            addedAt: Date.now()
        };
        
        const cart = getCart();
        cart.push(testItem);
        saveCart(cart);
        displayCartItems();
        
        console.log('Test item added with ID: 999');
        console.log('Try: changeQuantity("999", 1)');
        return testItem;
    };
    
    // Simplified promo code functions (removed due to order summary removal)
    window.applyPromoCode = function() {
        showAlert('Promo codes will be available at checkout', 'info', '<i class="bi bi-info-circle me-2"></i>');
    };
    
    window.applyQuickPromo = function(promoCode) {
        showAlert('Promo codes will be available at checkout', 'info', '<i class="bi bi-info-circle me-2"></i>');
    };
    
    window.removePromoCode = function() {
        showAlert('Promo codes will be available at checkout', 'info', '<i class="bi bi-info-circle me-2"></i>');
    };
    
    // Debug test functions
    window.testAddDirectly = function() {
        console.log('=== TESTING DIRECT ADD ===');
        const testItem = {
            id: '1',
            name: 'Test Product',
            price: 999.99,
            image: 'Images/default-product.jpg',
            quantity: 1,
            addedAt: Date.now()
        };
        
        console.log('Adding test item:', testItem);
        
        // Add directly to localStorage
        const currentCart = getCart();
        currentCart.push(testItem);
        saveCart(currentCart);
        
        console.log('Cart after adding:', getCart());
        
        // Refresh display
        displayCartItems();
        alert('Test item added! Check console for details.');
    };
    
    window.checkStorage = function() {
        console.log('=== STORAGE CHECK ===');
        const rawData = localStorage.getItem('velvetVogueCart');
        const parsedData = getCart();
        
        console.log('Raw localStorage data:', rawData);
        console.log('Parsed cart data:', parsedData);
        console.log('Cart length:', parsedData.length);
        
        alert('Storage data: ' + (rawData || 'EMPTY') + '\nParsed length: ' + parsedData.length);
    };
    
    window.clearStorage = function() {
        localStorage.removeItem('velvetVogueCart');
        localStorage.removeItem('cartLastUpdated');
        displayCartItems();
        console.log('Storage cleared');
        alert('Storage cleared!');
    };
    
    // Proceed to checkout function
    window.proceedToCheckout = function() {
        const cart = getCart();
        
        if (cart.length === 0) {
            showAlert('Your cart is empty', 'warning', '<i class="bi bi-cart-x me-2"></i>');
            return;
        }
        
        // Calculate total
        let totalAmount = 0;
        cart.forEach((item) => {
            totalAmount += (item.price || 0) * (item.quantity || 1);
        });
        
        if (totalAmount <= 0) {
            showAlert('Invalid cart total', 'error', '<i class="bi bi-exclamation-triangle me-2"></i>');
            return;
        }
        
        // Show loading notification
        showAlert(
            'Redirecting to checkout...', 
            'info', 
            '<i class="bi bi-arrow-right-circle me-2"></i>',
            2000
        );
        
        // Prepare cart data for the payment page
        const cartProductIds = cart.map(item => item.id).join(',');
        const cartQuantities = cart.map(item => `${item.id}:${item.quantity}`).join(',');
        
        // Store detailed cart data in sessionStorage for the payment page
        sessionStorage.setItem('checkoutCart', JSON.stringify(cart));
        
        // Redirect to payment page with cart information
        setTimeout(() => {
            window.location.href = `./ProPayment.php?from_cart=true&cart_items=${cartProductIds}&quantities=${cartQuantities}&total=${totalAmount.toFixed(2)}`;
        }, 1000);
    };
    
    </script>
  </body>
</html>

