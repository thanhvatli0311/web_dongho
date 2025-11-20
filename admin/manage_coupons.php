<?php
session_start();
require_once '../includes/db.php'; // Đường dẫn đến file kết nối CSDL PDO
require_once '../includes/functions.php'; // Nếu bạn có các hàm tiện ích khác

// Kiểm tra quyền Admin (Cần thiết cho mọi trang Admin)
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

$message = '';
$edit_coupon = null;

// --- XỬ LÝ THÊM / CẬP NHẬT MÃ KHUYẾN MÃI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_coupon']) || isset($_POST['edit_coupon']))) {
    $makhuyenmai = strtoupper(trim($_POST['makhuyenmai']));
    $loai = $_POST['loai'];
    $giatri = (float)$_POST['giatri'];
    $dieukientoithieu = (float)$_POST['dieukientoithieu'];
    $ngaybatdau = $_POST['ngaybatdau'];
    $ngayketthuc = $_POST['ngayketthuc'];
    $trangthai = isset($_POST['trangthai']) ? 1 : 0;
    
    // Kiểm tra tính hợp lệ cơ bản
    if (empty($makhuyenmai) || empty($ngaybatdau) || empty($ngayketthuc) || $giatri <= 0) {
        $message = '<div class="alert alert-danger">Vui lòng điền đầy đủ và chính xác các trường bắt buộc (Mã KM, Giá trị, Ngày bắt đầu, Ngày kết thúc).</div>';
    } else {
        if (isset($_POST['add_coupon'])) {
            // Thêm mới
            try {
                $stmt = $pdo->prepare("INSERT INTO tbkhuyenmai (makhuyenmai, loai, giatri, dieukientoithieu, ngaybatdau, ngayketthuc, trangthai) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$makhuyenmai, $loai, $giatri, $dieukientoithieu, $ngaybatdau, $ngayketthuc, $trangthai]);
                $message = '<div class="alert alert-success">Thêm mã khuyến mãi <strong>' . htmlspecialchars($makhuyenmai) . '</strong> thành công!</div>';
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $message = '<div class="alert alert-danger">Lỗi: Mã khuyến mãi <strong>' . htmlspecialchars($makhuyenmai) . '</strong> đã tồn tại.</div>';
                } else {
                    $message = '<div class="alert alert-danger">Lỗi cơ sở dữ liệu: ' . $e->getMessage() . '</div>';
                }
            }
        } elseif (isset($_POST['edit_coupon'])) {
            // Cập nhật
            try {
                $original_makhuyenmai = $_POST['original_makhuyenmai'];
                $stmt = $pdo->prepare("UPDATE tbkhuyenmai SET makhuyenmai = ?, loai = ?, giatri = ?, dieukientoithieu = ?, ngaybatdau = ?, ngayketthuc = ?, trangthai = ? WHERE makhuyenmai = ?");
                $stmt->execute([$makhuyenmai, $loai, $giatri, $dieukientoithieu, $ngaybatdau, $ngayketthuc, $trangthai, $original_makhuyenmai]);
                $message = '<div class="alert alert-success">Cập nhật mã khuyến mãi <strong>' . htmlspecialchars($makhuyenmai) . '</strong> thành công!</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Lỗi cập nhật: ' . $e->getMessage() . '</div>';
            }
        }
        // Redirect để tránh gửi lại form
        if (strpos($message, 'success') !== false) {
             header('Location: manage_coupons.php?status=success');
             exit;
        }
    }
}

// --- XỬ LÝ XOÁ MÃ KHUYẾN MÃI ---
if (isset($_GET['delete'])) {
    $makhuyenmai = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tbkhuyenmai WHERE makhuyenmai = ?");
        $stmt->execute([$makhuyenmai]);
        $message = '<div class="alert alert-success">Xóa mã khuyến mãi <strong>' . htmlspecialchars($makhuyenmai) . '</strong> thành công!</div>';
        header('Location: manage_coupons.php?status=deleted');
        exit;
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Lỗi xóa: ' . $e->getMessage() . '</div>';
    }
}

// --- XỬ LÝ LẤY DỮ LIỆU ĐỂ SỬA ---
if (isset($_GET['edit'])) {
    $makhuyenmai = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM tbkhuyenmai WHERE makhuyenmai = ?");
    $stmt->execute([$makhuyenmai]);
    $edit_coupon = $stmt->fetch();
    if (!$edit_coupon) {
        $message = '<div class="alert alert-warning">Không tìm thấy mã khuyến mãi cần sửa.</div>';
    }
}

// --- LẤY TẤT CẢ MÃ KHUYẾN MÃI ---
try {
    $coupons = $pdo->query("SELECT * FROM tbkhuyenmai ORDER BY ngayketthuc DESC")->fetchAll();
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Lỗi truy vấn danh sách khuyến mãi: ' . $e->getMessage() . '</div>';
    $coupons = [];
}

// Hiển thị thông báo sau khi Redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = '<div class="alert alert-success">Thao tác thành công!</div>';
    } elseif ($_GET['status'] == 'deleted') {
        $message = '<div class="alert alert-success">Xóa mã khuyến mãi thành công!</div>';
    }
}

require __DIR__ . '/../templates/adminheader.php';

?>

