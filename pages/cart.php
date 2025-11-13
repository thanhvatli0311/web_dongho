<?php
ob_start(); 
session_start();
// Sử dụng require_once để đảm bảo file db.php được nhúng thành công và biến $pdo được tạo
require_once '../includes/db.php'; 
include '../templates/header.php';

// Thêm mặt hàng vào giỏ (từ GET - Cần cảnh báo: nên dùng POST cho hành động này để tránh CSRF)
if (isset($_GET['add_to_cart'])) {
    $item_id = $_GET['add_to_cart'];
    $quantity = 1;
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] += $quantity;
    } else {
        // Mặc định khi thêm mới, sản phẩm được chọn thanh toán
        $_SESSION['cart'][$item_id] = array('quantity' => $quantity, 'checked' => true);
    }
    header("Location: cart.php");
    exit;
}

// Xóa mặt hàng khỏi giỏ (từ GET - Cần cảnh báo: nên dùng POST cho hành động này để tránh CSRF)
if (isset($_GET['remove_from_cart'])) {
    $item_id = $_GET['remove_from_cart'];
    unset($_SESSION['cart'][$item_id]);
    header("Location: cart.php");
    exit;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" 
      integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2DhKNd3T/s9s2rzt5CfBmRZJ9IcnTE9jxQlQlMkHOMfJfI8N7d19S8k58G5FVhUXA==" 
      crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
    /* CSS code remains unchanged */
    .cart-container {
        max-width: 900px;
        margin: 20px auto;
        padding: 0 15px;
    }
    .cart-title {
        text-align: center;
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #333;
    }
    .cart-item {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 15px;
        padding: 15px;
    }
    /* Cột checkbox bên trái */
    .checkbox-column {
        flex: 0 0 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .checkbox-column input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    /* Cột nội dung sản phẩm */
    .item-content {
        flex: 1;
        display: flex;
        align-items: center;
    }
    .item-content img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        margin-right: 15px;
    }
    .item-details h3 {
        font-size: 20px;
        margin: 0;
        color: #333;
    }
    .item-details p {
        margin: 5px 0;
        font-size: 16px;
        color: #777;
    }
    /* Cột thao tác (số lượng & nút xoá) bên phải */
    .actions-column {
        flex: 0 0 150px;
        text-align: center;
    }
    .quantity-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
    }
    .quantity-controls button {
        background: #3498db;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .quantity-controls button:hover {
        background: #2980b9;
    }
    .quantity-controls input[type="number"] {
        width: 70px;
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
        text-align: center;
        margin: 0 10px;
        font-size: 16px;
        appearance: textfield;
    }
    .quantity-controls input[type="number"]::-webkit-inner-spin-button, 
    .quantity-controls input[type="number"]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    .btn-remove {
        color: #e74c3c;
        font-size: 28px;
        text-decoration: none;
        transition: color 0.3s;
    }
    .btn-remove:hover {
        color: #c0392b;
    }
    .cart-summary {
        text-align: center;
        margin-top: 30px;
    }
    .cart-summary p {
        font-size: 20px;
        font-weight: bold;
        color: #333;
    }
    .btn-checkout {
        display: inline-block;
        background: #27ae60;
        color: #fff;
        padding: 12px 20px;
        font-size: 18px;
        border-radius: 5px;
        text-decoration: none;
        margin-top: 15px;
        transition: background 0.3s;
    }
    .btn-checkout:hover {
        background: #219150;
    }
    /* Khung giỏ hàng trống */
    .empty-cart-container {
        border: 1px dashed #ccc;
        padding: 30px;
        text-align: center;
        min-height: 200px;
        border-radius: 10px;
        background: #f9f9f9;
    }
</style>

