<?php
$page_title = "Manage Payments";
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$conn = getDBConnection();

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where = $status_filter ? "WHERE p.payment_status = '$status_filter'" : '';

// Get payments
$query = "SELECT p.*, o.id as order_id, u.username, u.full_name, o.created_at as order_date
          FROM payments p
          JOIN orders o ON p.order_id = o.id
          JOIN users u ON o.user_id = u.id
          $where
          ORDER BY p.created_at DESC";
$payments = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get stats
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM payments")->fetch_assoc()['count'];
$stats['pending'] = $conn->query("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'pending'")->fetch_assoc()['count'];
$stats['verified'] = $conn->query("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'verified'")->fetch_assoc()['count'];
$stats['rejected'] = $conn->query("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'rejected'")->fetch_assoc()['count'];

closeDBConnection($conn);
require_once 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-credit-card"></i> Manage Payments</h2>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6>Total Payments</h6>
                <h2><?php echo $stats['total']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6>Pending Verification</h6>
                <h2><?php echo $stats['pending']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6>Verified</h6>
                <h2><?php echo $stats['verified']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h6>Rejected</h6>
                <h2><?php echo $stats['rejected']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Status Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="btn-group" role="group">
            <a href="payments.php" class="btn btn-outline-primary <?php echo !$status_filter ? 'active' : ''; ?>">
                All Payments
            </a>
            <a href="?status=pending" class="btn btn-outline-warning <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                Pending
            </a>
            <a href="?status=verified" class="btn btn-outline-success <?php echo $status_filter === 'verified' ? 'active' : ''; ?>">
                Verified
            </a>
            <a href="?status=rejected" class="btn btn-outline-danger <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                Rejected
            </a>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No payments found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><strong>#<?php echo $payment['id']; ?></strong></td>
                        <td>
                            <a href="order_detail.php?id=<?php echo $payment['order_id']; ?>">
                                #<?php echo $payment['order_id']; ?>
                            </a>
                        </td>
                        <td>
                            <?php echo $payment['full_name']; ?><br>
                            <small class="text-muted">@<?php echo $payment['username']; ?></small>
                        </td>
                        <td><strong>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></strong></td>
                        <td><?php echo $payment['payment_date'] ? date('d M Y, H:i', strtotime($payment['payment_date'])) : '-'; ?></td>
                        <td>
                            <span class="payment-status payment-<?php echo $payment['payment_status']; ?>">
                                <?php echo strtoupper($payment['payment_status']); ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <button class="btn btn-sm btn-info" onclick="viewPayment(<?php echo htmlspecialchars(json_encode($payment)); ?>)">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if ($payment['payment_status'] === 'pending'): ?>
                            <a href="payment_verify.php?id=<?php echo $payment['id']; ?>" 
                               class="btn btn-sm btn-success" title="Verify">
                                <i class="bi bi-check-circle"></i>
                            </a>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="rejectPayment(<?php echo $payment['id']; ?>)" title="Reject">
                                <i class="bi bi-x-circle"></i>
                            </button>
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

<!-- View Payment Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Payment Information</h6>
                        <p><strong>Payment ID:</strong> <span id="modal_payment_id"></span></p>
                        <p><strong>Order ID:</strong> <span id="modal_order_id"></span></p>
                        <p><strong>Amount:</strong> <span id="modal_amount"></span></p>
                        <p><strong>Method:</strong> <span id="modal_method"></span></p>
                        <p><strong>Status:</strong> <span id="modal_status"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Sender Information</h6>
                        <p><strong>Name:</strong> <span id="modal_sender"></span></p>
                        <p><strong>Account:</strong> <span id="modal_account"></span></p>
                        <p><strong>Date:</strong> <span id="modal_date"></span></p>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Payment Proof</h6>
                    <div id="modal_proof"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="payment_reject.php">
                <input type="hidden" id="reject_id" name="payment_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" name="rejection_reason" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewPayment(payment) {
    document.getElementById('modal_payment_id').textContent = '#' + payment.id;
    document.getElementById('modal_order_id').innerHTML = '<a href="order_detail.php?id=' + payment.order_id + '">#' + payment.order_id + '</a>';
    document.getElementById('modal_amount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(payment.amount);
    document.getElementById('modal_method').textContent = payment.payment_method.replace('_', ' ').toUpperCase();
    document.getElementById('modal_status').innerHTML = '<span class="payment-status payment-' + payment.payment_status + '">' + payment.payment_status.toUpperCase() + '</span>';
    document.getElementById('modal_sender').textContent = payment.account_name || '-';
    document.getElementById('modal_account').textContent = payment.bank_account || '-';
    document.getElementById('modal_date').textContent = payment.payment_date ? new Date(payment.payment_date).toLocaleString('id-ID') : '-';
    
    if (payment.payment_proof) {
        const ext = payment.payment_proof.split('.').pop().toLowerCase();
        if (ext === 'pdf') {
            document.getElementById('modal_proof').innerHTML = '<a href="../assets/uploads/payments/' + payment.payment_proof + '" target="_blank" class="btn btn-primary"><i class="bi bi-file-pdf"></i> View PDF</a>';
        } else {
            document.getElementById('modal_proof').innerHTML = '<img src="../assets/uploads/payments/' + payment.payment_proof + '" class="img-fluid" style="max-height: 400px;">';
        }
    } else {
        document.getElementById('modal_proof').innerHTML = '<p class="text-muted">No proof uploaded</p>';
    }
    
    new bootstrap.Modal(document.getElementById('viewModal')).show();
}

function rejectPayment(id) {
    document.getElementById('reject_id').value = id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>