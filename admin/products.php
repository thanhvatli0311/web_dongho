<?php
// Bắt đầu session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Bao gồm các file cần thiết
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php'; // Chắc chắn rằng functions.php chứa formatCurrency và escapeHtml

// Kiểm tra quyền admin (hoặc middleware để bảo vệ trang admin)
// require_once '../includes/auth_middleware.php'; // Nếu bạn có middleware

// Khởi tạo đối tượng database
$db = new Database();

// Xử lý các hành động (thêm, sửa, xóa) nếu có
// (Phần này sẽ được mở rộng tùy theo yêu cầu cụ thể của bạn)

// 1. Xử lý xóa sản phẩm
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $product_id = $_GET['id'];
    try {
        $db->query("DELETE FROM products WHERE id = :id");
        $db->bind(':id', $product_id);
        if ($db->execute()) {
            $_SESSION['message'] = "Xóa sản phẩm thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Không thể xóa sản phẩm.";
            $_SESSION['message_type'] = "error";
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "Lỗi khi xóa sản phẩm: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    header('Location: products.php'); // Chuyển hướng để tránh gửi lại form
    exit();
}

// 2. Lấy danh sách sản phẩm
$products = []; // Khởi tạo mảng rỗng để tránh lỗi nếu không có sản phẩm
try {
    $db->query("
        SELECT 
            p.id, 
            p.name, 
            p.price, 
            p.stock_quantity, 
            p.status, 
            p.image_url,
            c.name as category_name,
            b.name as brand_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN brands b ON p.brand_id = b.id
        ORDER BY p.id DESC
    ");
    $products = $db->resultSet();
} catch (Exception $e) {
    // Ghi log lỗi và thông báo cho người dùng
    error_log("Error fetching products: " . $e->getMessage());
    $_SESSION['message'] = "Có lỗi xảy ra khi tải danh sách sản phẩm: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

// Lấy thông báo flash message nếu có
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm - ADMIN</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <!-- Thêm thư viện icon nếu cần -->
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'partials/sidebar.php'; // Giả sử bạn có sidebar riêng ?>
        <main class="admin-main-content">
            <?php include 'partials/header.php'; // Giả sử bạn có header riêng ?>

            <div class="page-header">
                <h2>Quản lý Sản phẩm</h2>
                <a href="product_add.php" class="btn btn-primary">Thêm Sản phẩm mới</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo escapeHtml($message_type); ?>">
                    <?php echo escapeHtml($message); ?>
                </div>
            <?php endif; ?>

            <table class="product-list-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Thương hiệu</th>
                        <th>Giá</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9">Không có sản phẩm nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo escapeHtml($product['id']); ?></td>
                                <td>
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?php echo BASE_URL . 'public/images/products/' . escapeHtml($product['image_url']); ?>" 
                                             alt="<?php echo escapeHtml($product['name']); ?>" width="50">
                                    <?php else: ?>
                                        <img src="<?php echo BASE_URL . 'public/images/placeholder.png'; ?>" 
                                             alt="No Image" width="50">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo escapeHtml($product['name']); ?></td>
                                <td><?php echo escapeHtml($product['category_name']); ?></td>
                                <td><?php echo escapeHtml($product['brand_name']); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td><?php echo escapeHtml($product['stock_quantity']); ?></td>
                                <td><?php echo escapeHtml($product['status']); ?></td>
                                <td class="actions">
                                    <a href="product_edit.php?id=<?php echo escapeHtml($product['id']); ?>" class="btn btn-sm btn-info">Sửa</a>
                                    <a href="products.php?action=delete&id=<?php echo escapeHtml($product['id']); ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?');">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    <script src="js/admin_script.js"></script>
</body>
</html>