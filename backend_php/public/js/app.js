// backend_php/public/js/app.js
let currentUser = null;
let currentToken = localStorage.getItem('mobile_shop_token') || '';
let chartTrend = null;
let chartBrands = null;
let cart = [];
let allMobiles = [];

// Initialize App
document.addEventListener('DOMContentLoaded', () => {
    initAuth();
});

// Auth & Switcher
async function initAuth() {
    if (!currentToken) {
        // Auto login as default Admin for initial seamless experience
        await quickLogin('admin@mobileshop.com', 'admin123');
    } else {
        try {
            const res = await fetch('api/auth.php?action=me', {
                headers: { 'Authorization': `Bearer ${currentToken}` }
            });
            const data = await res.json();
            if (data.success) {
                currentUser = data.user;
                updateUserUI();
                loadTab('dashboard');
            } else {
                await quickLogin('admin@mobileshop.com', 'admin123');
            }
        } catch (e) {
            await quickLogin('admin@mobileshop.com', 'admin123');
        }
    }
}

async function quickLogin(email, password) {
    try {
        const res = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        if (data.success) {
            currentToken = data.token;
            currentUser = data.user;
            localStorage.setItem('mobile_shop_token', currentToken);
            updateUserUI();
            showToast(`Logged in as ${currentUser.name} (${currentUser.role})`, 'success');
            loadTab('dashboard');
        } else {
            showToast(data.error || 'Login failed', 'danger');
        }
    } catch (e) {
        showToast('Login connection failed: ' + e.message, 'danger');
    }
}

function updateUserUI() {
    if (!currentUser) return;
    document.getElementById('currentUserName').innerText = currentUser.name;
    document.getElementById('currentUserRole').innerText = currentUser.role.toUpperCase();
    
    // Check permission badges
    const rbacTab = document.getElementById('nav-rbac');
    if (rbacTab) {
        rbacTab.style.display = (currentUser.role === 'admin' || hasPermission('manage_users')) ? 'flex' : 'none';
    }

    const pricingTab = document.getElementById('nav-pricing');
    if (pricingTab) {
        pricingTab.style.display = (currentUser.role === 'admin' || hasPermission('manage_pricing')) ? 'flex' : 'none';
    }

    const posTab = document.getElementById('nav-pos');
    if (posTab) {
        posTab.style.display = (currentUser.role === 'admin' || hasPermission('record_sales')) ? 'flex' : 'none';
    }

    const analyticsTab = document.getElementById('nav-analytics');
    if (analyticsTab) {
        analyticsTab.style.display = (currentUser.role === 'admin' || hasPermission('view_analytics')) ? 'flex' : 'none';
    }
}

function hasPermission(perm) {
    if (!currentUser) return false;
    if (currentUser.role === 'admin') return true;
    return Array.isArray(currentUser.permissions) && currentUser.permissions.includes(perm);
}

// Navigation Tabs
function loadTab(tabName) {
    document.querySelectorAll('.nav-link-custom').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-view').forEach(el => el.style.display = 'none');

    const navLink = document.getElementById(`nav-${tabName}`);
    if (navLink) navLink.classList.add('active');

    const view = document.getElementById(`view-${tabName}`);
    if (view) view.style.display = 'block';

    if (tabName === 'dashboard') loadDashboard();
    if (tabName === 'inventory') loadInventory();
    if (tabName === 'pricing') loadPricingModule();
    if (tabName === 'pos') loadPOS();
    if (tabName === 'daily-sales') loadDailySales();
    if (tabName === 'soldout') loadSoldOutTracker();
    if (tabName === 'rbac') loadRBAC();
}

