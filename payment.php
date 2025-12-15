<?php
$page_title = "Payment";
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

// Get order details
$query = "SELECT o.*, p.payment_status, p.payment_proof, p.payment_date, p.rejection_reason
          FROM orders o 
          LEFT JOIN payments p ON o.id = p.order_id
          WHERE o.id = ? AND o.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    redirect('orders.php');
}

// Get bank accounts
$banks = $conn->query("SELECT * FROM bank_accounts WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="orders.php">My Orders</a></li>
        <li class="breadcrumb-item active">Payment</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Information</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Order #<?php echo $order['id']; ?></strong> - 
                    Total: <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong>
                </div>

                <?php if ($order['payment_status'] === 'unpaid'): ?>
                <!-- Upload Bukti Transfer -->
                <h6 class="mb-3">Transfer ke salah satu rekening berikut:</h6>
                <div class="row mb-4">
                    <?php foreach ($banks as $bank): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h5 class="text-primary"><?php echo $bank['bank_name']; ?></h5>
                                <p class="mb-1"><strong><?php echo $bank['account_number']; ?></strong></p>
                                <small class="text-muted"><?php echo $bank['account_name']; ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr>

                <h6 class="mb-3">Upload Bukti Transfer</h6>
                <form method="POST" action="payment_confirm.php" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Pengirim *</label>
                        <input type="text" class="form-control" name="account_name" required>
                        <small class="text-muted">Nama sesuai rekening pengirim</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor Rekening Pengirim *</label>
                        <input type="text" class="form-control" name="bank_account" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Transfer *</label>
                        <input type="datetime-local" class="form-control" name="payment_date" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bukti Transfer (JPG, PNG, PDF) *</label>
                        <input type="file" class="form-control" name="payment_proof" accept="image/*,.pdf" required>
                        <small class="text-muted">Max 2MB</small>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-upload"></i> Submit Payment Proof
                    </button>
                </form>

                <?php elseif ($order['payment_status'] === 'pending'): ?>
                <div class="alert alert-warning text-center py-4">
                    <i class="bi bi-clock-history display-4 d-block mb-3"></i>
                    <h5>Payment Verification in Progress</h5>
                    <p>Your payment proof is being verified by our team.</p>
                    <p class="mb-0"><strong>Uploaded:</strong> <?php echo date('d M Y, H:i', strtotime($order['payment_date'])); ?></p>
                    <?php if ($order['payment_proof']): ?>
                    <a href="assets/uploads/payments/<?php echo $order['payment_proof']; ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-file-earmark"></i> View Uploaded Proof
                    </a>
                    <?php endif; ?>
                </div>

                <?php elseif ($order['payment_status'] === 'verified'): ?>
                <div class="alert alert-success text-center py-4">
                    <i class="bi bi-check-circle display-4 d-block mb-3"></i>
                    <h5>Payment Verified!</h5>
                    <p>Your payment has been verified. Your order is being processed.</p>
                    <a href="order_tracking.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-truck"></i> Track Order
                    </a>
                </div>

                <?php elseif ($order['payment_status'] === 'rejected'): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> <strong>Payment Rejected</strong>
                    <?php if ($order['rejection_reason']): ?>
                    <p class="mb-0"><strong>Reason:</strong> <?php echo $order['rejection_reason']; ?></p>
                    <?php endif; ?>
                    <p class="mb-0 mt-2">Please upload correct payment proof.</p>
                </div>

                <!-- Re-upload Form -->
                <form method="POST" action="payment_confirm.php" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Pengirim *</label>
                        <input type="text" class="form-control" name="account_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor Rekening Pengirim *</label>
                        <input type="text" class="form-control" name="bank_account" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Transfer *</label>
                        <input type="datetime-local" class="form-control" name="payment_date" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bukti Transfer (JPG, PNG, PDF) *</label>
                        <input type="file" class="form-control" name="payment_proof" accept="image/*,.pdf" required>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-upload"></i> Re-submit Payment Proof
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Payment Status</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <span class="payment-status payment-<?php echo $order['payment_status']; ?>">
                        <?php echo strtoupper($order['payment_status']); ?>
                    </span>
                </div>

                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="bi bi-circle-fill text-<?php echo $order['payment_status'] === 'unpaid' ? 'danger' : 'secondary'; ?>"></i> 
                        Awaiting Payment
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-circle-fill text-<?php echo $order['payment_status'] === 'pending' ? 'warning' : 'secondary'; ?>"></i> 
                        Verification
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-circle-fill text-<?php echo $order['payment_status'] === 'verified' ? 'success' : 'secondary'; ?>"></i> 
                        Verified
                    </li>
                </ul>
            </div>
        </div>

        <a href="orders.php" class="btn btn-outline-secondary w-100 mt-3">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>