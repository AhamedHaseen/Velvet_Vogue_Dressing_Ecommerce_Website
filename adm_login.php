<?php
session_start();
include "db_connection.php";

// Clean URL - remove any unwanted query parameters (but allow POST requests)
if (!empty($_GET) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: adm_login.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Debug: Add temporary message to see if form is being processed
    if (empty($email) && empty($password)) {
        $error_message = "Form submitted but no data received. Check form fields.";
    }
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        try {
            // First check if table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'admin_users'");
            if ($tableCheck->num_rows == 0) {
                $error_message = "Admin users table does not exist. Please create it first.";
            } else {
                // Check if admin exists (first check without status filter)
                $stmt = $conn->prepare("SELECT admin_id, name, email, password, role, status FROM admin_users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $error_message = "No account found with email: " . htmlspecialchars($email);
                } else {
                    $admin = $result->fetch_assoc();
                    
                    // Check status
                    if ($admin['status'] !== 'active') {
                        $error_message = "Account is " . $admin['status'] . ". Please contact administrator.";
                    } else {
                        // Verify password
                        if (password_verify($password, $admin['password'])) {
                            // Set session variables
                            $_SESSION['admin_id'] = $admin['admin_id'];
                            $_SESSION['admin_name'] = $admin['name'];
                            $_SESSION['admin_email'] = $admin['email'];
                            $_SESSION['admin_role'] = $admin['role'];
                            $_SESSION['admin_logged_in'] = true;
                            
                            // Set remember me cookie if checked
                            if ($remember) {
                                $token = bin2hex(random_bytes(32));
                                setcookie('admin_remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                            }
                            
                            // Redirect to dashboard
                            header('Location: adm_dashboard.php');
                            exit();
                        } else {
                            $error_message = "Incorrect password for: " . htmlspecialchars($email);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: adm_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - Velvet Vogue</title>

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

    <!-- Custom CSS -->
    <style>
      .form-control:hover {
        cursor: pointer;
      }
      
      .btn {
        transition: all 0.3s ease;
      }
      
      .btn:disabled {
        opacity: 0.7;
      }
      
      .spinner-border-sm {
        width: 1rem;
        height: 1rem;
      }
      
      .loading-blur {
        filter: blur(1px);
        pointer-events: none;
      }
      
      #loadingOverlay {
        backdrop-filter: blur(2px);
      }
    </style>
  </head>
  <body
    class="d-flex align-items-center min-vh-100"
    style="background-color: #cbf3f0"
  >
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="card shadow-sm" style="min-height: 500px">
            <div
              class="card-body p-5 d-flex flex-column justify-content-center"
            >
              <!-- Header -->
              <div class="text-center mb-5">
                <h2 class="fw-bold mb-3" style="color: #004e89">Admin Login</h2>
                <p class="text-muted fs-5" style="color: #004e89">
                  Velvet Vogue Administration
                </p>
              </div>

              <!-- Error Message -->
              <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  <?php echo htmlspecialchars($error_message); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <!-- Success Message -->
              <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <i class="bi bi-check-circle-fill me-2"></i>
                  <?php echo htmlspecialchars($success_message); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <!-- Login Form -->
              <form id="loginForm" method="POST" action="">
                <!-- Email -->
                <div class="mb-4">
                  <label for="email" class="form-label fs-6">
                    <i class="bi bi-envelope me-2"></i>Email Address
                  </label>
                  <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                  />
                </div>

                <!-- Password -->
                <div class="mb-4">
                  <label for="password" class="form-label fs-6">
                    <i class="bi bi-lock me-2"></i>Password
                  </label>
                  <div class="input-group">
                    <input
                      type="password"
                      class="form-control"
                      id="password"
                      name="password"
                      placeholder="Enter password"
                      required
                    />
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      id="togglePassword"
                    >
                      <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                  </div>
                </div>

                <!-- Remember Me -->
                <div class="mb-4 form-check">
                  <input
                    type="checkbox"
                    class="form-check-input"
                    id="remember"
                    name="remember"
                    <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>
                  />
                  <label class="form-check-label" for="remember">
                    Remember me
                  </label>
                </div>

                <!-- Login Button -->
                <div class="d-grid mt-4">
                  <button type="submit" id="loginBtn" class="btn btn-primary" style="background-color: #004e89; border-color: #004e89;">
                    <span id="loginBtnText">
                      <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </span>
                    <span id="loginBtnLoading" class="d-none">
                      <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      Logging in...
                    </span>
                  </button>
                </div>
              </form>

              <!-- Links -->
              <div class="text-center mt-3">
                <div class="mb-2">
                  <a href="#" class="text-decoration-none">Forgot Password?</a>
                </div>
                <div>
                  <span class="text-muted">Don't have an account? </span>
                  <a href="adm_register.php" class="text-decoration-none fw-bold">Register here</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="d-none position-fixed top-0 start-0 w-100 h-100" style="background-color: rgba(0, 0, 0, 0.5); z-index: 9999;">
      <div class="d-flex justify-content-center align-items-center h-100">
        <div class="text-center text-white">
          <div class="spinner-border mb-3" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <div class="h5">Authenticating...</div>
          <small class="text-light">Please wait while we verify your credentials</small>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Admin Login Script -->
    <script>
      // Simple form validation
      document
        .getElementById("loginForm")
        .addEventListener("submit", function (e) {
          const email = document.getElementById("email").value.trim();
          const password = document.getElementById("password").value;

          // Basic client-side validation only
          if (!email || !password) {
            e.preventDefault();
            alert("Please enter both email and password.");
            return false;
          }

          if (!email.includes('@')) {
            e.preventDefault();
            alert("Please enter a valid email address.");
            return false;
          }

          // Show simple loading state
          const loginBtn = document.getElementById("loginBtn");
          const loginBtnText = document.getElementById("loginBtnText");
          const loginBtnLoading = document.getElementById("loginBtnLoading");
          
          loginBtn.disabled = true;
          loginBtnText.classList.add('d-none');
          loginBtnLoading.classList.remove('d-none');
          
          // Form will submit normally
        });

      // Password toggle functionality
      document
        .getElementById("togglePassword")
        .addEventListener("click", function () {
          const passwordField = document.getElementById("password");
          const eyeIcon = document.getElementById("eyeIcon");

          if (passwordField.type === "password") {
            passwordField.type = "text";
            eyeIcon.classList.remove("bi-eye");
            eyeIcon.classList.add("bi-eye-slash");
          } else {
            passwordField.type = "password";
            eyeIcon.classList.remove("bi-eye-slash");
            eyeIcon.classList.add("bi-eye");
          }
        });
    </script>
  </body>
</html>

