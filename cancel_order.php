<?php
require_once 'config/database.php';
require_once 'includes/session.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Verify order belongs to user and can be cancelled
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    setFlashMessage('danger', 'Order not found or cannot be cancelled');
    redirect('orders.php');
}

// Get order items to restore stock
$items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Start transaction
$conn->begin_transaction();

try {
    // Update order status
    $update_query = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    // Restore product stock
    foreach ($items as $item) {
        $restore_query = "UPDATE products SET stock = stock + ? WHERE id = ?";
        $stmt = $conn->prepare($restore_query);
        $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt->execute();
    }
    
    $conn->commit();
    setFlashMessage('success', 'Order cancelled successfully');
    
} catch (Exception $e) {
    $conn->rollback();
    setFlashMessage('danger', 'Failed to cancel order');
}

closeDBConnection($conn);
redirect('orders.php');
?>