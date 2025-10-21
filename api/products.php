<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getProductDetail($_GET['id']);
        } else {
            getProducts();
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.']);
        break;
}

function getProducts() {
    global $db;

    // Lấy tham số bộ lọc/tìm kiếm từ URL (query string)
    $category_id = $_GET['category_id'] ?? null;
    $brand_id = $_GET['brand_id'] ?? null;
    $search_term = $_GET['search'] ?? null;
    $min_price = $_GET['min_price'] ?? null;
    $max_price = $_GET['max_price'] ?? null;
    $sort_by = $_GET['sort_by'] ?? 'created_at'; // Mặc định sắp xếp theo ngày tạo
    $sort_order = $_GET['sort_order'] ?? 'DESC'; // Mặc định giảm dần
    $limit = $_GET['limit'] ?? 12; // Mặc định 12 sản phẩm/trang
    $offset = $_GET['offset'] ?? 0; // Vị trí bắt đầu lấy dữ liệu

    $sql = "SELECT p.*, c.name as category_name, b.name as brand_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            JOIN brands b ON p.brand_id = b.id
            WHERE 1=1"; // Điều kiện ban đầu luôn đúng

    $params = [];

    if ($category_id) {
        $sql .= " AND p.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }
    if ($brand_id) {
        $sql .= " AND p.brand_id = :brand_id";
        $params[':brand_id'] = $brand_id;
    }
    if ($search_term) {
        $sql .= " AND (p.name LIKE :search_term OR p.description LIKE :search_term_desc)";
        $params[':search_term'] = '%' . $search_term . '%';
        $params[':search_term_desc'] = '%' . $search_term . '%';
    }
    if ($min_price) {
        $sql .= " AND p.price >= :min_price";
        $params[':min_price'] = $min_price;
    }
    if ($max_price) {
        $sql .= " AND p.price <= :max_price";
        $params[':max_price'] = $max_price;
    }

    // Sắp xếp
    $allowed_sort_columns = ['name', 'price', 'created_at'];
    if (in_array($sort_by, $allowed_sort_columns)) {
        $sql .= " ORDER BY p." . $sort_by . " " . (strtoupper($sort_order) == 'ASC' ? 'ASC' : 'DESC');
    } else {
        $sql .= " ORDER BY p.created_at DESC"; // Mặc định nếu $sort_by không hợp lệ
    }


    // Phân trang
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = (int)$limit;
    $params[':offset'] = (int)$offset;


    $db->query($sql);
    foreach ($params as $param => $value) {
        $db->bind($param, $value);
    }

    $products = $db->resultSet();

    if ($db->rowCount() > 0) {
        echo json_encode(['success' => true, 'data' => $products]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm nào.']);
    }
}

function getProductDetail($id) {
    global $db;
    $db->query("SELECT p.*, c.name as category_name, b.name as brand_name
                FROM products p
                JOIN categories c ON p.category_id = c.id
                JOIN brands b ON p.brand_id = b.id
                WHERE p.id = :id");
    $db->bind(':id', $id);
    $product = $db->single();

    if ($product) {
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
    }
}
?>