<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if (!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$cart_id = intval($_POST['cart_id']);
$quantity = intval($_POST['quantity']);
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Verify cart item belongs to user
$verify_query = "SELECT c.*, p.stock FROM cart c 
                 JOIN products p ON c.product_id = p.id 
                 WHERE c.id = ? AND c.user_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$cart_item = $stmt->get_result()->fetch_assoc();

if (!$cart_item) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    exit;
}

if ($quantity <= 0) {
    // Remove item
    $delete_query = "DELETE FROM cart WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
} else {
    // Check stock
    if ($quantity > $cart_item['stock']) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
        exit;
    }
    
    // Update quantity
    $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $quantity, $cart_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Cart updated']);
}

closeDBConnection($conn);
?>