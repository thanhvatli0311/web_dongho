<?php
ob_start();
session_start();
// Đảm bảo file db.php của bạn đã tạo biến $pdo
require_once '../includes/db.php'; 
include '../templates/header.php';
?>

<style>
/* CSS cho trang thanh toán */
.checkout-container {
    max-width: 600px;
    margin: 50px auto;
    padding: 20px;
    text-align: center;
}
.checkout-card {
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 30px;
    background: #fff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.order-value {
    font-size: 24px;
    font-weight: bold;
    color: #27ae60;
    margin-bottom: 20px;
}
.btn-confirm {
    background: #27ae60;
    color: white;
    padding: 10px 30px;
    border: none;
    border-radius: 5px;
    font-size: 18px;
    cursor: pointer;
    margin-top: 15px;
    transition: background 0.3s;
}
.btn-confirm:hover {
    background: #219150;
}
.btn-back {
    background: #95a5a6;
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
    display: inline-block;
}
.btn-back:hover {
    background: #7f8c8d;
}
.btn-continue {
    background: #3498db;
    color: white;
    padding: 10px 30px;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    margin-top: 20px;
}
/* Các style cho alert */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}
.alert-success {
    color: #3c763d;
    background-color: #dff0d8;
    border-color: #d6e9c6;
}
.alert-warning {
    color: #8a6d3b;
    background-color: #fcf8e3;
    border-color: #faebcc;
}
.alert-danger {
    color: #a94442;
    background-color: #f2dede;
    border-color: #ebccd1;
}
</style>

<?php
// Kiểm tra đăng nhập trước khi vào trang thanh toán
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

