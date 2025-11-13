<?php
session_start();
// Đồng bộ đường dẫn include/require
require __DIR__ . '/../includes/db.php'; 
require __DIR__ . '/../templates/adminheader.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Kiểm tra biến $pdo
if (!isset($pdo)) {
    die("Lỗi: Không thể kết nối CSDL (PDO). Vui lòng kiểm tra file includes/db.php.");
}

// Hàm tự tạo mã hàng (Đã chuyển sang dùng PDO)
function getNextProductCode($pdo) {
    $sql = "SELECT MAX(mahang) AS max_code FROM tbmathang WHERE mahang LIKE 'MH%'";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxCode = $row['max_code'];
    
    if ($maxCode) {
        $numPart = substr($maxCode, 2);
        $nextNum = intval($numPart) + 1;
        if ($nextNum < 1000) {
            $newCode = 'MH' . sprintf("%03d", $nextNum);
        } else {
            $newCode = 'MH' . $nextNum;
        }
    } else {
        $newCode = 'MH001';
    }
    
    return $newCode;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sử dụng hàm để tự động tạo mã hàng
    $mahang = getNextProductCode($pdo); // Truyền $pdo thay vì $conn
    $tenhang    = trim($_POST['tenhang']);
    $mota       = trim($_POST['mota']);
    $dongia     = floatval($_POST['dongia']);
    $nguongoc   = trim($_POST['nguongoc']);
    $thuonghieu = trim($_POST['thuonghieu']);
    $conhang    = trim($_POST['conhang']);
    $hinhanh    = '';

    // Xử lý tải lên hình ảnh (Phần này sử dụng PHP cơ bản, giữ nguyên logic cơ bản, nhưng sẽ chuyển sang PDO cho phần INSERT)
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['hinhanh']['tmp_name'];
        $fileName = $_FILES['hinhanh']['name'];
        $fileSize = $_FILES['hinhanh']['size'];
        $fileType = $_FILES['hinhanh']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $dest_path = __DIR__ . "/../assets/images/" . $newFileName;

        if(move_uploaded_file($fileTmpPath, $dest_path)) {
            $hinhanh = $newFileName;
        }
    }
    
    // Sử dụng PDO prepared statement để INSERT
    try {
        $sql = "INSERT INTO tbmathang (mahang, tenhang, mota, dongia, nguongoc, thuonghieu, conhang, hinhanh) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mahang, $tenhang, $mota, $dongia, $nguongoc, $thuonghieu, $conhang, $hinhanh]);
        
        // Thông báo thành công
        echo "<div class='container' style='text-align: center; margin-top: 50px;'>";
        echo "<h2 style='color: #28A745;'>Thêm sản phẩm thành công!</h2>";
        echo "<p>Mã hàng: <strong>" . htmlspecialchars($mahang) . "</strong></p>";
        echo "<a href='manage_products.php' style='display: inline-block; padding: 10px 20px; background: #28A745; color: white; text-decoration: none; border-radius: 5px;'>Xong</a>";
        echo "</div>";
        exit;

    } catch (PDOException $e) {
        // Xử lý lỗi
        echo "<div class='container' style='text-align: center; margin-top: 50px;'>";
        echo "<h2 style='color: #DC3545;'>Lỗi khi thêm sản phẩm:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<a href='add_product.php' style='display: inline-block; padding: 10px 20px; background: #007BFF; color: white; text-decoration: none; border-radius: 5px;'>Thử lại</a>";
        echo "</div>";
        exit;
    }
}
?>

<h2 style="text-align: center;">Thêm sản phẩm</h2>
<form method="post" enctype="multipart/form-data" style="max-width: 500px; margin: auto;">
    <p style="text-align:center;">Mã hàng sẽ được tạo tự động: <strong><?= getNextProductCode($pdo) ?></strong></p>
    <label>Tên hàng: <input type="text" name="tenhang" class="form-control" required></label><br><br>
    <label>Mô tả chi tiết: <textarea name="mota" class="form-control"></textarea></label><br><br>
    <label>Đơn giá: <input type="number" step="0.01" name="dongia" class="form-control" required></label><br><br>
    <label>Nguồn gốc: <input type="text" name="nguongoc" class="form-control" required></label><br><br>
    <label>Thương hiệu: <input type="text" name="thuonghieu" class="form-control" required></label><br><br>
    
    <div class="form-group">
        <label>Còn hàng:</label><br>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="conhang" id="conhang_yes" value="1" checked>
            <label class="form-check-label" for="conhang_yes">Có</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="conhang" id="conhang_no" value="0">
            <label class="form-check-label" for="conhang_no">Không</label>
        </div>
    </div>
    
    <label>Hình ảnh chính: <input type="file" name="hinhanh" class="form-control-file" accept="image/*" required></label><br><br>
    
    <button type="submit" class="btn btn-primary" style="display: block; width: 100%; margin-top: 20px;">Thêm sản phẩm</button>
</form>