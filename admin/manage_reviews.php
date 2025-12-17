<?php
session_start();

require __DIR__ . '/../includes/db.php'; 

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($action === 'delete') {
        try {
            $sql_delete = "DELETE FROM tbreview WHERE id = ?";
            $stmt = $pdo->prepare($sql_delete);
            $stmt->execute([$review_id]);
            $_SESSION['message'] = ['type' => 'success', 'content' => 'Đánh giá đã được xóa thành công.'];
        } catch (PDOException $e) {
            // Bắt lỗi nếu có sự cố xảy ra
            $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi khi xóa đánh giá: ' . $e->getMessage()];
        }
        
        header("Location: manage_reviews.php");
        exit;
    }
}

try {
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

    $stmt_reviews = $pdo->query($sql_reviews);
    $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage()];
    $reviews = []; 
}
require __DIR__ . '/../templates/adminheader.php';
?>


<div class="container mt-5">
    <h2>Quản lý đánh giá sản phẩm</h2>

    <?php 
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['message']['type'] . ' alert-dismissible fade show" role="alert">'
             . $_SESSION['message']['content'] .
             '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>';
        unset($_SESSION['message']);
    }
    ?>

    <div class="table-responsive">
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
                            <td><?php echo str_repeat('⭐', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></td>
                            <td style="min-width: 250px;"><?php echo nl2br(htmlspecialchars($review['content'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                            <td>
                                <a href="manage_reviews.php?action=delete&id=<?php echo $review['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa đánh giá này không?');">
                                   <i class="fas fa-trash-alt"></i> Xóa
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>