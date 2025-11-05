<?php
// update_cart_ajax.php
session_start();

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $itemId = $_POST['item_id'];
    // Lấy số lượng, đảm bảo là số nguyên và tối thiểu là 1
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    // Lấy trạng thái checked, chuyển chuỗi 'true'/'false' thành boolean
    $checked = isset($_POST['checked']) ? ($_POST['checked'] === 'true') : true;

    if (isset($_SESSION['cart'][$itemId])) {
        // Cập nhật số lượng và trạng thái chọn (checked) trong session
        $_SESSION['cart'][$itemId]['quantity'] = $quantity;
        $_SESSION['cart'][$itemId]['checked'] = $checked;
        
        $response = ['status' => 'success', 'message' => 'Cart session updated.'];
    } else {
        $response = ['status' => 'error', 'message' => 'Item not found in cart.'];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>