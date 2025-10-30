<?php
session_start();
include '../includes/db.php';

// Check admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

$madonhang = isset($_GET['madonhang']) ? trim($_GET['madonhang']) : '';
if ($madonhang == '') {
    echo '<div class="alert alert-danger">Không tìm thấy mã đơn hàng.</div>';
    exit;
}

// Lấy thông tin đơn hàng và khách hàng
$sql_donhang = "SELECT d.*, k.tenkhach, k.sodienthoai, k.diachi FROM tbDonHang d
                JOIN tbkhachhang k ON d.makhach = k.makhach
                WHERE d.madonhang = ?";
$stmt = $conn->prepare($sql_donhang);
$stmt->bind_param("s", $madonhang);
$stmt->execute();
$donhang = $stmt->get_result()->fetch_assoc();

if (!$donhang) {
    echo '<div class="alert alert-danger">Đơn hàng không tồn tại!</div>';
    exit;
}

// Lấy chi tiết đơn hàng + ảnh sản phẩm (lấy ảnh chính của mathang)
$sql_chitiet = "SELECT c.*, m.tenhang, m.dongia AS dongia_mathang, m.hinhanh
                FROM tbChiTietDonHang c
                JOIN tbmathang m ON c.mahang = m.mahang
                WHERE c.madonhang = ?";
$stmt2 = $conn->prepare($sql_chitiet);
$stmt2->bind_param("s", $madonhang);
$stmt2->execute();
$chitiet = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng <?= htmlspecialchars($madonhang) ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
    .order-head { margin-bottom: 20px; }
    .order-info, .customer-info { font-size: 16px; }
    .order-detail-table th { background: #2c3e50; color: #fff; }
    .order-detail-table img {
        width: 70px; height: 70px; object-fit: cover; border-radius: 8px; border: 1px solid #ccc;
        background: #fafbfc;
    }
    </style>
</head>
<body>
<div class="container mt-4">
    <a href="manage_orders.php" class="btn btn-secondary mb-3">&laquo; Quay lại</a>
    <h2 class="mb-3">Chi tiết đơn hàng: <span class="text-primary"><?= htmlspecialchars($madonhang) ?></span></h2>
    <div class="order-head row">
        <div class="col-md-6 order-info">
            <strong>Ngày mua:</strong> <?= htmlspecialchars($donhang['ngaymua']) ?><br>
            <strong>Tình trạng:</strong> <?= htmlspecialchars($donhang['tinhtrang']) ?>
        </div>
        <div class="col-md-6 customer-info">
            <strong>Khách hàng:</strong> <?= htmlspecialchars($donhang['tenkhach']) ?><br>
            <strong>SĐT:</strong> <?= htmlspecialchars($donhang['sodienthoai']) ?><br>
            <strong>Địa chỉ:</strong> <?= htmlspecialchars($donhang['diachi']) ?>
        </div>
    </div>
    <table class="table table-bordered order-detail-table">
        <thead>
            <tr>
                
                <th>Mã hàng</th>
                <th>Hình ảnh</th>
                <th>Tên hàng</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th>Tổng phụ</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tong = 0;
            while ($ct = $chitiet->fetch_assoc()):
                $subtotal = $ct['dongia'] * $ct['soluong'];
                $tong += $subtotal;
                // Đường dẫn ảnh: ../assets/images/{hinhanh}
                $img = !empty($ct['hinhanh']) ? "../assets/images/" . htmlspecialchars($ct['hinhanh']) : "https://via.placeholder.com/70";
            ?>
            <tr>
                <td><?= htmlspecialchars($ct['mahang']) ?></td>
                <td>
                    <img src="<?= $img ?>" alt="Ảnh sản phẩm" loading="lazy">
                </td>                
                <td><?= htmlspecialchars($ct['tenhang']) ?></td>
                <td><?= number_format($ct['dongia'], 0) ?> VND</td>
                <td><?= $ct['soluong'] ?></td>
                <td><?= number_format($subtotal, 0) ?> VND</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Tổng đơn hàng:</th>
                <th><?= number_format($tong, 0) ?> VND</th>
            </tr>
        </tfoot>
    </table>
</div>
</body>
</html>