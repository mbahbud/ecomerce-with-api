<?php
$page_title = "Checkout";
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get cart items
$query = "SELECT c.*, p.name, p.price, p.stock 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    redirect('cart.php');
}

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal > 100000 ? 0 : 15000;
$total = $subtotal + $shipping;

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize($_POST['shipping_address']);
    $payment_method = sanitize($_POST['payment_method']);
    
    if (empty($shipping_address) || empty($payment_method)) {
        setFlashMessage('danger', 'Please fill all required fields');
    } else {
        // Check stock availability
        $stock_error = false;
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock']) {
                $stock_error = true;
                break;
            }
        }
        
        if ($stock_error) {
            setFlashMessage('danger', 'Some items are out of stock');
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create order
                $order_query = "INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, payment_status) 
                               VALUES (?, ?, 'pending', ?, ?, 'unpaid')";
                $stmt = $conn->prepare($order_query);
                $stmt->bind_param("idss", $user_id, $total, $shipping_address, $payment_method);
                $stmt->execute();
                $order_id = $conn->insert_id;
                
                // Add order items and update stock
                foreach ($cart_items as $item) {
                    // Insert order item
                    $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                  VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($item_query);
                    $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                    $stmt->execute();
                    
                    // Update product stock
                    $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                    $stmt = $conn->prepare($update_stock);
                    $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stmt->execute();
                }
                
                // ⭐ CREATE PAYMENT RECORD
                $payment_query = "INSERT INTO payments (order_id, amount, payment_method, payment_status) 
                                 VALUES (?, ?, ?, 'unpaid')";
                $stmt = $conn->prepare($payment_query);
                $stmt->bind_param("ids", $order_id, $total, $payment_method);
                $stmt->execute();
                
                // ⭐ CREATE SHIPPING RECORD
                $shipping_query = "INSERT INTO shipping (order_id, shipping_status) 
                                  VALUES (?, 'processing')";
                $stmt = $conn->prepare($shipping_query);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                
                // Clear cart
                $clear_cart = "DELETE FROM cart WHERE user_id = ?";
                $stmt = $conn->prepare($clear_cart);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                $conn->commit();
                
                setFlashMessage('success', 'Order placed successfully! Please complete payment.');
                redirect('payment.php?order_id=' . $order_id); // ⭐ REDIRECT KE PAYMENT
                
            } catch (Exception $e) {
                $conn->rollback();
                setFlashMessage('danger', 'Failed to process order. Please try again.');
            }
        }
    }
}

closeDBConnection($conn);
?>

<h2 class="mb-4"><i class="bi bi-credit-card"></i> Checkout</h2>

<form method="POST" action="">
    <div class="row">
        <div class="col-md-8">
            <!-- Shipping Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Shipping Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" value="<?php echo $user['full_name']; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo $user['email']; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" value="<?php echo $user['phone']; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shipping Address *</label>
                        <textarea class="form-control" name="shipping_address" rows="3" required><?php echo $user['address']; ?></textarea>
                        <small class="text-muted">Please provide complete address including city and postal code</small>
                    </div>
                </div>
            </div>
            
            <!-- Payment Method -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Payment Method</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" value="bank_transfer" id="bank" required>
                        <label class="form-check-label" for="bank">
                            <i class="bi bi-bank"></i> Bank Transfer
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" value="credit_card" id="credit">
                        <label class="form-check-label" for="credit">
                            <i class="bi bi-credit-card"></i> Credit/Debit Card
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" value="ewallet" id="ewallet">
                        <label class="form-check-label" for="ewallet">
                            <i class="bi bi-wallet2"></i> E-Wallet (GoPay, OVO, Dana)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" value="cod" id="cod">
                        <label class="form-check-label" for="cod">
                            <i class="bi bi-cash"></i> Cash on Delivery (COD)
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Items (<?php echo count($cart_items); ?>):</h6>
                    <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <small><?php echo $item['name']; ?> x<?php echo $item['quantity']; ?></small>
                        <small>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></small>
                    </div>
                    <?php endforeach; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Shipping:</span>
                        <strong>
                            <?php if ($shipping == 0): ?>
                            <span class="text-success">FREE</span>
                            <?php else: ?>
                            Rp <?php echo number_format($shipping, 0, ',', '.'); ?>
                            <?php endif; ?>
                        </strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <h4 class="text-success mb-0">Rp <?php echo number_format($total, 0, ',', '.'); ?></h4>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-check-circle"></i> Place Order
                    </button>
                    <a href="cart.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once 'includes/footer.php'; ?>