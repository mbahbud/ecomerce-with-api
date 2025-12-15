<?php
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orders.php');
}

$order_id = intval($_POST['order_id']);
$new_status = sanitize($_POST['status']);

$allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($new_status, $allowed_statuses)) {
    setFlashMessage('danger', 'Invalid status');
    redirect('orders.php');
}

$conn = getDBConnection();

// Update order status
$query = "UPDATE orders SET status = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $new_status, $order_id);

if ($stmt->execute()) {
    setFlashMessage('success', 'Order status updated successfully');
} else {
    setFlashMessage('danger', 'Failed to update order status');
}

closeDBConnection($conn);
redirect('order_detail.php?id=' . $order_id);
?>