<?php
// admin/process_tracking.php - Xử lý gán Mã Vận Đơn GHTK
session_start();

// === CHỈNH SỬA ĐƯỜNG DẪN NÀY CHO ĐÚNG ===
include '../db.php'; 
// Nếu db.php nằm trong thư mục includes, sử dụng: include '../includes/db.php';
// ======================================

// Khai báo $pdo là biến toàn cục
global $pdo; 

if (!isset($pdo) || !$pdo) {
    // Nếu kết nối CSDL lỗi, chuyển hướng và báo lỗi
    header('Location: assign_tracking_code.php?msg=error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'assign_code') {
    
    $madonhang = $_POST['madonhang'] ?? '';
    $mavandon = $_POST['mavandon'] ?? '';

    if (empty($madonhang) || empty($mavandon)) {
        header('Location: assign_tracking_code.php?msg=error');
        exit;
    }

    try {
        // Cập nhật mã vận đơn, trạng thái, và thời gian
        $sql = "UPDATE tbdonhang 
                SET mavandon = ?, 
                    tinhtrang = 'Đang xử lý', 
                    current_status_id = 1,  -- 1 là ID trạng thái khởi tạo (Web/GHTK)
                    thoigian_capnhat = NOW() 
                WHERE madonhang = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mavandon, $madonhang]);

        if ($stmt->rowCount() > 0) {
            // Cập nhật thành công, chuyển hướng về trang gán mã và báo success
            header('Location: assign_tracking_code.php?msg=success');
        } else {
            // Không có dòng nào được cập nhật (madonhang có thể không tồn tại)
            header('Location: assign_tracking_code.php?msg=error');
        }

    } catch (PDOException $e) {
        error_log('Lỗi CSDL khi gán mã vận đơn: ' . $e->getMessage());
        header('Location: assign_tracking_code.php?msg=error');
    }
} else {
    // Truy cập trực tiếp hoặc không hợp lệ
    header('Location: assign_tracking_code.php');
}

exit;
?>