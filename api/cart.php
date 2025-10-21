<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Giỏ hàng được lưu trong session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // Khởi tạo giỏ hàng nếu chưa có
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getCart();
        break;
    case 'POST':
        addToCart();
        break;
    case 'PUT': // Dùng để cập nhật số lượng
        updateCartItem();
        break;
    case 'DELETE':
        removeFromCart();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.']);
        break;
}

function getCart() {
    echo json_encode(['success' => true, 'cart' => $_SESSION['cart']]);
}

function addToCart() {
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);

    $product_id = $data['product_id'] ?? null;
    $quantity = $data['quantity'] ?? 1;

    if (!$product_id || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        return;
    }

    // Lấy thông tin sản phẩm để thêm vào giỏ hàng
    $db->query("SELECT id, name, price, image_url, stock_quantity FROM products WHERE id = :id");
    $db->bind(':id', $product_id);
    $product = $db->single();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại.']);
        return;
    }

    if ($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ.']);
        return;
    }

    // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
    if (isset($_SESSION['cart'][$product_id])) {
        // Nếu có, tăng số lượng
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        // Nếu chưa, thêm sản phẩm mới vào giỏ hàng
        $_SESSION['cart'][$product_id] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image_url' => $product['image_url'],
            'quantity' => $quantity
        ];
    }
    echo json_encode(['success' => true, 'message' => 'Đã thêm sản phẩm vào giỏ hàng.', 'cart' => $_SESSION['cart']]);
}

function updateCartItem() {
    $data = json_decode(file_get_contents('php://input'), true);

    $product_id = $data['product_id'] ?? null;
    $quantity = $data['quantity'] ?? 0;

    if (!$product_id || $quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        return;
    }

    if (isset($_SESSION['cart'][$product_id])) {
        if ($quantity == 0) {
            unset($_SESSION['cart'][$product_id]); // Xóa khỏi giỏ hàng nếu số lượng là 0
            echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.', 'cart' => $_SESSION['cart']]);
        } else {
            // Cần kiểm tra lại số lượng tồn kho trước khi cập nhật
            global $db;
            $db->query("SELECT stock_quantity FROM products WHERE id = :id");
            $db->bind(':id', $product_id);
            $product_stock = $db->single();

            if ($product_stock && $product_stock['stock_quantity'] >= $quantity) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật số lượng sản phẩm.', 'cart' => $_SESSION['cart']]);
            } else {
                 echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong giỏ hàng.']);
    }
}

function removeFromCart() {
    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = $data['product_id'] ?? null;

    if (!$product_id) {
        echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ.']);
        return;
    }

    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.', 'cart' => $_SESSION['cart']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong giỏ hàng.']);
    }
}
?>