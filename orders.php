<?php
$page_title = "My Orders";
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get user orders
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o 
          WHERE o.user_id = ? 
          ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>

<h2 class="mb-4"><i class="bi bi-bag-check"></i> My Orders</h2>

<?php if (empty($orders)): ?>
<div class="alert alert-info text-center py-5">
    <i class="bi bi-inbox display-1 d-block mb-3"></i>
    <h4>No orders yet</h4>
    <p class="mb-4">Start shopping to see your orders here!</p>
    <a href="products.php" class="btn btn-primary">Browse Products</a>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($orders as $order): ?>
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Order #<?php echo $order['id']; ?></h5>
                    <small class="text-muted">
                        <i class="bi bi-calendar"></i> <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?>
                    </small>
                </div>
                <div>
                    <span class="order-status status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6><i class="bi bi-box"></i> Order Details</h6>
                        <p class="mb-1"><strong>Items:</strong> <?php echo $order['item_count']; ?> item(s)</p>
                        <p class="mb-1"><strong>Total:</strong> 
                            <span class="text-primary fw-bold">
                                Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                            </span>
                        </p>
                        <p class="mb-1"><strong>Payment:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                    </div>
                    <div class="col-md-5">
                        <h6><i class="bi bi-geo-alt"></i> Shipping Address</h6>
                        <p class="mb-0"><?php echo nl2br($order['shipping_address']); ?></p>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary mb-2">
                            <i class="bi bi-eye"></i> View Details
                        </a>
                        <?php if ($order['status'] === 'pending'): ?>
                        <button class="btn btn-outline-danger" onclick="if(confirm('Cancel this order?')) window.location='cancel_order.php?id=<?php echo $order['id']; ?>'">
                            <i class="bi bi-x-circle"></i> Cancel Order
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Status Timeline -->
                <div class="mt-3">
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
                    <div class="d-flex justify-content-between mt-2">
                        <small>Pending</small>
                        <small>Processing</small>
                        <small>Shipped</small>
                        <small>Delivered</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>