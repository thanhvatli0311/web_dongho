<?php
session_start();
// Đồng bộ đường dẫn include/require
require __DIR__ . '/../includes/db.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

// Kiểm tra biến $pdo (đảm bảo PDO được sử dụng)
if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL (PDO). Vui lòng kiểm tra file includes/db.php.");
}

// Lấy tham số 'id' (HOẶC 'madonhang') từ URL
$madonhang = '';
if (isset($_GET['id'])) {
    $madonhang = trim($_GET['id']);
} elseif (isset($_GET['madonhang'])) {
    $madonhang = trim($_GET['madonhang']);
}

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
    echo '<div class="container mt-4"><div class="alert alert-danger">Lỗi: Không tìm thấy mã đơn hàng.</div></div>';
    echo '</body></html>';
    exit;
}

// 1. CẬP NHẬT TRUY VẤN: Lấy thông tin đơn hàng + KHÁCH HÀNG + KHUYẾN MÃI
// Thêm các cột từ bảng tbkhuyenmai để hiển thị thông tin giảm giá
$sql_donhang = "SELECT d.*, k.tenkhach, k.sodienthoai, k.diachi,
                       km.tenkhuyenmai, km.loai, km.giatri
                FROM tbDonHang d
                JOIN tbkhachhang k ON d.makhach = k.makhach
                LEFT JOIN tbkhuyenmai km ON d.makhuyenmai = km.makhuyenmai
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

// Lấy chi tiết đơn hàng + ảnh sản phẩm
$sql_chitiet = "SELECT c.*, m.tenhang, m.dongia AS dongia_mathang, m.hinhanh
                FROM tbChiTietDonHang c
                JOIN tbmathang m ON c.mahang = m.mahang
                WHERE c.madonhang = :madonhang";
try {
    $stmt2 = $pdo->prepare($sql_chitiet);
    $stmt2->execute([':madonhang' => $madonhang]);
    $chitiet = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn chi tiết đơn hàng: " . $e->getMessage());
}

