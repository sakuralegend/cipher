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

// Hỏi lại trước các thao tác khó hoàn tác, ví dụ xoá: <form data-xac-nhan="Câu hỏi">.
document.querySelectorAll('form[data-xac-nhan]').forEach(function (form) {
    form.addEventListener('submit', function (suKien) {
        if (!window.confirm(form.dataset.xacNhan)) {
            suKien.preventDefault();
        }
    });
});

// Kiểm tra form ngay trên trình duyệt để báo lỗi nhanh: <form data-kiem-tra novalidate>.
// Quy tắc lấy từ thuộc tính HTML (required, minlength, pattern). Đây chỉ là tiện ích cho người
// dùng; lớp có tác dụng thật là PHP, vì JavaScript có thể bị bỏ qua.
document.querySelectorAll('form[data-kiem-tra]').forEach(function (form) {
    function kiemTraNhapLai() {
        // Ô nhập lại: <input data-giong="id của ô cần giống">.
        form.querySelectorAll('[data-giong]').forEach(function (o) {
            var goc = document.getElementById(o.dataset.giong);
            o.setCustomValidity(o.value === goc.value ? '' : 'khong-giong');
        });
    }

    form.addEventListener('input', function (suKien) {
        kiemTraNhapLai();
        // Người dùng đang sửa ô bị PHP báo lỗi thì bỏ viền đỏ của ô đó.
        suKien.target.classList.remove('is-invalid');
    });

    form.addEventListener('submit', function (suKien) {
        kiemTraNhapLai();
        if (!form.checkValidity()) {
            suKien.preventDefault();
            // Class của Bootstrap: từ lúc này ô sai hiện viền đỏ và dòng .invalid-feedback.
            form.classList.add('was-validated');
        }
    });
});
