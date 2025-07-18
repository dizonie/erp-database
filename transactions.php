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
// Fetch All Transactions for the Transactions Module
$all_transactions_sql = "
    SELECT t.transaction_date, t.transaction_id_str, rm.name as material_name, p.name as product_name, t.type, t.quantity, l.name as location_name, t.balance
    FROM transactions t
    JOIN raw_materials rm ON t.raw_material_id = rm.id
    LEFT JOIN products p ON t.product_id = p.id
    JOIN locations l ON t.location_id = l.id
    ORDER BY t.transaction_date DESC";
$all_transactions_result = $conn->query($all_transactions_sql);
// For filters
$raw_materials_for_modals = $conn->query("SELECT id, name, code_color FROM raw_materials ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - James Polymer ERP</title>
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
                <a href="index.php" class="menu-item" data-module="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
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
                    <a href="transactions.php" class="menu-item active" data-module="transactions">
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
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1 class="header-title">Transactions</h1>
            </div>
            <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"> <?php echo ucfirst($role); ?> </span>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="module-content active" id="transactions">
                <div class="section-header">
                    <h2>Material Transactions</h2>
                    <div class="actions">
                        <button class="btn btn-primary" id="addTransactionBtn">
                            <i class="fas fa-plus"></i> New Transaction
                        </button>
                        <button class="btn btn-outline">
                            <i class="fas fa-file-export"></i> Export
                        </button>
                    </div>
                </div>
                <div class="transaction-filters">
                    <div class="filter-group">
                        <label for="transTypeFilter">Transaction Type:</label>
                        <select id="transTypeFilter">
                            <option value="all">All</option>
                            <option value="in">Material In</option>
                            <option value="out">Material Out</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="transMaterialFilter">Material:</label>
                        <select id="transMaterialFilter">
                            <option value="all">All Materials</option>
                            <?php
                            if ($raw_materials_for_modals && $raw_materials_for_modals->num_rows > 0) {
                                $raw_materials_for_modals->data_seek(0);
                                while($row = $raw_materials_for_modals->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="transDateFrom">From:</label>
                        <input type="text" id="transDateFrom" class="datepicker" placeholder="Select date...">
                    </div>
                    <div class="filter-group">
                        <label for="transDateTo">To:</label>
                        <input type="text" id="transDateTo" class="datepicker" placeholder="Select date...">
                    </div>
                    <button class="btn btn-primary" id="applyTransactionFilters">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
                <div class="transactions-table table-section">
                    <div class="table-responsive">
                        <table id="transactionTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction ID</th>
                                    <th>Material</th>
                                    <th>Product Used</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Location</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($all_transactions_result && $all_transactions_result->num_rows > 0) {
                                    $all_transactions_result->data_seek(0);
                                    while ($row = $all_transactions_result->fetch_assoc()) {
                                        $badge_class = strtolower($row['type']) === 'out' ? 'out' : 'in';
                                        echo "<tr>";
                                        echo "<td>" . date('m/d/Y', strtotime($row['transaction_date'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['transaction_id_str']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['material_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['product_name'] ?? 'N/A') . "</td>";
                                        echo "<td><span class='badge " . $badge_class . "'>" . htmlspecialchars($row['type']) . "</span></td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['quantity'])) . " Bags</td>";
                                        echo "<td>" . htmlspecialchars($row['location_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['balance'])) . " Bag</td>";
                                        echo "<td>
                                                <button class='btn-icon' title='View Details'><i class='fas fa-eye'></i></button>
                                                <button class='btn-icon' title='Edit'><i class='fas fa-edit'></i></button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='9' style='text-align:center;'>No transactions found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pagination">
                    <button class="btn btn-outline" disabled><i class="fas fa-chevron-left"></i> Previous</button>
                    <span>Page 1 of 3</span>
                    <button class="btn btn-outline">Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </div>
    <!-- Transactions-specific modals go here -->
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