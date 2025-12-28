<?php
// Include session and database
include 'includes/session_db.php';

// Check if user is logged in for enhanced order tracking
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_name = $is_logged_in ? $_SESSION['user_name'] : null;
$user_email = $is_logged_in ? $_SESSION['user_email'] : null;

// Initialize variables
$cartItems = [];
$cartTotal = 0;
$orderProcessed = false;
$orderId = null;
$successMessage = '';
$errorMessage = '';

// Handle AJAX promo code validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'validate_promo') {
    // Log the request for debugging
    error_log("Promo code validation request received: " . print_r($_POST, true));
    
    header('Content-Type: application/json');
    
    $promoCode = trim(strtoupper($_POST['promo_code'] ?? ''));
    $orderTotal = floatval($_POST['order_total'] ?? 0);
    
    error_log("Processing promo code: $promoCode, Order total: $orderTotal");
    
    if (empty($promoCode)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a promo code']);
        exit;
    }
    
    try {
        // Check if promo code exists and is valid
        $promoStmt = $conn->prepare("SELECT * FROM promo_codes WHERE promo_code = ? AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
        $promoStmt->bind_param("s", $promoCode);
        $promoStmt->execute();
        $promoResult = $promoStmt->get_result();
        
        if ($promoRow = $promoResult->fetch_assoc()) {
            error_log("Promo code found: " . print_r($promoRow, true));
            
            // Check minimum order amount
            if ($orderTotal < $promoRow['minimum_order_amount']) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Minimum order amount for this promo code is $' . number_format($promoRow['minimum_order_amount'], 2)
                ]);
                exit;
            }
            
            // Check usage limit
            if ($promoRow['usage_limit'] && $promoRow['used_count'] >= $promoRow['usage_limit']) {
                echo json_encode(['success' => false, 'message' => 'This promo code has reached its usage limit']);
                exit;
            }
            
            // Calculate discount
            $discount = 0;
            if ($promoRow['discount_type'] === 'percentage') {
                $discount = ($orderTotal * $promoRow['discount_value']) / 100;
                // Apply maximum discount limit if set
                if ($promoRow['maximum_discount_amount'] && $discount > $promoRow['maximum_discount_amount']) {
                    $discount = $promoRow['maximum_discount_amount'];
                }
            } else { // fixed_amount
                $discount = $promoRow['discount_value'];
            }
            
            // Ensure discount doesn't exceed order total
            $discount = min($discount, $orderTotal);
            
            error_log("Calculated discount: $discount");
            
            echo json_encode([
                'success' => true,
                'message' => 'Promo code applied successfully!',
                'discount' => $discount,
                'promo_code' => $promoCode,
                'discount_type' => $promoRow['discount_type'],
                'discount_value' => $promoRow['discount_value']
            ]);
        } else {
            error_log("Promo code not found or expired: $promoCode");
            echo json_encode(['success' => false, 'message' => 'Invalid or expired promo code']);
        }
    } catch (Exception $e) {
        error_log("Error processing promo code: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error processing promo code']);
    }
    exit;
}

