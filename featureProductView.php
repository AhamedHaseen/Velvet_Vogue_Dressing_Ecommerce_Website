<?php
session_start();
// Include database connection
include "db_connection.php";

// Fetch featured products from database in order
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.is_featured = 1
        ORDER BY p.product_id ASC";
$result = $conn->query($sql);
$featured_products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Products - Velvet Vogue</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Text:wght@400;600;700&family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="./style.css">
    
    <style>
        .product-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .product-content {
            padding: 15px;
        }
        .product-title {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        .product-price .current-price {
            color: #e74c3c;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .product-price .original-price {
            color: #888;
            text-decoration: line-through;
            font-size: 0.9rem;
            margin-left: 8px;
        }
        .add-to-cart-btn, .add-to-cart-btn-overlay {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
            width: 100%;
        }
        .add-to-cart-btn:hover, .add-to-cart-btn-overlay:hover {
            background-color: #0056b3;
            color: white;
        }
        .add-to-cart-btn.success, .add-to-cart-btn-overlay.success {
            background-color: #28a745 !important;
            color: white !important;
        }
        
        /* Cart counter styles - Clear and Visible */
        .cart-counter {
            font-size: 12px !important;
            font-weight: bold !important;
            min-width: 22px !important;
            height: 22px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            line-height: 1 !important;
            border-radius: 45% !important;
            background: #dc3545 !important;
            color: white !important;
            /* border: 2px solid white !important; */
            /* box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important; */
        }
        
        .cart-pulse {
            animation: simplePulse 0.6s ease !important;
        }
        
        @keyframes simplePulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        /* Button feedback */
        .add-to-cart-btn:active, 
        .add-to-cart-btn-overlay:active {
            transform: scale(0.95) !important;
            background-color: #28a745 !important;
        }
        .product-buttons-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .product-image-container {
            position: relative;
        }
        .product-badges {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
        }
        .badge {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .trending-badge { background-color: #ff6b6b; }
        .sale-badge { background-color: #4ecdc4; }
        .new-badge { background-color: #45b7d1; }
        .bestseller-badge { background-color: #96ceb4; }
        .premium-badge { background-color: #feca57; }
        .flash-sale-badge { background-color: #ff9ff3; }
        
        .search-container {
            max-width: 300px;
        }
        
        /* Filter Styles */
        .filter-card {
            background: #f0ead2;
            border: 1px solid #e0e0e0;
        }
        
        .filter-card .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .filter-card .form-select {
            border: 1px solid #ddd;
            background-color: white;
            color: #333;
            border-radius: 8px;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }
        
        .filter-card .form-select:focus {
            border-color: #333;
            box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.1);
            background-color: white;
        }
        
        .filter-card .btn-primary {
            background-color: #333;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .filter-card .btn-primary:hover {
            background-color: #000;
            border-color: #000;
            transform: translateY(-1px);
        }
        
        .filter-card .btn-outline-secondary {
            border: 1px solid #666;
            color: #666;
            background-color: white;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .filter-card .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #333;
            color: #333;
        }
        
        .filter-card .badge {
            background-color: #333;
            color: white;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 20px;
        }
        
        .product-item {
            transition: all 0.3s ease;
        }
        
        .product-item.filtered-out {
            opacity: 0;
            transform: scale(0.8);
            pointer-events: none;
        }
        
        /* Cart Animation Styles */
        .cart-count-update {
            animation: cart-pulse 0.3s ease-in-out;
            background-color: #dc3545 !important;
        }
        
        @keyframes cart-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .cart-pulse {
            animation: cart-bounce 0.6s ease-in-out;
        }
        
        @keyframes cart-bounce {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.1); }
            50% { transform: scale(1.2); }
            75% { transform: scale(1.1); }
        }
        
        .floating-cart-item {
            animation: float-to-cart 0.8s ease-out forwards;
        }
        
        @keyframes float-to-cart {
            0% {
                opacity: 1;
                transform: scale(1);
            }
            100% {
                opacity: 0;
                transform: scale(0.3);
            }
        }
        
        /* Add to Cart Button Success State */
        .btn-success {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: white !important;
        }
        
        @media (max-width: 768px) {
            .product-image {
                height: 200px;
            }
            .product-content {
                padding: 10px;
            }
            .filter-card .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        
        /* Star Rating Styles - REMOVED ALL STYLES */
        
        .review-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-stars {
            color: #ffc107;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <!-- Simple Navigation Bar -->
    <nav
      class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm"
    >
      <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold text-primary" href="./index.php">
          Velvet Vogue
        </a>

        <!-- Mobile Toggle -->
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav mx-auto">
            <li class="nav-item me-2">
              <a class="nav-link" href="./index.php">
                <i class="bx bx-home-alt me-1"></i>Home
              </a>
            </li>
            <li class="nav-item me-2">
              <a class="nav-link active" href="./featureProductView.php">
                <i class="bx bx-store me-1"></i>Products
              </a>
            </li>
            <li class="nav-item me-2">
              <a class="nav-link" href="./about.php">
                <i class="bx bx-info-circle me-1"></i>About
              </a>
            </li>
            <li class="nav-item me-2">
              <a class="nav-link" href="./contact.php">
                <i class="bx bx-phone me-1"></i>Contact
              </a>
            </li>
          </ul>

          <!-- Right Side Items -->
          <div class="d-flex align-items-center ms-3">
            <!-- Search -->
            <form class="d-flex me-3" role="search">
              <div class="input-group input-group-sm search-container">
                <input
                  class="form-control search-input"
                  type="search"
                  placeholder="Search products..."
                  aria-label="Search"
                  id="searchInput"
                />
                <button
                  class="btn btn-outline-secondary search-btn"
                  type="submit"
                  aria-label="Search"
                >
                  <i class="bx bx-search"></i>
                </button>
              </div>
            </form>

            <!-- Favorites -->
            <a
              href="#"
              class="btn btn-outline-secondary position-relative me-2"
              title="Favorites"
            >
              <i class="bx bx-heart"></i>
              <span
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark wishlist-counter"
                style="font-size: 0.65rem"
              >
                0
              </span>
            </a>

            <!-- Cart -->
            <a
              href="./cart.php"
              class="btn btn-outline-primary position-relative me-2"
              title="Cart"
            >
              <i class="bx bx-cart"></i>
              <span
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-counter"
              >
                0
              </span>
            </a>

            <!-- Profile Dropdown -->
            <div class="dropdown">
              <button
                class="btn btn-outline-secondary dropdown-toggle"
                type="button"
                id="profileDropdown"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                title="Profile"
              >
                <i class="bx bx-user"></i>
              </button>
              <ul
                class="dropdown-menu dropdown-menu-end"
                aria-labelledby="profileDropdown"
              >
                <li>
                  <a class="dropdown-item" href="./accountInformation.php">
                    <i class="bx bx-user me-2"></i>Account Information
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="./setting.php">
                    <i class="bx bx-cog me-2"></i>Settings
                  </a>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                  <a class="dropdown-item text-danger" href="./signIn.php">
                    <i class="bx bx-log-out me-2"></i>Logout
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <section class="py-4 py-md-5">
        <div class="container-fluid container-xl">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="fw-bold text-dark mb-0">Featured Products</h2>
                    </div>

                    <!-- Filter Controls -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm filter-card">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- Gender Filter -->
                                        <div class="col-lg-3 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bx bx-user me-1"></i>Gender
                                            </label>
                                            <select class="form-select" id="genderFilter">
                                                <option value="">All Products</option>
                                                <option value="girls">Girls</option>
                                                <option value="boys">Boys</option>
                                            </select>
                                        </div>

                                        <!-- Age Range Filter -->
                                        <div class="col-lg-3 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bx bx-calendar me-1"></i>Age Range
                                            </label>
                                            <select class="form-select" id="ageFilter">
                                                <option value="">All Ages</option>
                                                <option value="kids">Kids (5-12)</option>
                                                <option value="teens">Teens (13-19)</option>
                                                <option value="adults">Adults (20+)</option>
                                            </select>
                                        </div>

                                        <!-- Size Filter -->
                                        <div class="col-lg-3 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bx bx-resize me-1"></i>Size
                                            </label>
                                            <select class="form-select" id="sizeFilter">
                                                <option value="">All Sizes</option>
                                                <option value="XS">XS</option>
                                                <option value="S">S</option>
                                                <option value="M">M</option>
                                                <option value="L">L</option>
                                                <option value="XL">XL</option>
                                                <option value="XXL">XXL</option>
                                                <option value="One Size">One Size</option>
                                            </select>
                                        </div>

                                        <!-- Price Range Filter -->
                                        <div class="col-lg-3 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bx bx-dollar me-1"></i>Price Range
                                            </label>
                                            <select class="form-select" id="priceFilter">
                                                <option value="">All Prices</option>
                                                <option value="0-50">$0 - $50</option>
                                                <option value="50-100">$50 - $100</option>
                                                <option value="100-200">$100 - $200</option>
                                                <option value="200+">$200+</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Filter Actions -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button class="btn btn-primary" id="applyFilters">
                                                    <i class="bx bx-filter-alt me-1"></i>Apply Filters
                                                </button>
                                                <button class="btn btn-outline-secondary" id="clearFilters">
                                                    <i class="bx bx-refresh me-1"></i>Clear All
                                                </button>
                                                <div class="ms-auto">
                                                    <span class="badge bg-info" id="productCount">
                                                        <?php echo count($featured_products); ?> Products
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="row g-3 g-md-4" id="featureProductGrid">
                        <?php if (!empty($featured_products)): ?>
                            <?php foreach ($featured_products as $index => $product): ?>
                                <?php
                                // Calculate discount percentage
                                $discount_percentage = 0;
                                if ($product['original_price'] && $product['original_price'] > $product['price']) {
                                    $discount_percentage = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                                }
                                
                                // Determine category for data-category attribute
                                $data_category = '';
                                if ($product['gender'] == 'female') {
                                    $data_category = $discount_percentage > 0 ? 'girls offers' : 'girls';
                                } else {
                                    $data_category = $discount_percentage > 0 ? 'boys offers' : 'boys';
                                }
                                
                                // Generate star rating
                                $rating = floatval($product['rating']);
                                $full_stars = floor($rating);
                                $has_half_star = ($rating - $full_stars) >= 0.5;
                                $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
                                
                                // Format price in Indian Rupees
                                $formatted_price = '$' . number_format($product['price'], 2);
                                $formatted_original_price = $product['original_price'] ? '$' . number_format($product['original_price'], 2) : '';
                                
                                // Determine age range based on product type
                                $age_range = 'adults';
                                if (stripos($product['product_name'], 'kids') !== false || stripos($product['product_name'], 'children') !== false) {
                                    $age_range = 'kids';
                                } elseif (stripos($product['product_name'], 'teen') !== false || stripos($product['product_name'], 'youth') !== false) {
                                    $age_range = 'teens';
                                }
                                
                                // Get sizes array for filtering
                                $sizes_array = json_decode($product['sizes'], true) ?: [];
                                $sizes_string = implode(',', $sizes_array);
                                ?>
                                
                                <div class="col-6 col-sm-6 col-md-4 col-lg-4 col-xl-3 product-item" 
                                     data-category="<?php echo htmlspecialchars($data_category); ?>"
                                     data-gender="<?php echo $product['gender'] == 'female' ? 'girls' : 'boys'; ?>"
                                     data-age="<?php echo $age_range; ?>"
                                     data-price="<?php echo $product['price']; ?>"
                                     data-sizes="<?php echo htmlspecialchars($sizes_string); ?>"
                                     data-rating="<?php echo $product['rating']; ?>">
                                    <div class="product-card">
                                        <div class="product-image-container">
                                            <?php 
                                            $image_url = $product['image_url'] ?? 'Images/default-product.jpg';
                                            // Clean up the image path - remove ./ if present
                                            $image_url = str_replace('./', '', $image_url);
                                            ?>
                                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                                 class="product-image"
                                                 onerror="this.src='Images/default-product.jpg'; this.onerror=null;" />
                                            <div class="product-badges">
                                                <?php if ($index < 3): ?>
                                                    <span class="badge new-badge">New</span>
                                                <?php elseif ($product['rating'] >= 4.5): ?>
                                                    <span class="badge bestseller-badge">Best Seller</span>
                                                <?php elseif ($product['rating'] >= 4.2): ?>
                                                    <span class="badge trending-badge">Trending</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($discount_percentage > 0): ?>
                                                    <?php if ($discount_percentage >= 40): ?>
                                                        <span class="badge flash-sale-badge">Flash Sale</span>
                                                    <?php elseif ($discount_percentage >= 30): ?>
                                                        <span class="badge offer-badge"><?php echo $discount_percentage; ?>% Off</span>
                                                    <?php else: ?>
                                                        <span class="badge sale-badge"><?php echo $discount_percentage; ?>% Off</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <?php if ($product['price'] >= 4000): ?>
                                                    <span class="badge premium-badge">Premium</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-buttons-overlay">
                                                <button class="btn btn-primary add-to-cart-btn-overlay" 
                                                        data-product-id="<?php echo $product['product_id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                        data-product-price="<?php echo $product['price']; ?>"
                                                        data-product-image="<?php echo htmlspecialchars($product['image_url']); ?>">
                                                    <i class="bx bx-cart-add"></i>
                                                </button>
                                                <button class="btn btn-outline-light favorite-btn-overlay" data-product-id="<?php echo $product['product_id']; ?>">
                                                    <i class="bx bx-heart"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="product-content">
                                            <h5 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                            <div class="product-price">
                                                <span class="current-price"><?php echo $formatted_price; ?></span>
                                                <?php if ($formatted_original_price): ?>
                                                    <span class="original-price"><?php echo $formatted_original_price; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-buttons">
                                                <button class="btn btn-primary btn-block add-to-cart-btn mb-2" 
                                                        data-product-id="<?php echo $product['product_id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                        data-product-price="<?php echo $product['price']; ?>"
                                                        data-product-image="<?php echo htmlspecialchars($image_url); ?>">
                                                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                                                </button>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-success btn-sm w-100 write-review-btn" 
                                                                data-product-id="<?php echo $product['product_id']; ?>"
                                                                data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                                data-bs-toggle="modal" data-bs-target="#writeReviewModal">
                                                            <i class="bx bx-edit me-1"></i> Write Review
                                                        </button>
                                                    </div>
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-info btn-sm w-100 view-reviews-btn" 
                                                                data-product-id="<?php echo $product['product_id']; ?>"
                                                                data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                                data-bs-toggle="modal" data-bs-target="#viewReviewsModal">
                                                            <i class="bx bx-comment-detail me-1"></i> View Reviews
                                                        </button>
                                                    </div>
                                                </div>
                                                <button class="btn btn-outline-danger btn-sm mt-2 favorite-btn w-100" data-product-id="<?php echo $product['product_id']; ?>">
                                                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="bx bx-package fs-1 text-muted"></i>
                                    <h4 class="text-muted mt-3">No featured products available</h4>
                                    <p class="text-muted">Please check back later for new arrivals.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Write Review Modal -->
    <div class="modal fade" id="writeReviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Write Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reviewForm">
                        <input type="hidden" id="reviewProductId" name="product_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Product:</label>
                            <p id="reviewProductName" class="fw-bold text-primary"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customerName" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="customerName" name="customer_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customerEmail" class="form-label">Your Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="customerEmail" name="customer_email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ratingSelect" class="form-label">Rating <span class="text-danger">*</span></label>
                            <select class="form-select" id="ratingSelect" name="rating" required>
                                <option value="">Select Rating</option>
                                <option value="1">One Star</option>
                                <option value="2">Two Star</option>
                                <option value="3">Three Star</option>
                                <option value="4">Four Star</option>
                                <option value="5">Five Star</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reviewText" class="form-label">Your Review</label>
                            <textarea class="form-control" id="reviewText" name="review_text" rows="4" placeholder="Share your experience with this product..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">Submit Review</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Reviews Modal -->
    <div class="modal fade" id="viewReviewsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Reviews</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="reviewsContainer">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Main E-commerce JavaScript -->
    <script src="main.js"></script> <!-- Re-enabled for cart functionality -->
    
    <!-- Page Specific JavaScript -->
    <script>
        $(document).ready(function() {
            console.log('Page loaded - Setting up cart functionality');
            
            // Clear cart count on page load (reset to zero)
            clearCartOnRefresh();
            
            // Initialize simple cart functionality 
            initializeSimpleCart();
            
            // Initialize filter functionality
            initializeFilters();
            
            // Initialize search functionality  
            initializeSearch();
            
            // Update initial product count
            updateProductCount();
        });
        
        // Clear cart count on page refresh
        function clearCartOnRefresh() {
            console.log('Clearing cart count on page refresh');
            
            // Set cart counter to 0
            $('.cart-counter').text('0');
            $('.cart-counter').show();
            
            // Clear any stored cart data (optional)
            localStorage.removeItem('velvetVogueCart');
            localStorage.removeItem('cartCount');
            
            console.log('Cart cleared - counter set to 0');
        }
        
        // Simple standalone cart functionality
        function initializeSimpleCart() {
            // Initialize session cart counter
            window.sessionCartCount = 0;
            
            // Handle add to cart button clicks
            $(document).off('click', '.add-to-cart-btn, .add-to-cart-btn-overlay');
            $(document).on('click', '.add-to-cart-btn, .add-to-cart-btn-overlay', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('Add to cart button clicked');
                
                // Get product details from button data attributes
                const productId = $(this).data('product-id');
                const productName = $(this).data('product-name') || 'Product';
                const productPrice = $(this).data('product-price') || '0';
                
                if (!productId) {
                    console.error('No product ID found');
                    return false;
                }
                
                // Also call main.js function if available
                if (typeof handleAddToCart === 'function') {
                    handleAddToCart($(this));
                }
                
                // Increment session cart count
                window.sessionCartCount++;
                
                // Update cart counter display
                updateCartCounter();
                
                console.log(`Product added: ${productName} - $${productPrice}. New count:`, window.sessionCartCount);
                
                return false;
            });
        }
        
        // Update cart counter display
        function updateCartCounter() {
            const count = window.sessionCartCount || 0;
            $('.cart-counter').text(count);
            $('.cart-counter').show();
            
            // Add pulse animation
            $('.cart-counter').addClass('cart-pulse');
            setTimeout(() => $('.cart-counter').removeClass('cart-pulse'), 600);
            
            console.log('Cart counter updated to:', count);
        }
        
        // Show add to cart success animation
        function showAddToCartSuccess(button) {
            const originalText = button.html();
            const originalClass = button.attr('class');
            
            // Change button appearance
            button.html('<i class="bx bx-check"></i> Added!');
            button.removeClass('btn-primary').addClass('btn-success');
            
            // Revert after 2 seconds
            setTimeout(function() {
                button.html(originalText);
                button.attr('class', originalClass);
            }, 2000);
        }
        
        // =================== FILTER FUNCTIONALITY ===================
        function initializeFilters() {
            // Filter change event listeners
            $('#genderFilter, #ageFilter, #sizeFilter, #priceFilter').on('change', function() {
                applyFilters();
            });
            
            // Clear filters button
            $('.clear-filters').on('click', function() {
                clearAllFilters();
            });
        }
        
        // Apply all filters
        function applyFilters() {
            const filters = {
                gender: $('#genderFilter').val().toLowerCase(),
                age: $('#ageFilter').val().toLowerCase(), 
                size: $('#sizeFilter').val().toLowerCase(),
                price: $('#priceFilter').val(),
                search: $('#searchInput').val().toLowerCase()
            };
            
            const productItems = $('.product-item');
            let visibleCount = 0;
            
            productItems.each(function() {
                const $item = $(this);
                const productCard = $item.find('.product-card');
                const productName = $item.find('.product-title').text().toLowerCase();
                const productPriceText = $item.find('.current-price').text();
                const productPrice = parseFloat(productPriceText.replace(/[^\d.]/g, ''));
                
                // Get product attributes (can be enhanced with data attributes)
                const productGender = inferGenderFromName(productName);
                const productAge = inferAgeFromName(productName);
                const productSize = $item.data('size') || '';
                
                let isVisible = true;
                
                // Apply gender filter
                if (filters.gender && !productGender.includes(filters.gender)) {
                    isVisible = false;
                }
                
                // Apply age filter  
                if (filters.age && !productAge.includes(filters.age)) {
                    isVisible = false;
                }
                
                // Apply size filter
                if (filters.size && !productSize.includes(filters.size)) {
                    isVisible = false;
                }
                
                // Apply price filter
                if (filters.price) {
                    const [minPrice, maxPrice] = filters.price.split('-').map(p => parseFloat(p));
                    if (maxPrice && (productPrice < minPrice || productPrice > maxPrice)) {
                        isVisible = false;
                    } else if (!maxPrice && productPrice < minPrice) {
                        isVisible = false;
                    }
                }
                
                // Apply search filter
                if (filters.search && !productName.includes(filters.search)) {
                    isVisible = false;
                }
                
                // Show/hide product
                if (isVisible) {
                    $item.fadeIn(300);
                    visibleCount++;
                } else {
                    $item.fadeOut(300);
                }
            });
            
            updateProductCount(visibleCount);
        }
        
        // Clear all filters
        function clearAllFilters() {
            $('#genderFilter, #ageFilter, #sizeFilter, #priceFilter').val('');
            $('#searchInput').val('');
            
            $('.product-item').fadeIn(300);
            updateProductCount();
            
            // Show notification
            if (window.VelvetVogue && window.VelvetVogue.ui) {
                window.VelvetVogue.ui.showNotification('Filters cleared', 'success');
            }
        }
        
        // =================== SEARCH FUNCTIONALITY ===================
        function initializeSearch() {
            let searchTimeout;
            
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                // Debounce search for better performance
                searchTimeout = setTimeout(() => {
                    if (searchTerm.length >= 2 || searchTerm.length === 0) {
                        applyFilters();
                    }
                }, 300);
            });
            
            // Search on Enter key
            $('#searchInput').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    applyFilters();
                }
            });
        }
        
        // =================== HELPER FUNCTIONS ===================
        function inferGenderFromName(productName) {
            // Simple gender inference based on product name keywords
            const maleKeywords = ['men', 'male', 'him', 'guy', 'man', 'mens'];
            const femaleKeywords = ['women', 'female', 'her', 'lady', 'woman', 'womens'];
            
            const name = productName.toLowerCase();
            
            if (maleKeywords.some(keyword => name.includes(keyword))) {
                return 'men';
            }
            if (femaleKeywords.some(keyword => name.includes(keyword))) {
                return 'women';
            }
            return 'unisex'; // Default to unisex if cannot determine
        }
        
        function inferAgeFromName(productName) {
            // Simple age inference based on product name keywords
            const kidKeywords = ['kid', 'child', 'baby', 'toddler', 'youth'];
            const teenKeywords = ['teen', 'teenager', 'young'];
            
            const name = productName.toLowerCase();
            
            if (kidKeywords.some(keyword => name.includes(keyword))) {
                return 'kids';
            }
            if (teenKeywords.some(keyword => name.includes(keyword))) {
                return 'teens';
            }
            return 'adults'; // Default to adults
        }
        
        function updateProductCount(count = null) {
            const productCountElement = $('#productCount');
            if (count === null) {
                count = $('.product-item:visible').length;
            }
            productCountElement.text(count + ' Products');
        }
        
        // Review functionality
        
        // Write review modal
        $('.write-review-btn').on('click', function() {
            const productId = $(this).data('product-id');
            const productName = $(this).data('product-name');
            
            $('#reviewProductId').val(productId);
            $('#reviewProductName').text(productName);
            
            // Reset form
            $('#reviewForm')[0].reset();
        });
        
        // View reviews modal
        $('.view-reviews-btn').on('click', function() {
            const productId = $(this).data('product-id');
            const productName = $(this).data('product-name');
            
            $('.modal-title').text(`Reviews for ${productName}`);
            
            // Load reviews
            loadReviews(productId);
        });
        
        // Submit review form
        $('#reviewForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate rating selection
            const rating = $('#ratingSelect').val();
            if (!rating) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Rating Required',
                    text: 'Please select a rating before submitting your review',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ffc107'
                });
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'submit');
            
            $.ajax({
                url: 'review_handler.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Review Submitted!',
                            text: 'Thank you for your review!',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#28a745',
                            timer: 3000,
                            timerProgressBar: true
                        }).then(() => {
                            $('#writeReviewModal').modal('hide');
                            $('#reviewForm')[0].reset();
                            $('#ratingSelect').val('');
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Failed',
                            text: response.message || 'Failed to submit review. Please try again.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Unable to submit review. Please check your connection and try again.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        });
        
        // Load reviews function
        function loadReviews(productId) {
            $('#reviewsContainer').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
            
            $.ajax({
                url: `review_handler.php?action=fetch&product_id=${productId}`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        displayReviews(response);
                    } else {
                        $('#reviewsContainer').html(`
                            <div class="alert alert-warning">
                                <i class="bx bx-info-circle"></i> No reviews found for this product.
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#reviewsContainer').html(`
                        <div class="alert alert-danger">
                            <i class="bx bx-error"></i> Error loading reviews.
                        </div>
                    `);
                }
            });
        }
        
        // Display reviews function
        function displayReviews(data) {
            let html = '';
            
            if (data.review_count > 0) {
                html += `
                    <div class="mb-4 p-3 bg-light rounded">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-1">Average Rating</h5>
                                <div class="d-flex align-items-center">
                                    <span class="h4 text-warning me-2">${data.average_rating}/5</span>
                                    <span class="text-muted">Average</span>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-0 text-muted">${data.review_count} review${data.review_count > 1 ? 's' : ''}</p>
                            </div>
                        </div>
                    </div>
                `;
                
                data.reviews.forEach(review => {
                    html += `
                        <div class="review-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">${review.customer_name}</h6>
                                    <span class="badge bg-warning text-dark">${review.rating_text}</span>
                                </div>
                                <small class="text-muted">${review.review_date}</small>
                            </div>
                            ${review.review_text ? `<p class="mb-0">${review.review_text}</p>` : ''}
                        </div>
                    `;
                });
            } else {
                html = `
                    <div class="alert alert-info text-center">
                        <i class="bx bx-comment-detail fs-2"></i>
                        <h5 class="mt-2">No reviews yet</h5>
                        <p class="mb-0">Be the first to review this product!</p>
                    </div>
                `;
            }
            
            $('#reviewsContainer').html(html);
        }
        
    </script>

    <br /><br /><br />

    <!-- Enhanced Footer -->
    <footer class="bg-dark text-light py-5">
      <div class="container">
        <div class="row g-4">
          <!-- Company Info Section -->
          <div class="col-lg-4 col-md-6">
            <div class="mb-4">
              <h4 class="fw-bold text-primary mb-3">
                <i class="bx bx-diamond me-2"></i>Velvet Vogue
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

      <!-- Back to Top Button -->
      <button
        class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle shadow"
        onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
        style="width: 50px; height: 50px; z-index: 1000"
        aria-label="Back to top"
        title="Back to top"
      >
        <i class="bx bx-chevron-up fs-5"></i>
      </button>
    </footer>

</body>
</html>