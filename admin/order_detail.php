<?php
session_start();
// Đồng bộ đường dẫn include/require
require __DIR__ . '/../includes/db.php';
// Bỏ require __DIR__ . '/../templates/adminheader.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Kiểm tra biến $pdo (đảm bảo PDO được sử dụng)
if (!isset($pdo)) {
    // Chỉ hiển thị lỗi, không cần đóng HTML vì lỗi này hiếm gặp
    die("Lỗi: Không thể kết nối CSDL (PDO). Vui lòng kiểm tra file includes/db.php.");
}

// Sửa đổi để lấy tham số 'id' (HOẶC 'madonhang') từ URL
$madonhang = '';
if (isset($_GET['id'])) {
    $madonhang = trim($_GET['id']);
} elseif (isset($_GET['madonhang'])) {
    $madonhang = trim($_GET['madonhang']);
}

// --- Khối HTML đầu tiên (nếu có lỗi, sẽ đóng HTML tại đây) ---
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng <?= htmlspecialchars($madonhang) ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    .order-detail-table img {
        width: 70px; height: 70px; object-fit: cover; border-radius: 8px; border: 1px solid #ccc;
        background: #fafbfc;
    }
    /* Thêm style để ẩn các yếu tố không cần thiết khi in */
    @media print {
        .btn-secondary, .float-end { display: none; }
    }
    </style>
</head>
<body>

<?php
if ($madonhang == '') {
    // Hiển thị lỗi và đóng HTML
    echo '<div class="container mt-4"><div class="alert alert-danger">Lỗi: Không tìm thấy mã đơn hàng.</div></div>';
    echo '</body></html>';
    exit;
}

// Lấy thông tin đơn hàng và khách hàng (Sử dụng PDO)
$sql_donhang = "SELECT d.*, k.tenkhach, k.sodienthoai, k.diachi FROM tbDonHang d
                JOIN tbkhachhang k ON d.makhach = k.makhach
                WHERE d.madonhang = :madonhang";
try {
    $stmt = $pdo->prepare($sql_donhang);
    $stmt->execute([':madonhang' => $madonhang]);
    $donhang = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn thông tin đơn hàng: " . $e->getMessage());
}

if (!$donhang) {
    echo '<div class="container mt-4"><div class="alert alert-danger">Đơn hàng không tồn tại!</div></div>';
    echo '</body></html>';
    exit;
}

// Lấy chi tiết đơn hàng + ảnh sản phẩm (Sử dụng PDO)
$sql_chitiet = "SELECT c.*, m.tenhang, m.dongia AS dongia_mathang, m.hinhanh
                FROM tbChiTietDonHang c
                JOIN tbmathang m ON c.mahang = m.mahang
                WHERE c.madonhang = :madonhang";
try {
    $stmt2 = $pdo->prepare($sql_chitiet);
    $stmt2->execute([':madonhang' => $madonhang]);
    $chitiet = $stmt2->fetchAll(PDO::FETCH_ASSOC); // Lấy tất cả chi tiết
} catch (PDOException $e) {
    die("Lỗi truy vấn chi tiết đơn hàng: " . $e->getMessage());
}

// Định nghĩa hàm để tạo badge cho tình trạng đơn hàng
function get_status_badge($status) {
    switch ($status) {
        case 'Đã giao hàng': return '<span class="badge bg-success text-white p-2">' . htmlspecialchars($status) . '</span>';
        case 'Đang giao hàng': return '<span class="badge bg-info text-dark p-2">' . htmlspecialchars($status) . '</span>';
        case 'Đã hủy': return '<span class="badge bg-danger text-white p-2">' . htmlspecialchars($status) . '</span>';
        default: return '<span class="badge bg-warning text-dark p-2">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<div class="container mt-4">
    <a href="manage_orders.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Quay lại</a>
    <button class="btn btn-primary mb-3 float-end" onclick="window.print()"><i class="fas fa-print"></i> In đơn hàng</button>

    <h2 class="mb-3">Chi tiết đơn hàng: <span class="text-primary"><?= htmlspecialchars($madonhang) ?></span></h2>

    <div class="row mb-4">
        <div class="col-md-6 card shadow-sm p-3">
            <h4><i class="fas fa-user-circle"></i> Thông tin Khách hàng</h4>
            <div class="customer-info">
                <p><strong>Khách hàng:</strong> <?= htmlspecialchars($donhang['tenkhach']) ?></p>
                <p><strong>SĐT:</strong> <?= htmlspecialchars($donhang['sodienthoai']) ?></p>
                <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($donhang['diachi']) ?></p>
            </div>
        </div>

        <div class="col-md-6 card shadow-sm p-3">
            <h4><i class="fas fa-receipt"></i> Thông tin Đơn hàng</h4>
            <div class="order-info">
                <p><strong>Ngày mua:</strong> <?= date('d/m/Y H:i', strtotime($donhang['ngaymua'])) ?></p>
                <p><strong>Tình trạng:</strong> <?= get_status_badge($donhang['tinhtrang']) ?></p>
                <p><strong>Ghi chú:</strong> <?= !empty($donhang['ghichu']) ? htmlspecialchars($donhang['ghichu']) : 'Không có' ?></p>
            </div>
        </div>
    </div>

    <h4 class="mt-4 mb-3"><i class="fas fa-list"></i> Danh sách Sản phẩm</h4>
    <table class="table table-bordered order-detail-table">
        <thead>
            <tr class="text-white bg-dark">
                <th>Mã hàng</th>
                <th>Hình ảnh</th>
                <th>Tên hàng</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th>Tổng tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tong = 0;
            if (!empty($chitiet)):
                foreach ($chitiet as $ct):
                    $subtotal = $ct['dongia'] * $ct['soluong'];
                    $tong += $subtotal;
                    
                    $img = !empty($ct['hinhanh']) 
                         ? "../assets/images/" . htmlspecialchars($ct['hinhanh']) 
                         : "https://via.placeholder.com/70";
            ?>
            <tr>
                <td><?= htmlspecialchars($ct['mahang']) ?></td>
                <td style="width: 100px;">
                    <img src="<?= $img ?>" alt="Ảnh sản phẩm" loading="lazy" class="img-fluid">
                </td>
                <td><?= htmlspecialchars($ct['tenhang']) ?></td>
                <td><?= number_format($ct['dongia'], 0, ',', '.') ?> VND</td>
                <td><?= $ct['soluong'] ?></td>
                <td class="text-danger fw-bold"><?= number_format($subtotal, 0, ',', '.') ?> VND</td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="6" class="text-center">Đơn hàng không có sản phẩm nào.</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right table-secondary">Tổng đơn hàng:</th>
                <th class="text-danger fw-bold table-secondary"><?= number_format($tong, 0, ',', '.') ?> VND</th>
            </tr>
        </tfoot>
    </table>
</div>

</body>
</html>