<?php
ob_start();
session_start();
include '../includes/db.php';

if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

$customer_sql = "SELECT * FROM tbkhachhang WHERE username = ?";
$stmt_customer = $pdo->prepare($customer_sql);
$stmt_customer->execute([$username]);
$customer = $stmt_customer->fetch();

if (!$customer) {
    echo "<div class='flash-message error'>Khách hàng không tồn tại!</div>";
    exit;
}
$makhach = $customer['makhach'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'cancel_order') {
            $order_id = $_POST['order_id'];
            $sql_cancel = "UPDATE tbDonHang SET tinhtrang = 'Đã hủy' WHERE madonhang = ? AND makhach = ? AND tinhtrang IN ('Mới', 'Chờ xử lý')";
            $stmt_cancel = $pdo->prepare($sql_cancel);
            $stmt_cancel->execute([$order_id, $makhach]);
            
            if ($stmt_cancel->rowCount() == 0) {
                throw new Exception("Đơn hàng không thể hủy: Không tìm thấy hoặc đã được xử lý.");
            }
            
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đơn hàng đã được hủy thành công!'];
            header("Location: account.php?tab=orders");
            exit;

        } elseif ($_POST['action'] == 'update_profile') {
            $tenkhach = trim($_POST['tenkhach']);
            $ngaysinh = $_POST['ngaysinh'];
            $gioitinh = $_POST['gioitinh'];
            $sodienthoai = trim($_POST['sodienthoai']);
            $diachi = trim($_POST['diachi']);
            
            if (empty($tenkhach) || empty($sodienthoai)) {
                 throw new Exception("Tên và Số điện thoại không được để trống!");
            }

            $sql_update = "UPDATE tbkhachhang SET tenkhach=?, ngaysinh=?, gioitinh=?, sodienthoai=?, diachi=? WHERE username=?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$tenkhach, $ngaysinh, $gioitinh, $sodienthoai, $diachi, $username]);

            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cập nhật thông tin thành công!'];
            header("Location: account.php?tab=info&subtab=profile");
            exit;

        } elseif ($_POST['action'] == 'change_password') {
            $old_password = $_POST['old_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (!preg_match('/^(?=.*[A-Z])(?=.*\W).{8,}$/', $new_password)) {
                throw new Exception('Mật khẩu mới phải có tối thiểu 8 ký tự, 1 chữ hoa và 1 ký tự đặc biệt!');
            }
            if ($new_password !== $confirm_password) {
                throw new Exception('Mật khẩu mới và xác nhận không khớp!');
            }
            
            $sql_user = "SELECT password FROM tbuser WHERE username = ?";
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute([$username]);
            $user_data = $stmt_user->fetch();

            if (!$user_data || !password_verify($old_password, $user_data['password'])) {
                throw new Exception('Mật khẩu cũ không chính xác!');
            }
            
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update_pw = "UPDATE tbuser SET password=? WHERE username=?";
            $stmt_update_pw = $pdo->prepare($sql_update_pw);
            $stmt_update_pw->execute([$new_hash, $username]);

            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đổi mật khẩu thành công!'];
            header("Location: account.php?tab=info&subtab=changepass");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => $e->getMessage()];
        $redirect_url = "account.php?tab=" . ($_POST['action'] == 'cancel_order' ? 'orders' : 'info');
        if (isset($_POST['action']) && $_POST['action'] == 'change_password') $redirect_url .= "&subtab=changepass";
        if (isset($_POST['action']) && $_POST['action'] == 'update_profile') $redirect_url .= "&subtab=profile";
        header("Location: $redirect_url");
        exit;
    }
}

$tab = $_GET['tab'] ?? 'info';
$subtab = $_GET['subtab'] ?? 'profile';

