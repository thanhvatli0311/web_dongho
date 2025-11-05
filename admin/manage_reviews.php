<?php
session_start();
include '../includes/db.php';
include '../templates/adminheader.php';
include '../includes/functions.php'; // Cần thiết cho các hàm chung (nếu có)

// Kiểm tra nếu chưa đăng nhập hoặc không phải admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Xử lý hành động Xóa đánh giá (vì bảng không có cột status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'delete') {
        $sql_delete = "DELETE FROM tbreview WHERE id = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $review_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'content' => 'Đánh giá đã được xóa thành công.'];
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi khi xóa đánh giá: ' . $conn->error];
        }
        header("Location: manage_reviews.php");
        exit;
    }
}

// Lấy tất cả đánh giá và tên sản phẩm
$sql_reviews = "
    SELECT 
        r.id, 
        r.mahang, 
        r.username, 
        r.rating, 
        r.content, 
        r.created_at,
        m.tenhang AS product_name
    FROM tbreview r
    JOIN tbmathang m ON r.mahang = m.mahang
    ORDER BY r.created_at DESC
";

$result_reviews = $conn->query($sql_reviews);
$reviews = $result_reviews ? $result_reviews->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="container mt-5">
    <h2>Quản lý Đánh giá Sản phẩm</h2>

    <?php 
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['message']['type'] . '">'. $_SESSION['message']['content'] .'</div>';
        unset($_SESSION['message']);
    }
    ?>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Sản phẩm</th>
                <th>Người đánh giá</th>
                <th>Sao</th>
                <th>Bình luận</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="7" class="text-center">Không có đánh giá nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><?php echo $review['id']; ?></td>
                        <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($review['username']); ?></td>
                        <td><?php echo $review['rating']; ?> / 5</td>
                        <td><?php echo nl2br(htmlspecialchars($review['content'])); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                        <td>
                            <a href="manage_reviews.php?action=delete&id=<?php echo $review['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Bạn có chắc chắn muốn xóa đánh giá này không?');">
                               Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
