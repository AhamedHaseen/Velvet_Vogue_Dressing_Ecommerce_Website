<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Settings - Velvet Vogue</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Crimson+Text:wght@400;600;700&family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

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

    <!-- Custom CSS -->
    <link rel="stylesheet" href="./style.css" />
  </head>

  <body>
    <!-- Navigation Bar -->
    <nav
      class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm"
    >
      <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold text-primary" href="index.php">
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
                  <a class="dropdown-item active" href="./setting.php">
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
                <li class="breadcrumb-item active" aria-current="page">
                  Settings
                </li>
              </ol>
            </nav>

            <!-- Page Title -->
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <h4 class="fw-bold text-dark mb-0 me-3">
                  <i class="bx bx-cog me-2 text-primary"></i>Settings
                </h4>
                <p class="text-muted mb-0">
                  Customize your Velvet Vogue experience
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Settings Dashboard -->
    <section class="py-5">
      <div class="container">
        <div class="row g-4">
          <!-- Settings Navigation -->
          <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                  <i class="bx bx-cog me-2"></i>Settings Menu
                </h6>
              </div>
              <div class="card-body p-0">
                <div class="list-group list-group-flush">
                  <a
                    href="#appearance"
                    class="list-group-item list-group-item-action active"
                    data-tab="appearance"
                  >
                    <i class="bx bx-palette me-2"></i>Appearance
                  </a>
                  <a
                    href="#notifications"
                    class="list-group-item list-group-item-action"
                    data-tab="notifications"
                  >
                    <i class="bx bx-bell me-2"></i>Notifications
                  </a>
                  <a
                    href="#privacy"
                    class="list-group-item list-group-item-action"
                    data-tab="privacy"
                  >
                    <i class="bx bx-shield me-2"></i>Privacy & Security
                  </a>
                  <a
                    href="#shopping"
                    class="list-group-item list-group-item-action"
                    data-tab="shopping"
                  >
                    <i class="bx bx-shopping-bag me-2"></i>Shopping Preferences
                  </a>
                  <a
                    href="#language"
                    class="list-group-item list-group-item-action"
                    data-tab="language"
                  >
                    <i class="bx bx-globe me-2"></i>Language & Region
                  </a>
                  <a
                    href="#accessibility"
                    class="list-group-item list-group-item-action"
                    data-tab="accessibility"
                  >
                    <i class="bx bx-accessibility me-2"></i>Accessibility
                  </a>
                  <a
                    href="#data"
                    class="list-group-item list-group-item-action"
                    data-tab="data"
                  >
                    <i class="bx bx-data me-2"></i>Data Management
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Settings Content -->
          <div class="col-lg-9">
            <!-- Appearance Settings Tab -->
            <div class="tab-content-area" id="appearance">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                  <h5 class="mb-0">
                    <i class="bx bx-palette me-2 text-primary"></i>Appearance
                    Settings
                  </h5>
                </div>
                <div class="card-body">
                  <!-- Theme Selection -->
                  <div class="row g-4">
                    <div class="col-12">
                      <h6 class="fw-bold mb-3">
                        <i class="bx bx-brush me-2"></i>Theme Preferences
                      </h6>

                      <!-- Theme Options -->
                      <div class="row g-3 justify-content-center">
                        <div class="col-md-6 col-lg-4">
                          <div class="theme-option" data-theme="light">
                            <div
                              class="theme-preview-card bg-light border rounded p-3 text-center"
                            >
                              <i class="bx bx-sun fs-2 text-warning mb-2"></i>
                              <h6 class="fw-bold">Light Mode</h6>
                              <p class="small text-muted mb-3">
                                Clean and bright interface
                              </p>
                              <div class="form-check">
                                <input
                                  class="form-check-input"
                                  type="radio"
                                  name="themeOption"
                                  id="lightTheme"
                                  value="light"
                                  checked
                                  disabled
                                />
                                <label
                                  class="form-check-label"
                                  for="lightTheme"
                                >
                                  Active Theme
                                </label>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Display Settings -->
                    <div class="col-12">
                      <hr />
                      <h6 class="fw-bold mb-3">
                        <i class="bx bx-desktop me-2"></i>Display Settings
                      </h6>

                      <div class="row g-3">
                        <div class="col-md-6">
                          <label for="fontSize" class="form-label fw-semibold">
                            <i class="bx bx-font-size me-2"></i>Font Size
                          </label>
                          <select class="form-select" id="fontSize">
                            <option value="small">Small</option>
                            <option value="medium" selected>Medium</option>
                            <option value="large">Large</option>
                            <option value="extra-large">Extra Large</option>
                          </select>
                        </div>

                        <div class="col-md-6">
                          <label
                            for="colorScheme"
                            class="form-label fw-semibold"
                          >
                            <i class="bx bx-color me-2"></i>Accent Color
                          </label>
                          <select class="form-select" id="colorScheme">
                            <option value="blue" selected>Blue</option>
                            <option value="purple">Purple</option>
                            <option value="green">Green</option>
                            <option value="red">Red</option>
                            <option value="orange">Orange</option>
                          </select>
                        </div>
                      </div>
                    </div>

                    <!-- Animation Settings -->
                    <div class="col-12">
                      <hr />
                      <h6 class="fw-bold mb-3">
                        <i class="bx bx-movie me-2"></i>Animation & Effects
                      </h6>

                      <div class="row g-3">
                        <div class="col-md-6">
                          <div class="form-check form-switch">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="enableAnimations"
                              checked
                            />
                            <label
                              class="form-check-label"
                              for="enableAnimations"
                            >
                              <i class="bx bx-play-circle me-2"></i>Enable
                              Animations
                            </label>
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-check form-switch">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="reduceMotion"
                            />
                            <label class="form-check-label" for="reduceMotion">
                              <i class="bx bx-pause-circle me-2"></i>Reduce
                              Motion
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Save Settings Button -->
                    <div class="col-12">
                      <hr />
                      <div class="d-flex justify-content-between">
                        <button
                          class="btn btn-outline-secondary"
                          id="resetAppearance"
                        >
                          <i class="bx bx-refresh me-2"></i>Reset to Default
                        </button>
                        <button class="btn btn-primary" id="saveAppearance">
                          <i class="bx bx-save me-2"></i>Save Appearance
                          Settings
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notifications Settings Tab -->
            <div class="tab-content-area d-none" id="notifications">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                  <h5 class="mb-0">
                    <i class="bx bx-bell me-2 text-primary"></i>Notification
                    Settings
                  </h5>
                </div>
                <div class="card-body">
                  <div class="row g-4">
                    <!-- Email Notifications -->
                    <div class="col-12">
                      <h6 class="fw-bold mb-3">
                        <i class="bx bx-envelope me-2"></i>Email Notifications
                      </h6>

                      <div class="notification-group">
                        <div
                          class="d-flex justify-content-between align-items-center mb-2"
                        >
                          <div>
                            <strong>Order Updates</strong>
                            <p class="small text-muted mb-0">
                              Notifications about your order status
                            </p>
                          </div>
                          <div class="form-check form-switch">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="orderUpdates"
                              checked
                            />
                          </div>
                        </div>

                        <div
                          class="d-flex justify-content-between align-items-center mb-2"
                        >
                          <div>
                            <strong>Promotional Offers</strong>
                            <p class="small text-muted mb-0">
                              Special deals and discounts
                            </p>
                          </div>
                          <div class="form-check form-switch">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="promoOffers"
                              checked
                            />
                          </div>
                        </div>

                        <div
                          class="d-flex justify-content-between align-items-center mb-2"
                        >
                          <div>
                            <strong>Newsletter</strong>
                            <p class="small text-muted mb-0">
                              Weekly fashion updates and tips
                            </p>
                          </div>
                          <div class="form-check form-switch">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="newsletter"
                              checked
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Push Notifications -->
                    <div class="col-12">
                      <hr />
                      <h6 class="fw-bold mb-3">
                        <i class="bx bx-mobile me-2"></i>Push Notifications
                      </h6>

                      <div class="notification-group">
                        <div
                          class="d-flex justify-content-between align-items-center mb-2"
                        >
                          <div>
                            <strong>Browser Notifications</strong>
                            <p class="small text-muted mb-0">
                              Real-time updates in your browser
                            </p>
                          </div>
                          <div class="form-check form-switch">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="browserNotifications"
                            />
                          </div>
                        </div>

                        <div
                          class="d-flex justify-content-between align-items-center mb-2"
                        >
                          <div>
                            <strong>New Arrivals</strong>
                            <p class="small text-muted mb-0">
                              Alert when new products arrive
                            </p>
                          </div>
                          <div class="form-check form-switch">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="newArrivals"
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Save Button -->
                    <div class="col-12">
                      <hr />
                      <button class="btn btn-primary">
                        <i class="bx bx-save me-2"></i>Save Notification
                        Settings
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Privacy & Security Tab -->
            <div class="tab-content-area d-none" id="privacy">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                  <h5 class="mb-0">
                    <i class="bx bx-shield me-2 text-primary"></i>Privacy &
                    Security
                  </h5>
                </div>
                <div class="card-body">
                  <div class="row g-4">
                    <div class="col-12">
                      <h6 class="fw-bold mb-3">
                        <i class="bx bx-lock me-2"></i>Security Settings
                      </h6>

                      <div class="security-options">
                        <div
                          class="d-flex justify-content-between align-items-center mb-3"
                        >
                          <div>
                            <strong>Two-Factor Authentication</strong>
                            <p class="small text-muted mb-0">
                              Add an extra layer of security
                            </p>
                          </div>
                          <button class="btn btn-outline-primary btn-sm">
                            Enable
                          </button>
                        </div>

                        <div
                          class="d-flex justify-content-between align-items-center mb-3"
                        >
                          <div>
                            <strong>Login Alerts</strong>
                            <p class="small text-muted mb-0">
                              Get notified of new login attempts
                            </p>
                          </div>
                          <div class="form-check form-switch">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="loginAlerts"
                              checked
                            />
                          </div>
                        </div>

                        <div
                          class="d-flex justify-content-between align-items-center"
                        >
                          <div>
                            <strong>Password Change</strong>
                            <p class="small text-muted mb-0">
                              Update your account password
                            </p>
                          </div>
                          <button class="btn btn-outline-secondary btn-sm">
                            Change Password
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Other tabs content (simplified for brevity) -->
            <div class="tab-content-area d-none" id="shopping">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                  <h5 class="mb-0">
                    <i class="bx bx-shopping-bag me-2 text-primary"></i>Shopping
                    Preferences
                  </h5>
                </div>
                <div class="card-body">
                  <p class="text-muted">
                    Configure your shopping preferences and saved payment
                    methods.
                  </p>
                </div>
              </div>
            </div>

            <div class="tab-content-area d-none" id="language">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                  <h5 class="mb-0">
                    <i class="bx bx-globe me-2 text-primary"></i>Language &
                    Region
                  </h5>
                </div>
                <div class="card-body">
                  <p class="text-muted">
                    Set your preferred language and regional settings.
                  </p>
                </div>
              </div>
            </div>

            <div class="tab-content-area d-none" id="accessibility">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                  <h5 class="mb-0">
                    <i class="bx bx-accessibility me-2 text-primary"></i
                    >Accessibility
                  </h5>
                </div>
                <div class="card-body">
                  <p class="text-muted">
                    Customize accessibility features to improve your experience.
                  </p>
                </div>
              </div>
            </div>

            <div class="tab-content-area d-none" id="data">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                  <h5 class="mb-0">
                    <i class="bx bx-data me-2 text-primary"></i>Data Management
                  </h5>
                </div>
                <div class="card-body">
                  <p class="text-muted">
                    Manage your data, export information, or delete your
                    account.
                  </p>
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
                <a
                  href="contact.php"
                  class="text-light-emphasis text-decoration-none"
                >
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

              <form class="mb-3" id="footerNewsletterForm">
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

    <!-- Settings Page JavaScript -->
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // Light mode only - removed theme management

        // Tab navigation
        const tabLinks = document.querySelectorAll(
          ".list-group-item[data-tab]"
        );
        const tabContents = document.querySelectorAll(".tab-content-area");

        tabLinks.forEach((link) => {
          link.addEventListener("click", function (e) {
            e.preventDefault();

            // Remove active class from all tabs
            tabLinks.forEach((tab) => tab.classList.remove("active"));
            tabContents.forEach((content) => content.classList.add("d-none"));

            // Add active class to clicked tab
            this.classList.add("active");

            // Show corresponding content
            const targetTab = this.getAttribute("data-tab");
            document.getElementById(targetTab).classList.remove("d-none");
          });
        });

        // Save appearance settings
        document
          .getElementById("saveAppearance")
          .addEventListener("click", function () {
            const fontSize = document.getElementById("fontSize").value;
            const colorScheme = document.getElementById("colorScheme").value;
            const enableAnimations =
              document.getElementById("enableAnimations").checked;
            const reduceMotion =
              document.getElementById("reduceMotion").checked;

            // Save to localStorage
            localStorage.setItem("fontSize", fontSize);
            localStorage.setItem("colorScheme", colorScheme);
            localStorage.setItem("enableAnimations", enableAnimations);
            localStorage.setItem("reduceMotion", reduceMotion);

            // Apply settings
            document.body.style.fontSize =
              fontSize === "small"
                ? "14px"
                : fontSize === "large"
                ? "18px"
                : fontSize === "extra-large"
                ? "20px"
                : "16px";

            // Show success message
            this.innerHTML = '<i class="bx bx-check me-2"></i>Settings Saved!';
            this.classList.remove("btn-primary");
            this.classList.add("btn-success");

            setTimeout(() => {
              this.innerHTML =
                '<i class="bx bx-save me-2"></i>Save Appearance Settings';
              this.classList.remove("btn-success");
              this.classList.add("btn-primary");
            }, 2000);
          });

        // Reset appearance settings
        document
          .getElementById("resetAppearance")
          .addEventListener("click", function () {
            if (
              confirm(
                "Are you sure you want to reset all appearance settings to default?"
              )
            ) {
              // Reset form values
              document.getElementById("fontSize").value = "medium";
              document.getElementById("colorScheme").value = "blue";
              document.getElementById("enableAnimations").checked = true;
              document.getElementById("reduceMotion").checked = false;

              // Clear localStorage
              localStorage.removeItem("fontSize");
              localStorage.removeItem("colorScheme");
              localStorage.removeItem("enableAnimations");
              localStorage.removeItem("reduceMotion");

              alert("Appearance settings have been reset to default.");
            }
          });

        // Load saved settings
        const savedFontSize = localStorage.getItem("fontSize");
        const savedColorScheme = localStorage.getItem("colorScheme");
        const savedAnimations = localStorage.getItem("enableAnimations");
        const savedReduceMotion = localStorage.getItem("reduceMotion");

        if (savedFontSize)
          document.getElementById("fontSize").value = savedFontSize;
        if (savedColorScheme)
          document.getElementById("colorScheme").value = savedColorScheme;
        if (savedAnimations)
          document.getElementById("enableAnimations").checked =
            savedAnimations === "true";
        if (savedReduceMotion)
          document.getElementById("reduceMotion").checked =
            savedReduceMotion === "true";

        // Newsletter form
        const newsletterForm = document.getElementById("footerNewsletterForm");
        if (newsletterForm) {
          newsletterForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            if (email) {
              alert("Thank you for subscribing to our newsletter!");
              this.reset();
            }
          });
        }
      });
    </script>

    <!-- Custom CSS for Settings Page -->
    <style>
      /* Theme option cards */
      .theme-option {
        cursor: pointer;
        transition: transform 0.3s ease;
      }

      .theme-option:hover {
        transform: scale(1.02);
      }

      .theme-preview-card {
        transition: all 0.3s ease;
        min-height: 180px;
      }

      .theme-preview-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
      }

      /* Settings navigation */
      .list-group-item {
        border: none;
        border-radius: 0;
        transition: all 0.3s ease;
      }

      .list-group-item:hover {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
      }

      .list-group-item.active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
      }

      /* Animation utilities */
      .fade-in {
        animation: fadeIn 0.5s ease-in;
      }

      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      /* Notification toggle switches */
      .notification-group .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
      }

      /* Security options */
      .security-options .btn-outline-primary:hover {
        color: #fff;
        background-color: #0d6efd;
        border-color: #0d6efd;
      }

      /* Responsive design */
      @media (max-width: 768px) {
        .theme-preview-card {
          min-height: 150px;
        }

        .d-flex.justify-content-between {
          flex-direction: column;
          gap: 1rem;
        }
      }

      /* Theme transition */
      * {
        transition: background-color 0.3s ease, color 0.3s ease,
          border-color 0.3s ease;
      }
    </style>
  </body>
</html>

