<?php
session_start();
// Đảm bảo file này khởi tạo biến $pdo
include '../includes/db.php'; 

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Lưu URL yêu cầu
    header("Location: login.php");
    exit;
}

if (!isset($pdo)) {
    die("Lỗi: Biến \$pdo (kết nối CSDL) không được định nghĩa. Vui lòng kiểm tra file db.php.");
}

include '../templates/header.php';
$username = $_SESSION['username'];

// --- 1. Lấy thông tin khách hàng ---
try {
    $customer_sql = "SELECT makhach FROM tbKhachHang WHERE username = ?";
    $stmt_customer = $pdo->prepare($customer_sql);
    $stmt_customer->execute([$username]);
    $customer = $stmt_customer->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo "<p style='text-align: center; color: red;'>Khách hàng không tồn tại trong hệ thống!</p>";
        include '../templates/footer.php';
        exit;
    }
    $makhach = $customer['makhach']; 
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

// --- 2. Lấy danh sách đơn hàng ---
// CẬP NHẬT: Thêm cột tongtiendonhang vào câu SELECT
$order_sql = "SELECT madonhang, ngaymua, tinhtrang, tongtiendonhang FROM tbDonHang WHERE makhach = ? ORDER BY ngaymua DESC";
$stmt_order = $pdo->prepare($order_sql);
$stmt_order->execute([$makhach]);
$orders = $stmt_order->fetchAll(PDO::FETCH_ASSOC);

echo "<h2 style='text-align: center; margin-bottom: 20px;'>Đơn hàng của bạn</h2>";

if (count($orders) > 0) {
    foreach ($orders as $order) {
        $madonhang = $order['madonhang'];
        // Lấy tổng tiền trực tiếp từ CSDL (đã tính giảm giá lúc đặt hàng)
        $total_amount = $order['tongtiendonhang'];

        // --- 3. Lấy chi tiết sản phẩm ---
        $detail_sql = "
            SELECT 
                ctdh.mahang, ctdh.soluong, ctdh.dongia,
                mh.tenhang, mh.hinhanh
            FROM tbChiTietDonHang ctdh
            JOIN tbMathang mh ON ctdh.mahang = mh.mahang
            WHERE ctdh.madonhang = ?
        ";
        $stmt_detail = $pdo->prepare($detail_sql);
        $stmt_detail->execute([$madonhang]);
        $products = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);
        
        // (Bỏ đoạn tính toán lại tổng tiền bằng vòng lặp ở đây vì đã lấy từ tongtiendonhang)

        // Đặt màu cho trạng thái
        $status_class = '';
        if ($order['tinhtrang'] == 'Đã giao') $status_class = 'status-success';
        elseif ($order['tinhtrang'] == 'Đã hủy') $status_class = 'status-danger';
        else $status_class = 'status-default';
        
        echo "<div class='order-card'>";
        
        // Header Đơn hàng
        echo "<div class='order-header'>";
        echo "<div><p class='order-id'>Mã đơn hàng: <strong>" . htmlspecialchars($madonhang) . "</strong></p>";
        echo "<p class='order-date'>Ngày mua: " . date('d/m/Y H:i', strtotime($order['ngaymua'])) . "</p></div>";
        echo "<span class='order-status " . $status_class . "'>" . htmlspecialchars($order['tinhtrang']) . "</span>";
        echo "</div>"; // end order-header

        // Chi tiết sản phẩm
        echo "<div class='order-products'>";
        echo "<p class='section-title'>Sản phẩm đã mua:</p>";
        foreach ($products as $product) {
            echo "<div class='product-item'>";
            echo "<img src='../assets/images/" . htmlspecialchars($product['hinhanh']) . "' alt='" . htmlspecialchars($product['tenhang']) . "' class='product-img'>";
            echo "<div class='product-info'>";
            echo "<a href='product_detail.php?mahang=" . htmlspecialchars($product['mahang']) . "' class='product-name'>" . htmlspecialchars($product['tenhang']) . "</a>";
            echo "<p class='product-qty'>SL: " . htmlspecialchars($product['soluong']) . " x " . number_format($product['dongia'], 0) . " VND</p>";
            echo "</div>"; // end product-info

            // NÚT ĐÁNH GIÁ (CHỈ HIỂN THỊ NẾU ĐÃ GIAO)
            if ($order['tinhtrang'] == 'Đã giao') {
                echo "<a href='product_detail.php?mahang=" . htmlspecialchars($product['mahang']) . "#reviews' class='btn btn-review'><i class='fas fa-star'></i> Đánh giá</a>";
            }
            echo "</div>"; // end product-item
        }
        echo "</div>"; // end order-products

        // Footer Đơn hàng
        echo "<div class='order-footer'>";
        // HIỂN THỊ: Sử dụng biến $total_amount lấy từ cột tongtiendonhang
        echo "<p class='total-amount'>Tổng thanh toán: <strong>" . number_format($total_amount, 0) . " VND</strong></p>";
        echo "</div>"; // end order-footer
        
        echo "</div>"; // end order-card
    }
} else {
    echo "<p style='text-align: center; font-size: 18px; color: #777;'>Bạn chưa có đơn hàng nào.</p>";
}
?>

<style>
/* -------------------------------------- */
/* CSS Cho Trang Đơn Hàng Cập Nhật */
/* -------------------------------------- */

.order-card {
    border: 1px solid #ddd;
    padding: 15px 20px;
    margin: 15px auto;
    max-width: 700px; /* Tăng chiều rộng để hiển thị chi tiết tốt hơn */
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 10px;
    margin-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.order-id strong {
    color: #007bff;
}

.order-date {
    font-size: 0.9em;
    color: #777;
}

.order-status {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: 600;
}

.status-success { background-color: #d4edda; color: #155724; }
.status-danger { background-color: #f8d7da; color: #721c24; }
.status-default { background-color: #cce5ff; color: #004085; }

.order-products {
    margin-top: 15px;
    border-top: 1px dashed #ddd;
    padding-top: 15px;
}

.section-title {
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
}

.product-item:last-of-type {
    border-bottom: none;
}

.product-img {
    width: 65px;
    height: 65px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #eee;
}

.product-info {
    flex-grow: 1;
}

.product-name {
    font-weight: 500;
    color: #333;
    text-decoration: none;
    display: block;
    margin-bottom: 3px;
}

.product-name:hover {
    color: #007bff;
}

.product-qty {
    font-size: 0.9em;
    color: #666;
}

.btn-review {
    background-color: #F39C12;
    color: #fff;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 5px;
    font-size: 0.9em;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-review:hover {
    background-color: #E67E22;
}

.order-footer {
    padding-top: 10px;
    margin-top: 15px;
    border-top: 2px solid #ddd;
    text-align: right;
}

.total-amount {
    font-size: 1.1em;
    color: #dc3545;
}
</style>

<?php include '../templates/footer.php'; ?>