<?php
ob_start();
session_start();
include '../includes/db.php'; // Kết nối cơ sở dữ liệu

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// **********************************************
// ********** LOGIC XỬ LÝ HỦY ĐƠN HÀNG **********
// **********************************************
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $cancel_order_id = $_POST['order_id'] ?? '';
    
    if (!empty($cancel_order_id)) {
        $conn->begin_transaction();
        try {
            // BƯỚC 1: KIỂM TRA QUYỀN VÀ TRẠNG THÁI HỢP LỆ (Dùng FOR UPDATE để khóa dòng)
            $check_sql = "
                SELECT tinhtrang 
                FROM tbDonHang dh
                JOIN tbKhachHang kh ON dh.makhach = kh.makhach
                WHERE dh.madonhang = ? AND kh.username = ?
                FOR UPDATE
            ";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("ss", $cancel_order_id, $username); 
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows === 0) {
                throw new Exception("Đơn hàng không tồn tại hoặc bạn không có quyền hủy.");
            }
            
            $order_info = $result_check->fetch_assoc();
            $current_status = $order_info['tinhtrang'];

            // Sửa lỗi: Kiểm tra các trạng thái KHÔNG cho phép hủy
            $statuses_not_allowed_to_cancel = ['Đã giao', 'Đang giao hàng', 'Đã hủy'];
            
            if (in_array($current_status, $statuses_not_allowed_to_cancel)) {
                throw new Exception("Đơn hàng không thể hủy vì đang ở trạng thái: " . htmlspecialchars($current_status));
            }
            
            // BƯỚC 2: CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
            $update_sql = "UPDATE tbDonHang SET tinhtrang = 'Đã hủy' WHERE madonhang = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("s", $cancel_order_id);
            $stmt_update->execute();
            
            if ($stmt_update->affected_rows === 0) {
                throw new Exception("Không thể cập nhật trạng thái hủy đơn hàng.");
            }
            
            $conn->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đơn hàng ' . htmlspecialchars($cancel_order_id) . ' đã được hủy thành công.'];
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Lỗi hủy đơn hàng: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Mã đơn hàng hủy không hợp lệ.'];
    }
    
    // Chuyển hướng về trang danh sách đơn hàng sau khi xử lý
    header("Location: account.php?tab=orders"); 
    exit;
}
// **********************************************
// ********** KẾT THÚC LOGIC HỦY ĐƠN HÀNG *******
// **********************************************


$madonhang = isset($_GET['madonhang']) ? $_GET['madonhang'] : '';

// Kiểm tra mã đơn hàng hợp lệ
if (empty($madonhang)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Mã đơn hàng không hợp lệ!'];
    header("Location: account.php?tab=orders");
    exit;
}

// 2. Truy vấn thông tin chi tiết đơn hàng (Bảo mật: Chỉ lấy đơn hàng thuộc về khách hàng hiện tại)
$sql_order = "
    SELECT 
        dh.*, 
        kh.tenkhach, 
        kh.sodienthoai,
        kh.diachi
    FROM 
        tbDonHang dh
    JOIN 
        tbkhachhang kh ON dh.makhach = kh.makhach
    WHERE 
        dh.madonhang = ? AND kh.username = ?
";
$stmt_order = $conn->prepare($sql_order);

if (!$stmt_order) {
    // Xử lý lỗi chuẩn bị truy vấn
    error_log("SQL Error: " . $conn->error);
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Lỗi hệ thống: Không thể chuẩn bị truy vấn.'];
    header("Location: account.php?tab=orders");
    exit;
}

$stmt_order->bind_param("ss", $madonhang, $username);
$stmt_order->execute();
$order_result = $stmt_order->get_result();

if ($order_result->num_rows == 0) {
    // Đơn hàng không tồn tại hoặc không thuộc về khách hàng này
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Bạn không có quyền xem đơn hàng này hoặc đơn hàng không tồn tại.'];
    header("Location: account.php?tab=orders");
    exit;
}

$order = $order_result->fetch_assoc();
$makhach = $order['makhach']; // Lấy mã khách hàng để dùng cho việc hủy nếu cần

// 3. Truy vấn chi tiết các mặt hàng trong đơn hàng
$sql_items = "
    SELECT 
        ctdh.*, 
        mh.tenhang
    FROM 
        tbChiTietDonHang ctdh
    JOIN 
        tbMathang mh ON ctdh.mahang = mh.mahang
    WHERE 
        ctdh.madonhang = ?
";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("s", $madonhang);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

