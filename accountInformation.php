<?php
// Include common user authentication
include 'includes/auth_user.php';
$success_message = "";
$error_message = "";

// Handle form submission for updating user information
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['firstName'] ?? '');
    $last_name = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    
    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email is already taken by another user
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists. Please use a different email address.";
        }
    }
    
    // Update user information if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
        
        if ($stmt === false) {
            $error_message = "Database prepare error: " . $conn->error;
        } else {
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Profile updated successfully!";
                // Update session variables
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
            } else {
                $error_message = "Update failed: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error_message = implode(", ", $errors);
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Set default values if not available
$user_data['date_of_birth'] = $user_data['date_of_birth'] ?? '';
$user_data['address'] = $user_data['address'] ?? '';
$user_data['city'] = $user_data['city'] ?? '';
$user_data['state'] = $user_data['state'] ?? '';
$user_data['zip_code'] = $user_data['zip_code'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Account Information - Velvet Vogue</title>
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

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                0
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
                  <a
                    class="dropdown-item active"
                    href="./accountInformation.php"
                  >
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
                  <a class="dropdown-item text-danger" href="./logout.php">
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
                  Account Information
                </li>
              </ol>
            </nav>

            <!-- Page Title -->
            <div class="d-flex align-items-center">
              <h4 class="fw-bold text-dark mb-0 me-3">
                <i class="bx bx-user-circle me-2 text-primary"></i>Account
                Information
              </h4>
              <span class="badge bg-success rounded-pill">
                <i class="bx bx-shield-check me-1"></i>Verified Account
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Account Dashboard -->
    <section class="py-5">
      <div class="container">
        <div class="row g-4">
          <!-- Sidebar Navigation -->
          <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                  <i class="bx bx-user me-2"></i>Account Dashboard
                </h6>
              </div>
              <div class="card-body p-0">
                <div class="list-group list-group-flush">
                  <a
                    href="#personal-info"
                    class="list-group-item list-group-item-action active"
                    data-tab="personal-info"
                  >
                    <i class="bx bx-user me-2"></i>Personal Information
                  </a>
                </div>
              </div>
            </div>

            <!-- Account Summary Card -->
            <div class="card border-0 shadow-sm mt-4">
              <div class="card-body text-center">
                <div class="profile-avatar mb-3">
                  <img
                    src="./Images/si_1.jpg"
                    alt="Profile Picture"
                    class="rounded-circle img-fluid"
                    style="width: 80px; height: 80px; object-fit: cover"
                    id="profileImage"
                  />
                </div>
                <h6 class="fw-bold mb-1" id="displayName"><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h6>
                <p class="text-muted small mb-2" id="displayEmail">
                  <?php echo htmlspecialchars($user_data['email']); ?>
                </p>
                <div class="d-flex justify-content-center gap-2">
                  <span class="badge bg-primary">Premium Member</span>
                  <span class="badge bg-success">Active</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Content Area -->
          <div class="col-lg-9">
            <!-- Personal Information Tab -->
            <div class="tab-content-area" id="personal-info">
              <div class="card border-0 shadow-sm">
                <div
                  class="card-header bg-light d-flex justify-content-between align-items-center"
                >
                  <h5 class="mb-0">
                    <i class="bx bx-edit me-2 text-primary"></i>Personal
                    Information
                  </h5>
                </div>
                <div class="card-body">
                  <!-- Success/Error Messages -->
                  <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <i class="bx bx-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <i class="bx bx-error-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                  <?php endif; ?>

                  <form id="personalInfoForm" method="POST" action="".>
                    <div class="row g-3">
                      <!-- Profile Picture Upload -->
                      <div class="col-12">
                        <div class="profile-upload-section text-center mb-4">
                          <div
                            class="profile-picture-wrapper position-relative d-inline-block"
                          >
                            <img
                              src="./Images/si_1.jpg"
                              alt="Profile Picture"
                              class="rounded-circle img-fluid border"
                              style="
                                width: 120px;
                                height: 120px;
                                object-fit: cover;
                              "
                              id="profilePicturePreview"
                            />
                            <button
                              type="button"
                              class="btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle"
                              style="width: 35px; height: 35px"
                              id="changePhotoBtn"
                              disabled
                            >
                              <i class="bx bx-camera"></i>
                            </button>
                          </div>
                          <input
                            type="file"
                            id="profilePictureInput"
                            class="d-none"
                            accept="image/*"
                          />
                        </div>
                      </div>

                      <!-- Basic Information -->
                      <div class="col-md-6">
                        <label for="firstName" class="form-label fw-semibold">
                          <i class="bx bx-user me-2 text-primary"></i>First Name
                          <span class="text-danger">*</span>
                        </label>
                        <input
                          type="text"
                          class="form-control"
                          id="firstName"
                          name="firstName"
                          value="<?php echo htmlspecialchars($user_data['first_name']); ?>"
                          required
                        />
                      </div>
                      <div class="col-md-6">
                        <label for="lastName" class="form-label fw-semibold">
                          <i class="bx bx-user me-2 text-primary"></i>Last Name
                          <span class="text-danger">*</span>
                        </label>
                        <input
                          type="text"
                          class="form-control"
                          id="lastName"
                          name="lastName"
                          value="<?php echo htmlspecialchars($user_data['last_name']); ?>"
                          required
                        />
                      </div>

                      <!-- Contact Information -->
                      <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">
                          <i class="bx bx-envelope me-2 text-primary"></i>Email
                          Address
                          <span class="text-danger">*</span>
                        </label>
                        <input
                          type="email"
                          class="form-control"
                          id="email"
                          name="email"
                          value="<?php echo htmlspecialchars($user_data['email']); ?>"
                          required
                        />
                      </div>
                      <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">
                          <i class="bx bx-phone me-2 text-primary"></i>Phone
                          Number
                        </label>
                        <input
                          type="tel"
                          class="form-control"
                          id="phone"
                          name="phone"
                          value="<?php echo htmlspecialchars($user_data['phone']); ?>"
                        />
                      </div>

                    </div>

                    <!-- Action Buttons -->
                    <div
                      class="d-flex justify-content-between mt-4"
                      id="formActions"
                    >
                      <button
                        type="button"
                        class="btn btn-outline-secondary"
                        id="cancelEditBtn"
                      >
                        <i class="bx bx-x me-2"></i>Cancel
                      </button>
                      <div>
                        <button
                          type="button"
                          class="btn btn-outline-primary me-2"
                          id="previewChangesBtn"
                        >
                          <i class="bx bx-show me-2"></i>Preview Changes
                        </button>
                        <button
                          type="submit"
                          class="btn btn-primary"
                          id="saveChangesBtn"
                        >
                          <span class="save-text">
                            <i class="bx bx-save me-2"></i>Save Changes
                          </span>
                          <div
                            class="spinner-border spinner-border-sm ms-2 d-none"
                            id="saveSpinner"
                          >
                            <span class="visually-hidden">Saving...</span>
                          </div>
                        </button>
                      </div>
                    </div>
                  </form>
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
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Account Information JavaScript -->
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // Wrap in try-catch to prevent external errors from breaking our code
        try {
          // Show SweetAlert for PHP messages
          <?php if (!empty($success_message)): ?>
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: '<?php echo addslashes($success_message); ?>',
              showConfirmButton: false,
              timer: 3000,
              timerProgressBar: true,
              position: 'top-end',
              toast: true,
              background: '#d1edff',
              iconColor: '#198754'
            });
          <?php endif; ?>

          <?php if (!empty($error_message)): ?>
            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: '<?php echo addslashes($error_message); ?>',
              confirmButtonText: 'OK',
              confirmButtonColor: '#dc3545'
            });
          <?php endif; ?>
        } catch(e) {
          console.log('SweetAlert error:', e);
        }

        // Tab navigation
        try {
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

        // ULTRA SIMPLE Edit functionality - Direct approach
        console.log('Setting up edit functionality...');
        
        setTimeout(function() {
          const editBtn = document.getElementById("editPersonalBtn");
          
          if (editBtn) {
            editBtn.onclick = function() {
              console.log('EDIT BUTTON CLICKED!');
              
              // Get fields directly
              const f1 = document.getElementById("firstName");
              const f2 = document.getElementById("lastName"); 
              const f3 = document.getElementById("email");
              const f4 = document.getElementById("phone");
              const actions = document.getElementById("formActions");
              
              if (f1 && f1.disabled) {
                // ENABLE
                f1.disabled = false; f1.style.border = '2px solid blue';
                f2.disabled = false; f2.style.border = '2px solid blue';
                f3.disabled = false; f3.style.border = '2px solid blue';
                f4.disabled = false; f4.style.border = '2px solid blue';
                
                editBtn.innerHTML = 'Cancel Edit';
                editBtn.style.backgroundColor = 'red';
                editBtn.style.color = 'white';
                
                if (actions) actions.style.display = 'flex';
                console.log('ENABLED!');
              } else {
                // DISABLE
                f1.disabled = true; f1.style.border = '1px solid #ccc';
                f2.disabled = true; f2.style.border = '1px solid #ccc';
                f3.disabled = true; f3.style.border = '1px solid #ccc';
                f4.disabled = true; f4.style.border = '1px solid #ccc';
                
                editBtn.innerHTML = 'Edit Details';
                editBtn.style.backgroundColor = '';
                editBtn.style.color = '';
                
                if (actions) actions.style.display = 'none';
                console.log('DISABLED!');
              }
            };
            console.log('Edit button setup complete');
          } else {
            console.error('No edit button found!');
          }
        }, 1000);

        // Cancel button functionality
        const cancelBtn = document.getElementById("cancelEditBtn");
        if (cancelBtn) {
          cancelBtn.addEventListener("click", function(e) {
            e.preventDefault();
            console.log('Cancel button clicked!');
            
            // Disable all fields
            const firstName = document.getElementById("firstName");
            const lastName = document.getElementById("lastName");
            const email = document.getElementById("email");
            const phone = document.getElementById("phone");
            
            if (firstName) firstName.disabled = true;
            if (lastName) lastName.disabled = true;
            if (email) email.disabled = true;
            if (phone) phone.disabled = true;
            
            // Reset edit button
            if (editBtn) {
              editBtn.innerHTML = '<i class="bx bx-edit me-1"></i>Edit Details';
              editBtn.classList.remove("btn-outline-danger");
              editBtn.classList.add("btn-outline-primary");
            }
            
            // Hide save buttons
            if (formActions) {
              formActions.style.display = "none";
            }
            
            console.log('Edit cancelled');
          });
        }

        // Profile picture change
        changePhotoBtn.addEventListener("click", function () {
          if (!this.disabled) {
            profilePictureInput.click();
          }
        });

        profilePictureInput.addEventListener("change", function (e) {
          const file = e.target.files[0];
          if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
              profilePicturePreview.src = e.target.result;
              document.getElementById("profileImage").src = e.target.result;
            };
            reader.readAsDataURL(file);
          }
        });

        // Preview changes
        previewBtn.addEventListener("click", function () {
          const changes = [];

          formFields.forEach((fieldId) => {
            const field = document.getElementById(fieldId);
            if (field && originalValues.hasOwnProperty(fieldId)) {
              let currentValue =
                field.type === "checkbox" ? field.checked : field.value;
              let originalValue = originalValues[fieldId];

              if (currentValue !== originalValue) {
                const fieldLabel =
                  field.previousElementSibling?.textContent?.replace("*", "") ||
                  fieldId;
                changes.push(
                  `<strong>${fieldLabel}:</strong> "${originalValue}" → "${currentValue}"`
                );
              }
            }
          });

          if (changes.length > 0) {
            Swal.fire({
              icon: 'info',
              title: 'Preview Changes',
              html: '<div class="text-start">' + changes.join('<br>') + '</div>',
              width: '600px',
              confirmButtonText: 'Got it',
              confirmButtonColor: '#0d6efd'
            });
          } else {
            Swal.fire({
              icon: 'info',
              title: 'No Changes',
              text: 'No changes detected in your profile information.',
              confirmButtonColor: '#6c757d'
            });
          }
        });

        // Form submission
        personalForm.addEventListener("submit", function (e) {
          e.preventDefault();

          // Validation
          const firstName = document.getElementById("firstName").value.trim();
          const lastName = document.getElementById("lastName").value.trim();
          const email = document.getElementById("email").value.trim();

          if (!firstName || !lastName || !email) {
            Swal.fire({
              icon: 'warning',
              title: 'Missing Information',
              text: 'Please fill in all required fields (First Name, Last Name, Email).',
              confirmButtonColor: '#ffc107'
            });
            return;
          }

          // Confirmation dialog
          Swal.fire({
            icon: 'question',
            title: 'Save Changes?',
            text: 'Are you sure you want to update your profile information?',
            showCancelButton: true,
            confirmButtonText: 'Yes, Save Changes',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
          }).then((result) => {
            if (result.isConfirmed) {
              // Show loading alert
              Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we update your information.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                  Swal.showLoading();
                }
              });

              // Submit the actual form
              personalForm.removeEventListener("submit", arguments.callee);
              personalForm.submit();
            }
          });
        });
        });

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

        // Form validation
        const inputs = personalForm.querySelectorAll(
          "input[required], select[required]"
        );
        inputs.forEach((input) => {
          input.addEventListener("blur", function () {
            if (!this.disabled && !this.value.trim()) {
              this.classList.add("is-invalid");
            } else {
              this.classList.remove("is-invalid");
              this.classList.add("is-valid");
            }
          });

          input.addEventListener("input", function () {
            if (this.classList.contains("is-invalid") && this.value.trim()) {
              this.classList.remove("is-invalid");
              this.classList.add("is-valid");
            }
          });
        });
      });
    </script>

    <!-- Custom CSS for Account Page -->
    <style>
      .profile-picture-wrapper {
        transition: transform 0.3s ease;
      }

      .profile-picture-wrapper:hover {
        transform: scale(1.05);
      }

      .address-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }

      .address-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
      }

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

      .form-control:disabled,
      .form-select:disabled {
        background-color: #f8f9fa;
        opacity: 0.8;
      }

      .form-control:focus,
      .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
      }

      .form-control.is-valid,
      .form-select.is-valid {
        border-color: #198754;
      }

      .form-control.is-invalid,
      .form-select.is-invalid {
        border-color: #dc3545;
      }

      .btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
      }

      .profile-upload-section {
        background: rgba(13, 110, 253, 0.05);
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
      }

      @media (max-width: 768px) {
        .profile-upload-section {
          padding: 1rem;
        }

        .d-flex.justify-content-between {
          flex-direction: column;
          gap: 1rem;
        }

        .d-flex.justify-content-between > div {
          display: flex;
          gap: 0.5rem;
        }
      }
    </style>
  </body>
</html>

