// Nút đổi sáng/tối. tabler-theme.min.js đọc cùng khoá localStorage này khi tải trang,
// nên lựa chọn được giữ khi chuyển trang và khi mở lại trình duyệt.
document.querySelectorAll('[data-doi-giao-dien]').forEach(function (nut) {
    nut.addEventListener('click', function () {
        var html = document.documentElement;
        var moi = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', moi);
        try {
            localStorage.setItem('tabler-theme', moi);
        } catch (loi) {
            // Trình duyệt chặn lưu trữ thì giao diện vẫn đổi được trong trang hiện tại.
        }
    });
});
