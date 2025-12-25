<!-- Common Customer JavaScript includes -->
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.all.min.js"></script>

<!-- Custom Scripts -->
<script src="main.js"></script>

<script>
    // Cart and Wishlist functionality
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];

    // Update cart and wishlist counters
    function updateCounters() {
        document.querySelector('.cart-counter').textContent = cart.length;
        document.querySelector('.wishlist-counter').textContent = wishlist.length;
    }

    // Add to cart functionality
    function addToCart(productId, productName = 'Product', price = 0) {
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: productId,
                name: productName,
                price: price,
                quantity: 1
            });
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCounters();
        
        Swal.fire({
            icon: 'success',
            title: 'Added to Cart!',
            text: `${productName} has been added to your cart.`,
            showConfirmButton: false,
            timer: 1500
        });
    }

    // Add to wishlist functionality
    function addToWishlist(productId, productName = 'Product') {
        const existingItem = wishlist.find(item => item.id === productId);
        
        if (!existingItem) {
            wishlist.push({
                id: productId,
                name: productName
            });
            
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            updateCounters();
            
            Swal.fire({
                icon: 'success',
                title: 'Added to Wishlist!',
                text: `${productName} has been added to your wishlist.`,
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            Swal.fire({
                icon: 'info',
                title: 'Already in Wishlist',
                text: `${productName} is already in your wishlist.`,
                showConfirmButton: false,
                timer: 1500
            });
        }
    }

    // Event listeners for add to cart buttons
    document.addEventListener('DOMContentLoaded', function() {
        updateCounters();
        
        // Add to cart buttons
        document.querySelectorAll('.add-to-cart-btn, .add-to-cart-btn-overlay').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId || 'unknown';
                const productCard = this.closest('.product-card') || this.closest('.card');
                const productName = productCard?.querySelector('.product-title, .card-title')?.textContent || 'Product';
                const priceElement = productCard?.querySelector('.current-price, .h5.text-primary');
                const price = priceElement ? parseFloat(priceElement.textContent.replace('$', '')) : 0;
                
                addToCart(productId, productName, price);
            });
        });
        
        // Add to wishlist buttons
        document.querySelectorAll('.favorite-btn, .favorite-btn-overlay').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId || 'unknown';
                const productCard = this.closest('.product-card') || this.closest('.card');
                const productName = productCard?.querySelector('.product-title, .card-title')?.textContent || 'Product';
                
                addToWishlist(productId, productName);
            });
        });
    });

    // Search functionality
    document.getElementById('searchInput')?.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        // Add your search logic here
    });
</script>