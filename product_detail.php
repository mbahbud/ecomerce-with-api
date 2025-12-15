<?php
require_once 'config/database.php';
require_once 'includes/session.php';

if (!isset($_GET['id'])) {
    redirect('products.php');
}

$product_id = intval($_GET['id']);
$conn = getDBConnection();

// Get product details
$query = "SELECT p.*, c.name as category_name, c.id as category_id FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('products.php');
}

$product = $result->fetch_assoc();

// Get related products
$related_query = "SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4";
$stmt = $conn->prepare($related_query);
$stmt->bind_param("ii", $product['category_id'], $product_id);
$stmt->execute();
$related_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);

$page_title = $product['name'];
require_once 'includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="products.php">Products</a></li>
        <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category_id']; ?>">
            <?php echo $product['category_name']; ?>
        </a></li>
        <li class="breadcrumb-item active"><?php echo $product['name']; ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <img src="assets/images/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                 class="card-img-top product-detail-image p-4" alt="<?php echo $product['name']; ?>">
        </div>
    </div>
    
    <div class="col-md-7">
        <h1 class="mb-3"><?php echo $product['name']; ?></h1>
        <p class="lead mb-4"><?php echo $product['description']; ?></p>
        
        <div class="mb-3">
            <span class="badge bg-secondary fs-6"><?php echo $product['category_name']; ?></span>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="text-primary mb-0">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></h3>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success fs-6">
                            <i class="bi bi-check-circle"></i> In Stock (<?php echo $product['stock']; ?> available)
                        </span>
                        <?php else: ?>
                        <span class="badge bg-danger fs-6">
                            <i class="bi bi-x-circle"></i> Out of Stock
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($product['stock'] > 0): ?>
        <div class="d-grid gap-2">
            <?php if (isLoggedIn()): ?>
            <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary btn-lg">
                <i class="bi bi-cart-plus"></i> Add to Cart
            </button>
            <a href="products.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Continue Shopping
            </a>
            <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-lg">Login to Purchase</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <h5>Product Information</h5>
            <table class="table table-sm">
                <tr>
                    <th width="30%">Category:</th>
                    <td><?php echo $product['category_name']; ?></td>
                </tr>
                <tr>
                    <th>Price:</th>
                    <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <th>Availability:</th>
                    <td><?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?></td>
                </tr>
                <tr>
                    <th>Added:</th>
                    <td><?php echo date('d M Y', strtotime($product['created_at'])); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Related Products -->
<?php if (!empty($related_products)): ?>
<section class="mt-5">
    <h3 class="mb-4">Related Products</h3>
    <div class="row g-4">
        <?php foreach ($related_products as $related): ?>
        <div class="col-md-3">
            <div class="card product-card h-100">
                <img src="assets/images/<?php echo $related['image'] ?: 'placeholder.jpg'; ?>" 
                     class="card-img-top product-image" alt="<?php echo $related['name']; ?>">
                <div class="card-body">
                    <h6 class="card-title"><?php echo $related['name']; ?></h6>
                    <p class="price mb-3">Rp <?php echo number_format($related['price'], 0, ',', '.'); ?></p>
                    <a href="product_detail.php?id=<?php echo $related['id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                        View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>