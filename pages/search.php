<?php
include '../session/session_start.php';
include '../includes/db.php';
include '../templates/header.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : "";
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : "";
$price_range = isset($_GET['price_range']) ? trim($_GET['price_range']) : "";
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : "";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả tìm kiếm cho: <?= htmlspecialchars($q) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        
    </style>
</head>
<body>
<div class="container">
    <h1>Kết quả tìm kiếm cho: <?= htmlspecialchars($q) ?></h1>
    <!-- Sidebar lọc sản phẩm -->
    <aside class="sidebar">
        <h3>Lọc theo</h3>
        <form action="search.php" method="get">
            <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
            <div class="filter-group">
                <label for="brand">Hãng:</label>
                <select name="brand" id="brand">
                    <option value="">Tất cả</option>
                    <?php
                    $brands_sql = "SELECT DISTINCT thuonghieu FROM tbmathang WHERE thuonghieu IS NOT NULL";
                    $brands_result = $conn->query($brands_sql);
                    while ($brand_row = $brands_result->fetch_assoc()) {
                        $selected = ($brand === $brand_row['thuonghieu']) ? 'selected' : '';
                        echo '<option value="'.htmlspecialchars($brand_row['thuonghieu']).'" '.$selected.'>'.htmlspecialchars($brand_row['thuonghieu']).'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="price_range">Khoảng giá:</label>
                <select name="price_range" id="price_range">
                    <option value="">Tất cả</option>
                    <option value="0-50000000" <?= ($price_range === '0-50000000') ? 'selected' : '' ?>>Dưới 50 triệu</option>
                    <option value="50000000-200000000" <?= ($price_range === '50000000-200000000') ? 'selected' : '' ?>>50 triệu - 200 triệu</option>
                    <option value="200000000-500000000" <?= ($price_range === '200000000-500000000') ? 'selected' : '' ?>>200 triệu - 500 triệu</option>
                    <option value="500000000-1000000000" <?= ($price_range === '500000000-1000000000') ? 'selected' : '' ?>>500 triệu - 1 tỷ</option>
                    <option value="1000000000+" <?= ($price_range === '1000000000+') ? 'selected' : '' ?>>Trên 1 tỷ</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="sort">Sắp xếp:</label>
                <select name="sort" id="sort">
                    <option value="" <?= ($sort === "") ? 'selected' : '' ?>>Mặc định</option>
                    <option value="price_asc" <?= ($sort === "price_asc") ? 'selected' : '' ?>>Giá tăng dần</option>
                    <option value="price_desc" <?= ($sort === "price_desc") ? 'selected' : '' ?>>Giá giảm dần</option>
                </select>
            </div>
            <button type="submit">Lọc sản phẩm</button>
        </form>
    </aside>
    
    <?php
    if (!empty($q)) {
        // Phân trang: số sản phẩm mỗi trang
        $limit = 8;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        
        // Xây dựng truy vấn tìm kiếm với lọc động
        $searchParam = "%" . $q . "%";
        $sql = "SELECT * FROM tbmathang WHERE tenhang LIKE ?";
        $params = [$searchParam];
        $types = "s";
        
        if (!empty($brand)) {
            $sql .= " AND thuonghieu = ?";
            $params[] = $brand;
            $types .= "s";
        }
        
        if (!empty($price_range)) {
            switch($price_range) {
                case '0-50000000':
                    $sql .= " AND dongia <= 50000000";
                    break;
                case '50000000-200000000':
                    $sql .= " AND dongia BETWEEN 50000000 AND 200000000";
                    break;
                case '200000000-500000000':
                    $sql .= " AND dongia BETWEEN 200000000 AND 500000000";
                    break;
                case '500000000-1000000000':
                    $sql .= " AND dongia BETWEEN 500000000 AND 1000000000";
                    break;
                case '1000000000+':
                    $sql .= " AND dongia > 1000000000";
                    break;
            }
        }
        
        // Xử lý sắp xếp
        if ($sort === "price_asc") {
            $sql .= " ORDER BY dongia ASC";
        } elseif ($sort === "price_desc") {
            $sql .= " ORDER BY dongia DESC";
        } else {
            $sql .= " ORDER BY tenhang ASC";
        }
        
        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo '<div class="product-grid">';
            while ($row = $result->fetch_assoc()) {
                $hinhanh = !empty($row['hinhanh']) ? "../assets/images/" . $row['hinhanh'] : "../assets/images/default.jpg";
                if (!file_exists($hinhanh)) {
                    $hinhanh = "../assets/images/default.jpg";
                }
                ?>
                <div class="product-card">
                    <a href="product_detail.php?mahang=<?= urlencode($row['mahang']) ?>">
                        <img src="<?= $hinhanh ?>" alt="<?= htmlspecialchars($row['tenhang']) ?>">
                    </a>
                    <div class="card-content">
                        <h3><?= htmlspecialchars($row['tenhang']) ?></h3>
                        <div class="action-group">
                            <span class="price"><?= number_format($row['dongia'], 0) ?> VND</span>
                            <button class="add-to-cart" data-id="<?= $row['mahang'] ?>">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
            
            // Tính tổng số sản phẩm và phân trang
            $sql_total = "SELECT COUNT(*) AS count FROM tbmathang WHERE tenhang LIKE ?";
            $total_params = [$searchParam];
            $total_types = "s";
            
            if (!empty($brand)) {
                $sql_total .= " AND thuonghieu = ?";
                $total_params[] = $brand;
                $total_types .= "s";
            }
            if (!empty($price_range)) {
                switch($price_range) {
                    case '0-50000000':
                        $sql_total .= " AND dongia <= 50000000";
                        break;
                    case '50000000-200000000':
                        $sql_total .= " AND dongia BETWEEN 50000000 AND 200000000";
                        break;
                    case '200000000-500000000':
                        $sql_total .= " AND dongia BETWEEN 200000000 AND 500000000";
                        break;
                    case '500000000-1000000000':
                        $sql_total .= " AND dongia BETWEEN 500000000 AND 1000000000";
                        break;
                    case '1000000000+':
                        $sql_total .= " AND dongia > 1000000000";
                        break;
                }
            }
            $stmt_total = $conn->prepare($sql_total);
            $stmt_total->bind_param($total_types, ...$total_params);
            $stmt_total->execute();
            $total_items = $stmt_total->get_result()->fetch_assoc()['count'];
            $total_pages = ceil($total_items / $limit) ?: 1;
            
            echo '<div class="pagination">';
            if ($page > 1) {
                echo '<a href="search.php?page=' . ($page - 1) . '&q=' . urlencode($q) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">« Trước</a>';
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                $active = ($i == $page) ? 'active' : '';
                echo '<a class="' . $active . '" href="search.php?page=' . $i . '&q=' . urlencode($q) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">' . $i . '</a>';
            }
            if ($page < $total_pages) {
                echo '<a href="search.php?page=' . ($page + 1) . '&q=' . urlencode($q) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">Sau »</a>';
            }
            echo '</div>';
            
        } else {
            echo "<p>Không tìm thấy sản phẩm nào phù hợp với từ khóa <strong>" . htmlspecialchars($q) . "</strong>.</p>";
        }
    } else {
        echo "<p>Vui lòng nhập từ khóa tìm kiếm.</p>";
    }
    ?>
</div>

<!-- Toast Notification -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i> Sản phẩm đã được thêm vào giỏ hàng!
</div>

<!-- jQuery & AJAX xử lý nút Thêm vào giỏ -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function(){
        $(".add-to-cart").click(function(){
            var item_id = $(this).data("id");
            $.ajax({
                url: "cart.php",
                type: "GET",
                data: { add_to_cart: item_id },
                success: function(response){
                    $("#toast").fadeIn(400).delay(1500).fadeOut(400);
                },
                error: function(){
                    alert("Có lỗi xảy ra, vui lòng thử lại!");
                }
            });
        });
    });
</script>

<?php include '../templates/footer.php'; ?>
</body>
</html>
