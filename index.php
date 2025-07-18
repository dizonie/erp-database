<?php
// index.php (Dashboard only, with its own sidebar, header, and dashboard modals)

session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

// Fetch user details from session for display.
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

// --- DATA FETCHING FOR THE DASHBOARD ---
$total_inventory_value_query = "SELECT SUM(stock_quantity) as total_value FROM raw_materials";
$total_value_result = $conn->query($total_inventory_value_query);
$total_inventory_value = $total_value_result->fetch_assoc()['total_value'] ?? 0;

$materials_in_query = "SELECT SUM(quantity) as total_in FROM transactions WHERE type = 'IN' AND MONTH(transaction_date) = MONTH(CURRENT_DATE())";
$materials_in_result = $conn->query($materials_in_query);
$materials_in = $materials_in_result->fetch_assoc()['total_in'] ?? 0;

$materials_out_query = "SELECT SUM(quantity) as total_out FROM transactions WHERE type = 'OUT' AND MONTH(transaction_date) = MONTH(CURRENT_DATE())";
$materials_out_result = $conn->query($materials_out_query);
$materials_out = $materials_out_result->fetch_assoc()['total_out'] ?? 0;

$low_stock_query = "SELECT COUNT(*) as low_stock_count FROM raw_materials WHERE status = 'Low Stock' OR status = 'Out of Stock'";
$low_stock_result = $conn->query($low_stock_query);
$low_stock_count = $low_stock_result->fetch_assoc()['low_stock_count'] ?? 0;

$recent_transactions_sql = "
    SELECT t.transaction_date, t.transaction_id_str, rm.name as material_name, t.type, t.quantity, l.name as location_name, t.balance
    FROM transactions t
    JOIN raw_materials rm ON t.raw_material_id = rm.id
    JOIN locations l ON t.location_id = l.id
    ORDER BY t.transaction_date DESC
    LIMIT 5";
$transactions_result = $conn->query($recent_transactions_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>James Polymer Manufacturing Corporation - Production & Inventory ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="logo.png">
</head>
<body>
    <!-- Sidebar Navigation (Dashboard only) -->
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
                <div class="menu-item active" data-module="dashboard">
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
                <a href="reports.php" class="menu-item">
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
    <!-- Main Content Area (Dashboard only) -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1 class="header-title">Dashboard</h1>
            </div>
           <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"><?php echo ucfirst($role); ?></span>
                </div>
            </div>
        </div>
        <div class="content">
            <!-- Dashboard Module -->
            <div class="module-content active" id="dashboard">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Total Inventory Value</div>
                                <div class="stat-subtitle">Current stock worth</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-boxes"></i>
                            </div>
                        </div>
                        <div class="stat-value">₱<?php echo number_format($total_inventory_value, 2); ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>5.2% from last month</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Materials In</div>
                                <div class="stat-subtitle">This month</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-arrow-circle-down"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($materials_in); ?> Bags</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>12.7% from last month</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Materials Out</div>
                                <div class="stat-subtitle">This month</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-arrow-circle-up"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($materials_out); ?> Bags</div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-down"></i>
                            <span>3.5% from last month</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Low Stock Items</div>
                                <div class="stat-subtitle">Needs attention</div>
                            </div>
                            <div class="stat-icon red">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $low_stock_count; ?></div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-up"></i>
                            <span>3 new alerts today</span>
                        </div>
                    </div>
                </div>
                <div class="charts-section">
                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">Inventory Movement</div>
                            <div class="chart-controls">
                                <button class="chart-btn active">Weekly</button>
                                <button class="chart-btn">Monthly</button>
                                <button class="chart-btn">Quarterly</button>
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <canvas id="inventoryMovementChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">Material Stock Levels</div>
                            <div class="chart-controls">
                                <button class="chart-btn active">All</button>
                                <button class="chart-btn">Critical</button>
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <canvas id="stockLevelsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="table-section">
                    <div class="table-header">
                        <div class="table-title">Recent Transactions</div>
                        <div class="table-actions">
                            <button class="btn btn-outline">Export</button>
                            <button class="btn btn-primary">View All</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction ID</th>
                                    <th>Material</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Location</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($transactions_result && $transactions_result->num_rows > 0) {
                                    while ($row = $transactions_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . date('m/d/Y', strtotime($row['transaction_date'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['transaction_id_str']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['material_name']) . "</td>";
                                        $badge_class = strtolower($row['type']) === 'out' ? 'out' : 'in';
                                        echo "<td><span class='badge " . $badge_class . "'>" . htmlspecialchars($row['type']) . "</span></td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['quantity'])) . " Bags</td>";
                                        echo "<td>" . htmlspecialchars($row['location_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['balance'])) . " Bag</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' style='text-align:center;'>No recent transactions found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Dashboard-specific modals (if any) go here -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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