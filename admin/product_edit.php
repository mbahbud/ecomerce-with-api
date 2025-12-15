<?php
$page_title = "Edit Product";
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) {
    redirect('products.php');
}

$product_id = intval($_GET['id']);
$conn = getDBConnection();

// Get product
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    redirect('products.php');
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image = $product['image'];
    
    if (empty($name)) $errors[] = "Product name is required";
    if ($price <= 0) $errors[] = "Price must be greater than 0";
    if ($stock < 0) $errors[] = "Stock cannot be negative";
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type";
        } else {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../assets/images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image
                if ($product['image'] && file_exists('../assets/images/' . $product['image'])) {
                    unlink('../assets/images/' . $product['image']);
                }
                $image = $new_filename;
            }
        }
    }
    
    if (empty($errors)) {
        $query = "UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssdsi", $category_id, $name, $description, $price, $stock, $image, $product_id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Product updated successfully');
            redirect('products.php');
        } else {
            $errors[] = "Failed to update product";
        }
    }
}

closeDBConnection($conn);
require_once 'includes/admin_header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="products.php">Products</a></li>
        <li class="breadcrumb-item active">Edit Product</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Product</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" class="form-control" name="name" required 
                               value="<?php echo $product['name']; ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (Rp) *</label>
                            <input type="number" class="form-control" name="price" min="0" step="0.01" required 
                                   value="<?php echo $product['price']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stock *</label>
                        <input type="number" class="form-control" name="stock" min="0" required 
                               value="<?php echo $product['stock']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?php echo $product['description']; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <?php if ($product['image']): ?>
                        <div class="mb-2">
                            <img src="../assets/images/<?php echo $product['image']; ?>" 
                                 class="img-thumbnail" style="max-width: 200px;">
                            <p class="text-muted small mb-0">Current image</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="image" accept="image/*" 
                               onchange="previewImage(this, 'preview')">
                        <small class="text-muted">Leave empty to keep current image</small>
                        <div class="mt-2">
                            <img id="preview" src="" style="max-width: 200px; display: none;" class="img-thumbnail">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Update Product
                        </button>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Product Info</h6>
            </div>
            <div class="card-body">
                <p><strong>Product ID:</strong> #<?php echo $product['id']; ?></p>
                <p><strong>Created:</strong> <?php echo date('d M Y', strtotime($product['created_at'])); ?></p>
                <p class="mb-0"><strong>Current Stock:</strong> 
                    <span class="badge bg-<?php echo $product['stock'] < 10 ? 'danger' : 'success'; ?>">
                        <?php echo $product['stock']; ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>