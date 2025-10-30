<?php
session_start();
include '../includes/db.php';


// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Lưu URL yêu cầu
    header("Location: login.php");
    exit;
}
include '../templates/header.php';
$username = $_SESSION['username'];

// Lấy thông tin khách hàng từ bảng tbKhachHang dựa trên username
$customer_sql = "SELECT * FROM tbKhachHang WHERE username = ?";
$stmt = $conn->prepare($customer_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$customer_result = $stmt->get_result();

if ($customer_result->num_rows == 0) {
    echo "Khách hàng không tồn tại trong hệ thống!";
    exit;
}

$customer = $customer_result->fetch_assoc();
$makhach = $customer['makhach']; 

// Lấy danh sách đơn hàng của khách hàng (sắp xếp theo ngày mua giảm dần)
$order_sql = "SELECT * FROM tbDonHang WHERE makhach = ? ORDER BY ngaymua DESC";
$stmt_order = $conn->prepare($order_sql);
$stmt_order->bind_param("s", $makhach);
$stmt_order->execute();
$order_result = $stmt_order->get_result();

echo "<h2 style='text-align: center; margin-bottom: 20px;'>Đơn hàng của bạn</h2>";

if ($order_result->num_rows > 0) {
    while ($order = $order_result->fetch_assoc()) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 15px auto; max-width: 400px; border-radius: 10px; background: #f9f9f9;'>";
        echo "<p><strong>Mã đơn hàng:</strong> " . htmlspecialchars($order['madonhang']) . "</p>";
        echo "<p><strong>Ngày mua:</strong> " . htmlspecialchars($order['ngaymua']) . "</p>";
        echo "<p><strong>Tình trạng:</strong> " . htmlspecialchars($order['tinhtrang']) . "</p>";
        
        // Tính tổng giá trị của đơn hàng từ bảng tbChiTietDonHang
        $total_sql = "SELECT SUM(dongia) AS total FROM tbChiTietDonHang WHERE madonhang = ?";
        $stmt_total = $conn->prepare($total_sql);
        $stmt_total->bind_param("s", $order['madonhang']);
        $stmt_total->execute();
        $total_result = $stmt_total->get_result();
        $total_row = $total_result->fetch_assoc();
        $total_amount = $total_row['total'];
        
        echo "<p><strong>Tổng giá trị:</strong> " . number_format($total_amount, 2) . " VND</p>";
        echo "</div>";
    }
} else {
    echo "<p style='text-align: center; font-size: 18px; color: #777;'>Bạn chưa có đơn hàng nào.</p>";
}

include '../templates/footer.php';
?>
