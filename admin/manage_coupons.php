<?php
session_start();
require_once '../includes/db.php'; /** @var PDO $pdo */
require_once '../includes/functions.php'; 

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
        $_SESSION['message'] = ['type' => 'danger', 'content' => 'Vui lòng điền đầy đủ và chính xác các trường bắt buộc (Mã KM, Giá trị, Ngày bắt đầu, Ngày kết thúc).'];
    } else {
        if (isset($_POST['add_coupon'])) {
            // Thêm mới
            try {
                $stmt = $pdo->prepare("INSERT INTO tbkhuyenmai (makhuyenmai, loai, giatri, dieukientoithieu, ngaybatdau, ngayketthuc, trangthai) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$makhuyenmai, $loai, $giatri, $dieukientoithieu, $ngaybatdau, $ngayketthuc, $trangthai]);
                $_SESSION['message'] = ['type' => 'success', 'content' => 'Thêm mã khuyến mãi ' . htmlspecialchars($makhuyenmai) . ' thành công!'];
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi: Mã khuyến mãi ' . htmlspecialchars($makhuyenmai) . ' đã tồn tại.'];
                } else {
                    $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()];
                }
            }
        } elseif (isset($_POST['edit_coupon'])) {
            // Cập nhật
            try {
                $original_makhuyenmai = $_POST['original_makhuyenmai'];
                $stmt = $pdo->prepare("UPDATE tbkhuyenmai SET makhuyenmai = ?, loai = ?, giatri = ?, dieukientoithieu = ?, ngaybatdau = ?, ngayketthuc = ?, trangthai = ? WHERE makhuyenmai = ?");
                $stmt->execute([$makhuyenmai, $loai, $giatri, $dieukientoithieu, $ngaybatdau, $ngayketthuc, $trangthai, $original_makhuyenmai]);
                $_SESSION['message'] = ['type' => 'success', 'content' => 'Cập nhật mã khuyến mãi <strong>' . htmlspecialchars($makhuyenmai) . '</strong> thành công!'];
            } catch (PDOException $e) {
                $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi cập nhật: ' . $e->getMessage()];
            }
        }
    }
    // Redirect để tránh gửi lại form, dùng session message thay vì biến $message
    header('Location: manage_coupons.php');
    exit;
}

// --- XỬ LÝ XOÁ MÃ KHUYẾN MÃI ---
if (isset($_GET['delete'])) {
    $makhuyenmai = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tbkhuyenmai WHERE makhuyenmai = ?");
        $stmt->execute([$makhuyenmai]);
        $_SESSION['message'] = ['type' => 'success', 'content' => 'Xóa mã khuyến mãi <strong>' . htmlspecialchars($makhuyenmai) . '</strong> thành công!'];
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi xóa: ' . $e->getMessage()];
    }
    header('Location: manage_coupons.php');
    exit;
}

// --- XỬ LÝ LẤY DỮ LIỆU ĐỂ SỬA ---
if (isset($_GET['edit'])) {
    $makhuyenmai = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM tbkhuyenmai WHERE makhuyenmai = ?");
    $stmt->execute([$makhuyenmai]);
    $edit_coupon = $stmt->fetch();
    if (!$edit_coupon) {
        $_SESSION['message'] = ['type' => 'warning', 'content' => 'Không tìm thấy mã khuyến mãi cần sửa.'];
        header('Location: manage_coupons.php');
        exit;
    }
}

// --- LẤY TẤT CẢ MÃ KHUYẾN MÃI ---
try {
    // Sắp xếp theo ngày kết thúc, mã hoạt động lên trước
    $coupons = $pdo->query("SELECT * FROM tbkhuyenmai ORDER BY trangthai DESC, ngayketthuc DESC")->fetchAll();
} catch (PDOException $e) {
    // Dùng biến $message để hiển thị lỗi truy vấn nếu không có redirect
    $message = '<div class="alert alert-danger card-clean p-3">Lỗi truy vấn danh sách khuyến mãi: ' . $e->getMessage() . '</div>';
    $coupons = [];
}

require __DIR__ . '/../templates/adminheader.php';

?>

