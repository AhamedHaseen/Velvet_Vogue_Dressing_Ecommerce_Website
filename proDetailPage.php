
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Product Details - Velvet Vogue</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <!-- Bootstrap CSS -->
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

    <!-- Custom CSS -->
    <link rel="stylesheet" href="./style.css" />
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Enhanced Product Detail Page Styles -->
    <style>
      .enhanced-product-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 70vh;
      }
      
      .product-card {
        background: white;
        border: none;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
      }
      
      .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      }
      
      .product-image {
        transition: transform 0.3s ease;
      }
      
      .product-image:hover {
        transform: scale(1.05);
      }
      
      .product-title {
        font-family: "Playfair Display", serif !important;
        letter-spacing: 0.5px;
      }
      
      .price-section .current-price {
        font-family: "Inter", sans-serif;
        font-weight: 700;
      }
      
      .action-buttons .btn {
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      }
      
      .action-buttons .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
      }
      
      .badge {
        border-radius: 50px;
        font-weight: 500;
      }
      
      .form-select, .form-control {
        border-radius: 10px;
        transition: all 0.3s ease;
      }
      
      .form-select:focus, .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        transform: translateY(-1px);
      }
      
      /* Mobile Responsive Enhancements */
      @media (max-width: 768px) {
        .product-image {
          height: 180px !important;
        }
        
        .card-body {
          padding: 1.5rem !important;
        }
        
        .action-buttons {
          flex-direction: column;
          align-items: stretch !important;
        }
        
        .action-buttons .btn {
          margin-bottom: 8px;
          text-align: center;
        }
        
        .price-section .current-price {
          font-size: 1.75rem !important;
        }
      }
      
      /* Enhanced Rating Stars */
      .rating {
        font-size: 1.2rem;
      }
      
      /* Custom animations */
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      .product-card {
        animation: fadeInUp 0.6s ease-out;
      }
    </style>
  </head>

  <body class="vv-pd-body">
    <!-- Navigation Bar -->
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
              <a class="nav-link" href="./featureProductView.php">
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
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark"
                style="font-size: 0.65rem"
              >
                5
              </span>
            </a>

            <!-- Cart -->
            <a
              href="#"
              class="btn btn-outline-primary position-relative me-2"
              title="Cart"
            >
              <i class="bx bx-cart"></i>
              <span
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              >
                2
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

    <!-- Page Header Section -->
    <section class="bg-light py-3">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-2">
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                  <a href="index.php" class="text-decoration-none">
                    <i class="bx bx-home me-1"></i>Home
                  </a>
                </li>
                <li class="breadcrumb-item">
                  <a
                    href="featureProductView.php"
                    class="text-decoration-none"
                  >
                    Products
                  </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                  Product Details
                </li>
              </ol>
            </nav>

            <!-- Page Title -->
            <div class="d-flex align-items-center">
              <h4 class="fw-bold text-dark mb-0 me-3">Product Detail Page</h4>
              <span class="badge bg-primary rounded-pill">
                <i class="bx bx-info-circle me-1"></i>Detailed View
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Enhanced Product Detail Section -->
    <section class="enhanced-product-section py-5">
      <div class="container">
        <!-- Back Button -->
        <div class="row mb-4">
          <div class="col-12">
            <button class="btn btn-outline-primary" onclick="goBackToIndex()">
              <i class="bx bx-arrow-back me-2"></i>Back to Products
            </button>
          </div>
        </div>
        
        <div class="row justify-content-center">
          <div class="col-lg-10 col-xl-8">
            <div class="product-card shadow-lg rounded-3 overflow-hidden">
              <div class="row g-0 align-items-center">
                <!-- Single Product Image -->
                <div class="col-md-5">
                  <div class="product-image-wrapper p-3">
                    <img
                      id="productImage"
                      src="Images/product1.jpg"
                      alt="Stylish Fashion Dress"
                      class="img-fluid rounded-3 product-image w-100"
                      style="height: 220px; object-fit: cover;"
                    />
                  </div>
                </div>

                <!-- Simple Product Info -->
                <div class="col-md-7">
                  <div class="card-body p-3">
                    <!-- Product Title -->
                    <h4 id="productTitle" class="product-title mb-2 fw-bold text-primary">Stylish Fashion Dress</h4>
                    
                    <!-- Rating -->
                    <div class="rating mb-2 d-flex align-items-center">
                      <div class="text-warning me-2" style="font-size: 1.1rem;">
                        ★★★★☆
                      </div>
                      <span class="text-muted small">(4.2) • <span class="text-success">150 sold</span></span>
                    </div>

                    <!-- Price -->
                    <div class="price-section mb-3">
                      <div class="d-flex align-items-center flex-wrap gap-2">
                        <span id="productPrice" class="current-price h4 text-primary fw-bold mb-0">₹1,299</span>
                        <span class="original-price text-muted text-decoration-line-through">₹1,999</span>
                        <span class="badge bg-success px-2 py-1">35% OFF</span>
                      </div>
                    </div>

                    <!-- Description -->
                    <p class="product-description text-muted mb-3 small lh-sm">
                      Elegant and stylish fashion dress perfect for casual and formal occasions. Made with premium quality fabric for ultimate comfort and style.
                    </p>

                    <!-- Product Status -->
                    <div class="mb-3">
                      <span class="badge bg-success me-2 px-3 py-2">New</span>
                      <span class="badge bg-warning text-dark me-2 px-3 py-2">Bestseller</span>
                      <span class="badge bg-primary me-2 px-3 py-2">Featured</span>
                    </div>

                    <!-- Enhanced Product Options -->
                    <div class="product-options mb-3">
                      <div class="row g-2">
                        <!-- Color -->
                        <div class="col-4">
                          <label class="form-label small fw-semibold text-dark">Color:</label>
                          <select class="form-select form-select-sm border-2">
                            <option>Blue</option>
                            <option>Black</option>
                            <option>White</option>
                          </select>
                        </div>

                        <!-- Size -->
                        <div class="col-4">
                          <label class="form-label small fw-semibold text-dark">Size:</label>
                          <select class="form-select form-select-sm border-2">
                            <option>Small</option>
                            <option selected>Medium</option>
                            <option>Large</option>
                          </select>
                        </div>

                        <!-- Quantity -->
                        <div class="col-4">
                          <label class="form-label small fw-semibold text-dark">Qty:</label>
                          <input type="number" class="form-control form-control-sm border-2" value="1" min="1">
                        </div>
                      </div>
                    </div>

                    <!-- Enhanced Action Buttons -->
                    <div class="action-buttons d-flex flex-wrap align-items-center gap-2">
                      <button class="btn btn-primary px-3 add-to-cart-btn" data-product-id="1">
                        <i class="bx bx-cart me-1"></i>Add to Cart
                      </button>
                      <button class="btn btn-success px-3" onclick="window.location.href='ProPayment.php'">
                        <i class="bx bx-credit-card me-1"></i>Buy Now
                      </button>
                      <span class="badge bg-success px-2 py-1">
                        <i class="bx bx-check-circle me-1"></i>In Stock (25)
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.all.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Main E-commerce JavaScript -->
    <script src="main.js"></script>

    <!-- Product Detail JavaScript -->
    <script>
      // Global variables
      let vvPdCurrentStock = 50;
      let vvPdSelectedColorName = "Blue";
      let vvPdSelectedSizeName = "Medium";
      let vvPdCurrentQuantity = 1;

      // Image change functionality
      function vvPdChangeImage(thumbnail) {
        const mainImage = document.getElementById("vvPdMainImage");
        mainImage.src = thumbnail.src;
        mainImage.alt = thumbnail.alt;

        // Update active thumbnail
        document.querySelectorAll(".vv-pd-thumbnail-item").forEach((item) => {
          item.classList.remove("vv-pd-thumbnail-active");
        });
        thumbnail
          .closest(".vv-pd-thumbnail-item")
          .classList.add("vv-pd-thumbnail-active");
      }

      // Color selection
      function vvPdSelectColor(colorElement) {
        // Remove active class from all colors
        document.querySelectorAll(".vv-pd-color-item").forEach((item) => {
          item.classList.remove("vv-pd-color-active");
        });

        // Add active class to selected color
        colorElement.classList.add("vv-pd-color-active");

        // Update selected color text
        vvPdSelectedColorName = colorElement.getAttribute("data-color");
        document.getElementById("vvPdSelectedColor").textContent =
          vvPdSelectedColorName;
      }

      // Size selection
      function vvPdSelectSize(sizeElement) {
        // Remove active class from all sizes
        document.querySelectorAll(".vv-pd-size-btn").forEach((btn) => {
          btn.classList.remove("vv-pd-size-active");
        });

        // Add active class to selected size
        sizeElement.classList.add("vv-pd-size-active");

        // Update selected size text
        vvPdSelectedSizeName = sizeElement.getAttribute("data-size");
        document.getElementById("vvPdSelectedSize").textContent =
          vvPdSelectedSizeName;
      }

      // Quantity controls
      function vvPdChangeQuantity(change) {
        const quantityInput = document.getElementById("vvPdQuantity");
        const currentValue = parseInt(quantityInput.value);
        const newValue = currentValue + change;

        if (newValue >= 1 && newValue <= vvPdCurrentStock) {
          quantityInput.value = newValue;
          vvPdCurrentQuantity = newValue;
        }
      }

      // Buy now functionality
      function vvPdBuyNow() {
        const orderDetails = {
          product: "Simple Modern Minimalist Tshirt",
          color: vvPdSelectedColorName,
          size: vvPdSelectedSizeName,
          quantity: vvPdCurrentQuantity,
          price: "$98.00",
        };

        alert(
          `Proceeding to checkout:\n` +
            `Product: ${orderDetails.product}\n` +
            `Color: ${orderDetails.color}\n` +
            `Size: ${orderDetails.size}\n` +
            `Quantity: ${orderDetails.quantity}\n` +
            `Price: ${orderDetails.price}`
        );
      }

      // Add to cart functionality
      function vvPdAddToCart() {
        const cartItem = {
          product: "Simple Modern Minimalist Tshirt",
          color: vvPdSelectedColorName,
          size: vvPdSelectedSizeName,
          quantity: vvPdCurrentQuantity,
        };

        alert(
          `Added to cart:\n` +
            `${cartItem.quantity}x ${cartItem.product}\n` +
            `Color: ${cartItem.color}\n` +
            `Size: ${cartItem.size}`
        );

        // Update cart count
        const cartBadge = document.querySelector(".vv-pd-cart-badge");
        let currentCount = parseInt(cartBadge.textContent);
        cartBadge.textContent = currentCount + vvPdCurrentQuantity;
      }

      // Add to wishlist
      function vvPdAddToWishlist() {
        alert("Added to wishlist!");
      }

      // Quantity input validation
      document.addEventListener("DOMContentLoaded", function () {
        const quantityInput = document.getElementById("vvPdQuantity");
        quantityInput.addEventListener("change", function () {
          const value = parseInt(this.value);
          if (value > vvPdCurrentStock) {
            this.value = vvPdCurrentStock;
          } else if (value < 1) {
            this.value = 1;
          }
          vvPdCurrentQuantity = parseInt(this.value);
        });
      });

      // Navigate to Review Page with Loading
      function navigateToReviewPage() {
        const btn = document.getElementById("writeReviewBtn");
        const btnText = btn.querySelector(".btn-text");
        const spinner = document.getElementById("reviewSpinner");

        // Show loading state
        btn.disabled = true;
        btnText.textContent = "Loading...";
        spinner.classList.remove("d-none");
        btn.style.cursor = "not-allowed";
        btn.style.opacity = "0.7";

        // Add loading animation to button
        btn.classList.add("btn-loading");

        // Simulate loading time then navigate
        setTimeout(() => {
          window.location.href = "writeReview.php";
        }, 800); // 800ms loading effect
      }
    </script>

    <!-- Professional Product Detail Page Styles -->
    <style>
      /* Base Styles */
      .vv-pd-body {
        font-family: "Inter", sans-serif;
        line-height: 1.6;
        color: #2d3748;
      }

      .vv-pd-navbar {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      .vv-pd-search-container {
        min-width: 250px;
      }

      .vv-pd-search-input {
        border: 1px solid #e2e8f0;
        border-radius: 6px 0 0 6px;
      }

      .vv-pd-search-btn {
        border: 1px solid #e2e8f0;
        border-radius: 0 6px 6px 0;
      }

      .vv-pd-cart-badge {
        font-size: 0.65rem;
      }

      /* Main Section */
      .vv-pd-main-section {
        padding: 3rem 0;
        background-color: #f8fafc;
      }

      /* Image Container */
      .vv-pd-image-container {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
      }

      .vv-pd-main-image-wrapper {
        position: relative;
        margin-bottom: 1.5rem;
      }

      .vv-pd-main-image {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
      }

      .vv-pd-thumbnail-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 0.75rem;
      }

      .vv-pd-thumbnail-item {
        border-radius: 6px;
        overflow: hidden;
        border: 2px solid #e2e8f0;
        cursor: pointer;
      }

      .vv-pd-thumbnail-item.vv-pd-thumbnail-active {
        border-color: #3b82f6;
      }

      .vv-pd-thumbnail-img {
        width: 100%;
        height: 60px;
        object-fit: cover;
      }

      /* Product Information */
      .vv-pd-info-container {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        height: fit-content;
      }

      .vv-pd-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
      }

      .vv-pd-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: #1a202c;
        margin-bottom: 1rem;
        line-height: 1.3;
      }

      .vv-pd-rating-section {
        display: flex;
        align-items: center;
        gap: 1rem;
      }

      .vv-pd-stars {
        display: flex;
        gap: 2px;
      }

      .vv-pd-star-filled {
        color: #fbbf24;
        font-size: 1rem;
      }

      .vv-pd-star-empty {
        color: #d1d5db;
        font-size: 1rem;
      }

      .vv-pd-rating-text {
        color: #6b7280;
        font-size: 0.9rem;
      }

      .vv-pd-sold-count {
        color: #6b7280;
        font-size: 0.9rem;
      }

      /* Price Section */
      .vv-pd-price-section {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
      }

      .vv-pd-price-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.5rem;
      }

      .vv-pd-current-price {
        font-size: 2rem;
        font-weight: 700;
        color: #1a202c;
      }

      .vv-pd-original-price {
        font-size: 1.25rem;
        color: #9ca3af;
        text-decoration: line-through;
      }

      .vv-pd-stock-status {
        margin-top: 0.5rem;
      }

      .vv-pd-stock-text {
        color: #059669;
        font-weight: 500;
        font-size: 0.9rem;
      }

      /* Description */
      .vv-pd-description {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
      }

      .vv-pd-description-text {
        color: #4b5563;
        line-height: 1.6;
        margin: 0;
      }

      /* Option Groups */
      .vv-pd-option-group {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
      }

      .vv-pd-option-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1a202c;
        margin-bottom: 1rem;
      }

      /* Color Options */
      .vv-pd-color-options {
        display: flex;
        gap: 0.75rem;
      }

      .vv-pd-color-item {
        cursor: pointer;
        padding: 3px;
        border: 2px solid transparent;
        border-radius: 50%;
      }

      .vv-pd-color-item.vv-pd-color-active {
        border-color: #3b82f6;
      }

      .vv-pd-color-swatch {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 1px solid #e5e7eb;
      }

      /* Size Options */
      .vv-pd-size-options {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
      }

      .vv-pd-size-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #d1d5db;
        background: white;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        color: #4b5563;
        cursor: pointer;
      }

      .vv-pd-size-btn:hover {
        border-color: #9ca3af;
      }

      .vv-pd-size-btn.vv-pd-size-active {
        border-color: #3b82f6;
        background: #3b82f6;
        color: white;
      }

      /* Quantity */
      .vv-pd-quantity-wrapper {
        display: flex;
        align-items: center;
        max-width: 140px;
      }

      .vv-pd-quantity-btn {
        width: 36px;
        height: 36px;
        border: 1px solid #d1d5db;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #4b5563;
      }

      .vv-pd-quantity-btn:first-child {
        border-radius: 6px 0 0 6px;
      }

      .vv-pd-quantity-btn:last-child {
        border-radius: 0 6px 6px 0;
      }

      .vv-pd-quantity-btn:hover {
        background: #f3f4f6;
      }

      .vv-pd-quantity-input {
        width: 60px;
        height: 36px;
        border: 1px solid #d1d5db;
        border-left: none;
        border-right: none;
        text-align: center;
        font-size: 0.9rem;
        outline: none;
      }

      /* Action Buttons */
      .vv-pd-action-buttons {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
      }

      .vv-pd-btn-primary {
        flex: 1;
        padding: 0.875rem 1.5rem;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
      }

      .vv-pd-btn-primary:hover {
        background: #2563eb;
      }

      .vv-pd-btn-secondary {
        flex: 1;
        padding: 0.875rem 1.5rem;
        background: white;
        color: #3b82f6;
        border: 1px solid #3b82f6;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
      }

      .vv-pd-btn-secondary:hover {
        background: #eff6ff;
      }

      /* Additional Actions */
      .vv-pd-additional-actions {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
      }

      .vv-pd-action-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: none;
        border: none;
        color: #6b7280;
        cursor: pointer;
        font-size: 0.9rem;
      }

      .vv-pd-action-item:hover {
        color: #3b82f6;
      }

      /* Features */
      .vv-pd-features {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
      }

      .vv-pd-feature-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .vv-pd-feature-icon {
        color: #059669;
        font-size: 1.1rem;
      }

      .vv-pd-feature-text {
        color: #4b5563;
        font-size: 0.9rem;
      }

      /* Tabs */
      .vv-pd-tabs-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      }

      .vv-pd-nav-tabs {
        border-bottom: 1px solid #e2e8f0;
        padding: 0 2rem;
      }

      .vv-pd-tab-link {
        border: none;
        color: #6b7280;
        font-weight: 500;
        padding: 1rem 1.5rem;
      }

      .vv-pd-tab-link.active {
        color: #3b82f6;
        border-bottom: 2px solid #3b82f6;
        background: none;
      }

      .vv-pd-tab-content {
        padding: 0;
      }

      .vv-pd-tab-pane {
        background: white;
      }

      .vv-pd-tab-inner {
        padding: 2rem;
      }

      .vv-pd-content-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1a202c;
        margin-bottom: 1rem;
      }

      .vv-pd-content-text {
        color: #4b5563;
        line-height: 1.6;
      }

      .vv-pd-feature-list {
        list-style: none;
        padding: 0;
        margin: 1rem 0 0 0;
      }

      .vv-pd-feature-list li {
        padding: 0.5rem 0;
        color: #4b5563;
        position: relative;
        padding-left: 1.5rem;
      }

      .vv-pd-feature-list li::before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #059669;
        font-weight: bold;
      }

      /* Reviews */
      .vv-pd-review-summary {
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #e2e8f0;
      }

      .vv-pd-rating-overview {
        text-align: center;
      }

      .vv-pd-rating-score {
        font-size: 3rem;
        font-weight: 700;
        color: #1a202c;
        line-height: 1;
      }

      .vv-pd-rating-stars {
        margin: 0.5rem 0;
        color: #fbbf24;
      }

      .vv-pd-review-count {
        color: #6b7280;
        font-size: 0.9rem;
      }

      .vv-pd-rating-breakdown {
        padding-left: 1rem;
      }

      .vv-pd-rating-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.5rem;
      }

      .vv-pd-rating-label {
        width: 60px;
        font-size: 0.9rem;
        color: #4b5563;
      }

      .vv-pd-rating-bar {
        flex: 1;
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
      }

      .vv-pd-rating-fill {
        height: 100%;
        background: #fbbf24;
      }

      .vv-pd-rating-percent {
        width: 40px;
        text-align: right;
        font-size: 0.9rem;
        color: #6b7280;
      }

      .vv-pd-write-review {
        margin-bottom: 2rem;
      }

      .vv-pd-btn-outline {
        padding: 0.75rem 1.5rem;
        background: white;
        color: #3b82f6;
        border: 1px solid #3b82f6;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .vv-pd-btn-outline:hover {
        background: #eff6ff;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
      }

      /* Loading Button Styles */
      .vv-pd-btn-outline.btn-loading {
        animation: pulse 1.5s infinite;
      }

      @keyframes pulse {
        0% {
          transform: scale(1);
        }
        50% {
          transform: scale(1.02);
        }
        100% {
          transform: scale(1);
        }
      }

      .vv-pd-btn-outline:disabled {
        cursor: not-allowed !important;
        opacity: 0.7 !important;
      }

      /* Spinner customization */
      .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.125em;
      }

      .vv-pd-reviews-list {
        margin-top: 1.5rem;
      }

      .vv-pd-review-item {
        padding: 1.5rem 0;
        border-bottom: 1px solid #e2e8f0;
      }

      .vv-pd-review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
      }

      .vv-pd-reviewer-info {
        flex: 1;
      }

      .vv-pd-reviewer-name {
        color: #1a202c;
        font-size: 1rem;
        margin-bottom: 0.25rem;
        display: block;
      }

      .vv-pd-review-stars {
        color: #fbbf24;
        font-size: 0.9rem;
      }

      .vv-pd-review-date {
        color: #9ca3af;
        font-size: 0.85rem;
      }

      .vv-pd-review-text {
        color: #4b5563;
        line-height: 1.6;
        margin: 0;
      }

      /* Shipping */
      .vv-pd-shipping-options {
        display: flex;
        flex-direction: column;
        gap: 1rem;
      }

      .vv-pd-shipping-item {
        padding: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
      }

      .vv-pd-shipping-item strong {
        color: #1a202c;
        display: block;
        margin-bottom: 0.25rem;
      }

      .vv-pd-shipping-item p {
        color: #6b7280;
        margin: 0;
        font-size: 0.9rem;
      }

      /* Seller Profile */
      .vv-pd-seller-info {
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
      }

      .vv-pd-seller-details h6 {
        color: #1a202c;
        font-weight: 600;
        margin-bottom: 0.5rem;
      }

      .vv-pd-seller-details p {
        color: #6b7280;
        margin-bottom: 1rem;
      }

      .vv-pd-seller-stats {
        display: flex;
        gap: 1.5rem;
      }

      .vv-pd-seller-stat {
        color: #059669;
        font-size: 0.9rem;
        font-weight: 500;
      }

      /* Footer */
      .vv-pd-footer {
        margin-top: 4rem;
      }

      /* Responsive Design */
      @media (max-width: 768px) {
        .vv-pd-main-section {
          padding: 1.5rem 0;
        }

        .vv-pd-image-container,
        .vv-pd-info-container {
          padding: 1.5rem;
          margin-bottom: 1.5rem;
        }

        .vv-pd-main-image {
          height: 300px;
        }

        .vv-pd-thumbnail-grid {
          grid-template-columns: repeat(3, 1fr);
        }

        .vv-pd-title {
          font-size: 1.5rem;
        }

        .vv-pd-current-price {
          font-size: 1.75rem;
        }

        .vv-pd-action-buttons {
          flex-direction: column;
        }

        .vv-pd-rating-breakdown {
          padding-left: 0;
          margin-top: 1rem;
        }

        .vv-pd-seller-stats {
          flex-direction: column;
          gap: 0.5rem;
        }

        .vv-pd-search-container {
          min-width: 200px;
        }
      }

      @media (max-width: 576px) {
        .vv-pd-thumbnail-grid {
          grid-template-columns: repeat(2, 1fr);
        }

        .vv-pd-size-options {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
        }

        .vv-pd-rating-section {
          flex-direction: column;
          align-items: flex-start;
          gap: 0.5rem;
        }

        .vv-pd-nav-tabs {
          padding: 0 1rem;
        }

        .vv-pd-tab-inner {
          padding: 1.5rem;
        }

        .vv-pd-search-container {
          min-width: 150px;
        }
      }
    </style>
    
    <!-- Product Detail Page JavaScript -->
    <script>
      // Enhanced function to load product data from sessionStorage
      function loadProductDataWithCartSupport() {
        // First check if coming from Add to Cart click
        let productData = sessionStorage.getItem('selectedProductFromCart');
        let isFromCart = false;
        
        if (productData) {
          isFromCart = true;
        } else {
          // Fallback to regular product selection
          productData = sessionStorage.getItem('selectedProduct');
        }
        
        if (productData) {
          const product = JSON.parse(productData);
          
          // Update product title
          const titleElement = document.getElementById('productTitle');
          if (titleElement && product.name) {
            titleElement.textContent = product.name;
          }
          
          // Update product price
          const priceElement = document.getElementById('productPrice');
          if (priceElement && product.price) {
            priceElement.textContent = product.price;
          }
          
          // Update original price if available
          if (product.originalPrice) {
            const originalPriceElement = document.querySelector('.original-price');
            if (originalPriceElement) {
              originalPriceElement.textContent = product.originalPrice;
            }
          }
          
          // Update product image
          const imageElement = document.getElementById('productImage');
          if (imageElement && product.image) {
            imageElement.src = product.image;
            imageElement.alt = product.name || 'Product Image';
          }
          
          // Update product description if available
          if (product.description) {
            const descriptionElement = document.querySelector('.product-description');
            if (descriptionElement) {
              descriptionElement.textContent = product.description;
            }
          }
          
          // If this came from Add to Cart, show a special message
          if (isFromCart && product.isFromCart) {
            showCartAddedNotification(product.name);
          }
        }
      }
      
      // Function to show notification that item was added from cart
      function showCartAddedNotification(productName) {
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show';
        notification.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          z-index: 9999;
          max-width: 350px;
        `;
        notification.innerHTML = `
          <i class="bx bx-info-circle me-2"></i>
          <strong>${productName}</strong> was added to your cart!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
          if (notification && notification.parentNode) {
            notification.remove();
          }
        }, 4000);
      }
      
      // Back to index function  
      function goBackToIndex() {
        // Clear the cart-specific data when going back
        sessionStorage.removeItem('selectedProductFromCart');
        window.location.href = 'index.php';
      }
      
      // Load product data when page loads
      document.addEventListener('DOMContentLoaded', loadProductDataWithCartSupport);
    </script>
  </body>
</html>

