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
// Fetch All Finished Products (Finished Goods)
$products_sql = "
    SELECT p.id, p.product_code, p.name, c.name as category, p.stock_quantity, p.status, GROUP_CONCAT(rm.name SEPARATOR ', ') as materials
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_materials pm ON p.id = pm.product_id
    LEFT JOIN raw_materials rm ON pm.raw_material_id = rm.id
    GROUP BY p.id
    ORDER BY p.id";
$products_result = $conn->query($products_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List - James Polymer ERP</title>
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
                    <a href="finished_goods.php" class="menu-item active" data-module="finished-goods">
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
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1 class="header-title">Product List</h1>
            </div>
            <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"> <?php echo ucfirst($role); ?> </span>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="module-content active" id="finished-goods">
                <div class="table-section">
                    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="search-container" style="position: relative; display: inline-block;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="productSearchInput" class="search-input" placeholder="Search products...">
                        </div>
                        <div class="table-title" style="text-align: center; flex-grow: 1;">Finished Products</div>
                        <button class="btn btn-primary" id="openAddProductModal">
                            <i class="fas fa-plus"></i> Add New Product
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table id="productTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Raw Materials</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($products_result && $products_result->num_rows > 0) {
                                    $products_result->data_seek(0); // Reset pointer
                                    $count = 1;
                                    while ($row = $products_result->fetch_assoc()) {
                                        $status_class = strtolower($row['status']) === 'active' ? 'completed' : 'cancelled';
                                        echo "<tr>";
                                        echo "<td>" . $count++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['materials'] ?? 'N/A') . "</td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['stock_quantity'])) . "</td>";
                                        echo "<td><span class='status-badge " . $status_class . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                        echo "<td>
                                                <button class='btn btn-outline view-product-btn' data-product-id='" . htmlspecialchars($row['product_code']) . "'>View</button>
                                                <button class='btn btn-primary edit-product-btn' data-product-id='" . htmlspecialchars($row['product_code']) . "'>Edit</button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                     echo "<tr><td colspan='7' style='text-align:center;'>No products found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Product List-specific modals go here -->
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