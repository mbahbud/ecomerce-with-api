<?php
$page_title = "Manage Orders";
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$conn = getDBConnection();

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where = $status_filter ? "WHERE o.status = '$status_filter'" : '';

// Get orders
$query = "SELECT o.*, u.username, u.full_name,
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          $where
          ORDER BY o.created_at DESC";
$orders = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
require_once 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cart-check"></i> Manage Orders</h2>
</div>

<!-- Status Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="btn-group" role="group">
            <a href="orders.php" class="btn btn-outline-primary <?php echo !$status_filter ? 'active' : ''; ?>">
                All Orders
            </a>
            <a href="?status=pending" class="btn btn-outline-warning <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                Pending
            </a>
            <a href="?status=processing" class="btn btn-outline-info <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">
                Processing
            </a>
            <a href="?status=shipped" class="btn btn-outline-primary <?php echo $status_filter === 'shipped' ? 'active' : ''; ?>">
                Shipped
            </a>
            <a href="?status=delivered" class="btn btn-outline-success <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>">
                Delivered
            </a>
            <a href="?status=cancelled" class="btn btn-outline-danger <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                Cancelled
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No orders found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                        <td>
                            <?php echo $order['full_name']; ?><br>
                            <small class="text-muted">@<?php echo $order['username']; ?></small>
                        </td>
                        <td><?php echo $order['item_count']; ?> item(s)</td>
                        <td><strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></td>
                        <td>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                        <td class="table-actions">
                            <a href="order_detail.php?id=<?php echo $order['id']; ?>" 
                               class="btn btn-sm btn-primary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
                            <a href="order_update.php?id=<?php echo $order['id']; ?>" 
                               class="btn btn-sm btn-warning" title="Update Status">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>