<div class="cart-container">
    <h2 class="cart-title"><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h2>
    <?php if (!empty($_SESSION['cart'])): ?>
        <?php
            $total_price = 0;
            // Thay đổi code truy vấn CSDL từ MySQLi sang PDO
            foreach ($_SESSION['cart'] as $item_id => $item):
                $product_sql = "SELECT * FROM tbmathang WHERE mahang = ?";
                
                // >>> FIX 1: Dùng $pdo thay vì $conn
                $stmt = $pdo->prepare($product_sql); 
                
                // >>> FIX 2: Dùng PDO execute với mảng tham số
                // Tham số $item_id là biến string, nên không cần cast type
                $stmt->execute([$item_id]); 
                
                // >>> FIX 3: Dùng PDO fetch() để lấy kết quả (PDO::FETCH_ASSOC đã là mặc định)
                $product = $stmt->fetch();
                
                if (!$product) continue;
                
                $quantity = (int)$item['quantity'];
                $unit_price = (float)$product['dongia'];
                $price = $unit_price * $quantity;
                
                // Nếu tồn tại trạng thái checkbox trong session, dùng nó; nếu không mặc định true
                $checked = isset($item['checked']) ? (bool)$item['checked'] : true;
                
                // >>> FIX LỖI LOGIC: Chỉ cộng tổng nếu sản phẩm được chọn thanh toán
                if ($checked) {
                    $total_price += $price;
                }
                
                $image = isset($product['hinhanh']) && !empty($product['hinhanh']) ? $product['hinhanh'] : 'placeholder.png';
        ?>
            <div class="cart-item" data-item-id="<?php echo htmlspecialchars($item_id); ?>" data-unit-price="<?php echo $unit_price; ?>">
                <div class="checkbox-column">
                    <input type="checkbox" class="product-check" id="check-<?php echo htmlspecialchars($item_id); ?>" 
                            data-item-id="<?php echo htmlspecialchars($item_id); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                </div>
                <div class="item-content">
                    <a href="product_detail.php?mahang=<?php echo urlencode($item_id); ?>" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                        <img src="../assets/images/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['tenhang']); ?>">
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($product['tenhang']); ?></h3>
                            <p>Đơn giá: <?php echo number_format($unit_price, 0); ?> VND</p>
                            <p>Tổng: <span class="product-subtotal" id="subtotal-<?php echo htmlspecialchars($item_id); ?>">
                                <?php echo number_format($price, 0); ?>
                            </span> VND</p>
                        </div>
                    </a>
                </div>
                <div class="actions-column">
                    <div class="quantity-controls">
                        <button type="button" class="qty-decrease" data-target="qty-<?php echo htmlspecialchars($item_id); ?>">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="qty-<?php echo htmlspecialchars($item_id); ?>" 
                                name="quantities[<?php echo htmlspecialchars($item_id); ?>]" 
                                value="<?php echo $quantity; ?>" min="1" data-unit-price="<?php echo $unit_price; ?>">
                        <button type="button" class="qty-increase" data-target="qty-<?php echo htmlspecialchars($item_id); ?>">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <a href="cart.php?remove_from_cart=<?php echo htmlspecialchars($item_id); ?>" class="btn-remove" title="Xóa sản phẩm">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="cart-summary">
            <p> Tổng giá trị đơn hàng: <span id="overall-total"><?php echo number_format($total_price, 0); ?></span> VND</p>
            <a href="checkout.php" class="btn-checkout">
                <i class="fas fa-solid fa-credit-card"></i> Đặt hàng
            </a>
        </div>
    <?php else: ?>
        <div class="empty-cart-container">
            <p style="font-size: 18px; color: #777;">🛒 Giỏ hàng của bạn đang trống!</p>
        </div>
    <?php endif; ?>
</div>

<script>
    // Javascript code remains unchanged
    // Hàm định dạng số tiền theo chuẩn Việt Nam
    function formatCurrency(value) {
        return parseInt(value).toLocaleString('vi-VN');
    }
    
    // Gọi AJAX cập nhật session cho 1 sản phẩm
    function updateCartSession(itemId, quantity, checked) {
        fetch('update_cart_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                item_id: itemId,
                quantity: quantity,
                checked: checked
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                console.error('Lỗi cập nhật session:', data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Tính lại thành tiền của 1 sản phẩm dựa vào số lượng và đơn giá
    function recalcSubtotal(itemId) {
        var qtyInput = document.getElementById('qty-' + itemId);
        var unitPrice = parseFloat(qtyInput.getAttribute('data-unit-price'));
        var quantity = parseInt(qtyInput.value) || 0;
        var subtotal = unitPrice * quantity;
        document.getElementById('subtotal-' + itemId).innerText = formatCurrency(subtotal);
    }
    
    // Tính lại tổng giỏ hàng từ các sản phẩm được chọn
    function recalcOverallTotal() {
        var overallTotal = 0;
        document.querySelectorAll('.cart-item').forEach(function(item) {
            var itemId = item.getAttribute('data-item-id');
            var checkbox = document.getElementById('check-' + itemId);
            if (checkbox && checkbox.checked) {
                var qtyInput = document.getElementById('qty-' + itemId);
                var unitPrice = parseFloat(qtyInput.getAttribute('data-unit-price'));
                var quantity = parseInt(qtyInput.value) || 0;
                overallTotal += unitPrice * quantity;
            }
        });
        document.getElementById('overall-total').innerText = formatCurrency(overallTotal);
    }
    
    // Cập nhật thành tiền của sản phẩm và tổng giỏ hàng, đồng thời lưu thay đổi vào session qua AJAX
    function updateCalculations(itemId) {
        var qtyInput = document.getElementById('qty-' + itemId);
        var quantity = parseInt(qtyInput.value) || 0;
        var checkbox = document.getElementById('check-' + itemId);
        var checked = checkbox.checked;
        recalcSubtotal(itemId);
        recalcOverallTotal();
        updateCartSession(itemId, quantity, checked);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý nút giảm số lượng
        document.querySelectorAll('.qty-decrease').forEach(function(button) {
            button.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                var currentValue = parseInt(input.value) || 1;
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                    var itemId = targetId.replace('qty-', '');
                    updateCalculations(itemId);
                }
            });
        });
        
        // Xử lý nút tăng số lượng
        document.querySelectorAll('.qty-increase').forEach(function(button) {
            button.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                input.value = parseInt(input.value) + 1;
                var itemId = targetId.replace('qty-', '');
                updateCalculations(itemId);
            });
        });
        
        // Xử lý khi nhập trực tiếp số lượng
        document.querySelectorAll('.quantity-controls input[type="number"]').forEach(function(input) {
            input.addEventListener('input', function() {
                var itemId = this.getAttribute('id').replace('qty-', '');
                updateCalculations(itemId);
            });
        });
        
        // Xử lý thay đổi trạng thái checkbox
        document.querySelectorAll('.product-check').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var itemId = this.getAttribute('data-item-id');
                updateCalculations(itemId);
            });
        });
    });
</script>

<?php include '../templates/footer.php'; ?>