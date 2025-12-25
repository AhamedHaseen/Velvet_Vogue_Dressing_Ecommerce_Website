<?php
session_start();
include "db_connection.php";

$success_message = '';
$error_message = '';

// Check for success parameter from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = 'Thank you for contacting us! We have received your message and will get back to you within 24 hours.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['submit_contact']) || isset($_POST['firstName']))) {
    $first_name = trim($_POST['firstName'] ?? '');
    $last_name = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';
    $preferred_contact = $_POST['preferredContact'] ?? 'email';
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $agree_terms = isset($_POST['agreeTerms']) ? 1 : 0;
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (!$agree_terms) {
        $error_message = 'Please agree to the Terms of Service and Privacy Policy.';
        } else {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO contacts (first_name, last_name, email, phone, subject, category, message, priority, preferred_contact, newsletter_subscription, notifications_enabled, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')");
            
            if ($stmt) {
                $stmt->bind_param("sssssssssii", $first_name, $last_name, $email, $phone, $subject, $category, $message, $priority, $preferred_contact, $newsletter, $notifications);
                
                if ($stmt->execute()) {
                    $success_message = 'Thank you for contacting us! We have received your message and will get back to you within 24 hours.';
                    // Redirect to prevent form resubmission on refresh
                    header("Location: contact.php?success=1");
                    exit();
                } else {
                    $error_message = 'Sorry, there was an error sending your message. Please try again later.';
                }
                $stmt->close();
            } else {
                $error_message = 'Database error. Please try again later.';
            }
        }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Contact Us - Velvet Vogue</title>
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
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  </head>

  <body>
    <!-- Navigation Bar (Same as Index Page) -->
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
              <a class="nav-link active" href="./contact.php">
                <i class="bx bx-phone me-1"></i>Contact
              </a>
            </li>
          </ul>

          <!-- Right Side Items -->
          <div class="d-flex align-items-center ms-3">
            <!-- Favorites -->
            <a
              href="./cart.php"
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

    <!-- Page Header -->
    <section class="bg-light py-5">
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
                  Contact
                </li>
              </ol>
            </nav>

            <!-- Page Title -->
            <div class="text-center">
              <h1 class="display-4 fw-bold text-dark mb-3">
                <i class="bx bx-phone me-3 text-primary"></i>Contact Us
              </h1>
              <p class="lead text-muted mb-0">
                We'd love to hear from you. Get in touch with our team!
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Information Section -->
    <section class="py-5">
      <div class="container">
        <div class="row g-4 mb-5">
          <!-- Contact Info Cards -->
          <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm contact-card">
              <div class="card-body text-center p-4">
                <div class="contact-icon mb-3">
                  <i class="bx bx-phone fs-1 text-primary"></i>
                </div>
                <h5 class="fw-bold mb-3">Phone Support</h5>
                <p class="text-muted mb-3">
                  Call us directly for immediate assistance
                </p>
                <div class="contact-details">
                  <p class="mb-2">
                    <strong>Customer Service:</strong><br />
                    <a href="tel:+15551234567" class="text-decoration-none">
                      +1 (555) 123-4567
                    </a>
                  </p>
                  <p class="mb-0">
                    <strong>Sales Inquiries:</strong><br />
                    <a href="tel:+15551234568" class="text-decoration-none">
                      +1 (555) 123-4568
                    </a>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm contact-card">
              <div class="card-body text-center p-4">
                <div class="contact-icon mb-3">
                  <i class="bx bx-envelope fs-1 text-primary"></i>
                </div>
                <h5 class="fw-bold mb-3">Email Support</h5>
                <p class="text-muted mb-3">
                  Send us an email and we'll respond within 24 hours
                </p>
                <div class="contact-details">
                  <p class="mb-2">
                    <strong>General Inquiries:</strong><br />
                    <a
                      href="mailto:info@velvetvogue.com"
                      class="text-decoration-none"
                    >
                      info@velvetvogue.com
                    </a>
                  </p>
                  <p class="mb-0">
                    <strong>Support:</strong><br />
                    <a
                      href="mailto:support@velvetvogue.com"
                      class="text-decoration-none"
                    >
                      support@velvetvogue.com
                    </a>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-12">
            <div class="card h-100 border-0 shadow-sm contact-card">
              <div class="card-body text-center p-4">
                <div class="contact-icon mb-3">
                  <i class="bx bx-map fs-1 text-primary"></i>
                </div>
                <h5 class="fw-bold mb-3">Visit Our Store</h5>
                <p class="text-muted mb-3">
                  Come see our latest collections in person
                </p>
                <div class="contact-details">
                  <p class="mb-2">
                    <strong>Address:</strong><br />
                    123 Fashion Street<br />
                    Style City, SC 12345
                  </p>
                  <p class="mb-0">
                    <strong>Hours:</strong><br />
                    Mon-Fri: 9:00 AM - 8:00 PM<br />
                    Sat-Sun: 10:00 AM - 6:00 PM
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Form Section -->
    <section class="py-5 bg-light">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-8 col-xl-7">
            <!-- Contact Form Card -->
            <div class="card border-0 shadow-lg">
              <div class="card-header bg-primary text-white py-4">
                <div class="text-center">
                  <h3 class="mb-2">
                    <i class="bx bx-message-dots me-2"></i>Send us a Message
                  </h3>
                  <p class="mb-0 opacity-75">
                    Fill out the form below and we'll get back to you soon
                  </p>
                </div>
              </div>

              <div class="card-body p-5">
                <form id="contactForm" method="POST" action="">
                  <!-- Personal Information Row -->
                  <div class="row g-3 mb-4">
                    <div class="col-md-6">
                      <label for="firstName" class="form-label fw-semibold">
                        <i class="bx bx-user me-2 text-primary"></i>First Name
                        <span class="text-danger">*</span>
                      </label>
                      <input
                        type="text"
                        class="form-control form-control-lg"
                        id="firstName"
                        name="firstName"
                        placeholder="Enter your first name"
                        value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>"
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
                        class="form-control form-control-lg"
                        id="lastName"
                        name="lastName"
                        placeholder="Enter your last name"
                        value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>"
                        required
                      />
                    </div>
                  </div>

                  <!-- Contact Information Row -->
                  <div class="row g-3 mb-4">
                    <div class="col-md-6">
                      <label for="email" class="form-label fw-semibold">
                        <i class="bx bx-envelope me-2 text-primary"></i>Email
                        Address
                        <span class="text-danger">*</span>
                      </label>
                      <input
                        type="email"
                        class="form-control form-control-lg"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
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
                        class="form-control form-control-lg"
                        id="phone"
                        name="phone"
                        placeholder="Enter your phone number"
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                      />
                    </div>
                  </div>

                  <!-- Subject and Category -->
                  <div class="row g-3 mb-4">
                    <div class="col-md-8">
                      <label for="subject" class="form-label fw-semibold">
                        <i class="bx bx-text me-2 text-primary"></i>Subject
                        <span class="text-danger">*</span>
                      </label>
                      <input
                        type="text"
                        class="form-control form-control-lg"
                        id="subject"
                        name="subject"
                        placeholder="What's this about?"
                        value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                        required
                      />
                    </div>
                    <div class="col-md-4">
                      <label for="category" class="form-label fw-semibold">
                        <i class="bx bx-category me-2 text-primary"></i>Category
                      </label>
                      <select class="form-select form-select-lg" id="category" name="category">
                        <option value="">Select Category</option>
                        <option value="general" <?php echo (($_POST['category'] ?? '') === 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                        <option value="support" <?php echo (($_POST['category'] ?? '') === 'support') ? 'selected' : ''; ?>>Customer Support</option>
                        <option value="orders" <?php echo (($_POST['category'] ?? '') === 'orders') ? 'selected' : ''; ?>>Order Questions</option>
                        <option value="returns" <?php echo (($_POST['category'] ?? '') === 'returns') ? 'selected' : ''; ?>>Returns & Exchanges</option>
                        <option value="wholesale" <?php echo (($_POST['category'] ?? '') === 'wholesale') ? 'selected' : ''; ?>>Wholesale Inquiries</option>
                        <option value="partnerships" <?php echo (($_POST['category'] ?? '') === 'partnerships') ? 'selected' : ''; ?>>Partnerships</option>
                        <option value="feedback" <?php echo (($_POST['category'] ?? '') === 'feedback') ? 'selected' : ''; ?>>Feedback</option>
                        <option value="other" <?php echo (($_POST['category'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                      </select>
                    </div>
                  </div>

                  <!-- Message -->
                  <div class="mb-4">
                    <label for="message" class="form-label fw-semibold">
                      <i class="bx bx-message-detail me-2 text-primary"></i
                      >Message
                      <span class="text-danger">*</span>
                    </label>
                    <textarea
                      class="form-control"
                      id="message"
                      name="message"
                      rows="6"
                      placeholder="Tell us more about your inquiry..."
                      required
                    ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    <div class="form-text">
                      Please provide as much detail as possible to help us
                      assist you better.
                    </div>
                  </div>

                  <!-- Priority and Preferences -->
                  <div class="row g-3 mb-4">
                    <div class="col-md-6">
                      <label for="priority" class="form-label fw-semibold">
                        <i class="bx bx-flag me-2 text-warning"></i>Priority
                        Level
                      </label>
                      <select class="form-select" id="priority" name="priority">
                        <option value="normal" <?php echo (($_POST['priority'] ?? 'normal') === 'normal') ? 'selected' : ''; ?>>Normal</option>
                        <option value="high" <?php echo (($_POST['priority'] ?? '') === 'high') ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo (($_POST['priority'] ?? '') === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label
                        for="preferredContact"
                        class="form-label fw-semibold"
                      >
                        <i class="bx bx-time me-2 text-info"></i>Preferred
                        Contact Method
                      </label>
                      <select class="form-select" id="preferredContact" name="preferredContact">
                        <option value="email" <?php echo (($_POST['preferredContact'] ?? 'email') === 'email') ? 'selected' : ''; ?>>Email</option>
                        <option value="phone" <?php echo (($_POST['preferredContact'] ?? '') === 'phone') ? 'selected' : ''; ?>>Phone Call</option>
                        <option value="either" <?php echo (($_POST['preferredContact'] ?? '') === 'either') ? 'selected' : ''; ?>>Either is fine</option>
                      </select>
                    </div>
                  </div>

                  <!-- Newsletter and Notifications -->
                  <div class="mb-4">
                    <div class="form-check mb-2">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="newsletter"
                        name="newsletter"
                        value="1"
                        <?php echo isset($_POST['newsletter']) ? 'checked' : ''; ?>
                      />
                      <label class="form-check-label" for="newsletter">
                        <i class="bx bx-envelope-open me-2 text-success"></i>
                        Subscribe to our newsletter for exclusive offers and
                        updates
                      </label>
                    </div>
                    <div class="form-check mb-2">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="notifications"
                        name="notifications"
                        value="1"
                        <?php echo isset($_POST['notifications']) ? 'checked' : ''; ?>
                      />
                      <label class="form-check-label" for="notifications">
                        <i class="bx bx-bell me-2 text-info"></i>
                        Send me notifications about order updates and promotions
                      </label>
                    </div>
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="agreeTerms"
                        name="agreeTerms"
                        value="1"
                        required
                      />
                      <label class="form-check-label" for="agreeTerms">
                        I agree to the
                        <a href="#" class="text-decoration-none"
                          >Privacy Policy</a
                        >
                        and
                        <a href="#" class="text-decoration-none"
                          >Terms of Service</a
                        >
                        <span class="text-danger">*</span>
                      </label>
                    </div>
                  </div>

                  <!-- Submit Buttons -->
                  <div
                    class="d-grid gap-2 d-md-flex justify-content-md-end"
                  >
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-lg me-md-2"
                      id="clearForm"
                    >
                      <i class="bx bx-refresh me-2"></i>Clear Form
                    </button>
                    <button
                      type="submit"
                      class="btn btn-primary btn-lg"
                      id="submitBtn"
                      name="submit_contact"
                    >
                      <span class="submit-text">
                        <i class="bx bx-send me-2"></i>Send Message
                      </span>
                      <div
                        class="spinner-border spinner-border-sm ms-2 d-none"
                        id="submitSpinner"
                      >
                        <span class="visually-hidden">Sending...</span>
                      </div>
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Contact Tips -->
            <div class="card mt-4 border-info">
              <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                  <i class="bx bx-info-circle me-2"></i>Quick Tips for Better
                  Support
                </h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <h6 class="text-success">
                      <i class="bx bx-check me-2"></i>For Faster Response:
                    </h6>
                    <ul class="small">
                      <li>Include your order number if applicable</li>
                      <li>Be specific about your issue</li>
                      <li>Provide relevant dates and details</li>
                      <li>Choose the correct category</li>
                    </ul>
                  </div>
                  <div class="col-md-6">
                    <h6 class="text-info">
                      <i class="bx bx-time me-2"></i>Response Times:
                    </h6>
                    <ul class="small">
                      <li><strong>Urgent:</strong> Within 2 hours</li>
                      <li><strong>High:</strong> Within 4 hours</li>
                      <li><strong>Normal:</strong> Within 24 hours</li>
                      <li><strong>General:</strong> Within 48 hours</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Map Section (Optional) -->
    <section class="py-5">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-light">
                <h5 class="mb-0 text-center">
                  <i class="bx bx-map-pin me-2 text-primary"></i>Find Our Store
                </h5>
              </div>
              <div class="card-body p-0">
                <!-- Placeholder for Map - Replace with actual map integration -->
                <div
                  class="map-placeholder bg-light d-flex align-items-center justify-content-center"
                  style="height: 300px"
                >
                  <div class="text-center text-muted">
                    <i class="bx bx-map fs-1 mb-3"></i>
                    <p class="mb-2"><strong>123 Fashion Street</strong></p>
                    <p class="mb-2">Style City, SC 12345</p>
                    <p class="small">Interactive map integration available</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Enhanced Footer (Same as Index Page) -->
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

    <!-- Contact Form JavaScript -->
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // Form elements
        const contactForm = document.getElementById("contactForm");
        const submitBtn = document.getElementById("submitBtn");
        const submitSpinner = document.getElementById("submitSpinner");
        const clearFormBtn = document.getElementById("clearForm");

        // Clear form functionality with SweetAlert
        clearFormBtn.addEventListener("click", function () {
          Swal.fire({
            title: 'Clear Form?',
            text: 'Are you sure you want to clear all form data?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, clear it!',
            cancelButtonText: 'Cancel'
          }).then((result) => {
            if (result.isConfirmed) {
              contactForm.reset();
              Swal.fire({
                title: 'Cleared!',
                text: 'Form data has been cleared.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
              });
            }
          });
        });

        // Form validation with SweetAlert
        contactForm.addEventListener("submit", function (e) {
          const firstName = document.getElementById("firstName").value.trim();
          const lastName = document.getElementById("lastName").value.trim();
          const email = document.getElementById("email").value.trim();
          const subject = document.getElementById("subject").value.trim();
          const message = document.getElementById("message").value.trim();
          const agreeTerms = document.getElementById("agreeTerms").checked;

          // Client-side validation - only prevent submission if validation fails
          if (!firstName) {
            e.preventDefault();
            Swal.fire({
              title: 'Missing Information',
              text: 'Please enter your first name.',
              icon: 'warning',
              confirmButtonColor: '#0d6efd'
            });
            return;
          }

          if (!lastName) {
            e.preventDefault();
            Swal.fire({
              title: 'Missing Information',
              text: 'Please enter your last name.',
              icon: 'warning',
              confirmButtonColor: '#0d6efd'
            });
            return;
          }

          if (!email) {
            e.preventDefault();
            Swal.fire({
              title: 'Missing Information',
              text: 'Please enter your email address.',
              icon: 'warning',
              confirmButtonColor: '#0d6efd'
            });
            return;
          }

          if (!subject) {
            e.preventDefault();
            Swal.fire({
              title: 'Missing Information',
              text: 'Please enter a subject.',
              icon: 'warning',
              confirmButtonColor: '#0d6efd'
            });
            return;
          }

          if (!message) {
            e.preventDefault();
            Swal.fire({
              title: 'Missing Information',
              text: 'Please enter your message.',
              icon: 'warning',
              confirmButtonColor: '#0d6efd'
            });
            return;
          }

          if (!agreeTerms) {
            e.preventDefault();
            Swal.fire({
              title: 'Agreement Required',
              text: 'Please agree to the Terms of Service and Privacy Policy.',
              icon: 'warning',
              confirmButtonColor: '#0d6efd'
            });
            return;
          }

          // If all validation passes, show loading state and allow form submission
          submitBtn.disabled = true;
          document.querySelector(".submit-text").innerHTML =
            '<i class="bx bx-loader-alt bx-spin me-2"></i>Sending...';
          submitSpinner.classList.remove("d-none");
          
          // Allow form to submit naturally to PHP backend
        });

        // Form validation feedback
        const inputs = contactForm.querySelectorAll(
          "input[required], textarea[required]"
        );
        inputs.forEach((input) => {
          input.addEventListener("blur", function () {
            if (!this.value.trim()) {
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

        // Show PHP messages with SweetAlert
        <?php if ($success_message): ?>
        Swal.fire({
          title: 'Success!',
          text: '<?php echo addslashes($success_message); ?>',
          icon: 'success',
          confirmButtonColor: '#28a745',
          timer: 4000
        }).then(() => {
          // Clean the URL by removing the success parameter
          if (window.location.search.includes('success=1')) {
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, '', url.pathname);
          }
        });
        <?php endif; ?>

        <?php if ($error_message): ?>
        Swal.fire({
          title: 'Error!',
          text: '<?php echo addslashes($error_message); ?>',
          icon: 'error',
          confirmButtonColor: '#dc3545'
        });
        <?php endif; ?>
      });
    </script>

    <!-- Custom CSS for Contact Page -->
    <style>
      .contact-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }

      .contact-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
      }

      .contact-icon {
        transition: transform 0.3s ease;
      }

      .contact-card:hover .contact-icon {
        transform: scale(1.1);
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

      .btn-primary:disabled {
        opacity: 0.7;
        cursor: not-allowed;
      }

      .search-container {
        min-width: 250px;
      }

      .search-input {
        border: 1px solid #e2e8f0;
        border-radius: 6px 0 0 6px;
      }

      .search-btn {
        border: 1px solid #e2e8f0;
        border-radius: 0 6px 6px 0;
      }

      .map-placeholder {
        border: 2px dashed #dee2e6;
      }

      @media (max-width: 768px) {
        .search-container {
          min-width: 200px;
        }

        .hero-buttons {
          flex-direction: column;
        }

        .hero-buttons .btn {
          margin-bottom: 1rem;
        }
      }
    </style>
  </body>
</html>

