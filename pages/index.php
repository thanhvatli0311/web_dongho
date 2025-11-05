<?php
include '../session/session_start.php';
include '../includes/db.php';
include '../templates/header.php';

// Tính số lượng sản phẩm duy nhất trong giỏ hàng từ session
$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
  $cart_count = count($_SESSION['cart']);
}

// Lấy dữ liệu lọc từ URL
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : "";
$price_range = isset($_GET['price_range']) ? trim($_GET['price_range']) : "";
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : "";  // Thêm biến sắp xếp

// Lấy seed từ URL hoặc tạo mới nếu chưa có
$seed = isset($_GET['seed']) ? (int)$_GET['seed'] : mt_rand();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cửa Hàng Đồng Hồ Cao Cấp</title>
  <link rel="stylesheet" href="../LIB/fontawesome-free-6.4.2-web/css/all.min.css">
  <!-- Slick Slider CSS từ CDN -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.css" />
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.css" />
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
  </style>
</head>

<body>

  <!-- Hero Section với Slideshow -->
  <div class="hero">
    <div class="slideshow-container">
      <div class="mySlides fade">
        <div class="numbertext">1 / 4</div>
        <img src="../assets/images/rolex_banner2.jpg" alt="Banner 1">
      </div>
      <div class="mySlides fade">
        <div class="numbertext">2 / 4</div>
        <img src="../assets/images/banner4.jpg" alt="Banner 2">
      </div>
      <div class="mySlides fade">
        <div class="numbertext">3 / 4</div>
        <img src="../assets/images/nu_banner.jpg" alt="Banner 3">
      </div>
      <div class="mySlides fade">
        <div class="numbertext">4 / 4</div>
        <img src="../assets/images/rolex_banner3.jpg" alt="Banner 4">
      </div>
      <a class="prev" onclick="plusSlides(-1)">❮</a>
      <a class="next" onclick="plusSlides(1)">❯</a>
    </div>
    <br>
    <div style="text-align:center">
      <span class="dot" onclick="currentSlide(1)"></span>
      <span class="dot" onclick="currentSlide(2)"></span>
      <span class="dot" onclick="currentSlide(3)"></span>
    </div>
  </div>

  <!-- Carousel: Top 7 sản phẩm nổi bật -->
  <section class="highest-price-carousel">
    <h2>Sản phẩm nổi bật</h2>
    <div class="carousel-container">
      <?php
      $sql_highest = "SELECT * FROM tbmathang ORDER BY dongia DESC LIMIT 7";
      $result_highest = $conn->query($sql_highest);
      while ($row_high = $result_highest->fetch_assoc()) {
        $hinhanh = !empty($row_high['hinhanh']) ? "../assets/images/" . $row_high['hinhanh'] : "../assets/images/default.jpg";
        if (!file_exists($hinhanh)) {
          $hinhanh = "../assets/images/default.jpg";
        }
      ?>
        <div class="carousel-item">
          <a href="product_detail.php?mahang=<?php echo urlencode($row_high['mahang']); ?>">
            <img src="<?php echo $hinhanh; ?>" alt="<?php echo htmlspecialchars($row_high['tenhang']); ?>">
            <h3><?php echo htmlspecialchars($row_high['tenhang']); ?></h3>
            <span class="price"><?php echo number_format($row_high['dongia'], 0); ?> VND</span>
          </a>
        </div>
      <?php } ?>
    </div>
  </section>

  <!-- Sidebar lọc sản phẩm -->
  <aside class="sidebar">
    <h3>Lọc theo</h3>
    <form action="index.php" method="GET">
      <div class="filter-group">
        <label for="brand">Hãng:</label>
        <select name="brand" id="brand">
          <option value="">Tất cả</option>
          <?php
          $brands_sql = "SELECT DISTINCT thuonghieu FROM tbmathang WHERE thuonghieu IS NOT NULL";
          $brands_result = $conn->query($brands_sql);
          while ($brand_row = $brands_result->fetch_assoc()) { ?>
            <option value="<?php echo htmlspecialchars($brand_row['thuonghieu']); ?>"
              <?php echo $brand === $brand_row['thuonghieu'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($brand_row['thuonghieu']); ?>
            </option>
          <?php } ?>
        </select>
      </div>
      <div class="filter-group">
        <label for="price_range">Khoảng giá:</label>
        <select name="price_range" id="price_range">
          <option value="">Tất cả</option>
          <option value="0-50000000" <?php echo $price_range === '0-50000000' ? 'selected' : ''; ?>>
            Dưới 50 triệu
          </option>
          <option value="50000000-200000000" <?php echo $price_range === '50000000-200000000' ? 'selected' : ''; ?>>
            50 triệu - 200 triệu
          </option>
          <option value="200000000-500000000" <?php echo $price_range === '200000000-500000000' ? 'selected' : ''; ?>>
            200 triệu - 500 triệu
          </option>
          <option value="500000000-1000000000" <?php echo $price_range === '500000000-1000000000' ? 'selected' : ''; ?>>
            500 triệu - 1 tỷ
          </option>
          <option value="1000000000+" <?php echo $price_range === '1000000000+' ? 'selected' : ''; ?>>
            Trên 1 tỷ
          </option>
        </select>
      </div>
      <!-- Dropdown sắp xếp theo giá -->
      <div class="filter-group">
        <label for="sort">Sắp xếp:</label>
        <select name="sort" id="sort">
          <option value="" <?php echo $sort === "" ? 'selected' : ''; ?>>Mặc định</option>
          <option value="price_asc" <?php echo $sort === "price_asc" ? 'selected' : ''; ?>>Giá tăng dần</option>
          <option value="price_desc" <?php echo $sort === "price_desc" ? 'selected' : ''; ?>>Giá giảm dần</option>
        </select>
      </div>
      <input type="hidden" name="seed" value="<?php echo $seed; ?>">
      <button type="submit">Lọc sản phẩm</button>
    </form>
  </aside>

  <!-- Danh sách sản phẩm -->
  <section class="product-listing">
    <div class="product-grid">
      <?php
      $limit = 8;
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $offset = ($page - 1) * $limit;

      $sql = "SELECT * FROM tbmathang WHERE tenhang LIKE ?";
      $params = ["%$search%"];
      $types = "s";
      if (!empty($brand)) {
        $sql .= " AND thuonghieu = ?";
        $params[] = $brand;
        $types .= "s";
      }
      if (!empty($price_range)) {
        switch ($price_range) {
          case '0-50000000':
            $sql .= " AND dongia <= 50000000";
            break;
          case '50000000-200000000':
            $sql .= " AND dongia BETWEEN 50000000 AND 200000000";
            break;
          case '200000000-500000000':
            $sql .= " AND dongia BETWEEN 500000000 AND 200000000";
            break;
          case '500000000-1000000000':
            $sql .= " AND dongia BETWEEN 500000000 AND 1000000000";
            break;
          case '1000000000+':
            $sql .= " AND dongia > 1000000000";
            break;
        }
      }
      // Xử lý sắp xếp: nếu có chọn sort thì dùng ORDER BY theo giá, nếu không thì dùng thứ tự ngẫu nhiên
      if ($sort === "price_asc") {
        $sql .= " ORDER BY dongia ASC";
      } elseif ($sort === "price_desc") {
        $sql .= " ORDER BY dongia DESC";
      } else {
        $sql .= " ORDER BY RAND($seed)";
      }
      $sql .= " LIMIT ?, ?";
      $params[] = $offset;
      $params[] = $limit;
      $types .= "ii";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $hinhanh = !empty($row['hinhanh']) ? "../assets/images/" . $row['hinhanh'] : "../assets/images/default.jpg";
        if (!file_exists($hinhanh)) {
          $hinhanh = "../assets/images/default.jpg";
        }
        $disabled = (strtolower(trim($row['conhang'])) === 'hết hàng') ? 'disabled' : '';
      ?>
        <div class="product-card">
          <a href="product_detail.php?mahang=<?php echo urlencode($row['mahang']); ?>">
            <img src="<?php echo $hinhanh; ?>" alt="<?php echo htmlspecialchars($row['tenhang']); ?>">
          </a>
          <div class="card-content">
            <h3><?php echo htmlspecialchars($row['tenhang']); ?></h3>
            <div class="action-group">
              <span class="price"><?php echo number_format($row['dongia'], 0); ?> VND</span>
              <button class="add-to-cart" data-id="<?php echo $row['mahang']; ?>" <?php echo $disabled ? 'disabled' : ''; ?>>
                <?php echo $disabled ? "Hết hàng" : '<i class="fas fa-cart-plus"></i> Thêm vào giỏ'; ?>
              </button>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>
  </section>

  <!-- Phân trang -->
  <div class="pagination">
    <?php
    $sql_total = "SELECT COUNT(*) AS count FROM tbmathang WHERE tenhang LIKE ?";
    $total_params = ["%$search%"];
    $total_types = "s";
    if (!empty($brand)) {
      $sql_total .= " AND thuonghieu = ?";
      $total_params[] = $brand;
      $total_types .= "s";
    }
    if (!empty($price_range)) {
      switch ($price_range) {
        case '0-50000000':
          $sql_total .= " AND dongia <= 50000000";
          break;
        case '50000000-200000000':
          $sql_total .= " AND dongia BETWEEN 50000000 AND 200000000";
          break;
        case '200000000-500000000':
          $sql_total .= " AND dongia BETWEEN 500000000 AND 200000000";
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
    if ($page > 1) {
      echo '<a href="index.php?page=' . ($page - 1) . '&seed=' . $seed . '&search=' . urlencode($search) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">« Trước</a>';
    }
    for ($i = 1; $i <= $total_pages; $i++) {
      $active = ($i == $page) ? 'active' : '';
      echo '<a class="' . $active . '" href="index.php?page=' . $i . '&seed=' . $seed . '&search=' . urlencode($search) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">' . $i . '</a>';
    }
    if ($page < $total_pages) {
      echo '<a href="index.php?page=' . ($page + 1) . '&seed=' . $seed . '&search=' . urlencode($search) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">Sau »</a>';
    }
    ?>
  </div>

  <!-- Toast Notification -->
  <div class="toast" id="toast">
    <i class="fas fa-check-circle"></i> Sản phẩm đã được thêm vào giỏ hàng!
  </div>

  <!-- jQuery & Slick Slider JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
  <script>
    $(document).ready(function() {
      // Xử lý nút thêm vào giỏ
      $(".add-to-cart").click(function() {
        if ($(this).is(':disabled')) return;
        var btn = $(this);
        var item_id = btn.data("id");
        $.ajax({
          url: "cart.php",
          type: "GET",
          data: {
            add_to_cart: item_id
          },
          success: function(response) {
            $("#toast").fadeIn(400).delay(1500).fadeOut(400);
            if (!btn.hasClass("added")) {
              btn.addClass("added");
              var badge = $(".cart-box .cart-count");
              if (badge.length === 0) {
                $(".cart-box .cart-link").append('<span class="cart-count">1</span>');
              } else {
                var currentCount = parseInt(badge.text()) || 0;
                badge.text(currentCount + 1);
              }
            }
          },
          error: function() {
            alert("Có lỗi xảy ra, vui lòng thử lại!");
          }
        });
      });

      // Khởi tạo Slick Slider cho carousel sản phẩm
      $('.carousel-container').slick({
        slidesToShow: 4,
        slidesToScroll: 4,
        infinite: true,
        autoplay: true,
        autoplaySpeed: 3000,
        arrows: true,
        prevArrow: '<button type="button" class="slick-prev"></button>',
        nextArrow: '<button type="button" class="slick-next"></button>',
        responsive: [{
            breakpoint: 1024,
            settings: {
              slidesToShow: 3,
              slidesToScroll: 3
            }
          },
          {
            breakpoint: 768,
            settings: {
              slidesToShow: 2,
              slidesToScroll: 2
            }
          },
          {
            breakpoint: 480,
            settings: {
              slidesToShow: 1,
              slidesToScroll: 1
            }
          }
        ]
      });

      // Slideshow code
      let slideIndex = 1;
      showSlides(slideIndex);
      window.plusSlides = function(n) {
        showSlides(slideIndex += n);
      }
      window.currentSlide = function(n) {
        showSlides(slideIndex = n);
      }

      function showSlides(n) {
        let i;
        let slides = document.getElementsByClassName("mySlides");
        let dots = document.getElementsByClassName("dot");
        if (n > slides.length) {
          slideIndex = 1;
        }
        if (n < 1) {
          slideIndex = slides.length;
        }
        for (i = 0; i < slides.length; i++) {
          slides[i].style.display = "none";
        }
        for (i = 0; i < dots.length; i++) {
          dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex - 1].style.display = "block";
        dots[slideIndex - 1].className += " active";
      }
    });
  </script>

  <?php include '../templates/footer.php'; ?>
</body>

</html>