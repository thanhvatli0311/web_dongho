<?php
// search.php
// Đảm bảo file db.php của bạn đã tạo biến $pdo
require_once '../includes/db.php'; 
// Đảm bảo session_start.php chứa session_start()
include '../session/session_start.php'; 
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
.main-content-search {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 15px;
}

.filter-bar {
    background: #f7f7f7;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    gap: 20px;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.filter-bar .filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0;
}
.filter-bar label {
    font-weight: bold;
    color: #555;
    white-space: nowrap;
}
.filter-bar select {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    min-width: 150px;
}
.filter-bar button {
    padding: 8px 15px;
    background: #F39C12;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
}
.filter-bar button:hover {
    background: #E67E22;
}


.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 25px;
    margin-top: 20px;
}
.product-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    text-align: center;
    padding: 15px;
    transition: box-shadow 0.3s, transform 0.3s;
}
.product-card:hover {
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    transform: translateY(-5px);
}
.product-card img {
    max-width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 6px;
}
.product-card h3 {
    font-size: 18px;
    margin: 10px 0;
    height: 45px;
    overflow: hidden;
    color: #34495E;
}
.action-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
}
.price {
    font-size: 20px;
    color: #E74C3C;
    font-weight: bold;
}
.add-to-cart {
    background: #2ECC71;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 50px;
    cursor: pointer;
    transition: background 0.3s;
    font-size: 14px;
}
.add-to-cart:hover {
    background: #27AE60;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.pagination {
    margin-top: 30px;
    text-align: center;
}
.pagination a {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 5px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    border-radius: 4px;
    transition: background 0.3s, border-color 0.3s;
}
.pagination a:hover:not(.active) {
    background: #eee;
}
.pagination a.active {
    background: #F39C12;
    color: white;
    border-color: #F39C12;
}
.toast {
    display: none;
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #2ECC71;
    color: #fff;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    z-index: 1000;
}
.toast i {
    margin-right: 10px;
}

@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-bar .filter-group {
        margin-bottom: 10px;
    }
    .filter-bar select {
        width: 100%;
    }
}
</style>
</head>
<body>

