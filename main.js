// =================== VELVET VOGUE E-COMMERCE MAIN JS ===================
// Professional E-commerce Cart & Navigation System

// =================== CART FUNCTIONALITY ===================

// Initialize cart functionality on page load
$(document).ready(function () {
  initializeCartFunctionality();
  initializeProductNavigation();
});

// Main cart initialization function
function initializeCartFunctionality() {
  initializeCartCount();
  setupAddToCartButtons();
  setupCartAnimations();
}

// Initialize cart count from localStorage
function initializeCartCount() {
  const cart = getCartFromStorage();
  const totalCount = cart.reduce(
    (sum, item) => sum + parseInt(item.quantity),
    0
  );
  updateCartDisplay(totalCount);
}

// Get cart data from localStorage
function getCartFromStorage() {
  try {
    return JSON.parse(localStorage.getItem("velvetVogueCart") || "[]");
  } catch (e) {
    console.error("Error reading cart from storage:", e);
    return [];
  }
}

// Save cart data to localStorage
function saveCartToStorage(cart) {
  try {
    localStorage.setItem("velvetVogueCart", JSON.stringify(cart));
    localStorage.setItem("cartLastUpdated", Date.now());
  } catch (e) {
    console.error("Error saving cart to storage:", e);
  }
}

// Setup add to cart button event listeners
function setupAddToCartButtons() {
  $(document).off("click", ".add-to-cart-btn, .add-to-cart-btn-overlay");
  $(document).on(
    "click",
    ".add-to-cart-btn, .add-to-cart-btn-overlay",
    function (e) {
      e.preventDefault();
      e.stopPropagation();
      handleAddToCart($(this));
    }
  );
}

// Handle add to cart button click
function handleAddToCart(button) {
  const productCard = button.closest(".product-card");
  const productId = button.data("product-id");

  if (!productId || !productCard.length) {
    showNotification("Error: Product not found", "error");
    return;
  }

  // Extract product details from card
  const productData = extractProductData(productCard, productId);

  if (!productData) {
    showNotification("Error: Could not get product details", "error");
    return;
  }

  // Show loading state
  showButtonLoading(button);

  // Add to cart with delay for UX
  setTimeout(() => {
    const result = addProductToCart(productData);

    if (result.success) {
      showButtonSuccess(button);
      updateCartDisplay(result.totalCount);
      showCartAnimation(button);
      // Notification removed as requested
    } else {
      showButtonError(button);
      showNotification(result.message, "error");
    }

    // Reset button after animation
    setTimeout(() => resetButton(button), 2000);
  }, 500);
}

// Extract product data from product card
function extractProductData(productCard, productId) {
  try {
    console.log("Extracting product data for ID:", productId);
    console.log("Product card:", productCard);

    // First try to get data from the button's data attributes (more reliable)
    const addButton = productCard.find(
      ".add-to-cart-btn, .add-to-cart-btn-overlay"
    );
    console.log("Found add button:", addButton);

    if (addButton.length && addButton.data("product-name")) {
      const productData = {
        id: productId,
        name: addButton.data("product-name"),
        price: parseFloat(addButton.data("product-price")),
        priceText: `₹${parseFloat(addButton.data("product-price")).toFixed(2)}`,
        image: addButton.data("product-image") || "Images/default-product.jpg",
        quantity: 1,
        addedAt: Date.now(),
      };
      console.log(
        "Extracted product data from button attributes:",
        productData
      );
      return productData;
    }

    // Fallback to DOM element extraction
    const nameElement = productCard.find(".product-title");
    const priceElement = productCard.find(".current-price");
    const imageElement = productCard.find(".product-image");

    if (!nameElement.length || !priceElement.length) {
      return null;
    }

    const name = nameElement.text().trim();
    const priceText = priceElement.text().trim();
    const price = extractPriceFromText(priceText);
    const image = imageElement.length
      ? imageElement.attr("src")
      : "Images/default-product.jpg";

    return {
      id: productId,
      name: name,
      price: price,
      priceText: priceText,
      image: image,
      quantity: 1,
      addedAt: Date.now(),
    };
  } catch (e) {
    console.error("Error extracting product data:", e);
    return null;
  }
}

