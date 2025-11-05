<?php
ob_start();
session_start();
include '../includes/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Lấy thông tin khách hàng sớm (cần cho cả action POST và hiển thị)
$customer_sql = "SELECT * FROM tbkhachhang WHERE username = ?";
$stmt_customer = $conn->prepare($customer_sql);
$stmt_customer->bind_param("s", $username);
$stmt_customer->execute();
$customer_result = $stmt_customer->get_result();
if ($customer_result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Khách hàng không tồn tại trong hệ thống!</div>";
    exit;
}
$customer = $customer_result->fetch_assoc();
$makhach = $customer['makhach'];


// Xử lý các hành động POST khi người dùng submit form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'cancel_order') {
            $order_id = $_POST['order_id'];
            
            // Tinh chỉnh bảo mật: Chỉ cho phép hủy khi trạng thái là 'Mới' hoặc 'Chờ xử lý' VÀ đơn hàng thuộc về khách hàng này.
            $sql_cancel = "UPDATE tbDonHang SET tinhtrang = 'Đã hủy' WHERE madonhang = ? AND makhach = ? AND tinhtrang IN ('Mới', 'Chờ xử lý')";
            $stmt_cancel = $conn->prepare($sql_cancel);
            if (!$stmt_cancel) {
                throw new Exception("Lỗi khi chuẩn bị truy vấn hủy đơn hàng: " . $conn->error);
            }
            $stmt_cancel->bind_param("ss", $order_id, $makhach);
            if (!$stmt_cancel->execute()) {
                throw new Exception("Lỗi khi cập nhật đơn hàng: " . $stmt_cancel->error);
            }
            
            if ($stmt_cancel->affected_rows == 0) {
                throw new Exception("Đơn hàng không thể hủy: Không tìm thấy, đã hủy, hoặc đang được xử lý/giao.");
            }
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Đơn hàng đã được hủy thành công!'
            ];
            header("Location: account.php?tab=orders");
            exit;
        } elseif ($_POST['action'] == 'update_profile') {
            // Cập nhật hồ sơ khách hàng
            $tenkhach = trim($_POST['tenkhach']);
            $ngaysinh = $_POST['ngaysinh'];
            $gioitinh = $_POST['gioitinh'];
            $sodienthoai = trim($_POST['sodienthoai']);
            $diachi = trim($_POST['diachi']);
            
            // Validation cơ bản
            if (empty($tenkhach) || empty($sodienthoai)) {
                 throw new Exception("Tên và Số điện thoại không được để trống!");
            }

            $sql_update = "UPDATE tbkhachhang SET tenkhach=?, ngaysinh=?, gioitinh=?, sodienthoai=?, diachi=? WHERE username=?";
            $stmt_update = $conn->prepare($sql_update);
            if (!$stmt_update) {
                throw new Exception("Lỗi khi chuẩn bị truy vấn cập nhật hồ sơ: " . $conn->error);
            }
            $stmt_update->bind_param("ssssss", $tenkhach, $ngaysinh, $gioitinh, $sodienthoai, $diachi, $username);
            if (!$stmt_update->execute()) {
                throw new Exception("Lỗi khi cập nhật hồ sơ: " . $stmt_update->error);
            }
            
            // Cập nhật lại biến $customer sau khi update thành công
            $customer = array_merge($customer, ['tenkhach' => $tenkhach, 'ngaysinh' => $ngaysinh, 'gioitinh' => $gioitinh, 'sodienthoai' => $sodienthoai, 'diachi' => $diachi]);

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Cập nhật thông tin thành công!'
            ];
            header("Location: account.php?tab=info&subtab=profile");
            exit;
        } elseif ($_POST['action'] == 'change_password') {
            // Đổi mật khẩu
            $old_password = $_POST['old_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Kiểm tra định dạng mật khẩu mới
            if (!preg_match('/^(?=.*[A-Z])(?=.*\W).{8,}$/', $new_password)) {
                throw new Exception('Mật khẩu mới phải có tối thiểu 8 ký tự, có ít nhất 1 chữ hoa và 1 ký tự đặc biệt!');
            }
            if ($new_password !== $confirm_password) {
                throw new Exception('Mật khẩu mới và xác nhận không khớp!');
            }
            
            // Lấy mật khẩu hiện tại từ bảng tbuser để xác thực
            $sql_user = "SELECT password FROM tbuser WHERE username = ?";
            $stmt_user = $conn->prepare($sql_user);
            if (!$stmt_user) {
                throw new Exception("Lỗi khi chuẩn bị truy vấn xác thực mật khẩu: " . $conn->error);
            }
            $stmt_user->bind_param("s", $username);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            $user_data = $result_user->fetch_assoc();
            if (!$user_data || !password_verify($old_password, $user_data['password'])) {
                throw new Exception('Mật khẩu cũ không chính xác!');
            }
            
            // Cập nhật mật khẩu mới
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update_pw = "UPDATE tbuser SET password=? WHERE username=?";
            $stmt_update_pw = $conn->prepare($sql_update_pw);
            if (!$stmt_update_pw) {
                throw new Exception("Lỗi khi chuẩn bị truy vấn đổi mật khẩu: " . $conn->error);
            }
            $stmt_update_pw->bind_param("ss", $new_hash, $username);
            if (!$stmt_update_pw->execute()) {
                throw new Exception("Lỗi khi cập nhật mật khẩu: " . $stmt_update_pw->error);
            }
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Đổi mật khẩu thành công!'
            ];
            header("Location: account.php?tab=info&subtab=changepass");
            exit;
        }
    } catch (Exception $e) {
        // Lưu thông báo lỗi và redirect về trang hiện tại
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
        // Xác định URL redirect phù hợp dựa trên action
        $redirect_url = "account.php?tab=info";
        if (isset($_POST['action']) && $_POST['action'] == 'change_password') {
            $redirect_url .= "&subtab=changepass";
        } elseif (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
            $redirect_url .= "&subtab=profile";
        } elseif (isset($_POST['action']) && $_POST['action'] == 'cancel_order') {
            $redirect_url = "account.php?tab=orders";
        }
        header("Location: $redirect_url");
        exit;
    }
}

