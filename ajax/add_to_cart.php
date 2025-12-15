<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$product_id = intval($_POST['product_id']);
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Check if product exists and has stock
$product_query = "SELECT stock FROM products WHERE id = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if ($product['stock'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product out of stock']);
    exit;
}

// Check if product already in cart
$check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    // Check if adding one more exceeds stock
    if ($existing['quantity'] >= $product['stock']) {
        echo json_encode(['success' => false, 'message' => 'Maximum stock reached']);
        exit;
    }
    
    // Update quantity
    $update_query = "UPDATE cart SET quantity = quantity + 1 WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $existing['id']);
    $stmt->execute();
} else {
    // Add new item
    $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
}

closeDBConnection($conn);
echo json_encode(['success' => true, 'message' => 'Product added to cart']);
?>