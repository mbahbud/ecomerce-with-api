<?php
$page_title = "Admin Dashboard";
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$conn = getDBConnection();

// Get statistics
$stats = [];
$stats['total_products'] = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$stats['total_orders'] = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'")->fetch_assoc()['total'];
$stats['total_revenue'] = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'")->fetch_assoc()['total'] ?? 0;

// Recent orders
$recent_orders = $conn->query("SELECT o.*, u.username FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Low stock products
$low_stock = $conn->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

require_once 'includes/admin_header.php';
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Products</h6>
                        <h2 class="mb-0"><?php echo $stats['total_products']; ?></h2>
                    </div>
                    <i class="bi bi-box-seam display-4"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Orders</h6>
                        <h2 class="mb-0"><?php echo $stats['total_orders']; ?></h2>
                    </div>
                    <i class="bi bi-cart-check display-4"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Users</h6>
                        <h2 class="mb-0"><?php echo $stats['total_users']; ?></h2>
                    </div>
                    <i class="bi bi-people display-4"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Revenue</h6>
                        <h2 class="mb-0">Rp <?php echo number_format($stats['total_revenue'] ); ?></h2>
                    </div>
                    <i class="bi bi-currency-dollar display-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['username']; ?></td>
                                <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                <td><span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span></td>
                                <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="orders.php" class="btn btn-primary mt-2">View All Orders</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Low Stock Alert</h5>
            </div>
            <div class="card-body">
                <?php if (empty($low_stock)): ?>
                <p class="text-muted">All products have sufficient stock</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($low_stock as $product): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <strong><?php echo $product['name']; ?></strong>
                            <br>
                            <small class="text-muted">Stock: <?php echo $product['stock']; ?></small>
                        </div>
                        <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="products.php" class="btn btn-warning mt-3 w-100">Manage Products</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>