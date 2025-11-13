<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../templates/adminheader.php';

// --- KIỂM TRA QUYỀN VÀ THAM SỐ ---
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = ['type' => 'danger', 'content' => 'Không có mã sản phẩm được chọn.'];
    header("Location: manage_products.php"); // Chuyển hướng về trang quản lý sản phẩm
    exit;
}

$mahang = trim($_GET['id']);
$target_dir = __DIR__ . "/../assets/images/"; // Đường dẫn tuyệt đối cho an toàn

// --- LẤY THÔNG TIN SẢN PHẨM ---
try {
    $sql = "SELECT * FROM tbmathang WHERE mahang = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mahang]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['message'] = ['type' => 'danger', 'content' => 'Sản phẩm không tồn tại.'];
        header("Location: manage_products.php");
        exit;
    }
} catch (PDOException $e) {
    die("Lỗi truy vấn sản phẩm: " . $e->getMessage());
}

// --- XỬ LÝ FORM SUBMIT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. CẬP NHẬT THÔNG TIN SẢN PHẨM & ẢNH CHÍNH
    if (isset($_POST['update_product'])) {
        try {
            $tenhang    = trim($_POST['tenhang']);
            $mota       = trim($_POST['mota']);
            $dongia     = floatval($_POST['dongia']);
            $nguongoc   = trim($_POST['nguongoc']);
            $thuonghieu = trim($_POST['thuonghieu']);
            $conhang    = trim($_POST['conhang']);
            $hinhanh    = $product['hinhanh']; // Giữ ảnh cũ làm mặc định

            // Xử lý upload ảnh chính mới
            if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
                $file_name = basename($_FILES["hinhanh"]["name"]);
                $target_file = $target_dir . $file_name;
                if (move_uploaded_file($_FILES["hinhanh"]["tmp_name"], $target_file)) {
                    $hinhanh = $file_name; // Cập nhật tên ảnh mới nếu upload thành công
                }
            }

            $sql_update = "UPDATE tbmathang SET tenhang = ?, mota = ?, dongia = ?, nguongoc = ?, thuonghieu = ?, hinhanh = ?, conhang = ? WHERE mahang = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$tenhang, $mota, $dongia, $nguongoc, $thuonghieu, $hinhanh, $conhang, $mahang]);
            
            $_SESSION['message'] = ['type' => 'success', 'content' => 'Cập nhật thông tin sản phẩm thành công!'];

        } catch (PDOException $e) {
            $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi cập nhật sản phẩm: ' . $e->getMessage()];
        }
        // Tải lại trang để hiển thị thông báo và dữ liệu mới
        header("Location: edit_product.php?id=" . $mahang);
        exit;
    }

    // 2. UPLOAD ẢNH CHI TIẾT MỚI
    if (isset($_POST['upload_detail_images'])) {
        if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
            $files = $_FILES['new_images'];
            $file_count = count($files['name']);
            $success_count = 0;
            
            for ($i = 0; $i < $file_count; $i++) {
                $file_name = basename($files['name'][$i]);
                $target_file = $target_dir . $file_name;
                
                if (move_uploaded_file($files['tmp_name'][$i], $target_file)) {
                    try {
                        $sql_insert = "INSERT INTO tbhinhanhchitiet (mahang, hinhanh_chitiet) VALUES (?, ?)";
                        $stmt_insert = $pdo->prepare($sql_insert);
                        $stmt_insert->execute([$mahang, $file_name]);
                        $success_count++;
                    } catch (PDOException $e) {
                        // Bỏ qua nếu ảnh đã tồn tại (lỗi duplicate) hoặc có lỗi khác
                    }
                }
            }
            $_SESSION['message'] = ['type' => 'success', 'content' => "Đã upload thành công {$success_count}/{$file_count} ảnh chi tiết."];
        }
        header("Location: edit_product.php?id=" . $mahang);
        exit;
    }

    // 3. XÓA ẢNH CHI TIẾT
    if (isset($_POST['delete_image'])) {
        $image_to_delete = $_POST['image_name'];
        try {
            $sql_delete = "DELETE FROM tbhinhanhchitiet WHERE mahang = ? AND hinhanh_chitiet = ?";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute([$mahang, $image_to_delete]);

            // (Tùy chọn) Xóa file ảnh khỏi server
            if (file_exists($target_dir . $image_to_delete)) {
                unlink($target_dir . $image_to_delete);
            }
            $_SESSION['message'] = ['type' => 'success', 'content' => 'Đã xóa ảnh chi tiết.'];
        } catch (PDOException $e) {
            $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi khi xóa ảnh.'];
        }
        header("Location: edit_product.php?id=" . $mahang);
        exit;
    }
}