// Dashboard Loader
async function loadDashboard() {
    try {
        const res = await fetch('api/analytics.php', {
            headers: { 'Authorization': `Bearer ${currentToken}` }
        });
        const json = await res.json();
        if (!json.success) {
            showToast(json.error || 'Failed to load dashboard', 'warning');
            return;
        }

        const data = json.data;
        const kpis = data.kpis;
        const stockStatus = data.stockStatus;

        // Populate KPIs
        document.getElementById('kpi-available-stock').innerText = kpis.totalAvailableStock.toLocaleString();
        document.getElementById('kpi-total-models').innerText = kpis.totalModels;
        document.getElementById('kpi-sold-out-count').innerText = stockStatus.soldOut;
        document.getElementById('kpi-today-revenue').innerText = '৳ ' + kpis.todayRevenue.toLocaleString();
        document.getElementById('kpi-today-profit').innerText = '৳ ' + kpis.todayProfit.toLocaleString();
        document.getElementById('kpi-total-revenue').innerText = '৳ ' + kpis.totalRevenue.toLocaleString();
        document.getElementById('kpi-total-profit').innerText = '৳ ' + kpis.totalProfit.toLocaleString();
        document.getElementById('kpi-inventory-valuation').innerText = '৳ ' + kpis.inventoryRetailValue.toLocaleString();

        // Render Charts
        renderTrendChart(data.dailyTrends);
        renderBrandsChart(data.topBrands);

        // Render Low Stock & Sold Out previews
        renderAlertsPreview(stockStatus);

        // Render Recent Sales
        renderRecentSales(data.recentSales);

    } catch (e) {
        console.error(e);
    }
}

function renderTrendChart(trends) {
    const ctx = document.getElementById('salesTrendChart').getContext('2d');
    const labels = trends.map(t => t.date.substring(5));
    const revenueData = trends.map(t => t.revenue);
    const profitData = trends.map(t => t.profit);

    if (chartTrend) chartTrend.destroy();

    chartTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenue (৳)',
                    data: revenueData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointRadius: 4
                },
                {
                    label: 'Net Profit (৳)',
                    data: profitData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#9ca3af', font: { size: 12 } } }
            },
            scales: {
                x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#9ca3af' } },
                y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#9ca3af' } }
            }
        }
    });
}

function renderBrandsChart(topBrands) {
    const ctx = document.getElementById('brandsPieChart').getContext('2d');
    const labels = topBrands.length > 0 ? topBrands.map(b => b.brand) : ['No Data'];
    const dataVals = topBrands.length > 0 ? topBrands.map(b => b.revenue) : [1];

    if (chartBrands) chartBrands.destroy();

    chartBrands = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: dataVals,
                backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ec4899', '#6366f1'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#9ca3af', boxWidth: 12, font: { size: 11 } } }
            },
            cutout: '70%'
        }
    });
}

function renderAlertsPreview(stockStatus) {
    const container = document.getElementById('dashboardAlerts');
    if (!container) return;

    let html = '';
    if (stockStatus.soldOutItems.length === 0 && stockStatus.lowStockItems.length === 0) {
        html = `<div class="p-3 text-center text-secondary">All devices have optimal stock levels.</div>`;
    } else {
        // Sold out alerts
        stockStatus.soldOutItems.forEach(item => {
            html += `
                <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);">
                    <div>
                        <div class="fw-bold text-danger"><i class="fas fa-exclamation-circle me-1"></i> SOLD OUT: ${escapeHtml(item.brand)} ${escapeHtml(item.model)}</div>
                        <small class="text-secondary">${escapeHtml(item.variant || '')} • Sold: ${item.sold} units</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="openRestockModal('${item.id}', '${escapeHtml(item.brand)} ${escapeHtml(item.model)}')">
                        <i class="fas fa-plus me-1"></i> Restock
                    </button>
                </div>
            `;
        });

        // Low stock alerts
        stockStatus.lowStockItems.forEach(item => {
            html += `
                <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2);">
                    <div>
                        <div class="fw-bold text-warning"><i class="fas fa-exclamation-triangle me-1"></i> LOW STOCK: ${escapeHtml(item.brand)} ${escapeHtml(item.model)}</div>
                        <small class="text-secondary">${escapeHtml(item.variant || '')} • Only ${item.stock} left!</small>
                    </div>
                    <button class="btn btn-sm btn-outline-warning" onclick="openRestockModal('${item.id}', '${escapeHtml(item.brand)} ${escapeHtml(item.model)}')">
                        <i class="fas fa-plus me-1"></i> Restock
                    </button>
                </div>
            `;
        });
    }

    container.innerHTML = html;
}

