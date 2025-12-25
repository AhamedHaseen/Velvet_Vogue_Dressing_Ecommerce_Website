<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Order - Velvet Vogue Admin</title>

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

      .product-item {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: var(--light-bg);
      }

      .total-summary {
        background: linear-gradient(
          45deg,
          var(--primary-color),
          var(--secondary-color)
        );
        color: white;
        border-radius: 10px;
        padding: 20px;
      }

      .customer-info {
        background: var(--light-bg);
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #e0e0e0;
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
          <h2 class="mb-0" style="color: var(--dark-color)">
            Create New Order
          </h2>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container">
      <div class="row">
        <div class="col-lg-8">
          <!-- Customer Selection -->
          <div class="content-card">
            <div class="card-body p-4">
              <h5 class="card-title mb-4">Customer Information</h5>
              <form id="orderForm">
                <div class="row">
                  <div class="col-md-8 mb-3">
                    <label for="customerSearch" class="form-label"
                      >Search Customer *</label
                    >
                    <div class="input-group">
                      <input
                        type="text"
                        class="form-control"
                        id="customerSearch"
                        placeholder="Search by name, email, or phone..."
                        required
                      />
                      <button
                        class="btn btn-outline-secondary"
                        type="button"
                        id="newCustomerBtn"
                      >
                        <i class="bi bi-person-plus"></i> New Customer
                      </button>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="orderDate" class="form-label">Order Date</label>
                    <input
                      type="date"
                      class="form-control"
                      id="orderDate"
                      value="2025-10-18"
                    />
                  </div>
                </div>

                <!-- Selected Customer Info -->
                <div
                  class="customer-info"
                  id="customerInfo"
                  style="display: none"
                >
                  <h6 class="mb-2">Selected Customer</h6>
                  <div class="row">
                    <div class="col-md-6">
                      <strong id="customerName">Jane Doe</strong><br />
                      <span id="customerEmail">jane@example.com</span>
                    </div>
                    <div class="col-md-6 text-end">
                      <span id="customerPhone">+1 (555) 123-4567</span><br />
                      <small class="text-muted">Customer since 2023</small>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Products Section -->
          <div class="content-card">
            <div class="card-body p-4">
              <div
                class="d-flex justify-content-between align-items-center mb-4"
              >
                <h5 class="card-title mb-0">Order Items</h5>
                <button
                  type="button"
                  class="btn btn-outline-primary"
                  id="addProductBtn"
                >
                  <i class="bi bi-plus"></i> Add Product
                </button>
              </div>

              <div id="productsList">
                <!-- Product items will be added here -->
              </div>

              <!-- Add Product Form -->
              <div
                class="product-item"
                id="addProductForm"
                style="display: none"
              >
                <div class="row">
                  <div class="col-md-5 mb-3">
                    <label class="form-label">Product</label>
                    <select class="form-select" id="productSelect">
                      <option value="">Select Product</option>
                      <option value="1" data-price="129.99">
                        Velvet Evening Dress - $129.99
                      </option>
                      <option value="2" data-price="89.50">
                        Silk Blouse - $89.50
                      </option>
                      <option value="3" data-price="249.00">
                        Designer Handbag - $249.00
                      </option>
                      <option value="4" data-price="179.99">
                        Casual Jacket - $179.99
                      </option>
                    </select>
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="form-label">Quantity</label>
                    <input
                      type="number"
                      class="form-control"
                      id="productQuantity"
                      value="1"
                      min="1"
                    />
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="form-label">Price</label>
                    <input
                      type="number"
                      class="form-control"
                      id="productPrice"
                      step="0.01"
                      readonly
                    />
                  </div>
                  <div class="col-md-2 mb-3">
                    <label class="form-label">Total</label>
                    <input
                      type="number"
                      class="form-control"
                      id="productTotal"
                      step="0.01"
                      readonly
                    />
                  </div>
                  <div class="col-md-1 mb-3 d-flex align-items-end">
                    <button
                      type="button"
                      class="btn btn-success w-100"
                      id="confirmAddProduct"
                    >
                      <i class="bi bi-check"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Shipping & Payment -->
          <div class="content-card">
            <div class="card-body p-4">
              <h5 class="card-title mb-4">Shipping & Payment</h5>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="shippingMethod" class="form-label"
                    >Shipping Method</label
                  >
                  <select class="form-select" id="shippingMethod">
                    <option value="standard">Standard Shipping - $5.99</option>
                    <option value="express">Express Shipping - $12.99</option>
                    <option value="overnight">
                      Overnight Shipping - $24.99
                    </option>
                    <option value="pickup">Store Pickup - Free</option>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="paymentMethod" class="form-label"
                    >Payment Method</label
                  >
                  <select class="form-select" id="paymentMethod">
                    <option value="credit_card">Credit Card</option>
                    <option value="debit_card">Debit Card</option>
                    <option value="paypal">PayPal</option>
                    <option value="cash">Cash on Delivery</option>
                    <option value="bank_transfer">Bank Transfer</option>
                  </select>
                </div>
              </div>

              <div class="mb-3">
                <label for="orderNotes" class="form-label">Order Notes</label>
                <textarea
                  class="form-control"
                  id="orderNotes"
                  rows="3"
                  placeholder="Special instructions or notes..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <!-- Order Summary -->
          <div class="content-card">
            <div class="card-body p-4">
              <h5 class="card-title mb-4">Order Summary</h5>
              <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span id="subtotalAmount">$0.00</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Shipping:</span>
                <span id="shippingAmount">$5.99</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Tax (8.5%):</span>
                <span id="taxAmount">$0.00</span>
              </div>
              <hr />
              <div class="total-summary text-center">
                <h4 class="mb-0">Total: <span id="totalAmount">$5.99</span></h4>
              </div>
            </div>
          </div>

          <!-- Order Status -->
          <div class="content-card">
            <div class="card-body p-4">
              <h5 class="card-title mb-4">Order Settings</h5>

              <div class="mb-3">
                <label for="orderStatus" class="form-label">Order Status</label>
                <select class="form-select" id="orderStatus">
                  <option value="pending">Pending</option>
                  <option value="processing">Processing</option>
                  <option value="shipped">Shipped</option>
                  <option value="delivered">Delivered</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="orderPriority" class="form-label">Priority</label>
                <select class="form-select" id="orderPriority">
                  <option value="normal">Normal</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>

              <div class="form-check mb-3">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="sendConfirmationEmail"
                  checked
                />
                <label class="form-check-label" for="sendConfirmationEmail">
                  Send Confirmation Email
                </label>
              </div>

              <div class="form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="requireSignature"
                />
                <label class="form-check-label" for="requireSignature">
                  Require Signature on Delivery
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
                <button type="button" class="btn btn-outline-primary">
                  Save as Draft
                </button>
                <button type="submit" form="orderForm" class="btn btn-primary">
                  Create Order
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
      let orderItems = [];

      // Customer search simulation
      document
        .getElementById("customerSearch")
        .addEventListener("input", function (e) {
          const value = e.target.value;
          if (value.length > 2) {
            // Simulate finding a customer
            document.getElementById("customerInfo").style.display = "block";
          } else {
            document.getElementById("customerInfo").style.display = "none";
          }
        });

      // Add product button
      document
        .getElementById("addProductBtn")
        .addEventListener("click", function () {
          document.getElementById("addProductForm").style.display = "block";
          this.style.display = "none";
        });

      // Product selection
      document
        .getElementById("productSelect")
        .addEventListener("change", function (e) {
          const selectedOption = e.target.selectedOptions[0];
          const price = selectedOption.getAttribute("data-price");
          document.getElementById("productPrice").value = price || "";
          calculateProductTotal();
        });

      // Quantity change
      document
        .getElementById("productQuantity")
        .addEventListener("input", calculateProductTotal);

      function calculateProductTotal() {
        const price =
          parseFloat(document.getElementById("productPrice").value) || 0;
        const quantity =
          parseInt(document.getElementById("productQuantity").value) || 0;
        const total = price * quantity;
        document.getElementById("productTotal").value = total.toFixed(2);
      }

      // Confirm add product
      document
        .getElementById("confirmAddProduct")
        .addEventListener("click", function () {
          const productSelect = document.getElementById("productSelect");
          const quantity = document.getElementById("productQuantity").value;
          const price = document.getElementById("productPrice").value;
          const total = document.getElementById("productTotal").value;

          if (productSelect.value && quantity && price) {
            const item = {
              name: productSelect.selectedOptions[0].text,
              quantity: parseInt(quantity),
              price: parseFloat(price),
              total: parseFloat(total),
            };

            orderItems.push(item);
            displayOrderItems();

            // Reset form
            document.getElementById("addProductForm").style.display = "none";
            document.getElementById("addProductBtn").style.display = "block";
            productSelect.value = "";
            document.getElementById("productQuantity").value = "1";
            document.getElementById("productPrice").value = "";
            document.getElementById("productTotal").value = "";

            calculateOrderTotal();
          }
        });

      function displayOrderItems() {
        const productsList = document.getElementById("productsList");
        productsList.innerHTML = "";

        orderItems.forEach((item, index) => {
          const itemHTML = `
                    <div class="product-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${item.name}</h6>
                                <small class="text-muted">Qty: ${
                                  item.quantity
                                } × $${item.price.toFixed(2)}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold">$${item.total.toFixed(
                                  2
                                )}</span>
                                <button class="btn btn-outline-danger btn-sm" onclick="removeItem(${index})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
          productsList.innerHTML += itemHTML;
        });
      }

      function removeItem(index) {
        orderItems.splice(index, 1);
        displayOrderItems();
        calculateOrderTotal();
      }

      function calculateOrderTotal() {
        const subtotal = orderItems.reduce((sum, item) => sum + item.total, 0);
        const shipping = parseFloat(
          document
            .getElementById("shippingMethod")
            .selectedOptions[0].text.match(/\$(\d+\.\d+)/)?.[1] || 0
        );
        const tax = subtotal * 0.085; // 8.5% tax
        const total = subtotal + shipping + tax;

        document.getElementById(
          "subtotalAmount"
        ).textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById(
          "shippingAmount"
        ).textContent = `$${shipping.toFixed(2)}`;
        document.getElementById("taxAmount").textContent = `$${tax.toFixed(2)}`;
        document.getElementById("totalAmount").textContent = `$${total.toFixed(
          2
        )}`;
      }

      // Shipping method change
      document
        .getElementById("shippingMethod")
        .addEventListener("change", calculateOrderTotal);

      // Form submission
      document
        .getElementById("orderForm")
        .addEventListener("submit", function (e) {
          e.preventDefault();

          if (orderItems.length === 0) {
            alert("Please add at least one product to the order.");
            return;
          }

          const orderData = {
            customer: document.getElementById("customerSearch").value,
            items: orderItems,
            shipping: document.getElementById("shippingMethod").value,
            payment: document.getElementById("paymentMethod").value,
            status: document.getElementById("orderStatus").value,
            priority: document.getElementById("orderPriority").value,
            notes: document.getElementById("orderNotes").value,
            confirmationEmail: document.getElementById("sendConfirmationEmail")
              .checked,
            requireSignature:
              document.getElementById("requireSignature").checked,
          };

          // Here you would normally send the data to your backend
          alert("Order created successfully!");
          console.log("Order data:", orderData);
        });

      // Initialize
      calculateOrderTotal();
    </script>
  </body>
</html>

