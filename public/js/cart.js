document.addEventListener('DOMContentLoaded', () => {
    const cartContent = document.getElementById('cart-content');

    // Hàm tải và hiển thị giỏ hàng
    const loadCart = async () => {
        cartContent.innerHTML = '<p style="text-align: center;">Đang tải giỏ hàng...</p>';
        try {
            const response = await fetch('api/cart.php');
            const result = await response.json();

            if (result.success && Object.keys(result.cart).length > 0) {
                let totalAmount = 0;
                let cartHtml = `
                    <table class="cart-items-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                for (const productId in result.cart) {
                    const item = result.cart[productId];
                    const itemTotal = item.price * item.quantity;
                    totalAmount += itemTotal;

                    cartHtml += `
                        <tr data-product-id="${item.id}">
                            <td>
                                <img src="${item.image_url ? 'images/products/' + item.image_url : 'images/placeholder.jpg'}" alt="${escapeHTML(item.name)}" class="cart-item-image">
                                <span class="cart-item-name">${escapeHTML(item.name)}</span>
                            </td>
                            <td>${formatCurrency(item.price)}</td>
                            <td>
                                <div class="quantity-control">
                                    <button class="decrease-quantity-btn" data-product-id="${item.id}">-</button>
                                    <input type="number" value="${item.quantity}" min="1" class="item-quantity-input" data-product-id="${item.id}">
                                    <button class="increase-quantity-btn" data-product-id="${item.id}">+</button>
                                </div>
                            </td>
                            <td>${formatCurrency(itemTotal)}</td>
                            <td>
                                <button class="remove-item-btn" data-product-id="${item.id}">Xóa</button>
                            </td>
                        </tr>
                    `;
                }

                cartHtml += `
                        </tbody>
                    </table>
                    <div class="cart-summary">
                        <div class="cart-total">
                            Tổng cộng: <span>${formatCurrency(totalAmount)}</span>
                        </div>
                    </div>
                    <div class="checkout-actions">
                        <a href="checkout.html" class="btn-primary">Tiến hành thanh toán</a>
                    </div>
                `;
                cartContent.innerHTML = cartHtml;

                // Gắn sự kiện cho các nút điều khiển số lượng và nút xóa
                attachCartEventListeners();

            } else {
                cartContent.innerHTML = '<p style="text-align: center;">Giỏ hàng của bạn đang trống.</p>';
            }
            window.updateCartCount(); // Cập nhật số lượng trên header
        } catch (error) {
            console.error('Error loading cart:', error);
            cartContent.innerHTML = '<p style="text-align: center; color: red;">Đã xảy ra lỗi khi tải giỏ hàng.</p>';
        }
    };

    // Hàm gắn sự kiện cho các nút trong giỏ hàng
    const attachCartEventListeners = () => {
        document.querySelectorAll('.decrease-quantity-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const productId = e.target.dataset.productId;
                const input = document.querySelector(`.item-quantity-input[data-product-id="${productId}"]`);
                let newQuantity = parseInt(input.value) - 1;
                if (newQuantity < 1) newQuantity = 1; // Số lượng tối thiểu là 1
                await updateCartItem(productId, newQuantity);
            });
        });

        document.querySelectorAll('.increase-quantity-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const productId = e.target.dataset.productId;
                const input = document.querySelector(`.item-quantity-input[data-product-id="${productId}"]`);
                let newQuantity = parseInt(input.value) + 1;
                await updateCartItem(productId, newQuantity);
            });
        });

        document.querySelectorAll('.item-quantity-input').forEach(input => {
            input.addEventListener('change', async (e) => {
                const productId = e.target.dataset.productId;
                let newQuantity = parseInt(e.target.value);
                if (isNaN(newQuantity) || newQuantity < 1) {
                    newQuantity = 1; // Đảm bảo số lượng hợp lệ
                    e.target.value = 1;
                }
                await updateCartItem(productId, newQuantity);
            });
        });

        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const productId = e.target.dataset.productId;
                const confirmDelete = confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?');
                if (confirmDelete) {
                    await updateCartItem(productId, 0); // Đặt số lượng là 0 để xóa
                }
            });
        });
    };

    // Hàm cập nhật hoặc xóa sản phẩm trong giỏ hàng thông qua API
    const updateCartItem = async (productId, quantity) => {
        try {
            const method = quantity === 0 ? 'DELETE' : 'PUT';
            const response = await fetch('api/cart.php', {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: quantity })
            });
            const result = await response.json();
            showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                loadCart(); // Tải lại giỏ hàng để cập nhật UI
            }
        } catch (error) {
            console.error('Error updating cart item:', error);
            showToast('Đã xảy ra lỗi khi cập nhật giỏ hàng.', 'error');
        }
    };

    // Tải giỏ hàng khi trang được tải
    loadCart();
});