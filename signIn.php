<?php
// Include session and database
include 'includes/session_db.php';

// Initialize message variables
$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET)) {
    // Get form data
    $email = trim($_GET['email'] ?? '');
    $password = $_GET['password'] ?? '';
    $remember_me = isset($_GET['rememberMe']);
    
    // Validation
    $errors = [];
    
    // Check required fields
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // If no validation errors, check credentials
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password_hash FROM users WHERE email = ?");
        
        if ($stmt === false) {
            $error_message = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Set remember me cookie if checked
                    if ($remember_me) {
                        $remember_token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $remember_token, time() + (86400 * 30), '/'); // 30 days
                        // You can store this token in database for better security
                    }
                    
                    $success_message = "Login successful! Welcome back, " . $user['first_name'] . "!";
                    
                    // Redirect to dashboard or home page after a delay
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 2000);
                    </script>";
                } else {
                    $error_message = "Invalid email or password";
                }
            } else {
                $error_message = "Invalid email or password";
            }
            $stmt->close();
        }
    } else {
        $error_message = implode(", ", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign In - Velvet Vogue</title>
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
    <!-- Custom CSS -->
    <link href="style.css" rel="stylesheet" />
    <style>
      /* SIGN-IN PAGE STYLES */
      .signin-body {
        background-color: #ffffff;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      }

      .signin-container {
        min-height: 100vh;
        background-color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
      }

      .signin-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        border: 2px solid #dee2e6;
        overflow: hidden;
        max-width: 450px;
        width: 100%;
      }

      .signin-header {
        text-align: center;
        padding: 40px 40px 30px;
        background: #003049;
        border-bottom: 1px solid #f0f0f0;
      }

      .signin-brand-logo {
        font-size: 2.2rem;
        font-weight: bold;
        color: #ffffff;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
      }

      .signin-brand-tagline {
        color: #ffffff;
        font-size: 0.95rem;
        margin: 0;
      }

      .signin-form {
        padding: 40px;
      }

      .signin-form-title {
        color: #333;
        font-weight: 600;
        margin-bottom: 30px;
        text-align: center;
        font-size: 1.5rem;
      }

      .form-floating .form-control {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        background-color: transparent;
        height: 58px;
      }

      .form-floating .form-control:focus {
        border: 1px solid #007bff;
        box-shadow: none;
        background-color: rgba(255, 255, 255, 0.1);
      }

      .form-floating label {
        color: #6c757d;
        font-weight: 500;
      }

      /* Enhanced Input Group Styles */
      .input-group {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: none;
        transition: all 0.3s ease;
      }

      .input-group:focus-within {
        box-shadow: none;
        transform: translateY(-1px);
      }

      .input-group-text {
        border: 1px solid #dee2e6;
        background-color: transparent !important;
        color: #6c757d;
        font-size: 1.1rem;
        min-width: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .input-group .form-control {
        border: 1px solid #dee2e6;
        background-color: transparent;
        height: 58px;
        transition: all 0.3s ease;
      }

      .input-group .form-control:focus {
        background-color: rgba(255, 255, 255, 0.1);
        box-shadow: none;
        border: 1px solid #007bff;
      }

      .input-group:focus-within .input-group-text {
        border: 1px solid #007bff;
        color: #007bff;
      }

      .input-group .form-floating label {
        color: #6c757d;
        font-weight: 500;
        padding-left: 0.75rem;
      }

      /* Password toggle button styling */
      .input-group .btn {
        color: #6c757d;
        transition: color 0.3s ease;
      }

      .input-group .btn:hover {
        color: #007bff;
      }

      .signin-btn-login {
        background: #198754;
        color: white;
        border-radius: 8px;
        padding: 14px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 1rem;
        border: 2px solid #198754;
      }

      .signin-btn-login:hover {
        background: transparent;
        border-color: #198754;
        color: black;
        transform: translateY(-1px);
      }

      .signin-btn-login:active,
      .signin-btn-login:focus {
        background: #198754 !important;
        border-color: #198754 !important;
        color: white !important;
        box-shadow: none !important;
      }

      .signin-footer-links {
        text-align: center;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
      }

      .signin-footer-links a {
        color: #007bff;
        text-decoration: none;
        transition: color 0.3s ease;
        font-weight: 500;
      }

      .signin-footer-links a:hover {
        color: #0056b3;
        text-decoration: underline;
      }

      .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none;
        color: #6c757d;
        cursor: pointer;
        z-index: 10;
        font-size: 1.1rem;
      }

      .form-check-input {
        cursor: pointer;
      }

      .form-check-label {
        font-size: 0.9rem;
        color: #6c757d;
        cursor: pointer;
      }

      .signin-forgot-password {
        font-size: 0.9rem;
        color: #007bff;
        text-decoration: none;
      }

      .signin-forgot-password:hover {
        color: #0056b3;
        text-decoration: underline;
      }

      @media (max-width: 768px) {
        .signin-header {
          padding: 30px 30px 20px;
        }

        .signin-form {
          padding: 30px;
        }

        .signin-brand-logo {
          font-size: 2rem;
        }
      }
    </style>
  </head>

  <body class="signin-body">
    <div class="signin-container">
      <div class="signin-card">
        <!-- Header Section -->
        <div class="signin-header">
          <h1 class="signin-brand-logo">Velvet Vogue</h1>
          <p class="signin-brand-tagline">Elegance in Every Thread</p>
        </div>

        <!-- Sign In Form -->
        <div class="signin-form">
          <h2 class="signin-form-title">Welcome Back</h2>

          <?php if (!empty($success_message)): ?>
            <script>
              document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                  title: 'Success!',
                  text: '<?php echo $success_message; ?>',
                  icon: 'success',
                  confirmButtonText: 'OK',
                  confirmButtonColor: '#003049'
                });
              });
            </script>
          <?php endif; ?>

          <?php if (!empty($error_message)): ?>
            <script>
              document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                  title: 'Error!',
                  text: '<?php echo $error_message; ?>',
                  icon: 'error',
                  confirmButtonText: 'Try Again',
                  confirmButtonColor: '#003049'
                });
              });
            </script>
          <?php endif; ?>

          <form id="signInForm" method="GET" action="signIn.php" novalidate>
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
                  onclick="togglePassword()"
                >
                  <i class="bx bx-hide text-muted" id="toggleIcon"></i>
                </button>
              </span>
              <div class="invalid-feedback">Please provide a password.</div>
            </div>

            <!-- Remember Me and Forgot Password -->
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="rememberMe"
                  name="rememberMe"
                />
                <label class="form-check-label" for="rememberMe">
                  Remember me
                </label>
              </div>
              <a href="#" class="signin-forgot-password">Forgot Password?</a>
            </div>

            <!-- Sign In Button -->
            <button type="submit" class="signin-btn-login btn w-100 mb-3">
              Sign In
            </button>
          </form>

          <!-- Footer Links -->
          <div class="signin-footer-links">
            <p class="mb-0 text-muted">
              Don't have an account? <a href="signUp.php">Sign up</a>
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
      // Toggle password visibility
      function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
          passwordInput.type = 'text';
          toggleIcon.classList.remove('bx-hide');
          toggleIcon.classList.add('bx-show');
        } else {
          passwordInput.type = 'password';
          toggleIcon.classList.remove('bx-show');
          toggleIcon.classList.add('bx-hide');
        }
      }
    </script>
  </body>
</html>

