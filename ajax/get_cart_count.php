<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

$count = 0;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $conn = getDBConnection();
    
    $query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $count = $result['total'] ?? 0;
    closeDBConnection($conn);
}

echo json_encode(['count' => $count]);
?>