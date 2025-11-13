<?php
session_start();

// Sử dụng require với __DIR__ và file db.php đã thống nhất
require __DIR__ . '/../includes/db.php'; 
require __DIR__ . '/../templates/adminheader.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = ['type' => 'danger', 'content' => 'Không tìm thấy ID khách hàng.'];
    header("Location: manage_customers.php");
    exit;
}

// Lấy ID khách hàng từ URL (không cần escape vì sẽ dùng prepared statement)
$id = $_GET['id'];

// Xử lý cập nhật thông tin khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $tenkhach    = $_POST['tenkhach'];
    $ngaysinh    = $_POST['ngaysinh'];
    $sodienthoai = $_POST['sodienthoai'];
    $diachi      = $_POST['diachi'];
    $gioitinh    = $_POST['gioitinh'];
    try {
        // Chuẩn bị câu lệnh SQL UPDATE với PDO
        $sql = "UPDATE tbkhachhang SET 
                    tenkhach    = :tenkhach,
                    ngaysinh    = :ngaysinh,
                    sodienthoai = :sodienthoai,
                    diachi      = :diachi,
                    gioitinh    = :gioitinh
                WHERE makhach = :makhach";
        
        $stmt = $pdo->prepare($sql);
        
        // Gán giá trị vào các placeholder
        $stmt->execute([
            ':tenkhach'    => $tenkhach,
            ':ngaysinh'    => $ngaysinh,
            ':sodienthoai' => $sodienthoai,
            ':diachi'      => $diachi,
            ':gioitinh'    => $gioitinh,
            ':makhach'     => $id
        ]);

        $_SESSION['message'] = ['type' => 'success', 'content' => 'Cập nhật thông tin khách hàng thành công!'];
        header("Location: manage_customers.php");
        exit;

    } catch (PDOException $e) {
        // Bắt lỗi và lưu vào session để hiển thị
        $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi cập nhật: ' . $e->getMessage()];
    }
}


// Lấy thông tin khách hàng hiện tại để hiển thị trong form
try {
    $sql_select = "SELECT * FROM tbkhachhang WHERE makhach = ?";
    $stmt = $pdo->prepare($sql_select);
    $stmt->execute([$id]);
    
    // fetch() để lấy một dòng duy nhất
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $_SESSION['message'] = ['type' => 'danger', 'content' => 'Khách hàng không tồn tại.'];
        header("Location: manage_customers.php");
        exit;
    }
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

?>

<!-- Phần HTML và CSS giữ nguyên như code cũ của bạn -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa thông tin khách hàng</title>
    <!-- Các link CSS và style của bạn -->
    <style>
        .container { max-width: 600px; }
        .btn-primary { width: 100%; }
        .btn-secondary { width: 100%; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2>Sửa thông tin khách hàng</h2>
    
    <?php 
    // Hiển thị thông báo (nếu có)
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['message']['type'] . '">'. $_SESSION['message']['content'] .'</div>';
        unset($_SESSION['message']);
    }
    ?>

    <form method="POST">
        <div class="form-group">
            <label>Tên khách hàng:</label>
            <input type="text" name="tenkhach" class="form-control" 
                   value="<?= htmlspecialchars($customer['tenkhach']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Ngày sinh:</label>
            <input type="date" name="ngaysinh" class="form-control" 
                   value="<?= htmlspecialchars($customer['ngaysinh']) ?>">
        </div>
        
        <div class="form-group">
            <label>Số điện thoại:</label>
            <input type="tel" name="sodienthoai" class="form-control" 
                   value="<?= htmlspecialchars($customer['sodienthoai']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Địa chỉ:</label>
            <textarea name="diachi" class="form-control"><?= htmlspecialchars($customer['diachi']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Giới tính:</label>
            <select name="gioitinh" class="form-control">
                <option value="Nam" <?= ($customer['gioitinh'] ?? '') == 'Nam' ? 'selected' : '' ?>>Nam</option>
                <option value="Nữ" <?= ($customer['gioitinh'] ?? '') == 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                <option value="Khác" <?= ($customer['gioitinh'] ?? '') == 'Khác' ? 'selected' : '' ?>>Khác</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
        <a href="manage_customers.php" class="btn btn-secondary">Hủy bỏ</a>
    </form>
</div>
</body>
</html>