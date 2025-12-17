<?php
// admin/assign_tracking_code.php
session_start();

// SỬA LẠI ĐƯỜNG DẪN NÀY CHO ĐÚNG VỊ TRÍ FILE db.php CỦA BẠN!
require __DIR__ . '/../includes/db.php'; 

// Cần khai báo $pdo là biến toàn cục
global $pdo; 


$message = $_GET['msg'] ?? '';

if (!isset($pdo) || !$pdo) {
    // Nếu vẫn lỗi, dừng và báo lỗi chi tiết đường dẫn
    die("Lỗi: Biến \$pdo không tồn tại. Vui lòng kiểm tra lại đường dẫn file db.php: " . __DIR__ . '/../db.php');
}

// Lấy danh sách đơn hàng đã xác nhận nhưng chưa có Mã Vận Đơn
try {
    $sql = "SELECT madonhang, tennguoinhan, diachi, sdt 
            FROM tbdonhang
            WHERE mavandon IS NULL AND tinhtrang IN ('Đang xử lý', 'Đã xác nhận') 
            ORDER BY ngaymua ASC";
    $stmt = $pdo->prepare($sql); // Sử dụng prepare() cho truy vấn
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Lỗi CSDL khi lấy đơn hàng: " . $e->getMessage());
    die("LỖI CSDL: Không thể tải danh sách đơn hàng. Vui lòng kiểm tra log.");
}
if (file_exists(__DIR__ . '/../templates/adminheader.php')) {
    include __DIR__ . '/../templates/adminheader.php';
}
?>

<style>
/* ... (Giữ nguyên khối CSS Clean Minimalism & Card-based Design) ... */
    :root {
        --primary-color: #007bff;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --card-bg: #fff;
        --border-color: #e9ecef;
        --shadow-light: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    .card-clean {
        background: var(--card-bg);
        border-radius: 8px;
        box-shadow: var(--shadow-light);
        padding: 20px;
        margin-bottom: 25px;
    }
    .card-clean h2 {
        color: #343a40;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 10px;
        margin-top: 0;
        margin-bottom: 20px;
    }
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
        font-weight: 600;
    }
    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    .table-custom {
        width: 100%;
        border-collapse: collapse;
    }
    .table-custom th, .table-custom td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    .table-custom th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #495057;
    }
    .table-custom tbody tr:hover {
        background-color: #f2f2f2;
    }
    .btn-primary {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
</style>
<div class="card-clean">
    <h2>🎯 Gán mã vận đơn GHTK</h2>
    
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Mã Đơn Hàng</th>
                    <th>Thông tin Khách hàng</th>
                    <th width="300px">Gán mã vận đơn GHTK</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="3" style="text-align: center; color: #777; padding: 20px;">Không có đơn hàng nào cần gán mã vận đơn.</td></tr>
                <?php endif; ?>

                <?php foreach ($orders as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['madonhang']); ?></td>
                    <td>
                        Tên: **<?php echo htmlspecialchars($row['tennguoinhan']); ?>**<br>
                        SĐT: <?php echo htmlspecialchars($row['sdt']); ?><br>
                        Địa chỉ: <?php echo htmlspecialchars($row['diachi']); ?>
                    </td>
                    <td>
                        <form method="POST" action="process_tracking.php" style="display: flex; gap: 10px;">
                            <input type="hidden" name="madonhang" value="<?php echo htmlspecialchars($row['madonhang']); ?>">
                            <input type="text" name="mavandon" placeholder="Nhập mã vận đơn GHTK" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; flex-grow: 1;">
                            <button type="submit" name="action" value="assign_code" class="btn btn-primary" style="white-space: nowrap;">Gán mã</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
