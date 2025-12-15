<?php
$page_title = "Shopping Cart";
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get cart items
$query = "SELECT c.*, p.name, p.price, p.stock, p.image 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping = $subtotal > 100000 ? 0 : 15000;
$total = $subtotal + $shipping;

closeDBConnection($conn);
?>

<h2 class="mb-4"><i class="bi bi-cart"></i> Shopping Cart</h2>

<?php if (empty($cart_items)): ?>
<div class="alert alert-info text-center py-5">
    <i class="bi bi-cart-x display-1 d-block mb-3"></i>
    <h4>Your cart is empty</h4>
    <p class="mb-4">Start adding some products to your cart!</p>
    <a href="products.php" class="btn btn-primary">Browse Products</a>
</div>
<?php else: ?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <img src="assets/images/<?php echo $item['image'] ?: 'placeholder.jpg'; ?>" 
                                 class="img-fluid rounded" alt="<?php echo $item['name']; ?>">
                        </div>
                        <div class="col-md-4">
                            <h5><?php echo $item['name']; ?></h5>
                            <p class="text-muted mb-0">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></p>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group quantity-input">
                                <button class="btn btn-outline-secondary btn-quantity" type="button" 
                                        onclick="updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" class="form-control text-center" 
                                       value="<?php echo $item['quantity']; ?>" readonly>
                                <button class="btn btn-outline-secondary btn-quantity" type="button"
                                        onclick="updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                        <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>>
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <?php if ($item['quantity'] >= $item['stock']): ?>
                            <small class="text-warning">Max stock reached</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2 text-end">
                            <strong class="d-block mb-2">
                                Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>
                            </strong>
                        </div>
                        <div class="col-md-1 text-end">
                            <button class="btn btn-danger btn-sm" 
                                    onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <strong>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping:</span>
                    <strong>
                        <?php if ($shipping == 0): ?>
                        <span class="text-success">FREE</span>
                        <?php else: ?>
                        Rp <?php echo number_format($shipping, 0, ',', '.'); ?>
                        <?php endif; ?>
                    </strong>
                </div>
                <?php if ($subtotal < 100000 && $shipping > 0): ?>
                <small class="text-muted d-block mb-3">
                    Add Rp <?php echo number_format(100000 - $subtotal, 0, ',', '.'); ?> more for free shipping!
                </small>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <h4 class="text-primary mb-0">Rp <?php echo number_format($total, 0, ',', '.'); ?></h4>
                </div>
                
                <a href="checkout.php" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-credit-card"></i> Proceed to Checkout
                </a>
                <a href="products.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-shield-check"></i> Safe & Secure Payment</h6>
                <p class="card-text small text-muted mb-0">
                    Your payment information is processed securely. We do not store credit card details.
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>