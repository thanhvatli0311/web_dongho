<?php
session_start();
include '../includes/db.php'; 

$error_message = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Lấy và làm sạch dữ liệu
    // Sử dụng htmlspecialchars và ENT_QUOTES để ngăn chặn XSS nhẹ
    $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
    $password = $_POST['password'];

    try {
        // 2. Truy vấn lấy thông tin user từ tbUser (Bước 1)
        $sql = "SELECT username, password FROM tbUser WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(); // Lấy một hàng duy nhất

        if (!$user) {
            // Không tìm thấy username
            $error_message = "Tên tài khoản không tồn tại!";
        } else {
            // 3. Kiểm tra mật khẩu
            if (password_verify($password, $user['password'])) {
                
                // 4. Lưu username vào session
                $_SESSION['username'] = $username;
                
                // 5. Lấy role từ bảng tbUserInRole (Bước 2)
                $role_sql = "SELECT role FROM tbUserInRole WHERE username = :username";
                $stmt_role = $pdo->prepare($role_sql);
                $stmt_role->execute([':username' => $username]);
                $role_row = $stmt_role->fetch();
                
                // Nếu tìm thấy role, lưu vào session, ngược lại mặc định là Member
                $_SESSION['role'] = $role_row ? $role_row['role'] : 'Member';

                // =========================================================
                // START: LOGIC TẢI LẠI GIỎ HÀNG TỪ CSDL
                // =========================================================
                try {
                    // 1. Lấy makhach từ tbkhachhang
                    $stmt_khach = $pdo->prepare("SELECT makhach FROM tbkhachhang WHERE username = :username");
                    $stmt_khach->execute([':username' => $username]);
                    $customer = $stmt_khach->fetch(PDO::FETCH_ASSOC);
                    
                    if ($customer) {
                        $makhach = $customer['makhach'];
                        
                        // 2. TẢI GIỎ HÀNG ĐÃ LƯU TỪ CSDL (tbgiohang_luu)
                        $sql_load = "SELECT mahang, soluong, checked FROM tbgiohang_luu WHERE makhach = :makhach";
                        $stmt_load = $pdo->prepare($sql_load);
                        $stmt_load->execute([':makhach' => $makhach]);
                        $saved_cart_items = $stmt_load->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($saved_cart_items)) {
                            // Khởi tạo/ghi đè giỏ hàng trong session bằng dữ liệu đã lưu
                            $_SESSION['cart'] = [];
                            
                            foreach ($saved_cart_items as $item) {
                                $_SESSION['cart'][$item['mahang']] = [
                                    'quantity' => (int)$item['soluong'],
                                    'checked' => (bool)$item['checked'] 
                                ];
                            }

                            // 3. Xóa giỏ hàng đã tải khỏi bảng lưu trữ
                            $pdo->prepare("DELETE FROM tbgiohang_luu WHERE makhach = :makhach_del")->execute([':makhach_del' => $makhach]);
                        }
                    }
                } catch (PDOException $e) {
                    // Xử lý lỗi CSDL (có thể ghi log, nhưng không báo lỗi ra màn hình người dùng)
                    // error_log("Lỗi tải giỏ hàng khi đăng nhập: " . $e->getMessage()); 
                }
                // =========================================================
                // END: LOGIC TẢI LẠI GIỎ HÀNG TỪ CSDL
                // =========================================================

                // 6. Chuyển hướng
                if ($_SESSION['role'] === 'Admin') {
                    header("Location: ../admin/admin.php");
                } else {
                    // Nếu có URL chuyển hướng trước khi đăng nhập (ví dụ: đang thanh toán)
                    if (isset($_SESSION['redirect_url'])) {
                        $redirect_url = $_SESSION['redirect_url'];
                        unset($_SESSION['redirect_url']);
                        header("Location: " . $redirect_url);
                    } else {
                        header("Location: index.php");
                    }
                }
                exit;
            } else {
                // Sai mật khẩu
                $error_message = "Mật khẩu không chính xác!";
            }
        }
    } catch (PDOException $e) {
        $error_message = "Lỗi kết nối CSDL: Vui lòng thử lại sau.";
        // error_log("Lỗi đăng nhập: " . $e->getMessage());
    }
}

// Nội dung HTML và các file include khác giữ nguyên
include '../templates/header.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Thêm style CSS để giao diện đẹp hơn nếu cần */
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            background: #fff;
        }
        .login-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-header img {
            max-width: 80px;
            margin-bottom: 15px;
        }
        .btn-login {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/images/logo.png" alt="Logo">
                <h2>Đăng nhập</h2>
                <p class="text-muted">Vui lòng đăng nhập để tiếp tục</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Tên đăng nhập" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <label for="username"><i class="fas fa-user me-2"></i>Tên đăng nhập</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Mật khẩu" required>
                    <label for="password"><i class="fas fa-lock me-2"></i>Mật khẩu</label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="show_password">
                    <label class="form-check-label" for="show_password">
                        Hiển thị mật khẩu
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                </button>

                <div class="text-center mt-3">
                    <p>Chưa có tài khoản?</p>
                    <a href="register.php" class="text-decoration-none">Đăng ký tài khoản mới</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('show_password').addEventListener('change', function() {
            var passwordInput = document.getElementById('password');
            if (this.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });
    </script>
</body>
</html>

<?php include '../templates/footer.php'; ?>