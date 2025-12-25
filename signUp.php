<?php
// Include session and database
include 'includes/session_db.php';

// Initialize message variables
$success_message = "";
$error_message = "";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    $error_message = "Error: 'users' table does not exist. Please run the SQL script first.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET)) {
    // Debug: Check if form data is received
    // error_log("Form submitted with data: " . print_r($_GET, true));
    
    // Get form data
    $first_name = trim($_GET['firstName'] ?? '');
    $last_name = trim($_GET['lastName'] ?? '');
    $email = trim($_GET['email'] ?? '');
    $phone = trim($_GET['phone'] ?? '');
    $password = $_GET['password'] ?? '';
    $confirm_password = $_GET['confirmPassword'] ?? '';
    $gender = $_GET['gender'] ?? '';
    $newsletter = isset($_GET['newsletter']) ? 1 : 0;
    $terms = isset($_GET['terms']);
    
    // Validation
    $errors = [];
    
    // Check required fields
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($confirm_password)) $errors[] = "Confirm password is required";
    if (empty($gender)) $errors[] = "Gender is required";
    
    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Validate password
    if (!empty($password) && strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check password confirmation
    if (!empty($password) && !empty($confirm_password) && $password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check terms agreement
    if (!$terms) {
        $errors[] = "You must agree to the terms and conditions";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists. Please use a different email address.";
        }
    }
    
    // If no errors, insert new user
    if (empty($errors)) {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into database
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash, gender, newsletter_subscribed) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            $error_message = "Database prepare error: " . $conn->error;
        } else {
            $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $password_hash, $gender, $newsletter);
            
            if ($stmt->execute()) {
                $success_message = "Registration successful!";
                // Optionally, auto-login the user
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
            } else {
                $error_message = "Registration failed: " . $stmt->error . " | SQL Error: " . $conn->error;
            }
            $stmt->close();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up - Velvet Vogue</title>

    <!-- Bootstrap CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <!-- Boxicons -->
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />

    <!-- SweetAlert2 CSS -->
    <link 
      href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" 
      rel="stylesheet"
    />

    <!-- Custom CSS -->
    <link href="style.css" rel="stylesheet" />
    <style>
      /* ===== SIGN-UP PAGE STYLES ===== */
      .signup-body {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
      }

      .signup-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
      }

      .signup-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
        overflow: hidden;
        max-width: 600px;
        width: 100%;
        margin: 20px 0;
      }

      .signup-header {
        text-align: center;
        padding: 40px 40px 20px;
        background: #003049;
        color: white;
      }

      .signup-brand-logo {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
      }

      .signup-brand-tagline {
        font-size: 1rem;
        margin: 0;
        opacity: 0.9;
      }

      .signup-form {
        padding: 40px;
      }

      .signup-form-title {
        color: #333;
        font-weight: 700;
        margin-bottom: 30px;
        text-align: center;
        font-size: 1.8rem;
      }

      /* Input Group Styles */
      .signup-form .input-group {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        margin-bottom: 1rem;
      }

      .signup-form .input-group:focus-within {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
      }

      .signup-form .input-group-text {
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
        color: #6c757d;
        font-size: 1.2rem;
        min-width: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
      }

      .signup-form .input-group:focus-within .input-group-text {
        background-color: #e3f2fd;
        border-color: #007bff;
        color: #007bff;
      }

      .signup-form .form-control {
        border: 1px solid #dee2e6;
        background-color: white;
        height: 58px;
        transition: all 0.3s ease;
        font-size: 1rem;
      }

      .signup-form .form-control:focus {
        background-color: #fafafa;
        box-shadow: none;
        border-color: #007bff;
      }

      .signup-form .form-floating label {
        color: #6c757d;
        font-weight: 500;
        padding-left: 0.75rem;
      }

      /* Section Labels */
      .signup-section-label {
        color: #495057;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
      }

      /* Gender Options */
      .signup-gender-options {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e9ecef;
      }

      .signup-gender-options .form-check {
        margin-right: 25px;
      }

      .signup-gender-options .form-check-input {
        cursor: pointer;
        transform: scale(1.1);
      }

      .signup-gender-options .form-check-label {
        font-weight: 500;
        color: #495057;
        cursor: pointer;
        margin-left: 8px;
      }

      /* Checkboxes */
      .signup-checkbox {
        cursor: pointer;
        transform: scale(1.1);
      }

      .signup-checkbox-label {
        font-size: 0.95rem;
        color: #6c757d;
        cursor: pointer;
        margin-left: 10px;
        line-height: 1.5;
      }

      /* Links */
      .signup-link {
        color: #007bff;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
      }

      .signup-link:hover {
        color: #0056b3;
        text-decoration: underline;
      }

      /* Submit Button */
      .signup-btn-submit {
        background: transparent;
        color: #28a745;
        border-radius: 12px;
        padding: 16px 30px;
        font-weight: 700;
        transition: all 0.3s ease;
        font-size: 1.1rem;
        border: 2px solid #28a745;
        box-shadow: none;
        position: relative;
        overflow: hidden;
      }

      .signup-btn-submit::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(
          90deg,
          transparent,
          rgba(40, 167, 69, 0.1),
          transparent
        );
        transition: left 0.5s;
      }

      .signup-btn-submit:hover::before {
        left: 100%;
      }

      .signup-btn-submit:hover {
        color: #218838;
        border-color: #218838;
      }

      .signup-btn-submit:active,
      .signup-btn-submit:focus {
        background: transparent !important;
        color: #28a745 !important;
        border-color: #28a745 !important;
        transform: translateY(0) !important;
        box-shadow: none !important;
      }

      /* Footer Links */
      .signup-footer-links {
        text-align: center;
        margin-top: 30px;
        padding-top: 25px;
        border-top: 2px solid #f0f0f0;
      }

      .signup-footer-links p {
        font-size: 1rem;
        color: #6c757d;
      }

      /* Password Toggle Buttons */
      .signup-form .btn {
        color: #ffffff;
        background-color: #218838;
      }

      .signup-form .btn:hover {
        color: #007bff;
        background: transparent;
      }

      /* Responsive Design */
      @media (max-width: 768px) {
        .signup-header {
          padding: 30px 20px 15px;
        }

        .signup-form {
          padding: 30px 20px;
        }

        .signup-brand-logo {
          font-size: 2.2rem;
        }

        .signup-form-title {
          font-size: 1.5rem;
        }

        .signup-gender-options .form-check {
          margin-right: 15px;
          margin-bottom: 10px;
        }
      }

      @media (max-width: 576px) {
        .signup-container {
          padding: 10px;
        }

        .signup-card {
          margin: 10px 0;
          border-radius: 15px;
        }

        .signup-form {
          padding: 25px 15px;
        }

        .signup-header {
          padding: 25px 15px 15px;
        }
      }
    </style>
  </head>

  <body class="signup-body">
    <div class="signup-container">
      <div class="signup-card">
        <!-- Header Section -->
        <div class="signup-header">
          <h1 class="signup-brand-logo">Velvet Vogue</h1>
          <p class="signup-brand-tagline">Elegance in Every Thread</p>
        </div>

        <!-- Sign Up Form -->
        <div class="signup-form">
          <h2 class="signup-form-title">Create Your Account</h2>

          <form id="signUpForm" method="GET" action="signUp.php" novalidate>
            <!-- First Name and Last Name -->
            <div class="row mb-3">
              <div class="col-md-6">
                <div class="input-group">
                  <span class="input-group-text bg-transparent border-end-0">
                    <i class="bx bx-user text-muted"></i>
                  </span>
                  <div class="form-floating">
                    <input
                      type="text"
                      class="form-control border-start-0"
                      id="firstName"
                      name="firstName"
                      placeholder="First Name"
                      required
                    />
                    <label for="firstName">First Name</label>
                  </div>
                  <div class="invalid-feedback">
                    Please provide your first name.
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="input-group">
                  <span class="input-group-text bg-transparent border-end-0">
                    <i class="bx bx-user text-muted"></i>
                  </span>
                  <div class="form-floating">
                    <input
                      type="text"
                      class="form-control border-start-0"
                      id="lastName"
                      name="lastName"
                      placeholder="Last Name"
                      required
                    />
                    <label for="lastName">Last Name</label>
                  </div>
                  <div class="invalid-feedback">
                    Please provide your last name.
                  </div>
                </div>
              </div>
            </div>

            <!-- Email Input -->
            <div class="input-group mb-3">
              <span class="input-group-text bg-transparent border-end-0">
                <i class="bx bx-envelope text-muted"></i>
              </span>
              <div class="form-floating">
                <input
                  type="email"
                  class="form-control border-start-0"
                  id="email"
                  name="email"
                  placeholder="name@example.com"
                  required
                />
                <label for="email">Email address</label>
              </div>
              <div class="invalid-feedback">
                Please provide a valid email address.
              </div>
            </div>

            <!-- Phone Number -->
            <div class="input-group mb-3">
              <span class="input-group-text bg-transparent border-end-0">
                <i class="bx bx-phone text-muted"></i>
              </span>
              <div class="form-floating">
                <input
                  type="tel"
                  class="form-control border-start-0"
                  id="phone"
                  name="phone"
                  placeholder="Phone Number"
                  required
                />
                <label for="phone">Phone Number</label>
              </div>
              <div class="invalid-feedback">
                Please provide your phone number.
              </div>
            </div>

            <!-- Password Input -->
            <div class="input-group mb-3">
              <span class="input-group-text bg-transparent border-end-0">
                <i class="bx bx-lock text-muted"></i>
              </span>
              <div class="form-floating position-relative flex-grow-1">
                <input
                  type="password"
                  class="form-control border-start-0 border-end-0"
                  id="password"
                  name="password"
                  placeholder="Password"
                  required
                />
                <label for="password">Password</label>
              </div>
              <span class="input-group-text bg-transparent border-start-0">
                <button
                  type="button"
                  class="btn p-0 border-0 bg-transparent"
                  onclick="togglePassword('password', 'toggleIcon1')"
                >
                  <i class="bx bx-hide text-muted" id="toggleIcon1"></i>
                </button>
              </span>
              <div class="invalid-feedback">
                Password must be at least 8 characters long.
              </div>
            </div>

            <!-- Confirm Password Input -->
            <div class="input-group mb-3">
              <span class="input-group-text bg-transparent border-end-0">
                <i class="bx bx-lock-alt text-muted"></i>
              </span>
              <div class="form-floating position-relative flex-grow-1">
                <input
                  type="password"
                  class="form-control border-start-0 border-end-0"
                  id="confirmPassword"
                  name="confirmPassword"
                  placeholder="Confirm Password"
                  required
                />
                <label for="confirmPassword">Confirm Password</label>
              </div>
              <span class="input-group-text bg-transparent border-start-0">
                <button
                  type="button"
                  class="btn p-0 border-0 bg-transparent"
                  onclick="togglePassword('confirmPassword', 'toggleIcon2')"
                >
                  <i class="bx bx-hide text-muted" id="toggleIcon2"></i>
                </button>
              </span>
              <div class="invalid-feedback">Passwords do not match.</div>
            </div>

            <!-- Gender Selection -->
            <div class="mb-3">
              <label class="form-label signup-section-label">
                <i class="bx bx-user-circle me-2"></i>Gender
              </label>
              <div class="signup-gender-options">
                <div class="form-check form-check-inline">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="gender"
                    id="male"
                    value="male"
                    required
                  />
                  <label class="form-check-label" for="male">Male</label>
                </div>
                <div class="form-check form-check-inline">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="gender"
                    id="female"
                    value="female"
                    required
                  />
                  <label class="form-check-label" for="female">Female</label>
                </div>
                <div class="form-check form-check-inline">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="gender"
                    id="other"
                    value="other"
                    required
                  />
                  <label class="form-check-label" for="other">Other</label>
                </div>
              </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="form-check mb-4">
              <input
                class="form-check-input signup-checkbox"
                type="checkbox"
                id="terms"
                name="terms"
                required
              />
              <label class="form-check-label signup-checkbox-label" for="terms">
                I agree to the
                <a href="#" class="signup-link">Terms and Conditions</a> and
                <a href="#" class="signup-link">Privacy Policy</a>
              </label>
              <div class="invalid-feedback">
                You must agree to the terms and conditions.
              </div>
            </div>

            <!-- Newsletter Subscription -->
            <div class="form-check mb-4">
              <input
                class="form-check-input signup-checkbox"
                type="checkbox"
                id="newsletter"
                name="newsletter"
              />
              <label
                class="form-check-label signup-checkbox-label"
                for="newsletter"
              >
                Subscribe to our newsletter for exclusive offers and updates
              </label>
            </div>

            <!-- Sign Up Button -->
            <button type="submit" class="signup-btn-submit btn w-100 mb-3">
              Create Account
            </button>
          </form>

          <!-- Footer Links -->
          <div class="signup-footer-links">
            <p class="mb-0 text-muted">
              Already have an account?
              <a href="signIn.php" class="signup-link">Sign In</a>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
      function togglePassword(fieldId, iconId) {
        const passwordInput = document.getElementById(fieldId);
        const toggleIcon = document.getElementById(iconId);

        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          toggleIcon.className = "bx bx-show text-muted";
        } else {
          passwordInput.type = "password";
          toggleIcon.className = "bx bx-hide text-muted";
        }
      }

      // Form validation and password matching with SweetAlert2
      (function () {
        "use strict";

        const form = document.getElementById("signUpForm");
        const password = document.getElementById("password");
        const confirmPassword = document.getElementById("confirmPassword");

        function validatePasswords() {
          if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords do not match");
          } else {
            confirmPassword.setCustomValidity("");
          }
        }

        password.addEventListener("input", validatePasswords);
        confirmPassword.addEventListener("input", validatePasswords);

        form.addEventListener(
          "submit",
          function (event) {
            validatePasswords();

            if (!form.checkValidity()) {
              event.preventDefault();
              event.stopPropagation();
            }

            form.classList.add("was-validated");
          },
          false
        );
      })();

      // Show PHP messages with SweetAlert2
      <?php if (!empty($success_message)): ?>
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: '<?php echo addslashes($success_message); ?>',
          confirmButtonColor: '#28a745',
          confirmButtonText: 'Continue',
          timer: 3000,
          timerProgressBar: true
        }).then((result) => {
          // Optionally redirect to sign in page or dashboard
          window.location.href = 'signIn.php';
        });
      <?php endif; ?>

      <?php if (!empty($error_message)): ?>
        Swal.fire({
          icon: 'error',
          title: 'Registration Error',
          html: '<?php echo addslashes($error_message); ?>',
          confirmButtonColor: '#dc3545',
          confirmButtonText: 'Try Again'
        });
      <?php endif; ?>
    </script>
  </body>
</html>

