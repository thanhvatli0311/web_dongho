<?php
// 1. XỬ LÝ LOGIC
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/bao_cao.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}

$defaultStart = date('Y-m-d', strtotime('-30 days'));
$defaultEnd = date('Y-m-d');
$startDate = $_GET['start'] ?? $defaultStart;
$endDate = $_GET['end'] ?? $defaultEnd;

if (strtotime($startDate) > strtotime($endDate)) {
    [$startDate, $endDate] = [$endDate, $startDate];
}

// *** SỬ DỤNG HÀM MỚI ***
$reportData = getProductRevenueReport($pdo, $startDate, $endDate);

// Tính tổng footer
$sumQty = 0;
$sumTotal = 0;

foreach ($reportData as $row) {
    $sumQty += $row['so_luong_ban'];
    $sumTotal += $row['thanh_tien'];
}

require __DIR__ . '/../templates/adminheader.php';
?>

<!-- STYLE RIÊNG CHO BẢNG GIỐNG ẢNH MẪU -->
<style>
    .table-report thead th {
        background-color: #0d47a1 !important; /* Màu xanh đậm giống MISA/Excel */
        color: #ffffff !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        vertical-align: middle;
        border-color: #0d47a1;
        padding: 12px;
    }
    .table-report tbody td {
        vertical-align: middle;
        padding: 10px 12px;
        border-bottom: 1px solid #e0e0e0;
        color: #333;
    }
    .table-report tr:hover {
        background-color: #e3f2fd; /* Màu hover xanh nhạt */
    }
    .total-row td {
        background-color: #fff9c4 !important; /* Màu vàng nhạt cho dòng tổng */
        font-weight: bold;
        color: #d84315;
        border-top: 2px solid #ffb74d;
    }
</style>

<div class="card-clean">
    <!-- FILTER BAR -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h5 class="m-0 text-primary fw-bold" style="color: #0d47a1 !important;">
            <i class="fas fa-file-invoice-dollar me-2"></i>BÁO CÁO CHI TIẾT DOANH THU
        </h5>
        
        <form method="GET" class="d-flex gap-2 align-items-center">
            <div class="input-group input-group-sm">
                <span class="input-group-text fw-bold">Từ ngày</span>
                <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="input-group input-group-sm">
                <span class="input-group-text fw-bold">Đến ngày</span>
                <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm fw-bold" style="background-color: #0d47a1; border:none;">
                <i class="fas fa-filter"></i> Xem báo cáo
            </button>
            <a href="export_excel.php?start=<?= $startDate ?>&end=<?= $endDate ?>" class="btn btn-success btn-sm text-white fw-bold">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </a>
        </form>
    </div>

    <!-- TABLE -->
    <div class="table-responsive">
        <table class="table table-report table-bordered mb-0">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">TT</th>
                    <th>Ngày bán</th>
                    <th>Mã sản phẩm</th>
                    <th>Tên sản phẩm</th>
                    <th class="text-center">Số lượng bán</th>
                    <th class="text-end">Đơn giá (VNĐ)</th>
                    <th class="text-end">Thành tiền (VNĐ)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($reportData)): ?>
                    <?php $i = 1; foreach($reportData as $row): ?>
                    <tr>
                        <td class="text-center text-muted"><?= $i++ ?></td>
                        <td><?= date('d/m/Y', strtotime($row['ngay_ban'])) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['mahang']) ?></span></td>
                        <td class="fw-medium"><?= htmlspecialchars($row['tenhang']) ?></td>
                        <td class="text-center"><?= number_format($row['so_luong_ban']) ?></td>
                        <td class="text-end"><?= number_format($row['dongia'], 0, ',', '.') ?></td>
                        <td class="text-end fw-bold text-dark"><?= number_format($row['thanh_tien'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- DÒNG TỔNG -->
                    <tr class="total-row">
                        <td colspan="4" class="text-center">TỔNG CỘNG</td>
                        <td class="text-center"><?= number_format($sumQty) ?></td>
                        <td></td>
                        <td class="text-end"><?= number_format($sumTotal, 0, ',', '.') ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-search fa-2x mb-2"></i><br>
                            Không có dữ liệu bán hàng trong khoảng thời gian này.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Đóng thẻ div do header mở -->
    </div> 
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>