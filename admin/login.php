<?php
session_start(); // Đảm bảo session được khởi động
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
$db = new Database(); // Khởi tạo đối tượng Database

if (isLoggedIn() && isAdmin()) {
    redirect(BASE_URL . 'admin/index.php'); // Nếu đã đăng nhập và là admin, chuyển hướng đến dashboard
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Vui lòng điền đầy đủ tên người dùng và mật khẩu.';
    } else {
        $db->query("SELECT id, username, password, role FROM users WHERE username = :username");
        $db->bind(':username', $username);
        $user = $db->single();

        if ($user && verifyPassword($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                redirect(BASE_URL . 'admin/index.php');
            } else {
                $error_message = 'Bạn không có quyền truy cập trang quản trị.';
            }
        } else {
            $error_message = 'Tên người dùng hoặc mật khẩu không đúng.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Admin</title>
    <link rel="stylesheet" href="../public/css/style.css"> <!-- Sử dụng lại CSS chính -->
    <style>
        body { background-color: #f0f2f5; }
        .admin-login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 100px auto;
            text-align: center;
        }
        .admin-login-container h2 {
            margin-bottom: 30px;
            color: #007bff;
        }
        .admin-login-container form div {
            margin-bottom: 20px;
            text-align: left;
        }
        .admin-login-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .admin-login-container input[type="text"],
        .admin-login-container input[type="password"] {
            width: calc(100% - 22px);
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .admin-login-container button {
            width: 100%;
            padding: 15px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .admin-login-container button:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: #dc3545;
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <h2>Đăng Nhập Quản Trị</h2>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo escapeHTML($error_message); ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div>
                <label for="username">Tên người dùng:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Đăng Nhập</button>
        </form>
    </div>
</body>
</html>