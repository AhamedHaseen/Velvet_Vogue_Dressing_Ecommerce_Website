<?php
session_start();
include "db_connection.php";

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        try {
            // Create admin_users table if it doesn't exist
            $createTable = "
            CREATE TABLE IF NOT EXISTS admin_users (
                admin_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'admin',
                status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($createTable);
            
            // Check if email already exists
            $checkEmail = $conn->prepare("SELECT admin_id FROM admin_users WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            
            if ($checkEmail->get_result()->num_rows > 0) {
                $errors[] = "Email already exists";
            } else {
                // Hash password and insert admin
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $insertAdmin = $conn->prepare("INSERT INTO admin_users (name, email, password) VALUES (?, ?, ?)");
                $insertAdmin->bind_param("sss", $name, $email, $hashedPassword);
                
                if ($insertAdmin->execute()) {
                    $success_message = "Admin account created successfully! You can now login.";
                    // Clear form data after successful registration
                    $_POST = array();
                } else {
                    $errors[] = "Failed to create admin account";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Velvet Vogue</title>
    
    <!-- Bootstrap 5.3.8 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <style>
        .form-control:hover {
            cursor: pointer;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            transition: all 0.3s;
        }
        .strength-weak { background-color: #dc3545; }
        .strength-medium { background-color: #ffc107; }
        .strength-strong { background-color: #198754; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100" style="background-color: #cbf3f0">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <h2 class="fw-bold mb-3" style="color: #004e89">
                                <i class="bi bi-person-plus me-2"></i>Admin Registration
                            </h2>
                            <p class="text-muted fs-6" style="color: #004e89">
                                Create your Velvet Vogue admin account
                            </p>
                        </div>

                        <!-- Success Message -->
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Error Message -->
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Registration Form -->
                        <form id="registrationForm" method="POST" action="">
                            <!-- Full Name -->
                            <div class="mb-4">
                                <label for="name" class="form-label fs-6">
                                    <i class="bi bi-person me-2"></i>Full Name
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       placeholder="Enter your full name"
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Please enter your full name.
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label fs-6">
                                    <i class="bi bi-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Enter your email address"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label fs-6">
                                    <i class="bi bi-lock me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Create a strong password"
                                           minlength="8"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2" id="passwordStrength"></div>
                                <small class="text-muted">Password must be at least 8 characters long</small>
                                <div class="invalid-feedback">
                                    Password must be at least 8 characters long.
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label fs-6">
                                    <i class="bi bi-shield-check me-2"></i>Confirm Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Confirm your password"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="bi bi-eye" id="eyeIcon2"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="passwordMatch">
                                    Passwords do not match.
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-decoration-none">Terms and Conditions</a>
                                </label>
                                <div class="invalid-feedback">
                                    You must agree to the terms and conditions.
                                </div>
                            </div>

                            <!-- Register Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg" style="background-color: #004e89; border-color: #004e89;">
                                    <i class="bi bi-person-check me-2"></i>Create Admin Account
                                </button>
                            </div>

                            <!-- Already have account -->
                            <div class="text-center">
                                <p class="mb-0">Already have an account? 
                                    <a href="adm_login.php" class="text-decoration-none fw-bold">Login here</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });

        // Confirm password toggle functionality
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordField = document.getElementById('confirm_password');
            const eyeIcon2 = document.getElementById('eyeIcon2');

            if (confirmPasswordField.type === 'password') {
                confirmPasswordField.type = 'text';
                eyeIcon2.classList.remove('bi-eye');
                eyeIcon2.classList.add('bi-eye-slash');
            } else {
                confirmPasswordField.type = 'password';
                eyeIcon2.classList.remove('bi-eye-slash');
                eyeIcon2.classList.add('bi-eye');
            }
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('passwordStrength');
            
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Character variety checks
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Set strength class
            strengthIndicator.className = 'password-strength mt-2';
            
            if (strength < 3) {
                strengthIndicator.classList.add('strength-weak');
            } else if (strength < 5) {
                strengthIndicator.classList.add('strength-medium');
            } else {
                strengthIndicator.classList.add('strength-strong');
            }
        });

        // Password match validation
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchFeedback = document.getElementById('passwordMatch');
            const confirmField = document.getElementById('confirm_password');
            
            if (confirmPassword && password !== confirmPassword) {
                confirmField.classList.add('is-invalid');
                matchFeedback.style.display = 'block';
            } else {
                confirmField.classList.remove('is-invalid');
                matchFeedback.style.display = 'none';
            }
        }

        document.getElementById('password').addEventListener('input', checkPasswordMatch);
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;

            let isValid = true;

            // Name validation
            if (!name) {
                document.getElementById('name').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('name').classList.remove('is-invalid');
            }

            // Email validation
            if (!email || !email.includes('@')) {
                document.getElementById('email').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('email').classList.remove('is-invalid');
            }

            // Password validation
            if (!password || password.length < 8) {
                document.getElementById('password').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('password').classList.remove('is-invalid');
            }

            // Confirm password validation
            if (password !== confirmPassword) {
                document.getElementById('confirm_password').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('confirm_password').classList.remove('is-invalid');
            }

            // Terms validation
            if (!terms) {
                document.getElementById('terms').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('terms').classList.remove('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
            }

            this.classList.add('was-validated');
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Clear form after successful registration
        <?php if (!empty($success_message)): ?>
        document.getElementById('registrationForm').reset();
        document.getElementById('passwordStrength').className = 'password-strength mt-2';
        <?php endif; ?>
    </script>
</body>
</html>