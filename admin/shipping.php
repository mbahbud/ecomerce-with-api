<?php
$page_title = "Manage Shipping";
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$conn = getDBConnection();

// Get shipments with payment verified
$query = "SELECT s.*, o.id as order_id, o.total_amount, u.username, u.full_name, o.shipping_address
          FROM shipping s
          JOIN orders o ON s.order_id = o.id
          JOIN users u ON o.user_id = u.id
          WHERE o.payment_status = 'verified'
          ORDER BY s.created_at DESC";
$shipments = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
require_once 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-truck"></i> Manage Shipping</h2>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Tracking Number</th>
                        <th>Courier</th>
                        <th>Status</th>
                        <th>Est. Delivery</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shipments)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No shipments found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($shipments as $ship): ?>
                    <tr>
                        <td>
                            <a href="order_detail.php?id=<?php echo $ship['order_id']; ?>">
                                #<?php echo $ship['order_id']; ?>
                            </a>
                        </td>
                        <td>
                            <?php echo $ship['full_name']; ?><br>
                            <small class="text-muted">@<?php echo $ship['username']; ?></small>
                        </td>
                        <td>
                            <?php if ($ship['tracking_number']): ?>
                            <strong><?php echo $ship['tracking_number']; ?></strong>
                            <?php else: ?>
                            <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $ship['courier'] ? strtoupper($ship['courier']) : '-'; ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                $color = 'secondary';
                                switch($ship['shipping_status']) {
                                    case 'processing': $color = 'warning'; break;
                                    case 'picked_up': $color = 'info'; break;
                                    case 'in_transit': $color = 'primary'; break;
                                    case 'out_for_delivery': $color = 'success'; break;
                                    case 'delivered': $color = 'dark'; break;
                                }
                                echo $color;
                            ?>">
                                <?php echo ucwords(str_replace('_', ' ', $ship['shipping_status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $ship['estimated_delivery'] ? date('d M Y', strtotime($ship['estimated_delivery'])) : '-'; ?>
                        </td>
                        <td class="table-actions">
                            <button class="btn btn-sm btn-primary" 
                                    onclick="updateShipping(<?php echo htmlspecialchars(json_encode($ship)); ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-info" 
                                    onclick="viewShipping(<?php echo htmlspecialchars(json_encode($ship)); ?>)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update Shipping Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="shipping_update.php">
                <input type="hidden" id="update_id" name="shipping_id">
                <input type="hidden" id="update_order_id" name="order_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Update Shipping</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tracking Number *</label>
                            <input type="text" class="form-control" id="update_tracking" name="tracking_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Courier *</label>
                            <select class="form-select" id="update_courier" name="courier" required>
                                <option value="">Select Courier</option>
                                <option value="jne">JNE</option>
                                <option value="tiki">TIKI</option>
                                <option value="jnt">J&T</option>
                                <option value="sicepat">SiCepat</option>
                                <option value="anteraja">AnterAja</option>
                                <option value="pos">POS Indonesia</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Shipping Status *</label>
                            <select class="form-select" id="update_status" name="shipping_status" required>
                                <option value="processing">Processing</option>
                                <option value="picked_up">Picked Up</option>
                                <option value="in_transit">In Transit</option>
                                <option value="out_for_delivery">Out for Delivery</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estimated Delivery</label>
                            <input type="date" class="form-control" id="update_estimate" name="estimated_delivery">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes / Description</label>
                        <textarea class="form-control" id="update_notes" name="notes" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Add Tracking History</label>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <input type="text" class="form-control" name="track_location" placeholder="Location (optional)">
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="text" class="form-control" name="track_description" placeholder="Description">
                            </div>
                        </div>
                        <small class="text-muted">Add tracking update for customer visibility</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Shipping</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Shipping Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Shipping Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Shipping Information</h6>
                        <p><strong>Order ID:</strong> <span id="view_order"></span></p>
                        <p><strong>Tracking:</strong> <span id="view_tracking"></span></p>
                        <p><strong>Courier:</strong> <span id="view_courier"></span></p>
                        <p><strong>Status:</strong> <span id="view_status"></span></p>
                        <p><strong>Estimated:</strong> <span id="view_estimate"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p><strong>Name:</strong> <span id="view_customer"></span></p>
                        <p><strong>Address:</strong> <span id="view_address"></span></p>
                    </div>
                </div>
                <div id="view_notes_section"></div>
            </div>
        </div>
    </div>
</div>

<script>
function updateShipping(ship) {
    document.getElementById('update_id').value = ship.id;
    document.getElementById('update_order_id').value = ship.order_id;
    document.getElementById('update_tracking').value = ship.tracking_number || '';
    document.getElementById('update_courier').value = ship.courier || '';
    document.getElementById('update_status').value = ship.shipping_status;
    document.getElementById('update_estimate').value = ship.estimated_delivery || '';
    document.getElementById('update_notes').value = ship.notes || '';
    
    new bootstrap.Modal(document.getElementById('updateModal')).show();
}

function viewShipping(ship) {
    document.getElementById('view_order').innerHTML = '<a href="order_detail.php?id=' + ship.order_id + '">#' + ship.order_id + '</a>';
    document.getElementById('view_tracking').textContent = ship.tracking_number || 'Not set';
    document.getElementById('view_courier').textContent = ship.courier ? ship.courier.toUpperCase() : '-';
    document.getElementById('view_status').textContent = ship.shipping_status.replace('_', ' ').toUpperCase();
    document.getElementById('view_estimate').textContent = ship.estimated_delivery || 'Not set';
    document.getElementById('view_customer').textContent = ship.full_name;
    document.getElementById('view_address').innerHTML = ship.shipping_address.replace(/\n/g, '<br>');
    
    if (ship.notes) {
        document.getElementById('view_notes_section').innerHTML = '<hr><h6>Notes</h6><p>' + ship.notes + '</p>';
    } else {
        document.getElementById('view_notes_section').innerHTML = '';
    }
    
    new bootstrap.Modal(document.getElementById('viewModal')).show();
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>