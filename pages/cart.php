<?php
ob_start(); 
session_start();
// Sử dụng require_once để đảm bảo file db.php được nhúng thành công và biến $pdo được tạo
require_once '../includes/db.php'; 
// NHÚNG FILE CHỨA HÀM KHUYẾN MÃI
require_once '../includes/functions.php'; 
include '../templates/header.php';

// Xử lý Thêm/Xóa mặt hàng (giữ nguyên)
if (isset($_GET['add_to_cart'])) {
    $item_id = $_GET['add_to_cart'];
    $quantity = 1;
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$item_id] = array('quantity' => $quantity, 'checked' => true);
    }
    header("Location: cart.php");
    exit;
}

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
    .cart-container { max-width: 900px; margin: 20px auto; padding: 0 15px; }
    .cart-title { text-align: center; font-size: 28px; font-weight: bold; margin-bottom: 20px; color: #333; }
    .cart-item { display: flex; align-items: center; border: 1px solid #ddd; border-radius: 10px; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 15px; padding: 15px; }
    .checkbox-column { flex: 0 0 40px; display: flex; align-items: center; justify-content: center; }
    .checkbox-column input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
    .item-content { flex: 1; display: flex; align-items: center; }
    .item-content img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; margin-right: 15px; }
    .item-details h3 { font-size: 20px; margin: 0; color: #333; }
    .item-details p { margin: 5px 0; font-size: 16px; color: #777; }
    .actions-column { flex: 0 0 150px; text-align: center; }
    .quantity-controls { display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
    .quantity-controls button { background: #3498db; color: #fff; border: none; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 16px; cursor: pointer; transition: background 0.3s; }
    .quantity-controls button:hover { background: #2980b9; }
    .quantity-controls input[type="number"] { width: 70px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: center; margin: 0 10px; font-size: 16px; appearance: textfield; }
    .quantity-controls input[type="number"]::-webkit-inner-spin-button, 
    .quantity-controls input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .btn-remove { color: #e74c3c; font-size: 28px; text-decoration: none; transition: color 0.3s; }
    .btn-remove:hover { color: #c0392b; }
    .cart-summary { text-align: center; margin-top: 30px; border-top: 2px solid #eee; padding-top: 20px;}
    .cart-summary p { font-size: 20px; font-weight: bold; color: #333; margin: 10px 0;}
    .btn-checkout { display: inline-block; background: #27ae60; color: #fff; padding: 12px 20px; font-size: 18px; border-radius: 5px; text-decoration: none; margin-top: 15px; transition: background 0.3s; }
    .btn-checkout:hover { background: #219150; }
    .empty-cart-container { border: 1px dashed #ccc; padding: 30px; text-align: center; min-height: 200px; border-radius: 10px; background: #f9f9f9; }
    .coupon-area { display: flex; justify-content: center; align-items: center; gap: 10px; margin: 15px 0; }
    .coupon-message { color: #e74c3c; font-size: 14px; margin-top: 5px; }
</style>

<div class="cart-container">
    <h2 class="cart-title"><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h2>
    <?php if (!empty($_SESSION['cart'])): ?>
        <?php
            $raw_total_price = 0;
            $coupon_code = $_SESSION['coupon_code'] ?? null; // Lấy mã KM từ session
            $products_in_cart = []; // Mảng lưu thông tin sản phẩm để dùng lại bên dưới
            
            // --- 1. Vòng lặp tính tổng giá trị gốc ---
            foreach ($_SESSION['cart'] as $item_id => $item):
                $product_sql = "SELECT * FROM tbmathang WHERE mahang = ?";
                $stmt = $pdo->prepare($product_sql); 
                $stmt->execute([$item_id]); 
                $product = $stmt->fetch();
                
                if (!$product) continue;
                
                $quantity = (int)$item['quantity'];
                $unit_price = (float)$product['dongia'];
                $price = $unit_price * $quantity;
                
                $checked = isset($item['checked']) ? (bool)$item['checked'] : true;
                
                if ($checked) {
                    $raw_total_price += $price; // Tính tổng giá trị GỐC của các sản phẩm được chọn
                }
                
                // Lưu thông tin sản phẩm để dùng cho phần hiển thị HTML
                $products_in_cart[$item_id] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'price' => $price,
                    'checked' => $checked
                ];
                
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
        <?php endforeach; 
        
        // --- 2. Gọi hàm tính toán khuyến mãi ---
        $discount_result = tinhGiaSauKhuyenMai($pdo, $coupon_code, $raw_total_price);
        $total_discount = $discount_result['tonggiatrigiam'];
        $final_total_price = $discount_result['giacuoicung'];
        ?>

        <input type="hidden" id="current-coupon-code-php" value="<?php echo htmlspecialchars($coupon_code ?? ''); ?>">

        <div class="cart-summary">
            <p>Tổng tiền hàng (chưa giảm): <span id="raw-total-display"><?php echo number_format($raw_total_price, 0); ?></span> VND</p>
            
            <div class="coupon-area">
                <input type="text" id="coupon-code-input" placeholder="Nhập mã khuyến mãi" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 200px;" value="<?php echo htmlspecialchars($coupon_code ?? ''); ?>">
                <button type="button" id="apply-coupon-btn" class="btn-checkout" style="padding: 8px 15px; margin: 0; background: #f39c12; font-size: 16px;">Áp dụng</button>
            </div>

            <p style="color: #e74c3c;">Giảm giá: <span id="discount-amount"><?php echo number_format($total_discount, 0); ?></span> VND</p>
            <p class="coupon-message" id="coupon-message-display" 
               style="color: <?php echo (strpos($discount_result['thongbao'] ?? '', 'thành công') !== false) ? '#27ae60' : '#e74c3c'; ?>; 
                      display: <?php echo $discount_result['thongbao'] ? 'block' : 'none'; ?>">
                <?php echo htmlspecialchars($discount_result['thongbao'] ?? ''); ?>
            </p>

            <p> **Tổng thanh toán:** <span id="overall-total"><?php echo number_format($final_total_price, 0); ?></span> VND</p>
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
    // Hàm định dạng số tiền theo chuẩn Việt Nam
    function formatCurrency(value) {
        // Đảm bảo là số nguyên trước khi định dạng
        return parseInt(value).toLocaleString('vi-VN');
    }
    
    /**
     * Gửi yêu cầu AJAX để cập nhật session và tính toán lại giá
     * * @param {string|null} itemId ID sản phẩm thay đổi (chỉ cần khi thay đổi số lượng/check)
     * @param {number|null} quantity Số lượng mới
     * @param {boolean|null} checked Trạng thái check mới
     * @param {string|null} couponCode Mã coupon muốn áp dụng
     * @param {string} action Hành động ('update_item' hoặc 'apply_coupon')
     */
    function updateCartAndRecalc(itemId, quantity, checked, couponCode, action) {
        
        // Nếu không phải hành động áp dụng coupon, lấy mã coupon hiện tại từ input
        if (action !== 'apply_coupon') {
             couponCode = document.getElementById('coupon-code-input').value.trim();
        }
        
        fetch('update_cart_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: action, 
                item_id: itemId,
                quantity: quantity,
                checked: checked,
                coupon_code: couponCode,
                update_coupon: action === 'apply_coupon' ? 1 : 0 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                
                // Cập nhật tổng phụ cho từng sản phẩm
                if (data.item_prices) {
                    for (const id in data.item_prices) {
                         const subtotalElement = document.getElementById('subtotal-' + id);
                         if(subtotalElement) {
                             subtotalElement.innerText = formatCurrency(data.item_prices[id]);
                         }
                    }
                }
                
                // Cập nhật tóm tắt giỏ hàng
                document.getElementById('raw-total-display').innerText = formatCurrency(data.raw_total_price);
                document.getElementById('discount-amount').innerText = formatCurrency(data.discount_amount);
                document.getElementById('overall-total').innerText = formatCurrency(data.final_total_price);
                
                // Hiển thị thông báo khuyến mãi
                const messageEl = document.getElementById('coupon-message-display');
                if (messageEl) {
                    if (data.coupon_message) {
                        messageEl.innerText = data.coupon_message;
                        messageEl.style.display = 'block';
                        // Đổi màu thông báo
                        if (data.coupon_message.includes('thành công')) {
                            messageEl.style.color = '#27ae60'; // Xanh lá
                        } else {
                            messageEl.style.color = '#e74c3c'; // Đỏ
                        }
                    } else {
                        messageEl.style.display = 'none'; 
                    }
                }
                
            } else {
                console.error('Lỗi cập nhật giỏ hàng:', data.message);
                alert('Có lỗi xảy ra: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Hàm điều phối chung cho thay đổi số lượng/checked
    function handleItemChange(itemId) {
        var qtyInput = document.getElementById('qty-' + itemId);
        var quantity = parseInt(qtyInput.value) || 0;
        var checkbox = document.getElementById('check-' + itemId);
        var checked = checkbox.checked;
        
        updateCartAndRecalc(itemId, quantity, checked, null, 'update_item');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Xử lý nút giảm số lượng
        document.querySelectorAll('.qty-decrease').forEach(function(button) {
            button.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                var currentValue = parseInt(input.value) || 1;
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                    var itemId = targetId.replace('qty-', '');
                    handleItemChange(itemId);
                }
            });
        });
        
        // 2. Xử lý nút tăng số lượng
        document.querySelectorAll('.qty-increase').forEach(function(button) {
            button.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                input.value = parseInt(input.value) + 1;
                var itemId = targetId.replace('qty-', '');
                handleItemChange(itemId);
            });
        });
        
        // 3. Xử lý khi nhập trực tiếp số lượng
        document.querySelectorAll('.quantity-controls input[type="number"]').forEach(function(input) {
            input.addEventListener('input', function() {
                if (parseInt(this.value) < 1 || isNaN(parseInt(this.value))) {
                    this.value = 1; 
                }
                var itemId = this.getAttribute('id').replace('qty-', '');
                handleItemChange(itemId);
            });
        });
        
        // 4. Xử lý thay đổi trạng thái checkbox
        document.querySelectorAll('.product-check').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var itemId = this.getAttribute('data-item-id');
                handleItemChange(itemId);
            });
        });

        // 5. Xử lý nút áp dụng khuyến mãi
        document.getElementById('apply-coupon-btn').addEventListener('click', function() {
            var couponCode = document.getElementById('coupon-code-input').value.trim();
            // Truyền null cho itemId, quantity, checked vì không thay đổi chúng, action là 'apply_coupon'
            updateCartAndRecalc(null, null, null, couponCode, 'apply_coupon'); 
        });

        // 6. Xử lý khi nhấn ENTER trong ô nhập mã khuyến mãi
        document.getElementById('coupon-code-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                document.getElementById('apply-coupon-btn').click();
            }
        });
    });
</script>

<?php include '../templates/footer.php'; ?>