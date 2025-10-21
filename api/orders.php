<?php
// Bắt đầu session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Bao gồm file cấu hình và database
require_once '../includes/config.php';
require_once '../includes/database.php';

// Tạo đối tượng database
$db = new Database();

// Thiết lập header để trả về JSON
header('Content-Type: application/json');

// Lấy phương thức HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Lấy dữ liệu từ body của request (JSON)
        $data = json_decode(file_get_contents('php://input'), true);

        // Kiểm tra xem người dùng đã đăng nhập chưa
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Bạn phải đăng nhập để đặt hàng.']);
            exit();
        }

        // Validate required fields
        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items']) ||
            !isset($data['total_amount']) || !isset($data['shipping_address']) ||
            !isset($data['phone_number']) || !isset($data['payment_method'])) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đặt hàng cần thiết.']);
            exit();
        }

        // Validate items and stock
        foreach ($data['items'] as $item) {
            if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                echo json_encode(['success' => false, 'message' => 'Thông tin sản phẩm trong giỏ hàng không hợp lệ.']);
                exit();
            }

            // Kiểm tra tồn kho
            $db->query("SELECT stock_quantity, name FROM products WHERE id = :product_id");
            $db->bind(param: ':product_id', value: $item['product_id']); // Fixed bind syntax
            $product = $db->single();

            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm "' . ($product['name'] ?? 'không xác định') . '" không đủ số lượng trong kho hoặc không tồn tại.']);
                exit();
            }
        }

        try {
            // Bắt đầu giao dịch
            $db->beginTransaction();

            // 1. Tạo đơn hàng mới
            $db->query("INSERT INTO orders (user_id, total_amount, shipping_address, phone_number, payment_method, notes)
                        VALUES (:user_id, :total_amount, :shipping_address, :phone_number, :payment_method, :notes)");
            $db->bind(param: ':user_id', value: $user_id);
            $db->bind(param: ':total_amount', value: $data['total_amount']);
            $db->bind(param: ':shipping_address', value: $data['shipping_address']);
            $db->bind(param: ':phone_number', value: $data['phone_number']);
            $db->bind(param: ':payment_method', value: $data['payment_method']);
            $db->bind(param: ':notes', value: $data['notes'] ?? null); // Sử dụng null coalescing operator cho notes
            $db->execute();
            $order_id = $db->lastInsertId();

            if (!$order_id) {
                throw new Exception("Không thể tạo đơn hàng.");
            }

            // 2. Thêm các sản phẩm vào order_items và cập nhật tồn kho
            foreach ($data['items'] as $item) {
                // Thêm vào order_items
                $db->query("INSERT INTO order_items (order_id, product_id, quantity, price)
                            VALUES (:order_id, :product_id, :quantity, :price)");
                $db->bind(param: ':order_id', value: $order_id);
                $db->bind(param: ':product_id', value: $item['product_id']);
                $db->bind(param: ':quantity', value: $item['quantity']);
                $db->bind(param: ':price', value: $item['price']);
                $db->execute();

                // Cập nhật tồn kho sản phẩm
                $db->query("UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id");
                $db->bind(param: ':quantity', value: $item['quantity']);
                $db->bind(param: ':product_id', value: $item['product_id']);
                $db->execute();
            }

            // Kết thúc giao dịch
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Đặt hàng thành công!', 'order_id' => $order_id]);

        } catch (Exception $e) {
            $db->rollBack(); // Hoàn tác nếu có lỗi
            error_log("Order placement error: " . $e->getMessage()); // Sửa lỗi cú pháp error_log
            echo json_encode(['success' => false, 'message' => 'Lỗi khi đặt hàng: ' . $e->getMessage()]); // Hiển thị lỗi chi tiết hơn cho dev
        }
        break;

    case 'GET':
        // Lấy danh sách hoặc chi tiết đơn hàng
        $order_id = $_GET['id'] ?? null;
        $user_role = $_SESSION['user_role'] ?? 'customer'; // Giả sử vai trò được lưu trong session

        $sql = "SELECT o.*, u.fullname as customer_name, u.email as customer_email
                FROM orders o
                JOIN users u ON o.user_id = u.id";

        if ($order_id) { // Lấy chi tiết đơn hàng cụ thể
            $sql .= " WHERE o.id = :order_id";
            // Nếu không phải admin, đảm bảo chỉ lấy đơn hàng của người dùng đó
            if ($user_role !== 'admin') {
                $sql .= " AND o.user_id = :user_id";
            }
            $db->query($sql);
            $db->bind(':order_id', $order_id);
            if ($user_role !== 'admin') {
                $db->bind(':user_id', $user_id); // Giả sử $user_id đã được định nghĩa từ session
            }
            $order = $db->single();

            if ($order) {
                // Lấy chi tiết sản phẩm trong đơn hàng
                $db->query("SELECT oi.*, p.name as product_name, p.image_url
                            FROM order_items oi
                            JOIN products p ON oi.product_id = p.id
                            WHERE oi.order_id = :order_id");
                $db->bind(':order_id', $order_id);
                $order_items = $db->resultSet();
                $order['items'] = $order_items;

                echo json_encode(['success' => true, 'order' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng hoặc bạn không có quyền truy cập.']);
            }
        } else { // Lấy danh sách đơn hàng
            // Nếu không phải admin, chỉ lấy đơn hàng của người dùng hiện tại
            if ($user_role !== 'admin') {
                $sql .= " WHERE o.user_id = :user_id";
            }
            $sql .= " ORDER BY o.order_date DESC"; // Sắp xếp theo ngày mới nhất
            $db->query($sql);
            if ($user_role !== 'admin') {
                $db->bind(':user_id', $user_id); // Giả sử $user_id đã được định nghĩa từ session
            }
            $orders = $db->resultSet();
            echo json_encode(['success' => true, 'orders' => $orders]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.']);
        break;
}
?>