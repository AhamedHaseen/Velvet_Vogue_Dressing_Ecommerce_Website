<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Reports - Velvet Vogue Admin</title>

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

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

      .stats-card {
        background: var(--white);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        position: relative;
        overflow: hidden;
      }

      .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      }

      .stats-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(
          90deg,
          var(--primary-color),
          var(--secondary-color)
        );
      }

      .stats-card .icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        margin-bottom: 15px;
      }

      .stats-card .icon.primary {
        background: linear-gradient(
          45deg,
          var(--primary-color),
          var(--secondary-color)
        );
      }

      .stats-card .icon.success {
        background: linear-gradient(45deg, var(--success-color), #20c997);
      }

      .stats-card .icon.warning {
        background: linear-gradient(45deg, var(--warning-color), #fd7e14);
      }

      .stats-card .icon.danger {
        background: linear-gradient(45deg, var(--danger-color), #e83e8c);
      }

      .filter-card {
        background: linear-gradient(
          45deg,
          var(--primary-color),
          var(--secondary-color)
        );
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 30px;
      }

      .filter-card .form-control,
      .filter-card .form-select {
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 8px;
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

      .btn-outline-primary {
        border-color: var(--primary-color);
        color: var(--primary-color);
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 500;
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
    </style>
  </head>
  <body>
    <!-- Header -->
    <div class="header">
      <div class="container">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <a href="adm_dashboard.php" class="btn btn-outline-secondary me-3">
              <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <h2 class="mb-0" style="color: var(--dark-color)">
              Reports & Analytics
            </h2>
          </div>
          <div>
            <button class="btn btn-outline-primary me-2">
              <i class="bi bi-download"></i> Export
            </button>
            <button class="btn btn-primary">
              <i class="bi bi-printer"></i> Print
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container">
      <!-- Filters -->
      <div class="filter-card">
        <h5 class="mb-3">Report Filters</h5>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label">Date Range</label>
            <select class="form-select" id="dateRange">
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month" selected>This Month</option>
              <option value="quarter">This Quarter</option>
              <option value="year">This Year</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Report Type</label>
            <select class="form-select" id="reportType">
              <option value="overview" selected>Overview</option>
              <option value="sales">Sales Report</option>
              <option value="products">Product Performance</option>
              <option value="customers">Customer Analytics</option>
            </select>
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Category</label>
            <select class="form-select" id="category">
              <option value="all" selected>All Categories</option>
              <option value="dresses">Dresses</option>
              <option value="blouses">Blouses</option>
              <option value="handbags">Handbags</option>
              <option value="accessories">Accessories</option>
            </select>
          </div>
          <div class="col-md-3 mb-3 d-flex align-items-end">
            <button class="btn btn-light w-100">
              <i class="bi bi-funnel"></i> Apply Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Key Metrics -->
      <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
          <div class="stats-card">
            <div class="icon primary">
              <i class="bi bi-graph-up"></i>
            </div>
            <div class="stats-info">
              <h3>$45,890</h3>
              <p class="mb-0">Total Revenue</p>
              <small class="text-success">+18.2% vs last month</small>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="stats-card">
            <div class="icon success">
              <i class="bi bi-cart-check"></i>
            </div>
            <div class="stats-info">
              <h3>2,340</h3>
              <p class="mb-0">Orders Processed</p>
              <small class="text-success">+12.5% vs last month</small>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="stats-card">
            <div class="icon warning">
              <i class="bi bi-people"></i>
            </div>
            <div class="stats-info">
              <h3>1,890</h3>
              <p class="mb-0">New Customers</p>
              <small class="text-warning">+3.2% vs last month</small>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="stats-card">
            <div class="icon danger">
              <i class="bi bi-arrow-return-left"></i>
            </div>
            <div class="stats-info">
              <h3>4.2%</h3>
              <p class="mb-0">Return Rate</p>
              <small class="text-success">-1.1% vs last month</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="row g-4 mb-4">
        <!-- Sales Chart -->
        <div class="col-lg-8">
          <div class="content-card">
            <div class="card-body p-4">
              <div
                class="d-flex justify-content-between align-items-center mb-4"
              >
                <h5 class="card-title mb-0">Sales Trend</h5>
                <div class="btn-group btn-group-sm">
                  <button
                    type="button"
                    class="btn btn-outline-primary active"
                    onclick="updateSalesChart('daily')"
                  >
                    Daily
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-primary"
                    onclick="updateSalesChart('weekly')"
                  >
                    Weekly
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-primary"
                    onclick="updateSalesChart('monthly')"
                  >
                    Monthly
                  </button>
                </div>
              </div>
              <div style="position: relative; height: 300px">
                <canvas id="salesChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Category Performance -->
        <div class="col-lg-4">
          <div class="content-card">
            <div class="card-body p-4">
              <h5 class="card-title mb-4">Category Performance</h5>
              <div style="position: relative; height: 300px">
                <canvas id="categoryChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Products Table -->
      <div class="row">
        <div class="col-12">
          <div class="content-card">
            <div class="card-body p-4">
              <div
                class="d-flex justify-content-between align-items-center mb-4"
              >
                <h5 class="card-title mb-0">Top Performing Products</h5>
                <button class="btn btn-outline-primary btn-sm">
                  View All Products
                </button>
              </div>
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Product Name</th>
                      <th>Category</th>
                      <th>Units Sold</th>
                      <th>Revenue</th>
                      <th>Growth</th>
                      <th>Inventory</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div
                            style="
                              width: 40px;
                              height: 40px;
                              background: linear-gradient(
                                45deg,
                                #219ebc,
                                #8ecae6
                              );
                              border-radius: 8px;
                              margin-right: 15px;
                            "
                          ></div>
                          <div>
                            <div class="fw-bold">Velvet Evening Dress</div>
                            <small class="text-muted">SKU: VV-VED-001</small>
                          </div>
                        </div>
                      </td>
                      <td>Dresses</td>
                      <td>245</td>
                      <td>$31,755</td>
                      <td><span class="text-success">+15.2%</span></td>
                      <td><span class="badge bg-success">In Stock</span></td>
                    </tr>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div
                            style="
                              width: 40px;
                              height: 40px;
                              background: linear-gradient(
                                45deg,
                                #28a745,
                                #20c997
                              );
                              border-radius: 8px;
                              margin-right: 15px;
                            "
                          ></div>
                          <div>
                            <div class="fw-bold">Silk Blouse</div>
                            <small class="text-muted">SKU: VV-SB-002</small>
                          </div>
                        </div>
                      </td>
                      <td>Blouses</td>
                      <td>189</td>
                      <td>$16,915</td>
                      <td><span class="text-success">+8.7%</span></td>
                      <td>
                        <span class="badge bg-warning text-dark"
                          >Low Stock</span
                        >
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div
                            style="
                              width: 40px;
                              height: 40px;
                              background: linear-gradient(
                                45deg,
                                #ffc107,
                                #fd7e14
                              );
                              border-radius: 8px;
                              margin-right: 15px;
                            "
                          ></div>
                          <div>
                            <div class="fw-bold">Designer Handbag</div>
                            <small class="text-muted">SKU: VV-DH-003</small>
                          </div>
                        </div>
                      </td>
                      <td>Handbags</td>
                      <td>156</td>
                      <td>$38,844</td>
                      <td><span class="text-success">+22.1%</span></td>
                      <td><span class="badge bg-success">In Stock</span></td>
                    </tr>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div
                            style="
                              width: 40px;
                              height: 40px;
                              background: linear-gradient(
                                45deg,
                                #dc3545,
                                #e83e8c
                              );
                              border-radius: 8px;
                              margin-right: 15px;
                            "
                          ></div>
                          <div>
                            <div class="fw-bold">Casual Jacket</div>
                            <small class="text-muted">SKU: VV-CJ-004</small>
                          </div>
                        </div>
                      </td>
                      <td>Jackets</td>
                      <td>134</td>
                      <td>$24,119</td>
                      <td><span class="text-danger">-2.3%</span></td>
                      <td><span class="badge bg-success">In Stock</span></td>
                    </tr>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div
                            style="
                              width: 40px;
                              height: 40px;
                              background: linear-gradient(
                                45deg,
                                #6f42c1,
                                #e83e8c
                              );
                              border-radius: 8px;
                              margin-right: 15px;
                            "
                          ></div>
                          <div>
                            <div class="fw-bold">Summer Dress</div>
                            <small class="text-muted">SKU: VV-SD-005</small>
                          </div>
                        </div>
                      </td>
                      <td>Dresses</td>
                      <td>98</td>
                      <td>$11,760</td>
                      <td><span class="text-success">+5.8%</span></td>
                      <td><span class="badge bg-danger">Out of Stock</span></td>
                    </tr>
                  </tbody>
                </table>
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
      let salesChart, categoryChart;

      // Initialize charts when DOM is loaded
      document.addEventListener("DOMContentLoaded", function () {
        initializeSalesChart();
        initializeCategoryChart();
      });

      // Sales Chart
      function initializeSalesChart() {
        const ctx = document.getElementById("salesChart").getContext("2d");
        salesChart = new Chart(ctx, {
          type: "line",
          data: {
            labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            datasets: [
              {
                label: "Sales ($)",
                data: [3200, 2800, 4100, 3600, 4200, 5100, 4800],
                borderColor: "#219ebc",
                backgroundColor: "rgba(33, 158, 188, 0.1)",
                borderWidth: 3,
                fill: true,
                tension: 0.4,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: "rgba(0, 0, 0, 0.1)",
                },
                ticks: {
                  callback: function (value) {
                    return "$" + value.toLocaleString();
                  },
                },
              },
              x: {
                grid: {
                  display: false,
                },
              },
            },
          },
        });
      }

      // Category Chart
      function initializeCategoryChart() {
        const ctx = document.getElementById("categoryChart").getContext("2d");
        categoryChart = new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: [
              "Dresses",
              "Blouses",
              "Handbags",
              "Jackets",
              "Accessories",
            ],
            datasets: [
              {
                data: [35, 25, 20, 15, 5],
                backgroundColor: [
                  "#219ebc",
                  "#28a745",
                  "#ffc107",
                  "#dc3545",
                  "#6f42c1",
                ],
                borderWidth: 0,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "bottom",
                labels: {
                  padding: 20,
                  usePointStyle: true,
                },
              },
            },
          },
        });
      }

      // Update sales chart based on period
      function updateSalesChart(period) {
        // Update button states
        document
          .querySelectorAll(".btn-group .btn")
          .forEach((btn) => btn.classList.remove("active"));
        event.target.classList.add("active");

        let newData, newLabels;

        switch (period) {
          case "daily":
            newLabels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
            newData = [3200, 2800, 4100, 3600, 4200, 5100, 4800];
            break;
          case "weekly":
            newLabels = ["Week 1", "Week 2", "Week 3", "Week 4"];
            newData = [18500, 22300, 19800, 25400];
            break;
          case "monthly":
            newLabels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun"];
            newData = [45000, 52000, 48000, 58000, 62000, 71000];
            break;
        }

        salesChart.data.labels = newLabels;
        salesChart.data.datasets[0].data = newData;
        salesChart.update();
      }

      // Filter functionality
      document
        .getElementById("reportType")
        .addEventListener("change", function () {
          // Here you would update the reports based on the selected type
          console.log("Report type changed to:", this.value);
        });

      document
        .getElementById("dateRange")
        .addEventListener("change", function () {
          // Here you would update the date range for reports
          console.log("Date range changed to:", this.value);
        });

      document
        .getElementById("category")
        .addEventListener("change", function () {
          // Here you would filter reports by category
          console.log("Category filter changed to:", this.value);
        });
    </script>
  </body>
</html>

