<?php
$page_title = "Order Detail";
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$order_id = intval($_GET['id']);
$conn = getDBConnection();

// Get order with customer info
$query = "SELECT o.*, u.username, u.full_name, u.email, u.phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
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
require_once 'includes/admin_header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="orders.php">Orders</a></li>
        <li class="breadcrumb-item active">Order #<?php echo $order['id']; ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Order #<?php echo $order['id']; ?></h5>
                <span class="order-status status-<?php echo $order['status']; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-person"></i> Customer Information</h6>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Name:</strong> <?php echo $order['full_name']; ?></p>
                        <p class="mb-1"><strong>Username:</strong> @<?php echo $order['username']; ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo $order['email']; ?></p>
                        <p class="mb-0"><strong>Phone:</strong> <?php echo $order['phone'] ?: 'N/A'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Order Date:</strong> <?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?></p>
                        <p class="mb-1"><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                        <p class="mb-0"><strong>Total Amount:</strong> 
                            <span class="text-primary fs-5">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                        </p>
                    </div>
                </div>
                
                <h6 class="mb-3"><i class="bi bi-geo-alt"></i> Shipping Address</h6>
                <p class="mb-4"><?php echo nl2br($order['shipping_address']); ?></p>
                
                <h6 class="mb-3"><i class="bi bi-box-seam"></i> Order Items</h6>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../assets/images/<?php echo $item['image'] ?: 'placeholder.jpg'; ?>" 
                                             width="50" class="rounded me-2">
                                        <?php echo $item['name']; ?>
                                    </div>
                                </td>
                                <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><strong>Rp <?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong class="text-primary">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
        <div class="card mb-3">
            <div class="card-header bg-warning">
                <h6 class="mb-0"><i class="bi bi-arrow-repeat"></i> Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="order_update_status.php">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">New Status:</label>
                        <select class="form-select" name="status" required>
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Update Status</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Order Timeline</h6>
            </div>
            <div class="card-body">
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
        
        <a href="orders.php" class="btn btn-outline-primary w-100 mt-3">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>