// Định nghĩa hàm để tạo badge cho tình trạng đơn hàng
function get_status_badge($status) {
    switch ($status) {
        case 'Đã giao': return '<span class="badge bg-success text-white p-2">' . htmlspecialchars($status) . '</span>'; // Sửa 'Đã giao hàng' thành 'Đã giao' cho khớp data mẫu
        case 'Đang giao hàng': return '<span class="badge bg-info text-dark p-2">' . htmlspecialchars($status) . '</span>';
        case 'Đã hủy': return '<span class="badge bg-danger text-white p-2">' . htmlspecialchars($status) . '</span>';
        default: return '<span class="badge bg-warning text-dark p-2">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<div class="container mt-4 mb-5">
    <a href="manage_orders.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Quay lại</a>
    <button class="btn btn-primary mb-3 float-right" onclick="window.print()"><i class="fas fa-print"></i> In đơn hàng</button>

    <h2 class="mb-3">Chi tiết đơn hàng: <span class="text-primary"><?= htmlspecialchars($madonhang) ?></span></h2>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-user-circle"></i> Thông tin Khách hàng</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Khách hàng:</strong> <?= htmlspecialchars($donhang['tenkhach']) ?></p>
                    <p class="mb-2"><strong>SĐT:</strong> <?= htmlspecialchars($donhang['sodienthoai']) ?></p>
                    <p class="mb-0"><strong>Địa chỉ:</strong> <?= htmlspecialchars($donhang['diachi']) ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Thông tin Đơn hàng</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Ngày mua:</strong> <?= date('d/m/Y H:i', strtotime($donhang['ngaymua'])) ?></p>
                    <p class="mb-2"><strong>Tình trạng:</strong> <?= get_status_badge($donhang['tinhtrang']) ?></p>
                    <p class="mb-2"><strong>Thanh toán:</strong> <?= htmlspecialchars($donhang['phuongthuctt']) ?> (<?= htmlspecialchars($donhang['trangthaitt']) ?>)</p>
                    
                    <?php if (!empty($donhang['makhuyenmai'])): ?>
                        <p class="mb-0"><strong>Mã khuyến mãi:</strong> <span class="badge badge-success"><?= htmlspecialchars($donhang['makhuyenmai']) ?></span></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mt-4 mb-3"><i class="fas fa-list"></i> Danh sách Sản phẩm</h4>
    <div class="table-responsive">
        <table class="table table-bordered order-detail-table">
            <thead class="thead-dark">
                <tr>
                    <th>Mã hàng</th>
                    <th>Hình ảnh</th>
                    <th>Tên hàng</th>
                    <th class="text-right">Đơn giá</th>
                    <th class="text-center">SL</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $tong_tam_tinh = 0;
                if (!empty($chitiet)):
                    foreach ($chitiet as $ct):
                        $dongia = $ct['dongia']; // Sử dụng đơn giá lúc đặt hàng trong tbChiTietDonHang
                        $soluong = $ct['soluong'];
                        $thanh_tien = $dongia * $soluong;
                        $tong_tam_tinh += $thanh_tien;
                        
                        $img = !empty($ct['hinhanh']) 
                             ? "../assets/images/" . htmlspecialchars($ct['hinhanh']) 
                             : "https://via.placeholder.com/70";
                ?>
                <tr>
                    <td><?= htmlspecialchars($ct['mahang']) ?></td>
                    <td style="width: 100px;" class="text-center">
                        <img src="<?= $img ?>" alt="Ảnh sản phẩm" loading="lazy" class="img-fluid">
                    </td>
                    <td><?= htmlspecialchars($ct['tenhang']) ?></td>
                    <td class="text-right"><?= number_format($dongia, 0, ',', '.') ?> đ</td>
                    <td class="text-center"><?= $soluong ?></td>
                    <td class="text-right fw-bold"><?= number_format($thanh_tien, 0, ',', '.') ?> đ</td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="6" class="text-center">Đơn hàng không có sản phẩm nào.</td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right"><strong>Tạm tính:</strong></td>
                    <td class="text-right"><?= number_format($tong_tam_tinh, 0, ',', '.') ?> đ</td>
                </tr>

                <?php 
                $tien_giam = 0;
                $discount_desc = "";

                // Logic tính giảm giá dựa trên thông tin từ LEFT JOIN
                if (!empty($donhang['makhuyenmai'])) {
                    if (isset($donhang['giatri']) && $donhang['giatri'] > 0) {
                        if ($donhang['loai'] === 'PHAN_TRAM') {
                            $tien_giam = ($tong_tam_tinh * $donhang['giatri']) / 100;
                            $discount_desc = "Giảm " . number_format($donhang['giatri'], 0) . "%";
                        } else { // TIEN_MAT
                            $tien_giam = $donhang['giatri'];
                            $discount_desc = "Giảm trực tiếp";
                        }
                    }
                }
                
                // Đảm bảo tiền giảm không vượt quá tổng tiền
                if($tien_giam > $tong_tam_tinh) $tien_giam = $tong_tam_tinh;
                $tong_thanh_toan = $tong_tam_tinh - $tien_giam;
                ?>

                <?php if ($tien_giam > 0): ?>
                <tr>
                    <td colspan="5" class="text-right text-success">
                        <strong>Khuyến mãi (<?= htmlspecialchars($donhang['makhuyenmai']) ?> - <?= $discount_desc ?>):</strong>
                    </td>
                    <td class="text-right text-success">-<?= number_format($tien_giam, 0, ',', '.') ?> đ</td>
                </tr>
                <?php endif; ?>

                <tr class="bg-light">
                    <td colspan="5" class="text-right"><strong>Tổng thanh toán:</strong></td>
                    <td class="text-right text-danger font-weight-bold" style="font-size: 1.2em;">
                        <?= number_format($tong_thanh_toan, 0, ',', '.') ?> đ
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

</body>
</html>