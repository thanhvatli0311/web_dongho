<?php
// Bắt đầu session nếu cần (thường không cần cho category API nếu không có phân quyền)
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

require_once '../includes/config.php';
require_once '../includes/database.php';

// Khởi tạo đối tượng database ngay ở đầu file
$db = new Database();

header('Content-Type: application/json');

// Lấy phương thức HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Truy vấn tất cả danh mục sản phẩm, sắp xếp theo tên
        $db->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $db->resultSet();

        if ($db->rowCount() > 0) {
            echo json_encode(['success' => true, 'data' => $categories]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không có danh mục nào được tìm thấy.']);
        }
        break;

    // Các phương thức khác (POST, PUT, DELETE) nếu bạn muốn thêm chức năng quản lý danh mục
    // case 'POST':
    //     // Logic để thêm danh mục mới
    //     break;
    // case 'PUT':
    //     // Logic để cập nhật danh mục
    //     break;
    // case 'DELETE':
    //     // Logic để xóa danh mục
    //     break;

    default:
        // Phương thức HTTP không được hỗ trợ
        echo json_encode(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.']);
        break;
}
?>