<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $code_color = trim($_POST['code_color']);
    $stock_quantity = floatval($_POST['stock_quantity']);
    $location_id = intval($_POST['location_id']);
    $status = ($stock_quantity > 0) ? 'In Stock' : 'Out of Stock';
    $category_id = 1; // You can make this dynamic if you have categories
    $now = date('Y-m-d H:i:s');

    // Handle image uploads
    $imageNames = [];
    for ($i = 1; $i <= 3; $i++) {
        $imgField = 'image' . $i;
        if (isset($_FILES[$imgField]) && $_FILES[$imgField]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES[$imgField]['name'], PATHINFO_EXTENSION);
            $filename = uniqid('matimg_') . "." . $ext;
            $target = __DIR__ . "/images/" . $filename;
            if (move_uploaded_file($_FILES[$imgField]['tmp_name'], $target)) {
                $imageNames[] = $filename;
            } else {
                $imageNames[] = null;
            }
        } else {
            $imageNames[] = null;
        }
    }
    list($image1, $image2, $image3) = $imageNames;

    $stmt = $conn->prepare("INSERT INTO raw_materials (code_color, name, category_id, location_id, stock_quantity, status, image1, image2, image3, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiidssssss", $code_color, $name, $category_id, $location_id, $stock_quantity, $status, $image1, $image2, $image3, $now, $now);

    if ($stmt->execute()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => true]);
            exit;
        }
        header("Location: raw_materials.php?success=1");
        exit;
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => false]);
            exit;
        }
        header("Location: raw_materials.php?error=1");
        exit;
    }
}
?> 