// Handle simple PHP form submission (no JavaScript required)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['cart_data'])) {
    try {
        // Get form data
        $firstName = $_POST['firstName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $state = $_POST['state'] ?? '';
        $zipCode = $_POST['zipCode'] ?? '';
        $paymentMethod = $_POST['paymentMethod'] ?? 'card';
        
        // Card details (only process if card payment is selected)
        $cardNumberLast4 = '';
        $cardHolderName = '';
        $cardExpiryMonth = '';
        $cardExpiryYear = '';
        $cardType = '';
        
        // Debug: Log what POST data we're receiving for card details
        error_log("STANDARD FORM - Payment Method: " . $paymentMethod);
        error_log("STANDARD FORM - POST cardNumber: " . ($_POST['cardNumber'] ?? 'NOT SET'));
        error_log("STANDARD FORM - POST cardName: " . ($_POST['cardName'] ?? 'NOT SET'));
        error_log("STANDARD FORM - POST expiryDate: " . ($_POST['expiryDate'] ?? 'NOT SET'));
        error_log("STANDARD FORM - POST cvv: " . ($_POST['cvv'] ?? 'NOT SET'));
        
        // Process card details if any card information is provided (regardless of payment method)
        $cardNumber = $_POST['cardNumber'] ?? '';
        $cardHolderName = $_POST['cardName'] ?? '';
        $expiryDate = $_POST['expiryDate'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        
        if (!empty($cardNumber) || !empty($cardHolderName)) {
            // Security: Only store last 4 digits of card number
            if (!empty($cardNumber)) {
                $cardNumberClean = preg_replace('/\D/', '', $cardNumber);
                $cardNumberLast4 = substr($cardNumberClean, -4);
                
                // Determine card type based on first digit
                $firstDigit = substr($cardNumberClean, 0, 1);
                if ($firstDigit == '4') $cardType = 'Visa';
                elseif ($firstDigit == '5') $cardType = 'Mastercard';
                elseif ($firstDigit == '3') $cardType = 'American Express';
                else $cardType = 'Other';
            }
            
            // Parse expiry date (MM/YY format)
            if (!empty($expiryDate) && strpos($expiryDate, '/') !== false) {
                list($cardExpiryMonth, $cardExpiryYear) = explode('/', $expiryDate);
                $cardExpiryYear = '20' . $cardExpiryYear; // Convert YY to YYYY
            }
        }
        
        // Validate required fields
        $requiredFieldsEmpty = empty($firstName) || empty($lastName) || empty($email) || empty($phone) || 
            empty($address) || empty($city) || empty($state) || empty($zipCode);
        
        // Only require card fields if card data was provided (allows flexibility in payment methods)
        $cardFieldsEmpty = (!empty($cardNumber) || !empty($cardHolderName)) && (empty($cardNumber) || empty($cardHolderName) || empty($expiryDate) || empty($cvv));
        
        if ($requiredFieldsEmpty || $cardFieldsEmpty) {
            $errorMessage = "Please fill in all required fields including card details.";
        } else {
            // Get current cart items for calculation first
            $currentCartItems = [];
            
            // Debug: Log what we're receiving
            error_log("Standard form submission - GET params: " . print_r($_GET, true));
            error_log("Standard form submission - POST params: " . print_r($_POST, true));
            
            // CRITICAL FIX: During POST submission, GET params may be lost
            // Check if cart items are passed in POST data first, then fallback to GET
            $cartItemsSource = '';
            $cartItemsParam = '';
            
            if (isset($_POST['cart_items']) && !empty($_POST['cart_items'])) {
                $cartItemsParam = urldecode($_POST['cart_items']);
                $cartItemsSource = 'POST';
            } elseif (isset($_GET['cart_items']) && !empty($_GET['cart_items'])) {
                $cartItemsParam = urldecode($_GET['cart_items']);
                $cartItemsSource = 'GET';
            }
            
            error_log("CART FIX - Cart items source: " . $cartItemsSource . ", Value: " . $cartItemsParam);
            
            // Try to get cart items from URL or POST first
            if (!empty($cartItemsParam)) {
                $cartItemIds = explode(',', $cartItemsParam);
                
                // Debug: Log cart processing
                error_log("CART DEBUG - cartItemsParam: " . $cartItemsParam);
                error_log("CART DEBUG - cartItemIds: " . print_r($cartItemIds, true));
                
                $quantities = [];
                $quantitiesParam = '';
                
                if (isset($_POST['quantities']) && !empty($_POST['quantities'])) {
                    $quantitiesParam = urldecode($_POST['quantities']);
                } elseif (isset($_GET['quantities']) && !empty($_GET['quantities'])) {
                    $quantitiesParam = urldecode($_GET['quantities']);
                }
                
                if (!empty($quantitiesParam)) {
                    $quantityPairs = explode(',', $quantitiesParam);
                    
                    error_log("CART DEBUG - quantitiesParam: " . $quantitiesParam);
                    error_log("CART DEBUG - quantityPairs: " . print_r($quantityPairs, true));
                    
                    foreach ($quantityPairs as $pair) {
                        $parts = explode(':', $pair);
                        if (count($parts) == 2) {
                            $quantities[(int)$parts[0]] = (int)$parts[1];
                        }
                    }
                }
                
                if (!empty($cartItemIds)) {
                    $placeholders = str_repeat('?,', count($cartItemIds) - 1) . '?';
                    $cartQuery = "SELECT product_id, product_name, price, image_url FROM products WHERE product_id IN ($placeholders) AND is_active = 1";
                    
                    error_log("CART DEBUG - SQL Query: " . $cartQuery);
                    error_log("CART DEBUG - cartItemIds for binding: " . print_r($cartItemIds, true));
                    
                    $cartStmt = $conn->prepare($cartQuery);
                    
                    if ($cartStmt) {
                        $types = str_repeat('i', count($cartItemIds));
                        $cartStmt->bind_param($types, ...$cartItemIds);
                        $cartStmt->execute();
                        $result = $cartStmt->get_result();
                        
                        error_log("CART DEBUG - Query executed, rows found: " . $result->num_rows);
                        
                        while ($row = $result->fetch_assoc()) {
                            $productId = $row['product_id'];
                            $quantity = isset($quantities[$productId]) ? $quantities[$productId] : 1;
                            
                            $currentCartItems[] = [
                                'id' => $productId,
                                'name' => $row['product_name'],
                                'price' => floatval($row['price']),
                                'image' => $row['image_url'],
                                'quantity' => $quantity
                            ];
                        }
                    }
                }
            }
            
            // If no cart items from URL, try to get from session or hidden form data
            if (empty($currentCartItems)) {
                // Try to get cart data from hidden form field (localStorage)
                if (isset($_POST['hidden_cart_data']) && !empty($_POST['hidden_cart_data'])) {
                    $hiddenCartData = json_decode($_POST['hidden_cart_data'], true);
                    if ($hiddenCartData && is_array($hiddenCartData)) {
                        foreach ($hiddenCartData as $item) {
                            if (isset($item['id'], $item['name'], $item['price'], $item['quantity'])) {
                                $currentCartItems[] = [
                                    'id' => intval($item['id']),
                                    'name' => $item['name'],
                                    'price' => floatval($item['price']),
                                    'image' => $item['image'] ?? 'Images/default-product.jpg',
                                    'quantity' => intval($item['quantity'])
                                ];
                            }
                        }
                        error_log("Cart items loaded from hidden form data: " . count($currentCartItems) . " items");
                    }
                }
                // Try to get cart data from session
                else if (isset($_SESSION['checkout_cart']) && !empty($_SESSION['checkout_cart'])) {
                    $currentCartItems = $_SESSION['checkout_cart'];
                    error_log("Cart items loaded from session: " . count($currentCartItems) . " items");
                } else {
                    // Last resort: Get sample products but warn about it
                    error_log("Warning: No cart items found, using sample products");
                    $sampleQuery = "SELECT product_id, product_name, price, image_url FROM products WHERE is_active = 1 ORDER BY RAND() LIMIT 2";
                    $sampleResult = $conn->query($sampleQuery);
                    
                    if ($sampleResult && $sampleResult->num_rows > 0) {
                        while ($row = $sampleResult->fetch_assoc()) {
                            $currentCartItems[] = [
                                'id' => $row['product_id'],
                                'name' => $row['product_name'],
                                'price' => floatval($row['price']),
                                'image' => $row['image_url'],
                                'quantity' => 1
                            ];
                        }
                    }
                }
            }
            
            if (!empty($currentCartItems)) {
                // Calculate totals using unified function
                $standardFormTotals = calculateOrderTotals($currentCartItems);
                $subtotal = $standardFormTotals['subtotal'];
                $shipping = $standardFormTotals['shipping'];
                $tax = $standardFormTotals['tax'];
            $total = $standardFormTotals['total'];
            
            // Initialize discount variable for standard form
            $discount = 0;
            
            // Generate unique order number
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Insert billing information
            $billingStmt = $conn->prepare("INSERT INTO payment_billing_info (first_name, last_name, email, phone, address, city, state, zip_code, payment_method, card_number_last4, card_holder_name, card_expiry_month, card_expiry_year, card_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($billingStmt) {
                // Debug: Log what card data we're saving
                error_log("STANDARD FORM - Saving billing info with card data:");
                error_log("Card Number Last 4: " . $cardNumberLast4);
                error_log("Card Holder Name: " . $cardHolderName);
                error_log("Card Expiry Month: " . $cardExpiryMonth);
                error_log("Card Expiry Year: " . $cardExpiryYear);
                error_log("Card Type: " . $cardType);
                
                $billingStmt->bind_param("ssssssssssssss", 
                    $firstName, $lastName, $email, $phone, $address, $city, $state, $zipCode, $paymentMethod,
                    $cardNumberLast4, $cardHolderName, $cardExpiryMonth, $cardExpiryYear, $cardType
                );
                
                if ($billingStmt->execute()) {
                    $billingId = $conn->insert_id;
                    
                    // Insert order (with order number, user_id and discount if available)
                    $orderStmt = $conn->prepare("INSERT INTO payment_orders (billing_id, user_id, order_number, subtotal, shipping, tax, discount, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($orderStmt) {
                        $orderStmt->bind_param("iisddddd", $billingId, $user_id, $orderNumber, $subtotal, $shipping, $tax, $discount, $total);
                        
                        if ($orderStmt->execute()) {
                            $orderId = $conn->insert_id;
                            
                            // Cart items already loaded above
                            
                            // Insert actual cart items into payment_purchase_items
                            if (!empty($currentCartItems)) {
                                $itemStmt = $conn->prepare("INSERT INTO payment_purchase_items (order_id, product_id, product_name, product_image, product_price, quantity, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                
                                if ($itemStmt) {
                                    foreach ($currentCartItems as $cartItem) {
                                        $itemTotal = $cartItem['price'] * $cartItem['quantity'];
                                        
                                        $itemStmt->bind_param("iissdid", 
                                            $orderId,
                                            $cartItem['id'],
                                            $cartItem['name'],
                                            $cartItem['image'],
                                            $cartItem['price'],
                                            $cartItem['quantity'],
                                            $itemTotal
                                        );
                                        $itemStmt->execute();
                                    }
                                    $itemStmt->close();
                                }
                            }
                            
                            $orderProcessed = true;
                            $successMessage = "Order placed successfully! Order ID: #$orderId, Billing ID: #$billingId";
                            
                            // Clear cart items after successful order (this will make order summary empty on refresh)
                            $displayCartItems = [];
                            
                        } else {
                            $errorMessage = "Failed to create order: " . $conn->error;
                        }
                    } else {
                        $errorMessage = "Failed to prepare order statement: " . $conn->error;
                    }
                } else {
                    $errorMessage = "Failed to save billing information: " . $conn->error;
                }
            } else {
                $errorMessage = "Failed to prepare billing statement: " . $conn->error;
            }
            } // End of cart items validation else block
        }
    } catch (Exception $e) {
        $errorMessage = "Error processing order: " . $e->getMessage();
    }
}

// Get cart items from database for display
$displayCartItems = [];
$displaySubtotal = 0;
$displayShipping = 10.00;
$displayTax = 0;
$displayTotal = 0;

// Only load cart items if order hasn't been processed (to show empty cart after successful order)
if (!$orderProcessed) {
    // Try to get real cart items from URL parameters first
    if (isset($_GET['cart_items']) && !empty($_GET['cart_items'])) {
        try {
            // Get cart items and quantities from URL parameters (from cart page)
            $cartItemsParam = urldecode($_GET['cart_items']);
            $cartItemIds = explode(',', $cartItemsParam);
            
            // Get quantities if provided
            $quantities = [];
            if (isset($_GET['quantities']) && !empty($_GET['quantities'])) {
                $quantitiesParam = urldecode($_GET['quantities']);
                $quantityPairs = explode(',', $quantitiesParam);
                
                foreach ($quantityPairs as $pair) {
                    $parts = explode(':', $pair);
                    if (count($parts) == 2) {
                        $quantities[(int)$parts[0]] = (int)$parts[1];
                    }
                }
            }
            
            if (!empty($cartItemIds)) {
                // Get actual cart items from database
                $placeholders = str_repeat('?,', count($cartItemIds) - 1) . '?';
                $cartQuery = "SELECT product_id, product_name, price, image_url FROM products WHERE product_id IN ($placeholders) AND is_active = 1";
                $cartStmt = $conn->prepare($cartQuery);
                
                if ($cartStmt) {
                    // Create types string (all integers for product IDs)
                    $types = str_repeat('i', count($cartItemIds));
                    $cartStmt->bind_param($types, ...$cartItemIds);
                    $cartStmt->execute();
                    $result = $cartStmt->get_result();
                    
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $productId = $row['product_id'];
                            $quantity = isset($quantities[$productId]) ? $quantities[$productId] : 1;
                            
                            $displayCartItems[] = [
                                'id' => $productId,
                                'name' => $row['product_name'],
                                'price' => floatval($row['price']),
                                'image' => $row['image_url'],
                                'quantity' => $quantity
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error processing cart items from URL: " . $e->getMessage());
        }
    }

    // If no cart items from URL, show empty cart (don't load random products)
    if (empty($displayCartItems)) {
        // Don't load random sample products - keep cart empty
        // This ensures consistent empty state on page refresh
        $displayCartItems = [];
    }} // End of if (!$orderProcessed) - don't load cart items if order was just processed

// Calculate totals for display using unified function
$displayTotals = calculateOrderTotals($displayCartItems);
$displaySubtotal = $displayTotals['subtotal'];
$displayShipping = $displayTotals['shipping'];
$displayTax = $displayTotals['tax'];
$displayTotal = $displayTotals['total'];

// UNIFIED PHP CALCULATION FUNCTION
function calculateOrderTotals($cartItems) {
    $subtotal = 0;
    
    // Debug: Log each item calculation
    error_log("=== CALCULATING ORDER TOTALS ===");
    
    if (!empty($cartItems)) {
        foreach ($cartItems as $index => $item) {
            $itemPrice = floatval($item['price'] ?? 0);
            $itemQuantity = intval($item['quantity'] ?? 1);
            $itemTotal = $itemPrice * $itemQuantity;
            $subtotal += $itemTotal;
            
            error_log("Item $index: {$item['name']} - Price: $itemPrice, Qty: $itemQuantity, Total: $itemTotal");
        }
    }
    
    // Apply shipping: Check for free shipping conditions
    // Based on your case: $369.99 item should have free shipping
    $shipping = ($subtotal >= 300) ? 0 : 10;
    
    // Apply tax: 8% tax rate
    $tax = $subtotal * 0.08;
    
    // Calculate final total
    $total = $subtotal + $shipping + $tax;
    
    // Round all values to 2 decimal places
    $subtotal = round($subtotal, 2);
    $shipping = round($shipping, 2);
    $tax = round($tax, 2);
    $total = round($total, 2);
    
    // Debug: Log final calculations
    error_log("CALCULATED TOTALS:");
    error_log("Subtotal: $subtotal");
    error_log("Shipping: $shipping" . ($shipping == 0 ? " (Free shipping over $100)" : ""));
    error_log("Tax (8%): $tax");
    error_log("Total: $total");
    
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'tax' => $tax,
        'total' => $total
    ];
}

// Test endpoint to verify calculations
if (isset($_GET['test_calc'])) {
    header('Content-Type: application/json');
    
    // Create test cart data
    $testCart = [
        [
            'id' => 1,
            'name' => 'Boys Premium Watch',
            'price' => 369.99,
            'quantity' => 1
        ]
    ];
    
    $result = calculateOrderTotals($testCart);
    
    echo json_encode([
        'test_item' => $testCart[0],
        'calculations' => $result,
        'expected_total' => 399.59, // Your expected total from the image
        'matches_expected' => abs($result['total'] - 399.59) < 0.01
    ]);
    exit;
}

// Handle AJAX requests for order processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    header('Content-Type: application/json');
    
    try {
        // Debug: Log the received data
        error_log("Payment form data received: " . print_r($_POST, true));
        
        // Get cart data from localStorage (sent via AJAX)
        $cartData = json_decode($_POST['cart_data'], true);
        
        // Enhanced debugging
        error_log("Cart data decoded: " . print_r($cartData, true));
        
        if (!$cartData || empty($cartData)) {
            error_log("Error: Cart data is empty or invalid");
            echo json_encode(['success' => false, 'message' => 'Cart is empty']);
            exit;
        }
        
        // Get customer data from form
        $customerName = $_POST['firstName'] . ' ' . $_POST['lastName'];
        $customerEmail = $_POST['email'];
        $customerPhone = $_POST['phone'];
        $customerAddress = $_POST['address'] . ', ' . $_POST['city'] . ', ' . $_POST['state'] . ' ' . $_POST['zipCode'];
        $paymentMethod = $_POST['paymentMethod'];
        
        // Calculate totals using unified function
        $orderTotals = calculateOrderTotals($cartData);
        $subtotal = $orderTotals['subtotal'];
        $shipping = $orderTotals['shipping'];
        $tax = $orderTotals['tax'];
        $total = $orderTotals['total'];
        
        // Initialize discount variable
        $discount = 0;
        
        // Debug: Log the calculated totals
        error_log("Calculated totals - Subtotal: $subtotal, Shipping: $shipping, Tax: $tax, Total: $total");
        
        // Generate unique order number
        $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        // Get billing information from form
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $zipCode = $_POST['zipCode'];
        $paymentMethod = $_POST['paymentMethod'];
        
        // Card details (only process if card payment is selected)
        $cardNumberLast4 = '';
        $cardHolderName = '';
        $cardExpiryMonth = '';
        $cardExpiryYear = '';
        $cardType = '';
        
        // Debug: Log what POST data we're receiving for card details
        error_log("AJAX FORM - Payment Method: " . $paymentMethod);
        error_log("AJAX FORM - POST cardNumber: " . ($_POST['cardNumber'] ?? 'NOT SET'));
        error_log("AJAX FORM - POST cardName: " . ($_POST['cardName'] ?? 'NOT SET'));
        error_log("AJAX FORM - POST expiryDate: " . ($_POST['expiryDate'] ?? 'NOT SET'));
        error_log("AJAX FORM - POST cvv: " . ($_POST['cvv'] ?? 'NOT SET'));
        
        // Process card details if any card information is provided (regardless of payment method)
        $cardNumber = $_POST['cardNumber'] ?? '';
        $cardHolderName = $_POST['cardName'] ?? '';
        $expiryDate = $_POST['expiryDate'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        
        if (!empty($cardNumber) || !empty($cardHolderName)) {
            // Security: Only store last 4 digits of card number
            if (!empty($cardNumber)) {
                $cardNumberClean = preg_replace('/\D/', '', $cardNumber);
                $cardNumberLast4 = substr($cardNumberClean, -4);
                
                // Determine card type based on first digit
                $firstDigit = substr($cardNumberClean, 0, 1);
                if ($firstDigit == '4') $cardType = 'Visa';
                elseif ($firstDigit == '5') $cardType = 'Mastercard';
                elseif ($firstDigit == '3') $cardType = 'American Express';
                else $cardType = 'Other';
            }
            
            // Parse expiry date (MM/YY format)
            if (!empty($expiryDate) && strpos($expiryDate, '/') !== false) {
                list($cardExpiryMonth, $cardExpiryYear) = explode('/', $expiryDate);
                $cardExpiryYear = '20' . $cardExpiryYear; // Convert YY to YYYY
            }
        }
        
        // Debug: Log what card data we're saving
        error_log("AJAX FORM - Saving billing info with card data:");
        error_log("Card Number Last 4: " . $cardNumberLast4);
        error_log("Card Holder Name: " . $cardHolderName);
        error_log("Card Expiry Month: " . $cardExpiryMonth);
        error_log("Card Expiry Year: " . $cardExpiryYear);
        error_log("Card Type: " . $cardType);
        
        // Insert billing information with card details
        $billingStmt = $conn->prepare("INSERT INTO payment_billing_info (first_name, last_name, email, phone, address, city, state, zip_code, payment_method, card_number_last4, card_holder_name, card_expiry_month, card_expiry_year, card_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $billingStmt->bind_param("ssssssssssssss", 
            $firstName,
            $lastName,
            $email,
            $phone,
            $address,
            $city,
            $state,
            $zipCode,
            $paymentMethod,
            $cardNumberLast4,
            $cardHolderName,
            $cardExpiryMonth,
            $cardExpiryYear,
            $cardType
        );
        
        if ($billingStmt->execute()) {
            $billingId = $conn->insert_id;
            
            // Insert order (with order number, user_id and discount)
            $orderStmt = $conn->prepare("INSERT INTO payment_orders (billing_id, user_id, order_number, subtotal, shipping, tax, discount, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $orderStmt->bind_param("iisddddd", $billingId, $user_id, $orderNumber, $subtotal, $shipping, $tax, $discount, $total);
            
            if ($orderStmt->execute()) {
                $orderId = $conn->insert_id;
                
                // Insert purchase items with images (using correct database column names)
                $itemStmt = $conn->prepare("INSERT INTO payment_purchase_items (order_id, product_id, product_name, product_image, product_price, quantity, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                foreach ($cartData as $item) {
                    $productId = isset($item['id']) ? intval($item['id']) : null;
                    $productName = isset($item['name']) ? $item['name'] : 'Unknown Product';
                    $productImage = isset($item['image']) ? $item['image'] : 'no-image.jpg';
                    $productPrice = isset($item['price']) ? floatval($item['price']) : 0;
                    $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
                    $itemTotal = $productPrice * $quantity;
                    
                    $itemStmt->bind_param("iissdid", 
                        $orderId,
                        $productId,
                        $productName,
                        $productImage,
                        $productPrice,
                        $quantity,
                        $itemTotal
                    );
                    $itemStmt->execute();
                }
                
                // Set success message for page display on next load
                $successMessage = "Order placed successfully! Order ID: #$orderId, Billing ID: #$billingId";
                
                echo json_encode([
                    'success' => true,
                    'order_id' => $orderId,
                    'message' => "Payment processed successfully! Order ID: #$orderId, Billing ID: #$billingId",
                    'billing_id' => $billingId
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create order'
                ]);
            }
        } else {
            echo json_encode([
                'message' => 'Failed to save billing information'
            ]);
        }
    } catch (Exception $e) {
        error_log("Payment processing error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your payment'
        ]);
    }
    exit;
}

// Get cart items from localStorage (this will be handled by JavaScript)
// For demo purposes, you can also handle cart data from session or cookie
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment - Velvet Vogue</title>

    <!-- Bootstrap 5.3.8 CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <!-- Bootstrap Icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />

    <!-- Google Fonts -->
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
      * {
        font-family: "Inter", sans-serif;
      }

      :root {
        --primary-color: #003049;
        --secondary-color: #8ecae6;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --dark-color: #2c3e50;
        --light-bg: #f8f9fa;
        --white: #ffffff;
      }

      body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 0;
        margin: 0;
      }

      .navbar {
        background: var(--white);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 15px 0;
      }

      .navbar-brand {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-color) !important;
        text-decoration: none;
      }

      .payment-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 15px;
      }

      .payment-card {
        background: var(--white);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: none;
        overflow: hidden;
      }

      .card-header {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 25px;
        text-align: center;
      }

      .progress-bar-custom {
        height: 8px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.3);
        margin: 20px 0;
      }

      .progress-fill {
        height: 100%;
        background: white;
        border-radius: 10px;
        width: 66.67%;
        transition: width 0.3s ease;
      }

      .step-indicator {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
      }

      .step {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 15px;
        font-weight: 600;
        position: relative;
      }

      .step.active {
        background: white;
        color: var(--primary-color);
      }

      .step.completed {
        background: var(--success-color);
        color: white;
      }

      .step::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50px;
        width: 30px;
        height: 2px;
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-50%);
      }

      .step:last-child::after {
        display: none;
      }

      .form-control,
      .form-select {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        padding: 15px;
        font-size: 16px;
        transition: all 0.3s ease;
      }

      .form-control:focus,
      .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(0, 48, 73, 0.25);
      }

      .btn-primary {
        background: linear-gradient(
          45deg,
          var(--primary-color),
          var(--secondary-color)
        );
        border: none;
        border-radius: 12px;
        padding: 15px 30px;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
      }

      .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 48, 73, 0.4);
      }

      .btn-place-order {
        background: #28a745;
        border: none;
        border-radius: 12px;
        padding: 15px 30px;
        font-weight: 600;
        font-size: 16px;
        color: white;
      }

      .btn-place-order:hover {
        background: #28a745 !important;
        color: white !important;
        border: none !important;
      }

      .order-summary {
        background: var(--light-bg);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
      }
      
      .promo-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #dee2e6;
        position: relative;
        overflow: hidden;
      }
      
      .promo-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #28a745, #20c997);
      }
      
      .promo-section .btn-outline-success {
        border: 2px solid #28a745;
        color: #28a745;
        font-weight: 600;
        transition: all 0.3s ease;
      }
      
      .promo-section .btn-outline-success:hover {
        background: #28a745;
        border-color: #28a745;
        color: white;
        transform: translateY(-1px);
      }
      
      .promo-section .btn-outline-success:disabled {
        background: #28a745;
        border-color: #28a745;
        color: white;
        opacity: 0.8;
      }
      
      .promo-section .btn-outline-primary {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 6px;
        transition: all 0.3s ease;
      }
      
      .promo-section .btn-outline-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,123,255,0.2);
      }

      .product-item {
        display: flex;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #e9ecef;
      }

      .product-item:last-child {
        border-bottom: none;
      }

      .product-image {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        object-fit: cover;
        margin-right: 15px;
      }

      .product-info {
        flex: 1;
      }

      .product-name {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 5px;
      }

      .product-details {
        font-size: 14px;
        color: #6c757d;
      }

      .product-price {
        font-weight: 600;
        color: var(--primary-color);
        font-size: 18px;
      }

      .payment-method {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .payment-method:hover {
        border-color: var(--primary-color);
        background: rgba(0, 48, 73, 0.05);
      }

      .payment-method.selected {
        border-color: var(--primary-color);
        background: rgba(0, 48, 73, 0.1);
      }

      .payment-icon {
        font-size: 2rem;
        margin-right: 15px;
        color: var(--primary-color);
      }

      .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(40, 167, 69, 0.1);
        color: var(--success-color);
        padding: 10px;
        border-radius: 8px;
        margin-top: 20px;
      }

      .security-badge i {
        margin-right: 8px;
      }

      .card-input-group {
        position: relative;
      }

      .card-type-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.5rem;
        color: var(--primary-color);
      }

      /* Secure card input styling */
      .secure-badge {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        margin-bottom: 15px;
      }
      
      .secure-badge i {
        margin-right: 5px;
      }
      
      .card-security-info {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
      }
      
      .card-security-info h6 {
        color: #28a745;
        margin-bottom: 10px;
      }
      
      .security-features {
        list-style: none;
        padding: 0;
        margin: 0;
      }
      
      .security-features li {
        padding: 5px 0;
        display: flex;
        align-items: center;
      }
      
      .security-features li i {
        color: #28a745;
        margin-right: 10px;
        width: 16px;
      }
      
      /* CVV tooltip */
      .cvv-help {
        position: relative;
        cursor: help;
      }
      
      .cvv-tooltip {
        position: absolute;
        top: -80px;
        right: 0;
        background: #333;
        color: white;
        padding: 10px;
        border-radius: 5px;
        font-size: 12px;
        width: 200px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1000;
      }
      
      .cvv-help:hover .cvv-tooltip {
        opacity: 1;
        visibility: visible;
      }
      
      /* Form validation styles */
      .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
      }
      
      .is-valid {
        border-color: #28a745 !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
      }
      
      /* SweetAlert custom styles */
      .swal-wide {
        width: 600px !important;
      }
      
      .swal2-html-container {
        text-align: left !important;
      }

      .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
      }

      .summary-row:last-child {
        border-bottom: none;
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--primary-color);
      }

      /* Discount Row Styling */
      .discount-row {
        background: linear-gradient(135deg, #d4edda, #c3e6cb) !important;
        border: 1px solid #28a745 !important;
        border-radius: 8px !important;
        margin: 8px 0 !important;
        padding: 12px 15px !important;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.1);
        animation: slideIn 0.3s ease-out;
      }

      .discount-row .text-success {
        color: #155724 !important;
        font-weight: 600;
      }

      .discount-row small {
        color: #155724 !important;
        opacity: 0.8;
      }

      @keyframes slideIn {
        from {
          opacity: 0;
          transform: translateY(-10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      .breadcrumb {
        background: transparent;
        padding: 0;
        margin-bottom: 20px;
      }

      .breadcrumb-item a {
        color: #0d6efd;
        text-decoration: none;
      }

      .breadcrumb-item.active {
        color: var(--dark-color);
      }

      @media (max-width: 768px) {
        .payment-container {
          padding: 20px 10px;
        }

        .step {
          width: 30px;
          height: 30px;
          margin: 0 10px;
          font-size: 14px;
        }

        .step::after {
          width: 20px;
          left: 40px;
        }
      }
    </style>
  </head>
  <body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
      <div class="container">
        <a class="navbar-brand" href="index.php">
          Velvet Vogue
        </a>
        <div class="d-flex align-items-center">
          <a href="featureProductView.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i> Continue Shopping
          </a>
          <div class="d-flex align-items-center">
            <i class="bi bi-shield-check text-success me-2"></i>
            <small class="text-muted">Secure Checkout</small>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="payment-container">
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item"><a href="./cart.php">Cart</a></li>
          <li class="breadcrumb-item active">Payment</li>
        </ol>
      </nav>

      <div class="row">
        <!-- Payment Form -->
        <div class="col-lg-8">
          <div class="payment-card">
            <div class="card-header">
              <h3 class="mb-0">Secure Payment</h3>
              <p class="mb-0">Complete your order with confidence</p>

              <!-- Progress Steps -->
              <div class="step-indicator">
                <div class="step completed">1</div>
                <div class="step completed">2</div>
                <div class="step active">3</div>
              </div>

              <div class="progress-bar-custom">
                <div class="progress-fill"></div>
              </div>
            </div>

            <div class="card-body p-4">
              <!-- Success/Error Messages -->
              <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($successMessage); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                
                <!-- Order Success Details -->
                <div class="card border-success mb-4">
                  <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Payment Successful!</h5>
                  </div>
                  <div class="card-body">
                    <p class="mb-3">Thank you for your order! Your payment has been processed successfully.</p>
                    <div class="row">
                      <div class="col-md-6">
                        <p><strong>Order Status:</strong> Confirmed</p>
                        <p><strong>Estimated Delivery:</strong> 3-5 business days</p>
                      </div>
                      <div class="col-md-6">
                        <p><strong>Payment Method:</strong> Secure Payment</p>
                        <p><strong>Order Processing:</strong> Complete</p>
                      </div>
                    </div>
                    <div class="mt-3">
                      <?php if ($is_logged_in): ?>
                        <a href="viewOrder.php?success=1" class="btn me-2" style="background-color: #003049; border-color: #003049; color: white;">View My Orders</a>
                        <a href="index.php" class="btn btn-outline-secondary">Continue Shopping</a>
                      <?php else: ?>
                        <a href="signIn.php" class="btn me-2" style="background-color: #003049; border-color: #003049; color: white;">Sign In to Track Orders</a>
                        <a href="index.php" class="btn btn-outline-secondary">Continue Shopping</a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
              
              <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <?php if (!$orderProcessed): ?>
                <!-- Only show form if order not processed -->
                <form id="paymentForm" method="POST" action="ProPayment.php">
                
                <!-- Hidden fields to preserve cart data during form submission -->
                <?php if (isset($_GET['cart_items'])): ?>
                <input type="hidden" name="cart_items" value="<?php echo htmlspecialchars($_GET['cart_items']); ?>">
                <?php endif; ?>
                <?php if (isset($_GET['quantities'])): ?>
                <input type="hidden" name="quantities" value="<?php echo htmlspecialchars($_GET['quantities']); ?>">
                <?php endif; ?>
                <?php if (isset($_GET['total'])): ?>
                <input type="hidden" name="cart_total" value="<?php echo htmlspecialchars($_GET['total']); ?>">
                <?php endif; ?>
                
                <!-- Billing Information -->
                <div class="mb-5">
                  <h5 class="mb-4">
                    <i class="bi bi-person-circle me-2"></i>Billing Information
                  </h5>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="firstName" class="form-label"
                        >First Name *</label
                      >
                      <input
                        type="text"
                        class="form-control"
                        id="firstName"
                        name="firstName"
                        required
                      />
                    </div>
                    <div class="col-md-6">
                      <label for="lastName" class="form-label"
                        >Last Name *</label
                      >
                      <input
                        type="text"
                        class="form-control"
                        id="lastName"
                        name="lastName"
                        required
                      />
                    </div>
                    <div class="col-12">
                      <label for="email" class="form-label"
                        >Email Address *</label
                      >
                      <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        required
                      />
                    </div>
                    <div class="col-12">
                      <label for="phone" class="form-label">Phone Number *</label>
                      <input
                        type="tel"
                        class="form-control"
                        id="phone"
                        name="phone"
                        placeholder="(123) 456-7890"
                        required
                      />
                    </div>
                    <div class="col-12">
                      <label for="address" class="form-label">Address *</label>
                      <input
                        type="text"
                        class="form-control"
                        id="address"
                        name="address"
                        placeholder="Street address"
                        required
                      />
                    </div>
                    <div class="col-md-6">
                      <label for="city" class="form-label">City *</label>
                      <input
                        type="text"
                        class="form-control"
                        id="city"
                        name="city"
                        required
                      />
                    </div>
                    <div class="col-md-3">
                      <label for="state" class="form-label">State *</label>
                      <select class="form-select" id="state" name="state" required>
                        <option value="">Choose...</option>
                        <option value="CA">California</option>
                        <option value="NY">New York</option>
                        <option value="TX">Texas</option>
                        <option value="FL">Florida</option>
                        <option value="IL">Illinois</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label for="zipCode" class="form-label">ZIP Code *</label>
                      <input
                        type="text"
                        class="form-control"
                        id="zipCode"
                        name="zipCode"
                        required
                      />
                    </div>
                  </div>
                </div>

                <!-- Payment Methods -->
                <div class="mb-5">
                  <h5 class="mb-4">
                    <i class="bi bi-credit-card me-2"></i>Payment Method
                  </h5>

                  <!-- Credit Card -->
                  <div
                    class="payment-method selected"
                    onclick="selectPaymentMethod('card')"
                  >
                    <div class="d-flex align-items-center">
                      <i class="bi bi-credit-card payment-icon"></i>
                      <div>
                        <h6 class="mb-1">Credit/Debit Card</h6>
                        <small class="text-muted"
                          >Visa, Mastercard, American Express</small
                        >
                      </div>
                      <div class="ms-auto">
                        <input
                          class="form-check-input"
                          type="radio"
                          name="paymentMethod"
                          value="card"
                          checked
                        />
                      </div>
                    </div>
                  </div>

                  <!-- PayPal -->
                  <div
                    class="payment-method"
                    onclick="selectPaymentMethod('paypal')"
                  >
                    <div class="d-flex align-items-center">
                      <i class="bi bi-paypal payment-icon"></i>
                      <div>
                        <h6 class="mb-1">PayPal</h6>
                        <small class="text-muted"
                          >Pay with your PayPal account</small
                        >
                      </div>
                      <div class="ms-auto">
                        <input
                          class="form-check-input"
                          type="radio"
                          name="paymentMethod"
                          value="paypal"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Apple Pay -->
                  <div
                    class="payment-method"
                    onclick="selectPaymentMethod('apple')"
                  >
                    <div class="d-flex align-items-center">
                      <i class="bi bi-apple payment-icon"></i>
                      <div>
                        <h6 class="mb-1">Apple Pay</h6>
                        <small class="text-muted">Touch ID or Face ID</small>
                      </div>
                      <div class="ms-auto">
                        <input
                          class="form-check-input"
                          type="radio"
                          name="paymentMethod"
                          value="apple"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Security Notice -->
                <div class="card-security-info">
                  <div class="secure-badge">
                    <i class="bi bi-shield-check"></i>
                    SSL Secured Payment
                  </div>
                  <h6><i class="bi bi-lock-fill me-2"></i>Your Payment is Secure</h6>
                  <ul class="security-features">
                    <li><i class="bi bi-check2"></i>256-bit SSL encryption</li>
                    <li><i class="bi bi-check2"></i>PCI DSS compliant</li>
                    <li><i class="bi bi-check2"></i>We never store full card numbers</li>
                    <li><i class="bi bi-check2"></i>Industry-standard security protocols</li>
                  </ul>
                </div>

                <!-- Card Details -->
                <div class="mb-5" id="cardDetails">
                  <h6 class="mb-3">Card Details</h6>
                  <div class="row g-3">
                    <div class="col-12">
                      <label for="cardNumber" class="form-label"
                        >Card Number *</label
                      >
                      <div class="card-input-group">
                        <input
                          type="text"
                          class="form-control"
                          id="cardNumber"
                          name="cardNumber"
                          placeholder="1234 5678 9012 3456"
                          maxlength="19"
                          required
                        />
                        <i
                          class="bi bi-credit-card card-type-icon"
                          id="cardTypeIcon"
                        ></i>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label for="expiryDate" class="form-label"
                        >Expiry Date *</label
                      >
                      <input
                        type="text"
                        class="form-control"
                        id="expiryDate"
                        name="expiryDate"
                        placeholder="MM/YY"
                        maxlength="5"
                        required
                      />
                    </div>
                    <div class="col-md-6">
                      <label for="cvv" class="form-label">
                        CVV * 
                        <span class="cvv-help">
                          <i class="bi bi-question-circle"></i>
                          <div class="cvv-tooltip">
                            <strong>What is CVV?</strong><br>
                            The 3-digit security code on the back of your card (4 digits for Amex on the front).
                          </div>
                        </span>
                      </label>
                      <input
                        type="text"
                        class="form-control"
                        id="cvv"
                        name="cvv"
                        placeholder="123"
                        maxlength="4"
                        required
                      />
                    </div>
                    <div class="col-12">
                      <label for="cardName" class="form-label"
                        >Name on Card *</label
                      >
                      <input
                        type="text"
                        class="form-control"
                        id="cardName"
                        name="cardName"
                        placeholder="John Doe"
                        required
                      />
                    </div>
                  </div>
                </div>

                <!-- Additional Options -->
                <div class="mb-4">
                  <div class="form-check mb-3">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="saveCard"
                    />
                    <label class="form-check-label" for="saveCard">
                      Save card for future purchases
                    </label>
                  </div>
                  <div class="form-check mb-3">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="sameAddress"
                      checked
                    />
                    <label class="form-check-label" for="sameAddress">
                      Shipping address same as billing address
                    </label>
                  </div>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="terms"
                      required
                    />
                    <label class="form-check-label" for="terms">
                      I agree to the
                      <a href="#" class="text-decoration-none"
                        >Terms and Conditions</a
                      >
                    </label>
                  </div>
                </div>

                <!-- Promo Code moved to Order Summary section -->

                <!-- Place Order Button -->
                <div class="d-grid mb-4">
                  <button
                    type="submit"
                    class="btn btn-place-order btn-lg"
                    name="place_order"
                  >
                    <i class="bi bi-lock-fill me-2"></i>Complete Secure Payment
                  </button>
                </div>

                <!-- Security Badge -->
                <div class="security-badge">
                  <i class="bi bi-shield-check"></i>
                  Your payment information is encrypted and secure
                </div>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
          <div class="order-summary">
            <h5 class="mb-4">
              <i class="bi bi-cart3 me-2"></i>Order Summary
            </h5>

            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
              <!-- Debug Information -->
              <div class="alert alert-info small mb-3">
                <strong>Debug Info:</strong><br>
                Cart Items from URL: <?php echo isset($_GET['cart_items']) ? $_GET['cart_items'] : 'None'; ?><br>
                Quantities: <?php echo isset($_GET['quantities']) ? $_GET['quantities'] : 'None'; ?><br>
                Items loaded: <?php echo count($displayCartItems); ?><br>
                Source: <?php echo isset($_GET['cart_items']) ? 'URL Parameters' : 'Sample from DB'; ?>
              </div>
            <?php endif; ?>

            <!-- Products (PHP-based cart display from MySQL database) -->
            <div id="orderSummaryItems">
              <?php if (!empty($displayCartItems)): ?>
                <?php foreach ($displayCartItems as $item): ?>
                  <div class="product-item" data-product-id="<?php echo $item['id']; ?>">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                         class="product-image"
                         onerror="console.log('Image failed to load:', '<?php echo htmlspecialchars($item['image']); ?>'); this.src='./Images/default-product.jpg';"
                         onload="console.log('Image loaded successfully:', '<?php echo htmlspecialchars($item['image']); ?>');">
                    <div class="product-info">
                      <div class="product-name" title="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php echo htmlspecialchars($item['name']); ?>
                      </div>
                      <div class="product-details">Qty: <?php echo $item['quantity']; ?></div>
                      <div class="product-details">Price: $<?php echo number_format($item['price'], 2); ?></div>
                      <div class="product-details text-muted small">ID: <?php echo $item['id']; ?></div>
                    </div>
                    <div class="product-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-center text-muted py-4">
                  <?php if ($orderProcessed): ?>
                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    <p class="mt-2 text-success">Order placed successfully!</p>
                  <?php else: ?>
                    <i class="bi bi-cart3" style="font-size: 2rem;"></i>
                    <p class="mt-2">Cart is empty</p>
                    <p class="small text-muted">Add items from the product catalog to see them here</p>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>

            <hr class="my-4" />

            <!-- Summary Calculations (PHP-based using unified function) -->
            <!-- DEBUG: Subtotal=<?php echo $displaySubtotal; ?>, Shipping=<?php echo $displayShipping; ?>, Tax=<?php echo $displayTax; ?>, Total=<?php echo $displayTotal; ?> -->
            <div class="summary-row">
              <span>Subtotal:</span>
              <span id="summarySubtotal">$<?php echo number_format($displaySubtotal, 2); ?></span>
            </div>
            <div class="summary-row">
              <span>Shipping:</span>
              <span id="summaryShipping"><?php echo $displayShipping > 0 ? '$' . number_format($displayShipping, 2) : 'Free'; ?></span>
            </div>
            <div class="summary-row">
              <span>Tax (8%):</span>
              <span id="summaryTax">$<?php echo number_format($displayTax, 2); ?></span>
            </div>
            <hr />
            <div class="summary-row">
              <strong>
                <span>Total:</span>
                <span id="summaryTotal">$<?php echo number_format($displayTotal, 2); ?></span>
              </strong>
            </div>

            <!-- Trust Badges -->
            <div class="text-center mt-4">
              <small class="text-muted d-block mb-2">We accept:</small>
              <div class="d-flex justify-content-center gap-3">
                <i
                  class="bi bi-credit-card text-primary"
                  style="font-size: 1.5rem"
                ></i>
                <i
                  class="bi bi-paypal text-primary"
                  style="font-size: 1.5rem"
                ></i>
                <i
                  class="bi bi-apple text-primary"
                  style="font-size: 1.5rem"
                ></i>
                <i
                  class="bi bi-shield-check text-success"
                  style="font-size: 1.5rem"
                ></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0 text-center">
            <div class="w-100">
              <i
                class="bi bi-check-circle text-success"
                style="font-size: 4rem"
              ></i>
              <h4 class="modal-title mt-3">Order Placed Successfully!</h4>
            </div>
          </div>
          <div class="modal-body text-center">
            <p class="mb-3">
              Thank you for your purchase. Your order has been confirmed and
              will be processed shortly.
            </p>
            <div class="alert alert-info">
              <strong>Order #VV-2024-001</strong><br />
              Confirmation email sent to your address
            </div>
            <p class="text-muted small">
              Estimated delivery: 3-5 business days
            </p>
          </div>
          <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-primary" onclick="goToHome()">
              Continue Shopping
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick="viewOrder()"
            >
              View Order
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.all.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
      // Cart data preservation and initialization
      document.addEventListener('DOMContentLoaded', function() {
        console.log('=== PAYMENT PAGE INITIALIZED ===');
        
        // Get cart data from localStorage and send to PHP session
        const cartData = localStorage.getItem('velvetVogueCart');
        if (cartData) {
          try {
            const parsedCart = JSON.parse(cartData);
            console.log('Cart data found in localStorage:', parsedCart);
            
            // Store cart data in a hidden form field for standard form submission
            let cartInput = document.getElementById('hiddenCartData');
            if (!cartInput) {
              cartInput = document.createElement('input');
              cartInput.type = 'hidden';
              cartInput.id = 'hiddenCartData';
              cartInput.name = 'hidden_cart_data';
              const form = document.getElementById('paymentForm');
              if (form) form.appendChild(cartInput);
            }
            cartInput.value = cartData;
            
            console.log('Cart data stored in hidden form field');
          } catch (error) {
            console.error('Error parsing cart data:', error);
          }
        } else {
          console.log('No cart data found in localStorage');
        }
      });
      
      // Payment method selection
      function selectPaymentMethod(method) {
        // Remove selected class from all payment methods
        document.querySelectorAll(".payment-method").forEach((pm) => {
          pm.classList.remove("selected");
        });

        // Add selected class to clicked method
        event.currentTarget.classList.add("selected");

        // Check the radio button
        document.querySelector(`input[value="${method}"]`).checked = true;

        // Show/hide card details
        const cardDetails = document.getElementById("cardDetails");
        if (method === "card") {
          cardDetails.style.display = "block";
        } else {
          cardDetails.style.display = "none";
        }
      }

      // Card number formatting
      document
        .getElementById("cardNumber")
        .addEventListener("input", function (e) {
          let value = e.target.value.replace(/\s/g, "").replace(/[^0-9]/gi, "");
          let formattedValue = value.match(/.{1,4}/g)?.join(" ") || "";
          e.target.value = formattedValue;

          // Update card type icon
          const cardTypeIcon = document.getElementById("cardTypeIcon");
          if (value.startsWith("4")) {
            cardTypeIcon.className = "bi bi-credit-card card-type-icon"; // Visa
          } else if (value.startsWith("5")) {
            cardTypeIcon.className = "bi bi-credit-card card-type-icon"; // Mastercard
          } else if (value.startsWith("3")) {
            cardTypeIcon.className = "bi bi-credit-card card-type-icon"; // Amex
          } else {
            cardTypeIcon.className = "bi bi-credit-card card-type-icon";
          }
        });

      // Expiry date formatting
      document
        .getElementById("expiryDate")
        .addEventListener("input", function (e) {
          let value = e.target.value.replace(/\D/g, "");
          if (value.length >= 2) {
            value = value.substring(0, 2) + "/" + value.substring(2, 4);
          }
          e.target.value = value;
        });

      // CVV input restriction
      document.getElementById("cvv").addEventListener("input", function (e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, "");
      });

      // Enhanced card validation
      function validateCard() {
        const cardNumber = document.getElementById("cardNumber").value.replace(/\s/g, "");
        const expiryDate = document.getElementById("expiryDate").value;
        const cvv = document.getElementById("cvv").value;
        const cardName = document.getElementById("cardName").value.trim();
        
        // Card number validation (Luhn algorithm)
        function isValidCardNumber(cardNumber) {
          if (cardNumber.length < 13 || cardNumber.length > 19) return false;
          
          let sum = 0;
          let isEven = false;
          
          for (let i = cardNumber.length - 1; i >= 0; i--) {
            let digit = parseInt(cardNumber.charAt(i), 10);
            
            if (isEven) {
              digit *= 2;
              if (digit > 9) {
                digit -= 9;
              }
            }
            
            sum += digit;
            isEven = !isEven;
          }
          
          return sum % 10 === 0;
        }
        
        // Expiry date validation
        function isValidExpiryDate(expiryDate) {
          const regex = /^(0[1-9]|1[0-2])\/([0-9]{2})$/;
          if (!regex.test(expiryDate)) return false;
          
          const [month, year] = expiryDate.split('/');
          const expiry = new Date(2000 + parseInt(year), parseInt(month) - 1);
          const now = new Date();
          
          return expiry > now;
        }
        
        // CVV validation
        function isValidCVV(cvv, cardNumber) {
          if (cardNumber.startsWith('3')) { // Amex
            return cvv.length === 4;
          } else {
            return cvv.length === 3;
          }
        }
        
        // Validate all fields
        const errors = [];
        
        if (!cardNumber || !isValidCardNumber(cardNumber)) {
          errors.push("Please enter a valid card number");
        }
        
        if (!expiryDate || !isValidExpiryDate(expiryDate)) {
          errors.push("Please enter a valid expiry date (MM/YY)");
        }
        
        if (!cvv || !isValidCVV(cvv, cardNumber)) {
          errors.push("Please enter a valid CVV");
        }
        
        if (!cardName || cardName.length < 2) {
          errors.push("Please enter the name as shown on your card");
        }
        
        return {
          isValid: errors.length === 0,
          errors: errors
        };
      }

      // Real-time card validation feedback
      document.getElementById("cardNumber").addEventListener("blur", function() {
        const cardNumber = this.value.replace(/\s/g, "");
        if (cardNumber && !validateCard().isValid) {
          this.classList.add("is-invalid");
        } else {
          this.classList.remove("is-invalid");
          this.classList.add("is-valid");
        }
      });
      
      document.getElementById("expiryDate").addEventListener("blur", function() {
        const validation = validateCard();
        if (this.value && validation.errors.some(err => err.includes("expiry"))) {
          this.classList.add("is-invalid");
        } else if (this.value) {
          this.classList.remove("is-invalid");
          this.classList.add("is-valid");
        }
      });
      
      document.getElementById("cvv").addEventListener("blur", function() {
        const validation = validateCard();
        if (this.value && validation.errors.some(err => err.includes("CVV"))) {
          this.classList.add("is-invalid");
        } else if (this.value) {
          this.classList.remove("is-invalid");
          this.classList.add("is-valid");
        }
      });

      // Form submission validation
      document.getElementById("paymentForm").addEventListener("submit", function(e) {
        e.preventDefault(); // Always prevent default first
        
        // Check all required billing fields
        const requiredFields = [
          { id: 'firstName', name: 'First Name' },
          { id: 'lastName', name: 'Last Name' },
          { id: 'email', name: 'Email Address' },
          { id: 'phone', name: 'Phone Number' },
          { id: 'address', name: 'Address' },
          { id: 'city', name: 'City' },
          { id: 'state', name: 'State' },
          { id: 'zipCode', name: 'ZIP Code' }
        ];
        
        const emptyFields = [];
        
        // Check each required field
        requiredFields.forEach(field => {
          const element = document.getElementById(field.id);
          const value = element.value.trim();
          
          if (!value) {
            emptyFields.push(field.name);
            element.classList.add('is-invalid');
          } else {
            element.classList.remove('is-invalid');
            element.classList.add('is-valid');
          }
        });
        
        // Check payment method
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;
        
        if (!paymentMethod) {
          emptyFields.push('Payment Method');
        }
        
        // Additional validation for card payment
        if (paymentMethod === "card") {
          const cardFields = [
            { id: 'cardNumber', name: 'Card Number' },
            { id: 'expiryDate', name: 'Expiry Date' },
            { id: 'cvv', name: 'CVV' },
            { id: 'cardName', name: 'Name on Card' }
          ];
          
          cardFields.forEach(field => {
            const element = document.getElementById(field.id);
            const value = element.value.trim();
            
            if (!value) {
              emptyFields.push(field.name);
              element.classList.add('is-invalid');
            }
          });
          
          // If card fields are filled, validate them
          if (emptyFields.filter(field => ['Card Number', 'Expiry Date', 'CVV', 'Name on Card'].includes(field)).length === 0) {
            const validation = validateCard();
            
            if (!validation.isValid) {
              Swal.fire({
                title: 'Please Fix Card Details',
                html: validation.errors.join('<br>'),
                icon: 'error',
                confirmButtonText: 'Fix Issues'
              });
              return false;
            }
          }
        }
        
        // Show error if any required fields are empty
        if (emptyFields.length > 0) {
          let message = '<strong>Please fill in the following required fields:</strong><br><br>';
          message += emptyFields.map(field => `• ${field}`).join('<br>');
          
          Swal.fire({
            title: 'Please Complete the Form',
            html: message,
            icon: 'warning',
            confirmButtonText: 'Continue Filling',
            customClass: {
              popup: 'swal-wide'
            }
          });
          
          // Focus on first empty field
          const firstEmptyField = requiredFields.find(field => !document.getElementById(field.id).value.trim());
          if (firstEmptyField) {
            document.getElementById(firstEmptyField.id).focus();
          }
          
          return false;
        }
        
        // Show loading message and submit form
        Swal.fire({
          title: 'Processing Payment...',
          html: 'Please wait while we securely process your payment.',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        // Submit the form after a brief delay
        setTimeout(() => {
          this.submit();
        }, 1000);
      });

      // Real-time validation for required fields
      const requiredFieldIds = ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'state', 'zipCode'];
      
      requiredFieldIds.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        
        field.addEventListener('blur', function() {
          if (this.value.trim()) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
          } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
          }
        });
        
        field.addEventListener('input', function() {
          if (this.classList.contains('is-invalid') && this.value.trim()) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
          }
        });
      });
      
      // Email validation
      document.getElementById('email').addEventListener('blur', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value.trim() && !emailRegex.test(this.value)) {
          this.classList.add('is-invalid');
          this.classList.remove('is-valid');
        } else if (this.value.trim()) {
          this.classList.remove('is-invalid');
          this.classList.add('is-valid');
        }
      });
      
      // Phone validation (basic format check)
      document.getElementById('phone').addEventListener('input', function() {
        // Allow only numbers, spaces, hyphens, parentheses, and plus sign
        this.value = this.value.replace(/[^0-9\s\-\(\)\+]/g, '');
      });

      // Initialize form - show which fields are required
      document.addEventListener('DOMContentLoaded', function() {
        // Add required indicators to labels
        const requiredLabels = document.querySelectorAll('label[for]');
        requiredLabels.forEach(label => {
          if (label.textContent.includes('*')) {
            label.style.fontWeight = '600';
          }
        });
        
        // Show card details by default since card is selected
        const cardDetails = document.getElementById("cardDetails");
        const selectedPayment = document.querySelector('input[name="paymentMethod"]:checked');
        if (selectedPayment && selectedPayment.value === 'card') {
          cardDetails.style.display = "block";
        }
        
        // Add form validation helper text
        const form = document.getElementById('paymentForm');
        const helpText = document.createElement('div');
        helpText.innerHTML = `
          <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Required Fields:</strong> All fields marked with an asterisk (*) must be completed before placing your order.
          </div>
        `;
        form.insertBefore(helpText, form.firstChild);
      });

      // Process payment with SweetAlert confirmation
      function processPayment() {
        const form = document.getElementById("paymentForm");
        const paymentMethod = document.querySelector(
          'input[name="paymentMethod"]:checked'
        ).value;

        // Basic form validation
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }

        // Additional validation for card payment
        if (paymentMethod === "card") {
          const validation = validateCard();
          
          if (!validation.isValid) {
            Swal.fire({
              title: 'Card Validation Error',
              html: validation.errors.join('<br>'),
              icon: 'error',
              confirmButtonText: 'Fix Issues'
            });
            return;
          }
              text: 'Please enter a valid card number',
              icon: 'error',
              confirmButtonColor: '#003049'
            });
            return;
          }

          if (!expiryDate || expiryDate.length < 5) {
            Swal.fire({
              title: 'Invalid Expiry Date',
              text: 'Please enter a valid expiry date',
              icon: 'error',
              confirmButtonColor: '#003049'
            });
            return;
          }

          if (!cvv || cvv.length < 3) {
            Swal.fire({
              title: 'Invalid CVV',
              text: 'Please enter a valid CVV',
              icon: 'error',
              confirmButtonColor: '#003049'
            });
            return;
          }

          if (!cardName.trim()) {
            Swal.fire({
              title: 'Name Required',
              text: 'Please enter the name on card',
              icon: 'error',
              confirmButtonColor: '#003049'
            });
            return;
          }
        }

        // Get cart data
        let cartData = [];
        
        const sessionCartData = sessionStorage.getItem('checkoutCart');
        if (sessionCartData) {
          cartData = JSON.parse(sessionCartData);
        } else {
          cartData = JSON.parse(localStorage.getItem('cart') || '[]');
        }
        
        if (cartData.length === 0) {
          Swal.fire({
            title: 'Cart Empty',
            text: 'Your cart is empty',
            icon: 'warning',
            confirmButtonColor: '#003049'
          });
          return;
        }

        // Show confirmation dialog with SweetAlert
        Swal.fire({
          title: 'Confirm Your Order',
          text: 'Are you sure you want to place this order?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#003049',
          cancelButtonColor: '#6c757d',
          confirmButtonText: '<i class="bi bi-lock-fill me-2"></i>Yes, Place Order',
          cancelButtonText: 'Cancel',
          showLoaderOnConfirm: true,
          preConfirm: () => {
            return submitOrder(form, cartData);
          },
          allowOutsideClick: () => !Swal.isLoading()
        });
      }

      // Submit order function
      function submitOrder(form, cartData) {
        return new Promise((resolve, reject) => {
          const formData = new FormData(form);
          formData.append('cart_data', JSON.stringify(cartData));

          fetch('ProPayment.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Clear cart
              localStorage.removeItem('cart');
              sessionStorage.removeItem('checkoutCart');
              
              // Show success message
              Swal.fire({
                title: 'Payment Successful!',
                html: `
                  <div class="text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Order Placed Successfully!</h5>
                    <p class="text-muted">Order ID: #${data.order_id}</p>
                    <p class="text-muted">Billing ID: #${data.billing_id}</p>
                    <small class="text-success">Your billing information and purchase images have been saved.</small>
                  </div>
                `,
                icon: 'success',
                confirmButtonText: 'Continue Shopping',
                confirmButtonColor: '#003049',
                allowOutsideClick: false,
                allowEscapeKey: false
              }).then(() => {
                // Store order info in session for display on next page
                sessionStorage.setItem('orderSuccess', JSON.stringify({
                  order_id: data.order_id,
                  billing_id: data.billing_id,
                  message: data.message
                }));
                // Redirect logged-in users to orders page, others to homepage
                <?php if ($is_logged_in): ?>
                window.location.href = 'viewOrder.php?success=1';
                <?php else: ?>
                window.location.href = 'index.php';
                <?php endif; ?>
              });
              resolve(data);
            } else {
              Swal.fire({
                title: 'Payment Failed',
                text: data.message || 'Payment processing failed',
                icon: 'error',
                confirmButtonColor: '#003049'
              });
              reject(new Error(data.message));
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              title: 'Error',
              text: 'An error occurred while processing your payment',
              icon: 'error',
              confirmButtonColor: '#003049'
            });
            reject(error);
          });
        });
      }

      // Modal functions
      function goToHome() {
        window.location.href = "index.php";
      }

      function viewOrder() {
        alert("Redirecting to order details...");
        // In a real application, redirect to order tracking page
      }

      // Form auto-fill from billing info
      document
        .getElementById("sameAddress")
        .addEventListener("change", function () {
          if (this.checked) {
            // Auto-fill shipping address with billing address
            console.log("Using billing address for shipping");
          }
        });

      // Initialize tooltips
      document.addEventListener("DOMContentLoaded", function () {
        const tooltips = document.querySelectorAll(
          '[data-bs-toggle="tooltip"]'
        );
        tooltips.forEach((tooltip) => new bootstrap.Tooltip(tooltip));
      });

      // Real-time form validation
      document
        .querySelectorAll(".form-control, .form-select")
        .forEach((input) => {
          input.addEventListener("blur", function () {
            if (this.hasAttribute("required") && !this.value.trim()) {
              this.classList.add("is-invalid");
            } else {
              this.classList.remove("is-invalid");
              this.classList.add("is-valid");
            }
          });
        });
      
      // Global variables
      let cartItems = [];
      let cartTotal = 0;
      let selectedPaymentMethod = 'card';

      // Initialize page when document loads
      document.addEventListener("DOMContentLoaded", function () {
        console.log('=== PAYMENT PAGE LOADING ===');
        console.log('Document ready, initializing...');
        
        initializePage();
        loadCartItems();
        setupEventListeners();
        
        // Initialize Bootstrap tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach((tooltip) => new bootstrap.Tooltip(tooltip));
        
        console.log('=== PAYMENT PAGE INITIALIZED ===');
      });

      // Initialize page functionality
      function initializePage() {
        console.log('=== PAYMENT PAGE INITIALIZATION ===');
        
        // Check if coming from cart
        const urlParams = new URLSearchParams(window.location.search);
        const fromCart = urlParams.get('from_cart');
        
        if (fromCart === 'true') {
          showNotification('info', 'Cart loaded successfully!', 'Ready to proceed with payment');
        }
      }

      // Load cart items from localStorage (only if not already loaded from PHP)
      function loadCartItems() {
        try {
          // Check if we already have cart items displayed from PHP
          const orderSummaryItems = document.getElementById('orderSummaryItems');
          const phpCartItems = orderSummaryItems?.querySelector('.product-item');
          
          if (phpCartItems) {
            console.log('Cart items already loaded from PHP, skipping localStorage load');
            
            // Try to get cart data from sessionStorage for order processing
            const sessionCartData = sessionStorage.getItem('checkoutCart');
            if (sessionCartData) {
              cartItems = JSON.parse(sessionCartData);
              console.log('Cart items loaded from sessionStorage for order processing:', cartItems);
            }
            
            return;
          }
          
          // Fallback to localStorage if no PHP cart items
          const cartData = localStorage.getItem('cart');
          cartItems = cartData ? JSON.parse(cartData) : [];
          
          console.log('Cart items loaded from localStorage:', cartItems);
          console.log('Number of items:', cartItems.length);
          
          if (cartItems.length === 0) {
            showEmptyCartMessage();
            return;
          }
          
          displayCartItems();
          calculateCartTotal();
          
        } catch (error) {
          console.error('Error loading cart:', error);
          showNotification('error', 'Error loading cart', 'Please refresh the page');
        }
      }

      // Display cart items in the order summary
      function displayCartItems() {
        const orderSummaryItems = document.getElementById('orderSummaryItems');
        if (!orderSummaryItems) {
          console.error('orderSummaryItems element not found!');
          return;
        }
        
        console.log('Displaying cart items:', cartItems);
        
        // Clear existing content
        orderSummaryItems.innerHTML = '';
        
        // Add cart items
        cartItems.forEach((item, index) => {
          console.log(`Creating element for item ${index}:`, item);
          const productElement = createProductItemHTML(item);
          orderSummaryItems.appendChild(productElement);
        });
        
        console.log('Cart items displayed successfully');
      }

      // Create HTML for individual product item
      function createProductItemHTML(item) {
        console.log('Creating HTML for item:', item);
        
        const productDiv = document.createElement('div');
        productDiv.className = 'product-item';
        
        // Handle image path
        let imagePath = './Images/default-product.jpg'; // Default fallback
        if (item.image) {
          imagePath = item.image;
          // If image doesn't start with ./ or http, add ./
          if (!item.image.startsWith('./') && !item.image.startsWith('http')) {
            imagePath = './' + item.image;
          }
        }
        
        console.log('Using image path:', imagePath);
        
        const itemTotal = (item.price || 0) * (item.quantity || 1);
        
        productDiv.innerHTML = `
          <img src="${imagePath}" alt="${item.name || 'Product'}" class="product-image" 
               onerror="console.error('Image failed to load:', this.src); this.src='./Images/default-product.jpg';">
          <div class="product-info">
            <div class="product-name">${item.name || 'Unknown Product'}</div>
            <div class="product-details">Qty: ${item.quantity || 1}</div>
            <div class="product-details">Price: $${(item.price || 0).toFixed(2)}</div>
          </div>
          <div class="product-price">$${itemTotal.toFixed(2)}</div>
        `;
        
        return productDiv;
      }

      // Calculate and update cart total
      function calculateCartTotal() {
        let subtotal = 0;
        
        cartItems.forEach(item => {
          subtotal += (item.price || 0) * (item.quantity || 1);
        });
        
        const shipping = subtotal > 100 ? 0 : 10; // Free shipping over $100
        const tax = subtotal * 0.08; // 8% tax
        const discount = 0; // Can be calculated based on promo codes
        const total = subtotal + shipping + tax - discount;
        
        // Update summary display
        document.getElementById('summarySubtotal').textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById('summaryShipping').textContent = shipping > 0 ? `$${shipping.toFixed(2)}` : 'Free';
        document.getElementById('summaryTax').textContent = `$${tax.toFixed(2)}`;
        document.getElementById('summaryTotal').textContent = `$${total.toFixed(2)}`;
        
        cartTotal = total;
      }

      // Show empty cart message
      function showEmptyCartMessage() {
        const orderSummaryItems = document.getElementById('orderSummaryItems');
        if (orderSummaryItems) {
          orderSummaryItems.innerHTML = `
            <div class="text-center text-muted py-4">
              <i class="bi bi-cart3" style="font-size: 2rem;"></i>
              <p class="mt-2">Your cart is empty</p>
              <a href="index.php" class="btn btn-primary btn-sm">Continue Shopping</a>
            </div>
          `;
        }
      }

      // Update order totals in the UI
      function updateOrderTotals(subtotal, shipping, tax, total) {
        const subtotalEl = document.getElementById('orderSubtotal');
        const shippingEl = document.getElementById('orderShipping');
        const taxEl = document.getElementById('orderTax');
        const totalEl = document.getElementById('orderTotal');
        
        if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
        if (shippingEl) shippingEl.textContent = shipping === 0 ? 'Free' : `$${shipping.toFixed(2)}`;
        if (taxEl) taxEl.textContent = `$${tax.toFixed(2)}`;
        if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;
      }

      // Show empty cart message
      function showEmptyCartMessage() {
        showNotification('warning', 'Your cart is empty', 'Please add some products before proceeding to checkout');
        
        setTimeout(() => {
          window.location.href = 'index.php';
        }, 3000);
      }

      // Setup event listeners
      function setupEventListeners() {
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
          method.addEventListener('click', function() {
            selectPaymentMethod(this.dataset.method);
          });
        });

        // Form validation
        document.querySelectorAll(".form-control, .form-select").forEach((input) => {
          input.addEventListener("blur", validateField);
        });

        // Email validation
        const emailField = document.getElementById("email");
        if (emailField) {
          emailField.addEventListener("blur", validateEmail);
        }

        // Card number formatting
        const cardNumberField = document.getElementById("cardNumber");
        if (cardNumberField) {
          cardNumberField.addEventListener("input", formatCardNumber);
        }

        // Expiry date formatting
        const expiryField = document.getElementById("expiryDate");
        if (expiryField) {
          expiryField.addEventListener("input", formatExpiryDate);
        }
      }

      // Payment method selection
      function selectPaymentMethod(method) {
        selectedPaymentMethod = method;
        
        // Remove selected class from all payment methods
        document.querySelectorAll(".payment-method").forEach((pm) => {
          pm.classList.remove("selected");
        });

        // Add selected class to clicked method
        document.querySelector(`[data-method="${method}"]`).classList.add("selected");

        // Update hidden input
        const methodInput = document.querySelector('input[name="paymentMethod"]');
        if (methodInput) {
          methodInput.value = method;
        }

        // Show/hide card details
        const cardDetails = document.getElementById("cardDetails");
        if (cardDetails) {
          cardDetails.style.display = method === "card" ? "block" : "none";
        }
      }

      // Process order
      function processOrder() {
        if (cartItems.length === 0) {
          showNotification('error', 'Cart is empty', 'Please add items to your cart first');
          return;
        }

        // Show loading
        Swal.fire({
          title: 'Processing Order...',
          text: 'Please wait while we process your payment',
          icon: 'info',
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          }
        });

        // Validate form
        const form = document.getElementById("paymentForm");
        if (!form.checkValidity()) {
          Swal.close();
          form.reportValidity();
          return;
        }

        // Additional validation for card payment
        if (selectedPaymentMethod === "card" && !validateCardDetails()) {
          Swal.close();
          return;
        }

        // Collect form data
        const formData = new FormData(form);
        const customerData = {
          name: formData.get('fullName'),
          email: formData.get('email'),
          phone: formData.get('phone'),
          address: `${formData.get('address')}, ${formData.get('city')}, ${formData.get('state')} ${formData.get('zipCode')}`
        };

        const paymentData = {
          method: selectedPaymentMethod,
          cardNumber: selectedPaymentMethod === 'card' ? formData.get('cardNumber') : null,
          cardName: selectedPaymentMethod === 'card' ? formData.get('cardName') : null
        };

        // Send AJAX request to process order
        fetch('ProPayment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'process_order',
            cart_data: JSON.stringify(cartItems),
            customer_data: JSON.stringify(customerData),
            payment_data: JSON.stringify(paymentData)
          })
        })
        .then(response => response.json())
        .then(data => {
          Swal.close();
          
          if (data.success) {
            // Clear cart
            localStorage.removeItem('velvetVogueCart');
            localStorage.removeItem('cartLastUpdated');
            
            // Show success message
            Swal.fire({
              title: 'Payment Successful!',
              text: `Your order #${data.order_id} has been placed successfully. Thank you for shopping with Velvet Vogue!`,
              icon: 'success',
              confirmButtonText: 'View Order Details',
              confirmButtonColor: '#003049',
              allowOutsideClick: false
            }).then((result) => {
              if (result.isConfirmed) {
                // Redirect logged-in users to orders page, others to homepage
                <?php if ($is_logged_in): ?>
                window.location.href = 'viewOrder.php?success=1';
                <?php else: ?>
                window.location.href = 'signIn.php?redirect=viewOrder.php';
                <?php endif; ?>
              }
            });
            
          } else {
            showNotification('error', 'Payment Failed', data.message || 'An error occurred while processing your payment');
          }
        })
        .catch(error => {
          Swal.close();
          console.error('Error:', error);
          showNotification('error', 'Network Error', 'Please check your connection and try again');
        });
      }

      // Validate card details
      function validateCardDetails() {
        const cardNumber = document.getElementById("cardNumber").value.replace(/\s/g, "");
        const expiryDate = document.getElementById("expiryDate").value;
        const cvv = document.getElementById("cvv").value;
        const cardName = document.getElementById("cardName").value;

        if (!cardNumber || cardNumber.length < 13) {
          showNotification('error', 'Invalid Card Number', 'Please enter a valid card number');
          return false;
        }

        if (!expiryDate || expiryDate.length < 5) {
          showNotification('error', 'Invalid Expiry Date', 'Please enter a valid expiry date (MM/YY)');
          return false;
        }

        if (!cvv || cvv.length < 3) {
          showNotification('error', 'Invalid CVV', 'Please enter a valid CVV code');
          return false;
        }

        if (!cardName || cardName.trim().length < 2) {
          showNotification('error', 'Invalid Card Name', 'Please enter the name on the card');
          return false;
        }

        return true;
      }

      // Field validation
      function validateField() {
        if (this.hasAttribute("required") && !this.value.trim()) {
          this.classList.add("is-invalid");
        } else {
          this.classList.remove("is-invalid");
          this.classList.add("is-valid");
        }
      }

      // Email validation
      function validateEmail() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value && !emailRegex.test(this.value)) {
          this.classList.add("is-invalid");
        } else if (this.value) {
          this.classList.remove("is-invalid");
          this.classList.add("is-valid");
        }
      }

      // Format card number
      function formatCardNumber() {
        let value = this.value.replace(/\s/g, '').replace(/\D/g, '');
        value = value.substring(0, 16);
        value = value.replace(/(.{4})/g, '$1 ').trim();
        this.value = value;
      }

      // Format expiry date
      function formatExpiryDate() {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 2) {
          value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        this.value = value;
      }

      // Show notifications using SweetAlert
      function showNotification(type, title, text, timer = 4000) {
        const iconTypes = {
          success: 'success',
          error: 'error',
          warning: 'warning',
          info: 'info'
        };

        Swal.fire({
          title: title,
          text: text,
          icon: iconTypes[type] || 'info',
          timer: timer,
          timerProgressBar: true,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        });
      }

      // Navigate functions
      function goToHome() {
        window.location.href = 'index.php';
      }

      function goToCart() {
        window.location.href = 'cart.php';
      }

      // Make functions available globally
      window.selectPaymentMethod = selectPaymentMethod;
      window.processOrder = processOrder;
      window.goToHome = goToHome;
      window.goToCart = goToCart;

    </script>
  </body>
</html>

