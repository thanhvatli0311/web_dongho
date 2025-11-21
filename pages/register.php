<?php
session_start();
// Biến kết nối PDO là $pdo
include '../includes/db.php'; 
include '../templates/header.php';

// Khởi tạo biến để lưu thông báo lỗi/thành công
// Sử dụng Session để hiển thị thông báo ngay cả sau khi chuyển hướng
$message = null;
$message_type = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $password_raw = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $username = $_POST['username'];
    $role = 'Member';
    $customer_name = $_POST['customer_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $ngaysinh = $_POST['ngaysinh'];
    $gender = $_POST['gender'];

    // 1. Kiểm tra mật khẩu và xác nhận
    if ($password_raw !== $confirm_password) {
        $message = "Mật khẩu và xác nhận mật khẩu không khớp!";
        $message_type = 'danger';
    }

    // 2. Kiểm tra yêu cầu mật khẩu
    if (!$message && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/', $password_raw)) {
        $message = "Mật khẩu phải có tối thiểu 8 ký tự, bao gồm ít nhất 1 chữ hoa, 1 chữ thường và 1 ký tự đặc biệt!";
        $message_type = 'danger';
    }

    if (!$message) {
        // Mã hóa mật khẩu
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        try {
            // BẮT ĐẦU TRANSACTION để đảm bảo tính toàn vẹn dữ liệu
            $pdo->beginTransaction();

            // 3. Kiểm tra username đã tồn tại chưa (Dùng PDO)
            $check_sql = "SELECT username FROM tbuser WHERE username = ?";
            $stmt = $pdo->prepare($check_sql); 
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $message = "Tên đăng nhập đã tồn tại!";
                $message_type = 'danger';
                $pdo->rollBack(); // Rollback nếu đã bắt đầu transaction
            } else {
                
                // 4. Thêm tài khoản vào tbuser (Dùng PDO)
                $sql_user = "INSERT INTO tbuser (username, password) VALUES (?, ?)";
                $stmt_user = $pdo->prepare($sql_user);
                $stmt_user->execute([$username, $password]);
                
                // 5. TẠO MÃ KHÁCH HÀNG (Dùng PDO)
                $today = date('Ymd'); 
                $prefix = "KH" . $today;
                
                // Sử dụng PDO cho truy vấn không tham số
                $sql_check_id = "SELECT makhach FROM tbkhachhang WHERE makhach LIKE '{$prefix}%' ORDER BY makhach DESC LIMIT 1";
                // LƯU Ý: Không dùng prepare/execute cho truy vấn này nếu bạn không muốn dùng tham số.
                // Tuy nhiên, để thống nhất, ta dùng prepare
                $stmt_check_id = $pdo->prepare($sql_check_id);
                $stmt_check_id->execute();

                $nextNumber = 1;
                if ($row = $stmt_check_id->fetch(PDO::FETCH_ASSOC)) {
                    $lastNumber = (int)substr($row['makhach'], -3);
                    $nextNumber = $lastNumber + 1;
                }
                $customer_id = $prefix . sprintf("%03d", $nextNumber);

                // 6. Thêm thông tin khách hàng vào tbkhachhang (Dùng PDO)
                $sql_khach = "INSERT INTO tbkhachhang (makhach, tenkhach, ngaysinh, gioitinh, sodienthoai, diachi, username) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_khach = $pdo->prepare($sql_khach);
                // Truyền mảng tham số vào execute()
                $stmt_khach->execute([$customer_id, $customer_name, $ngaysinh, $gender, $phone, $address, $username]);

                // 7. Thêm quyền vào tbuserinrole (Dùng PDO)
                $sql_role = "INSERT INTO tbuserinrole (username, role) VALUES (?, ?)";
                $stmt_role = $pdo->prepare($sql_role);
                $stmt_role->execute([$username, $role]);

                // KẾT THÚC TRANSACTION
                $pdo->commit();

                // Chuyển hướng sau khi đăng ký thành công
                $_SESSION['message'] = ['type' => 'success', 'content' => 'Đăng ký thành công! Bạn có thể đăng nhập ngay.'];
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            // ROLLBACK nếu có lỗi
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = 'Lỗi đăng ký: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    // Lưu message vào session để hiển thị sau khi form bị gửi lại
    if ($message) {
        $_SESSION['message'] = ['type' => $message_type, 'content' => $message];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            background: white;
            padding: 30px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        .input-group input,
        .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .input-group input:focus,
        .input-group select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        .button-container button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
            transition: background 0.3s ease-in-out;
        }
        .button-container button:hover {
            background: #0056b3;
        }
        .show-password {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 3px;
            margin-top: 8px;
            margin-bottom: 15px;
            white-space: nowrap;
        }
        .show-password input[type="checkbox"],
        .show-password label {
            margin: 0;
            padding: 0;
        }
        /* Style cho message box (để hiển thị thông báo) */
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>

<body>
    
    <div class="container">
        <h2>Đăng ký tài khoản</h2>
        
        <?php 
        // Hiển thị thông báo (nếu có)
        if (isset($_SESSION['message'])) {
            echo '<div class="alert alert-' . htmlspecialchars($_SESSION['message']['type']) . '">'. htmlspecialchars($_SESSION['message']['content']) .'</div>';
            unset($_SESSION['message']);
        }
        ?>

        <form method="post" id="registerForm">
            <div class="input-group">
                <label for="username">Tên đăng nhập:</label>
                <input type="text" id="username" name="username" placeholder="Tên đăng nhập" required>
            </div>
            <div class="input-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" placeholder="Mật khẩu" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Xác nhận mật khẩu:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
            </div>
            <div class="show-password">
                <input type="checkbox" id="showPassword">
                <label for="showPassword">Hiển thị mật khẩu</label>
            </div>
            <div class="input-group">
                <label for="customer_name">Tên khách hàng:</label>
                <input type="text" id="customer_name" name="customer_name" placeholder="Tên khách hàng" required>
            </div>
            <div class="input-group">
                <label for="ngaysinh">Ngày sinh:</label>
                <input type="date" id="ngaysinh" name="ngaysinh" required>
            </div>
            <div class="input-group">
                <label for="gender">Giới tính:</label>
                <select id="gender" name="gender" required>
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>
            </div>
            <div class="input-group">
                <label for="phone">Số điện thoại:</label>
                <input type="text" id="phone" name="phone" placeholder="Số điện thoại" required>
            </div>
            <div class="input-group">
                <label for="address">Địa chỉ:</label>
                <input type="text" id="address" name="address" placeholder="Địa chỉ" required>
            </div>
            <div class="button-container">
                <button type="submit">Đăng ký</button>
            </div>
        </form>
    </div>

    <script>
        // Xử lý checkbox hiển thị mật khẩu cho cả 2 trường: mật khẩu và xác nhận mật khẩu
        document.getElementById('showPassword').addEventListener('change', function() {
            var pwdInput = document.getElementById('password');
            var cpwdInput = document.getElementById('confirm_password');
            var type = this.checked ? 'text' : 'password';
            pwdInput.type = type;
            cpwdInput.type = type;
        });

        // Kiểm tra mật khẩu phía client trước khi submit (Giữ nguyên)
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            var regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/;
            if (!regex.test(password)) {
                alert("Mật khẩu phải có tối thiểu 8 ký tự, bao gồm ít nhất 1 chữ hoa, 1 chữ thường và 1 ký tự đặc biệt!");
                e.preventDefault();
                return;
            }
            if (password !== confirmPassword) {
                alert("Mật khẩu và xác nhận mật khẩu không khớp!");
                e.preventDefault();
                return;
            }
        });
    </script>
</body>

</html>

<?php include '../templates/footer.php'; ?>