// --- LẤY DANH SÁCH ẢNH CHI TIẾT ---
try {
    $sql_images = "SELECT hinhanh_chitiet FROM tbhinhanhchitiet WHERE mahang = ?";
    $stmt_images = $pdo->prepare($sql_images);
    $stmt_images->execute([$mahang]);
    $images = $stmt_images->fetchAll(PDO::FETCH_COLUMN, 0); // Lấy cột đầu tiên của tất cả các dòng
} catch (PDOException $e) {
    die("Lỗi truy vấn ảnh chi tiết: " . $e->getMessage());
}

?>

<!-- ================================================================ -->
<!-- ======================= PHẦN HTML VÀ GIAO DIỆN =================== -->
<!-- ================================================================ -->
<div class="container mt-5">
    
    <?php 
    // Hiển thị thông báo (nếu có)
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['message']['type'] . ' alert-dismissible fade show" role="alert">'
             . $_SESSION['message']['content'] .
             '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>';
        unset($_SESSION['message']);
    }
    ?>

    <!-- FORM SỬA THÔNG TIN SẢN PHẨM -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Sửa thông tin sản phẩm</h3>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Mã hàng:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($product['mahang']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Tên hàng:</label>
                    <input type="text" name="tenhang" class="form-control" value="<?= htmlspecialchars($product['tenhang']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Mô tả:</label>
                    <textarea name="mota" class="form-control" rows="4"><?= htmlspecialchars($product['mota']) ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Đơn giá:</label>
                        <input type="number" step="1000" name="dongia" class="form-control" value="<?= htmlspecialchars($product['dongia']) ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Tình trạng:</label>
                        <select name="conhang" class="form-control" required>
                            <option value="Còn hàng" <?= ($product['conhang'] == 'Còn hàng') ? 'selected' : '' ?>>Còn hàng</option>
                            <option value="Hết hàng" <?= ($product['conhang'] == 'Hết hàng') ? 'selected' : '' ?>>Hết hàng</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Nguồn gốc:</label>
                        <input type="text" name="nguongoc" class="form-control" value="<?= htmlspecialchars($product['nguongoc']) ?>" required>
                    </div>
                     <div class="form-group col-md-6">
                        <label>Thương hiệu:</label>
                        <input type="text" name="thuonghieu" class="form-control" value="<?= htmlspecialchars($product['thuonghieu']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ảnh chính hiện tại:</label><br>
                    <img src="../assets/images/<?= htmlspecialchars($product['hinhanh']) ?>" alt="Ảnh chính" class="img-thumbnail" width="200">
                </div>
                 <div class="form-group">
                    <label>Chọn ảnh chính mới (để trống nếu không muốn thay đổi):</label>
                    <input type="file" name="hinhanh" class="form-control-file" accept="image/*">
                </div>
                <button type="submit" name="update_product" class="btn btn-primary">Cập nhật thông tin</button>
            </form>
        </div>
    </div>

    <!-- FORM QUẢN LÝ ẢNH CHI TIẾT -->
    <div class="card">
        <div class="card-header">
            <h3>Quản lý ảnh chi tiết</h3>
        </div>
        <div class="card-body">
            <!-- Hiển thị ảnh chi tiết hiện có -->
            <div class="row">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $img): ?>
                        <div class="col-md-3 mb-3 text-center">
                            <img src="../assets/images/<?= htmlspecialchars($img) ?>" class="img-thumbnail">
                            <form method="post" class="mt-2">
                                <input type="hidden" name="image_name" value="<?= htmlspecialchars($img) ?>">
                                <button type="submit" name="delete_image" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa ảnh này?');">Xóa</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="col-12">Chưa có ảnh chi tiết nào.</p>
                <?php endif; ?>
            </div>
            <hr>
            <!-- Form upload ảnh mới -->
            <form method="post" enctype="multipart/form-data">
                 <div class="form-group">
                    <label>Thêm ảnh chi tiết mới:</label>
                    <input type="file" name="new_images[]" class="form-control-file" accept="image/*" multiple required>
                </div>
                <button type="submit" name="upload_detail_images" class="btn btn-success">Upload ảnh mới</button>
            </form>
        </div>
    </div>
    
    <a href="manage_products.php" class="btn btn-secondary mt-4">Quay lại danh sách sản phẩm</a>
</div>