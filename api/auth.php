<?php
// Bắt đầu session nếu cần
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =======================================================
// Cấu hình CORS (Chỉ dùng trong môi trường dev hoặc với domain cụ thể)
// Nếu bạn truy cập client (HTML) và server (PHP) từ cùng một URL XAMPP
// ví dụ: http://localhost/project-dongho/public/login.html
// thì bạn KHÔNG cần các dòng CORS này.
// Nếu client chạy ở một cổng/domain khác (ví dụ: http://localhost:3000)
// thì bạn cần các dòng này.
// =======================================================
// header("Access-Control-Allow-Origin: *"); // Cẩn thận với * trong production
// header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
// if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
//     exit(0);
// }
// =======================================================

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$db = new Database(); // Khởi tạo đối tượng Database

header('Content-Type: application/json'); // Luôn trả về JSON

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

switch ($method) {
    case 'POST':
        if ($action === 'register') {
            $data = json_decode(file_get_contents('php://input'), true);

            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $fullname = $data['fullname'] ?? '';
            $address = $data['address'] ?? '';
            $phone_number = $data['phone_number'] ?? '';

            // 1. Xác thực dữ liệu đầu vào
            if (empty($username) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ các trường bắt buộc.']);
                exit();
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Địa chỉ email không hợp lệ.']);
                exit();
            }
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']);
                exit();
            }

            try {
                // 2. Kiểm tra trùng lặp username/email
                $db->query("SELECT id FROM users WHERE username = :username OR email = :email");
                $db->bind(':username', $username);
                $db->bind(':email', $email);
                $db->execute();
                if ($db->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Tên người dùng hoặc Email đã tồn tại.']);
                    exit();
                }

                // 3. Băm (hash) mật khẩu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 4. Lưu người dùng vào database
                $db->query("INSERT INTO users (username, password, email, fullname, address, phone_number, role)
                            VALUES (:username, :password, :email, :fullname, :address, :phone_number, 'customer')");
                $db->bind(':username', $username);
                $db->bind(':password', $hashed_password);
                $db->bind(':email', $email);
                $db->bind(':fullname', $fullname);
                $db->bind(':address', $address);
                $db->bind(':phone_number', $phone_number);
                $db->execute();

                if ($db->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Đăng ký tài khoản thành công!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi đăng ký tài khoản.']);
                }

            } catch (Exception $e) {
                error_log("Registration error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Lỗi server: Vui lòng thử lại sau.']);
            }
        } elseif ($action === 'login') {
            $data = json_decode(file_get_contents('php://input'), true);

            $identifier = $data['identifier'] ?? ''; // Có thể là username hoặc email
            $password = $data['password'] ?? '';

            if (empty($identifier) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ tên người dùng/email và mật khẩu.']);
                exit();
            }

            try {
                // Tìm người dùng theo username hoặc email
                $db->query("SELECT id, username, password, email, role FROM users WHERE username = :identifier OR email = :identifier");
                $db->bind(':identifier', $identifier);
                $user = $db->single();

                if (!$user) {
                    echo json_encode(['success' => false, 'message' => 'Tên người dùng hoặc mật khẩu không đúng.']);
                    exit();
                }

                // Xác minh mật khẩu
                if (password_verify($password, $user['password'])) {
                    // Đăng nhập thành công, lưu thông tin vào session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role']; // Lưu vai trò người dùng vào session

                    echo json_encode([
                        'success' => true,
                        'message' => 'Đăng nhập thành công!',
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'user_role' => $user['role'] // Trả về vai trò để JS có thể chuyển hướng
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Tên người dùng hoặc mật khẩu không đúng.']);
                }

            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Lỗi server: Vui lòng thử lại sau.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Hành động POST không hợp lệ.']);
        }
        break;

    case 'GET':
        if ($action === 'status') {
            // Kiểm tra trạng thái đăng nhập
            if (isset($_SESSION['user_id'])) {
                echo json_encode([
                    'isLoggedIn' => true,
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'user_role' => $_SESSION['user_role']
                ]);
            } else {
                echo json_encode(['isLoggedIn' => false]);
            }
            exit();
        } elseif ($action === 'logout') {
            // Xử lý đăng xuất
            if (isset($_SESSION['user_id'])) {
                session_unset();   // Xóa tất cả biến session
                session_destroy(); // Hủy session
                echo json_encode(['success' => true, 'message' => 'Đăng xuất thành công!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập.']);
            }
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Hành động GET không hợp lệ hoặc không được hỗ trợ.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.']);
        break;
}
?>