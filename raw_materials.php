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
// Fetch All Raw Materials with the new code_color column
$raw_materials_sql = "
    SELECT rm.id, rm.name, rm.code_color, c.name as category, rm.stock_quantity, l.name as location, rm.status, rm.image1, rm.image2, rm.image3
    FROM raw_materials rm
    LEFT JOIN categories c ON rm.category_id = c.id
    LEFT JOIN locations l ON rm.location_id = l.id
    ORDER BY rm.id";
$raw_materials_result = $conn->query($raw_materials_sql);
$locations_result = $conn->query("SELECT id, name FROM locations ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raw Materials - James Polymer ERP</title>
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
                    <a href="raw_materials.php" class="menu-item active" data-module="raw-materials">
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
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1 class="header-title">Raw Materials</h1>
            </div>
            <div class="header-right">
                <div class="user-profile" style="padding: 8px 12px; border-radius: 12px; display: flex; align-items: center;">
                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: #2563eb; margin-right: 10px;"></i>
                    <span style="font-weight: 600; color: #475569; font-size: 1rem;"> <?php echo ucfirst($role); ?> </span>
                </div>
            </div>
        </div>
        <div class="content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Material added successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">Error adding material. Please try again.</div>
            <?php endif; ?>
            <div class="module-content active" id="raw-materials">
                <div class="table-section">
                    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="search-container" style="position: relative; display: inline-block;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="rawMaterialSearchInput" class="search-input" placeholder="Search raw materials...">
                            <div id="recentSearches" class="recent-searches-dropdown" style="display:none;"></div>
                        </div>
                        <div class="table-title" style="text-align: center; flex-grow: 1;">Raw Materials Inventory</div>
                        <button class="btn btn-primary" id="openAddMaterialModal">
                            <i class="fas fa-plus"></i> Add Material
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table id="rawMaterialTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Material Name</th>
                                    <th>Code/Color</th>
                                    <th>Stock</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($raw_materials_result && $raw_materials_result->num_rows > 0) {
                                    $raw_materials_result->data_seek(0); // Reset pointer
                                    $count = 1;
                                    while ($row = $raw_materials_result->fetch_assoc()) {
                                        $status = ($row['stock_quantity'] > 0) ? 'In Stock' : 'Out of Stock';
                                        $status_class = ($status == 'In Stock') ? 'completed' : 'cancelled';
                                        echo "<tr"
                                            . " data-image1='" . htmlspecialchars($row['image1'] ?? '') . "'"
                                            . " data-image2='" . htmlspecialchars($row['image2'] ?? '') . "'"
                                            . " data-image3='" . htmlspecialchars($row['image3'] ?? '') . "'"
                                            . " data-location-id='" . htmlspecialchars($row['location_id']) . "'"
                                            . ">";
                                        echo "<td>" . $count++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['code_color']) . "</td>";
                                        echo "<td>" . htmlspecialchars(number_format($row['stock_quantity'], 2)) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                                        echo "<td><span class='status-badge " . $status_class . "'>" . htmlspecialchars($status) . "</span></td>";
                                        echo "<td>
                                                <button class='btn btn-outline view-raw-btn' data-raw-id='" . htmlspecialchars($row['id']) . "'>View</button>
                                                <button class='btn btn-primary edit-raw-btn' data-raw-id='" . htmlspecialchars($row['id']) . "'>Edit</button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                     echo "<tr><td colspan='7' style='text-align:center;'>No raw materials found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Raw Material Modal (direct child of body) -->
    <div id="addMaterialModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" id="closeAddMaterialModal">&times;</span>
            <form class="form-section" id="addMaterialForm" method="POST" action="add_material.php" enctype="multipart/form-data">
                <div class="form-header"><h2 class="form-title">Add Raw Material</h2></div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Material Name</label>
                        <input type="text" class="form-input" name="name" placeholder="Enter material name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Material Code & Color</label>
                        <input type="text" class="form-input" name="code_color" placeholder="e.g., RM-006 Red" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Initial Stock Quantity</label>
                        <input type="number" class="form-input" name="stock_quantity" placeholder="Enter quantity" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <select class="form-input" name="location_id" required>
                            <option value="">Select Location</option>
                            <?php while ($row = $locations_result->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Image 1</label>
                        <input type="file" class="form-input" name="image1" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image 2</label>
                        <input type="file" class="form-input" name="image2" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image 3</label>
                        <input type="file" class="form-input" name="image3" accept="image/*">
                    </div>
                </div>
                <div style="margin-top: 25px;">
                    <button class="btn btn-primary" type="submit">Save Material</button>
                    <button class="btn btn-outline" type="button" id="cancelAddMaterialModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <!-- View Raw Material Modal (direct child of body) -->
    <div id="viewRawMaterialModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" id="closeViewRawMaterialModal">&times;</span>
            <div class="form-section" style="box-shadow:none; border:none; margin-bottom:0;">
                <div class="form-header"><h2 class="form-title">Raw Material Details</h2></div>
                <div class="slider-container"><div class="slider" id="rawMaterialImageSlider"></div><div class="slider-controls"><button class="slider-btn" id="prevRawImage"><i class="fa-solid fa-chevron-left"></i></button><button class="slider-btn" id="nextRawImage"><i class="fa-solid fa-chevron-right"></i></button></div></div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Material Name</label><p id="rawMaterialName" class="form-input" style="border:none; background:none;"></p></div>
                    <div class="form-group"><label class="form-label">Material Code</label><p id="rawMaterialCode" class="form-input" style="border:none; background:none;"></p></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Stock</label><p id="rawMaterialStock" class="form-input" style="border:none; background:none;"></p></div>
                    <div class="form-group"><label class="form-label">Location</label><p id="rawMaterialLocation" class="form-input" style="border:none; background:none;"></p></div>
                    <div class="form-group"><label class="form-label">Status</label><p id="rawMaterialStatus" class="form-input" style="border:none; background:none;"></p></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Raw Material Modal (direct child of body) -->
    <div id="editRawMaterialModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <span class="close-modal" id="closeEditRawMaterialModal">&times;</span>
            <form class="form-section" id="editRawMaterialForm" method="POST" action="edit_material.php" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editRawMaterialId">
                <div class="form-header"><h2 class="form-title">Edit Raw Material</h2></div>
                <div class="slider-container"><div class="slider" id="editRawMaterialImageSlider"></div><div class="slider-controls"><button class="slider-btn" id="prevEditRawImage" type="button"><i class="fa-solid fa-chevron-left"></i></button><button class="slider-btn" id="nextEditRawImage" type="button"><i class="fa-solid fa-chevron-right"></i></button></div></div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Material Name</label>
                        <input type="text" class="form-input" name="name" id="editRawMaterialName" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Material Code & Color</label>
                        <input type="text" class="form-input" name="code_color" id="editRawMaterialCodeColor" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" class="form-input" name="stock_quantity" id="editRawMaterialStock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <select class="form-input" name="location_id" id="editRawMaterialLocation" required>
                            <option value="">Select Location</option>
                            <?php $locs = $conn->query("SELECT id, name FROM locations ORDER BY name"); while ($row = $locs->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Image 1</label>
                        <input type="file" class="form-input" name="image1" id="editImage1" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image 2</label>
                        <input type="file" class="form-input" name="image2" id="editImage2" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image 3</label>
                        <input type="file" class="form-input" name="image3" id="editImage3" accept="image/*">
                    </div>
                </div>
                <div style="margin-top: 25px;">
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                    <button class="btn btn-outline" type="button" id="cancelEditRawMaterialModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
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
            // Add Material Modal logic
            const openAddMaterialBtn = document.getElementById('openAddMaterialModal');
            const addMaterialModal = document.getElementById('addMaterialModal');
            const closeAddMaterialModal = document.getElementById('closeAddMaterialModal');
            const cancelAddMaterialModal = document.getElementById('cancelAddMaterialModal');
            if (openAddMaterialBtn && addMaterialModal) {
                openAddMaterialBtn.addEventListener('click', function() {
                    addMaterialModal.style.display = 'block';
                });
            }
            if (closeAddMaterialModal && addMaterialModal) {
                closeAddMaterialModal.addEventListener('click', function() {
                    addMaterialModal.style.display = 'none';
                });
            }
            if (cancelAddMaterialModal && addMaterialModal) {
                cancelAddMaterialModal.addEventListener('click', function() {
                    addMaterialModal.style.display = 'none';
                });
            }
            if (addMaterialModal) {
                addMaterialModal.addEventListener('click', function(e) {
                    if (e.target === addMaterialModal) {
                        addMaterialModal.style.display = 'none';
                    }
                });
            }
            // AJAX for Add Material
            const addMaterialForm = document.getElementById('addMaterialForm');
            if (addMaterialForm) {
                addMaterialForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(addMaterialForm);
                    fetch('add_material.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.text())
                    .then(response => {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                showAlert('Material added successfully!', 'success');
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                showAlert('Error adding material. Please try again.', 'danger');
                            }
                        } catch {
                            window.location.reload();
                        }
                    })
                    .catch(() => {
                        showAlert('Error adding material. Please try again.', 'danger');
                    });
                });
            }
            function showAlert(message, type) {
                let alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-' + type;
                alertDiv.textContent = message;
                document.querySelector('.table-section').prepend(alertDiv);
                setTimeout(() => alertDiv.remove(), 2000);
            }
            // Enhanced search with recent searches and nothing to show
            const searchInput = document.getElementById('rawMaterialSearchInput');
            const table = document.getElementById('rawMaterialTable');
            const recentSearchesDiv = document.getElementById('recentSearches');
            const maxRecent = 5;
            function getRecentSearches() {
                return JSON.parse(localStorage.getItem('recentRawMaterialSearches') || '[]');
            }
            function setRecentSearches(arr) {
                localStorage.setItem('recentRawMaterialSearches', JSON.stringify(arr.slice(0, maxRecent)));
            }
            function showRecentSearches() {
                const recent = getRecentSearches();
                if (recent.length === 0) {
                    recentSearchesDiv.style.display = 'none';
                    return;
                }
                recentSearchesDiv.innerHTML = '';
                recent.forEach(term => {
                    const div = document.createElement('div');
                    div.textContent = term;
                    div.onclick = () => {
                        searchInput.value = term;
                        filterTable(term);
                        recentSearchesDiv.style.display = 'none';
                    };
                    recentSearchesDiv.appendChild(div);
                });
                recentSearchesDiv.style.display = 'block';
            }
            function filterTable(searchTerm) {
                const tableRows = table.querySelectorAll('tbody tr');
                let anyVisible = false;
                tableRows.forEach(row => {
                    // skip the nothing-to-show row
                    if (row.classList.contains('nothing-to-show-row')) return;
                    const match = row.textContent.toLowerCase().includes(searchTerm.toLowerCase());
                    row.style.display = match ? '' : 'none';
                    if (match) anyVisible = true;
                });
                // Show/hide 'nothing to show' message
                let nothingRow = table.querySelector('.nothing-to-show-row');
                if (!anyVisible) {
                    if (!nothingRow) {
                        nothingRow = document.createElement('tr');
                        nothingRow.className = 'nothing-to-show-row';
                        nothingRow.innerHTML = `<td colspan="7" style="text-align:center; color:#888; font-weight:600;">No raw materials found.</td>`;
                        table.querySelector('tbody').appendChild(nothingRow);
                    }
                } else if (nothingRow) {
                    nothingRow.remove();
                }
            }
            if (searchInput && table) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value;
                    filterTable(searchTerm);
                    // Save to recent searches if not empty and not already present
                    if (searchTerm.trim()) {
                        let recent = getRecentSearches();
                        recent = recent.filter(term => term !== searchTerm);
                        recent.unshift(searchTerm);
                        setRecentSearches(recent);
                    }
                });
                searchInput.addEventListener('focus', showRecentSearches);
                searchInput.addEventListener('blur', () => setTimeout(() => recentSearchesDiv.style.display = 'none', 200));
            }
            // View Raw Material Modal logic
            const viewRawMaterialModal = document.getElementById('viewRawMaterialModal');
            const closeViewRawMaterialModal = document.getElementById('closeViewRawMaterialModal');
            const rawMaterialImageSlider = document.getElementById('rawMaterialImageSlider');
            const prevRawImage = document.getElementById('prevRawImage');
            const nextRawImage = document.getElementById('nextRawImage');

            function openViewRawMaterialModal(material) {
                document.getElementById('rawMaterialName').textContent = material.name;
                document.getElementById('rawMaterialCode').textContent = material.code_color;
                document.getElementById('rawMaterialStock').textContent = material.stock_quantity;
                document.getElementById('rawMaterialLocation').textContent = material.location;
                document.getElementById('rawMaterialStatus').textContent = material.status;
                // Images
                rawMaterialImageSlider.innerHTML = '';
                material.images.forEach((img, idx) => {
                    const imgTag = document.createElement('img');
                    imgTag.src = img;
                    imgTag.style.display = idx === 0 ? 'block' : 'none';
                    imgTag.style.width = '100%';
                    imgTag.style.maxHeight = '200px';
                    rawMaterialImageSlider.appendChild(imgTag);
                });
                let currentImage = 0;
                function showImage(idx) {
                    const imgs = rawMaterialImageSlider.querySelectorAll('img');
                    imgs.forEach((img, i) => img.style.display = i === idx ? 'block' : 'none');
                }
                prevRawImage.onclick = function() {
                    if (material.images.length === 0) return;
                    currentImage = (currentImage - 1 + material.images.length) % material.images.length;
                    showImage(currentImage);
                };
                nextRawImage.onclick = function() {
                    if (material.images.length === 0) return;
                    currentImage = (currentImage + 1) % material.images.length;
                    showImage(currentImage);
                };
                showImage(0);
                viewRawMaterialModal.style.display = 'block';
            }
            if (closeViewRawMaterialModal && viewRawMaterialModal) {
                closeViewRawMaterialModal.addEventListener('click', function() {
                    viewRawMaterialModal.style.display = 'none';
                });
            }
            if (viewRawMaterialModal) {
                viewRawMaterialModal.addEventListener('click', function(e) {
                    if (e.target === viewRawMaterialModal) {
                        viewRawMaterialModal.style.display = 'none';
                    }
                });
            }
            document.querySelectorAll('.view-raw-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const material = {
                        name: row.children[1].textContent,
                        code_color: row.children[2].textContent,
                        stock_quantity: row.children[3].textContent,
                        location: row.children[4].textContent,
                        status: row.children[5].textContent,
                        images: []
                    };
                    // Get image filenames from data attributes
                    material.images = [];
                    if (row.dataset.image1) material.images.push('images/' + row.dataset.image1);
                    if (row.dataset.image2) material.images.push('images/' + row.dataset.image2);
                    if (row.dataset.image3) material.images.push('images/' + row.dataset.image3);
                    openViewRawMaterialModal(material);
                });
            });

            // Edit Raw Material Modal logic
            const editRawMaterialModal = document.getElementById('editRawMaterialModal');
            const closeEditRawMaterialModal = document.getElementById('closeEditRawMaterialModal');
            const cancelEditRawMaterialModal = document.getElementById('cancelEditRawMaterialModal');
            const editRawMaterialImageSlider = document.getElementById('editRawMaterialImageSlider');
            const prevEditRawImage = document.getElementById('prevEditRawImage');
            const nextEditRawImage = document.getElementById('nextEditRawImage');
            let editImages = [];
            let editCurrentImage = 0;

            function openEditRawMaterialModal(material) {
                document.getElementById('editRawMaterialId').value = material.id;
                document.getElementById('editRawMaterialName').value = material.name;
                document.getElementById('editRawMaterialCodeColor').value = material.code_color;
                document.getElementById('editRawMaterialStock').value = material.stock_quantity;
                document.getElementById('editRawMaterialLocation').value = material.location_id;
                // Images
                editImages = material.images;
                editCurrentImage = 0;
                renderEditImageSlider();
                editRawMaterialModal.style.display = 'block';
            }
            function renderEditImageSlider() {
                editRawMaterialImageSlider.innerHTML = '';
                editImages.forEach((img, idx) => {
                    const imgTag = document.createElement('img');
                    imgTag.src = img ? 'images/' + img : 'images/placeholder.png';
                    imgTag.style.display = idx === editCurrentImage ? 'block' : 'none';
                    imgTag.style.width = '100%';
                    imgTag.style.maxHeight = '200px';
                    editRawMaterialImageSlider.appendChild(imgTag);
                });
            }
            if (prevEditRawImage) prevEditRawImage.onclick = function() {
                if (editImages.length === 0) return;
                editCurrentImage = (editCurrentImage - 1 + editImages.length) % editImages.length;
                renderEditImageSlider();
            };
            if (nextEditRawImage) nextEditRawImage.onclick = function() {
                if (editImages.length === 0) return;
                editCurrentImage = (editCurrentImage + 1) % editImages.length;
                renderEditImageSlider();
            };
            if (closeEditRawMaterialModal && editRawMaterialModal) {
                closeEditRawMaterialModal.addEventListener('click', function() {
                    editRawMaterialModal.style.display = 'none';
                });
            }
            if (cancelEditRawMaterialModal && editRawMaterialModal) {
                cancelEditRawMaterialModal.addEventListener('click', function() {
                    editRawMaterialModal.style.display = 'none';
                });
            }
            if (editRawMaterialModal) {
                editRawMaterialModal.addEventListener('click', function(e) {
                    if (e.target === editRawMaterialModal) {
                        editRawMaterialModal.style.display = 'none';
                    }
                });
            }
            document.querySelectorAll('.edit-raw-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const material = {
                        id: row.children[0].textContent.trim(),
                        name: row.children[1].textContent.trim(),
                        code_color: row.children[2].textContent.trim(),
                        stock_quantity: row.children[3].textContent.trim(),
                        location: row.children[4].textContent.trim(),
                        location_id: row.getAttribute('data-location-id'),
                        images: []
                    };
                    if (row.dataset.image1) material.images.push(row.dataset.image1);
                    if (row.dataset.image2) material.images.push(row.dataset.image2);
                    if (row.dataset.image3) material.images.push(row.dataset.image3);
                    openEditRawMaterialModal(material);
                });
            });
        });
    </script>
</body>
</html> 