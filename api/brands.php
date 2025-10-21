<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    global $db;
    $db->query("SELECT id, name FROM brands ORDER BY name ASC");
    $brands = $db->resultSet();

    if ($db->rowCount() > 0) {
        echo json_encode(['success' => true, 'data' => $brands]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không có thương hiệu nào.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.']);
}
?>