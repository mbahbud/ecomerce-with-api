<?php
$page_title = "Order Detail";
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get order details
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    redirect('orders.php');
}

// Get order items
$items_query = "SELECT oi.*, p.name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="orders.php">My Orders</a></li>
        <li class="breadcrumb-item active">Order #<?php echo $order['id']; ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Order #<?php echo $order['id']; ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Order Date:</strong></p>
                        <p><?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Status:</strong></p>
                        <span class="order-status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Payment Method:</strong></p>
                        <p><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Total Amount:</strong></p>
                        <h4 class="text-primary">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></h4>
                    </div>
                </div>
                
                <!-- ⭐ PAYMENT STATUS -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <p class="mb-1"><strong>Payment Status:</strong></p>
                        <span class="payment-status payment-<?php echo $order['payment_status']; ?>">
                            <?php echo strtoupper($order['payment_status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <p class="mb-1"><strong><i class="bi bi-geo-alt"></i> Shipping Address:</strong></p>
                    <p><?php echo nl2br($order['shipping_address']); ?></p>
                </div>
                
                <hr>
                
                <h6 class="mb-3"><i class="bi bi-box-seam"></i> Order Items</h6>
                <?php foreach ($items as $item): ?>
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <img src="assets/images/<?php echo $item['image'] ?: 'placeholder.jpg'; ?>" 
                         class="rounded" width="80" alt="<?php echo $item['name']; ?>">
                    <div class="ms-3 flex-grow-1">
                        <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                        <p class="text-muted mb-0">
                            Quantity: <?php echo $item['quantity']; ?> x 
                            Rp <?php echo number_format($item['price'], 0, ',', '.'); ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <strong>Rp <?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Order Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="progress" style="height: 25px;">
                        <?php
                        $status_percent = 0;
                        switch($order['status']) {
                            case 'pending': $status_percent = 25; break;
                            case 'processing': $status_percent = 50; break;
                            case 'shipped': $status_percent = 75; break;
                            case 'delivered': $status_percent = 100; break;
                            case 'cancelled': $status_percent = 0; break;
                        }
                        ?>
                        <div class="progress-bar <?php echo $order['status'] === 'cancelled' ? 'bg-danger' : 'bg-success'; ?>" 
                             role="progressbar" style="width: <?php echo $status_percent; ?>%">
                            <?php echo $status_percent; ?>%
                        </div>
                    </div>
                </div>
                
                <ul class="list-unstyled mb-0">
                    <li class="mb-2 <?php echo $order['status'] === 'pending' ? 'text-warning fw-bold' : ''; ?>">
                        <i class="bi bi-circle-fill"></i> Pending
                    </li>
                    <li class="mb-2 <?php echo $order['status'] === 'processing' ? 'text-primary fw-bold' : ''; ?>">
                        <i class="bi bi-circle-fill"></i> Processing
                    </li>
                    <li class="mb-2 <?php echo $order['status'] === 'shipped' ? 'text-info fw-bold' : ''; ?>">
                        <i class="bi bi-circle-fill"></i> Shipped
                    </li>
                    <li class="mb-2 <?php echo $order['status'] === 'delivered' ? 'text-success fw-bold' : ''; ?>">
                        <i class="bi bi-circle-fill"></i> Delivered
                    </li>
                    <?php if ($order['status'] === 'cancelled'): ?>
                    <li class="text-danger fw-bold">
                        <i class="bi bi-x-circle-fill"></i> Cancelled
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- ⭐ PAYMENT & TRACKING BUTTONS -->
        <div class="d-grid gap-2 mb-3">
            <?php if ($order['payment_status'] === 'unpaid'): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>Payment Required</strong>
                <p class="mb-0 small">Please complete payment to process your order.</p>
            </div>
            <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success">
                <i class="bi bi-credit-card"></i> Complete Payment
            </a>

            <?php elseif ($order['payment_status'] === 'pending'): ?>
            <div class="alert alert-warning">
                <i class="bi bi-clock"></i> 
                <strong>Payment Verification in Progress</strong>
                <p class="mb-0 small">Your payment proof is being verified by our team.</p>
            </div>
            <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline-warning">
                <i class="bi bi-eye"></i> View Payment Status
            </a>

            <?php elseif ($order['payment_status'] === 'rejected'): ?>
            <div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> 
                <strong>Payment Rejected</strong>
                <p class="mb-0 small">Please re-submit correct payment proof.</p>
            </div>
            <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-danger">
                <i class="bi bi-upload"></i> Re-submit Payment Proof
            </a>

            <?php elseif ($order['payment_status'] === 'verified'): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> 
                <strong>Payment Verified!</strong>
                <p class="mb-0 small">Your order is being processed.</p>
            </div>
            <a href="order_tracking.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">
                <i class="bi bi-truck"></i> Track Order
            </a>
            <?php endif; ?>
        </div>
        
        <div class="d-grid gap-2">
            <a href="orders.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
            <?php if ($order['status'] === 'pending' && $order['payment_status'] === 'unpaid'): ?>
            <button class="btn btn-danger" onclick="if(confirm('Cancel this order?')) window.location='cancel_order.php?id=<?php echo $order['id']; ?>'">
                <i class="bi bi-x-circle"></i> Cancel Order
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>