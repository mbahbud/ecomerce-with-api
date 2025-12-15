<?php
$page_title = "Manage Products";
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$conn = getDBConnection();

// Delete product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id = $id");
    setFlashMessage('success', 'Product deleted successfully');
    redirect('products.php');
}

// Get all products
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.created_at DESC";
$products = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
require_once 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam"></i> Manage Products</h2>
    <a href="product_add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New Product
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <img src="../assets/images/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                                 width="50" class="rounded" alt="">
                        </td>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['category_name']; ?></td>
                        <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                        <td>
                            <?php if ($product['stock'] < 10): ?>
                            <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                            <?php else: ?>
                            <span class="badge bg-success"><?php echo $product['stock']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($product['stock'] > 0): ?>
                            <span class="badge bg-success">Available</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Out of Stock</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="../product_detail.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-sm btn-info" target="_blank" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="product_edit.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-sm btn-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?delete=<?php echo $product['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirmDelete('Delete this product?')" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>