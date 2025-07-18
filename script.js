document.addEventListener('DOMContentLoaded', function() {
    // Initialize the entire ERP system
    initERP();
    // Sidebar Inventory Dropdown logic
    const inventoryDropdown = document.getElementById('inventoryDropdown');
    const inventoryDropdownMenu = document.getElementById('inventoryDropdownMenu');
    if (inventoryDropdown && inventoryDropdownMenu) {
        inventoryDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            inventoryDropdownMenu.classList.toggle('open');
            inventoryDropdown.classList.toggle('open');
        });
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!inventoryDropdown.contains(e.target) && !inventoryDropdownMenu.contains(e.target)) {
                inventoryDropdownMenu.classList.remove('open');
                inventoryDropdown.classList.remove('open');
            }
        });
    }
});

/**
 * Main initialization function. Calls all other init functions.
 */
function initERP() {
    initSidebarMenu();
    initDashboard();
    initRawMaterials();
    initFinishedGoods();
    initTransactions();
    initReports();
    initModalsAndForms();
    initDatePickers();
}

// =================================================================================
// SIDEBAR AND NAVIGATION
// =================================================================================

function initSidebarMenu() {
    const menuItems = document.querySelectorAll('.menu-item');
    const moduleContents = document.querySelectorAll('.module-content');
    const headerTitle = document.querySelector('.header-title');

    menuItems.forEach(item => {
        if (item.dataset.module) {
            item.addEventListener('click', function(e) {
                if (this.classList.contains('menu-dropdown')) {
                    e.stopPropagation();
                    toggleDropdown(this);
                    return;
                }
                
                const module = this.dataset.module;
                if (this.classList.contains('active')) return;

                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                headerTitle.textContent = this.querySelector('span').textContent;
                moduleContents.forEach(content => content.classList.remove('active'));
                const targetModule = document.getElementById(module);
                if (targetModule) {
                    targetModule.classList.add('active');
                }
            });
        }
    });

    const inventoryDropdown = document.getElementById('inventoryDropdown');
    if (inventoryDropdown) {
        inventoryDropdown.addEventListener('click', () => toggleDropdown(inventoryDropdown));
    }

    // --- Logout Functionality ---
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            // Redirect to login.html
            window.location.href = 'login.html';
        });
    }
}

function toggleDropdown(dropdownElement) {
    const dropdownMenu = dropdownElement.nextElementSibling;
    if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
        dropdownMenu.classList.toggle('open');
        dropdownElement.querySelector('.fa-chevron-down').classList.toggle('fa-rotate-180');
    }
}


// =================================================================================
// DASHBOARD MODULE
// =================================================================================

function initDashboard() {
    if (document.getElementById('inventoryMovementChart') && document.getElementById('stockLevelsChart')) {
        initDashboardCharts();
    }

    // --- Dynamic Stat Cards ---
    updateDashboardStats();

    document.querySelectorAll('#dashboard .chart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.parentElement.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const chartId = this.closest('.chart-container').querySelector('canvas').id;
            updateDashboardCharts(this.textContent.trim(), chartId);
        });
    });

    const viewAllBtn = document.querySelector('#dashboard .table-actions .btn-primary');
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', function() {
            const transactionMenuItem = document.querySelector('.menu-item[data-module="transactions"]');
            if(transactionMenuItem) transactionMenuItem.click();
        });
    }
}

// --- Demo Data for Stats Calculation ---
function getDemoTransactions() {
    // Simulate fetching recent transactions from the table
    const rows = document.querySelectorAll('#dashboard table tbody tr');
    let transactions = [];
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            transactions.push({
                date: cells[0].textContent,
                id: cells[1].textContent,
                material: cells[2].textContent,
                type: cells[3].textContent.includes('OUT') ? 'OUT' : 'IN',
                quantity: parseInt(cells[4].textContent) || 0,
                location: cells[5].textContent,
                balance: parseInt(cells[6].textContent) || 0
            });
        }
    });
    return transactions;
}

function getDemoRawMaterials() {
    // Simulate fetching raw materials from the table
    const rows = document.querySelectorAll('#rawMaterialTable tbody tr');
    let materials = [];
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            materials.push({
                name: cells[1].textContent,
                stock: parseFloat(cells[4].textContent.replace(/[^0-9.]/g, '')) || 0,
                status: cells[6].textContent.trim(),
                unit: (cells[4].textContent.match(/kg|Bags|Pieces/i) || [''])[0]
            });
        }
    });
    return materials;
}

