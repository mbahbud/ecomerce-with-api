<?php
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

$shipping_id = intval($_POST['shipping_id']);
$order_id = intval($_POST['order_id']);
$tracking_number = sanitize($_POST['tracking_number']);
$courier = sanitize($_POST['courier']);
$shipping_status = sanitize($_POST['shipping_status']);
$estimated_delivery = sanitize($_POST['estimated_delivery']);
$notes = sanitize($_POST['notes']);

// Tracking history
$track_location = sanitize($_POST['track_location']);
$track_description = sanitize($_POST['track_description']);

$conn = getDBConnection();
$conn->begin_transaction();

try {
    // Update shipping
    $update = "UPDATE shipping SET 
               tracking_number = ?,
               courier = ?,
               shipping_status = ?,
               estimated_delivery = ?,
               notes = ?
               WHERE id = ?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("sssssi", $tracking_number, $courier, $shipping_status, $estimated_delivery, $notes, $shipping_id);
    $stmt->execute();
    
    // Update order status based on shipping status
    if ($shipping_status === 'delivered') {
        $conn->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id");
        $conn->query("UPDATE shipping SET actual_delivery = NOW() WHERE id = $shipping_id");
    } elseif ($shipping_status === 'in_transit' || $shipping_status === 'out_for_delivery') {
        $conn->query("UPDATE orders SET status = 'shipped' WHERE id = $order_id");
    } elseif ($shipping_status === 'processing' || $shipping_status === 'picked_up') {
        $conn->query("UPDATE orders SET status = 'processing' WHERE id = $order_id");
    }
    
    // Add tracking history if provided
    if (!empty($track_description)) {
        $history = "INSERT INTO tracking_history (shipping_id, status, location, description) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($history);
        $status_display = ucwords(str_replace('_', ' ', $shipping_status));
        $stmt->bind_param("isss", $shipping_id, $status_display, $track_location, $track_description);
        $stmt->execute();
    }
    
    $conn->commit();
    setFlashMessage('success', 'Shipping information updated successfully!');
    
} catch (Exception $e) {
    $conn->rollback();
    setFlashMessage('danger', 'Failed to update shipping: ' . $e->getMessage());
}

closeDBConnection($conn);
redirect('shipping.php');
?>