function renderRecentSales(sales) {
    const tbody = document.getElementById('recentSalesTableBody');
    if (!tbody) return;

    if (!sales || sales.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No recent sales records found.</td></tr>`;
        return;
    }

    tbody.innerHTML = sales.map(s => `
        <tr>
            <td class="fw-bold text-primary">${escapeHtml(s.invoiceNumber)}</td>
            <td>${escapeHtml(s.customerName || 'Walk-in')}</td>
            <td>${(s.items || []).map(i => `${escapeHtml(i.brand)} ${escapeHtml(i.model)} (x${i.quantity})`).join(', ')}</td>
            <td class="fw-bold">৳ ${Number(s.grandTotal).toLocaleString()}</td>
            <td><span class="badge bg-secondary">${escapeHtml(s.paymentMethod)}</span></td>
            <td><small class="text-muted">${escapeHtml(s.date)}</small></td>
        </tr>
    `).join('');
}

// Inventory & Catalog Loader
async function loadInventory() {
    const search = document.getElementById('invSearch') ? document.getElementById('invSearch').value : '';
    const status = document.getElementById('invStatusFilter') ? document.getElementById('invStatusFilter').value : '';

    try {
        const url = `api/mobiles.php?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
        const res = await fetch(url, { headers: { 'Authorization': `Bearer ${currentToken}` } });
        const json = await res.json();
        if (!json.success) return;

        allMobiles = json.mobiles;
        const tbody = document.getElementById('inventoryTableBody');
        if (!tbody) return;

        if (allMobiles.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">No mobile devices found matching query.</td></tr>`;
            return;
        }

        tbody.innerHTML = allMobiles.map(m => {
            let statusBadge = '';
            if (m.status === 'sold_out') {
                statusBadge = `<span class="badge-soldout"><i class="fas fa-times-circle me-1"></i> Sold Out</span>`;
            } else if (m.status === 'low_stock') {
                statusBadge = `<span class="badge-lowstock"><i class="fas fa-exclamation-triangle me-1"></i> Low Stock (${m.stock})</span>`;
            } else {
                statusBadge = `<span class="badge-instock"><i class="fas fa-check-circle me-1"></i> In Stock (${m.stock})</span>`;
            }

            return `
                <tr>
                    <td>
                        <div class="fw-bold text-white">${escapeHtml(m.brand)} ${escapeHtml(m.model)}</div>
                        <small class="text-secondary">${escapeHtml(m.variant || '')}</small>
                    </td>
                    <td><span class="badge bg-dark border">${escapeHtml(m.brand)}</span></td>
                    <td class="fw-bold text-info">৳ ${Number(m.price).toLocaleString()}</td>
                    <td class="text-secondary">৳ ${Number(m.costPrice || 0).toLocaleString()}</td>
                    <td>
                        <span class="text-success fw-bold">${m.profitMargin}%</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td class="fw-bold text-light">${m.sold || 0}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-custom" title="Dynamic Pricing" onclick="openPriceModal('${m._id}')">
                                <i class="fas fa-tag text-primary"></i>
                            </button>
                            <button class="btn btn-outline-custom" title="Restock" onclick="openRestockModal('${m._id}', '${escapeHtml(m.brand)} ${escapeHtml(m.model)}')">
                                <i class="fas fa-boxes text-warning"></i>
                            </button>
                            <button class="btn btn-outline-custom" title="Price History" onclick="openHistoryModal('${m._id}')">
                                <i class="fas fa-history text-info"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

    } catch (e) {
        console.error(e);
    }
}

// Dynamic Pricing Module Loader
async function loadPricingModule() {
    try {
        const res = await fetch('api/mobiles.php', { headers: { 'Authorization': `Bearer ${currentToken}` } });
        const json = await res.json();
        if (!json.success) return;

        const mobiles = json.mobiles;
        const tbody = document.getElementById('pricingTableBody');
        if (!tbody) return;

        tbody.innerHTML = mobiles.map(m => {
            const margin = m.profitMargin || 0;
            const marginColor = margin > 15 ? 'text-success' : (margin > 8 ? 'text-warning' : 'text-danger');
            const historyCount = (m.priceHistory || []).length;

            return `
                <tr>
                    <td>
                        <div class="fw-bold">${escapeHtml(m.brand)} ${escapeHtml(m.model)}</div>
                        <small class="text-secondary">${escapeHtml(m.variant || '')}</small>
                    </td>
                    <td><span class="badge bg-dark">${escapeHtml(m.brand)}</span></td>
                    <td class="fw-bold">৳ ${Number(m.costPrice || 0).toLocaleString()}</td>
                    <td class="fw-bold text-primary">৳ ${Number(m.price).toLocaleString()}</td>
                    <td class="fw-bold ${marginColor}">${margin}%</td>
                    <td>
                        <span class="badge bg-secondary cursor-pointer" onclick="openHistoryModal('${m._id}')">
                            <i class="fas fa-history me-1"></i> ${historyCount} adjustments
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-custom-primary" onclick="openPriceModal('${m._id}')">
                            <i class="fas fa-edit me-1"></i> Update Price
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        console.error(e);
    }
}

// Dynamic Pricing Modal & Margin Calculation
function openPriceModal(mobileId) {
    const mobile = allMobiles.find(m => m._id === mobileId);
    if (!mobile) return;

    document.getElementById('priceModalId').value = mobile._id;
    document.getElementById('priceModalDeviceName').innerText = `${mobile.brand} ${mobile.model} (${mobile.variant || ''})`;
    document.getElementById('priceModalSellingPrice').value = mobile.price;
    document.getElementById('priceModalCostPrice').value = mobile.costPrice || (mobile.price * 0.8);
    document.getElementById('priceModalDiscountPrice').value = mobile.discountPrice || '';
    document.getElementById('priceModalReason').value = 'Dynamic market competition adjustment';

    calculateLiveMargin();
    const modal = new bootstrap.Modal(document.getElementById('pricingModal'));
    modal.show();
}

function calculateLiveMargin() {
    const sell = parseFloat(document.getElementById('priceModalSellingPrice').value) || 0;
    const cost = parseFloat(document.getElementById('priceModalCostPrice').value) || 0;

    let margin = 0;
    let profitPerUnit = 0;
    if (sell > 0) {
        profitPerUnit = sell - cost;
        margin = ((sell - cost) / sell) * 100;
    }

    const marginEl = document.getElementById('priceModalLiveMargin');
    const profitEl = document.getElementById('priceModalLiveProfit');
    if (marginEl) marginEl.innerText = margin.toFixed(2) + '%';
    if (profitEl) profitEl.innerText = '৳ ' + profitPerUnit.toLocaleString();
}

async function submitPriceUpdate() {
    const id = document.getElementById('priceModalId').value;
    const price = parseFloat(document.getElementById('priceModalSellingPrice').value);
    const costPrice = parseFloat(document.getElementById('priceModalCostPrice').value);
    const discountPrice = parseFloat(document.getElementById('priceModalDiscountPrice').value) || null;
    const reason = document.getElementById('priceModalReason').value;

    if (!price || price <= 0) {
        showToast('Please enter a valid selling price.', 'warning');
        return;
    }

    try {
        const res = await fetch(`api/pricing.php?action=update&id=${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentToken}`
            },
            body: JSON.stringify({ price, costPrice, discountPrice, reason })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Price updated seamlessly with dynamic margin calculation!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('pricingModal')).hide();
            loadInventory();
            loadPricingModule();
            loadDashboard();
        } else {
            showToast(data.error || 'Failed to update price', 'danger');
        }
    } catch (e) {
        showToast('Request failed: ' + e.message, 'danger');
    }
}

