<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';
// Fetch user details from session for display.
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="logo.png">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="company-logo">
                <img src="logo.png" alt="Company Logo" style="width: 60px; height: 60px; border-radius: 12px; object-fit: contain; display: block;">
            </div>
            <div class="company-name">James Polymer</div>
            <div class="company-subtitle">Manufacturing Corporation</div>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Inventory Management</div>
                <div class="menu-item" data-module="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </div>
                <div class="menu-item menu-dropdown" id="inventoryDropdown">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown-menu" id="inventoryDropdownMenu">
                    <a href="raw_materials.php" class="menu-item" data-module="raw-materials">
                        <i class="fas fa-cubes"></i>
                        <span>Raw Materials</span>
                    </a>
                    <a href="finished_goods.php" class="menu-item" data-module="finished-goods">
                        <i class="fas fa-box"></i>
                        <span>Product List</span>
                    </a>
                    <a href="transactions.php" class="menu-item" data-module="transactions">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </div>
                <a href="reports.php" class="menu-item active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </div>
            <div class="menu-section">
                <div class="menu-section-title">System</div>
                <a href="logout.php" class="menu-item" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1 class="header-title">Reports</h1>
            </div>
            <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"> <?php echo ucfirst($role); ?> </span>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="module-content active" id="reports">
                 <!-- This section is kept from the original HTML. It can be made dynamic later. -->
                 <div class="section-header">
                    <h2>Inventory Reports</h2>
                    <div class="actions">
                        <button class="btn btn-primary" id="generateReportBtnPDF">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </button>
                        <button class="btn btn-outline" id="generateReportBtnExcel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
                <div class="report-filters">
                    <div class="filter-group">
                        <label for="reportType">Report Type:</label>
                        <select id="reportType">
                            <option value="inventory">Inventory Summary</option>
                            <option value="transactions">Transaction Log</option>
                            <option value="monthly">Monthly Summary</option>
                            <option value="lowstock">Low Stock Report</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="reportPeriod">Period:</label>
                        <select id="reportPeriod">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="filter-group custom-range" style="display: none;">
                        <label for="reportDateFrom">From:</label>
                        <input type="date" id="reportDateFrom" class="datepicker">
                    </div>
                    <div class="filter-group custom-range" style="display: none;">
                        <label for="reportDateTo">To:</label>
                        <input type="date" id="reportDateTo" class="datepicker">
                    </div>
                    <button class="btn btn-primary" id="generateReportBtn">
                        <i class="fas fa-filter"></i> Generate Report
                    </button>
                </div>
                <div class="report-results">
                    <h3>Monthly Inventory Report - May 2025</h3>
                    <div class="report-summary">
                        <div class="summary-item">
                            <h4>Total Inventory</h4>
                            <p>217 Bags</p>
                        </div>
                        <div class="summary-item">
                            <h4>Materials In</h4>
                            <p>0 Bags</p>
                        </div>
                        <div class="summary-item">
                            <h4>Materials Out</h4>
                            <p>45 Bags</p>
                        </div>
                        <div class="summary-item">
                            <h4>Low Stock Items</h4>
                            <p>5 Items</p>
                        </div>
                    </div>
                    <div class="report-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>In</th>
                                    <th>Out</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>GAS KNOB (10 Bags)</td>
                                    <td>0</td>
                                    <td>9 Bags</td>
                                    <td>1 Bag</td>
                                </tr>
                                <tr>
                                    <td>PLASTIC CORE (75 Bags)</td>
                                    <td>0</td>
                                    <td>5 Bags</td>
                                    <td>70 Bags</td>
                                </tr>
                                <tr>
                                    <td>SWITCH KNOB (7 Bags)</td>
                                    <td>0</td>
                                    <td>6 Bags</td>
                                    <td>1 Bag</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="report-chart">
                        <canvas id="reportChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Reports-specific modals go here -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.getElementById('logoutBtn');
            if(logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); 
                });
            }
        });
    </script>
</body>
</html> 