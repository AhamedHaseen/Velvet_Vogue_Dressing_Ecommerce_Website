<?php
// Delete Product Handler
header('Content-Type: application/json');

// Include database connection
include "db_connection.php";

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get product ID
    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    // Check if product exists
    $check_sql = "SELECT product_id, product_name FROM products WHERE product_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    $product = $result->fetch_assoc();
    $check_stmt->close();

    // Delete the product
    $delete_sql = "DELETE FROM products WHERE product_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $product_id);

    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Product "' . $product['product_name'] . '" deleted successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $delete_stmt->error]);
    }

    $delete_stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>