<?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show card-clean p-3 mb-4" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']['content']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <?php echo $message; // Hiển thị lỗi truy vấn nếu có ?>

    <h3 class="mt-4 mb-3" style="color: var(--primary-color); font-weight: 600;">
        <?php echo $edit_coupon ? '<i class="fas fa-edit"></i> Chỉnh sửa Mã Khuyến Mãi: ' . htmlspecialchars($edit_coupon['makhuyenmai']) : '<i class="fas fa-plus-circle"></i> Thêm mã khuyến mãi mới'; ?>
    </h3>
    
    <div class="card-clean mb-5">
        <form method="POST" action="manage_coupons.php">
            <?php if ($edit_coupon): ?>
                <input type="hidden" name="original_makhuyenmai" value="<?php echo htmlspecialchars($edit_coupon['makhuyenmai']); ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="makhuyenmai" class="form-label">Mã Khuyến Mãi (không dấu)*</label>
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

            <div class="mt-4 text-center border-top pt-3">
                <?php if ($edit_coupon): ?>
                    <button type="submit" name="edit_coupon" class="btn btn-primary me-2">
                        <i class="fas fa-save"></i> Lưu Thay Đổi
                    </button>
                    <a href="manage_coupons.php" class="btn btn-secondary">
                          <i class="fas fa-times-circle"></i> Hủy
                    </a>
                <?php else: ?>
                    <button type="submit" name="add_coupon" class="btn btn-primary">
                          <i class="fas fa-plus"></i> Thêm mã giảm giá
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h3 class="mt-5 mb-3" style="color: var(--text-main); font-weight: 600;"><i class="fas fa-list-alt"></i> Danh sách khuyến mãi hiện có</h3>
    
    <div class="card-clean p-0"> 
        <div class="table-responsive">
            <table class="table-custom"> 
                <thead>
                    <tr>
                        <th style="width: 15%;">Mã KM</th>
                        <th style="width: 10%;">Loại</th>
                        <th style="width: 10%;">Giá trị</th>
                        <th style="width: 15%;">ĐK tối thiểu</th>
                        <th style="width: 15%;">Ngày BĐ</th>
                        <th style="width: 15%;">Ngày KT</th>
                        <th style="width: 10%;">Trạng thái</th>
                        <th style="width: 10%;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted p-5">Chưa có mã khuyến mãi nào được tạo.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $coupon): ?>
                        <tr>
                            <td><strong style="font-size: 1.1em; color: var(--primary-color);"><?php echo htmlspecialchars($coupon['makhuyenmai']); ?></strong></td>
                            <td>
                                <span class="badge rounded-pill <?php echo $coupon['loai'] === 'PHAN_TRAM' ? 'bg-info text-dark' : 'bg-secondary'; ?>">
                                    <?php echo $coupon['loai'] === 'PHAN_TRAM' ? 'Phần trăm' : 'Tiền mặt'; ?>
                                </span>
                            </td>
                            <td style="font-weight: 600;">
                                <?php 
                                    echo number_format($coupon['giatri'], 0, ',', '.'); 
                                    echo $coupon['loai'] === 'PHAN_TRAM' ? '%' : ' VND';
                                ?>
                            </td>
                            <td><?php echo number_format($coupon['dieukientoithieu'], 0, ',', '.'); ?> VND</td>
                            <td class="text-muted"><small><?php echo date('d/m/Y H:i', strtotime($coupon['ngaybatdau'])); ?></small></td>
                            <td class="text-muted"><small><?php echo date('d/m/Y H:i', strtotime($coupon['ngayketthuc'])); ?></small></td>
                            <td>
                                <?php 
                                    $is_active = (bool)$coupon['trangthai'] && (time() < strtotime($coupon['ngayketthuc']));
                                ?>
                                <?php if ($is_active): ?>
                                    <span class="badge bg-success">Đang hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Vô hiệu hóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="manage_coupons.php?edit=<?php echo urlencode($coupon['makhuyenmai']); ?>" class="btn btn-sm btn-warning me-1" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="manage_coupons.php?delete=<?php echo urlencode($coupon['makhuyenmai']); ?>" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa mã khuyến mãi này?');" title="Xóa">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
