<?php
$page_title = "Products";
require_once 'includes/header.php';

$conn = getDBConnection();

// Get categories
$cat_query = "SELECT * FROM categories ORDER BY name";
$categories = $conn->query($cat_query)->fetch_all(MYSQLI_ASSOC);

// Filter and search
$where = [];
$params = [];
$types = '';

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where[] = "p.category_id = ?";
    $params[] = $_GET['category'];
    $types .= 'i';
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search = '%' . $_GET['search'] . '%';
    $params[] = $search;
    $params[] = $search;
    $types .= 'ss';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get products
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          $where_clause
          ORDER BY p.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$products = $result->fetch_all(MYSQLI_ASSOC);
closeDBConnection($conn);
?>

<div class="row">
    <!-- Sidebar Filter -->
<div class="col-md-3">

    <!-- ⭐ LIVE SEARCH (DI LUAR FORM) -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-search"></i> Live Search
            </h5>
        </div>
        <div class="card-body">
            <input type="text"
                   class="form-control"
                   id="liveSearchInput"
                   placeholder="Type to search..."
                   autocomplete="off">
            <small class="text-muted">Search as you type</small>
        </div>
    </div>

    <!-- FILTER FORM -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">
                <i class="bi bi-filter"></i> Filter
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Search (Manual)</label>
                    <input type="text" class="form-control" name="search"
                           placeholder="Search products..."
                           value="<?php echo $_GET['search'] ?? ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>"
                                <?= (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?= $cat['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Apply Filter
                </button>

                <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">
                    Reset
                </a>
            </form>
        </div>
    </div>

</div>

    
    <!-- Products Grid -->
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Products</h2>
            <span class="text-muted" id="productCount"><?php echo count($products); ?> products found</span>
        </div>
        
        <!-- ⭐ LOADING INDICATOR -->
        <div id="loadingIndicator" class="alert alert-info text-center" style="display: none;">
            <i class="bi bi-hourglass-split"></i> Searching...
        </div>
        
        <!-- ⭐ PRODUCTS CONTAINER -->
        <div id="productsContainer">
            <?php if (empty($products)): ?>
            <div class="alert alert-info text-center" id="noResults">
                <i class="bi bi-info-circle"></i> No products found. Try adjusting your filters.
            </div>
            <?php else: ?>
            <div class="row g-4" id="productGrid">
                <?php foreach ($products as $product): ?>
                <div class="col-md-4">
                    <div class="card product-card h-100">
                        <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success badge-stock">In Stock</span>
                        <?php else: ?>
                        <span class="badge bg-danger badge-stock">Out of Stock</span>
                        <?php endif; ?>
                        
                        <img src="assets/images/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                             class="card-img-top product-image" alt="<?php echo $product['name']; ?>">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary mb-2 w-50"><?php echo $product['category_name']; ?></span>
                            <h5 class="card-title"><?php echo $product['name']; ?></h5>
                            <p class="card-text text-muted"><?php echo substr($product['description'], 0, 80); ?>...</p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                                    <small class="text-muted">Stock: <?php echo $product['stock']; ?></small>
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i> Details
                                    </a>
                                    <?php if ($product['stock'] > 0 && isLoggedIn()): ?>
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary">
                                        <i class="bi bi-cart-plus"></i> Add to Cart
                                    </button>
                                    <?php elseif (!isLoggedIn()): ?>
                                    <a href="login.php" class="btn btn-primary">Login to Buy</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ⭐ LIVE SEARCH SCRIPT -->
<script>
// Live Search Functionality
let searchTimeout;
const liveSearchInput = document.getElementById('liveSearchInput');
const productsContainer = document.getElementById('productsContainer');
const productCount = document.getElementById('productCount');
const loadingIndicator = document.getElementById('loadingIndicator');

liveSearchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    
    const keyword = this.value.trim();
    
    if (keyword.length === 0) {
        return;
    }
    
    if (keyword.length < 2) {
        return; // Minimal 2 karakter
    }
    
    // Debounce search (tunggu user selesai ngetik)
    searchTimeout = setTimeout(() => {
        performLiveSearch(keyword);
    }, 500); // 500ms delay
});

async function performLiveSearch(keyword) {
    // Show loading
    loadingIndicator.style.display = 'block';
    
    try {
        const response = await fetch(`api/search_products.php?q=${encodeURIComponent(keyword)}`);
        const data = await response.json();
        
        // Hide loading
        loadingIndicator.style.display = 'none';
        
        if (data.success) {
            displayProducts(data.products);
            productCount.textContent = `${data.count} products found`;
        } else {
            showError(data.message);
        }
    } catch (error) {
        loadingIndicator.style.display = 'none';
        console.error('Search error:', error);
        showError('Failed to search products');
    }
}

function displayProducts(products) {
    if (products.length === 0) {
        productsContainer.innerHTML = `
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle"></i> No products found. Try different keywords.
            </div>
        `;
        return;
    }
    
    let html = '<div class="row g-4" id="productGrid">';
    
    products.forEach(product => {
        const stockBadge = product.in_stock 
            ? '<span class="badge bg-success badge-stock">In Stock</span>'
            : '<span class="badge bg-danger badge-stock">Out of Stock</span>';
        
        const addToCartButton = product.in_stock && <?php echo isLoggedIn() ? 'true' : 'false'; ?>
            ? `<button onclick="addToCart(${product.id})" class="btn btn-primary">
                 <i class="bi bi-cart-plus"></i> Add to Cart
               </button>`
            : <?php echo isLoggedIn() ? '""' : '"<a href=\'login.php\' class=\'btn btn-primary\'>Login to Buy</a>"'; ?>;
        
        html += `
            <div class="col-md-4">
                <div class="card product-card h-100">
                    ${stockBadge}
                    <img src="assets/images/${product.image || 'placeholder.jpg'}" 
                         class="card-img-top product-image" alt="${product.name}">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-secondary mb-2 w-50">${product.category_name}</span>
                        <h5 class="card-title">${product.name}</h5>
                        <p class="card-text text-muted">${product.description.substring(0, 80)}...</p>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="price">${product.price_formatted}</span>
                                <small class="text-muted">Stock: ${product.stock}</small>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="product_detail.php?id=${product.id}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> Details
                                </a>
                                ${addToCartButton}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    productsContainer.innerHTML = html;
}

function showError(message) {
    productsContainer.innerHTML = `
        <div class="alert alert-danger text-center">
            <i class="bi bi-exclamation-triangle"></i> ${message}
        </div>
    `;
}
</script>

<?php require_once 'includes/footer.php'; ?>