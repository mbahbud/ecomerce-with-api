<?php
$page_title = "Manage Users";
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$conn = getDBConnection();

// Get users with order stats
$query = "SELECT u.*, 
          COUNT(DISTINCT o.id) as order_count,
          COALESCE(SUM(o.total_amount), 0) as total_spent
          FROM users u 
          LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
          GROUP BY u.id 
          ORDER BY u.created_at DESC";
$users = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
require_once 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Manage Users</h2>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6>Total Users</h6>
                <h2><?php echo count($users); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6>Admin Users</h6>
                <h2><?php echo count(array_filter($users, function($u) { return $u['role'] === 'admin'; })); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6>Customer Users</h6>
                <h2><?php echo count(array_filter($users, function($u) { return $u['role'] === 'customer'; })); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><strong>@<?php echo $user['username']; ?></strong></td>
                        <td><?php echo $user['full_name']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo $user['order_count']; ?></td>
                        <td>Rp <?php echo number_format($user['total_spent'], 0, ',', '.'); ?></td>
                        <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                        <td class="table-actions">
                            <button class="btn btn-sm btn-info" 
                                    onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Detail Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Account Information</h6>
                        <p><strong>Username:</strong> <span id="modal_username"></span></p>
                        <p><strong>Full Name:</strong> <span id="modal_fullname"></span></p>
                        <p><strong>Email:</strong> <span id="modal_email"></span></p>
                        <p><strong>Phone:</strong> <span id="modal_phone"></span></p>
                        <p><strong>Role:</strong> <span id="modal_role"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Statistics</h6>
                        <p><strong>Total Orders:</strong> <span id="modal_orders"></span></p>
                        <p><strong>Total Spent:</strong> <span id="modal_spent"></span></p>
                        <p><strong>Member Since:</strong> <span id="modal_joined"></span></p>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Address</h6>
                    <p id="modal_address"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewUser(user) {
    document.getElementById('modal_username').textContent = '@' + user.username;
    document.getElementById('modal_fullname').textContent = user.full_name;
    document.getElementById('modal_email').textContent = user.email;
    document.getElementById('modal_phone').textContent = user.phone || 'N/A';
    document.getElementById('modal_role').innerHTML = '<span class="badge bg-' + 
        (user.role === 'admin' ? 'danger' : 'primary') + '">' + 
        user.role.charAt(0).toUpperCase() + user.role.slice(1) + '</span>';
    document.getElementById('modal_orders').textContent = user.order_count;
    document.getElementById('modal_spent').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(user.total_spent);
    document.getElementById('modal_joined').textContent = new Date(user.created_at).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    document.getElementById('modal_address').textContent = user.address || 'No address provided';
    
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>