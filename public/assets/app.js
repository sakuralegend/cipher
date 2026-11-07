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
        document.dispatchEvent(new Event('doi-giao-dien'));
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
        // Ô ngày phải sau ô khác: <input data-sau="id của ô ngày trước">.
        // Dạng ngày của trình duyệt so sánh chuỗi đúng thứ tự thời gian.
        form.querySelectorAll('[data-sau]').forEach(function (o) {
            var truoc = document.getElementById(o.dataset.sau);
            o.setCustomValidity(truoc.value && o.value && o.value <= truoc.value ? 'khong-sau' : '');
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

// Nút in: <button data-in>.
document.querySelectorAll('[data-in]').forEach(function (nut) {
    nut.addEventListener('click', function () {
        window.print();
    });
});

// Biểu đồ: <canvas data-bieu-do='{"huong":"ngang"|"doc","nhan":[],"so":[],"mau":[]}'>. Chỉ trang Thống kê
// nạp Chart.js. "mau" là tên màu trạng thái cho từng cột; không có thì mọi cột cùng một màu.
// Số liệu đầy đủ nằm ở bảng ngay dưới biểu đồ, nên giá trị không phụ thuộc vào rê chuột.
(function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    // Màu riêng cho từng giao diện, đã kiểm tra độ phân biệt trên nền thẻ sáng và tối.
    var BANG_MAU = {
        light: {chinh: '#2a78d6', chu: '#52514e', luoi: '#e1e0d9', truc: '#c3c2b7'},
        dark: {chinh: '#3987e5', chu: '#c3c2b7', luoi: '#383838', truc: '#4a4a4a'},
    };
    var MAU_TRANG_THAI = {red: '#d03b3b', yellow: '#fab219', green: '#0ca30c', secondary: '#898781'};
    var cacBieuDo = [];
    Chart.defaults.font.family = '"Inter", "Segoe UI", system-ui, sans-serif';

    function ve() {
        var ten = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
        var mau = BANG_MAU[ten];
        cacBieuDo.forEach(function (bieuDo) {
            bieuDo.destroy();
        });
        cacBieuDo = [];

        document.querySelectorAll('canvas[data-bieu-do]').forEach(function (canvas) {
            var duLieu = JSON.parse(canvas.dataset.bieuDo);
            var ngang = duLieu.huong === 'ngang';
            var trucSo = {
                beginAtZero: true,
                ticks: {precision: 0, color: mau.chu},
                grid: {color: mau.luoi},
                border: {display: false},
            };
            var trucNhan = {
                ticks: {color: mau.chu},
                grid: {display: false},
                border: {color: mau.truc},
            };
            cacBieuDo.push(new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: duLieu.nhan,
                    datasets: [{
                        data: duLieu.so,
                        backgroundColor: duLieu.mau
                            ? duLieu.mau.map(function (tenMau) { return MAU_TRANG_THAI[tenMau] || mau.chinh; })
                            : mau.chinh,
                        maxBarThickness: 24,
                        borderRadius: 4, // Chỉ bo đầu cột; phía đường gốc giữ vuông.
                    }],
                },
                options: {
                    indexAxis: ngang ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    // Rê chuột vào bất cứ đâu trên hàng cột là hiện số, không cần trúng đúng cột.
                    interaction: {mode: 'nearest', axis: ngang ? 'y' : 'x', intersect: false},
                    plugins: {
                        legend: {display: false},
                        tooltip: {
                            displayColors: false,
                            callbacks: {
                                label: function (muc) {
                                    return muc.formattedValue + ' chứng chỉ';
                                },
                            },
                        },
                    },
                    scales: ngang ? {x: trucSo, y: trucNhan} : {x: trucNhan, y: trucSo},
                },
            }));
        });
    }

    ve();
    document.addEventListener('doi-giao-dien', ve);
    // Canvas không tự vẽ lại khi font tải xong, nên vẽ lại một lần để chữ trên trục dùng đúng Inter.
    if (document.fonts) {
        document.fonts.ready.then(ve);
    }
})();
