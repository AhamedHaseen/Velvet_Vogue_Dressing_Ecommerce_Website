<?php
// Save Product Handler
header('Content-Type: application/json');

// Include database connection
include "db_connection.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Handle both FormData and JSON input
    if (isset($_POST['name'])) {
        // FormData from form submission
        $input = $_POST;
    } else {
        // JSON input fallback
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
    }

    // Handle image upload
    $image_url = null;
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'Images/';
        $file_name = $_FILES['productImage']['name'];
        $file_tmp = $_FILES['productImage']['tmp_name'];
        
        // Simple filename with timestamp
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = 'product_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $new_file_name;
        
        // Upload file
        if (move_uploaded_file($file_tmp, $target_path)) {
            $image_url = $target_path;
        }
    }

    // Check if this is an update operation
    $is_update = !empty($input['productId']);
    $product_id = $is_update ? intval($input['productId']) : 0;

    // Validate required fields
    $required_fields = ['name', 'category', 'price'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '{$field}' is required"]);
            exit;
        }
    }

    // Prepare data
    $name = trim($input['name']);
    $sku = trim($input['sku']);
    $category = trim($input['category']);
    $brand = trim($input['brand'] ?? '');
    $description = trim($input['description'] ?? '');
    $price = floatval($input['price']);
    $compare_price = !empty($input['comparePrice']) ? floatval($input['comparePrice']) : null;
    $inventory = intval($input['inventory'] ?? 0);
    $status = trim($input['status'] ?? 'active');
    $is_featured = (!empty($input['featured']) && ($input['featured'] === 'true' || $input['featured'] === true)) ? 1 : 0;
    $is_active = ($status === 'active') ? 1 : 0;

    // Generate unique product slug
    $base_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    $product_slug = $base_slug;
    
    // Check for unique slug
    $slug_counter = 1;
    while (true) {
        $slug_check_sql = "SELECT product_id FROM products WHERE product_slug = ?" . ($is_update ? " AND product_id != ?" : "");
        $slug_stmt = $conn->prepare($slug_check_sql);
        
        if ($is_update) {
            $slug_stmt->bind_param("si", $product_slug, $product_id);
        } else {
            $slug_stmt->bind_param("s", $product_slug);
        }
        
        $slug_stmt->execute();
        $slug_result = $slug_stmt->get_result();
        
        if ($slug_result->num_rows == 0) {
            $slug_stmt->close();
            break;
        }
        
        $product_slug = $base_slug . '-' . $slug_counter;
        $slug_counter++;
        $slug_stmt->close();
    }

    // For this structure, we'll need to find or create category_id
    $category_id = null;
    if (!empty($category)) {
        $cat_sql = "SELECT category_id FROM categories WHERE category_name = ?";
        $cat_stmt = $conn->prepare($cat_sql);
        $cat_stmt->bind_param("s", $category);
        $cat_stmt->execute();
        $cat_result = $cat_stmt->get_result();
        
        if ($cat_result->num_rows > 0) {
            $category_row = $cat_result->fetch_assoc();
            $category_id = $category_row['category_id'];
        } else {
            // Create new category
            $new_cat_sql = "INSERT INTO categories (category_name, is_active) VALUES (?, 1)";
            $new_cat_stmt = $conn->prepare($new_cat_sql);
            $new_cat_stmt->bind_param("s", $category);
            $new_cat_stmt->execute();
            $category_id = $conn->insert_id;
            $new_cat_stmt->close();
        }
        $cat_stmt->close();
    }

    // Check if product with same name exists (but not the current product if updating)
    $check_sql = "SELECT product_id FROM products WHERE product_name = ?" . ($is_update ? " AND product_id != ?" : "");
    $check_stmt = $conn->prepare($check_sql);
    
    if ($is_update) {
        $check_stmt->bind_param("si", $name, $product_id);
    } else {
        $check_stmt->bind_param("s", $name);
    }
    
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Product with this name already exists']);
        exit;
    }

    if ($is_update) {
        // Update existing product
        if ($image_url) {
            $sql = "UPDATE products SET product_name = ?, product_slug = ?, category_id = ?, price = ?, original_price = ?, stock_quantity = ?, is_featured = ?, image_url = ? WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiddiisi", 
                $name, $product_slug, $category_id, $price, $compare_price, $inventory, $is_featured, $image_url, $product_id
            );
        } else {
            $sql = "UPDATE products SET product_name = ?, product_slug = ?, category_id = ?, price = ?, original_price = ?, stock_quantity = ?, is_featured = ? WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiddiiii", 
                $name, $product_slug, $category_id, $price, $compare_price, $inventory, $is_featured, $product_id
            );
        }

        if ($stmt->execute()) {
            // Try to update description separately if column exists
            $desc_sql = "UPDATE products SET product_description = ? WHERE product_id = ?";
            $desc_stmt = $conn->prepare($desc_sql);
            if ($desc_stmt) {
                $desc_stmt->bind_param("si", $description, $product_id);
                $desc_stmt->execute();
                $desc_stmt->close();
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product updated successfully',
                'product_id' => $product_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
    } else {
        // Insert new product
        $sql = "INSERT INTO products (product_name, product_slug, category_id, price, original_price, image_url, stock_quantity, is_featured, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        // Use uploaded image or default
        $final_image = $image_url ?: 'Images/default-product.jpg';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiddsii", 
            $name, $product_slug, $category_id, $price, $compare_price, $final_image, $inventory, $is_featured
        );

        if ($stmt->execute()) {
            $new_product_id = $conn->insert_id;
            
            // Try to update description separately if column exists
            if (!empty($description)) {
                $desc_sql = "UPDATE products SET product_description = ? WHERE product_id = ?";
                $desc_stmt = $conn->prepare($desc_sql);
                if ($desc_stmt) {
                    $desc_stmt->bind_param("si", $description, $new_product_id);
                    $desc_stmt->execute();
                    $desc_stmt->close();
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product added successfully',
                'product_id' => $new_product_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
    }

    $stmt->close();
    $check_stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>