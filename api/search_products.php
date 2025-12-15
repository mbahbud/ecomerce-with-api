<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// API Endpoint untuk Live Search Products
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

// Validasi input
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([
        'success' => false,
        'message' => 'Search keyword required',
        'products' => []
    ]);
    exit;
}

$keyword = sanitize($_GET['q']);
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;

$conn = getDBConnection();

// Build query
$where = [];
$params = [];
$types = '';

// Search by name or description
$where[] = "(p.name LIKE ? OR p.description LIKE ?)";
$search_param = "%$keyword%";
$params[] = $search_param;
$params[] = $search_param;
$types .= 'ss';

// Filter by category if specified
if ($category > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

$where_clause = implode(' AND ', $where);

// Get products with category name
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE $where_clause
          ORDER BY p.name ASC
          LIMIT 20";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format response
$formatted_products = [];
foreach ($products as $product) {
    $formatted_products[] = [
        'id' => (int)$product['id'],
        'name' => $product['name'],
        'description' => $product['description'],
        'price' => (float)$product['price'],
        'price_formatted' => 'Rp ' . number_format($product['price'], 0, ',', '.'),
        'stock' => (int)$product['stock'],
        'in_stock' => $product['stock'] > 0,
        'category_id' => (int)$product['category_id'],
        'category_name' => $product['category_name'],
        'image' => $product['image'],
        'image_url' => '../assets/images/' . ($product['image'] ?: 'placeholder.jpg')
    ];
}

closeDBConnection($conn);

// Send response
echo json_encode([
    'success' => true,
    'count' => count($formatted_products),
    'keyword' => $keyword,
    'products' => $formatted_products
], JSON_PRETTY_PRINT);
?>