function updateDashboardStats() {
    // Calculate stats from demo data
    const materials = getDemoRawMaterials();
    const transactions = getDemoTransactions();

    // Total Inventory Value (simulate with stock * unit cost, assume unit cost = 100 for demo)
    let totalValue = 0;
    materials.forEach(mat => {
        totalValue += mat.stock * 100; // Replace 100 with actual unit cost if available
    });

    // Materials In/Out (this month, simulate by counting IN/OUT in transactions)
    let materialsIn = 0, materialsOut = 0;
    transactions.forEach(trx => {
        if (trx.type === 'IN') materialsIn += trx.quantity;
        if (trx.type === 'OUT') materialsOut += trx.quantity;
    });

    // Low Stock Items (status contains 'Out of Stock' or 'Low Stock')
    let lowStockCount = materials.filter(mat =>
        mat.status.toLowerCase().includes('out of stock') ||
        mat.status.toLowerCase().includes('low stock')
    ).length;

    // Update DOM
    const statCards = document.querySelectorAll('.dashboard-grid .stat-card');
    if (statCards.length >= 4) {
        statCards[0].querySelector('.stat-value').textContent = '₱' + totalValue.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        statCards[1].querySelector('.stat-value').textContent = materialsIn + ' Bags';
        statCards[2].querySelector('.stat-value').textContent = materialsOut + ' Bags';
        statCards[3].querySelector('.stat-value').textContent = lowStockCount;
    }
}