<div class="container-fluid mt-4">
    <h2 class="mb-4 text-center">
        <i class="fas fa-tags"></i> Quản lý Mã Khuyến Mãi
    </h2>
    
    <?php echo $message; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <?php echo $edit_coupon ? '<i class="fas fa-edit"></i> Chỉnh sửa Mã Khuyến Mãi: ' . htmlspecialchars($edit_coupon['makhuyenmai']) : '<i class="fas fa-plus-circle"></i> Thêm Mã Khuyến Mãi Mới'; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="manage_coupons.php">
                <?php if ($edit_coupon): ?>
                    <input type="hidden" name="original_makhuyenmai" value="<?php echo htmlspecialchars($edit_coupon['makhuyenmai']); ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="makhuyenmai" class="form-label">Mã Khuyến Mãi (Viết HOA, không dấu)*</label>
                        <input type="text" class="form-control" id="makhuyenmai" name="makhuyenmai" required maxlength="20"
                               value="<?php echo htmlspecialchars($edit_coupon['makhuyenmai'] ?? ''); ?>" 
                               style="text-transform: uppercase;">
                    </div>

                    <div class="col-md-6">
                        <label for="loai" class="form-label">Loại Giảm Giá*</label>
                        <select class="form-select" id="loai" name="loai" required>
                            <option value="PHAN_TRAM" <?php echo ($edit_coupon['loai'] ?? '') === 'PHAN_TRAM' ? 'selected' : ''; ?>>Phần trăm (%)</option>
                            <option value="TIEN_MAT" <?php echo ($edit_coupon['loai'] ?? '') === 'TIEN_MAT' ? 'selected' : ''; ?>>Tiền mặt (VND)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="giatri" class="form-label">Giá trị giảm*</label>
                        <input type="number" step="0.01" min="1" class="form-control" id="giatri" name="giatri" required
                               value="<?php echo htmlspecialchars($edit_coupon['giatri'] ?? ''); ?>">
                        <small class="form-text text-muted">Nhập % nếu chọn Phần trăm, nhập VND nếu chọn Tiền mặt.</small>
                    </div>

                    <div class="col-md-4">
                        <label for="dieukientoithieu" class="form-label">Đơn hàng tối thiểu (VND)</label>
                        <input type="number" step="0" min="0" class="form-control" id="dieukientoithieu" name="dieukientoithieu" 
                               value="<?php echo htmlspecialchars($edit_coupon['dieukientoithieu'] ?? 0); ?>">
                        <small class="form-text text-muted">Tổng giá trị đơn hàng phải đạt mức này.</small>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                         <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" id="trangthai" name="trangthai" value="1"
                                <?php echo (!isset($edit_coupon) || (bool)($edit_coupon['trangthai'] ?? 1)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="trangthai">Kích hoạt/Sử dụng</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="ngaybatdau" class="form-label">Ngày Bắt Đầu*</label>
                        <input type="datetime-local" class="form-control" id="ngaybatdau" name="ngaybatdau" required
                               value="<?php echo date('Y-m-d\TH:i', strtotime($edit_coupon['ngaybatdau'] ?? 'now')); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="ngayketthuc" class="form-label">Ngày Kết Thúc*</label>
                        <input type="datetime-local" class="form-control" id="ngayketthuc" name="ngayketthuc" required
                               value="<?php echo date('Y-m-d\TH:i', strtotime($edit_coupon['ngayketthuc'] ?? '+1 week')); ?>">
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <?php if ($edit_coupon): ?>
                        <button type="submit" name="edit_coupon" class="btn btn-success me-2">
                            <i class="fas fa-save"></i> Lưu Thay Đổi
                        </button>
                        <a href="manage_coupons.php" class="btn btn-secondary">
                             <i class="fas fa-times-circle"></i> Hủy
                        </a>
                    <?php else: ?>
                        <button type="submit" name="add_coupon" class="btn btn-primary">
                             <i class="fas fa-plus"></i> Thêm Mã Giảm Giá
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <h3 class="mb-3"><i class="fas fa-list-alt"></i> Danh sách Khuyến Mãi Hiện Có</h3>
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Mã KM</th>
                    <th>Loại</th>
                    <th>Giá trị</th>
                    <th>ĐK tối thiểu</th>
                    <th>Ngày BĐ</th>
                    <th>Ngày KT</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($coupon['makhuyenmai']); ?></strong></td>
                    <td><?php echo $coupon['loai'] === 'PHAN_TRAM' ? 'Phần trăm' : 'Tiền mặt'; ?></td>
                    <td>
                        <?php 
                            echo number_format($coupon['giatri'], 0); 
                            echo $coupon['loai'] === 'PHAN_TRAM' ? '%' : ' VND';
                        ?>
                    </td>
                    <td><?php echo number_format($coupon['dieukientoithieu'], 0); ?> VND</td>
                    <td><?php echo date('d-m-Y H:i', strtotime($coupon['ngaybatdau'])); ?></td>
                    <td><?php echo date('d-m-Y H:i', strtotime($coupon['ngayketthuc'])); ?></td>
                    <td>
                        <?php if ((bool)$coupon['trangthai']): ?>
                            <span class="badge bg-success">Đang hoạt động</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Vô hiệu hóa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="manage_coupons.php?edit=<?php echo urlencode($coupon['makhuyenmai']); ?>" class="btn btn-sm btn-warning me-2" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="manage_coupons.php?delete=<?php echo urlencode($coupon['makhuyenmai']); ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Bạn có chắc chắn muốn xóa mã khuyến mãi này?');" title="Xóa">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($coupons)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Chưa có mã khuyến mãi nào được tạo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php 

?>