try {
    // 1. Lấy thông tin khách hàng (PDO)
    $check_customer_sql = "SELECT makhach FROM tbkhachhang WHERE username = ?";
    $stmt = $pdo->prepare($check_customer_sql);
    $stmt->execute([$username]);
    
    // PDO rowCount() thay thế cho num_rows
    if ($stmt->rowCount() == 0) {
        echo '<div class="container my-5"><div class="alert alert-warning text-center">Khách hàng không tồn tại trong hệ thống!</div></div>';
        exit;
    }

    // PDO fetch() thay thế cho fetch_assoc()
    $customer = $stmt->fetch();
    $makhach = $customer['makhach'];

    // 2. Kiểm tra giỏ hàng có rỗng không
    if (empty($_SESSION['cart'])) {
        echo '<div class="container my-5"><div class="alert alert-warning text-center">Giỏ hàng của bạn đang trống!</div></div>';
        exit;
    }

    // 3. Tính tổng giá trị đơn hàng (Sử dụng PDO)
    $total_price = 0;
    $has_checked_items = false;

    foreach ($_SESSION['cart'] as $item_id => $item) {
        // Chỉ tính các sản phẩm được chọn (checked)
        if (!isset($item['checked']) || $item['checked'] !== true) {
            continue;
        }

        $has_checked_items = true;

        $product_sql = "SELECT dongia FROM tbmathang WHERE mahang = ?";
        $stmt = $pdo->prepare($product_sql);
        $stmt->execute([$item_id]);
        $product = $stmt->fetch();

        if (!$product) continue;

        $quantity = (int)$item['quantity'];
        $unit_price = (float)$product['dongia'];
        $total_price += $unit_price * $quantity;
    }

    if (!$has_checked_items) {
        echo '<div class="container my-5"><div class="alert alert-warning text-center">Vui lòng chọn ít nhất một sản phẩm để thanh toán!</div></div>';
        exit;
    }

    // 4. Xử lý logic tạo và hiển thị đơn hàng
    
    // Tạo mã đơn hàng (Được giữ nguyên)
    $micro = str_replace('.', '', microtime(true));
    $order_id = 'DH' . $micro . rand(100, 999);

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
        
        // >>> BẮT ĐẦU TRANSACTION (PDO)
        $pdo->beginTransaction();

        if ($total_price <= 0) {
            $pdo->rollBack(); // PDO rollBack() thay thế $conn->rollback()
            echo '<div class="container my-5"><div class="alert alert-warning text-center">Không có sản phẩm hợp lệ được chọn để thanh toán!</div></div>';
            exit;
        }

        // 4.1. Lưu đơn hàng (INSERT tbdonhang - PDO)
        $sql_order = "INSERT INTO tbdonhang (madonhang, makhach, ngaymua, tinhtrang) VALUES (?, ?, NOW(), 'Đang xử lý')";
        $stmt_order = $pdo->prepare($sql_order);
        $stmt_order->execute([$order_id, $makhach]);

        $index = 1;
        $items_to_keep_in_cart = [];

        foreach ($_SESSION['cart'] as $item_id => $item) {
            if (!isset($item['checked']) || $item['checked'] !== true) {
                $items_to_keep_in_cart[$item_id] = $item;
                continue;
            }

            $currentMicro = str_replace('.', '', microtime(true));
            $product_code = strtoupper(trim($item_id));

            // 4.2. Lấy đơn giá lần nữa (để đảm bảo giá trị tại thời điểm đặt hàng) (PDO)
            $product_sql = "SELECT dongia FROM tbmathang WHERE mahang = ?";
            $stmt_product = $pdo->prepare($product_sql);
            $stmt_product->execute([$product_code]);
            $product = $stmt_product->fetch();

            if (!$product) continue;

            $quantity = (int)$item['quantity'];
            $unit_price = (float)$product['dongia'];
            $machitiet = 'CTDH' . $currentMicro . sprintf('%03d', $index);

            // 4.3. Lưu chi tiết đơn hàng (INSERT tbchitietdonhang - PDO)
            $sql_detail = "INSERT INTO tbchitietdonhang (machitiet, madonhang, mahang, soluong, dongia) VALUES (?, ?, ?, ?, ?)";
            $stmt_detail = $pdo->prepare($sql_detail);
            
            // Tham số PDO không cần chỉ định kiểu dữ liệu (sssid), chỉ cần mảng giá trị
            $stmt_detail->execute([$machitiet, $order_id, $product_code, $quantity, $unit_price]);

            $index++;
        }

        // >>> KẾT THÚC TRANSACTION (PDO)
        $pdo->commit(); 
        
        ?>
        <div class="checkout-container">
            <div class="checkout-card">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Đơn hàng của bạn đã được xác nhận!<br>
                    Giá trị đơn hàng: **<?php echo number_format($total_price, 0); ?> VND**
                </div>
                <a href="index.php" class="btn btn-primary btn-continue">
                    <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
                </a>
            </div>
        </div>
        <?php
        // Xóa các sản phẩm đã thanh toán khỏi giỏ hàng
        $_SESSION['cart'] = $items_to_keep_in_cart;
        if (empty($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }

    } else {
        // Hiển thị giao diện xác nhận đặt hàng
        ?>
        <div class="checkout-container">
            <div class="checkout-card">
                <p class="order-value">Đơn hàng của bạn có giá trị là: **<?php echo number_format($total_price, 0); ?> VND**</p>
                <p>Bạn có muốn xác nhận đặt đơn hàng?</p>
                <form method="post">
                    <button type="submit" name="confirm_payment" class="btn btn-success btn-confirm">
                        <i class="fas fa-credit-card"></i> Xác nhận đặt hàng
                    </button>
                </form>
                <p><a href="cart.php"><button type="button" class="btn-back">← Quay lại</button></a></p>
            </div>
        </div>
        <?php
    }
} catch (PDOException $e) { // Bắt lỗi PDO cụ thể
    // Nếu có lỗi, rollback transaction (PDO)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo '<div class="container my-5"><div class="alert alert-danger text-center">
    <i class="fas fa-exclamation-triangle"></i> Lỗi khi tạo đơn hàng: ' . $e->getMessage() . '</div></div>';
}

include '../templates/footer.php';
?>