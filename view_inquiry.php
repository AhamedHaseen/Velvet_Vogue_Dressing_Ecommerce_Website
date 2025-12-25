<?php
// Include common admin authentication
include 'includes/auth_admin.php';

// Get admin info
$admin_id = $_SESSION['admin_id'] ?? '';
$admin_name = $_SESSION['admin_name'] ?? 'Admin User';
$admin_email = $_SESSION['admin_email'] ?? 'admin@example.com';
$admin_role = $_SESSION['admin_role'] ?? 'Administrator';

// Handle status updates
if (isset($_POST['update_status'])) {
    $inquiry_id = $_POST['inquiry_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE contacts SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $inquiry_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($category_filter !== 'all') {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
    $param_types .= 's';
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "priority = ?";
    $params[] = $priority_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(CONCAT(first_name, ' ', last_name) LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'ssss';
}

$where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM contacts $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$total_inquiries = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get inquiries with pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_inquiries / $per_page);

$query = "SELECT id, first_name, last_name, email, phone, subject, category, message, priority, 
          preferred_contact, status, created_at, updated_at 
          FROM contacts $where_clause 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$inquiries = [];
while ($row = $result->fetch_assoc()) {
    $inquiries[] = $row;
}
$stmt->close();

// Get counts for different statuses
$status_counts = [];
$status_query = "SELECT status, COUNT(*) as count FROM contacts GROUP BY status";
$result = $conn->query($status_query);
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: adm_login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inquiries - Velvet Vogue Admin</title>
    
    <!-- Bootstrap 5.3.8 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary-color: #219ebc;
            --secondary-color: #8ecae6;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #2c3e50;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --sidebar-width: 280px;
            --header-height: 70px;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #001d3d 0%, #012a5f 100%);
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .logo h3 {
            color: var(--white);
            font-weight: 700;
            margin: 0;
            font-size: 1.5rem;
        }

        .sidebar .nav-menu {
            padding: 20px 0;
        }

        .sidebar .nav-item {
            margin: 5px 15px;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 0;
            background: #f8f9fa;
        }

        /* Header */
        .header {
            background: #ffffff;
            height: var(--header-height);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .content-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid #e9ecef;
            padding: 20px 25px;
        }

        .card-title {
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .priority-urgent { background-color: #dc3545; color: white; }
        .priority-high { background-color: #ffc107; color: #000; }
        .priority-normal { background-color: #6c757d; color: white; }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <h3>Velvet Vogue</h3>
            <small style="color: rgba(255, 255, 255, 0.7)">Admin Panel</small>
        </div>
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="adm_dashboard.php" class="nav-link">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="products.php" class="nav-link">
                    <i class="bi bi-bag-check"></i>
                    Products
                </a>
            </div>
            <div class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="bi bi-receipt"></i>
                    Orders
                </a>
            </div>
            <div class="nav-item">
                <a href="view_inquiry.php" class="nav-link active">
                    <i class="bi bi-envelope"></i>
                    View Inquiry
                </a>
            </div>
            <div class="nav-item">
                <a href="analytics.php" class="nav-link">
                    <i class="bi bi-bar-chart"></i>
                    Analytics
                </a>
            </div>
            <div class="nav-item">
                <a href="categories.php" class="nav-link">
                    <i class="bi bi-tags"></i>
                    Categories
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <h4 class="mb-0">Customer Inquiries Management</h4>
            <div class="ms-auto">
                <a href="adm_dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </header>

        <!-- Content -->
        <div class="container-fluid p-4">
            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-envelope-plus fs-2 mb-2"></i>
                            <h3><?= $status_counts['new'] ?? 0 ?></h3>
                            <p class="mb-0">New Inquiries</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-warning text-dark">
                        <div class="card-body text-center">
                            <i class="bi bi-clock-history fs-2 mb-2"></i>
                            <h3><?= $status_counts['in_progress'] ?? 0 ?></h3>
                            <p class="mb-0">In Progress</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-success text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle fs-2 mb-2"></i>
                            <h3><?= $status_counts['resolved'] ?? 0 ?></h3>
                            <p class="mb-0">Resolved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-secondary text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-archive fs-2 mb-2"></i>
                            <h3><?= $status_counts['closed'] ?? 0 ?></h3>
                            <p class="mb-0">Closed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="content-card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Filter Inquiries</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>New</option>
                                <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="all" <?= $category_filter === 'all' ? 'selected' : '' ?>>All Categories</option>
                                <option value="general" <?= $category_filter === 'general' ? 'selected' : '' ?>>General</option>
                                <option value="support" <?= $category_filter === 'support' ? 'selected' : '' ?>>Support</option>
                                <option value="orders" <?= $category_filter === 'orders' ? 'selected' : '' ?>>Orders</option>
                                <option value="returns" <?= $category_filter === 'returns' ? 'selected' : '' ?>>Returns</option>
                                <option value="feedback" <?= $category_filter === 'feedback' ? 'selected' : '' ?>>Feedback</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="all" <?= $priority_filter === 'all' ? 'selected' : '' ?>>All Priorities</option>
                                <option value="urgent" <?= $priority_filter === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                <option value="high" <?= $priority_filter === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="normal" <?= $priority_filter === 'normal' ? 'selected' : '' ?>>Normal</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Inquiries Table -->
            <div class="content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Customer Inquiries (<?= $total_inquiries ?> total)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($inquiries)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inquiries as $inquiry): ?>
                                <tr>
                                    <td><strong>#<?= str_pad($inquiry['id'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-2" style="background: #219ebc;">
                                                <?= strtoupper(substr($inquiry['first_name'] . ' ' . $inquiry['last_name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars($inquiry['first_name'] . ' ' . $inquiry['last_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($inquiry['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($inquiry['subject']) ?>">
                                            <?= htmlspecialchars($inquiry['subject']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= ucfirst($inquiry['category']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge priority-<?= $inquiry['priority'] ?>"><?= ucfirst($inquiry['priority']) ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                                <option value="new" <?= $inquiry['status'] === 'new' ? 'selected' : '' ?>>New</option>
                                                <option value="in_progress" <?= $inquiry['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="resolved" <?= $inquiry['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                <option value="closed" <?= $inquiry['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <small><?= date('M j, Y H:i', strtotime($inquiry['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="showInquiryDetails(<?= $inquiry['id'] ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success ms-1" onclick="replyInquiry(<?= $inquiry['id'] ?>)">
                                            <i class="bi bi-reply"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&category=<?= $category_filter ?>&priority=<?= $priority_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No inquiries found matching your criteria.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Inquiry Details Modal -->
    <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inquiryModalLabel">Inquiry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="inquiryModalBody">
                    <!-- Content will be loaded via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showInquiryDetails(id) {
            // Find the inquiry data from the table
            const inquiries = <?= json_encode($inquiries) ?>;
            const inquiry = inquiries.find(i => i.id == id);
            
            if (inquiry) {
                const modalBody = document.getElementById('inquiryModalBody');
                modalBody.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Customer Name:</label>
                            <p>${inquiry.first_name} ${inquiry.last_name}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email:</label>
                            <p>${inquiry.email}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone:</label>
                            <p>${inquiry.phone || 'Not provided'}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Preferred Contact:</label>
                            <p>${inquiry.preferred_contact}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Category:</label>
                            <p><span class="badge bg-info">${inquiry.category}</span></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Priority:</label>
                            <p><span class="badge priority-${inquiry.priority}">${inquiry.priority}</span></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status:</label>
                            <p><span class="badge bg-primary">${inquiry.status.replace('_', ' ')}</span></p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Subject:</label>
                            <p>${inquiry.subject}</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Message:</label>
                            <div class="border rounded p-3 bg-light">
                                ${inquiry.message.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Created:</label>
                            <p>${new Date(inquiry.created_at).toLocaleString()}</p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Last Updated:</label>
                            <p>${new Date(inquiry.updated_at).toLocaleString()}</p>
                        </div>
                    </div>
                `;
                
                const modal = new bootstrap.Modal(document.getElementById('inquiryModal'));
                modal.show();
            }
        }

        function replyInquiry(id) {
            // This could open an email client or a reply form
            alert('Reply functionality can be implemented here. Inquiry ID: ' + id);
        }
    </script>
</body>
</html>