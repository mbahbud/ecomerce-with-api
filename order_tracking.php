<?php
$page_title = "Order Tracking";
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['order_id'])) {
    redirect('orders.php');
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get order with shipping info
$query = "SELECT o.*, s.tracking_number, s.courier, s.shipping_status, s.estimated_delivery, s.actual_delivery, s.notes
          FROM orders o 
          LEFT JOIN shipping s ON o.id = s.order_id
          WHERE o.id = ? AND o.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    redirect('orders.php');
}

// Get tracking history
$history = [];
if ($order['tracking_number']) {
    $shipping_id = $conn->query("SELECT id FROM shipping WHERE order_id = $order_id")->fetch_assoc()['id'];
    $history_query = "SELECT * FROM tracking_history WHERE shipping_id = $shipping_id ORDER BY created_at DESC";
    $history = $conn->query($history_query)->fetch_all(MYSQLI_ASSOC);
}

closeDBConnection($conn);
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="orders.php">My Orders</a></li>
        <li class="breadcrumb-item active">Tracking</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-truck"></i> Order Tracking - #<?php echo $order['id']; ?></h5>
            </div>
            <div class="card-body">
                <?php if ($order['tracking_number']): ?>
                <!-- Tracking Info -->
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Tracking Number:</strong></p>
                            <h5 class="text-primary"><?php echo $order['tracking_number']; ?></h5>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Courier:</strong></p>
                            <h5><?php echo strtoupper($order['courier']); ?></h5>
                        </div>
                    </div>
                </div>

                <!-- Status Progress -->
                <div class="mb-4">
                    <h6 class="mb-3">Shipping Status</h6>
                    <div class="progress" style="height: 30px;">
                        <?php
                        $progress = 0;
                        switch($order['shipping_status']) {
                            case 'processing': $progress = 20; break;
                            case 'picked_up': $progress = 40; break;
                            case 'in_transit': $progress = 60; break;
                            case 'out_for_delivery': $progress = 80; break;
                            case 'delivered': $progress = 100; break;
                        }
                        ?>
                        <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%">
                            <?php echo ucwords(str_replace('_', ' ', $order['shipping_status'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Estimated Delivery -->
                <?php if ($order['estimated_delivery']): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-calendar"></i> 
                    <strong>Estimated Delivery:</strong> 
                    <?php echo date('d F Y', strtotime($order['estimated_delivery'])); ?>
                </div>
                <?php endif; ?>

                <?php if ($order['actual_delivery']): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> 
                    <strong>Delivered on:</strong> 
                    <?php echo date('d F Y, H:i', strtotime($order['actual_delivery'])); ?>
                </div>
                <?php endif; ?>

                <!-- Tracking Timeline -->
                <h6 class="mb-3 mt-4">Tracking History</h6>
                <?php if (!empty($history)): ?>
                <div class="tracking-timeline">
                    <?php foreach ($history as $track): ?>
                    <div class="tracking-item <?php echo $track === $history[0] ? 'active' : 'completed'; ?>">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1"><?php echo $track['status']; ?></h6>
                                <?php if ($track['location']): ?>
                                <p class="text-muted mb-1">
                                    <i class="bi bi-geo-alt"></i> <?php echo $track['location']; ?>
                                </p>
                                <?php endif; ?>
                                <?php if ($track['description']): ?>
                                <p class="mb-0"><?php echo $track['description']; ?></p>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?php echo date('d M Y, H:i', strtotime($track['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-secondary">
                    <i class="bi bi-info-circle"></i> No tracking history yet. Please check back later.
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- No Tracking Yet -->
                <div class="alert alert-warning text-center py-5">
                    <i class="bi bi-clock-history display-4 d-block mb-3"></i>
                    <h5>Tracking Information Not Available</h5>
                    <p class="mb-0">Your order is being prepared. Tracking number will be available once shipped.</p>
                </div>
                <?php endif; ?>

                <?php if ($order['notes']): ?>
                <div class="alert alert-info mt-3">
                    <strong>Notes:</strong> <?php echo nl2br($order['notes']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Order Summary -->
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-box"></i> Order Summary</h6>
            </div>
            <div class="card-body">
                <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                <p><strong>Order Date:</strong> <?php echo date('d M Y', strtotime($order['created_at'])); ?></p>
                <p><strong>Total:</strong> Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                <p><strong>Payment:</strong> 
                    <span class="badge bg-<?php echo $order['payment_status'] === 'verified' ? 'success' : 'warning'; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </p>
                <p class="mb-0"><strong>Order Status:</strong> 
                    <span class="badge bg-primary"><?php echo ucfirst($order['status']); ?></span>
                </p>
            </div>
        </div>

        <!-- Shipping Address -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-geo-alt"></i> Shipping Address</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br($order['shipping_address']); ?></p>
            </div>
        </div>

        <a href="orders.php" class="btn btn-outline-secondary w-100 mb-2">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary w-100">
            <i class="bi bi-receipt"></i> View Order Details
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>