// Price History Modal
async function openHistoryModal(mobileId) {
    try {
        const res = await fetch(`api/pricing.php?action=history&id=${mobileId}`, {
            headers: { 'Authorization': `Bearer ${currentToken}` }
        });
        const data = await res.json();
        if (!data.success) {
            showToast(data.error || 'Could not fetch history', 'warning');
            return;
        }

        document.getElementById('historyModalTitle').innerText = `Price Audit Trail: ${data.brand} ${data.model}`;
        const container = document.getElementById('historyTimelineContainer');
        const history = data.priceHistory || [];

        if (history.length === 0) {
            container.innerHTML = `<div class="text-secondary py-3 text-center">No price history logged for this device yet.</div>`;
        } else {
            container.innerHTML = history.slice().reverse().map(h => `
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-info">৳ ${Number(h.newPrice).toLocaleString()}</strong>
                        <small class="text-muted">${escapeHtml(h.date || '')}</small>
                    </div>
                    <div class="text-secondary small">
                        Old: ৳ ${Number(h.oldPrice || 0).toLocaleString()} • Cost: ৳ ${Number(h.newCost || h.costPrice || 0).toLocaleString()} • Margin: <span class="text-success">${h.margin || 0}%</span>
                    </div>
                    <div class="text-light small mt-1">
                        <em>"${escapeHtml(h.reason || 'Price adjustment')}"</em> — by <strong>${escapeHtml(h.changedBy || 'Admin')}</strong>
                    </div>
                </div>
            `).join('');
        }

        const modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();
    } catch (e) {
        console.error(e);
    }
}

