<?php
// Check if we're in edit mode
$edit_mode = isset($_GET['edit']) && !empty($_GET['edit']);
$product_data = null;

if ($edit_mode) {
    include "db_connection.php";
    $product_id = intval($_GET['edit']);
    
    $sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product_data = $result->fetch_assoc();
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add New Product - Velvet Vogue Admin</title>

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
      }

      body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
      }

      .header {
        background: var(--white);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px 0;
        margin-bottom: 30px;
      }

      .content-card {
        background: var(--white);
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 30px;
      }

      .form-label {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 8px;
      }

      .form-control,
      .form-select {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        padding: 12px 15px;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
      }

      .form-control:focus,
      .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(33, 158, 188, 0.25);
      }

      .btn-primary {
        background: linear-gradient(
          45deg,
          var(--primary-color),
          var(--secondary-color)
        );
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
      }

      .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
      }

      .image-upload-area {
        border: 2px dashed #e0e0e0;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        transition: border-color 0.3s ease;
        cursor: pointer;
      }

      .image-upload-area:hover {
        border-color: var(--primary-color);
      }

      .image-preview {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        margin: 10px;
      }
    </style>
  </head>
  <body>
    <!-- Header -->
    <div class="header">
      <div class="container">
        <div class="d-flex align-items-center">
          <a href="adm_dashboard.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
          </a>
          <h2 class="mb-0" style="color: var(--dark-color)"><?php echo $edit_mode ? 'Edit Product' : 'Add New Product'; ?></h2>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container">
      <!-- Alert Container -->
      <div id="alertContainer" class="mb-4"></div>
      
      <div class="row">
        <div class="col-lg-8">
          <div class="content-card">
            <div class="card-body p-4">
              <h5 class="card-title mb-4"><?php echo $edit_mode ? 'Edit Product Information' : 'Product Information'; ?></h5>
              <form id="productForm">
                <?php if ($edit_mode && $product_data): ?>
                <input type="hidden" id="productId" value="<?php echo htmlspecialchars($product_data['product_id']); ?>">
                <?php endif; ?>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="productName" class="form-label"
                      >Product Name *</label
                    >
                    <input
                      type="text"
                      class="form-control"
                      id="productName"
                      value="<?php echo $edit_mode && $product_data ? htmlspecialchars($product_data['product_name']) : ''; ?>"
                      required
                    />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="productSKU" class="form-label">SKU *</label>
                    <input
                      type="text"
                      class="form-control"
                      id="productSKU"
                      value="<?php echo $edit_mode && $product_data ? 'PRD-' . str_pad($product_data['product_id'], 4, '0', STR_PAD_LEFT) : ''; ?>"
                      required
                    />
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="category" class="form-label">Category *</label>
                    <select class="form-select" id="category" required>
                      <option value="">Select Category</option>
                      <option value="Girls Fashion" <?php echo ($edit_mode && $product_data && strtolower($product_data['category_name']) == 'girls fashion') ? 'selected' : ''; ?>>Girls Fashion</option>
                      <option value="Boys Fashion" <?php echo ($edit_mode && $product_data && strtolower($product_data['category_name']) == 'boys fashion') ? 'selected' : ''; ?>>Boys Fashion</option>
                      <option value="Formal Wear" <?php echo ($edit_mode && $product_data && strtolower($product_data['category_name']) == 'formal wear') ? 'selected' : ''; ?>>Formal Wear</option>
                      <option value="Casual Wear" <?php echo ($edit_mode && $product_data && strtolower($product_data['category_name']) == 'casual wear') ? 'selected' : ''; ?>>Casual Wear</option>
                      <option value="Accessories" <?php echo ($edit_mode && $product_data && strtolower($product_data['category_name']) == 'accessories') ? 'selected' : ''; ?>>Accessories</option>
                      <option value="Footwear" <?php echo ($edit_mode && $product_data && strtolower($product_data['category_name']) == 'footwear') ? 'selected' : ''; ?>>Footwear</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="brand" class="form-label">Brand</label>
                    <input type="text" class="form-control" id="brand" value="<?php echo $edit_mode && $product_data ? htmlspecialchars($product_data['brand'] ?? '') : ''; ?>" />
                  </div>
                </div>

                <div class="mb-3">
                  <label for="description" class="form-label"
                    >Description</label
                  >
                  <textarea
                    class="form-control"
                    id="description"
                    rows="4"
                    placeholder="Enter product description..."
                  ><?php echo $edit_mode && $product_data ? htmlspecialchars($product_data['product_description'] ?? '') : ''; ?></textarea>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="price" class="form-label">Price ($) *</label>
                    <input
                      type="number"
                      class="form-control"
                      id="price"
                      step="0.01"
                      value="<?php echo $edit_mode && $product_data ? $product_data['price'] : ''; ?>"
                      required
                    />
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="inventory" class="form-label"
                      >Inventory Quantity *</label
                    >
                    <input
                      type="number"
                      class="form-control"
                      id="inventory"
                      value="<?php echo $edit_mode && $product_data ? $product_data['stock_quantity'] : ''; ?>"
                      required
                    />
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <!-- Product Images -->
          <div class="content-card">
            <div class="card-body p-4">
              <h5 class="card-title mb-4">Product Images</h5>
              <div
                class="image-upload-area mb-3"
                onclick="document.getElementById('imageInput').click()"
              >
                <i
                  class="bi bi-cloud-upload"
                  style="font-size: 2rem; color: var(--primary-color)"
                ></i>
                <p class="mt-2 mb-0">Click to upload images</p>
                <small class="text-muted">JPG, PNG up to 5MB</small>
              </div>
              <input
                type="file"
                id="imageInput"
                multiple
                accept="image/*"
                style="display: none"
              />
              <div id="imagePreview" class="d-flex flex-wrap"></div>
            </div>
          </div>

          <!-- Product Status -->
          <div class="content-card">
            <div class="card-body p-4">
              <h5 class="card-title mb-4">Product Status</h5>
              <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status">
                  <option value="active" <?php echo ($edit_mode && $product_data && $product_data['stock_quantity'] > 0) ? 'selected' : 'selected'; ?>>Active</option>
                  <option value="draft" <?php echo ($edit_mode && $product_data && $product_data['stock_quantity'] == 0) ? 'selected' : ''; ?>>Draft</option>
                  <option value="archived">Archived</option>
                </select>
              </div>

              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="featured" <?php echo ($edit_mode && $product_data && $product_data['is_featured']) ? 'checked' : ''; ?> />
                <label class="form-check-label" for="featured">
                  Featured Product
                </label>
              </div>

              <div class="form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="trackInventory"
                  checked
                />
                <label class="form-check-label" for="trackInventory">
                  Track Inventory
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="row">
        <div class="col-12">
          <div class="content-card">
            <div class="card-body p-4">
              <div class="d-flex justify-content-end gap-3">
                <button
                  type="button"
                  class="btn btn-secondary"
                  onclick="window.history.back()"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  form="productForm"
                  class="btn btn-primary"
                >
                  <?php echo $edit_mode ? 'Update Product' : 'Save Product'; ?>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script>
      // Bootstrap Alert Function
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

      // Image upload preview
      document
        .getElementById("imageInput")
        .addEventListener("change", function (e) {
          const preview = document.getElementById("imagePreview");
          preview.innerHTML = "";

          for (let i = 0; i < e.target.files.length; i++) {
            const file = e.target.files[i];
            const reader = new FileReader();

            reader.onload = function (e) {
              const img = document.createElement("img");
              img.src = e.target.result;
              img.className = "image-preview";
              preview.appendChild(img);
            };

            reader.readAsDataURL(file);
          }
        });

      // Form submission
      document
        .getElementById("productForm")
        .addEventListener("submit", function (e) {
          e.preventDefault();

          // Collect form data using FormData for image upload
          const isEditMode = document.getElementById("productId");
          const formData = new FormData();
          
          // Add basic product data
          formData.append('name', document.getElementById("productName").value);
          formData.append('sku', document.getElementById("productSKU").value);
          formData.append('category', document.getElementById("category").value);
          formData.append('brand', document.getElementById("brand").value);
          formData.append('description', document.getElementById("description").value);
          formData.append('price', document.getElementById("price").value);
          formData.append('inventory', document.getElementById("inventory").value);
          formData.append('status', document.getElementById("status").value);
          formData.append('featured', document.getElementById("featured").checked);
          formData.append('trackInventory', document.getElementById("trackInventory").checked);

          // Add product ID if in edit mode
          if (isEditMode) {
            formData.append('productId', isEditMode.value);
          }

          // Add image file
          const imageInput = document.getElementById("imageInput");
          if (imageInput.files.length > 0) {
            formData.append('productImage', imageInput.files[0]);
          }

          // Send data to backend
          fetch('save_product.php', {
            method: 'POST',
            body: formData  // Don't set Content-Type for FormData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const action = isEditMode ? 'updated' : 'added';
              showAlert(`Product ${action} successfully!`, 'success');
              // Redirect to products page after showing alert
              setTimeout(() => {
                window.location.href = 'products.php';
              }, 2000);
            } else {
              showAlert("Error: " + data.message, 'danger');
            }
          })
          .catch(error => {
            showAlert("Error saving product: " + error.message, 'danger');
            console.error('Error:', error);
          });
        });

      // Auto-generate SKU based on product name
      document
        .getElementById("productName")
        .addEventListener("input", function (e) {
          const name = e.target.value;
          const sku = name.toUpperCase().replace(/\s+/g, "-").substring(0, 10);
          if (sku && !document.getElementById("productSKU").value) {
            document.getElementById("productSKU").value = "VV-" + sku;
          }
        });
    </script>
  </body>
</html>

