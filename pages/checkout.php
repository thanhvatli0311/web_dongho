<?php
ob_start();
session_start();
include '../includes/db.php';
include '../templates/header.php';
?>

<style>
</style>

<?php
// Kiểm tra đăng nhập trước khi vào trang thanh toán
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Lấy thông tin khách hàng
$check_customer_sql = "SELECT * FROM tbkhachhang WHERE username = ?";
$stmt = $conn->prepare($check_customer_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$customer_result = $stmt->get_result();

if ($customer_result->num_rows == 0) {
    echo '<div class="container my-5"><div class="alert alert-warning text-center">Khách hàng không tồn tại trong hệ thống!</div></div>';
    exit;
}

$customer = $customer_result->fetch_assoc();
$makhach = $customer['makhach'];

// Kiểm tra giỏ hàng có rỗng không
if (empty($_SESSION['cart'])) {
    echo '<div class="container my-5"><div class="alert alert-warning text-center">Giỏ hàng của bạn đang trống!</div></div>';
    exit;
}

// Tính tổng giá trị đơn hàng
$total_price = 0;
$has_checked_items = false;

foreach ($_SESSION['cart'] as $item_id => $item) {
    if (!isset($item['checked']) || $item['checked'] !== true) {
        continue;
    }

    $has_checked_items = true;

    $product_sql = "SELECT dongia FROM tbmathang WHERE mahang = ?";
    $stmt = $conn->prepare($product_sql);
    $stmt->bind_param("s", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) continue;

    $quantity = (int)$item['quantity'];
    $unit_price = (float)$product['dongia'];
    $total_price += $unit_price * $quantity;
}

if (!$has_checked_items) {
    echo '<div class="container my-5"><div class="alert alert-warning text-center">Vui lòng chọn ít nhất một sản phẩm để thanh toán!</div></div>';
    exit;
}

// Tạo mã đơn hàng
$micro = str_replace('.', '', microtime(true));
$order_id = 'DH' . $micro . rand(100, 999);

$conn->begin_transaction();

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {

        if ($total_price <= 0) {
            $conn->rollback();
            echo '<div class="container my-5"><div class="alert alert-warning text-center">Không có sản phẩm hợp lệ được chọn để thanh toán!</div></div>';
            exit;
        }

        // Lưu đơn hàng
        $sql_order = "INSERT INTO tbdonhang (madonhang, makhach, ngaymua, tinhtrang) VALUES (?, ?, NOW(), 'Đang xử lý')";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->bind_param("ss", $order_id, $makhach);
        $stmt_order->execute();

        $index = 1;
        $items_to_keep_in_cart = [];

        foreach ($_SESSION['cart'] as $item_id => $item) {
            if (!isset($item['checked']) || $item['checked'] !== true) {
                $items_to_keep_in_cart[$item_id] = $item;
                continue;
            }

            $currentMicro = str_replace('.', '', microtime(true));
            $product_code = strtoupper(trim($item_id));

            $product_sql = "SELECT dongia, tenhang FROM tbmathang WHERE mahang = ?";
            $stmt_product = $conn->prepare($product_sql);
            $stmt_product->bind_param("s", $product_code);
            $stmt_product->execute();
            $product_result = $stmt_product->get_result();
            $product = $product_result->fetch_assoc();

            if (!$product) continue;

            $quantity = (int)$item['quantity'];
            $unit_price = (float)$product['dongia'];
            $machitiet = 'CTDH' . $currentMicro . sprintf('%03d', $index);

            $sql_detail = "INSERT INTO tbchitietdonhang (machitiet, madonhang, mahang, soluong, dongia) VALUES (?, ?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($sql_detail);
            $stmt_detail->bind_param("sssid", $machitiet, $order_id, $product_code, $quantity, $unit_price);
            $stmt_detail->execute();

            $index++;
        }

        $conn->commit();
        ?>
        <div class="checkout-container">
            <div class="checkout-card">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Đơn hàng của bạn đã được xác nhận!<br>
                    Giá trị đơn hàng: <?php echo number_format($total_price, 2); ?> VND
                </div>
                <a href="index.php" class="btn btn-primary btn-continue">
                    <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
                </a>
            </div>
        </div>
        <?php
        $_SESSION['cart'] = $items_to_keep_in_cart;
        if (empty($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }

    } else {
        ?>
        <div class="checkout-container">
            <div class="checkout-card">
                <p class="order-value">Đơn hàng của bạn có giá trị là: <?php echo number_format($total_price, 2); ?> VND</p>
                <p>Bạn có muốn xác nhận đặt đơn hàng?</p>
                <form method="post">
                    <button type="submit" name="confirm_payment" class="btn btn-success btn-confirm">
                        <i class="fas fa-credit-card"></i> Xác nhận đặt hàng
                    </button>
                </form>
            </div>
        </div>
        <?php
    }
} catch (Exception $e) {
    $conn->rollback();
    echo '<div class="container my-5"><div class="alert alert-danger text-center">
    <i class="fas fa-exclamation-triangle"></i> Lỗi khi tạo đơn hàng: ' . $e->getMessage() . '</div></div>';
}

include '../templates/footer.php';
?>