// Extract numeric price from price text
function extractPriceFromText(priceText) {
  const match = priceText.match(/[\d,]+\.?\d*/);
  return match ? parseFloat(match[0].replace(/,/g, "")) : 0;
}

// Add product to cart
function addProductToCart(productData) {
  try {
    console.log("Adding product to cart:", productData);
    let cart = getCartFromStorage();
    console.log("Current cart before adding:", cart);

    // Check if product already exists in cart
    const existingIndex = cart.findIndex((item) => item.id === productData.id);

    if (existingIndex > -1) {
      // Update quantity if product exists
      cart[existingIndex].quantity = parseInt(cart[existingIndex].quantity) + 1;
      cart[existingIndex].lastUpdated = Date.now();
      console.log("Updated existing product quantity");
    } else {
      // Add new product to cart
      cart.push(productData);
      console.log("Added new product to cart");
    }

    // Save updated cart
    saveCartToStorage(cart);
    console.log("Cart saved to localStorage:", cart);

    // Calculate total count
    const totalCount = cart.reduce(
      (sum, item) => sum + parseInt(item.quantity),
      0
    );

    return {
      success: true,
      totalCount: totalCount,
      message: "Product added successfully",
    };
  } catch (e) {
    console.error("Error adding product to cart:", e);
    return {
      success: false,
      message: "Failed to add product to cart",
    };
  }
}

// =================== BUTTON STATE MANAGEMENT ===================

// Show loading state on button
function showButtonLoading(button) {
  const originalText = button.data("original-text") || button.html();
  button.data("original-text", originalText);
  button.html('<i class="bx bx-loader-alt bx-spin me-2"></i>Adding...');
  button.prop("disabled", true);
  button
    .removeClass("btn-primary btn-outline-primary")
    .addClass("btn-secondary");
}

// Show success state on button
function showButtonSuccess(button) {
  button.html('<i class="bx bx-check me-2"></i>Added!');
  button
    .removeClass("btn-secondary btn-primary btn-outline-primary")
    .addClass("btn-success");
}

// Show error state on button
function showButtonError(button) {
  button.html('<i class="bx bx-x me-2"></i>Error!');
  button
    .removeClass("btn-secondary btn-primary btn-outline-primary")
    .addClass("btn-danger");
}

// Reset button to original state
function resetButton(button) {
  const originalText = button.data("original-text");
  if (originalText) {
    button.html(originalText);
    button.prop("disabled", false);
    button
      .removeClass("btn-success btn-danger btn-secondary")
      .addClass("btn-primary");
  }
}

// =================== CART DISPLAY & ANIMATIONS ===================

// Update cart count display
function updateCartDisplay(count) {
  const cartCounter = $(".cart-counter");

  if (cartCounter.length) {
    cartCounter.text(count);

    if (count > 0) {
      cartCounter.show();
      cartCounter.addClass("cart-pulse");
      setTimeout(() => cartCounter.removeClass("cart-pulse"), 600);
    } else {
      cartCounter.hide();
    }
  }
}

// Setup cart animation styles
function setupCartAnimations() {
  if (!$("#cart-animations").length) {
    $("head").append(`
            <style id="cart-animations">
                .cart-pulse {
                    animation: cartPulse 0.6s ease-in-out;
                }
                
                @keyframes cartPulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.3); }
                }
                
                .floating-item {
                    position: fixed;
                    z-index: 9999;
                    pointer-events: none;
                    font-size: 20px;
                    color: #28a745;
                    font-weight: bold;
                }
                
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    max-width: 300px;
                    padding: 12px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                    opacity: 0;
                    transform: translateX(100%);
                    transition: all 0.3s ease;
                }
                
                .notification.show {
                    opacity: 1;
                    transform: translateX(0);
                }
                
                .notification.success {
                    background-color: #28a745;
                }
                
                .notification.error {
                    background-color: #dc3545;
                }
                
                .product-card {
                    transition: transform 0.2s ease;
                    cursor: pointer;
                }
                
                .product-card:hover {
                    transform: translateY(-2px);
                }
            </style>
        `);
  }
}