include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của tôi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- === CSS THEO PHONG CÁCH TỐI GIẢN === -->
    <style>
        /* 1. Thiết lập biến và Global Reset */
        :root {
            --color-primary: #007bff; /* Xanh dương làm màu nhấn */
            --color-primary-hover: #0056b3;
            --color-danger: #dc3545;
            --color-danger-hover: #c82333;
            --color-success: #28a745;
            --color-warning: #ffc107;
            --text-dark: #212529;
            --text-light: #6c757d;
            --bg-light: #f8f9fa;
            --bg-white: #ffffff;
            --border-color: #dee2e6;
            --font-family-sans-serif: 'Inter', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family-sans-serif);
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* 2. Layout chính */
        .account-container {
            display: flex;
            max-width: 1200px;
            margin: 3rem auto;
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .sidebar {
            width: 260px;
            background: var(--bg-white);
            border-right: 1px solid var(--border-color);
            padding: 1.5rem 0;
        }

        .main-content {
            flex: 1;
            padding: 2.5rem;
        }

        /* 3. Sidebar Navigation */
        .sidebar-nav { list-style: none; }
        .sidebar-nav-item > a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            color: var(--text-light);
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s ease-in-out;
        }
        .sidebar-nav-item > a:hover {
            color: var(--color-primary);
            background-color: #f1f5f9;
        }
        .sidebar-nav-item > a.active {
            color: var(--color-primary);
            border-left-color: var(--color-primary);
            font-weight: 600;
        }

        .submenu {
            list-style: none;
            padding-left: 2rem; /* Thụt vào so với menu cha */
            margin: 0.5rem 0;
        }
        .submenu-item a {
            padding: 0.5rem 1.5rem;
            font-size: 0.9em;
            color: var(--text-light);
            display: block;
            text-decoration: none;
            border-radius: 5px;
        }
        .submenu-item a.active,
        .submenu-item a:hover {
            background-color: #eef2ff;
            color: var(--color-primary);
            font-weight: 500;
        }

        /* 4. Main Content Elements */
        .main-content h2 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 2rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }

        .gender-group { display: flex; align-items: center; gap: 1.5rem; }
        .gender-group label { display: flex; align-items: center; gap: 0.5rem; font-weight: 400; cursor: pointer; }
        .show-password-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9em; color: var(--text-light); }

        .form-actions { margin-top: 2rem; text-align: left; }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }
        .btn-primary { background-color: var(--color-primary); color: #fff; }
        .btn-primary:hover { background-color: var(--color-primary-hover); }
        .btn-danger { background-color: var(--color-danger); color: #fff; }
        .btn-danger:hover { background-color: var(--color-danger-hover); }
        .btn-secondary { background-color: #6c757d; color: #fff; }
        .btn-secondary:hover { background-color: #5a6268; }

        /* 5. Orders List */
        .order-card {
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            background: #fff;
        }
        .order-header, .order-details, .order-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .order-header { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f0f0f0; }
        .order-id { font-weight: 600; color: var(--text-dark); }
        .order-date { color: var(--text-light); font-size: 0.9em; }
        .order-total { font-size: 1.1em; font-weight: 600; }
        .order-status { font-weight: 500; padding: 4px 10px; border-radius: 20px; font-size: 0.8em; }
        .status-success { color: #155724; background-color: #d4edda; }
        .status-danger { color: #721c24; background-color: #f8d7da; }
        .status-warning { color: #856404; background-color: #fff3cd; }
        .status-primary { color: #004085; background-color: #cce5ff; }
        .no-orders { text-align: center; font-size: 1.1rem; color: #777; padding: 3rem 0; }
        
        /* 6. Flash Messages */
        .flash-message {
            padding: 1rem;
            border-radius: 6px;
            margin: 0 auto 1.5rem auto;
            max-width: 1200px;
            text-align: center;
            border: 1px solid transparent;
        }
        .flash-message.success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .flash-message.error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }

    </style>
</head>
<body>
    <?php
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        $typeClass = ($flash['type'] == 'success') ? 'success' : 'error';
        echo "<div class='flash-message $typeClass'>" . htmlspecialchars($flash['message']) . "</div>";
        unset($_SESSION['flash_message']);
    }
    ?>
    <div class="account-container">
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="account.php?tab=info" class="<?= ($tab == 'info') ? 'active' : '' ?>">
                        <i class="fas fa-user-circle"></i> Thông tin tài khoản
                    </a>
                    <?php if ($tab == 'info'): ?>
                    <ul class="submenu">
                        <li class="submenu-item"><a href="account.php?tab=info&subtab=profile" class="<?= ($subtab == 'profile') ? 'active' : '' ?>">Hồ sơ của bạn</a></li>
                        <li class="submenu-item"><a href="account.php?tab=info&subtab=changepass" class="<?= ($subtab == 'changepass') ? 'active' : '' ?>">Đổi mật khẩu</a></li>
                    </ul>
                    <?php endif; ?>
                </li>
                <li class="sidebar-nav-item"><a href="account.php?tab=orders" class="<?= ($tab == 'orders') ? 'active' : '' ?>"><i class="fas fa-receipt"></i> Đơn hàng của bạn</a></li>
                <li class="sidebar-nav-item"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <?php if ($tab == 'info'): ?>
                <h2>Thông Tin Tài Khoản</h2>
                <?php if ($subtab == 'profile'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label for="tenkhach">Họ và tên</label>
                            <input type="text" id="tenkhach" name="tenkhach" class="form-control" value="<?= htmlspecialchars($customer['tenkhach']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="ngaysinh">Ngày sinh</label>
                            <input type="date" id="ngaysinh" name="ngaysinh" class="form-control" value="<?= htmlspecialchars($customer['ngaysinh']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Giới tính</label>
                            <div class="gender-group">
                                <label><input type="radio" name="gioitinh" value="Nam" <?= ($customer['gioitinh'] == 'Nam') ? 'checked' : '' ?>> Nam</label>
                                <label><input type="radio" name="gioitinh" value="Nữ" <?= ($customer['gioitinh'] == 'Nữ') ? 'checked' : '' ?>> Nữ</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="sodienthoai">Số điện thoại</label>
                            <input type="tel" id="sodienthoai" name="sodienthoai" class="form-control" value="<?= htmlspecialchars($customer['sodienthoai']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="diachi">Địa chỉ</label>
                            <textarea id="diachi" name="diachi" class="form-control" rows="3" required><?= htmlspecialchars($customer['diachi']) ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                <?php elseif ($subtab == 'changepass'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label for="old_password">Mật khẩu cũ</label>
                            <input type="password" name="old_password" id="old_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Mật khẩu mới</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="show-password-label">
                                <input type="checkbox" onclick="togglePasswordVisibility()">
                                Hiển thị mật khẩu
                            </label>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php elseif ($tab == 'orders'): ?>
                <h2>Đơn hàng của bạn</h2>
                <?php
                    $order_sql = "SELECT * FROM tbDonHang WHERE makhach = ? ORDER BY ngaymua DESC";
                    $stmt_order = $pdo->prepare($order_sql);
                    $stmt_order->execute([$makhach]);
                    $orders = $stmt_order->fetchAll();

                    if (count($orders) > 0):
                        foreach ($orders as $order):
                            $total_sql = "SELECT SUM(soluong * dongia) AS total FROM tbChiTietDonHang WHERE madonhang = ?";
                            $stmt_total = $pdo->prepare($total_sql);
                            $stmt_total->execute([$order['madonhang']]);
                            $total_amount = $stmt_total->fetchColumn();

                            $status_class = '';
                            switch ($order['tinhtrang']) {
                                case 'Đã giao': $status_class = 'status-success'; break;
                                case 'Đã hủy': $status_class = 'status-danger'; break;
                                case 'Đang giao hàng': $status_class = 'status-warning'; break;
                                default: $status_class = 'status-primary'; break;
                            }
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Mã ĐH: <?= htmlspecialchars($order['madonhang']) ?></span>
                                <span class="order-date"> | Ngày: <?= htmlspecialchars($order['ngaymua']) ?></span>
                            </div>
                            <span class="order-status <?= $status_class ?>"><?= htmlspecialchars($order['tinhtrang']) ?></span>
                        </div>
                        <div class="order-details">
                            <span class="order-total">Tổng giá trị: <?= number_format($total_amount, 0) ?> VND</span>
                            <div class="order-actions">
                                <a href="order_detail.php?madonhang=<?= htmlspecialchars($order['madonhang']) ?>" class="btn btn-secondary">
                                   <i class="fas fa-eye"></i> Xem
                                </a>
                                <?php if (in_array($order['tinhtrang'], ['Mới', 'Chờ xử lý'])): ?>
                                <form action="account.php?tab=orders" method="post" onsubmit="return confirm('Bạn có chắc chắn muốn HỦY đơn hàng này?');" style="display:inline;">
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['madonhang']) ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times-circle"></i> Hủy
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php 
                        endforeach;
                    else:
                        echo "<p class='no-orders'>Bạn chưa có đơn hàng nào.</p>";
                    endif;
                ?>
            <?php endif; ?>
        </main>
    </div>
    <script>
        function togglePasswordVisibility() {
            ["old_password", "new_password", "confirm_password"].forEach(id => {
                const field = document.getElementById(id);
                if (field) field.type = field.type === "password" ? "text" : "password";
            });
        }
    </script>
</body>
</html>
<?php include '../templates/footer.php'; ?>