// Restock Modal
function openRestockModal(mobileId, deviceName) {
    document.getElementById('restockModalId').value = mobileId;
    document.getElementById('restockModalDeviceName').innerText = deviceName;
    document.getElementById('restockModalQty').value = 5;

    const modal = new bootstrap.Modal(document.getElementById('restockModal'));
    modal.show();
}

async function submitRestock() {
    const id = document.getElementById('restockModalId').value;
    const quantity = parseInt(document.getElementById('restockModalQty').value) || 0;
    if (quantity <= 0) {
        showToast('Please enter a positive restock quantity', 'warning');
        return;
    }

    try {
        const res = await fetch(`api/mobiles.php?action=restock&id=${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentToken}`
            },
            body: JSON.stringify({ quantity })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('restockModal')).hide();
            loadInventory();
            loadSoldOutTracker();
            loadDashboard();
        } else {
            showToast(data.error || 'Restock failed', 'danger');
        }
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

// Sold Out Items Dedicated Tracker
async function loadSoldOutTracker() {
    try {
        const res = await fetch('api/mobiles.php?status=sold_out', {
            headers: { 'Authorization': `Bearer ${currentToken}` }
        });
        const json = await res.json();
        if (!json.success) return;

        const soldOutMobiles = json.mobiles;
        const countBadge = document.getElementById('soldOutTotalBadge');
        if (countBadge) countBadge.innerText = soldOutMobiles.length;

        const tbody = document.getElementById('soldOutTableBody');
        if (!tbody) return;

        if (soldOutMobiles.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-success py-5">
                <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                Great job! No sold-out items currently in catalog. All devices have stock available.
            </td></tr>`;
            return;
        }

        tbody.innerHTML = soldOutMobiles.map(m => `
            <tr>
                <td>
                    <div class="fw-bold text-danger">${escapeHtml(m.brand)} ${escapeHtml(m.model)}</div>
                    <small class="text-secondary">${escapeHtml(m.variant || '')}</small>
                </td>
                <td><span class="badge bg-dark">${escapeHtml(m.brand)}</span></td>
                <td class="fw-bold text-light">৳ ${Number(m.price).toLocaleString()}</td>
                <td class="fw-bold text-danger">0 Units</td>
                <td class="fw-bold text-info">${m.sold || 0} units</td>
                <td><small class="text-muted">${escapeHtml(m.updatedAt || 'Recently')}</small></td>
                <td>
                    <button class="btn btn-sm btn-custom-warning" onclick="openRestockModal('${m._id}', '${escapeHtml(m.brand)} ${escapeHtml(m.model)}')">
                        <i class="fas fa-boxes me-1"></i> Restock Now
                    </button>
                </td>
            </tr>
        `).join('');

    } catch (e) {
        console.error(e);
    }
}

// Point of Sale (POS) Terminal
async function loadPOS() {
    try {
        const res = await fetch('api/mobiles.php', {
            headers: { 'Authorization': `Bearer ${currentToken}` }
        });
        const json = await res.json();
        if (!json.success) return;

        allMobiles = json.mobiles;
        renderPOSProductList(allMobiles);
        renderPOSCart();
    } catch (e) {
        console.error(e);
    }
}

function renderPOSProductList(mobiles) {
    const container = document.getElementById('posProductGrid');
    if (!container) return;

    if (mobiles.length === 0) {
        container.innerHTML = `<div class="col-12 text-center text-secondary py-4">No matching mobile devices found.</div>`;
        return;
    }

    container.innerHTML = mobiles.map(m => {
        const isSoldOut = (m.stock <= 0);
        return `
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100 p-3" style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px; ${isSoldOut ? 'opacity: 0.6;' : ''}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-dark">${escapeHtml(m.brand)}</span>
                        ${isSoldOut ? '<span class="badge bg-danger">Sold Out</span>' : `<span class="badge bg-success">${m.stock} In Stock</span>`}
                    </div>
                    <h6 class="text-white mb-1">${escapeHtml(m.brand)} ${escapeHtml(m.model)}</h6>
                    <small class="text-secondary mb-2">${escapeHtml(m.variant || '')}</small>
                    <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top border-secondary">
                        <div class="fw-bold text-primary">৳ ${Number(m.price).toLocaleString()}</div>
                        <button class="btn btn-sm ${isSoldOut ? 'btn-secondary disabled' : 'btn-custom-primary'}" 
                                onclick="addToCart('${m._id}')" ${isSoldOut ? 'disabled' : ''}>
                            <i class="fas fa-cart-plus me-1"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function filterPOSProducts() {
    const query = (document.getElementById('posSearchInput').value || '').toLowerCase();
    const filtered = allMobiles.filter(m => 
        m.brand.toLowerCase().includes(query) ||
        m.model.toLowerCase().includes(query) ||
        (m.variant && m.variant.toLowerCase().includes(query))
    );
    renderPOSProductList(filtered);
}

function addToCart(mobileId) {
    const mobile = allMobiles.find(m => m._id === mobileId);
    if (!mobile) return;

    if (mobile.stock <= 0) {
        showToast('This device is completely Sold Out!', 'warning');
        return;
    }

    const existing = cart.find(c => c.mobileId === mobileId);
    if (existing) {
        if (existing.quantity >= mobile.stock) {
            showToast(`Cannot add more. Only ${mobile.stock} units available in stock.`, 'warning');
            return;
        }
        existing.quantity++;
    } else {
        cart.push({
            mobileId: mobile._id,
            brand: mobile.brand,
            model: mobile.model,
            variant: mobile.variant || '',
            unitPrice: mobile.discountPrice || mobile.price,
            costPrice: mobile.costPrice || (mobile.price * 0.8),
            quantity: 1,
            maxStock: mobile.stock
        });
    }

    renderPOSCart();
}

function changeCartQty(mobileId, delta) {
    const item = cart.find(c => c.mobileId === mobileId);
    if (!item) return;

    item.quantity += delta;
    if (item.quantity <= 0) {
        cart = cart.filter(c => c.mobileId !== mobileId);
    } else if (item.quantity > item.maxStock) {
        item.quantity = item.maxStock;
        showToast(`Stock limit reached (${item.maxStock} max)`, 'warning');
    }

    renderPOSCart();
}

function removeCartItem(mobileId) {
    cart = cart.filter(c => c.mobileId !== mobileId);
    renderPOSCart();
}

function renderPOSCart() {
    const container = document.getElementById('posCartItemsContainer');
    const totalEl = document.getElementById('posCartTotal');
    const countEl = document.getElementById('posCartCount');
    if (!container) return;

    if (cart.length === 0) {
        container.innerHTML = `<div class="text-center text-secondary py-5">Cart is empty. Select devices from the left catalog to start selling.</div>`;
        if (totalEl) totalEl.innerText = '৳ 0';
        if (countEl) countEl.innerText = '0 items';
        return;
    }

    let subtotal = 0;
    let itemCount = 0;

    container.innerHTML = cart.map(item => {
        const itemTotal = item.unitPrice * item.quantity;
        subtotal += itemTotal;
        itemCount += item.quantity;

        return `
            <div class="pos-cart-row">
                <div style="flex: 1;">
                    <div class="fw-bold text-white small">${escapeHtml(item.brand)} ${escapeHtml(item.model)}</div>
                    <small class="text-secondary">৳ ${Number(item.unitPrice).toLocaleString()} each</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="changeCartQty('${item.mobileId}', -1)">-</button>
                    <span class="fw-bold">${item.quantity}</span>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="changeCartQty('${item.mobileId}', 1)">+</button>
                    <div class="fw-bold text-primary ms-2" style="min-width: 70px; text-align: right;">
                        ৳ ${Number(itemTotal).toLocaleString()}
                    </div>
                    <button class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="removeCartItem('${item.mobileId}')">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');

    const discount = parseFloat(document.getElementById('posDiscountInput')?.value) || 0;
    const grandTotal = Math.max(0, subtotal - discount);

    if (totalEl) totalEl.innerText = '৳ ' + grandTotal.toLocaleString();
    if (countEl) countEl.innerText = `${itemCount} items`;
}

async function processPOSCheckout() {
    if (cart.length === 0) {
        showToast('Cart is empty! Add products before checkout.', 'warning');
        return;
    }

    const customerName = document.getElementById('posCustomerName').value || 'Walk-in Customer';
    const customerPhone = document.getElementById('posCustomerPhone').value || '';
    const paymentMethod = document.getElementById('posPaymentMethod').value || 'Cash';
    const discount = parseFloat(document.getElementById('posDiscountInput').value) || 0;

    const payload = {
        customerName,
        customerPhone,
        paymentMethod,
        discount,
        items: cart.map(c => ({
            mobileId: c.mobileId,
            quantity: c.quantity,
            unitPrice: c.unitPrice
        }))
    };

    try {
        const res = await fetch('api/sales.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentToken}`
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Invoice ${data.sale.invoiceNumber} processed successfully!`, 'success');
            cart = [];
            renderPOSCart();
            document.getElementById('posCustomerName').value = '';
            document.getElementById('posCustomerPhone').value = '';
            document.getElementById('posDiscountInput').value = 0;
            loadPOS();
            loadDashboard();
        } else {
            showToast(data.error || 'Checkout failed', 'danger');
        }
    } catch (e) {
        showToast('Checkout connection error: ' + e.message, 'danger');
    }
}

// Daily Sales Operations (for Sub-Admins & Admins)
async function loadDailySales() {
    try {
        const res = await fetch('api/sales.php?action=daily', {
            headers: { 'Authorization': `Bearer ${currentToken}` }
        });
        const json = await res.json();
        if (!json.success) return;

        const data = json.data;
        document.getElementById('dailyTotalInvoices').innerText = data.totalInvoices;
        document.getElementById('dailyTotalUnits').innerText = data.totalItemsSold;
        document.getElementById('dailyTotalRevenue').innerText = '৳ ' + data.totalRevenue.toLocaleString();
        document.getElementById('dailyTotalProfit').innerText = '৳ ' + data.totalProfit.toLocaleString();

        const tbody = document.getElementById('dailySalesTableBody');
        if (!tbody) return;

        if (data.transactions.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-secondary py-4">No sales recorded today yet.</td></tr>`;
            return;
        }

        tbody.innerHTML = data.transactions.map(tx => `
            <tr>
                <td class="fw-bold text-primary">${escapeHtml(tx.invoiceNumber)}</td>
                <td>${escapeHtml(tx.customerName || 'Walk-in')}</td>
                <td>${escapeHtml(tx.customerPhone || 'N/A')}</td>
                <td>${(tx.items || []).map(i => `${escapeHtml(i.brand)} ${escapeHtml(i.model)} (x${i.quantity})`).join(', ')}</td>
                <td class="fw-bold text-white">৳ ${Number(tx.grandTotal).toLocaleString()}</td>
                <td><span class="badge bg-info text-dark">${escapeHtml(tx.paymentMethod)}</span></td>
                <td><small class="text-light">${escapeHtml(tx.soldBy.name || 'Staff')}</small></td>
            </tr>
        `).join('');

    } catch (e) {
        console.error(e);
    }
}

// Role-Based Access Control (RBAC) Module
async function loadRBAC() {
    try {
        const res = await fetch('api/users.php', {
            headers: { 'Authorization': `Bearer ${currentToken}` }
        });
        const json = await res.json();
        if (!json.success) {
            showToast(json.error || 'Access denied', 'warning');
            return;
        }

        const users = json.users;
        const availablePerms = json.availablePermissions || {};
        const tbody = document.getElementById('rbacUsersTableBody');
        if (!tbody) return;

        tbody.innerHTML = users.map(u => {
            const isUserAdmin = (u.role === 'admin');
            const permsList = (u.permissions || []).map(p => `<span class="badge bg-primary me-1 mb-1">${p}</span>`).join(' ');

            return `
                <tr>
                    <td>
                        <div class="fw-bold text-white">${escapeHtml(u.name)}</div>
                        <small class="text-secondary">${escapeHtml(u.email)}</small>
                    </td>
                    <td>
                        <span class="badge ${isUserAdmin ? 'bg-danger' : 'bg-info text-dark'} text-uppercase">${escapeHtml(u.role)}</span>
                    </td>
                    <td>
                        <div style="max-width: 450px; display: flex; flex-wrap: wrap;">
                            ${permsList || '<span class="text-muted small">No permissions granted</span>'}
                        </div>
                    </td>
                    <td>
                        <span class="badge ${u.status === 'active' ? 'bg-success' : 'bg-secondary'}">${u.status}</span>
                    </td>
                    <td>
                        ${isUserAdmin ? '<span class="text-muted small">Super Admin (All Access)</span>' : `
                            <button class="btn btn-sm btn-outline-primary" onclick="openPermissionsModal('${u._id}', '${escapeHtml(u.name)}', ${JSON.stringify(u.permissions || []).replace(/"/g, '&quot;')})">
                                <i class="fas fa-user-shield me-1"></i> Permissions
                            </button>
                        `}
                    </td>
                </tr>
            `;
        }).join('');

    } catch (e) {
        console.error(e);
    }
}