// --- Hiển thị giao diện ---
include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= htmlspecialchars($madonhang) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            padding: 20px 0;
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h2 {
            color: #1a2b6d;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-summary, .customer-info {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
        }
        .order-summary h3, .customer-info h3 {
            color: #d4af37;
            margin-top: 0;
            border-bottom: 1px dashed #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .order-summary p, .customer-info p {
            margin: 8px 0;
        }
        .order-summary p strong, .customer-info p strong {
            color: #1a2b6d;
            display: inline-block;
            width: 150px; /* Cố định chiều rộng cho label */
        }
        /* Chi tiết sản phẩm */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .items-table th {
            background-color: #1a2b6d;
            color: white;
            font-weight: bold;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .items-table tfoot td {
            font-weight: bold;
            text-align: right;
            border-top: 2px solid #1a2b6d;
        }
        .items-table tfoot td:first-child {
            text-align: left;
        }
        .status-badge {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            color: #fff;
        }
        .status-Đang-xử-lý, .status-Chờ-xử-lý { background-color: #007bff; }
        .status-Đang-giao-hàng { background-color: #ffc107; color: #333; }
        .status-Đã-giao { background-color: #28a745; }
        .status-Đã-hủy { background-color: #dc3545; }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>
            Chi tiết đơn hàng #<?= htmlspecialchars($order['madonhang']) ?>
            <a href="account.php?tab=orders" class="back-btn"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </h2>

        <div class="order-summary">
            <h3>Tóm tắt đơn hàng</h3>
            <?php
            // Chuẩn hóa tên trạng thái để dùng làm tên class (loại bỏ dấu cách)
            $tinhtrang_slug = str_replace(' ', '-', $order['tinhtrang']);
            ?>
            <p><strong>Ngày đặt hàng:</strong> <?= date('d/m/Y H:i', strtotime($order['ngaymua'])) ?></p>
            <p><strong>Tình trạng:</strong> 
                <span class="status-badge status-<?= htmlspecialchars($tinhtrang_slug) ?>">
                    <?= htmlspecialchars($order['tinhtrang']) ?>
                </span>
            </p>
        </div>

        <div class="customer-info">
            <h3>Thông tin giao hàng</h3>
            <p><strong>Người nhận:</strong> <?= htmlspecialchars($order['tenkhach']) ?></p>
            <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['sodienthoai']) ?></p>
            <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['diachi']) ?></p>
            </div>

        <h3>Danh sách sản phẩm</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">STT</th>
                    <th style="width: 50%;">Sản phẩm</th>
                    <th style="width: 15%; text-align: center;">Số lượng</th>
                    <th style="width: 15%; text-align: right;">Đơn giá</th>
                    <th style="width: 15%; text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stt = 1;
                $tong_cong = 0;
                while ($item = $items_result->fetch_assoc()):
                    $thanhtien = $item['soluong'] * $item['dongia'];
                    $tong_cong += $thanhtien;
                ?>
                <tr>
                    <td style="text-align: center;"><?= $stt++ ?></td>
                    <td><?= htmlspecialchars($item['tenhang']) ?> (Mã: <?= htmlspecialchars($item['mahang']) ?>)</td>
                    <td style="text-align: center;"><?= htmlspecialchars($item['soluong']) ?></td>
                    <td style="text-align: right;"><?= number_format($item['dongia'], 0) ?> VND</td>
                    <td style="text-align: right;"><?= number_format($thanhtien, 0) ?> VND</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Tổng cộng (chưa bao gồm phí vận chuyển/thuế):</td>
                    <td style="text-align: right;"><?= number_format($tong_cong, 0) ?> VND</td>
                </tr>
                <tr>
                    <td colspan="4">Thành tiền cuối cùng:</td>
                    <td style="text-align: right; color: #a61c00; font-size: 1.1em;"><?= number_format($tong_cong, 0) ?> VND</td>
                </tr>
            </tfoot>
        </table>

        <?php 
        // Chỉ hiện nút Hủy nếu đơn hàng ở trạng thái cho phép hủy
        // Trạng thái 'Đang xử lý' hoặc 'Chờ xử lý' sẽ cho phép hủy
        $can_cancel = !in_array($order['tinhtrang'], array('Đã giao', 'Đang giao hàng', 'Đã hủy'));
        if ($can_cancel): 
        ?>
        <div style="text-align: center; margin-top: 30px;">
            <form action="order_detail.php?madonhang=<?= htmlspecialchars($order['madonhang']) ?>" method="post" onsubmit="return confirm('Bạn có chắc chắn muốn HỦY đơn hàng này? Thao tác này không thể hoàn tác.');">
                <input type="hidden" name="action" value="cancel_order">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['madonhang']) ?>">
                <button type="submit" style="padding: 10px 30px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                    <i class="fas fa-times-circle"></i> Hủy Đơn Hàng
                </button>
            </form>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
<?php include '../templates/footer.php'; ?>