function initDashboardCharts() {
    const inventoryCtx = document.getElementById('inventoryMovementChart').getContext('2d');
    window.inventoryMovementChart = new Chart(inventoryCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [
                {
                    label: 'Materials In', data: [120, 190, 170, 210, 220, 180],
                    borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2, tension: 0.3, fill: true
                },
                {
                    label: 'Materials Out', data: [80, 120, 150, 180, 190, 170],
                    borderColor: '#EF4444', backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2, tension: 0.3, fill: true
                }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantity (Bags)' } } } }
    });
    
    const stockCtx = document.getElementById('stockLevelsChart').getContext('2d');
    window.stockLevelsChart = new Chart(stockCtx, {
        type: 'bar',
        data: {
            labels: ['PP Propilinas', 'Nylon', 'ABS', 'Polystyrene', 'HIPS H-Impact'],
            datasets: [{
                label: 'Current Stock', data: [9, 1, 1, 9, 70],
                backgroundColor: ['rgba(59, 130, 246, 0.7)', 'rgba(239, 68, 68, 0.7)', 'rgba(239, 68, 68, 0.7)', 'rgba(234, 179, 8, 0.7)', 'rgba(16, 185, 129, 0.7)'],
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${c.raw} Bags` } } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantity (Bags)' } } } }
    });
}

function updateDashboardCharts(period, chartId) {
    if (chartId === 'inventoryMovementChart') {
        let newDataIn, newDataOut, newLabels;
        switch(period.toLowerCase()) {
            case 'monthly': newLabels = ['Month 1', 'Month 2', 'Month 3']; newDataIn = [220, 240, 210]; newDataOut = [180, 200, 170]; break;
            case 'quarterly': newLabels = ['Q1', 'Q2', 'Q3', 'Q4']; newDataIn = [650, 700, 680, 720]; newDataOut = [550, 600, 580, 620]; break;
            default: newLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4']; newDataIn = [50, 60, 70, 40]; newDataOut = [40, 50, 60, 30];
        }
        window.inventoryMovementChart.data.labels = newLabels;
        window.inventoryMovementChart.data.datasets[0].data = newDataIn;
        window.inventoryMovementChart.data.datasets[1].data = newDataOut;
        window.inventoryMovementChart.update();
    }
    if (chartId === 'stockLevelsChart') {
        if (period.toLowerCase() === 'critical') {
            window.stockLevelsChart.data.labels = ['Nylon', 'ABS'];
            window.stockLevelsChart.data.datasets[0].data = [1, 1];
        } else {
            window.stockLevelsChart.data.labels = ['PP Propilinas', 'Nylon', 'ABS', 'Polystyrene', 'HIPS H-Impact'];
            window.stockLevelsChart.data.datasets[0].data = [9, 1, 1, 9, 70];
        }
        window.stockLevelsChart.update();
    }
}

// =================================================================================
// RAW MATERIALS MODULE
// =================================================================================

function initRawMaterials() {
    const searchInput = document.getElementById('rawMaterialSearchInput');
    const table = document.getElementById('rawMaterialTable');
    if (searchInput && table) {
        const tableRows = table.querySelectorAll('tbody tr');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            tableRows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// =================================================================================
// FINISHED GOODS MODULE
// =================================================================================

function initFinishedGoods() {
    const searchInput = document.getElementById('productSearchInput');
    const table = document.getElementById('productTable');
    if (searchInput && table) {
        const tableRows = table.querySelectorAll('tbody tr');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            tableRows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// =================================================================================
// TRANSACTIONS MODULE
// =================================================================================

function initTransactions() {
    const applyFiltersBtn = document.getElementById('applyTransactionFilters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', filterTransactions);
    }
}

function filterTransactions() {
    const transType = document.getElementById('transTypeFilter').value;
    const transMaterial = document.getElementById('transMaterialFilter').value;
    const transDateFrom = document.getElementById('transDateFrom').value;
    const transDateTo = document.getElementById('transDateTo').value;
    
    const rows = document.querySelectorAll('#transactionTable tbody tr');
    
    rows.forEach(row => {
        let showRow = true;
        
        const typeBadge = row.cells[4].querySelector('.badge');
        if (transType !== 'all' && typeBadge) {
            const type = typeBadge.textContent.toLowerCase();
            if ((transType === 'in' && type !== 'in') || (transType === 'out' && type !== 'out')) {
                showRow = false;
            }
        }
        
        const materialCell = row.cells[2].textContent.toLowerCase();
        if (transMaterial !== 'all' && !materialCell.includes(transMaterial)) {
            showRow = false;
        }
        
        const dateStr = row.cells[0].textContent;
        const rowDate = new Date(dateStr);
        if (transDateFrom) {
            const fromDate = new Date(transDateFrom);
            if (rowDate < fromDate) showRow = false;
        }
        if (transDateTo) {
            const toDate = new Date(transDateTo);
            if (rowDate > toDate) showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}


// =================================================================================
// REPORTS MODULE
// =================================================================================

function initReports() {
    const reportPeriod = document.getElementById('reportPeriod');
    if (reportPeriod) {
        reportPeriod.addEventListener('change', function() {
            document.querySelectorAll('.custom-range').forEach(group => {
                group.style.display = this.value === 'custom' ? 'block' : 'none';
            });
        });
    }
    
    const generateReportBtn = document.querySelectorAll('#reports #generateReportBtn');
    generateReportBtn.forEach(btn => {
        btn.addEventListener('click', generateReport);
    });
    
    if (document.getElementById('reportChart')) {
        initReportChart();
    }
}

function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const reportPeriod = document.getElementById('reportPeriod').value;
    const reportDateFrom = document.getElementById('reportDateFrom').value;
    const reportDateTo = document.getElementById('reportDateTo').value;
    
    const reportTitle = document.querySelector('.report-results h3');
    const reportSummaryItems = document.querySelectorAll('.summary-item p');
    
    let title = `${reportType.charAt(0).toUpperCase() + reportType.slice(1)} Report`;
    let periodText = ` - ${reportPeriod.charAt(0).toUpperCase() + reportPeriod.slice(1)}`;
    if (reportPeriod === 'custom') {
        periodText = ` - From ${reportDateFrom} to ${reportDateTo}`;
    }
    reportTitle.textContent = title + periodText;

    // Demo data - in a real app, this would be fetched
    reportSummaryItems[0].textContent = (Math.floor(Math.random() * 200) + 100) + " Bags";
    reportSummaryItems[1].textContent = (Math.floor(Math.random() * 50)) + " Bags";
    reportSummaryItems[2].textContent = (Math.floor(Math.random() * 50)) + " Bags";
    reportSummaryItems[3].textContent = (Math.floor(Math.random() * 10)) + " Items";

    updateReportChart(reportType);
}

function initReportChart() {
    const reportCtx = document.getElementById('reportChart').getContext('2d');
    window.reportChart = new Chart(reportCtx, {
        type: 'bar',
        data: {
            labels: ['PP Propilinas', 'Nylon', 'ABS', 'Polystyrene', 'HIPS H-Impact'],
            datasets: [{
                label: 'Current Stock',
                data: [9, 1, 1, 9, 70],
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function updateReportChart(reportType) {
    const chart = window.reportChart;
    if (!chart) return;

    if (reportType === 'transactions') {
        chart.config.type = 'line';
        chart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        chart.data.datasets = [
            { label: 'In', data: [20, 30, 25, 40], borderColor: 'var(--success)', fill: false },
            { label: 'Out', data: [15, 25, 20, 35], borderColor: 'var(--error)', fill: false }
        ];
    } else {
        chart.config.type = 'bar';
        chart.data.labels = ['PP', 'Nylon', 'ABS', 'PS', 'HIPS'];
        chart.data.datasets = [{
            label: 'Stock Level',
            data: [50, 20, 35, 25, 60],
            backgroundColor: 'rgba(59, 130, 246, 0.7)'
        }];
    }
    chart.update();
}