// Show cart animation from button to cart icon
function showCartAnimation(button) {
  const cartIcon = $(".bx-cart").first();

  if (cartIcon.length && button.length) {
    const buttonOffset = button.offset();
    const cartOffset = cartIcon.offset();

    if (buttonOffset && cartOffset) {
      const floatingItem = $('<i class="bx bx-cart floating-item"></i>');

      floatingItem.css({
        left: buttonOffset.left + button.width() / 2,
        top: buttonOffset.top + button.height() / 2,
      });

      $("body").append(floatingItem);

      // Animate to cart
      floatingItem.animate(
        {
          left: cartOffset.left,
          top: cartOffset.top,
          opacity: 0,
        },
        800,
        function () {
          floatingItem.remove();
        }
      );
    }
  }
}

// =================== PRODUCT NAVIGATION ===================

// Initialize product navigation
function initializeProductNavigation() {
  setupProductCardNavigation();
}

// Setup product card click navigation
function setupProductCardNavigation() {
  $(document).off("click", ".product-card, .product-item");
  $(document).on("click", ".product-card, .product-item", function (e) {
    // Don't navigate if clicking on buttons
    if (
      $(e.target).closest(
        "button, .btn, .add-to-cart-btn, .add-to-cart-btn-overlay"
      ).length
    ) {
      return;
    }

    const productId =
      $(this).find(".add-to-cart-btn").data("product-id") ||
      $(this).find("[data-product-id]").data("product-id");

    if (productId) {
      navigateToProductDetail(productId);
    }
  });
}

// Navigate to product detail page
function navigateToProductDetail(productId) {
  if (productId) {
    // Add loading state to the page
    $("body").css("cursor", "wait");

    // Navigate to product detail page
    window.location.href = `proDetailPage.php?id=${productId}`;
  }
}

// =================== NOTIFICATION SYSTEM ===================

// Show notification message (disabled - no notifications)
function showNotification(message, type = "success", duration = 3000) {
  // Notification function disabled - no popups will appear
  return;
}

// =================== UTILITY FUNCTIONS ===================

// Get cart count
function getCartCount() {
  const cart = getCartFromStorage();
  return cart.reduce((sum, item) => sum + parseInt(item.quantity), 0);
}

// Get cart total value
function getCartTotal() {
  const cart = getCartFromStorage();
  return cart.reduce(
    (sum, item) => sum + parseFloat(item.price) * parseInt(item.quantity),
    0
  );
}

// Clear entire cart
function clearCart() {
  localStorage.removeItem("velvetVogueCart");
  localStorage.removeItem("cartLastUpdated");
  updateCartDisplay(0);
  showNotification("Cart cleared", "success");
}

// Remove item from cart
function removeFromCart(productId) {
  let cart = getCartFromStorage();
  cart = cart.filter((item) => item.id !== productId);
  saveCartToStorage(cart);

  const totalCount = cart.reduce(
    (sum, item) => sum + parseInt(item.quantity),
    0
  );
  updateCartDisplay(totalCount);

  return totalCount;
}

// =================== SEARCH & FILTER SUPPORT ===================

// Reinitialize cart functionality for dynamically loaded content
function reinitializeCart() {
  setupAddToCartButtons();
  setupProductCardNavigation();
  initializeCartCount();
}

// Export functions for global use
window.VelvetVogue = {
  cart: {
    add: addProductToCart,
    remove: removeFromCart,
    clear: clearCart,
    getCount: getCartCount,
    getTotal: getCartTotal,
    getItems: getCartFromStorage,
  },
  ui: {
    showNotification: showNotification,
    reinitialize: reinitializeCart,
  },
  navigation: {
    toProductDetail: navigateToProductDetail,
  },
};

console.log("Velvet Vogue E-commerce System Initialized ✓");