<div class="main-content-search">
    
    <h1>Kết quả tìm kiếm cho: **<?= htmlspecialchars($q) ?>**</h1>

    <div class="filter-bar">
        <form action="search.php" method="get" id="filter-form" style="display: flex; gap: 20px; align-items: center; width: 100%;">
            <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
            
            <div class="filter-group">
                <label for="brand">Hãng:</label>
                <select name="brand" id="brand">
                    <option value="">Tất cả</option>
                    <?php
                    $brands_sql = "SELECT DISTINCT thuonghieu FROM tbmathang WHERE thuonghieu IS NOT NULL AND thuonghieu <> '' ORDER BY thuonghieu ASC";
                    $brands_stmt = $pdo->query($brands_sql);
                    $brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($brands as $brand_row) {
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
                    <option value="" <?= ($sort === "") ? 'selected' : '' ?>>Tên (A-Z)</option>
                    <option value="price_asc" <?= ($sort === "price_asc") ? 'selected' : '' ?>>Giá tăng dần</option>
                    <option value="price_desc" <?= ($sort === "price_desc") ? 'selected' : '' ?>>Giá giảm dần</option>
                </select>
            </div>
            <button type="submit">Áp dụng</button>
        </form>
    </div>
    
    <?php
    if (!empty($q)) {
        // Phân trang
        $limit = 8;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        
        $searchParam = "%" . $q . "%";
        $sql = "SELECT * FROM tbmathang WHERE tenhang LIKE ?";
        $params = [$searchParam];
        
        // Thêm điều kiện lọc
        if (!empty($brand)) { $sql .= " AND thuonghieu = ?"; $params[] = $brand; }
        
        if (!empty($price_range)) {
            switch($price_range) {
                case '0-50000000': $sql .= " AND dongia <= 50000000"; break;
                case '50000000-200000000': $sql .= " AND dongia BETWEEN 50000000 AND 200000000"; break;
                case '200000000-500000000': $sql .= " AND dongia BETWEEN 200000000 AND 500000000"; break;
                case '500000000-1000000000': $sql .= " AND dongia BETWEEN 500000000 AND 1000000000"; break;
                case '1000000000+': $sql .= " AND dongia > 1000000000"; break;
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

        // TÍNH TỔNG SẢN PHẨM TRƯỚC (PDO)
        $count_sql = "SELECT COUNT(*) AS count FROM tbmathang WHERE tenhang LIKE ?";
        $count_params = [$searchParam];
        if (!empty($brand)) { $count_sql .= " AND thuonghieu = ?"; $count_params[] = $brand; }
        if (!empty($price_range)) {
            switch($price_range) {
                case '0-50000000': $count_sql .= " AND dongia <= 50000000"; break;
                case '50000000-200000000': $count_sql .= " AND dongia BETWEEN 50000000 AND 200000000"; break;
                case '200000000-500000000': $count_sql .= " AND dongia BETWEEN 200000000 AND 500000000"; break;
                case '500000000-1000000000': $count_sql .= " AND dongia BETWEEN 500000000 AND 1000000000"; break;
                case '1000000000+': $count_sql .= " AND dongia > 1000000000"; break;
            }
        }

        $stmt_total = $pdo->prepare($count_sql);
        $stmt_total->execute($count_params);
        $total_items = $stmt_total->fetch()['count'];
        $total_pages = ceil($total_items / $limit) ?: 1;

        // Truy vấn chính
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit; 
        $params[] = $offset; 

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC); 
        
        if (count($products) > 0) {
            echo "<p>Tìm thấy **{$total_items}** sản phẩm phù hợp.</p>";
            
            echo '<div class="product-grid">';
            foreach ($products as $row) {
                $hinhanh = !empty($row['hinhanh']) ? "../assets/images/" . $row['hinhanh'] : "../assets/images/default.jpg";
                if (!file_exists($hinhanh)) {
                    $hinhanh = "../assets/images/default.jpg";
                }
                ?>
                <div class="product-card">
                    <a href="product_detail.php?mahang=<?= urlencode($row['mahang']) ?>">
                        <img src="<?= htmlspecialchars($hinhanh) ?>" alt="<?= htmlspecialchars($row['tenhang']) ?>">
                    </a>
                    <div class="card-content">
                        <h3><?= htmlspecialchars($row['tenhang']) ?></h3>
                        <div class="action-group">
                            <span class="price"><?= number_format($row['dongia'], 0, ',', '.') ?> VND</span>
                            <button class="add-to-cart" data-id="<?= htmlspecialchars($row['mahang']) ?>">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
            
            // PHÂN TRANG
            echo '<div class="pagination">';
            $current_url_params = "q=" . urlencode($q) . "&brand=" . urlencode($brand) . "&price_range=" . urlencode($price_range) . "&sort=" . urlencode($sort);

            if ($page > 1) {
                echo '<a href="search.php?page=' . ($page - 1) . '&' . $current_url_params . '">« Trước</a>';
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                $active = ($i == $page) ? 'active' : '';
                echo '<a class="' . $active . '" href="search.php?page=' . $i . '&' . $current_url_params . '">' . $i . '</a>';
            }
            if ($page < $total_pages) {
                echo '<a href="search.php?page=' . ($page + 1) . '&' . $current_url_params . '">Sau »</a>';
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

<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i> Sản phẩm đã được thêm vào giỏ hàng!
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function(){
        // ===================================================
        // UX Improvement: Auto-submit form khi có thay đổi
        // ===================================================
        $("#brand, #price_range, #sort").change(function() {
            // Khi dropdown thay đổi, tự động submit form để cập nhật kết quả
            $(this).closest('form').submit();
        });

        // ===================================================
        // Xử lý Add to Cart & Toast
        // ===================================================
        $(".add-to-cart").click(function(){
            var item_id = $(this).data("id");
            var button = $(this);
            var originalText = button.html();
            
            // Cung cấp phản hồi tức thì cho người dùng
            button.html('<i class="fas fa-spinner fa-spin"></i> Đang thêm...').prop('disabled', true);

            $.ajax({
                url: "cart.php", 
                type: "GET", // Hoặc POST
                data: { add_to_cart: item_id },
                success: function(response){
                    $("#toast").fadeIn(400).delay(1500).fadeOut(400);

                    // Khôi phục nút
                    button.html(originalText).prop('disabled', false);

                    // Logic cập nhật số đếm giỏ hàng (tùy thuộc vào header.php)
                    // ...
                },
                error: function(){
                    alert("Có lỗi xảy ra, vui lòng thử lại!");
                    button.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>

<?php include '../templates/footer.php'; ?>
</body>
</html>