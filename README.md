DỰ ÁN WEB BÁN ĐỒNG HỒ TRỰC TUYẾN
1. Giới thiệu tổng quan
Dự án Web Bán Đồng Hồ Trực Tuyến là một ứng dụng thương mại điện tử được phát triển bằng PHP thuần và MySQL. Hệ thống giúp khách hàng có thể mua sắm trực tuyến, xem thông tin sản phẩm, thêm vào giỏ hàng và đặt hàng. Bên cạnh đó, quản trị viên có thể dễ dàng quản lý sản phẩm, danh mục, thương hiệu, đơn hàng và tài khoản người dùng thông qua giao diện quản trị.
2. Tính năng của hệ thống
2.1. Cho người dùng (Client-side)

- Duyệt và tìm kiếm sản phẩm đồng hồ.
- Xem chi tiết sản phẩm (hình ảnh, mô tả, giá, thương hiệu).
- Thêm sản phẩm vào giỏ hàng.
- Đăng ký, đăng nhập và đăng xuất tài khoản.
- Cập nhật thông tin cá nhân (profile).
- Thực hiện đặt hàng và theo dõi trạng thái đơn hàng.

2.2. Cho quản trị viên (Admin-side)

- Dashboard hiển thị số liệu thống kê.
- Quản lý sản phẩm (thêm, sửa, xóa, cập nhật tồn kho).
- Quản lý danh mục và thương hiệu sản phẩm.
- Quản lý đơn hàng (xem chi tiết, thay đổi trạng thái giao hàng).
- Quản lý tài khoản người dùng và phân quyền.

3. Công nghệ sử dụng

• Frontend: HTML5, CSS3 (Responsive Design), JavaScript (Vanilla JS)
• Backend: PHP (Native)
• Database: MySQL
• Web Server: Apache (XAMPP)
• IDE/Editor: Visual Studio Code

4. Cấu trúc thư mục dự án

project-dongho/
├── admin/                  # Trang quản trị
│   ├── css/
│   ├── js/
│   ├── partials/
│   ├── index.php           # Dashboard chính
│   ├── login.php           # Đăng nhập admin
│   ├── products.php        # Quản lý sản phẩm
│   └── ...                 # Các module quản lý khác
│
├── api/                    # Các API PHP trả về JSON
│   ├── auth.php            # Đăng ký, đăng nhập
│   ├── products.php        # API sản phẩm
│   ├── cart.php            # API giỏ hàng
│   ├── orders.php          # API đơn hàng
│   └── ...                 # Các API khác
│
├── includes/               # Các file cấu hình và hàm dùng chung
│   ├── config.php          # Cấu hình hệ thống & database
│   ├── database.php        # Kết nối MySQL
│   ├── functions.php       # Các hàm tiện ích
│   ├── auth_middleware.php # Middleware kiểm tra quyền
│
├── public/                 # Giao diện người dùng
│   ├── css/
│   ├── js/
│   ├── images/
│   ├── index.html          # Trang chủ
│   ├── products.html       # Danh sách sản phẩm
│   ├── product_detail.html # Chi tiết sản phẩm
│   ├── cart.html           # Giỏ hàng
│   ├── checkout.html       # Thanh toán
│   ├── login.html          # Đăng nhập
│   ├── register.html       # Đăng ký
│   └── ...                 # Các trang khác
│
└── .htaccess               # Cấu hình rewrite URL

5. Hướng dẫn cài đặt và chạy dự án

1. Cài đặt XAMPP hoặc WAMP (có Apache và MySQL).
2. Giải nén hoặc sao chép thư mục `project-dongho` vào `htdocs`.
   → Ví dụ: C:\xampp\htdocs\project-dongho
3. Mở XAMPP Control Panel, bật Apache và MySQL.
4. Truy cập http://localhost/phpmyadmin để tạo cơ sở dữ liệu `watch_shop_db`.
5. Mở file `includes/config.php` và cấu hình:

<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'watch_shop_db');
?>

6. Mở trình duyệt truy cập:
   - Giao diện người dùng: http://localhost/project-dongho/public/index.html
   - Trang quản trị: http://localhost/project-dongho/admin/index.php

6. Cấu trúc cơ sở dữ liệu (Database)

Dưới đây là danh sách các bảng chính và mô tả chức năng:

• users: Lưu thông tin tài khoản người dùng và quản trị viên.
• products: Lưu thông tin sản phẩm: tên, giá, mô tả, hình ảnh, số lượng tồn.
• categories: Lưu danh mục sản phẩm: Đồng hồ nam, nữ, cặp, trẻ em,...
• brands: Lưu thương hiệu: Casio, Citizen, Rolex,...
• orders: Lưu thông tin đơn hàng: tổng tiền, trạng thái, địa chỉ giao hàng.
• order_items: Chi tiết sản phẩm trong đơn hàng: mã sản phẩm, số lượng, giá.
7. Cách đóng góp (Contributing)

1. Fork repository này về tài khoản GitHub của bạn.
2. Tạo nhánh mới: git checkout -b feature/TenTinhNangMoi
3. Commit thay đổi: git commit -m "Add new feature"
4. Push lên GitHub: git push origin feature/TenTinhNangMoi
5. Tạo Pull Request để gửi đóng góp.


