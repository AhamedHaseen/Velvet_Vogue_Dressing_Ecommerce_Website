<?php
// Review Handler - Submit and Fetch Reviews
header('Content-Type: application/json');

// Include database connection
include "db_connection.php";

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'submit':
            handleSubmitReview();
            break;
        case 'fetch':
            handleFetchReviews();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function handleSubmitReview() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    // Get form data
    $product_id = intval($_POST['product_id'] ?? 0);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');
    
    // Validate required fields
    if (empty($product_id) || empty($customer_name) || empty($customer_email) || empty($rating) || empty($review_text)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate rating range
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5 stars']);
        return;
    }
    
    // Validate email
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    // Check if product exists
    $product_check = "SELECT product_id FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($product_check);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    // Insert review (with rating)
    $sql = "INSERT INTO reviews (product_id, customer_name, customer_email, rating, review_text) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issis", $product_id, $customer_name, $customer_email, $rating, $review_text);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
    }
}

function handleFetchReviews() {
    global $conn;
    
    $product_id = intval($_GET['product_id'] ?? 0);
    
    if (empty($product_id)) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    // Fetch reviews for the product
    $sql = "SELECT customer_name, rating, review_text, review_date 
            FROM reviews 
            WHERE product_id = ? AND is_approved = 1 
            ORDER BY review_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    $total_rating = 0;
    $review_count = 0;
    
    // Rating text conversion
    $rating_texts = [1 => 'One Star', 2 => 'Two Star', 3 => 'Three Star', 4 => 'Four Star', 5 => 'Five Star'];
    
    while ($row = $result->fetch_assoc()) {
        $reviews[] = [
            'customer_name' => $row['customer_name'],
            'rating' => $row['rating'],
            'rating_text' => $rating_texts[$row['rating']] ?? 'No Rating',
            'review_text' => $row['review_text'],
            'review_date' => date('M j, Y', strtotime($row['review_date']))
        ];
        $total_rating += $row['rating'];
        $review_count++;
    }
    
    $average_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'average_rating' => $average_rating,
        'review_count' => $review_count
    ]);
}

$conn->close();
?>