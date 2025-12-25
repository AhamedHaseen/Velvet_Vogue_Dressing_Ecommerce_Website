<?php
session_start();

// Include database connection
include "db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signIn.php?redirect=writeReview.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';

// Get order ID from URL parameter
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    header("Location: viewOrder.php");
    exit();
}

// Initialize variables
$order_products = [];
$order_info = [];
$error_message = '';
$success_message = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $review_title = trim($_POST['review_title'] ?? '');
    $review_text = trim($_POST['review_text'] ?? '');
    $recommend = isset($_POST['recommend']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if (!$product_id) $errors[] = "Invalid product selected";
    if ($rating < 1 || $rating > 5) $errors[] = "Please select a rating from 1 to 5 stars";
    if (empty($review_title)) $errors[] = "Review title is required";
    if (empty($review_text)) $errors[] = "Detailed review is required";
    if (strlen($review_text) < 10) $errors[] = "Review must be at least 10 characters long";
    
    if (empty($errors)) {
        try {
            // Check if user has already reviewed this product for this order
            $checkReview = $conn->prepare("
                SELECT review_id FROM product_reviews 
                WHERE user_id = ? AND product_id = ? AND order_id = ?
            ");
            $checkReview->bind_param("iii", $user_id, $product_id, $order_id);
            $checkReview->execute();
            
            if ($checkReview->get_result()->num_rows > 0) {
                $error_message = "You have already reviewed this product for this order.";
            } else {
                // Insert the review
                $insertReview = $conn->prepare("
                    INSERT INTO product_reviews 
                    (user_id, product_id, order_id, rating, review_title, review_text, recommend, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $insertReview->bind_param("iiisssi", 
                    $user_id, $product_id, $order_id, $rating, $review_title, $review_text, $recommend
                );
                
                if ($insertReview->execute()) {
                    // Update product's average rating
                    $updateRating = $conn->prepare("
                        UPDATE products SET 
                        rating = (SELECT AVG(rating) FROM product_reviews WHERE product_id = ?),
                        total_reviews = (SELECT COUNT(*) FROM product_reviews WHERE product_id = ?)
                        WHERE product_id = ?
                    ");
                    $updateRating->bind_param("iii", $product_id, $product_id, $product_id);
                    $updateRating->execute();
                    
                    $success_message = "Thank you! Your review has been submitted successfully.";
                } else {
                    $error_message = "Error submitting review. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = implode(", ", $errors);
    }
}

// Get order information and products
try {
    // First check payment_orders table
    $checkPaymentOrders = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'payment_orders'
    ");
    $checkPaymentOrders->execute();
    $paymentOrdersExists = $checkPaymentOrders->get_result()->fetch_assoc()['count'] > 0;
    
    if ($paymentOrdersExists) {
        // Get order from payment_orders table (without payment_billing dependency)
        $orderQuery = $conn->prepare("
            SELECT 
                po.order_id,
                po.order_number,
                po.total,
                po.created_at,
                ? as first_name,
                ? as last_name,
                ? as email
            FROM payment_orders po
            WHERE po.order_id = ?
        ");
        
        $firstName = explode(' ', $user_name)[0] ?? 'User';
        $lastName = explode(' ', $user_name)[1] ?? '';
        $orderQuery->bind_param("sssi", $firstName, $lastName, $user_email, $order_id);
        $orderQuery->execute();
        $orderResult = $orderQuery->get_result();
        
        if ($order_info = $orderResult->fetch_assoc()) {
            // Check if payment_order_items table exists
            $checkOrderItems = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'payment_order_items'
            ");
            $checkOrderItems->execute();
            $orderItemsExists = $checkOrderItems->get_result()->fetch_assoc()['count'] > 0;
            
            if ($orderItemsExists) {
                // Get order items from payment_order_items
                $itemsQuery = $conn->prepare("
                    SELECT 
                        poi.product_name,
                        poi.product_price,
                        poi.quantity,
                        p.product_id,
                        p.image_url,
                        p.description,
                        0 as user_rating,
                        NULL as review_id
                    FROM payment_order_items poi
                    LEFT JOIN products p ON poi.product_name = p.product_name
                    WHERE poi.order_id = ?
                ");
                $itemsQuery->bind_param("i", $order_id);
            } else {
                // Create sample products for demonstration if no order items table exists
                $itemsQuery = $conn->prepare("
                    SELECT 
                        p.product_name,
                        p.price as product_price,
                        1 as quantity,
                        p.product_id,
                        p.image_url,
                        p.description,
                        0 as user_rating,
                        NULL as review_id
                    FROM products p
                    WHERE p.is_active = 1
                    LIMIT 3
                ");
            }
            
            $itemsQuery->execute();
            $itemsResult = $itemsQuery->get_result();
            
            while ($item = $itemsResult->fetch_assoc()) {
                $order_products[] = $item;
            }
        }
    } else {
        // Get order from main orders table
        $orderQuery = $conn->prepare("
            SELECT 
                order_id,
                order_id as order_number,
                total,
                created_at,
                customer_name,
                customer_email
            FROM orders
            WHERE order_id = ? AND customer_id = ?
        ");
        $orderQuery->bind_param("ii", $order_id, $user_id);
        $orderQuery->execute();
        $orderResult = $orderQuery->get_result();
        
        if ($order_info = $orderResult->fetch_assoc()) {
            // Get order items
            $itemsQuery = $conn->prepare("
                SELECT 
                    oi.product_name,
                    oi.product_price,
                    oi.quantity,
                    p.product_id,
                    p.image_url,
                    p.description,
                    COALESCE(pr.rating, 0) as user_rating,
                    pr.review_id
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN product_reviews pr ON (p.product_id = pr.product_id AND pr.user_id = ? AND pr.order_id = ?)
                WHERE oi.order_id = ?
            ");
            $itemsQuery->bind_param("iii", $user_id, $order_id, $order_id);
            $itemsQuery->execute();
            $itemsResult = $itemsQuery->get_result();
            
            while ($item = $itemsResult->fetch_assoc()) {
                $order_products[] = $item;
            }
        }
    }
    
    // If no order found, create dummy order info for demonstration
    if (empty($order_info)) {
        $order_info = [
            'order_id' => $order_id,
            'order_number' => $order_id,
            'total' => 99.99,
            'created_at' => date('Y-m-d H:i:s'),
            'first_name' => explode(' ', $user_name)[0] ?? 'User',
            'last_name' => explode(' ', $user_name)[1] ?? '',
            'email' => $user_email
        ];
        
        // Get some sample products if no order products found
        if (empty($order_products)) {
            $sampleQuery = $conn->prepare("
                SELECT 
                    p.product_name,
                    p.price as product_price,
                    1 as quantity,
                    p.product_id,
                    p.image_url,
                    p.description,
                    0 as user_rating,
                    NULL as review_id
                FROM products p
                WHERE p.is_active = 1
                LIMIT 3
            ");
            $sampleQuery->execute();
            $sampleResult = $sampleQuery->get_result();
            
            while ($item = $sampleResult->fetch_assoc()) {
                $order_products[] = $item;
            }
        }
        
        $success_message = "Demo mode: Showing sample products for review. In production, this would show actual purchased products.";
    }
    
} catch (Exception $e) {
    $error_message = "Error loading order: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Review - Velvet Vogue</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Page Header -->
    <section class="bg-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="index.php" class="text-decoration-none">
                                    <i class="bx bx-home me-1"></i>Home
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="viewOrder.php" class="text-decoration-none">My Orders</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Write Review</li>
                        </ol>
                    </nav>

                    <!-- Page Title -->
                    <div class="text-center">
                        <h1 class="display-6 fw-bold text-dark mb-3">
                            <i class="bx bx-edit me-2 text-primary"></i>Write Product Reviews
                        </h1>
                        <p class="lead text-muted mb-0">
                            Share your experience with products from Order #<?php echo htmlspecialchars($order_info['order_number'] ?? 'N/A'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Review Content -->
    <section class="py-5">
        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Order Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">Order #<?php echo htmlspecialchars($order_info['order_number'] ?? 'N/A'); ?></h5>
                                    <p class="text-muted mb-0">
                                        <?php if (isset($order_info['created_at'])): ?>
                                        Placed on <?php echo date('M d, Y', strtotime($order_info['created_at'])); ?> • 
                                        <?php endif; ?>
                                        <?php if (isset($order_info['total'])): ?>
                                        Total: $<?php echo number_format($order_info['total'], 2); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <a href="viewOrder.php" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Back to Orders
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($order_products)): ?>
                <!-- No Products -->
                <div class="row justify-content-center">
                    <div class="col-md-6 text-center">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-5">
                                <i class="bx bx-package display-1 text-muted mb-4"></i>
                                <h3 class="h4 mb-3">No Products Found</h3>
                                <p class="text-muted mb-4">No products found for this order.</p>
                                <a href="viewOrder.php" class="btn btn-primary">Back to Orders</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Products List -->
                <div class="row">
                    <?php foreach ($order_products as $product): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <!-- Product Info -->
                                    <div class="row align-items-center mb-3">
                                        <div class="col-4">
                                            <?php if ($product['image_url']): ?>
                                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                                     class="img-fluid rounded">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                     style="height: 120px;">
                                                    <i class="bx bx-package text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-8">
                                            <h5 class="card-title mb-2"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                            <p class="text-muted mb-1">
                                                Qty: <?php echo $product['quantity']; ?> × $<?php echo number_format($product['product_price'], 2); ?>
                                            </p>
                                            <p class="text-muted small mb-0">
                                                <?php if (isset($order_info['created_at'])): ?>
                                                Purchased on: <?php echo date('M d, Y', strtotime($order_info['created_at'])); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if ($product['review_id']): ?>
                                        <!-- Already Reviewed -->
                                        <div class="alert alert-success">
                                            <i class="bx bx-check-circle me-2"></i>
                                            You have already reviewed this product.
                                            <div class="mt-2">
                                                <div class="star-display">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bx <?php echo $i <= $product['user_rating'] ? 'bxs-star' : 'bx-star'; ?> text-warning"></i>
                                                    <?php endfor; ?>
                                                    <span class="ms-2"><?php echo $product['user_rating']; ?>/5</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php elseif ($product['product_id']): ?>
                                        <!-- Review Form -->
                                        <form method="POST" class="review-form" data-product-id="<?php echo $product['product_id']; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            
                                            <!-- Rating -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="bx bx-star me-1 text-warning"></i>Rating <span class="text-danger">*</span>
                                                </label>
                                                <div class="star-rating" data-product="<?php echo $product['product_id']; ?>">
                                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                                        <input type="radio" id="star<?php echo $i; ?>_<?php echo $product['product_id']; ?>" 
                                                               name="rating" value="<?php echo $i; ?>" required>
                                                        <label for="star<?php echo $i; ?>_<?php echo $product['product_id']; ?>" class="star-label">
                                                            <i class="bx bx-star"></i>
                                                        </label>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>

                                            <!-- Review Title -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    Review Title <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="review_title" class="form-control" 
                                                       placeholder="Summarize your review" required>
                                            </div>

                                            <!-- Review Text -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    Detailed Review <span class="text-danger">*</span>
                                                </label>
                                                <textarea name="review_text" class="form-control" rows="4" 
                                                          placeholder="Share your experience with this product..." required></textarea>
                                                <div class="form-text">Minimum 10 characters</div>
                                            </div>

                                            <!-- Recommend -->
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input type="checkbox" name="recommend" class="form-check-input" 
                                                           id="recommend_<?php echo $product['product_id']; ?>">
                                                    <label class="form-check-label" for="recommend_<?php echo $product['product_id']; ?>">
                                                        <i class="bx bx-like me-1 text-success"></i>
                                                        I would recommend this product to others
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bx bx-send me-2"></i>Submit Review
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <!-- Product not found -->
                                        <div class="alert alert-warning">
                                            <i class="bx bx-info-circle me-2"></i>
                                            Product information not available for review.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .star-rating {
            display: inline-flex;
            flex-direction: row-reverse;
            gap: 0.25rem;
        }
        
        .star-rating input[type="radio"] {
            display: none;
        }
        
        .star-rating .star-label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
            transition: color 0.2s ease;
        }
        
        .star-rating .star-label:hover,
        .star-rating .star-label:hover ~ .star-label,
        .star-rating input[type="radio"]:checked ~ .star-label {
            color: #ffc107;
        }
        
        .star-display i {
            font-size: 1.2rem;
        }
    </style>

    <script>
        // Handle form submissions with SweetAlert
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.review-form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Validate form
                    const rating = form.querySelector('input[name="rating"]:checked');
                    const title = form.querySelector('input[name="review_title"]').value.trim();
                    const text = form.querySelector('textarea[name="review_text"]').value.trim();
                    
                    if (!rating) {
                        Swal.fire({
                            title: 'Rating Required',
                            text: 'Please select a star rating for this product.',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    if (!title) {
                        Swal.fire({
                            title: 'Title Required',
                            text: 'Please enter a review title.',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    if (text.length < 10) {
                        Swal.fire({
                            title: 'Review Too Short',
                            text: 'Please write at least 10 characters in your detailed review.',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    // Show confirmation
                    Swal.fire({
                        title: 'Submit Review?',
                        text: 'Are you sure you want to submit this review?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Submit',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Submit the form
                            form.submit();
                        }
                    });
                });
            });
        });

        // Show success message with SweetAlert if present
        <?php if ($success_message): ?>
        Swal.fire({
            title: 'Review Submitted!',
            text: '<?php echo addslashes($success_message); ?>',
            icon: 'success',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: true,
            confirmButtonText: 'Great!'
        });
        <?php endif; ?>
    </script>
</body>
</html>

  </body>
</html>

