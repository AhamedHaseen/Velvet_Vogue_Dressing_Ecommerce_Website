<?php
/**
 * Velvet Vogue E-Commerce - Homepage
 * Dynamic content loaded from database using arrays
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include session and database
include 'includes/session_db.php';

// Initialize data arrays
$heroImages = [];
$shopCategories = [];
$featuredProducts = [];
$newArrivals = [];
$bestsellers = [];
$categories = [];
$productBadges = [];
$stats = ['total_products' => 0, 'total_categories' => 0];

try {
    // Set charset
    $conn->set_charset("utf8mb4");
    
    // Get customer order history and user info if user is logged in
    $customerOrders = [];
    $userData = null;
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Get user information - try multiple approaches
        $userData = null;
        
        // First, try with user_id (note: signIn.php uses 'user_id' column, not 'id')
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
        
        // If no data found, try with email from session
        if (!$userData && isset($_SESSION['user_email'])) {
            $emailQuery = "SELECT first_name, last_name, email FROM users WHERE email = ?";
            $stmt = $conn->prepare($emailQuery);
            if ($stmt) {
                $stmt->bind_param("s", $_SESSION['user_email']);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $userData = $result->fetch_assoc();
                }
                $stmt->close();
            }
        }
        
        // Debug: Uncomment next line to check if user data is being fetched
        echo "<!-- Debug: User ID: " . $user_id . ", User Data: " . ($userData ? json_encode($userData) : 'null') . " -->";
        
        // Try payment_orders first since that's where current orders are stored
        $paymentQuery = "SELECT po.order_id, po.order_number, po.total, po.order_status, po.created_at
                       FROM payment_orders po 
                       WHERE po.user_id = ? 
                       ORDER BY po.created_at DESC 
                       LIMIT 10";
        
        $stmt = $conn->prepare($paymentQuery);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $customerOrders[] = $row;
            }
            $stmt->close();
        }
        
        // Debug: Show order count
        echo "<!-- Debug: Found " . count($customerOrders) . " orders for user_id: " . $user_id . " -->";
        
        // If no orders in payment_orders, try orders table as fallback
        if (empty($customerOrders)) {
            $orderQuery = "SELECT o.*, p.product_name, p.image_url 
                          FROM orders o 
                          LEFT JOIN products p ON o.product_id = p.product_id 
                          WHERE o.user_id = ? 
                          ORDER BY o.created_at DESC 
                          LIMIT 10";
            
            $stmt = $conn->prepare($orderQuery);
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $customerOrders[] = $row;
                }
                $stmt->close();
            }
        }
    }
    
    // 1. Fetch Hero Images Array
    $sql = "SELECT * FROM hero_images WHERE is_active = 1 ORDER BY display_order ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $heroImages[] = [
                'hero_id' => $row['hero_id'],
                'image_url' => $row['image_url'],
                'title' => $row['title'],
                'subtitle' => $row['subtitle'],
                'alt_text' => $row['alt_text'],
                'display_order' => $row['display_order']
            ];
        }
    }
    
    // 2. Fetch Shop Categories Array
    $sql = "SELECT * FROM shop_categories WHERE is_active = 1 ORDER BY display_order ASC LIMIT 6";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $shopCategories[] = [
                'shop_category_id' => $row['shop_category_id'],
                'category_name' => $row['category_name'],
                'image_url' => $row['image_url'],
                'description' => $row['description'],
                'item_count' => $row['item_count'],
                'display_order' => $row['display_order']
            ];
        }
    }
    
    // 3. Fetch Product Badges Array (for later use)
    $sql = "SELECT * FROM product_badges";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $productBadges[$row['product_id']][] = [
                'badge_text' => $row['badge_text'],
                'badge_type' => $row['badge_type']
            ];
        }
    }
    
    // 4. Featured Products - Using static data instead of database
    // Removed database query to use static image paths only
    
    // 5. Fetch New Arrivals Array
    $sql = "SELECT p.*, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.is_new = 1 AND p.stock_quantity > 0 
            ORDER BY p.created_at DESC 
            LIMIT 8";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $product = [
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'category_name' => $row['category_name'],
                'price' => $row['price'],
                'original_price' => $row['original_price'],
                'image_url' => $row['image_url'],
                'rating' => $row['rating'],
                'review_count' => $row['review_count'],
                'gender_category' => $row['gender_category'],
                'badges' => isset($productBadges[$row['product_id']]) ? $productBadges[$row['product_id']] : []
            ];
            $newArrivals[] = $product;
        }
    }
    
    // 6. Fetch Bestsellers Array
    $sql = "SELECT p.*, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.is_bestseller = 1 AND p.stock_quantity > 0 
            ORDER BY p.rating DESC, p.created_at DESC 
            LIMIT 8";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $product = [
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'category_name' => $row['category_name'],
                'price' => $row['price'],
                'original_price' => $row['original_price'],
                'image_url' => $row['image_url'],
                'rating' => $row['rating'],
                'review_count' => $row['review_count'],
                'gender_category' => $row['gender_category'],
                'badges' => isset($productBadges[$row['product_id']]) ? $productBadges[$row['product_id']] : []
            ];
            $bestsellers[] = $product;
        }
    }
    
    // 7. Fetch Categories Array
    $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'category_id' => $row['category_id'],
                'category_name' => $row['category_name'],
                'category_description' => $row['category_description'],
                'category_image' => $row['category_image']
            ];
        }
    }
    
    // 8. Get Statistics
    $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity > 0");
    if ($result) {
        $stats['total_products'] = $result->fetch_assoc()['total'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as total FROM categories WHERE is_active = 1");
    if ($result) {
        $stats['total_categories'] = $result->fetch_assoc()['total'];
    }
    
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    echo "<!-- Database Error: " . htmlspecialchars($e->getMessage()) . " -->";
}

// Helper Functions
function formatPrice($price) {
    return number_format((float)$price, 2);
}

function calculateDiscount($originalPrice, $currentPrice) {
    if ($originalPrice && $originalPrice > $currentPrice) {
        return round((($originalPrice - $currentPrice) / $originalPrice) * 100);
    }
    return 0;
}

function generateStars($rating) {
    $stars = '';
    $rating = (float)$rating;
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="bx bxs-star"></i>';
        } else {
            $stars .= '<i class="bx bx-star"></i>';
        }
    }
    return $stars;
}

function getBadgeClass($badgeType) {
    $classes = [
        'new' => 'new-badge',
        'sale' => 'sale-badge',
        'offer' => 'offer-badge',
        'limited' => 'limited-badge',
        'premium' => 'premium-badge',
        'trending' => 'trending-badge',
        'bestseller' => 'bestseller-badge',
        'flash-sale' => 'flash-sale-badge'
    ];
    return isset($classes[$badgeType]) ? $classes[$badgeType] : 'custom-badge';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include 'includes/customer_head.php'; ?>
    <title>Velvet Vogue - Premium Fashion & Style | Online Shopping</title>
  </head>
  <body>
    <?php include 'includes/customer_navbar.php'; ?>

    <!-- Modern Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center min-vh-100">
          <div class="col-lg-6">
            <div class="hero-content">
              <h1 class="hero-title premium-font">
                Each outfit is a<br />
                <span class="hero-title-emphasis">chapter of who you are</span>
              </h1>
              <p class="hero-subtitle">
                Find your style, fulfill your wish list, and join a community
                that celebrates self-expression shopping smarter, living better,
                and feeling good in every look.
              </p>
              <div class="hero-buttons mt-4">
                <a href="#" class="btn hero-btn-primary me-3">
                  <span>Shop Now</span>
                  <i class="bx bx-shopping-bag"></i>
                </a>
                <button
                  class="btn hero-btn-secondary"
                  onclick="window.location.href='featureProductView.php'"
                >
                  <span>View All Product</span>
                  <i class="bx bx-star"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-image-container">
              <div class="hero-image-slideshow">
                <?php 
                if (!empty($heroImages)) {
                    foreach ($heroImages as $index => $hero) {
                        $activeClass = $index === 0 ? 'active' : '';
                        echo '<img
                          src="' . htmlspecialchars($hero['image_url']) . '"
                          alt="' . htmlspecialchars($hero['alt_text']) . '"
                          class="hero-image ' . $activeClass . '"
                        />';
                    }
                } else {
                    // Fallback images
                    echo '<img src="./Images/si_1.jpg" alt="Fashion Collection" class="hero-image active" />';
                    echo '<img src="./Images/si_2.jpg" alt="Men\'s Fashion" class="hero-image" />';
                    echo '<img src="./Images/si_3.jpg" alt="Women\'s Fashion" class="hero-image" />';
                    echo '<img src="./Images/si_4.jpg" alt="Accessories" class="hero-image" />';
                }
                ?>
              </div>
              <div class="hero-image-overlay">
                <div class="hero-image-text">
                  <?php if (!empty($heroImages)): ?>
                    <h3><?= htmlspecialchars($heroImages[0]['title']) ?></h3>
                    <p><?= htmlspecialchars($heroImages[0]['subtitle']) ?></p>
                  <?php else: ?>
                    <h3>New Collection</h3>
                    <p>Discover Your Style</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Promotional Section -->
    <section class="promo-section">
      <div class="container">
        <!-- Main Promo Banner -->
        <div class="main-promo-banner">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="promo-content">
                <h2 class="promo-title">
                  <i class="bx bxs-offer-alt promo-icon"></i>
                  Exclusive Flash Sale
                </h2>
                <p class="promo-subtitle">
                  Limited Time: Up to 50% OFF on Premium Fashion Collections
                </p>
                <div class="promo-countdown">
                  <span class="countdown-text">Ends in:</span>
                  <div class="countdown-timer">
                    <div class="countdown-item">
                      <span id="hours">24</span>
                      <label>Hours</label>
                    </div>
                    <div class="countdown-item">
                      <span id="minutes">59</span>
                      <label>Minutes</label>
                    </div>
                    <div class="countdown-item">
                      <span id="seconds">59</span>
                      <label>Seconds</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="promo-action">
                <a href="#" class="btn promo-btn-primary">
                  Shop Sale Now
                  <i class="bx bx-right-arrow-alt"></i>
                </a>
                <p class="promo-note">Free shipping on orders over $100</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Feature Promotions -->
        <div class="feature-promos mt-5">
          <div class="row g-4">
            <div class="col-md-4">
              <div class="feature-promo-card">
                <div class="feature-icon">
                  <i class="bx bx-medal"></i>
                </div>
                <h4>New Arrivals</h4>
                <p>Fresh styles weekly</p>
                <span class="feature-badge">20% OFF</span>
              </div>
            </div>
            <div class="col-md-4">
              <div class="feature-promo-card">
                <div class="feature-icon">
                  <i class="bx bx-crown"></i>
                </div>
                <h4>Premium Collection</h4>
                <p>Luxury fashion pieces</p>
                <span class="feature-badge">30% OFF</span>
              </div>
            </div>
            <div class="col-md-4">
              <div class="feature-promo-card">
                <div class="feature-icon">
                  <i class="bx bx-star"></i>
                </div>
                <h4>Best Sellers</h4>
                <p>Customer favorites</p>
                <span class="feature-badge">25% OFF</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Enhanced Product Categories Section -->
    <section class="categories-section py-5">
      <div class="container">
        <div class="row mb-5">
          <div class="col-12 text-center">
            <h2 class="categories-main-title premium-font">Shop by Category</h2>
            <p class="categories-subtitle">
              Discover your perfect style across our curated collections
            </p>
          </div>
        </div>

        <!-- Main Categories Grid -->
        <div class="row g-4 mb-5">
          <?php 
          if (!empty($shopCategories)) {
              // Display first 2 categories as large cards
              $mainCategories = array_slice($shopCategories, 0, 2);
              foreach ($mainCategories as $category): 
          ?>
          <div class="col-lg-6 col-md-6">
            <div class="category-card category-card-large">
              <div class="category-image-container">
                <img
                  src="<?= htmlspecialchars($category['image_url']) ?>"
                  alt="<?= htmlspecialchars($category['category_name']) ?>"
                  class="category-image"
                />
                <div class="category-overlay">
                  <div class="category-content">
                    <h3 class="category-title"><?= htmlspecialchars($category['category_name']) ?></h3>
                    <p class="category-description"><?= htmlspecialchars($category['description']) ?></p>
                    <a href="#" class="btn category-btn">
                      Explore Collection <i class="bx bx-right-arrow-alt"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php 
              endforeach;
          } else {
              // Fallback content
          ?>
          <div class="col-lg-6 col-md-6">
            <div class="category-card category-card-large">
              <div class="category-image-container">
                <img src="./Images/sh_cat_1.jpg" alt="Men's Fashion" class="category-image" />
                <div class="category-overlay">
                  <div class="category-content">
                    <h3 class="category-title">Men's Fashion</h3>
                    <p class="category-description">Stylish & Contemporary</p>
                    <a href="#" class="btn category-btn">Explore Collection <i class="bx bx-right-arrow-alt"></i></a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-md-6">
            <div class="category-card category-card-large">
              <div class="category-image-container">
                <img src="./Images/sh_cat_2.jpg" alt="Women's Fashion" class="category-image" />
                <div class="category-overlay">
                  <div class="category-content">
                    <h3 class="category-title">Women's Fashion</h3>
                    <p class="category-description">Elegant & Chic</p>
                    <a href="#" class="btn category-btn">Explore Collection <i class="bx bx-right-arrow-alt"></i></a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php } ?>
        </div>

        <!-- Sub Categories -->
        <div class="row g-4">
          <?php 
          if (!empty($shopCategories) && count($shopCategories) > 2) {
              // Display remaining categories as small cards
              $subCategories = array_slice($shopCategories, 2, 4);
              foreach ($subCategories as $category): 
          ?>
          <div class="col-lg-3 col-md-6">
            <div class="category-card category-card-small">
              <div class="category-image-container">
                <img
                  src="<?= htmlspecialchars($category['image_url']) ?>"
                  alt="<?= htmlspecialchars($category['category_name']) ?>"
                  class="category-image"
                />
                <div class="category-overlay">
                  <div class="category-content">
                    <h4 class="category-title"><?= htmlspecialchars($category['category_name']) ?></h4>
                    <span class="category-count"><?= $category['item_count'] ?>+ items</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php 
              endforeach;
          } else {
              // Fallback sub categories
              $fallbackCategories = [
                  ['name' => 'Formal Wear', 'image' => './Images/sh_cat_3.jpg', 'count' => '120'],
                  ['name' => 'Casual Wear', 'image' => './Images/sh_cat_4.jpg', 'count' => '200'],
                  ['name' => 'Accessories', 'image' => './Images/sh_cat_5.jpg', 'count' => '150'],
                  ['name' => 'Footwear', 'image' => './Images/sh_cat_6.jpg', 'count' => '80']
              ];
              foreach ($fallbackCategories as $category): 
          ?>
          <div class="col-lg-3 col-md-6">
            <div class="category-card category-card-small">
              <div class="category-image-container">
                <img src="<?= $category['image'] ?>" alt="<?= $category['name'] ?>" class="category-image" />
                <div class="category-overlay">
                  <div class="category-content">
                    <h4 class="category-title"><?= $category['name'] ?></h4>
                    <span class="category-count"><?= $category['count'] ?>+ items</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; } ?>
        </div>

        <!-- Category Features -->
        <div class="row mt-5">
          <div class="col-12">
            <div class="category-features">
              <div class="row g-4">
                <div class="col-md-3 text-center">
                  <div class="feature-icon-box">
                    <i class="bx bx-medal feature-icon"></i>
                    <h5>Premium Quality</h5>
                    <p>Curated collections of highest quality</p>
                  </div>
                </div>
                <div class="col-md-3 text-center">
                  <div class="feature-icon-box">
                    <i class="bx bx-refresh feature-icon"></i>
                    <h5>Weekly Updates</h5>
                    <p>New arrivals added every week</p>
                  </div>
                </div>
                <div class="col-md-3 text-center">
                  <div class="feature-icon-box">
                    <i class="bx bx-tag feature-icon"></i>
                    <h5>Best Prices</h5>
                    <p>Competitive pricing on all categories</p>
                  </div>
                </div>
                <div class="col-md-3 text-center">
                  <div class="feature-icon-box">
                    <i class="bx bx-support feature-icon"></i>
                    <h5>Expert Support</h5>
                    <p>Style consultants ready to help</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Products Section - Girls & Boys -->
    <section class="products-section py-5">
      <div class="container">
        <div class="row mb-5">
          <div class="col-12 text-center">
            <h2 class="products-main-title premium-font">Featured Products</h2>
            <p class="products-subtitle">
              Discover our handpicked collection for girls and boys
            </p>
          </div>
        </div>

        <!-- Product Categories Tabs -->
        <div class="row mb-4">
          <div class="col-12 text-center">
            <div class="product-tabs">
              <button class="product-tab-btn active" data-category="all">
                All Products
              </button>
              <button class="product-tab-btn" data-category="girls">
                Girls Collection
              </button>
              <button class="product-tab-btn" data-category="boys">
                Boys Collection
              </button>
              <button class="product-tab-btn" data-category="offers">
                Special Offers
              </button>
            </div>
          </div>
        </div>

        <!-- Products Grid -->
        <div class="row g-4" id="products-grid">
          <!-- Girls Product 1 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="girls"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_1.jpg"
                  alt="Girls Elegant Dress"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge new-badge">New</span>
                  <span class="badge sale-badge">20% Off</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i>
                  </button>
                  <button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Girls Elegant Party Dress</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <span class="rating-count">(24)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$79.99</span>
                  <span class="original-price">$99.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Boys Product 1 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="boys"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_2.jpg"
                  alt="Boys Casual Shirt"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge trending-badge">Trending</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i>
                  </button>
                  <button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Boys Casual Cotton Shirt</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bx-star"></i>
                  <span class="rating-count">(18)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$49.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Girls Product 2 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="girls offers"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_3.jpg"
                  alt="Girls Denim Jacket"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge offer-badge">50% Off</span>
                  <span class="badge limited-badge">Limited</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i>
                  </button>
                  <button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Girls Trendy Denim Jacket</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <span class="rating-count">(31)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$59.99</span>
                  <span class="original-price">$119.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Boys Product 2 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="boys offers"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_4.jpg"
                  alt="Boys Jeans"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge sale-badge">30% Off</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Boys Slim Fit Jeans</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bx-star"></i>
                  <span class="rating-count">(22)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$69.99</span>
                  <span class="original-price">$99.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Girls Product 3 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="girls"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_5.jpg"
                  alt="Girls Handbag"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge bestseller-badge">Best Seller</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Girls Stylish Handbag</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <span class="rating-count">(47)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$89.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Boys Product 3 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="boys"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_6.jpg"
                  alt="Boys Sneakers"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge new-badge">New</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Boys Sport Sneakers</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bx-star"></i>
                  <span class="rating-count">(15)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$129.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Girls Product 4 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="girls offers"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_7.jpg"
                  alt="Girls White Top"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge flash-sale-badge">Flash Sale</span>
                  <span class="badge sale-badge">40% Off</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Girls Premium White Top</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <span class="rating-count">(38)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$35.99</span>
                  <span class="original-price">$59.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Boys Product 4 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="boys offers"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_8.jpg"
                  alt="Boys Formal Suit"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge premium-badge">Premium</span>
                  <span class="badge sale-badge">25% Off</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Boys Formal Suit Set</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bx-star"></i>
                  <span class="rating-count">(12)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$149.99</span>
                  <span class="original-price">$199.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Girls Product 5 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="girls"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_9.jpg"
                  alt="Girls Denim Jacket"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge trending-badge">Trending</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Girls Denim Jacket</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bx-star"></i>
                  <span class="rating-count">(15)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$45.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Boys Product 5 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="boys"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_10.jpg"
                  alt="Boys Hoodie"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge bestseller-badge">Best Seller</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Boys Comfortable Hoodie</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <span class="rating-count">(33)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$39.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- girls Product 6 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="girls"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_11.jpg"
                  alt="Girls Saree"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge bestseller-badge">Best Seller</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Girls Premium Saree</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <span class="rating-count">(40)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$40.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Boys Product 6 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="boys offers"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_12.jpg"
                  alt="coat suits for men"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge premium-badge">Premium</span>
                  <span class="badge sale-badge">25% Off</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">High Quality Coat Suits for Men</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bx-star"></i>
                  <span class="rating-count">(12)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$160.10</span>
                  <span class="original-price">$210.15</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Boys Product 7 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="boys"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_13.jpg"
                  alt="Boys Wedding Suit Indian"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge trending-badge">Trending</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i>
                  </button>
                  <button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Boys Wedding Suit Indian</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bx-star"></i>
                  <span class="rating-count">(18)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$49.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Girls Product 7 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="girls offers"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_15.jpg"
                  alt="Girls Wedding Dress"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge offer-badge">50% Off</span>
                  <span class="badge limited-badge">Limited</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i>
                  </button>
                  <button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Girls Premium Watch</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <span class="rating-count">(38)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$250.99</span>
                  <span class="original-price">$350.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Boys Product 8 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="boys offers"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_16.jpg"
                  alt="Boys Premium Watch"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge sale-badge">30% Off</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Boys Premium Watch</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bx-star"></i>
                  <span class="rating-count">(22)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$369.99</span>
                  <span class="original-price">$599.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Boys Product 9 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="boys"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_17.jpg"
                  alt="Boys Wallet"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge new-badge">New</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Boys Wallet</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bx-star"></i>
                  <span class="rating-count">(15)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$129.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Girls Product 9 -->
          <div
            class="col-xl-3 col-lg-3 col-md-6 col-sm-12 product-item"
            data-category="girls offers"
          >
            <div class="product-card">
              <div class="product-image-container">
                <img
                  src="./Images/fe_pro_18.jpg"
                  alt="Muslim Girls Abaya"
                  class="product-image"
                />
                <div class="product-badges">
                  <span class="badge flash-sale-badge">Flash Sale</span>
                  <span class="badge sale-badge">40% Off</span>
                </div>
                <div class="product-buttons-overlay">
                  <button class="btn btn-primary add-to-cart-btn-overlay">
                    <i class="bx bx-cart-add"></i></button
                  ><button class="btn btn-outline-light favorite-btn-overlay">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
              </div>
              <div class="product-content">
                <h5 class="product-title">Muslim Girls Abaya</h5>
                <div class="product-rating">
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <i class="bx bxs-star"></i>
                  <span class="rating-count">(38)</span>
                </div>
                <div class="product-price">
                  <span class="current-price">$49.99</span>
                  <span class="original-price">$100.99</span>
                </div>
                <div class="product-buttons">
                  <button
                    class="btn btn-primary btn-block add-to-cart-btn mb-2"
                  >
                    <i class="bx bx-cart-add me-2"></i> Add to Cart
                  </button>
                  <button class="btn btn-outline-danger btn-sm favorite-btn">
                    <i class="bx bx-heart me-1"></i> Add to Wishlist
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- View More Button -->
        <div class="row mt-5">
          <div class="col-12 text-center">
            <button
              class="btn view-more-btn"
              onclick="window.location.href='featureProductView.php'"
            >
              View All Products <i class="bx bx-right-arrow-alt"></i>
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Seasonal & Festival Promotional Banner -->
    <section class="seasonal-banner-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="seasonal-banner">
              <div class="seasonal-bg-overlay"></div>
              <div class="row align-items-center">
                <div class="col-lg-8 col-md-7">
                  <div class="seasonal-content">
                    <div class="seasonal-badge">
                      <i class="bx bxs-gift"></i>
                      <span>Festival Special</span>
                    </div>
                    <h2 class="seasonal-title">🎄 Holiday Season Sale</h2>
                    <h3 class="seasonal-subtitle">
                      Celebrate in Style - Up to 60% OFF
                    </h3>
                    <p class="seasonal-description">
                      Make this festive season memorable with our exclusive
                      collection. Perfect outfits for Christmas, New Year, and
                      all holiday celebrations!
                    </p>
                    <div class="seasonal-features">
                      <div class="feature-item">
                        <i class="bx bx-check-circle"></i>
                        <span>Free Gift Wrapping</span>
                      </div>
                      <div class="feature-item">
                        <i class="bx bx-check-circle"></i>
                        <span>Express Holiday Delivery</span>
                      </div>
                      <div class="feature-item">
                        <i class="bx bx-check-circle"></i>
                        <span>Extended Returns</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-4 col-md-5">
                  <div class="seasonal-action-area">
                    <div class="seasonal-offer-card">
                      <div class="offer-percentage">
                        <span class="big-number">60</span>
                        <span class="percentage">% OFF</span>
                      </div>
                      <p class="offer-text">On selected holiday collections</p>
                    </div>
                    <div class="seasonal-buttons">
                      <a href="#" class="btn seasonal-btn-primary">
                        <i class="bx bx-shopping-bag"></i>
                        Shop Holiday Collection
                      </a>
                      <a href="#" class="btn seasonal-btn-secondary">
                        <i class="bx bx-gift"></i>
                        Gift Guide
                      </a>
                    </div>
                    <div class="seasonal-timer">
                      <p class="timer-text">Offer ends in:</p>
                      <div class="mini-countdown">
                        <span id="days">15</span>d
                        <span id="hours-mini">08</span>h
                        <span id="minutes-mini">45</span>m
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- New Arrivals Section -->
    <section class="new-arrivals py-5 bg-light">
      <div class="container">
        <!-- Section Header -->
        <div class="row">
          <div class="col-12 text-center mb-5">
            <h2 class="display-5 fw-bold text-dark mb-3">New Arrivals</h2>
            <p class="lead text-muted mb-4">
              Discover the latest fashion trends and exclusive pieces
            </p>
            <div class="d-flex justify-content-center">
              <div
                class="bg-primary"
                style="width: 80px; height: 3px; border-radius: 2px"
              ></div>
            </div>
          </div>
        </div>

        <!-- Filter Tabs -->
        <div class="row mb-4">
          <div class="col-12">
            <ul
              class="nav nav-pills d-flex justify-content-center flex-wrap"
              id="newArrivalsTab"
            >
              <li class="nav-item">
                <button
                  class="nav-link active d-flex align-items-center me-2 mb-2"
                  id="all-tab"
                  type="button"
                >
                  <i class="bx bx-grid-alt me-2"></i>All Products
                </button>
              </li>
              <li class="nav-item">
                <button
                  class="nav-link d-flex align-items-center me-2 mb-2"
                  id="women-tab"
                  type="button"
                >
                  <i class="bx bx-female me-2"></i>Women
                </button>
              </li>
              <li class="nav-item">
                <button
                  class="nav-link d-flex align-items-center me-2 mb-2"
                  id="men-tab"
                  type="button"
                >
                  <i class="bx bx-male me-2"></i>Men
                </button>
              </li>
              <li class="nav-item">
                <button
                  class="nav-link d-flex align-items-center me-2 mb-2"
                  id="accessories-tab"
                  type="button"
                >
                  <i class="bx bx-diamond me-2"></i>Accessories
                </button>
              </li>
            </ul>
          </div>
        </div>

        <!-- Products Content -->
        <div id="newArrivalsContent">
          <div class="row g-3 g-md-4 align-items-stretch">
            <?php 
            if (!empty($newArrivals)) {
                foreach ($newArrivals as $product):
                    $discountPercent = calculateDiscount($product['original_price'], $product['price']);
            ?>
            <!-- Dynamic Product from Database -->
            <div class="col-6 col-sm-6 col-md-3">
              <div class="card h-100 border-0 shadow-sm product-card d-flex flex-column" data-product-id="<?= $product['product_id'] ?>">
                <div class="position-relative overflow-hidden">
                  <img
                    src="Images/<?= htmlspecialchars($product['image_url']) ?>"
                    class="card-img-top product-image"
                    alt="<?= htmlspecialchars($product['product_name']) ?>"
                    onerror="this.src='Images/na_pro_1.jpg'"
                  />
                  <div class="position-absolute top-0 start-0 m-2">
                    <?php if ($product['is_new']): ?>
                      <span class="badge bg-success">New</span>
                    <?php endif; ?>
                    <?php if ($product['is_on_sale'] && $discountPercent > 0): ?>
                      <span class="badge bg-danger"><?= $discountPercent ?>% Off</span>
                    <?php endif; ?>
                    <?php if ($product['is_bestseller']): ?>
                      <span class="badge bg-warning">Bestseller</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="card-body text-center d-flex flex-column">
                  <h6 class="card-title fw-bold mb-2">
                    <?= htmlspecialchars($product['product_name']) ?>
                  </h6>
                  <p class="card-text text-muted small mb-3 flex-grow-1">
                    <?= htmlspecialchars($product['product_description']) ?>
                  </p>
                  <div class="mb-3">
                    <span class="h5 text-primary mb-0">$<?= formatPrice($product['price']) ?></span>
                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                      <span class="text-muted text-decoration-line-through ms-2">$<?= formatPrice($product['original_price']) ?></span>
                    <?php endif; ?>
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart-btn" data-product-id="<?= $product['product_id'] ?>">
                    <i class="bx bx-shopping-bag me-2"></i>Add to Cart
                  </button>
                </div>
              </div>
            </div>
            <?php 
                endforeach;
            } else {
                // Fallback products if no database data
            ?>
            <!-- Fallback Product 1 -->
            <div class="col-6 col-sm-6 col-md-3">
              <div class="card h-100 border-0 shadow-sm product-card d-flex flex-column">
                <div class="position-relative overflow-hidden">
                  <img
                    src="./Images/na_pro_1.jpg"
                    class="card-img-top product-image"
                    alt="Women's Bottom"
                  />
                  <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-success">New</span>
                  </div>
                </div>
                <div class="card-body text-center d-flex flex-column">
                  <h6 class="card-title fw-bold mb-2">
                    Premium Women's Bottom
                  </h6>
                  <p class="card-text text-muted small mb-3 flex-grow-1">
                    Professional tailored bottom
                  </p>
                  <div class="mb-3">
                    <span class="h5 text-primary mb-0">$159.99</span>
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart-btn" data-product-id="fallback-1">
                    <i class="bx bx-shopping-bag me-2"></i>Add to Cart
                  </button>
                </div>
              </div>
            </div>
            <?php } ?>
            
            <!-- Product 2 -->
            <div class="col-6 col-sm-6 col-md-3">
              <div class="card h-100 border-0 shadow-sm product-card d-flex flex-column">
                <div class="position-relative overflow-hidden">
                  <img
                    src="./Images/na_pro_2.jpg"
                    class="card-img-top product-image"
                    alt="Boys Bottom"
                  />
                  <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-danger">Hot</span>
                  </div>
                </div>
                <div class="card-body text-center d-flex flex-column">
                  <h6 class="card-title fw-bold mb-2">Premium Men's Bottom</h6>
                  <p class="card-text text-muted small mb-3 flex-grow-1">
                    Classic vintage-style bottom
                  </p>
                  <div class="mb-3">
                    <span class="h5 text-primary mb-0">$89.99</span>
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart-btn" data-product-id="fallback-2">
                    <i class="bx bx-shopping-bag me-2"></i>Add to Cart
                  </button>
                </div>
              </div>
            </div>

            <!-- Product 3 -->
            <div class="col-6 col-sm-6 col-md-3">
              <div
                class="card h-100 border-0 shadow-sm product-card d-flex flex-column"
              >
                <div class="position-relative overflow-hidden">
                  <img
                    src="./Images/na_pro_3.jpg"
                    class="card-img-top product-image"
                    alt="girls combo packs"
                  />
                  <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-warning text-dark">Sale</span>
                  </div>
                </div>
                <div class="card-body text-center d-flex flex-column">
                  <h6 class="card-title fw-bold mb-2">Women's Combo Packs</h6>
                  <p class="card-text text-muted small mb-3 flex-grow-1">
                    Comfortable everyday sneakers
                  </p>
                  <div class="mb-3">
                    <span class="h5 text-primary mb-0">$119.99</span>
                    <small class="text-muted text-decoration-line-through ms-2"
                      >$149.99</small
                    >
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart-btn" data-product-id="fallback-3">
                    <i class="bx bx-shopping-bag me-2"></i>Add to Cart
                  </button>
                </div>
              </div>
            </div>

            <!-- Product 4 -->
            <div class="col-6 col-sm-6 col-md-3">
              <div
                class="card h-100 border-0 shadow-sm product-card d-flex flex-column"
              >
                <div class="position-relative overflow-hidden">
                  <img
                    src="./Images/na_pro_4.jpg"
                    class="card-img-top product-image"
                    alt="men's combo pack"
                  />
                  <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-primary">Premium</span>
                  </div>
                </div>
                <div class="card-body text-center d-flex flex-column">
                  <h6 class="card-title fw-bold">Men's Combo Pack</h6>
                  <p class="card-text text-muted small">Premium Cloths</p>
                  <div class="mb-3">
                    <span class="h5 text-primary mb-0">$299.99</span>
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart-btn" data-product-id="fallback-4">
                    <i class="bx bx-shopping-bag me-2"></i>Add to Cart
                  </button>
                </div>
              </div>
            </div>

            <!-- Product 5 -->
            <div class="col-6 col-sm-6 col-md-3">
              <div
                class="card h-100 border-0 shadow-sm product-card d-flex flex-column"
              >
                <div class="position-relative overflow-hidden">
                  <img
                    src="./Images/na_pro_5.jpg"
                    class="card-img-top product-image"
                    alt="White Shirt"
                  />
                  <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-info">Bestseller</span>
                  </div>
                </div>
                <div class="card-body text-center d-flex flex-column">
                  <h6 class="card-title fw-bold">Classic White Shirt</h6>
                  <p class="card-text text-muted small">
                    Timeless professional wardrobe
                  </p>
                  <div class="mb-3">
                    <span class="h5 text-primary mb-0">$49.99</span>
                    <small class="text-muted text-decoration-line-through ms-2"
                      >$62.49</small
                    >
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart-btn" data-product-id="fallback-5">
                    <i class="bx bx-shopping-bag me-2"></i>Add to Cart
                  </button>
                </div>
              </div>
            </div>

            <!-- Product 6 -->
            <div class="col-6 col-sm-6 col-md-3">
              <div
                class="card h-100 border-0 shadow-sm product-card d-flex flex-column"
              >
                <div class="position-relative overflow-hidden">
                  <img
                    src="./Images/na_pro_6.jpg"
                    class="card-img-top product-image"
                    alt="Jeans"
                  />
                  <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-success">New</span>
                  </div>
                </div>
                <div class="card-body text-center d-flex flex-column">
                  <h6 class="card-title fw-bold">Designer Slim Jeans</h6>
                  <p class="card-text text-muted small">
                    Premium slim-fit with perfect tailoring
                  </p>
                  <div class="mb-3">
                    <span class="h5 text-primary mb-0">$79.99</span>
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart-btn" data-product-id="fallback-6">
                    <i class="bx bx-shopping-bag me-2"></i>Add to Cart
                  </button>
                </div>
              </div>
            </div>

            <!-- Product 7 -->
            <div class="col-6 col-sm-6 col-md-3">
              <div
                class="card h-100 border-0 shadow-sm product-card d-flex flex-column"
              >
                <div class="position-relative overflow-hidden">
                  <img
                    src="./Images/na_pro_7.jpg"
                    class="card-img-top product-image"
                    alt="Watch"
                  />
                  <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-primary">Premium</span>
                  </div>
                </div>
                <div class="card-body text-center d-flex flex-column">
                  <h6 class="card-title fw-bold">Minimalist Watch</h6>
                  <p class="card-text text-muted small">
                    Elegant timepiece with clean design
                  </p>
                  <div class="mb-3">
                    <span class="h5 text-primary mb-0">$249.99</span>
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart-btn" data-product-id="fallback-7">
                    <i class="bx bx-shopping-bag me-2"></i>Add to Cart
                  </button>
                </div>
              </div>
            </div>

            <!-- Product 8 -->
            <div class="col-6 col-sm-6 col-md-3">
              <div
                class="card h-100 border-0 shadow-sm product-card d-flex flex-column"
              >
                <div class="position-relative overflow-hidden">
                  <img
                    src="./Images/na_pro_8.jpg"
                    class="card-img-top product-image"
                    alt="Sunglasses"
                  />
                  <div class="position-absolute top-0 start-0 m-2">
                    <span class="badge bg-danger">Hot</span>
                  </div>
                </div>
                <div class="card-body text-center d-flex flex-column">
                  <h6 class="card-title fw-bold">Trendy Sunglasses</h6>
                  <p class="card-text text-muted small">
                    Stylish sunglasses with UV protection
                  </p>
                  <div class="mb-3">
                    <span class="h5 text-primary mb-0">$139.99</span>
                    <small class="text-muted text-decoration-line-through ms-2"
                      >$169.99</small
                    >
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart-btn" data-product-id="fallback-8">
                    <i class="bx bx-shopping-bag me-2"></i>Add to Cart
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- View All Button -->
        <div class="row mt-5">
          <div class="col-12 text-center">
            <button class="btn btn-primary btn-lg px-5">
              View All New Arrivals <i class="bx bx-right-arrow-alt ms-2"></i>
            </button>
          </div>
        </div>
      </div>
    </section>

    <br /><br /><br />

    <!-- Beautiful Ramadan Promotion Banner -->
    <section class="ramadan-banner-new py-5 position-relative overflow-hidden">
      <div class="container-fluid px-4">
        <div class="row align-items-center min-vh-50">
          <!-- Left Content Section -->
          <div class="col-lg-7 col-md-6">
            <div class="ramadan-content-new position-relative z-3">
              <div class="mb-4">
                <div class="ramadan-greeting d-flex align-items-center mb-3">
                  <div class="islamic-icon-wrapper me-3">
                    <i class="bx bx-moon ramadan-moon"></i>
                    <i class="bx bx-star ramadan-star-1"></i>
                    <i class="bx bx-star ramadan-star-2"></i>
                    <i class="bx bx-star ramadan-star-3"></i>
                  </div>
                  <div>
                    <h1 class="ramadan-main-title mb-1">Ramadan Kareem</h1>
                    <p class="ramadan-arabic-text mb-0">رمضان كريم</p>
                  </div>
                </div>
              </div>

              <div class="ramadan-offer-section mb-4">
                <h2 class="ramadan-collection-title mb-3">
                  Exclusive Ramadan Collection 2025
                </h2>
                <p class="ramadan-description mb-4">
                  Embrace the spirit of Ramadan with our carefully curated
                  collection of elegant modest wear, traditional abayas, and
                  beautiful festive outfits perfect for this blessed month.
                </p>

                <!-- Offer Highlights -->
                <div class="ramadan-highlights d-flex flex-wrap gap-3 mb-4">
                  <div class="highlight-card">
                    <i class="bx bx-gift"></i>
                    <span>Up to 50% OFF</span>
                  </div>
                  <div class="highlight-card">
                    <i class="bx bx-shipping"></i>
                    <span>Free Delivery</span>
                  </div>
                  <div class="highlight-card">
                    <i class="bx bx-time-five"></i>
                    <span>Limited Edition</span>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="ramadan-actions d-flex flex-wrap gap-3">
                <a href="#" class="btn-ramadan-primary">
                  <i class="bx bx-shopping-bag me-2"></i>
                  Explore Collection
                </a>
                <a href="#" class="btn-ramadan-secondary">
                  <i class="bx bx-heart me-2"></i>
                  Save to Wishlist
                </a>
              </div>
            </div>
          </div>

          <!-- Right Visual Section -->
          <div class="col-lg-5 col-md-6">
            <div class="ramadan-visual-section text-center position-relative">
              <!-- Decorative Elements -->
              <div class="ramadan-decoration-new">
                <div class="mosque-silhouette">
                  <div class="mosque-dome"></div>
                  <div class="mosque-minaret mosque-minaret-1"></div>
                  <div class="mosque-minaret mosque-minaret-2"></div>
                </div>

                <div class="celestial-elements">
                  <div class="crescent-moon">
                    <i class="bx bx-moon"></i>
                  </div>
                  <div class="stars-constellation">
                    <div class="star star-1"></div>
                    <div class="star star-2"></div>
                    <div class="star star-3"></div>
                    <div class="star star-4"></div>
                    <div class="star star-5"></div>
                  </div>
                </div>

                <!-- Islamic Pattern Circle -->
                <div class="islamic-pattern-circle">
                  <div class="pattern-inner">
                    <div class="geometric-shape"></div>
                  </div>
                </div>
              </div>

              <!-- Blessing Text -->
              <div class="ramadan-blessing mt-4">
                <h3 class="blessing-text">
                  May this Ramadan bring peace, happiness and prosperity
                </h3>
                <p class="blessing-subtitle">تقبل الله منا ومنكم</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Countdown Timer Section -->
        <div class="row mt-5">
          <div class="col-12">
            <div class="ramadan-countdown text-center">
              <h4 class="countdown-title mb-4">Special Offer Ends In</h4>
              <div
                class="countdown-timer d-flex justify-content-center gap-3 flex-wrap"
              >
                <div class="timer-unit">
                  <div class="timer-number">28</div>
                  <div class="timer-label">Days</div>
                </div>
                <div class="timer-separator">:</div>
                <div class="timer-unit">
                  <div class="timer-number">14</div>
                  <div class="timer-label">Hours</div>
                </div>
                <div class="timer-separator">:</div>
                <div class="timer-unit">
                  <div class="timer-number">35</div>
                  <div class="timer-label">Minutes</div>
                </div>
                <div class="timer-separator">:</div>
                <div class="timer-unit">
                  <div class="timer-number">42</div>
                  <div class="timer-label">Seconds</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Animated Background Elements -->
      <div class="ramadan-bg-elements">
        <div class="floating-element floating-1"></div>
        <div class="floating-element floating-2"></div>
        <div class="floating-element floating-3"></div>
        <div class="bg-gradient-overlay"></div>
      </div>
    </section>

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

    <!-- Order History Modal -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="modal fade" id="orderHistoryModal" tabindex="-1" aria-labelledby="orderHistoryModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="orderHistoryModalLabel">
              <i class="bx bx-receipt me-2"></i>My Order History
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if (!empty($customerOrders)): ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Order ID</th>
                      <th>Product</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($customerOrders as $order): ?>
                    <tr>
                      <td>
                        <strong>#VV<?= str_pad($order['id'] ?? $order['order_id'] ?? '0', 3, '0', STR_PAD_LEFT) ?></strong>
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <?php if (!empty($order['image_url'])): ?>
                          <img src="<?= htmlspecialchars($order['image_url']) ?>" alt="Product" 
                               style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; margin-right: 10px;">
                          <?php endif; ?>
                          <div>
                            <div class="fw-medium"><?= htmlspecialchars($order['product_name'] ?? 'Product') ?></div>
                            <?php if (isset($order['quantity'])): ?>
                            <small class="text-muted">Qty: <?= $order['quantity'] ?></small>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td><strong>$<?= number_format($order['total'] ?? $order['amount'] ?? 0, 2) ?></strong></td>
                      <td>
                        <span class="badge bg-<?= 
                          ($order['status'] ?? 'pending') === 'completed' || ($order['status'] ?? 'pending') === 'delivered' ? 'success' : 
                          (($order['status'] ?? 'pending') === 'pending' || ($order['status'] ?? 'pending') === 'processing' ? 'warning' : 
                          (($order['status'] ?? 'pending') === 'cancelled' ? 'danger' : 'info')) 
                        ?>">
                          <?= ucfirst($order['status'] ?? 'Pending') ?>
                        </span>
                      </td>
                      <td>
                        <small><?= date('M j, Y', strtotime($order['created_at'] ?? $order['order_date'] ?? date('Y-m-d'))) ?></small>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-center py-5">
                <i class="bx bx-receipt" style="font-size: 4rem; color: #dee2e6;"></i>
                <h5 class="mt-3 text-muted">No Orders Yet</h5>
                <p class="text-muted">Your order history will appear here once you make a purchase.</p>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                  Start Shopping
                </button>
              </div>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.all.min.js"></script>
    <!-- main.js removed as requested -->
    <!-- Boxicons JS (if needed for some icons, not required for styling) -->
    <!-- Script for promo countdown logic -->
    <script>
      // Working Countdown Timer - Starts from 24:59:59
      let totalSeconds = 24 * 3600 + 59 * 60 + 59; // 24 hours, 59 minutes, 59 seconds

      function updateCountdown() {
        if (totalSeconds <= 0) {
          document.getElementById("hours").textContent = "00";
          document.getElementById("minutes").textContent = "00";
          document.getElementById("seconds").textContent = "00";
          return;
        }

        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        // Update the display with padded zeros
        document.getElementById("hours").textContent = String(hours).padStart(
          2,
          "0"
        );
        document.getElementById("minutes").textContent = String(
          minutes
        ).padStart(2, "0");
        document.getElementById("seconds").textContent = String(
          seconds
        ).padStart(2, "0");

        totalSeconds--;
      }

      // Start the countdown immediately and then every second
      updateCountdown();
      setInterval(updateCountdown, 1000);

      // Enhanced Search Functionality
      const searchInput = document.getElementById("searchInput");
      const searchForm = searchInput?.closest("form");

      if (searchInput && searchForm) {
        // Add search suggestions and enhanced UX
        searchInput.addEventListener("input", function (e) {
          const query = e.target.value.trim();
          if (query.length > 0) {
            e.target.style.background = "#fff";
          } else {
            e.target.style.background = "rgba(255, 255, 255, 0.95)";
          }
        });

        // Handle form submission
        searchForm.addEventListener("submit", function (e) {
          e.preventDefault();
          const query = searchInput.value.trim();
          if (query) {
            // Here you would typically redirect to search results
            console.log("Searching for:", query);
            // Example: window.location.href = `products.php?search=${encodeURIComponent(query)}`;
            alert(`Searching for: "${query}"`);
          }
        });

        // Add keyboard shortcuts
        document.addEventListener("keydown", function (e) {
          // Focus search on Ctrl+K or Cmd+K
          if ((e.ctrlKey || e.metaKey) && e.key === "k") {
            e.preventDefault();
            searchInput.focus();
          }
        });
      }

      // Hero Image Slideshow
      const heroImages = document.querySelectorAll(".hero-image");
      if (heroImages.length > 0) {
        let currentImageIndex = 0;

        function showNextImage() {
          // Remove active class from current image
          heroImages[currentImageIndex].classList.remove("active");

          // Move to next image (loop back to 0 if at end)
          currentImageIndex = (currentImageIndex + 1) % heroImages.length;

          // Add active class to new image
          heroImages[currentImageIndex].classList.add("active");
        }

        // Change image every 4 seconds
        setInterval(showNextImage, 4000);
      }
    </script>
    
    <!-- Main.js for index page functionality -->
    <script src="main.js"></script>
  </body>
</html>

