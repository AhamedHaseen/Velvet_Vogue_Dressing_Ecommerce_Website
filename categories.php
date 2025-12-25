<?php
include "db_connection.php";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = mysqli_real_escape_string($conn, $_POST['categoryName']);
                $description = mysqli_real_escape_string($conn, $_POST['categoryDescription']);
                $status = mysqli_real_escape_string($conn, $_POST['categoryStatus']);
                $sort_order = intval($_POST['sortOrder']);
                
                // Convert status to is_active (1 for active, 0 for inactive)
                $is_active = ($status === 'active') ? 1 : 0;
                
                $sql = "INSERT INTO categories (category_name, category_description, is_active, display_order, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssii", $name, $description, $is_active, $sort_order);
                
                if ($stmt->execute()) {
                    $success_message = "Category added successfully!";
                } else {
                    $error_message = "Error adding category: " . $conn->error;
                }
                break;
                
            case 'edit':
                $id = intval($_POST['categoryId']);
                $name = mysqli_real_escape_string($conn, $_POST['categoryName']);
                $description = mysqli_real_escape_string($conn, $_POST['categoryDescription']);
                $status = mysqli_real_escape_string($conn, $_POST['categoryStatus']);
                $sort_order = intval($_POST['sortOrder']);
                
                // Convert status to is_active (1 for active, 0 for inactive)
                $is_active = ($status === 'active') ? 1 : 0;
                
                $sql = "UPDATE categories SET category_name=?, category_description=?, is_active=?, display_order=? WHERE category_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiii", $name, $description, $is_active, $sort_order, $id);
                
                if ($stmt->execute()) {
                    $success_message = "Category updated successfully!";
                } else {
                    $error_message = "Error updating category: " . $conn->error;
                }
                break;
                
            case 'delete':
                $id = intval($_POST['categoryId']);
                
                $sql = "DELETE FROM categories WHERE category_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success_message = "Category deleted successfully!";
                } else {
                    $error_message = "Error deleting category: " . $conn->error;
                }
                break;
        }
    }
}

