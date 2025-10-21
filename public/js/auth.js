document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    const messageDiv = document.getElementById('message'); // Lấy div hiển thị thông báo

    if (registerForm) {
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Ngăn chặn form gửi đi theo cách mặc định

            // Lấy dữ liệu từ các trường input
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const fullname = document.getElementById('fullname').value.trim();
            const address = document.getElementById('address').value.trim();
            const phone_number = document.getElementById('phone_number').value.trim();

            // Tạo đối tượng dữ liệu để gửi đi
            const userData = {
                username: username,
                email: email,
                password: password,
                fullname: fullname,
                address: address,
                phone_number: phone_number
            };

            try {
                // Gửi dữ liệu đến API đăng ký
                const response = await fetch('http://localhost/project-dongho/api/auth.php?action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(userData)
                });

                const result = await response.json();

                // Hiển thị thông báo kết quả
                if (result.success) {
                    showMessage('success', result.message);
                    registerForm.reset(); // Xóa form sau khi đăng ký thành công
                    // Có thể chuyển hướng người dùng đến trang đăng nhập sau vài giây
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 3000);
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                console.error('Error during registration:', error);
                showMessage('error', 'Lỗi kết nối server. Vui lòng thử lại sau.');
            }
        });
    }
        // Thêm logic cho form Đăng Nhập
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Ngăn chặn form gửi đi theo cách mặc định

            const identifier = document.getElementById('identifier').value.trim(); // Có thể là username hoặc email
            const password = document.getElementById('password').value.trim();

            const loginData = {
                identifier: identifier,
                password: password
            };

            try {
                const response = await fetch('http://localhost/project-dongho/api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(loginData)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('success', result.message);
                    loginForm.reset(); // Xóa form
                    // Chuyển hướng người dùng sau khi đăng nhập thành công
                    // Tùy theo vai trò, có thể chuyển hướng đến trang admin hoặc trang chủ
                    setTimeout(() => {
                        if (result.user_role === 'admin') {
                            window.location.href = '../admin/index.php'; // Chuyển hướng đến dashboard admin
                        } else {
                            window.location.href = 'index.html'; // Chuyển hướng đến trang chủ
                        }
                    }, 1500); // 1.5 giây
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                console.error('Error during login:', error);
                showMessage('error', 'Lỗi kết nối server. Vui lòng thử lại sau.');
            }
        });
    }

    // Hàm hiển thị thông báo
    function showMessage(type, text) {
        messageDiv.textContent = text;
        messageDiv.className = `message ${type}`; // Thêm class 'success' hoặc 'error'
        messageDiv.style.display = 'block';
        // Tự động ẩn thông báo sau 5 giây (tùy chọn)
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
});