// Xác định tab hiển thị (mặc định "info") và subtab (mặc định "profile")
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'info';
$subtab = isset($_GET['subtab']) ? $_GET['subtab'] : 'profile';

include '../templates/header.php';

// Hiển thị flash message nếu có
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    $alertClass = ($flash['type'] == 'success') ? 'alert-success' : 'alert-danger';
    echo "<div class='alert $alertClass' style='padding:15px; border-radius:5px; margin:15px auto; max-width: 1200px; text-align:center;'>"
         . htmlspecialchars($flash['message']) . "</div>";
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của tôi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .account-page {
            display: flex;
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #f8f8f8;
            border-right: 1px solid #e0e0e0;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li {
            border-bottom: 1px solid #e0e0e0;
        }
        .sidebar ul li a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #333;
            transition: background 0.3s;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: #d4af37;
            color: #fff;
        }
        /* Nested submenu cho "Thông tin tài khoản" */
        .submenu {
            list-style: none;
            padding-left: 20px;
            background: #e9e9e9;
        }
        .submenu li a {
            padding: 10px 20px;
            font-size: 14px;
            color: #555;
            display: block;
            transition: background 0.3s;
        }
        .submenu li a:hover,
        .submenu li a.active {
            background: #d4af37;
            color: #fff;
        }
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 40px;
        }
        .main-content h2 {
            margin-top: 0;
            color: #1a2b6d;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .input-group {
            margin-bottom: 20px;
        }
        .input-group label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }
        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .input-group input:focus,
        .input-group textarea:focus {
            border-color: #1a2b6d;
            outline: none;
            box-shadow: 0 0 6px rgba(26,43,109,0.3);
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        .button-container {
            margin-top: 30px;
            text-align: center;
        }
        .button-container button {
            padding: 12px 30px;
            background: #1a2b6d;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .button-container button:hover {
            background: #d4af37;
        }
        /* Giới tính: radio nằm bên trái chữ */
        .gender-group label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        /* Checkbox hiển thị mật khẩu */
        .show-pass {
            margin-top: 10px;
        }
        .show-pass label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            font-size: 14px;
            color: #555;
            white-space: nowrap;
        }
        /* Phần đơn hàng */
        .order {
            border: 1px solid #ccc;
            padding: 20px;
            padding-bottom: 70px; /* Thêm padding dưới để chứa nút */
            margin-bottom: 20px;
            border-radius: 6px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        .order p {
            margin: 8px 0;
            font-size: 15px;
            color: #333;
        }
        .order p strong {
            color: #1a2b6d;
        }
        /* Highlight Tình trạng */
        .text-success { color: #28a745; } /* Xanh lá */
        .text-danger { color: #dc3545; } /* Đỏ */
        .text-warning { color: #ffc107; } /* Vàng cam */
        .text-primary { color: #1a2b6d; } /* Xanh đậm */
        
        /* Container cho các hành động (Xem chi tiết, Hủy) */
        .order-actions {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex; 
            align-items: center;
            gap: 10px;
        }

        /* Nút Xem chi tiết */
        .btn-detail-order {
            background: #1a2b6d;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-detail-order:hover {
            background: #d4af37;
        }
        /* Nút Hủy đơn hàng */
        .cancel-order-btn-inline {
            background: #a61c00;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-block;
        }
        .cancel-order-btn-inline:hover {
            background: #8c1600;
        }
    </style>
</head>
<body>
    <div class="account-page">
        <div class="sidebar">
            <ul>
                <li>
                    <a href="account.php?tab=info" class="<?= ($tab == 'info') ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Thông tin tài khoản
                    </a>
                    <ul class="submenu">
                        <li><a href="account.php?tab=info&subtab=profile" class="<?= ($tab == 'info' && $subtab == 'profile') ? 'active' : ''; ?>">Hồ sơ</a></li>
                        <li><a href="account.php?tab=info&subtab=changepass" class="<?= ($tab == 'info' && $subtab == 'changepass') ? 'active' : ''; ?>">Đổi mật khẩu</a></li>
                    </ul>
                </li>
                <li><a href="account.php?tab=orders" class="<?= ($tab == 'orders') ? 'active' : ''; ?>"><i class="fas fa-list"></i> Đơn hàng</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
        <div class="main-content">
            <?php if ($tab == 'info'): ?>
                <h2>Thông tin tài khoản</h2>
                <?php if ($subtab == 'profile'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="input-group">
                            <label>Họ và tên:</label>
                            <input type="text" name="tenkhach" value="<?= htmlspecialchars($customer['tenkhach']) ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Ngày sinh:</label>
                            <input type="date" name="ngaysinh" value="<?= htmlspecialchars($customer['ngaysinh']) ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Giới tính:</label>
                            <div class="gender-group">
                                <label><input type="radio" name="gioitinh" value="Nam" <?= ($customer['gioitinh'] == 'Nam') ? 'checked' : ''; ?>> Nam</label>
                                <label><input type="radio" name="gioitinh" value="Nữ" <?= ($customer['gioitinh'] == 'Nữ') ? 'checked' : ''; ?>> Nữ</label>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Số điện thoại:</label>
                            <input type="text" name="sodienthoai" value="<?= htmlspecialchars($customer['sodienthoai']) ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Địa chỉ:</label>
                            <textarea name="diachi" required><?= htmlspecialchars($customer['diachi']) ?></textarea>
                        </div>
                        <div class="button-container">
                            <button type="submit">Lưu thay đổi</button>
                        </div>
                    </form>
                <?php elseif ($subtab == 'changepass'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="change_password">
                        <div class="input-group">
                            <label>Mật khẩu cũ:</label>
                            <input type="password" name="old_password" id="old_password" required>
                        </div>
                        <div class="input-group">
                            <label>Mật khẩu mới:</label>
                            <input type="password" name="new_password" id="new_password" required>
                        </div>
                        <div class="input-group">
                            <label>Xác nhận mật khẩu mới:</label>
                            <input type="password" name="confirm_password" id="confirm_password" required>
                        </div>
                        <div class="input-group show-pass">
                            <label>
                                <input type="checkbox" onclick="togglePasswordVisibility()">
                                Hiển thị mật khẩu
                            </label>
                        </div>
                        <div class="button-container">
                            <button type="submit">Đổi mật khẩu</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php elseif ($tab == 'orders'): ?>
                <h2>Đơn hàng của bạn</h2>
                <?php
                    // Lấy danh sách đơn hàng của khách hàng, sắp xếp theo ngày mua giảm dần
                    $order_sql = "SELECT * FROM tbDonHang WHERE makhach = ? ORDER BY ngaymua DESC";
                    $stmt_order = $conn->prepare($order_sql);
                    $stmt_order->bind_param("s", $makhach);
                    $stmt_order->execute();
                    $order_result = $stmt_order->get_result();
                    if ($order_result->num_rows > 0):
                        while ($order = $order_result->fetch_assoc()):
                ?>
                    <div class="order">
                        <p><strong>Mã đơn hàng:</strong> <?= htmlspecialchars($order['madonhang']) ?></p>
                        <p><strong>Ngày mua:</strong> <?= htmlspecialchars($order['ngaymua']) ?></p>
                        <?php
                            // Xác định class CSS cho tình trạng
                            $status_class = '';
                            switch ($order['tinhtrang']) {
                                case 'Đã giao': $status_class = 'text-success'; break;
                                case 'Đã hủy': $status_class = 'text-danger'; break;
                                case 'Đang giao hàng': $status_class = 'text-warning'; break;
                                default: $status_class = 'text-primary'; break;
                            }
                        ?>
                        <p><strong>Tình trạng:</strong> <span class="<?= $status_class ?>"><?= htmlspecialchars($order['tinhtrang']) ?></span></p>
                        <?php
                            // Tính tổng giá trị đơn hàng
                            $total_sql = "SELECT SUM(soluong * dongia) AS total FROM tbChiTietDonHang WHERE madonhang = ?";

                            $stmt_total = $conn->prepare($total_sql);
                            $stmt_total->bind_param("s", $order['madonhang']);
                            $stmt_total->execute();
                            $total_result = $stmt_total->get_result();
                            $total_row = $total_result->fetch_assoc();
                            $total_amount = $total_row['total'];
                        ?>
                        <p><strong>Tổng giá trị:</strong> <?= number_format($total_amount, 0) ?> VND</p>

                        <div class="order-actions">
                            <a href="order_detail.php?madonhang=<?= htmlspecialchars($order['madonhang']) ?>" 
                               class="btn-detail-order">
                               <i class="fas fa-eye"></i> Xem chi tiết
                            </a>

                            <?php 
                                // Nút Hủy đơn hàng (chỉ hiển thị khi trạng thái cho phép)
                                if (!in_array($order['tinhtrang'], array('Đã giao', 'Đang giao hàng', 'Đã hủy'))): 
                            ?>
                                <form action="order_detail.php?madonhang=<?= htmlspecialchars($order['madonhang']) ?>" method="post" onsubmit="return confirm('Bạn có chắc chắn muốn HỦY đơn hàng này? Thao tác này không thể hoàn tác.');">
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['madonhang']) ?>">
                                    <button type="submit" style="padding: 10px 30px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                                        <i class="fas fa-times-circle"></i> Hủy Đơn Hàng
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                        endwhile;
                    else:
                        echo "<p style='text-align: center; font-size: 18px; color: #777;'>Bạn chưa có đơn hàng nào.</p>";
                    endif;
                ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function togglePasswordVisibility() {
            var fields = ["old_password", "new_password", "confirm_password"];
            fields.forEach(function(id) {
                var field = document.getElementById(id);
                if (field) { // Kiểm tra để tránh lỗi nếu không ở tab đổi mật khẩu
                    field.type = (field.type === "password") ? "text" : "password";
                }
            });
        }
    </script>
</body>
</html>
<?php include '../templates/footer.php'; ?>