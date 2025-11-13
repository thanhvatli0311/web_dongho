<?php
ob_start();
session_start();
include '../includes/db.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];

// Logic xử lý hủy đơn hàng (giữ nguyên)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $cancel_order_id = $_POST['order_id'] ?? '';
    if (!empty($cancel_order_id)) {
        $pdo->beginTransaction();
        try {
            $check_sql = "SELECT tinhtrang FROM tbDonHang dh JOIN tbKhachHang kh ON dh.makhach = kh.makhach WHERE dh.madonhang = ? AND kh.username = ? FOR UPDATE";
            $stmt_check = $pdo->prepare($check_sql);
            $stmt_check->execute([$cancel_order_id, $username]);
            $order_info = $stmt_check->fetch();
            
            if (!$order_info) throw new Exception("Đơn hàng không tồn tại hoặc bạn không có quyền hủy.");
            
            if (in_array($order_info['tinhtrang'], ['Đã giao', 'Đang giao hàng', 'Đã hủy'])) {
                throw new Exception("Đơn hàng không thể hủy vì đang ở trạng thái: " . htmlspecialchars($order_info['tinhtrang']));
            }
            
            $update_sql = "UPDATE tbDonHang SET tinhtrang = 'Đã hủy' WHERE madonhang = ?";
            $stmt_update = $pdo->prepare($update_sql);
            $stmt_update->execute([$cancel_order_id]);
            
            $pdo->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đơn hàng ' . htmlspecialchars($cancel_order_id) . ' đã được hủy thành công.'];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Lỗi hủy đơn hàng: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Mã đơn hàng hủy không hợp lệ.'];
    }
    header("Location: account.php?tab=orders"); 
    exit;
}

$madonhang = $_GET['madonhang'] ?? '';
if (empty($madonhang)) {
    header("Location: account.php?tab=orders");
    exit;
}

try {
    // Truy vấn thông tin đơn hàng và chi tiết sản phẩm
    $sql_order = "SELECT dh.*, kh.tenkhach, kh.sodienthoai, kh.diachi FROM tbDonHang dh JOIN tbkhachhang kh ON dh.makhach = kh.makhach WHERE dh.madonhang = ? AND kh.username = ?";
    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->execute([$madonhang, $username]);
    $order = $stmt_order->fetch();

    if (!$order) {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Bạn không có quyền xem đơn hàng này.'];
        header("Location: account.php?tab=orders");
        exit;
    }

    $sql_items = "SELECT ctdh.*, mh.tenhang, mh.hinhanh FROM tbChiTietDonHang ctdh JOIN tbMathang mh ON ctdh.mahang = mh.mahang WHERE ctdh.madonhang = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$madonhang]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("SQL Error: " . $e->getMessage());
    header("Location: account.php?tab=orders");
    exit;
}