function openPermissionsModal(userId, userName, currentPerms) {
    document.getElementById('permModalUserId').value = userId;
    document.getElementById('permModalUserName').innerText = userName;

    const checkboxes = document.querySelectorAll('.perm-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = currentPerms.includes(cb.value);
    });

    const modal = new bootstrap.Modal(document.getElementById('permissionsModal'));
    modal.show();
}

async function savePermissions() {
    const userId = document.getElementById('permModalUserId').value;
    const selectedPerms = [];
    document.querySelectorAll('.perm-checkbox:checked').forEach(cb => {
        selectedPerms.push(cb.value);
    });

    try {
        const res = await fetch(`api/users.php?action=permissions&id=${userId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentToken}`
            },
            body: JSON.stringify({ permissions: selectedPerms })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Permissions updated for Sub-Admin successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('permissionsModal')).hide();
            loadRBAC();
        } else {
            showToast(data.error || 'Failed to update permissions', 'danger');
        }
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

async function createSubAdmin() {
    const name = document.getElementById('newUserName').value.trim();
    const email = document.getElementById('newUserEmail').value.trim();
    const password = document.getElementById('newUserPassword').value.trim();
    const role = document.getElementById('newUserRole').value;

    const perms = [];
    document.querySelectorAll('.new-user-perm-checkbox:checked').forEach(cb => perms.push(cb.value));

    if (!name || !email || !password) {
        showToast('Please fill all required fields.', 'warning');
        return;
    }

    try {
        const res = await fetch('api/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentToken}`
            },
            body: JSON.stringify({ name, email, password, role, permissions: perms })
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Sub-Admin ${name} created with custom permissions!`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('createSubAdminModal')).hide();
            loadRBAC();
        } else {
            showToast(data.error || 'Failed to create user', 'danger');
        }
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

// Device Creation
async function createDevice() {
    const brand = document.getElementById('addDevBrand').value.trim();
    const model = document.getElementById('addDevModel').value.trim();
    const variant = document.getElementById('addDevVariant').value.trim();
    const price = parseFloat(document.getElementById('addDevPrice').value) || 0;
    const costPrice = parseFloat(document.getElementById('addDevCost').value) || 0;
    const stock = parseInt(document.getElementById('addDevStock').value) || 0;
    const minStockAlert = parseInt(document.getElementById('addDevMinAlert').value) || 3;

    if (!brand || !model || price <= 0) {
        showToast('Brand, model, and selling price are required.', 'warning');
        return;
    }

    try {
        const res = await fetch('api/mobiles.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentToken}`
            },
            body: JSON.stringify({ brand, model, variant, price, costPrice, stock, minStockAlert })
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Device ${brand} ${model} added to inventory!`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addDeviceModal')).hide();
            loadInventory();
            loadDashboard();
        } else {
            showToast(data.error || 'Failed to add device', 'danger');
        }
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

// Helpers
function showToast(msg, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `alert alert-${type} shadow-lg py-2 px-3 mb-2 rounded`;
    toast.style.minWidth = '280px';
    toast.innerHTML = `<div class="d-flex align-items-center justify-content-between">
        <span>${escapeHtml(msg)}</span>
        <button type="button" class="btn-close btn-close-white ms-2" onclick="this.parentElement.parentElement.remove()"></button>
    </div>`;

    container.appendChild(toast);
    setTimeout(() => {
        if (toast.parentElement) toast.remove();
    }, 4500);
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

