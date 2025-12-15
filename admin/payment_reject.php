<?php
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

$payment_id = intval($_POST['payment_id']);
$rejection_reason = sanitize($_POST['rejection_reason']);
$conn = getDBConnection();

// Get payment info
$payment = $conn->query("SELECT * FROM payments WHERE id = $payment_id")->fetch_assoc();

if (!$payment) {
    setFlashMessage('danger', 'Payment not found');
    redirect('payments.php');
}

$conn->begin_transaction();

try {
    // Update payment status
    $update_payment = "UPDATE payments SET 
                      payment_status = 'rejected',
                      rejection_reason = ?
                      WHERE id = ?";
    $stmt = $conn->prepare($update_payment);
    $stmt->bind_param("si", $rejection_reason, $payment_id);
    $stmt->execute();
    
    // Update order payment status
    $update_order = "UPDATE orders SET payment_status = 'rejected' WHERE id = ?";
    $stmt = $conn->prepare($update_order);
    $stmt->bind_param("i", $payment['order_id']);
    $stmt->execute();
    
    $conn->commit();
    setFlashMessage('success', 'Payment rejected');
    
} catch (Exception $e) {
    $conn->rollback();
    setFlashMessage('danger', 'Failed to reject payment');
}

closeDBConnection($conn);
redirect('payments.php');
?>