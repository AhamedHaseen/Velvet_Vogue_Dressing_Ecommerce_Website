<?php
include "db_connection.php";

// Fetch all products from database
$sql = "SELECT p.*, c.category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Products - Velvet Vogue Admin</title>

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

    <!-- SweetAlert2 -->
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
        background: var(--light-bg);
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

      .btn-primary {
        background: linear-gradient(
          45deg,
          var(--primary-color),
          var(--secondary-color)
        );
        border: none;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 600;
      }

      .table th {
        border-top: none;
        border-bottom: 2px solid #f0f0f0;
        font-weight: 600;
        color: var(--dark-color);
        padding: 15px;
      }

      .table td {
        padding: 15px;
        vertical-align: middle;
        border-top: 1px solid #f0f0f0;
      }

      .table tbody tr:hover {
        background-color: #f8f9fa;
      }

      .product-image {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
      }

      .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
      }

      .status-active {
        background: rgba(40, 167, 69, 0.1);
        color: var(--success-color);
      }

      .status-inactive {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
      }

      .status-draft {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
      }

      .status-archived {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger-color);
      }

      .filter-section {
        background: #003049;
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 30px;
      }

      .btn-primary {
        background: #003049;
        border-color: #003049;
      }

      .btn-primary:hover {
        background: #002438;
        border-color: #002438;
      }

      .filter-section .form-control,
      .filter-section .form-select {
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 8px;
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
          <a href="./products.php" class="nav-link active">
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
          <a href="./categories.php" class="nav-link">
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
            Products Management
          </h2>
          <div>
            <button class="btn btn-outline-primary me-2">
              <i class="bi bi-download"></i> Export
            </button>
            <a href="add_product.php" class="btn btn-primary">
              <i class="bi bi-plus"></i> Add Product
            </a>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="container-fluid px-4">
        <!-- Alert Container -->
        <div id="alertContainer" class="mb-4"></div>
        
        <!-- Filters -->
        <div class="filter-section">
          <h5 class="mb-3">Filter Products</h5>
          <div class="row">
            <div class="col-md-3 mb-3">
              <input
                type="text"
                class="form-control"
                placeholder="Search products..."
                id="searchInput"
              />
            </div>
            <div class="col-md-2 mb-3">
              <select class="form-select" id="categoryFilter">
                <option value="">All Categories</option>
                <option value="dresses">Dresses</option>
                <option value="blouses">Blouses</option>
                <option value="handbags">Handbags</option>
                <option value="jackets">Jackets</option>
                <option value="accessories">Accessories</option>
              </select>
            </div>
            <div class="col-md-2 mb-3">
              <select class="form-select" id="statusFilter">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div class="col-md-2 mb-3">
              <select class="form-select" id="stockFilter">
                <option value="">All Stock</option>
                <option value="in-stock">In Stock</option>
                <option value="low-stock">Low Stock</option>
                <option value="out-of-stock">Out of Stock</option>
              </select>
            </div>
            <div class="col-md-3 mb-3 d-flex gap-2">
              <button class="btn btn-light flex-fill">
                <i class="bi bi-funnel"></i> Apply
              </button>
              <button class="btn btn-outline-light">
                <i class="bi bi-arrow-clockwise"></i> Reset
              </button>
            </div>
          </div>
        </div>

        <!-- Products Table -->
        <div class="content-card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <div style="width: 50px; height: 50px; margin-right: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                              <?php 
                              $image_url = $product['image_url'] ?? 'Images/default-product.jpg';
                              // Clean up the image path - remove ./ if present
                              $image_url = str_replace('./', '', $image_url);
                              ?>
                              <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                   alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                   style="width: 48px; height: 48px; object-fit: cover; border-radius: 7px;"
                                   onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'bi bi-image\' style=\'font-size: 20px; color: #6c757d;\'></i>';">
                            </div>
                            <div>
                              <div class="fw-bold"><?php echo htmlspecialchars($product['product_name']); ?></div>
                              <small class="text-muted"><?php echo htmlspecialchars(substr($product['product_description'] ?? '', 0, 40)); ?><?php echo strlen($product['product_description'] ?? '') > 40 ? '...' : ''; ?></small>
                            </div>
                          </div>
                        </td>
                        <td>VV-<?php echo str_pad($product['product_id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td>
                          <span class="badge bg-<?php echo $product['stock_quantity'] > 10 ? 'success' : ($product['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>">
                            <?php echo $product['stock_quantity']; ?> units
                          </span>
                        </td>
                        <td>
                          <span class="status-badge <?php echo ($product['stock_quantity'] > 0) ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo ($product['stock_quantity'] > 0) ? 'Active' : 'Out of Stock'; ?>
                          </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                        <td>
                          <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" title="Edit" onclick="editProduct(<?php echo $product['product_id']; ?>)">
                              <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-secondary" title="View" onclick="viewProduct(<?php echo $product['product_id']; ?>)">
                              <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-danger" title="Delete" onclick="deleteProduct(<?php echo $product['product_id']; ?>, '<?php echo addslashes($product['product_name']); ?>')">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="8" class="text-center py-4">
                        <div class="text-muted">
                          <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                          <h5>No products found</h5>
                          <p>Start by adding your first product!</p>
                          <a href="add_product.php" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Add Product
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

            <!-- Pagination -->
            <div class="p-4 border-top">
              <nav aria-label="Products pagination">
                <ul class="pagination justify-content-center mb-0">
                  <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                  </li>
                  <li class="page-item active">
                    <a class="page-link" href="#">1</a>
                  </li>
                  <li class="page-item">
                    <a class="page-link" href="#">2</a>
                  </li>
                  <li class="page-item">
                    <a class="page-link" href="#">3</a>
                  </li>
                  <li class="page-item">
                    <a class="page-link" href="#">Next</a>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <!-- Bootstrap JS -->
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

      <!-- Custom JS -->
      <script>
        // Bootstrap Alert Functions
        function showAlert(message, type = 'success') {
          const alertContainer = document.getElementById('alertContainer');
          const alertId = 'alert-' + Date.now();
          
          const alertHTML = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
              ${message}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          `;
          
          alertContainer.innerHTML = alertHTML;
          
          // Auto-dismiss after 5 seconds
          setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
              const bsAlert = new bootstrap.Alert(alert);
              bsAlert.close();
            }
          }, 5000);
        }

        // Search functionality
        document
          .getElementById("searchInput")
          .addEventListener("input", function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll("tbody tr");

            rows.forEach((row) => {
              const productName = row
                .querySelector(".fw-bold")
                ?.textContent.toLowerCase() || '';
              const sku = row.cells[1]?.textContent.toLowerCase() || '';

              if (
                productName.includes(searchTerm) ||
                sku.includes(searchTerm)
              ) {
                row.style.display = "";
              } else {
                row.style.display = "none";
              }
            });
          });

        // Product management functions
        function editProduct(productId) {
          // For now, just redirect to add product page with edit parameter
          window.location.href = `add_product.php?edit=${productId}`;
        }

        function viewProduct(productId) {
          // Fetch product details and show in Bootstrap modal
          fetch(`get_product_details.php?id=${productId}`)
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                const product = data.product;
                showAlert(`
                  <strong>${product.product_name}</strong><br>
                  <small>Category: ${product.category_name || 'N/A'}</small><br>
                  <small>Price: $${parseFloat(product.price).toFixed(2)}</small><br>
                  <small>Stock: ${product.stock_quantity} units</small><br>
                  <small>Description: ${product.product_description || 'No description'}</small>
                `, 'info');
              } else {
                showAlert('Error loading product details: ' + data.message, 'danger');
              }
            })
            .catch(error => {
              showAlert('Error loading product details: ' + error.message, 'danger');
            });
        }

        function deleteProduct(productId, productName) {
          Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete "${productName}"? This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
          }).then((result) => {
            if (result.isConfirmed) {
              // Show loading alert
              Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the product',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                  Swal.showLoading();
                }
              });
              
              // Send delete request
              fetch(`delete_product.php?id=${productId}`, {
                method: 'DELETE'
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  Swal.fire({
                    title: 'Deleted!',
                    text: `Product "${productName}" has been deleted successfully.`,
                    icon: 'success',
                    confirmButtonColor: '#28a745'
                  }).then(() => {
                    location.reload();
                  });
                } else {
                  Swal.fire({
                    title: 'Error!',
                    text: 'Error deleting product: ' + data.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                  });
                }
              })
              .catch(error => {
                Swal.fire({
                  title: 'Error!',
                  text: 'Error deleting product: ' + error.message,
                  icon: 'error',
                  confirmButtonColor: '#dc3545'
                });
              });
            }
          });
        }

        // Filter functionality implementation
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const statusFilter = document.getElementById('statusFilter');
        const stockFilter = document.getElementById('stockFilter');
        const productTable = document.querySelector('tbody');
        const productRows = Array.from(productTable.querySelectorAll('tr'));
        
        function filterProducts() {
          const searchTerm = searchInput.value.toLowerCase();
          const selectedCategory = categoryFilter.value.toLowerCase();
          const selectedStatus = statusFilter.value.toLowerCase();
          const selectedStock = stockFilter.value.toLowerCase();
          
          productRows.forEach(row => {
            // Skip the "no products" row if it exists
            if (row.querySelector('td[colspan]')) {
              return;
            }
            
            const productName = row.querySelector('td:first-child .fw-bold')?.textContent.toLowerCase() || '';
            const productDescription = row.querySelector('td:first-child .text-muted')?.textContent.toLowerCase() || '';
            const category = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
            const status = row.querySelector('td:nth-child(6) .status-badge')?.textContent.toLowerCase() || '';
            const stockBadge = row.querySelector('td:nth-child(5) .badge')?.textContent || '';
            const stockNumber = parseInt(stockBadge.match(/\\d+/)?.[0] || '0');
            
            // Search filter
            const matchesSearch = searchTerm === '' || 
                                productName.includes(searchTerm) || 
                                productDescription.includes(searchTerm);
            
            // Category filter
            const matchesCategory = selectedCategory === '' || category.includes(selectedCategory);
            
            // Status filter
            const matchesStatus = selectedStatus === '' || 
                                 (selectedStatus === 'active' && status.includes('active')) ||
                                 (selectedStatus === 'draft' && status.includes('draft')) ||
                                 (selectedStatus === 'archived' && status.includes('archived'));
            
            // Stock filter
            let matchesStock = true;
            if (selectedStock !== '') {
              if (selectedStock === 'in-stock') {
                matchesStock = stockNumber > 10;
              } else if (selectedStock === 'low-stock') {
                matchesStock = stockNumber > 0 && stockNumber <= 10;
              } else if (selectedStock === 'out-of-stock') {
                matchesStock = stockNumber === 0;
              }
            }
            
            // Show/hide row based on all filters
            if (matchesSearch && matchesCategory && matchesStatus && matchesStock) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
          
          // Show "no results" message if all rows are hidden
          const visibleRows = productRows.filter(row => 
            row.style.display !== 'none' && !row.querySelector('td[colspan]')
          );
          
          const noResultsRow = productTable.querySelector('tr td[colspan]')?.parentElement;
          if (visibleRows.length === 0 && !noResultsRow) {
            const newNoResultsRow = document.createElement('tr');
            newNoResultsRow.innerHTML = `
              <td colspan="8" class="text-center py-4">
                <div class="text-muted">
                  <i class="bi bi-search fs-1 d-block mb-2"></i>
                  <h5>No products match your filters</h5>
                  <p>Try adjusting your search criteria</p>
                </div>
              </td>
            `;
            newNoResultsRow.id = 'noResultsRow';
            productTable.appendChild(newNoResultsRow);
          } else if (visibleRows.length > 0) {
            const noResultsRowToRemove = document.getElementById('noResultsRow');
            if (noResultsRowToRemove) {
              noResultsRowToRemove.remove();
            }
          }
        }
        
        // Add event listeners for all filters
        searchInput.addEventListener('input', filterProducts);
        categoryFilter.addEventListener('change', filterProducts);
        statusFilter.addEventListener('change', filterProducts);
        stockFilter.addEventListener('change', filterProducts);
        
        // Apply and Clear buttons functionality
        const applyBtn = document.querySelector('.btn-light');
        const clearBtn = document.querySelector('.btn-outline-light');
        
        if (applyBtn) {
          applyBtn.addEventListener('click', filterProducts);
        }
        
        if (clearBtn) {
          clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            categoryFilter.value = '';
            statusFilter.value = '';
            stockFilter.value = '';
            filterProducts();
          });
        }
      </script>
    </div>
  </body>
</html>