// Fetch categories
$categories = [];
$sql = "SELECT * FROM categories ORDER BY display_order ASC, category_name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get product count for each category (if products table exists)
$category_products = [];
$product_count_sql = "SELECT category_id, COUNT(*) as product_count FROM products GROUP BY category_id";
$product_result = $conn->query($product_count_sql);
if ($product_result) {
    while ($row = $product_result->fetch_assoc()) {
        $category_products[$row['category_id']] = $row['product_count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Categories - Velvet Vogue Admin</title>

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

    <!-- Google Fonts -->
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
      * {
        font-family: "Inter", sans-serif;
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
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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
        background: linear-gradient(
          180deg,
          #001d3d 0%,
          #012a5f 100%
        );
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
      }

      .header {
        background: var(--white);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px 30px;
        margin-bottom: 30px;
      }

      .content-card {
        background: var(--white);
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 30px;
      }

      .search-bar {
        background: #003049;
        color: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
      }

      .search-bar .form-control,
      .search-bar .form-select {
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 8px;
      }

      .btn-primary {
        background: #003049;
        border: none;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 600;
      }

      .category-card {
        background: var(--white);
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        overflow: hidden;
        margin-bottom: 20px;
      }

      .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      }

      .category-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 15px 15px 0 0;
      }

      .category-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        margin-bottom: 15px;
      }

      .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
      }

      .status-active {
        background: rgba(40, 167, 69, 0.1);
        color: var(--success-color);
      }

      .status-inactive {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger-color);
      }

      .category-stats {
        background: rgba(33, 158, 188, 0.1);
        border-radius: 8px;
        padding: 10px;
        margin-top: 15px;
      }

      .stats-item {
        text-align: center;
      }

      .stats-number {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary-color);
      }

      .stats-label {
        font-size: 0.8rem;
        color: var(--dark-color);
      }

      .tree-category {
        padding: 10px;
        margin: 5px 0;
        border-radius: 8px;
        background: rgba(33, 158, 188, 0.05);
        border-left: 3px solid var(--primary-color);
      }

      .sub-category {
        margin-left: 30px;
        padding: 8px;
        border-left: 2px solid var(--secondary-color);
        background: rgba(142, 202, 230, 0.1);
        border-radius: 6px;
      }

      .drag-handle {
        cursor: grab;
        color: var(--secondary-color);
      }

      .drag-handle:hover {
        color: var(--primary-color);
      }
    </style>
  </head>
  <body>
    <?php if (isset($success_message)): ?>
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?= $success_message ?>',
        timer: 3000,
        showConfirmButton: false
      });
    </script>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '<?= $error_message ?>',
        confirmButtonText: 'OK'
      });
    </script>
    <?php endif; ?>
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
          <a href="./products.php" class="nav-link">
            <i class="bi bi-bag-check"></i>
            Products
          </a>
        </div>
        <div class="nav-item">
          <a href="./orders.php" class="nav-link">
            <i class="bi bi-receipt"></i>
            Orders
          </a>
        </div>
        <div class="nav-item">
          <a href="./view_inquiry.php" class="nav-link">
            <i class="bi bi-envelope"></i>
            View Inquiry
          </a>
        </div>
        <div class="nav-item">
          <a href="./analytics.php" class="nav-link">
            <i class="bi bi-bar-chart"></i>
            Analytics
          </a>
        </div>
        <div class="nav-item">
          <a href="./categories.php" class="nav-link active">
            <i class="bi bi-tags"></i>
            Categories
          </a>
        </div>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Header -->
      <div class="header">
        <div class="d-flex align-items-center justify-content-between">
          <h2 class="mb-0" style="color: var(--dark-color)">
            Category Management
          </h2>
          <div>
            <button
              class="btn btn-outline-primary me-2"
              data-bs-toggle="modal"
              data-bs-target="#importModal"
            >
              <i class="bi bi-upload"></i> Import Categories
            </button>
            <button
              class="btn btn-primary"
              data-bs-toggle="modal"
              data-bs-target="#addCategoryModal"
            >
              <i class="bi bi-plus-circle"></i> Add Category
            </button>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="container-fluid px-4">
        <!-- Search and Filters -->
        <div class="search-bar">
          <h5 class="mb-3">Category Management</h5>
          <div class="row g-3">
            <div class="col-md-4">
              <div class="input-group">
                <span class="input-group-text bg-white border-0">
                  <i class="bi bi-search text-muted"></i>
                </span>
                <input
                  type="text"
                  class="form-control"
                  placeholder="Search categories..."
                />
              </div>
            </div>
            <div class="col-md-2">
              <select class="form-select">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="col-md-6">
              <div class="d-flex gap-2">
                <button class="btn btn-light">
                  <i class="bi bi-funnel"></i> Filter
                </button>
                <button class="btn btn-outline-light">
                  <i class="bi bi-arrow-clockwise"></i> Reset
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Simple Categories List -->
        <div class="content-card">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="card-title mb-0">Categories</h5>
            </div>

            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Category Name</th>
                    <th>Parent Category</th>
                    <th>Status</th>
                    <th>Products Count</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($category['category_name']) ?></strong></td>
                      <td>None</td>
                      <td>
                        <span class="badge bg-<?= $category['is_active'] == 1 ? 'success' : 'danger' ?>">
                          <?= $category['is_active'] == 1 ? 'Active' : 'Inactive' ?>
                        </span>
                      </td>
                      <td><?= isset($category_products[$category['category_id']]) ? $category_products[$category['category_id']] : 0 ?></td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editCategory(<?= $category['category_id'] ?>, '<?= addslashes($category['category_name']) ?>', '', '<?= addslashes($category['category_description']) ?>', '<?= $category['is_active'] == 1 ? 'active' : 'inactive' ?>', '<?= $category['display_order'] ?>')">
                          <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $category['category_id'] ?>, '<?= addslashes($category['category_name']) ?>')">
                          <i class="bi bi-trash"></i> Delete
                        </button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5" class="text-center py-4">
                        <i class="bi bi-folder-x fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No categories found. Add your first category!</p>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header text-white" style="background-color: #003049;">
            <h5 class="modal-title">Add New Category</h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              data-bs-dismiss="modal"
            ></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="" id="addCategoryForm">
              <input type="hidden" name="action" value="add">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="categoryName" class="form-label">Category Name</label>
                  <input type="text" class="form-control" name="categoryName" id="categoryName" required>
                </div>
                <div class="col-md-6">
                  <label for="categoryStatus" class="form-label">Status</label>
                  <select class="form-select" name="categoryStatus" id="categoryStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
                <div class="col-12">
                  <label for="categoryDescription" class="form-label">Description</label>
                  <textarea class="form-control" name="categoryDescription" id="categoryDescription" rows="3"></textarea>
                </div>
                <div class="col-md-6">
                  <label for="sortOrder" class="form-label">Sort Order</label>
                  <input type="number" class="form-control" name="sortOrder" id="sortOrder" value="0">
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="addCategoryForm" class="btn btn-primary">Create Category</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Import Categories Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header text-white" style="background-color: #003049;">
            <h5 class="modal-title">Import Categories</h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              data-bs-dismiss="modal"
            ></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="importFile" class="form-label">Choose CSV File</label>
              <input
                type="file"
                class="form-control"
                id="importFile"
                accept=".csv"
              />
              <div class="form-text">
                Upload a CSV file with categories data
              </div>
            </div>

            <div class="alert alert-info">
              <h6 class="alert-heading">CSV Format Requirements:</h6>
              <ul class="mb-0 small">
                <li>Columns: name, description, parent_category, status</li>
                <li>Use commas as separators</li>
                <li>First row should contain headers</li>
              </ul>
            </div>

            <div class="mb-3">
              <a href="#" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-download"></i> Download Sample CSV
              </a>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Cancel
            </button>
            <button type="button" class="btn btn-primary">
              Import Categories
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script>
      // Edit Category Function
      function editCategory(id, name, parent, description, status, sortOrder) {
        Swal.fire({
          title: 'Edit Category',
          html: `
            <form id="editForm">
              <div class="mb-3">
                <label class="form-label">Category Name</label>
                <input type="text" class="form-control" id="editName" value="${name}" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="editDescription" rows="3">${description || ''}</textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="editStatus">
                  <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
                  <option value="inactive" ${status === 'inactive' ? 'selected' : ''}>Inactive</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Sort Order</label>
                <input type="number" class="form-control" id="editSortOrder" value="${sortOrder}">
              </div>
            </form>
          `,
          showCancelButton: true,
          confirmButtonText: 'Update Category',
          cancelButtonText: 'Cancel',
          preConfirm: () => {
            const name = document.getElementById('editName').value;
            const description = document.getElementById('editDescription').value;
            const status = document.getElementById('editStatus').value;
            const sortOrder = document.getElementById('editSortOrder').value;
            
            if (!name.trim()) {
              Swal.showValidationMessage('Please enter a category name');
              return false;
            }
            
            return { name, description, status, sortOrder };
          }
        }).then((result) => {
          if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'edit');
            formData.append('categoryId', id);
            formData.append('categoryName', result.value.name);
            formData.append('categoryDescription', result.value.description);
            formData.append('categoryStatus', result.value.status);
            formData.append('sortOrder', result.value.sortOrder);
            
            fetch('', {
              method: 'POST',
              body: formData
            }).then(() => {
              location.reload();
            });
          }
        });
      }

      // Delete Category Function
      function deleteCategory(id, name) {
        Swal.fire({
          title: 'Delete Category',
          text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, Delete',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('categoryId', id);
            
            fetch('', {
              method: 'POST',
              body: formData
            }).then(() => {
              location.reload();
            });
          }
        });
      }

      // Search functionality
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[placeholder="Search categories..."]');
        if (searchInput) {
          searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
              const categoryName = row.querySelector('td:first-child')?.textContent.toLowerCase();
              if (categoryName && categoryName.includes(searchTerm)) {
                row.style.display = '';
              } else {
                row.style.display = 'none';
              }
            });
          });
        }
      });
    </script>
    </div>
  </body>
</html>

