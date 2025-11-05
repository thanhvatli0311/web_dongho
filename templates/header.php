<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
  $cart_count = count($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website bán hàng</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="../assets/js/jquery.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>

  </style>
</head>

<body>
  <header class="main-header">
    <div class="header-container">
      <!-- Logo và tên shop -->
      <div class="logo-area">
        <a href="../pages/index.php">
          <img src="../assets/images/logo.png" alt="Logo">
          <span class="shop-name">WATCH STORE</span>
        </a>
      </div>
      <!-- Navigation -->
      <nav class="main-nav">
        <ul>
        
        </ul>
      </nav>
      <!-- Right area: Search, Cart & Account -->
      <div class="header-right">
        <div class="search-box">
          <form action="../pages/search.php" method="GET">
            <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." required>
            <button type="submit"><i class="fas fa-search"></i></button>
          </form>
        </div>
        <div class="cart-box">
          <a href="../pages/cart.php" class="cart-link">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($cart_count > 0): ?>
              <span class="cart-count"><?= $cart_count ?></span>
            <?php endif; ?> 
            <div class="cart-text">Giỏ hàng</div>
          </a>
        </div>
        <div class="account-box">
          <?php if (isset($_SESSION['username'])): ?>
            <a href="../pages/account.php" class="account-link">
              <i class="fas fa-user"></i>
              <div class="account-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
            </a>
            <div class="account-dropdown">
              <ul>
                <li><a href="../pages/account.php">Tài khoản</a></li>
                <li><a href="account.php?tab=orders">Đơn hàng</a></li>
                <li><a href="../pages/logout.php">Đăng xuất</a></li>
              </ul>
            </div>
          <?php else: ?>
            <a href="../pages/login.php" class="account-link">
              <i class="fas fa-user"></i>
              <div class="account-name">Đăng nhập</div>
            </a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </header>
  <hr style="box-shadow: 0px 0px 1px 1px gray; margin-bottom: 20px;">
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const accountBox = document.querySelector('.account-box');
      const accountDropdown = document.querySelector('.account-dropdown');
      let hideTimeout;

      // Hiển thị dropdown khi di chuột vào accountBox
      accountBox.addEventListener('mouseenter', function() {
        clearTimeout(hideTimeout);
        accountDropdown.style.display = 'block';
      });

      // Ẩn dropdown khi di chuột ra khỏi accountBox với delay 300ms
      accountBox.addEventListener('mouseleave', function() {
        hideTimeout = setTimeout(function() {
          accountDropdown.style.display = 'none';
        }, 300);
      });

      // Nếu chuột di vào dropdown thì hủy bỏ việc ẩn
      accountDropdown.addEventListener('mouseenter', function() {
        clearTimeout(hideTimeout);
      });

      // Khi chuột rời khỏi dropdown cũng thực hiện ẩn với delay
      accountDropdown.addEventListener('mouseleave', function() {
        hideTimeout = setTimeout(function() {
          accountDropdown.style.display = 'none';
        }, 300);
      });
    });
  </script>
</body>

</html>