include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= htmlspecialchars($madonhang) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- === CSS TỐI GIẢN ĐỒNG BỘ VỚI TRANG ACCOUNT === -->
    <style>
        :root {
            --color-primary: #007bff;
            --color-danger: #dc3545;
            --color-danger-hover: #c82333;
            --color-secondary: #6c757d;
            --color-secondary-hover: #5a6268;
            --text-dark: #212529;
            --text-light: #6c757d;
            --bg-light: #f8f9fa;
            --bg-white: #ffffff;
            --border-color: #dee2e6;
            --font-family-sans-serif: 'Inter', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-family-sans-serif);
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .container { max-width: 960px; margin: 2rem auto; padding: 0 1rem; }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-header h2 { font-size: 1.75rem; font-weight: 600; }
        .page-header h2 .order-id { color: var(--color-primary); }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
            color: #fff;
        }
        .btn-secondary { background-color: var(--color-secondary); }
        .btn-secondary:hover { background-color: var(--color-secondary-hover); }
        .btn-danger { background-color: var(--color-danger); }
        .btn-danger:hover { background-color: var(--color-danger-hover); }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .info-card {
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
        }
        .info-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
        }
        .info-card p { margin-bottom: 0.5rem; color: var(--text-light); }
        .info-card p strong { color: var(--text-dark); font-weight: 500; }

        .order-status {
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            display: inline-block;
        }
        .status-success { color: #155724; background-color: #d4edda; }
        .status-danger { color: #721c24; background-color: #f8d7da; }
        .status-warning { color: #856404; background-color: #fff3cd; }
        .status-primary { color: #004085; background-color: #cce5ff; }

        .product-list-header {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }
        .product-table th, .product-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .product-table thead th {
            background-color: var(--bg-light);
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
            color: var(--text-light);
        }
        .product-table tbody tr:last-child td { border-bottom: none; }
        .product-table .text-center { text-align: center; }
        .product-table .text-right { text-align: right; }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }
        .product-name { font-weight: 500; }
        .total-row th, .total-row td {
            font-weight: 600;
            font-size: 1.1em;
            background-color: var(--bg-light);
        }
        .total-amount { color: var(--color-danger); }

        .cancel-order-container { text-align: center; margin-top: 2rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>Chi tiết đơn hàng <span class="order-id">#<?= htmlspecialchars($madonhang) ?></span></h2>
            <a href="account.php?tab=orders" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h4><i class="fas fa-user-circle"></i> Thông tin giao hàng</h4>
                <p><strong>Người nhận:</strong> <?= htmlspecialchars($order['tenkhach']) ?></p>
                <p><strong>Điện thoại:</strong> <?= htmlspecialchars($order['sodienthoai']) ?></p>
                <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['diachi']) ?></p>
            </div>
            <div class="info-card">
                <h4><i class="fas fa-receipt"></i> Tóm tắt đơn hàng</h4>
                <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['ngaymua'])) ?></p>
                <?php
                    $status_class = '';
                    switch ($order['tinhtrang']) {
                        case 'Đã giao': $status_class = 'status-success'; break;
                        case 'Đã hủy': $status_class = 'status-danger'; break;
                        case 'Đang giao hàng': $status_class = 'status-warning'; break;
                        default: $status_class = 'status-primary'; break;
                    }
                ?>
                <p><strong>Tình trạng:</strong> <span class="order-status <?= $status_class ?>"><?= htmlspecialchars($order['tinhtrang']) ?></span></p>
            </div>
        </div>

        <h4 class="product-list-header"><i class="fas fa-box-open"></i> Danh sách sản phẩm</h4>
        <table class="product-table">
            <thead>
                <tr>
                    <th colspan="2">Sản phẩm</th>
                    <th class="text-center">Số lượng</th>
                    <th class="text-right">Đơn giá</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $tong_cong = 0;
                if (!empty($items)):
                    foreach ($items as $item):
                        $thanhtien = $item['dongia'] * $item['soluong'];
                        $tong_cong += $thanhtien;
                        $img_path = !empty($item['hinhanh']) ? "../assets/images/" . htmlspecialchars($item['hinhanh']) : "https://via.placeholder.com/60";
                ?>
                <tr>
                    <td style="width: 80px;">
                        <img src="<?= $img_path ?>" alt="Ảnh sản phẩm" class="product-image">
                    </td>
                    <td class="product-name"><?= htmlspecialchars($item['tenhang']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['soluong']) ?></td>
                    <td class="text-right"><?= number_format($item['dongia'], 0, ',', '.') ?> VND</td>
                    <td class="text-right font-weight-bold"><?= number_format($thanhtien, 0, ',', '.') ?> VND</td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" class="text-center">Đơn hàng không có sản phẩm nào.</td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <th colspan="4" class="text-right">Tổng cộng:</th>
                    <td class="text-right total-amount"><?= number_format($tong_cong, 0, ',', '.') ?> VND</td>
                </tr>
            </tfoot>
        </table>

        <?php if (!in_array($order['tinhtrang'], ['Đã giao', 'Đang giao hàng', 'Đã hủy'])): ?>
        <div class="cancel-order-container">
            <form action="order_detail.php?madonhang=<?= htmlspecialchars($order['madonhang']) ?>" method="post" onsubmit="return confirm('Bạn có chắc chắn muốn HỦY đơn hàng này?');">
                <input type="hidden" name="action" value="cancel_order">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['madonhang']) ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times-circle"></i> Hủy Đơn Hàng
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php 
include '../templates/footer.php'; 
?>