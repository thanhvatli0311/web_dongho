<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để xem thông tin cá nhân.']);
    exit();
}

global $db;
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id_to_fetch = $_GET['id'] ?? $current_user_id; // Mặc định lấy user đang đăng nhập

    // Chỉ admin mới có quyền lấy thông tin của user khác
    if ($user_id_to_fetch != $current_user_id && $current_user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem thông tin người dùng này.']);
        exit();
    }

    $db->query("SELECT id, username, email, fullname, address, phone_number, role, created_at FROM users WHERE id = :id");
    $db->bind(':id', $user_id_to_fetch);
    $user = $db->single();

    if ($user) {
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng.']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') { // Cập nhật thông tin người dùng
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id_to_update = $_GET['id'] ?? $current_user_id;

    // Chỉ admin mới có quyền cập nhật user khác, hoặc user tự cập nhật chính mình
    if ($user_id_to_update != $current_user_id && $current_user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật thông tin người dùng này.']);
        exit();
    }

    $updateFields = [];
    $bindings = [':id' => $user_id_to_update];

    if (isset($data['fullname'])) {
        $updateFields[] = 'fullname = :fullname';
        $bindings[':fullname'] = $data['fullname'];
    }
    if (isset($data['email'])) {
        // Kiểm tra email trùng lặp nếu email mới khác email cũ
        $db->query("SELECT id FROM users WHERE email = :email AND id != :id");
        $db->bind(':email', $data['email']);
        $db->bind(':id', $user_id_to_update);
        if ($db->single()) {
            echo json_encode(['success' => false, 'message' => 'Email đã tồn tại.']);
            exit();
        }
        $updateFields[] = 'email = :email';
        $bindings[':email'] = $data['email'];
    }
    if (isset($data['address'])) {
        $updateFields[] = 'address = :address';
        $bindings[':address'] = $data['address'];
    }
    if (isset($data['phone_number'])) {
        $updateFields[] = 'phone_number = :phone_number';
        $bindings[':phone_number'] = $data['phone_number'];
    }
    // Admin có thể cập nhật role của người dùng khác
    if ($current_user_role === 'admin' && isset($data['role'])) {
        $updateFields[] = 'role = :role';
        $bindings[':role'] = $data['role'];
    }

    // Xử lý đổi mật khẩu (nếu có)
    if (isset($data['old_password']) && isset($data['new_password'])) {
        $db->query("SELECT password FROM users WHERE id = :id");
        $db->bind(':id', $user_id_to_update);
        $user_pw_hash = $db->single()['password'];

        if (!password_verify($data['old_password'], $user_pw_hash)) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu cũ không chính xác.']);
            exit();
        }
        $updateFields[] = 'password = :password';
        $bindings[':password'] = password_hash($data['new_password'], PASSWORD_DEFAULT);
    }


    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'Không có dữ liệu để cập nhật.']);
        exit();
    }

    $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $db->query($query);
    foreach ($bindings as $key => $value) {
        $db->bind($key, $value);
    }

    if ($db->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật thông tin người dùng.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.']);
}
?>