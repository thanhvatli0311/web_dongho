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
    // Sửa lỗi cú pháp MySQL: Thay thế dấu ngoặc kép bằng dấu nháy đơn hoặc dấu backtick cho tên cột
    $sql = "SELECT MAX(mahang) AS max_code FROM tbmathang WHERE mahang LIKE 'MH%'";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxCode = $row['max_code'];
    
    if ($maxCode) {
        $numPart = substr($maxCode, 2);
        $nextNum = intval($numPart) + 1;
        // Đảm bảo phần số luôn có ít nhất 3 chữ số (ví dụ: MH001)
        $newCode = 'MH' . sprintf("%03d", $nextNum);
    } else {
        $newCode = 'MH001';
    }
    
    return $newCode;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Chuẩn bị dữ liệu
    $mahang = getNextProductCode($pdo); // Tự động tạo mã hàng
    $tenhang    = trim($_POST['tenhang']);
    $mota       = trim($_POST['mota']);
    $dongia     = floatval($_POST['dongia']);
    $nguongoc   = trim($_POST['nguongoc']);
    $thuonghieu = trim($_POST['thuonghieu']);
    $conhang    = trim($_POST['conhang']);
    $hinhanh    = '';

    // 2. Xử lý tải lên Hình ảnh chính (hinhanh)
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['hinhanh']['tmp_name'];
        $fileName = $_FILES['hinhanh']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $dest_path = __DIR__ . "/../assets/images/" . $newFileName;

        if(move_uploaded_file($fileTmpPath, $dest_path)) {
            $hinhanh = $newFileName;
        }
    }
    
    // 3. Sử dụng PDO prepared statement để INSERT vào tbmathang
    try {
        $sql = "INSERT INTO tbmathang (mahang, tenhang, mota, dongia, nguongoc, thuonghieu, conhang, hinhanh) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mahang, $tenhang, $mota, $dongia, $nguongoc, $thuonghieu, $conhang, $hinhanh]);
        
        // 4. THÊM LOGIC: Xử lý tải lên và lưu Hình ảnh chi tiết vào tbhinhanhchitiet
        if (isset($_FILES['hinhanh_chitiet'])) {
            $files = $_FILES['hinhanh_chitiet'];
            $count = count($files['name']);

            for ($i = 0; $i < $count; $i++) {
                // Kiểm tra xem file có được tải lên thành công không
                if ($files['error'][$i] == UPLOAD_ERR_OK) {
                    $fileTmpPath = $files['tmp_name'][$i];
                    $fileName = $files['name'][$i];
                    $fileNameCmps = explode(".", $fileName);
                    $fileExtension = strtolower(end($fileNameCmps));
                    
                    // Tạo tên file duy nhất, thêm $i để tránh trùng lặp
                    $newFileName = md5(time() . $i . $fileName) . '.' . $fileExtension;
                    $dest_path = __DIR__ . "/../assets/images/" . $newFileName;

                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Lưu tên file vào bảng tbhinhanhchitiet
                        $sql_detail = "INSERT INTO tbhinhanhchitiet (mahang, hinhanh_chitiet) VALUES (?, ?)";
                        $stmt_detail = $pdo->prepare($sql_detail);
                        $stmt_detail->execute([$mahang, $newFileName]); 
                    }
                }
            }
        }
        
        // Thông báo thành công (Sử dụng Bootstrap alert)
        echo "<div class='container' style='text-align: center; margin-top: 50px;'>";
        echo "<div class='alert alert-success' role='alert'>";
        echo "<h2>Thêm sản phẩm thành công!</h2>";
        echo "<p>Mã hàng: <strong>" . htmlspecialchars($mahang) . "</strong></p>";
        echo "<a href='manage_products.php' class='btn btn-success mt-3'>Quản lý sản phẩm</a>";
        echo "</div>";
        echo "</div>";
        exit;

    } catch (PDOException $e) {
        // Xử lý lỗi (Sử dụng Bootstrap alert)
        echo "<div class='container' style='text-align: center; margin-top: 50px;'>";
        echo "<div class='alert alert-danger' role='alert'>";
        echo "<h2>Lỗi khi thêm sản phẩm:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<a href='add_product.php' class='btn btn-primary mt-3'>Thử lại</a>";
        echo "</div>";
        echo "</div>";
        exit;
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">Thêm sản phẩm mới</h2>
        </div>
        <div class="card-body">
            <p class="text-center text-muted">Mã hàng sẽ được tạo tự động: <strong><?= getNextProductCode($pdo) ?></strong></p>
            
            <form method="post" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="tenhang">Tên hàng:</label>
                    <input type="text" name="tenhang" id="tenhang" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="mota">Mô tả chi tiết:</label>
                    <textarea name="mota" id="mota" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="dongia">Đơn giá:</label>
                    <input type="number" step="0.01" name="dongia" id="dongia" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="nguongoc">Nguồn gốc:</label>
                    <input type="text" name="nguongoc" id="nguongoc" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="thuonghieu">Thương hiệu:</label>
                    <input type="text" name="thuonghieu" id="thuonghieu" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Tình trạng:</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="conhang" id="conhang_yes" value="1" checked>
                        <label class="form-check-label" for="conhang_yes">Còn hàng</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="conhang" id="conhang_no" value="0">
                        <label class="form-check-label" for="conhang_no">Hết hàng</label>
                    </div>
                </div>
                
                <div class="form-group border-top pt-3">
                    <label for="hinhanh_chinh">Hình ảnh chính (Đại diện):</label>
                    <input type="file" name="hinhanh" id="hinhanh_chinh" class="form-control-file" accept="image/*" required>
                </div>

                <div class="form-group border-top pt-3">
                    <label for="hinhanh_chitiet">Hình ảnh chi tiết (Chọn nhiều ảnh):</label>
                    <input type="file" name="hinhanh_chitiet[]" id="hinhanh_chitiet" class="form-control-file" accept="image/*" multiple>
                    <small class="form-text text-muted">Chọn nhiều file ảnh để hiển thị chi tiết sản phẩm.</small>
                </div>
                
                <button type="submit" class="btn btn-success btn-block mt-4">Thêm sản phẩm</button>
            </form>
        </div>
    </div>
</div>