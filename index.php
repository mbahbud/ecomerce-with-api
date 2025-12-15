<?php
$page_title = "Home";
require_once 'includes/header.php';

// Get featured products
$conn = getDBConnection();
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.created_at DESC LIMIT 8";
$result = $conn->query($query);
$products = $result->fetch_all(MYSQLI_ASSOC);
closeDBConnection($conn);
?>

<!-- Hero Section -->
<div class="hero-section rounded">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Welcome to E-Commerce Store</h1>
        <p class="lead mb-4">Temukan produk berkualitas dengan harga terbaik</p>
        <a href="products.php" class="btn btn-light btn-lg">Shop Now</a>
    </div>
</div>

<!-- Categories Section -->
<section class="mb-5">
    <h2 class="text-center mb-4">Shop by Category</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card text-center p-4 h-100">
                <i class="bi bi-laptop display-1 text-primary mb-3"></i>
                <h4>Electronics</h4>
                <p class="text-muted">Latest gadgets and technology</p>
                <a href="products.php?category=1" class="btn btn-outline-primary">Browse</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-4 h-100">
                <i class="bi bi-bag display-1 text-success mb-3"></i>
                <h4>Fashion</h4>
                <p class="text-muted">Trendy clothes and accessories</p>
                <a href="products.php?category=2" class="btn btn-outline-success">Browse</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-4 h-100">
                <i class="bi bi-cup-hot display-1 text-warning mb-3"></i>
                <h4>Food & Beverage</h4>
                <p class="text-muted">Fresh and quality products</p>
                <a href="products.php?category=3" class="btn btn-outline-warning">Browse</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section>
    <h2 class="text-center mb-4">Featured Products</h2>
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-md-3">
            <div class="card product-card">
                <?php if ($product['stock'] > 0): ?>
                <span class="badge bg-success badge-stock">In Stock</span>
                <?php else: ?>
                <span class="badge bg-danger badge-stock">Out of Stock</span>
                <?php endif; ?>
                
                <img src="assets/images/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                     class="card-img-top product-image" alt="<?php echo $product['name']; ?>">
                <div class="card-body">
                    <span class="badge bg-secondary mb-2"><?php echo $product['category_name']; ?></span>
                    <h5 class="card-title"><?php echo $product['name']; ?></h5>
                    <p class="card-text text-muted"><?php echo substr($product['description'], 0, 60); ?>...</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i> View Details
                        </a>
                        <?php if ($product['stock'] > 0 && isLoggedIn()): ?>
                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-4">
        <a href="products.php" class="btn btn-primary btn-lg">View All Products</a>
    </div>
</section>

<!-- Features Section -->
<section class="mt-5 py-5 bg-light rounded">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3">
                <i class="bi bi-truck display-4 text-primary mb-3"></i>
                <h5>Free Shipping</h5>
                <p class="text-muted">On orders over Rp 100.000</p>
            </div>
            <div class="col-md-3">
                <i class="bi bi-shield-check display-4 text-success mb-3"></i>
                <h5>Secure Payment</h5>
                <p class="text-muted">100% secure transactions</p>
            </div>
            <div class="col-md-3">
                <i class="bi bi-arrow-repeat display-4 text-warning mb-3"></i>
                <h5>Easy Returns</h5>
                <p class="text-muted">30 days return policy</p>
            </div>
            <div class="col-md-3">
                <i class="bi bi-headset display-4 text-info mb-3"></i>
                <h5>24/7 Support</h5>
                <p class="text-muted">Dedicated support team</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>