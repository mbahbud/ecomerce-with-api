<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if (!isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Cart ID required']);
    exit;
}

$cart_id = intval($_POST['cart_id']);
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Verify cart item belongs to user
$verify_query = "SELECT id FROM cart WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    exit;
}

// Delete item
$delete_query = "DELETE FROM cart WHERE id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $cart_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
}

closeDBConnection($conn);
?>