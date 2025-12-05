<?php
// Include DB Connection (Header đã start session rồi nhưng include db ở đây để chắc chắn logic xử lý hoạt động)
require __DIR__ . '/../includes/db.php';

// XỬ LÝ XÓA LEAD (Khi đã gọi xong)
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM customer_leads WHERE id = ?");
    $stmt->execute([$id]);
    // Redirect để tránh resubmit form
    header("Location: manage_leads.php");
    exit;
}

// LẤY DANH SÁCH KHÁCH HÀNG
$stmt = $pdo->query("SELECT * FROM customer_leads ORDER BY created_at DESC");
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include Header (Chứa HTML mở đầu, sidebar, navbar)
require __DIR__ . '/../templates/adminheader.php';
?>

<!-- NỘI DUNG TRANG (Nằm trong .main-content) -->

<div class="row">
    <div class="col-12">
        <div class="card-clean">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 style="margin:0; font-size:18px; font-weight:600;">Danh sách SĐT thu thập từ Chatbot</h4>
                <span class="badge bg-primary rounded-pill px-3 py-2">Tổng: <?= count($leads) ?></span>
            </div>

            <?php if (empty($leads)): ?>
                <div class="text-center py-5">
                    <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" alt="Empty" style="width: 80px; opacity: 0.5; margin-bottom: 20px;">
                    <p class="text-muted">Chưa có khách hàng nào để lại số điện thoại.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Thời gian</th>
                                <th>Số điện thoại</th>
                                <th>Nội dung nhắn</th>
                                <th>Mã User</th>
                                <th class="text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <div style="font-weight: 500;"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?= date('d/m/Y', strtotime($row['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <span style="color: var(--primary-color); font-weight: 700; font-size: 15px;">
                                            <?= htmlspecialchars($row['phone_number']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="background: #F3F4F6; padding: 8px 12px; border-radius: 6px; display: inline-block; font-style: italic;">
                                            "<?= htmlspecialchars($row['message_content']) ?>"
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars(substr($row['user_identifier'], 0, 8)) ?>...
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <!-- Nút Gọi -->
                                        <a href="tel:<?= htmlspecialchars($row['phone_number']) ?>" class="btn btn-sm btn-success me-2" title="Gọi ngay">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                        
                                        <!-- Nút Xóa/Hoàn thành -->
                                        <a href="manage_leads.php?delete_id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Xác nhận đã xử lý xong khách hàng này? Dữ liệu sẽ bị xóa.')"
                                           title="Đã xử lý xong">
                                            <i class="fas fa-check"></i> Xử lý
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Đóng thẻ div main-content, main-wrapper và body (Đã mở ở header) -->
</div> 
</div>
</body>
</html>