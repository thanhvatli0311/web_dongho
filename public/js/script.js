document.addEventListener('DOMContentLoaded', () => {
    // Các hàm và biến chung cho toàn bộ website
    console.log('Website scripts loaded.');

    // Hàm format tiền tệ Việt Nam
    window.formatCurrency = (amount) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    };

    // Hàm hiển thị thông báo toast/alert đơn giản
    window.showToast = (message, type = 'success') => {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            const newContainer = document.createElement('div');
            newContainer.id = 'toast-container';
            document.body.appendChild(newContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;

        document.getElementById('toast-container').appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000); // Tự động biến mất sau 3 giây
    };

    // Tạo toast container nếu chưa có
    if (!document.getElementById('toast-container')) {
        const newContainer = document.createElement('div');
        newContainer.id = 'toast-container';
        newContainer.style.position = 'fixed';
        newContainer.style.bottom = '20px';
        newContainer.style.right = '20px';
        newContainer.style.zIndex = '1000';
        newContainer.style.display = 'flex';
        newContainer.style.flexDirection = 'column';
        newContainer.style.gap = '10px';
        document.body.appendChild(newContainer);
    }
});