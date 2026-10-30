<?php
// backend_php/public/index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Shop Management System | Enterprise MongoDB Cloud Edition</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand-header">
            <div class="brand-icon">
                <i class="fas fa-mobile-screen-button"></i>
            </div>
            <div>
                <h1 class="brand-title">MobilePulse</h1>
                <div class="brand-subtitle"><i class="fas fa-database me-1"></i> MongoDB Cloud</div>
            </div>
        </div>

        <div class="nav-section-title">Central Management</div>
        <a class="nav-link-custom active" id="nav-dashboard" onclick="loadTab('dashboard')">
            <i class="fas fa-chart-line"></i>
            <span>Admin Dashboard</span>
        </a>
        <a class="nav-link-custom" id="nav-inventory" onclick="loadTab('inventory')">
            <i class="fas fa-boxes-stacked"></i>
            <span>Inventory & Devices</span>
        </a>
        <a class="nav-link-custom" id="nav-soldout" onclick="loadTab('soldout')">
            <i class="fas fa-ban text-danger"></i>
            <span>Sold Out Tracker</span>
        </a>
        <a class="nav-link-custom" id="nav-pricing" onclick="loadTab('pricing')">
            <i class="fas fa-tags text-warning"></i>
            <span>Dynamic Pricing</span>
        </a>

        <div class="nav-section-title">Sales Operations</div>
        <a class="nav-link-custom" id="nav-pos" onclick="loadTab('pos')">
            <i class="fas fa-cash-register text-success"></i>
            <span>POS Sales Terminal</span>
        </a>
        <a class="nav-link-custom" id="nav-daily-sales" onclick="loadTab('daily-sales')">
            <i class="fas fa-calendar-check text-info"></i>
            <span>Daily Operations</span>
        </a>

        <div class="nav-section-title">System & Access</div>
        <a class="nav-link-custom" id="nav-rbac" onclick="loadTab('rbac')">
            <i class="fas fa-user-shield text-purple"></i>
            <span>Sub-Admin RBAC</span>
        </a>

        <div class="user-profile-badge">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="fw-bold text-white small" id="currentUserName">Admin</div>
                    <span class="badge bg-primary" id="currentUserRole" style="font-size: 10px;">ADMIN</span>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-custom w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-arrows-rotate me-1"></i> Switch Role
                </button>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><a class="dropdown-item" href="#" onclick="quickLogin('admin@mobileshop.com', 'admin123')"><i class="fas fa-crown text-warning me-2"></i> Admin</a></li>
                    <li><a class="dropdown-item" href="#" onclick="quickLogin('subadmin@mobileshop.com', 'subadmin123')"><i class="fas fa-user-tie text-info me-2"></i> Sub-Admin (Rahim)</a></li>
                    <li><a class="dropdown-item" href="#" onclick="quickLogin('cashier@mobileshop.com', 'cashier123')"><i class="fas fa-cash-register text-success me-2"></i> Cashier (Farhana)</a></li>
                </ul>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-3 py-2">
                    <i class="fas fa-circle-check me-1"></i> MongoDB Atlas Connected
                </span>
                <span class="text-secondary small d-none d-md-inline">Replica Set: atlas-q86f5x-shard-0</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-custom-primary btn-sm" onclick="loadTab('pos')">
                    <i class="fas fa-cart-plus me-1"></i> New Sale (POS)
                </button>
                <button class="btn btn-outline-custom btn-sm" onclick="loadTab(document.querySelector('.nav-link-custom.active')?.id.replace('nav-', '') || 'dashboard')">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </header>

        <!-- Body Content Views -->
        <main class="content-body">

            <!-- 1. DASHBOARD VIEW -->
            <section id="view-dashboard" class="tab-view">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Central Admin Dashboard</h2>
                        <p class="text-secondary mb-0">Real-time inventory levels, dynamic pricing health, and sales telemetry.</p>
                    </div>
                    <div>
                        <button class="btn btn-custom-warning btn-sm" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                            <i class="fas fa-plus me-1"></i> Add Mobile Device
                        </button>
                    </div>
                </div>

                <!-- KPI Metric Cards Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="metric-card emerald">
                            <div class="metric-icon-box emerald"><i class="fas fa-boxes-packing"></i></div>
                            <div class="metric-label">Available In-Stock Units</div>
                            <div class="metric-val" id="kpi-available-stock">--</div>
                            <div class="metric-sub"><span id="kpi-total-models">--</span> Active Phone Models</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="metric-card rose">
                            <div class="metric-icon-box rose"><i class="fas fa-ban"></i></div>
                            <div class="metric-label">Sold Out Device Models</div>
                            <div class="metric-val text-danger" id="kpi-sold-out-count">--</div>
                            <div class="metric-sub text-danger"><i class="fas fa-triangle-exclamation me-1"></i> Requires immediate restock</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="metric-card blue">
                            <div class="metric-icon-box blue"><i class="fas fa-calendar-day"></i></div>
                            <div class="metric-label">Today's Sales Revenue</div>
                            <div class="metric-val text-primary" id="kpi-today-revenue">--</div>
                            <div class="metric-sub text-success">Today's Profit: <strong id="kpi-today-profit">--</strong></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="metric-card purple">
                            <div class="metric-icon-box purple"><i class="fas fa-vault"></i></div>
                            <div class="metric-label">Total Inventory Retail Value</div>
                            <div class="metric-val text-purple" id="kpi-inventory-valuation">--</div>
                            <div class="metric-sub">All-Time Revenue: <strong id="kpi-total-revenue">--</strong></div>
                        </div>
                    </div>
                </div>

                <!-- Charts & Alerts Row -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-8">
                        <div class="glass-panel h-100">
                            <div class="panel-header">
                                <h3 class="panel-title"><i class="fas fa-chart-area text-primary"></i> 7-Day Revenue & Net Profit Trend</h3>
                                <span class="badge bg-dark border">Dynamic Sales Telemetry</span>
                            </div>
                            <div style="height: 280px;">
                                <canvas id="salesTrendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="glass-panel h-100">
                            <div class="panel-header">
                                <h3 class="panel-title"><i class="fas fa-chart-pie text-info"></i> Sales by Brand</h3>
                            </div>
                            <div style="height: 200px;">
                                <canvas id="brandsPieChart"></canvas>
                            </div>
                            <div class="mt-3 pt-3 border-top border-secondary">
                                <h6 class="small text-muted mb-2">CRITICAL INVENTORY NOTIFICATIONS</h6>
                                <div id="dashboardAlerts">
                                    <div class="text-center text-secondary py-2"><i class="fas fa-spinner fa-spin"></i> Loading alerts...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions Ledger -->
                <div class="glass-panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="fas fa-receipt text-success"></i> Recent Sales Transactions</h3>
                        <button class="btn btn-sm btn-outline-custom" onclick="loadTab('daily-sales')">View Full Operations Log</button>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Items Sold</th>
                                    <th>Total Amount</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="recentSalesTableBody">
                                <tr><td colspan="6" class="text-center text-muted py-4">Loading sales data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 2. INVENTORY & DEVICES VIEW -->
            <section id="view-inventory" class="tab-view" style="display: none;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Inventory & Mobile Devices</h2>
                        <p class="text-secondary mb-0">Track real-time stock levels, cost and retail pricing, and status alerts.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-custom-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                            <i class="fas fa-plus me-1"></i> Add New Phone
                        </button>
                    </div>
                </div>

                <!-- Search and Filters Bar -->
                <div class="glass-panel p-3 mb-4">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fas fa-search"></i></span>
                                <input type="text" id="invSearch" class="form-control form-control-custom" placeholder="Search by brand, model, or variant..." oninput="loadInventory()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="invStatusFilter" class="form-select form-select-custom" onchange="loadInventory()">
                                <option value="">Filter By Stock Status: All</option>
                                <option value="in_stock">In Stock (Good)</option>
                                <option value="low_stock">Low Stock (Alert)</option>
                                <option value="sold_out">Sold Out (Depleted)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-custom w-100" onclick="loadInventory()"><i class="fas fa-filter me-1"></i> Apply</button>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="glass-panel">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Device / Variant</th>
                                    <th>Brand</th>
                                    <th>Selling Price</th>
                                    <th>Cost Price</th>
                                    <th>Margin</th>
                                    <th>Stock Status</th>
                                    <th>Sold Units</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                                <tr><td colspan="8" class="text-center text-muted py-4">Loading inventory items...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 3. SOLD OUT TRACKER VIEW -->
            <section id="view-soldout" class="tab-view" style="display: none;">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fw-bold mb-1 text-danger"><i class="fas fa-ban me-2"></i> Sold Out Items Tracker</h2>
                        <p class="text-secondary mb-0">Real-time surveillance of out-of-stock devices to prevent lost sales revenue.</p>
                    </div>
                    <span class="badge bg-danger fs-6 px-3 py-2">
                        Total Depleted: <strong id="soldOutTotalBadge">0</strong>
                    </span>
                </div>

                <div class="glass-panel">
                    <div class="panel-header">
                        <h3 class="panel-title text-danger"><i class="fas fa-exclamation-triangle"></i> Completely Depleted Inventory Items</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Depleted Model</th>
                                    <th>Brand</th>
                                    <th>Catalog Price</th>
                                    <th>Available Stock</th>
                                    <th>Historical Sold</th>
                                    <th>Last Transaction</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="soldOutTableBody">
                                <tr><td colspan="7" class="text-center text-muted py-4">Loading sold out devices...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 4. DYNAMIC PRICING VIEW -->
            <section id="view-pricing" class="tab-view" style="display: none;">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fw-bold mb-1 text-warning"><i class="fas fa-tags me-2"></i> Dynamic Pricing Module</h2>
                        <p class="text-secondary mb-0">Seamlessly update device prices, simulate profit margins, and track price history.</p>
                    </div>
                </div>

                <div class="glass-panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="fas fa-sliders text-warning"></i> Active Pricing & Margin Matrix</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Mobile Device</th>
                                    <th>Brand</th>
                                    <th>Cost Price (Wholesale)</th>
                                    <th>Retail Price</th>
                                    <th>Profit Margin (%)</th>
                                    <th>Price Adjustments</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="pricingTableBody">
                                <tr><td colspan="7" class="text-center text-muted py-4">Loading dynamic pricing matrix...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 5. POINT OF SALE (POS) VIEW -->
            <section id="view-pos" class="tab-view" style="display: none;">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="glass-panel mb-3 p-3">
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fas fa-search"></i></span>
                                <input type="text" id="posSearchInput" class="form-control form-control-custom" placeholder="Quick find phone by model, brand, or specs..." oninput="filterPOSProducts()">
                            </div>
                        </div>

                        <!-- Product Grid -->
                        <div class="row g-3" id="posProductGrid" style="max-height: calc(100vh - 220px); overflow-y: auto;">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- Cart and Checkout Box -->
                    <div class="col-lg-4">
                        <div class="pos-cart-box">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary">
                                <h5 class="fw-bold mb-0 text-white"><i class="fas fa-shopping-cart text-primary me-2"></i> Sale Invoice</h5>
                                <span class="badge bg-primary" id="posCartCount">0 items</span>
                            </div>

                            <div class="pos-cart-items" id="posCartItemsContainer">
                                <div class="text-center text-secondary py-5">Cart is empty</div>
                            </div>

                            <div class="border-top border-secondary pt-3 mt-2">
                                <div class="mb-2">
                                    <label class="form-label small text-secondary mb-1">Customer Name</label>
                                    <input type="text" id="posCustomerName" class="form-control form-control-custom py-1" placeholder="Walk-in Customer">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-secondary mb-1">Customer Phone</label>
                                    <input type="text" id="posCustomerPhone" class="form-control form-control-custom py-1" placeholder="017XXXXXXXX">
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small text-secondary mb-1">Payment Method</label>
                                        <select id="posPaymentMethod" class="form-select form-select-custom py-1">
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Credit/Debit Card</option>
                                            <option value="bKash">bKash / Mobile Pay</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-secondary mb-1">Special Discount (৳)</label>
                                        <input type="number" id="posDiscountInput" class="form-control form-control-custom py-1" value="0" oninput="renderPOSCart()">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center my-3">
                                    <span class="fs-5 text-secondary">Total Payable:</span>
                                    <span class="fs-4 fw-bold text-success" id="posCartTotal">৳ 0</span>
                                </div>

                                <button class="btn btn-custom-success w-100 py-2 fs-6" onclick="processPOSCheckout()">
                                    <i class="fas fa-check-circle me-1"></i> Complete Sale & Issue Invoice
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 6. DAILY SALES OPERATIONS VIEW -->
            <section id="view-daily-sales" class="tab-view" style="display: none;">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fw-bold mb-1 text-info"><i class="fas fa-calendar-check me-2"></i> Daily Sales Operations</h2>
                        <p class="text-secondary mb-0">Shift monitoring and operational tracking for Sub-Admins and Cashiers.</p>
                    </div>
                    <button class="btn btn-outline-custom btn-sm" onclick="loadDailySales()">
                        <i class="fas fa-rotate me-1"></i> Refresh Today's Ledger
                    </button>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="metric-card blue">
                            <div class="metric-label">Today's Invoices</div>
                            <div class="metric-val" id="dailyTotalInvoices">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card emerald">
                            <div class="metric-label">Total Units Sold Today</div>
                            <div class="metric-val text-success" id="dailyTotalUnits">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card purple">
                            <div class="metric-label">Today's Total Revenue</div>
                            <div class="metric-val text-info" id="dailyTotalRevenue">৳ 0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card amber">
                            <div class="metric-label">Today's Net Profit</div>
                            <div class="metric-val text-warning" id="dailyTotalProfit">৳ 0</div>
                        </div>
                    </div>
                </div>

                <div class="glass-panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="fas fa-list-check"></i> Today's Detailed Transactions Ledger</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Items Sold</th>
                                    <th>Grand Total</th>
                                    <th>Payment</th>
                                    <th>Sold By</th>
                                </tr>
                            </thead>
                            <tbody id="dailySalesTableBody">
                                <tr><td colspan="7" class="text-center text-muted py-4">Loading daily ledger...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 7. SUB-ADMIN RBAC VIEW -->
            <section id="view-rbac" class="tab-view" style="display: none;">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fw-bold mb-1 text-purple"><i class="fas fa-user-shield me-2"></i> Role-Based Access Control (RBAC)</h2>
                        <p class="text-secondary mb-0">Administer Sub-Admin roles and grant specific permissions for daily operations.</p>
                    </div>
                    <button class="btn btn-custom-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSubAdminModal">
                        <i class="fas fa-user-plus me-1"></i> Add New Sub-Admin
                    </button>
                </div>

                <div class="glass-panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="fas fa-users-gear"></i> System Users & Permission Assignment</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>User & Email</th>
                                    <th>Assigned Role</th>
                                    <th>Granted Permissions</th>
                                    <th>Account Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="rbacUsersTableBody">
                                <tr><td colspan="5" class="text-center text-muted py-4">Loading user records...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container-custom" id="toastContainer"></div>

    <!-- ================= MODALS ================= -->

    <!-- Dynamic Pricing Modal -->
    <div class="modal fade" id="pricingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-tags text-warning me-2"></i> Seamless Dynamic Pricing</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="priceModalId">
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Target Device</label>
                        <h5 class="text-white" id="priceModalDeviceName">--</h5>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-secondary small">Selling Retail Price (৳) *</label>
                            <input type="number" id="priceModalSellingPrice" class="form-control form-control-custom" oninput="calculateLiveMargin()">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">Cost Price (৳) *</label>
                            <input type="number" id="priceModalCostPrice" class="form-control form-control-custom" oninput="calculateLiveMargin()">
                        </div>
                    </div>

                    <div class="p-3 mb-3 rounded" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-secondary small">Dynamic Profit Margin:</span>
                            <span class="fw-bold text-success fs-5" id="priceModalLiveMargin">0.00%</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary small">Net Profit Per Device:</span>
                            <span class="fw-bold text-primary" id="priceModalLiveProfit">৳ 0</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small">Optional Flash Sale / Discount Price (৳)</label>
                        <input type="number" id="priceModalDiscountPrice" class="form-control form-control-custom" placeholder="Leave empty if regular price applies">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small">Reason for Price Adjustment *</label>
                        <input type="text" id="priceModalReason" class="form-control form-control-custom" placeholder="e.g. Supplier wholesale reduction, Festival discount">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom-warning" onclick="submitPriceUpdate()">
                        <i class="fas fa-check me-1"></i> Apply & Log Price
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Price History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historyModalTitle"><i class="fas fa-history text-info me-2"></i> Price History & Audit Log</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="timeline" id="historyTimelineContainer">
                        <!-- Loaded dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restock Modal -->
    <div class="modal fade" id="restockModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-boxes-packing text-warning me-2"></i> Restock Mobile Device</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="restockModalId">
                    <h6 class="text-white mb-3" id="restockModalDeviceName">--</h6>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Units to Add to Stock</label>
                        <input type="number" id="restockModalQty" class="form-control form-control-custom" value="10" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom-warning" onclick="submitRestock()">Confirm Restock</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Device Modal -->
    <div class="modal fade" id="addDeviceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-mobile text-primary me-2"></i> Add Mobile Device to Catalog</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Brand *</label>
                            <input type="text" id="addDevBrand" class="form-control form-control-custom" placeholder="e.g. Apple, Samsung, Google">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Model Name *</label>
                            <input type="text" id="addDevModel" class="form-control form-control-custom" placeholder="e.g. iPhone 16 Pro, Galaxy S25">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Variant / Color / Storage</label>
                            <input type="text" id="addDevVariant" class="form-control form-control-custom" placeholder="e.g. 256GB / 8GB RAM Space Black">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Initial Stock Quantity *</label>
                            <input type="number" id="addDevStock" class="form-control form-control-custom" value="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Selling Price (৳) *</label>
                            <input type="number" id="addDevPrice" class="form-control form-control-custom" placeholder="e.g. 120000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Cost Price (৳)</label>
                            <input type="number" id="addDevCost" class="form-control form-control-custom" placeholder="e.g. 100000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Low Stock Alert Threshold</label>
                            <input type="number" id="addDevMinAlert" class="form-control form-control-custom" value="3">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom-primary" onclick="createDevice()">Save Device</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sub-Admin Permissions Modal -->
    <div class="modal fade" id="permissionsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-lock text-purple me-2"></i> Sub-Admin Permissions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="permModalUserId">
                    <h6 class="text-white mb-3" id="permModalUserName">--</h6>
                    <p class="text-secondary small">Select specific operational permissions granted to this sub-admin:</p>

                    <div class="form-check mb-2">
                        <input class="form-check-input perm-checkbox" type="checkbox" value="manage_inventory" id="perm_inventory">
                        <label class="form-check-label text-white small" for="perm_inventory">
                            <strong>Manage Inventory</strong> (Add, edit devices, restock)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input perm-checkbox" type="checkbox" value="manage_pricing" id="perm_pricing">
                        <label class="form-check-label text-white small" for="perm_pricing">
                            <strong>Dynamic Pricing</strong> (Adjust selling prices, discounts, margin calculation)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input perm-checkbox" type="checkbox" value="record_sales" id="perm_pos">
                        <label class="form-check-label text-white small" for="perm_pos">
                            <strong>Record Sales (POS)</strong> (Point of sale checkout, issue invoices)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input perm-checkbox" type="checkbox" value="view_sales_daily" id="perm_daily">
                        <label class="form-check-label text-white small" for="perm_daily">
                            <strong>Track Daily Sales Operations</strong> (View daily transactions & shift sales)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input perm-checkbox" type="checkbox" value="view_analytics" id="perm_analytics">
                        <label class="form-check-label text-white small" for="perm_analytics">
                            <strong>View Centralized Analytics</strong> (Full profit, valuation, sold-out tracking)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom-primary" onclick="savePermissions()">Save Permissions</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Sub-Admin Modal -->
    <div class="modal fade" id="createSubAdminModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus text-primary me-2"></i> Create New Sub-Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Full Name *</label>
                        <input type="text" id="newUserName" class="form-control form-control-custom" placeholder="e.g. Moniruzzaman">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Email Address *</label>
                        <input type="email" id="newUserEmail" class="form-control form-control-custom" placeholder="e.g. monir@mobileshop.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Password *</label>
                        <input type="password" id="newUserPassword" class="form-control form-control-custom" placeholder="Enter secure password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Role</label>
                        <select id="newUserRole" class="form-select form-select-custom">
                            <option value="sub_admin">Sub-Admin (Operations Manager)</option>
                            <option value="cashier">Store Cashier (Sales Only)</option>
                            <option value="inventory_manager">Inventory Specialist</option>
                        </select>
                    </div>

                    <label class="form-label text-secondary small">Initial Permissions</label>
                    <div class="form-check mb-1">
                        <input class="form-check-input new-user-perm-checkbox" type="checkbox" value="record_sales" id="new_perm_pos" checked>
                        <label class="form-check-label text-white small" for="new_perm_pos">Record Sales (POS)</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input new-user-perm-checkbox" type="checkbox" value="view_sales_daily" id="new_perm_daily" checked>
                        <label class="form-check-label text-white small" for="new_perm_daily">Track Daily Sales Operations</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input new-user-perm-checkbox" type="checkbox" value="manage_pricing" id="new_perm_pricing">
                        <label class="form-check-label text-white small" for="new_perm_pricing">Dynamic Pricing</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input new-user-perm-checkbox" type="checkbox" value="manage_inventory" id="new_perm_inv">
                        <label class="form-check-label text-white small" for="new_perm_inv">Manage Inventory</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom-primary" onclick="createSubAdmin()">Create Account</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Main Application JS -->
    <script src="js/app.js"></script>
</body>
</html>

