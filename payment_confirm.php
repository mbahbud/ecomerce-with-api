<?php
require_once 'config/database.php';
require_once 'includes/session.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orders.php');
}

$order_id = intval($_POST['order_id']);
$user_id = $_SESSION['user_id'];
$account_name = sanitize($_POST['account_name']);
$bank_account = sanitize($_POST['bank_account']);
$payment_date = sanitize($_POST['payment_date']);

$conn = getDBConnection();

// Verify order belongs to user
$verify = $conn->query("SELECT id, total_amount, payment_method FROM orders WHERE id = $order_id AND user_id = $user_id");
if ($verify->num_rows === 0) {
    setFlashMessage('danger', 'Order not found');
    redirect('orders.php');
}

$order = $verify->fetch_assoc();

// Validate file upload
$upload_error = false;
$filename = '';

if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $file_type = $_FILES['payment_proof']['type'];
    $file_size = $_FILES['payment_proof']['size'];
    
    // Check file type
    if (!in_array($file_type, $allowed_types)) {
        $upload_error = true;
        setFlashMessage('danger', 'Invalid file type. Only JPG, PNG, PDF allowed');
    }
    
    // Check file size (max 2MB)
    if ($file_size > 2 * 1024 * 1024) {
        $upload_error = true;
        setFlashMessage('danger', 'File too large. Maximum 2MB');
    }
    
    if (!$upload_error) {
        // Create upload directory if not exists
        $upload_dir = 'assets/uploads/payments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $ext = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . $order_id . '_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
            $upload_error = true;
            setFlashMessage('danger', 'Failed to upload file');
        }
    }
} else {
    $upload_error = true;
    setFlashMessage('danger', 'Please select a file to upload');
}

if ($upload_error) {
    closeDBConnection($conn);
    redirect('payment.php?order_id=' . $order_id);
}

// Check if payment record exists
$check_payment = $conn->query("SELECT id FROM payments WHERE order_id = $order_id");

if ($check_payment->num_rows > 0) {
    // Update existing payment
    $update = "UPDATE payments SET 
               payment_status = 'pending',
               payment_proof = ?,
               bank_account = ?,
               account_name = ?,
               payment_date = ?
               WHERE order_id = ?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("ssssi", $filename, $bank_account, $account_name, $payment_date, $order_id);
} else {
    // Insert new payment
    $insert = "INSERT INTO payments (order_id, amount, payment_method, payment_status, payment_proof, bank_account, account_name, payment_date) 
               VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert);
    $stmt->bind_param("idsssss", $order_id, $order['total_amount'], $order['payment_method'], $filename, $bank_account, $account_name, $payment_date);
}

if ($stmt->execute()) {
    // Update order payment status
    $conn->query("UPDATE orders SET payment_status = 'pending' WHERE id = $order_id");
    
    setFlashMessage('success', 'Payment proof uploaded successfully! Waiting for verification.');
    redirect('payment.php?order_id=' . $order_id);
} else {
    setFlashMessage('danger', 'Failed to submit payment proof');
    redirect('payment.php?order_id=' . $order_id);
}

closeDBConnection($conn);
?>