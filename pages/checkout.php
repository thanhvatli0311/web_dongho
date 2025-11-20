<?php
ob_start();
session_start();

require_once '../includes/db.php';
require_once '../includes/functions.php';
include '../templates/header.php';

?>
<style>
    /* ... (Giữ nguyên các style cũ) ... */
    /* Thêm style cho QR Code */
    .qr-code-img {
        max-width: 200px; /* Điều chỉnh kích thước QR cho phù hợp */
        width: 100%;
        height: auto;
        display: block;
        margin: 15px auto; /* Căn giữa */
        border: 1px solid #ddd;
        border-radius: 8px;
    }
    
    /* Tổng thể trang */
    body {
        background-color: #f8f9fa;
    }
    .checkout-page-container {
        display: flex;
        max-width: 1200px;
        margin: 50px auto;
        gap: 30px;
        font-family: Arial, sans-serif;
    }

    /* Cột thông tin khách hàng và thanh toán (bên trái) */
    .customer-info-column {
        flex: 2;
    }

    /* Cột tóm tắt đơn hàng (bên phải) */
    .order-summary-column {
        flex: 1;
        background-color: #fff;
        padding: 25px;
        border: 1px solid #e1e1e1;
        border-radius: 8px;
        height: fit-content;
    }

    /* Các khối chung */
    .checkout-section {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        border: 1px solid #e1e1e1;
        margin-bottom: 25px;
    }

    h2, h3 {
        margin-top: 0;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
    }

    /* Tóm tắt sản phẩm */
    .product-summary-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .product-summary-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .product-summary-thumbnail {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        margin-right: 15px;
        object-fit: cover;
        border: 1px solid #ddd;
    }

    .product-summary-info {
        flex-grow: 1;
    }

    .product-summary-info p {
        margin: 0;
        font-size: 14px;
        line-height: 1.4;
    }

    .product-summary-price {
        font-weight: bold;
        font-size: 14px;
        white-space: nowrap;
        margin-left: 10px;
    }

    /* Tóm tắt giá */
    .price-summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 16px;
    }

    .price-summary-row.total {
        font-weight: bold;
        font-size: 20px;
        color: #d9534f;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    /* Phương thức thanh toán */
    .payment-method-option {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        cursor: pointer;
        position: relative;
    }
    .payment-method-option input {
        margin-right: 10px;
    }
    .payment-method-option label {
        font-weight: bold;
        display: flex;
        align-items: center;
        width: 100%;
    }
    .payment-method-description {
        padding: 15px;
        background-color: #f7f7f7;
        margin-top: 10px;
        border-radius: 5px;
        font-size: 14px;
        display: none; /* Ẩn mặc định */
    }
    .payment-method-option input:checked + label + .payment-method-description {
        display: block; /* Hiện khi được chọn */
    }

    /* Nút bấm */
    .btn-confirm-order {
        width: 100%;
        padding: 15px;
        background-color: #ff7c1a;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s;
        margin-top: 15px;
    }

    .btn-confirm-order:hover {
        background-color: #e66a00;
    }
    
    .back-to-cart-link {
        display: inline-block;
        margin-top: 15px;
        color: #007bff;
        text-decoration: none;
    }

    /* Trang xác nhận đơn hàng thành công */
    .order-success-container {
        max-width: 650px;
        margin: 50px auto;
        padding: 30px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        text-align: center;
    }
    .order-success-icon {
        color: #28a745;
        font-size: 50px;
        margin-bottom: 20px;
    }
    .bank-info-box {
        background: #f7f7f7;
        padding: 20px;
        border-radius: 5px;
        text-align: left;
        margin: 25px 0;
        border: 1px solid #eee;
    }
    .bank-info-box p { margin: 8px 0; }

    /* Alert messages */
    .alert {
        padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; text-align: center;
    }
    .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
    .alert-warning { color: #8a6d3b; background-color: #fcf8e3; border-color: #faebcc; }
    .alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }

</style>

<?php
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

try {
    $check_customer_sql = "SELECT makhach FROM tbkhachhang WHERE username = ?";
    $stmt = $pdo->prepare($check_customer_sql);
    $stmt->execute([$username]);

    if ($stmt->rowCount() == 0) {
        echo '<div class="container my-5"><div class="alert alert-warning">Khách hàng không tồn tại!</div></div>';
        exit;
    }
    $customer = $stmt->fetch();
    $makhach = $customer['makhach'];

    if (empty($_SESSION['cart'])) {
        echo '<div class="container my-5"><div class="alert alert-warning">Giỏ hàng của bạn đang trống!</div></div>';
        exit;
    }

    $raw_total_price = 0;
    $has_checked_items = false;
    $checked_items = [];

    foreach ($_SESSION['cart'] as $item_id => $item) {
        if (!isset($item['checked']) || $item['checked'] !== true) {
            continue;
        }
        $has_checked_items = true;
        $product_sql = "SELECT tenhang, dongia, hinhanh FROM tbmathang WHERE mahang = ?";
        $stmt_product = $pdo->prepare($product_sql);
        $stmt_product->execute([$item_id]);
        $product = $stmt_product->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $quantity = (int)$item['quantity'];
            $unit_price = (float)$product['dongia'];
            $raw_total_price += $unit_price * $quantity;
            $checked_items[] = array_merge($product, ['quantity' => $quantity, 'mahang' => $item_id]);
        }
    }

    if (!$has_checked_items) {
        echo '<div class="container my-5"><div class="alert alert-warning">Vui lòng chọn sản phẩm để thanh toán!</div></div>';
        exit;
    }

    $coupon_code = $_SESSION['coupon_code'] ?? null;
    $discount_result = tinhGiaSauKhuyenMai($pdo, $coupon_code, $raw_total_price);
    $final_total_price = $discount_result['giacuoicung'];
    $total_discount = $discount_result['tonggiatrigiam'];
    
    if ($final_total_price <= 0) {
        echo '<div class="container my-5"><div class="alert alert-warning">Tổng thanh toán không hợp lệ (<= 0).</div></div>';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
        $payment_method = $_POST['payment_method'] ?? 'COD';
        $initial_tinhtrang = 'Đang xử lý';
        $initial_trangthaitt = ($payment_method == 'COD') ? 'Chưa thanh toán' : 'Chờ thanh toán';

        $pdo->beginTransaction();

        $order_id = 'DH' . str_replace('.', '', microtime(true)) . rand(100, 999);
        
        // CẬP NHẬT CÂU LỆNH INSERT ĐỂ LƯU makhuyenmai
        $sql_order = "INSERT INTO tbdonhang (madonhang, makhach, ngaymua, tinhtrang, phuongthuctt, trangthaitt, tongtiendonhang, makhuyenmai) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
        $stmt_order = $pdo->prepare($sql_order);
        
        // THÊM $coupon_code VÀO MẢNG THAM SỐ EXECUTE
        if (!$stmt_order->execute([$order_id, $makhach, $initial_tinhtrang, $payment_method, $initial_trangthaitt, $final_total_price, $coupon_code])) {
            $pdo->rollBack();
            echo '<div class="container my-5"><div class="alert alert-danger">Lỗi khi lưu đơn hàng.</div></div>';
            exit;
        }

        $index = 1;
        $items_to_keep_in_cart = [];
        foreach ($_SESSION['cart'] as $item_id => $item) {
            if (!isset($item['checked']) || $item['checked'] !== true) {
                $items_to_keep_in_cart[$item_id] = $item;
                continue;
            }
            
            $product_sql = "SELECT dongia FROM tbmathang WHERE mahang = ?";
            $stmt_product_price = $pdo->prepare($product_sql);
            $stmt_product_price->execute([$item_id]);
            $product_price = $stmt_product_price->fetch();

            if($product_price){
                $machitiet = 'CTDH' . str_replace('.', '', microtime(true)) . sprintf('%03d', $index++);
                $sql_detail = "INSERT INTO tbchitietdonhang (machitiet, madonhang, mahang, soluong, dongia) VALUES (?, ?, ?, ?, ?)";
                $stmt_detail = $pdo->prepare($sql_detail);
                
                if (!$stmt_detail->execute([$machitiet, $order_id, $item_id, $item['quantity'], $product_price['dongia']])) {
                    $pdo->rollBack();
                    echo '<div class="container my-5"><div class="alert alert-danger">Lỗi khi lưu chi tiết đơn hàng.</div></div>';
                    exit;
                }
            }
        }

        $pdo->commit();
        $_SESSION['cart'] = $items_to_keep_in_cart;
        if (empty($_SESSION['cart'])) unset($_SESSION['cart']);
        unset($_SESSION['coupon_code']);

        // Redirect hoặc hiển thị thông báo thành công
        if ($payment_method == 'ONLINE_CARD') {
            header("Location: payment_online.php?order_id=$order_id&amount=$final_total_price");
            exit;
        }

        $success_message = '';
        $next_action_html = '';

        if ($payment_method == 'COD') {
            $success_message = 'Đơn hàng của bạn đã được xác nhận. Chúng tôi sẽ sớm giao hàng. Vui lòng chuẩn bị tiền mặt khi nhận hàng.';
            $next_action_html = '<a href="index.php" class="btn-confirm-order" style="text-decoration:none; display:inline-block; width:auto; padding: 10px 30px;">Tiếp tục mua sắm</a>';
        } elseif ($payment_method == 'BANK_TRANSFER') {
            $success_message = 'Vui lòng chuyển khoản hoặc quét mã QR để hoàn tất đơn hàng. Đơn hàng sẽ được xử lý sau khi chúng tôi nhận được thanh toán.';
            
            // --- THÊM PHẦN HIỂN THỊ MÃ QR ---
            $qr_code_path = '../assets/images/qr_payment.jpg'; // ĐƯỜNG DẪN ẢNH MÃ QR CỦA BẠN
            // Bạn có thể thay thế bằng đường dẫn URL ảnh QR động nếu có
            
            $bank_info = "
                <div class='bank-info-box'>
                    <p class='text-danger' style='font-weight: bold; font-size: 1.1em; text-align: center;'>NỘI DUNG CHUYỂN KHOẢN: <span style='color: #d9534f;'>$order_id</span></p>
                    
                    <div style='text-align: center; margin: 15px 0;'>
                        <p style='margin-bottom: 5px; font-weight: bold;'>Quét mã QR để thanh toán nhanh:</p>
                        <img src='$qr_code_path' alt='Mã QR Thanh Toán' class='qr-code-img' onerror=\"this.style.display='none'\"> 
                        </div>
                    <hr>
                    <p><strong>Ngân hàng:</strong> Sacombank</p>
                    <p><strong>Số tài khoản:</strong> 050137759605</p>
                    <p><strong>Chủ tài khoản:</strong> Nguyễn Văn Tâm</p>
                    <p><strong>Số tiền cần thanh toán:</strong> <span style='color: #d9534f; font-weight: bold; font-size: 1.2em;'>" . number_format($final_total_price, 0) . " VND</span></p>
                </div>
            ";
            $next_action_html = $bank_info . '<div style="text-align: center;"><a href="index.php" class="btn-confirm-order" style="text-decoration:none; display:inline-block; width:auto; padding: 10px 30px;">Về trang chủ</a></div>';
        }

        ?>
        <div class="order-success-container">
            <i class="fas fa-check-circle order-success-icon"></i>
            <h2>Đặt hàng thành công!</h2>
            <p><?php echo $success_message; ?></p>
            <p>Mã đơn hàng của bạn là: <strong><?php echo $order_id; ?></strong></p>
            <?php echo $next_action_html; ?>
        </div>
        <?php

    } else {
    ?>
        <form method="post" class="checkout-page-container">
            <div class="customer-info-column">
                <div class="checkout-section">
                    <h3><i class="fas fa-money-check-alt"></i> Phương thức thanh toán</h3>
                    
                    <div class="payment-method-option">
                        <input type="radio" id="payment_cod" name="payment_method" value="COD" checked>
                        <label for="payment_cod"><i class="fas fa-truck" style="margin-right: 10px;"></i> Thanh toán khi nhận hàng (COD)</label>
                        <div class="payment-method-description">
                           Bạn sẽ thanh toán bằng tiền mặt trực tiếp cho nhân viên giao hàng khi nhận được sản phẩm.
                        </div>
                    </div>

                    <div class="payment-method-option">
                        <input type="radio" id="payment_bank" name="payment_method" value="BANK_TRANSFER">
                        <label for="payment_bank"><i class="fas fa-university" style="margin-right: 10px;"></i> Chuyển khoản ngân hàng</label>
                        <div class="payment-method-description">
                           Sau khi đặt hàng, bạn sẽ nhận được thông tin tài khoản và mã QR để chuyển khoản. Đơn hàng sẽ được xử lý ngay sau khi chúng tôi xác nhận đã nhận được tiền.
                        </div>
                    </div>

                     <div class="payment-method-option">
                        <input type="radio" id="payment_online" name="payment_method" value="ONLINE_CARD">
                        <label for="payment_online"><i class="fas fa-credit-card" style="margin-right: 10px;"></i> Thanh toán Online (Thẻ/Ví điện tử)</label>
                         <div class="payment-method-description">
                           An toàn và bảo mật. Bạn sẽ được chuyển hướng đến cổng thanh toán để hoàn tất.
                        </div>
                    </div>
                </div>

                <a href="cart.php" class="back-to-cart-link"><i class="fas fa-arrow-left"></i> Quay lại giỏ hàng</a>
            </div>

            <div class="order-summary-column">
                <h3>Tóm tắt đơn hàng</h3>
                <?php foreach ($checked_items as $item): ?>
                    <div class="product-summary-item">
                        <img src="../assets/images/<?php echo htmlspecialchars($item['hinhanh']); ?>" alt="<?php echo htmlspecialchars($item['tenhang']); ?>" class="product-summary-thumbnail">
                        <div class="product-summary-info">
                            <p><?php echo htmlspecialchars($item['tenhang']); ?></p>
                            <p style="color: #6c757d;">Số lượng: <?php echo $item['quantity']; ?></p>
                        </div>
                        <p class="product-summary-price"><?php echo number_format($item['dongia'] * $item['quantity'], 0); ?> đ</p>
                    </div>
                <?php endforeach; ?>

                <div class="price-summary">
                    <div class="price-summary-row">
                        <span>Tạm tính:</span>
                        <span><?php echo number_format($raw_total_price, 0); ?> đ</span>
                    </div>
                     <?php if ($total_discount > 0): ?>
                    <div class="price-summary-row" style="color: green;">
                        <span>Giảm giá:</span>
                        <span>-<?php echo number_format($total_discount, 0); ?> đ</span>
                    </div>
                    <?php endif; ?>
                    <div class="price-summary-row total">
                        <span>Tổng cộng:</span>
                        <span><?php echo number_format($final_total_price, 0); ?> đ</span>
                    </div>
                </div>
                
                <button type="submit" name="confirm_payment" class="btn-confirm-order">ĐẶT HÀNG</button>
            </div>
        </form>
    <?php
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo '<div class="container my-5"><div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div></div>';
}

include '../templates/footer.php';
?>