<?php
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) {
    redirect('payments.php');
}

$payment_id = intval($_GET['id']);
$admin_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get payment info
$payment = $conn->query("SELECT * FROM payments WHERE id = $payment_id")->fetch_assoc();

if (!$payment || $payment['payment_status'] !== 'pending') {
    setFlashMessage('danger', 'Payment not found or already processed');
    redirect('payments.php');
}

$conn->begin_transaction();

try {
    // Update payment status
    $update_payment = "UPDATE payments SET 
                      payment_status = 'verified',
                      verified_by = ?,
                      verified_at = NOW()
                      WHERE id = ?";
    $stmt = $conn->prepare($update_payment);
    $stmt->bind_param("ii", $admin_id, $payment_id);
    $stmt->execute();
    
    // Update order payment status
    $update_order = "UPDATE orders SET payment_status = 'verified' WHERE id = ?";
    $stmt = $conn->prepare($update_order);
    $stmt->bind_param("i", $payment['order_id']);
    $stmt->execute();
    
    $conn->commit();
    setFlashMessage('success', 'Payment verified successfully!');
    
} catch (Exception $e) {
    $conn->rollback();
    setFlashMessage('danger', 'Failed to verify payment');
}

closeDBConnection($conn);
redirect('payments.php');
?>