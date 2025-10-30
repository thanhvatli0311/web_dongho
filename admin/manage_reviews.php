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

// Xử lý hành động Duyệt/Từ chối đánh giá
if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = (int)$_GET['id'];
    $action = $_GET['action'];
    $status = '';
    
    if ($action === 'approve') {
        $status = 'approved';
        $message = 'Đánh giá đã được duyệt thành công.';
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $message = 'Đánh giá đã bị từ chối.';
    }

    if ($status) {
        $sql_update = "UPDATE reviews SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $status, $review_id);
        if ($stmt_update->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'content' => $message];
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'content' => 'Lỗi khi cập nhật trạng thái: ' . $conn->error];
        }
        // Chuyển hướng để loại bỏ tham số action/id khỏi URL
        header("Location: manage_reviews.php");
        exit;
    }
}

// Lấy tất cả đánh giá, sắp xếp đánh giá "pending" lên đầu
$sql_reviews = "SELECT 
                    r.id, 
                    r.rating, 
                    r.comment, 
                    r.status, 
                    r.created_at, 
                    r.user_id, 
                    m.tenhang AS product_name 
                FROM reviews r
                JOIN tbmathang m ON r.mathang_id = m.mahang
                ORDER BY FIELD(r.status, 'pending') DESC, r.created_at DESC"; // Đưa 'pending' lên đầu
$result_reviews = $conn->query($sql_reviews);
$reviews = $result_reviews->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-5">
    <h2>Quản lý Đánh giá Sản phẩm</h2>

    <?php 
    // Hiển thị thông báo (nếu có)
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
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="8" class="text-center">Không có đánh giá nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <tr class="<?php 
                        if ($review['status'] === 'pending') echo 'table-warning'; 
                        elseif ($review['status'] === 'approved') echo 'table-success'; 
                        else echo 'table-danger'; 
                    ?>">
                        <td><?php echo $review['id']; ?></td>
                        <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($review['user_id']); ?></td>
                        <td><?php echo $review['rating']; ?> / 5</td>
                        <td><?php echo nl2br(htmlspecialchars(substr($review['comment'], 0, 100))) . (strlen($review['comment']) > 100 ? '...' : ''); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                if ($review['status'] === 'pending') echo 'warning'; 
                                elseif ($review['status'] === 'approved') echo 'success'; 
                                else echo 'danger'; 
                            ?>">
                                <?php 
                                    if ($review['status'] === 'pending') echo 'Chờ duyệt';
                                    elseif ($review['status'] === 'approved') echo 'Đã duyệt';
                                    else echo 'Từ chối';
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($review['status'] === 'pending'): ?>
                                <a href="manage_reviews.php?action=approve&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Bạn có chắc chắn muốn DUYỆT đánh giá này không?');">Duyệt</a>
                                <a href="manage_reviews.php?action=reject&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn TỪ CHỐI đánh giá này không?');">Từ chối</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>Đã xử lý</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

