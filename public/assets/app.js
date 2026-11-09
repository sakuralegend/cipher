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
        // Ô gõ có gợi ý (oGoiY trong khoi_dong.php): tên phải đúng một dòng trong danh sách, không phân biệt hoa thường.
        form.querySelectorAll('[data-trong-danh-sach]').forEach(function (o) {
            var go = o.value.trim().toLowerCase();
            var co = Array.prototype.some.call(o.list ? o.list.options : [], function (muc) {
                return muc.value.toLowerCase() === go;
            });
            o.setCustomValidity(go === '' || co ? '' : 'khong-co');
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

// Form chạy lâu (Nhập hàng loạt đọc tới 50 máy chủ): <form data-dang-cho="Đang đọc…">. Bấm gửi thì khoá nút và
// đổi chữ, để người dùng biết trang đang làm và không bấm lần hai.
document.querySelectorAll('form[data-dang-cho]').forEach(function (form) {
    form.addEventListener('submit', function (suKien) {
        var nut = suKien.submitter;
        if (nut) {
            // Nút bị khoá thì trình duyệt không gửi tên/giá trị của nó: gửi thay bằng một ô ẩn.
            var an = document.createElement('input');
            an.type = 'hidden';
            an.name = nut.name;
            an.value = nut.value;
            form.appendChild(an);
            window.setTimeout(function () {
                nut.disabled = true;
                nut.textContent = form.dataset.dangCho;
            }, 0);
        }
    });
});

// Nút in: <button data-in>.
document.querySelectorAll('[data-in]').forEach(function (nut) {
    nut.addEventListener('click', function () {
        window.print();
    });
});

// Nút con mắt trong ô mật khẩu: <button data-hien-mat-khau aria-controls="id ô" hidden>. Nút để ẩn sẵn
// trong HTML và chỉ hiện khi có JavaScript, để không có nút bấm mà không làm gì.
document.querySelectorAll('[data-hien-mat-khau]').forEach(function (nut) {
    var o = document.getElementById(nut.getAttribute('aria-controls'));
    nut.hidden = false;
    nut.addEventListener('click', function () {
        var dangHien = o.type === 'text';
        o.type = dangHien ? 'password' : 'text';
        nut.setAttribute('aria-pressed', String(!dangHien));
        nut.setAttribute('aria-label', dangHien ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
        o.focus();
    });
});

// Biểu đồ: <canvas data-bieu-do='{"huong":"ngang"|"doc","nhan":[],"so":[],"mau":[],"tran":số}'>. Chỉ trang có
// biểu đồ (Thống kê, Dashboard) nạp Chart.js. "mau" là tên màu trạng thái cho từng cột; không có thì mọi cột cùng
// một màu. "tran" là trần trục số tính trên toàn bộ dữ liệu: lọc thì cột ngắn lại trên cùng một thước.
// Dùng suggestedMax chứ không dùng max: Chart.js vẫn làm tròn lên mốc đẹp (22 thành 25) như lúc chưa lọc.
// Số liệu đầy đủ nằm ở bảng ngay dưới biểu đồ, nên giá trị không phụ thuộc vào rê chuột.
(function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    // Màu riêng cho từng giao diện. Cột chính lấy trong dải cyan sang xanh dương của landing (08/11/2026);
    // lưới và trục ngả xanh theo nền navy của giao diện tối.
    var BANG_MAU = {
        light: {chinh: '#2f6fe4', chu: '#59636e', luoi: '#e3e8f0', truc: '#c5cdd9'},
        dark: {chinh: '#38bdf8', chu: '#9fb0c8', luoi: '#1c2840', truc: '#2a3a58'},
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
                suggestedMax: duLieu.tran,
                ticks: {precision: 0, color: mau.chu},
                grid: {color: mau.luoi},
                border: {display: false},
            };
            var trucNhan = {
                ticks: {
                    color: mau.chu,
                    // Nhãn dài (tên hãng lạ) bị Chart.js cắt cụt ở mép khung: rút gọn kèm "…". Tên đủ ở bảng
                    // ngay dưới biểu đồ và trong ô hiện khi rê chuột.
                    callback: function (giaTri) {
                        var nhan = String(this.getLabelForValue(giaTri));
                        return nhan.length > 24 ? nhan.slice(0, 23) + '…' : nhan;
                    },
                },
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

// Hàm phụ cho các hiệu ứng bên dưới.
function chan(giaTri, thap, cao) {
    return Math.min(cao, Math.max(thap, giaTri));
}

// Từ 0 đến 1, chậm ở hai đầu, để chuyển cảnh không bắt đầu và dừng đột ngột.
function muotMa(x) {
    var t = chan(x, 0, 1);
    return t * t * (3 - 2 * t);
}

// Nội suy trên bảng [[x, y], ...]; x tăng hoặc giảm đều dùng được.
function noiSuy(bang, x) {
    for (var i = 0; i < bang.length - 1; i++) {
        var a = bang[i];
        var b = bang[i + 1];
        if ((x - a[0]) * (x - b[0]) <= 0) {
            return a[1] + (b[1] - a[1]) * (a[0] === b[0] ? 0 : (x - a[0]) / (b[0] - a[0]));
        }
    }
    return bang[bang.length - 1][1];
}

// Phim thẻ chứng chỉ (QD-23), dùng chung cho landing và trang đăng nhập. Cả "bộ phim" là một hàm veKhung(p),
// p từ 0 đến 1. goc là phần tử bọc thẻ và mang data-ld-muc, để CSS đổi màu trạng thái cho cả quầng sáng.
// Không có JavaScript hoặc máy bật giảm chuyển động thì giữ nguyên khung tĩnh trong views/the_chung_chi.php.
function taoPhimThe(goc) {
    var the = goc.querySelector('[data-ld-the]');
    var oSo = goc.querySelector('[data-ld-so]');
    var oNhan = goc.querySelector('[data-ld-nhan]');
    var ray = goc.querySelector('[data-ld-ray]');
    // Câu chú thích dưới thẻ chỉ landing có; trang đăng nhập thì danh sách này rỗng.
    var cacChuong = goc.querySelectorAll('[data-ld-chuong] li');
    var cacDong = goc.querySelectorAll('[data-ld-dong]');

    // Lịch của phim: tới p nào thì còn bao nhiêu ngày. Từ 7 ngày phim dừng một nhịp rồi mới gia hạn,
    // để người xem kịp đọc trạng thái khẩn cấp nhất.
    var LICH = [[0, 90], [0.22, 30], [0.42, 15], [0.6, 7], [0.74, 7]];
    var P_GIA_HAN = 0.76;
    var SO_NGAY_MOI = 365;
    // Vị trí trên thanh thời gian ứng với số ngày, khớp các vạch 30, 15, 7 trong views/the_chung_chi.php.
    var VI_TRI_RAY = [[90, 0], [30, 0.4], [15, 0.62], [7, 0.84], [0, 1]];

    // Trạng thái đang hiện, để chỉ ghi vào trang khi thật sự đổi.
    var hienTai = {so: null, muc: null, chuong: null};

    function veKhung(p) {
        var daGiaHan = p >= P_GIA_HAN;
        var soNgay;
        var viTri;
        if (daGiaHan) {
            // Số chạy nhanh lên 365 và kim thanh thời gian lùi về đầu.
            var diTiep = chan((p - P_GIA_HAN) / 0.07, 0, 1);
            var diem = 1 - Math.pow(1 - diTiep, 3);
            soNgay = Math.round(7 + (SO_NGAY_MOI - 7) * diem);
            viTri = 0.84 * (1 - diem);
        } else {
            soNgay = Math.round(noiSuy(LICH, Math.min(p, 0.74)));
            viTri = noiSuy(VI_TRI_RAY, soNgay);
        }

        var muc = 'xanh';
        var nhan = 'Còn hiệu lực';
        var chuong = 0;
        if (daGiaHan) {
            nhan = 'Đã gia hạn';
            chuong = 4;
        } else if (soNgay <= 7) {
            muc = 'do';
            nhan = 'Mốc 7 ngày';
            chuong = 3;
        } else if (soNgay <= 15) {
            muc = 'cam';
            nhan = 'Mốc 15 ngày';
            chuong = 2;
        } else if (soNgay <= 30) {
            muc = 'vang';
            nhan = 'Mốc 30 ngày';
            chuong = 1;
        }

        if (soNgay !== hienTai.so) {
            oSo.textContent = soNgay;
            hienTai.so = soNgay;
        }
        if (chuong !== hienTai.chuong) {
            var banDau = hienTai.chuong === null;
            goc.setAttribute('data-ld-muc', muc);
            oNhan.textContent = nhan;
            cacChuong.forEach(function (cau, i) {
                cau.classList.toggle('ld-bat', i === chuong);
            });
            cacDong.forEach(function (dong) {
                var moc = dong.getAttribute('data-ld-dong');
                dong.classList.toggle('ld-bat', moc === 'gia-han' ? daGiaHan : soNgay <= Number(moc) || daGiaHan);
            });
            the.classList.toggle('ld-vua-gia-han', daGiaHan);
            // Số "nảy" một nhịp mỗi lần chạm mốc, để mắt bắt được lúc trạng thái đổi.
            if (!banDau && oSo.animate) {
                oSo.animate([{transform: 'scale(1.12)'}, {transform: 'scale(1)'}], {duration: 350, easing: 'ease-out'});
            }
            hienTai.chuong = chuong;
        }
        ray.style.setProperty('--ld-ray', viTri.toFixed(4));
    }

    // Tự chạy khoảng 18 giây một vòng, và chỉ vẽ khi thẻ đang nằm trong màn hình.
    var THOI_LUONG_TU_CHAY = 18000;
    var dangThay = true;
    var mocTuChay = 0;
    var khungTuChay = null;
    new IntersectionObserver(function (muc) {
        dangThay = muc[0].isIntersecting;
    }).observe(goc);

    function tuChay(gio) {
        if (dangThay) {
            veKhung((((gio - mocTuChay) % THOI_LUONG_TU_CHAY) + THOI_LUONG_TU_CHAY) % THOI_LUONG_TU_CHAY / THOI_LUONG_TU_CHAY);
        }
        khungTuChay = requestAnimationFrame(tuChay);
    }

    // Bắt đầu tự chạy từ vị trí pBatDau của phim.
    function batTuChay(pBatDau) {
        cancelAnimationFrame(khungTuChay);
        mocTuChay = performance.now() - pBatDau * THOI_LUONG_TU_CHAY;
        khungTuChay = requestAnimationFrame(tuChay);
    }

    function dungTuChay() {
        cancelAnimationFrame(khungTuChay);
    }

    // Đợi hai khung hình: trình duyệt phải vẽ xong khung đầu rồi mới bật hiệu ứng đổi màu, nếu không
    // trang vừa mở đã chạy hiệu ứng từ khung tĩnh (đỏ, 7 ngày) sang đầu phim (xanh, 90 ngày).
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            goc.classList.add('ld-da-chay');
        });
    });

    return {veKhung: veKhung, batTuChay: batTuChay, dungTuChay: dungTuChay};
}

// Landing: màn hình rộng lấy p của phim thẻ từ độ cuộn ở lần xem đầu tiên, sau đó và trên màn hình hẹp
// thì phim tự chạy lặp lại.
(function () {
    var sanKhau = document.querySelector('[data-ld-san-khau]');
    if (!sanKhau) {
        return;
    }

    // Thanh trên cùng: qua khỏi hero thì theo giao diện của trang, tới dải cuối thì lại tối để hoà vào nền.
    // Phần này chạy cả khi giảm chuyển động.
    var thanhTren = document.querySelector('[data-ld-thanh-tren]');
    var daiCuoi = document.querySelector('[data-ld-dai-cuoi]');
    function capNhatThanhTren() {
        var caoThanh = thanhTren.offsetHeight;
        var daQua = sanKhau.getBoundingClientRect().bottom <= caoThanh
            && daiCuoi.getBoundingClientRect().top > caoThanh;
        thanhTren.classList.toggle('ld-qua-hero', daQua);
        if (daQua) {
            thanhTren.removeAttribute('data-bs-theme');
        } else {
            thanhTren.setAttribute('data-bs-theme', 'dark');
        }
    }
    window.addEventListener('scroll', capNhatThanhTren, {passive: true});
    capNhatThanhTren();

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    var phim = taoPhimThe(sanKhau);
    var cotChu = sanKhau.querySelector('.ld-cot-chu');
    var chongThe = sanKhau.querySelector('[data-ld-chong-the]');

    var cheDoCuon = window.matchMedia('(min-width: 992px) and (min-height: 640px)');
    var dangChoVe = false;
    // Phim theo tay cuộn chỉ chạy một lần trong mỗi lần mở trang: cuộn ngược qua đoạn giữ màn hình dài rất vô lý.
    // Cố ý không lưu lại: tải lại trang là phim chạy lại từ đầu (user chốt 07/11/2026).
    var daXemHet = false;
    // Tải lại luôn về đầu trang. Để trình duyệt tự trả về vị trí cũ thì trang sẽ mở ở giữa phim hoặc qua hết phim.
    history.scrollRestoration = 'manual';
    // Tới đây cột chữ đã trở lại hẳn, nên bố cục lúc này trùng với bố cục tĩnh của hero.
    var P_XEM_HET = 0.97;
    // Số ngày đã lên hẳn 365 từ mốc này: bắt đầu tự chạy từ đây thì thẻ giữ nguyên hình đang hiện vài giây
    // rồi mới quay về đầu vòng, không bị nhảy số ngay lúc chuyển.
    var P_GIA_HAN_XONG = 0.83;

    // Xoá các giá trị bố cục do phim đặt, trả hero về bố cục tĩnh trong CSS.
    function xoaBoCucPhim() {
        chongThe.style.removeProperty('--ld-nghieng');
        chongThe.style.removeProperty('--ld-troi');
        sanKhau.style.removeProperty('--ld-tap-trung');
        sanKhau.style.removeProperty('--ld-goi-y');
        cotChu.classList.remove('ld-da-an');
    }

    // Bỏ đoạn giữ màn hình cho tới khi tải lại trang. Trang ngắn đi bao nhiêu thì lùi vị trí cuộn bấy nhiêu,
    // để mọi thứ đứng yên trên màn hình lúc chuyển; đang ở đầu trang thì trình duyệt tự dừng ở 0, vẫn khớp.
    function chuyenSangVideo() {
        // Phải đọc vị trí cuộn trước khi trang ngắn lại: đọc sau thì trình duyệt đã kẹp nó vào độ dài mới,
        // người đang lướt nhanh qua hero sẽ bị kéo về đầu trang.
        var cuonCu = window.scrollY;
        var caoCu = sanKhau.offsetHeight;
        daXemHet = true;
        sanKhau.classList.add('ld-da-xem');
        xoaBoCucPhim();
        window.scrollTo({top: cuonCu - (caoCu - sanKhau.offsetHeight), behavior: 'instant'});
        phim.batTuChay(P_GIA_HAN_XONG);
    }

    function veTheoCuon() {
        var khung = sanKhau.getBoundingClientRect();
        var p = chan(-khung.top / (khung.height - window.innerHeight), 0, 1);
        if (p >= P_XEM_HET) {
            chuyenSangVideo();
            return;
        }
        phim.veKhung(p);
        // Đầu phim thẻ hơi nghiêng rồi phẳng dần; hai thẻ phía sau tách ra theo độ cuộn.
        var phang = chan(p / 0.14, 0, 1);
        chongThe.style.setProperty('--ld-nghieng', (1 - phang).toFixed(3));
        chongThe.style.setProperty('--ld-troi', (1 + p * 0.6).toFixed(3));
        sanKhau.style.setProperty('--ld-goi-y', chan(1 - p / 0.04, 0, 1).toFixed(2));
        // Chuyển cảnh: thẻ lên giữa màn hình trong lúc phim chạy, gia hạn xong thì cột chữ và nút trở lại.
        var tapTrung = muotMa((p - 0.03) / 0.13) * (1 - muotMa((p - 0.87) / 0.1));
        sanKhau.style.setProperty('--ld-tap-trung', tapTrung.toFixed(3));
        // Cột chữ đã mờ hẳn thì ẩn thật, để phím Tab không nhảy vào nút không nhìn thấy.
        cotChu.classList.toggle('ld-da-an', tapTrung > 0.99);
    }

    // Các mục dưới hero, chạy theo độ cuộn trên mọi cỡ màn hình.
    var sanAnh = document.querySelector('[data-ld-san-anh]');
    var khoiBuoc = document.querySelector('[data-ld-cac-buoc]');
    var cacBuoc = khoiBuoc.querySelectorAll('.ld-buoc');

    function veCacMuc() {
        var caoManHinh = window.innerHeight;
        // Khung ảnh Dashboard dựng thẳng dần trong lúc đi lên.
        var moAnh = muotMa((caoManHinh - sanAnh.getBoundingClientRect().top) / (caoManHinh * 0.7));
        sanAnh.style.setProperty('--ld-mo', moAnh.toFixed(3));

        // Đường nối ba bước: tiến độ 0 đến 1, mỗi đoạn vẽ trong một nửa. Cuộn hết trang mà chưa đủ
        // (màn hình rất cao) thì vẫn coi là xong, để bước cuối không bao giờ bị tối.
        var khung = khoiBuoc.getBoundingClientRect();
        var tienDo = chan((caoManHinh * 0.8 - khung.top) / (khung.height + caoManHinh * 0.2), 0, 1);
        if (caoManHinh + window.scrollY >= document.documentElement.scrollHeight - 2) {
            tienDo = 1;
        }
        cacBuoc.forEach(function (buoc, i) {
            buoc.style.setProperty('--ld-doan', chan(tienDo * 2 - i, 0, 1).toFixed(3));
            buoc.classList.toggle('ld-chua-toi', i === 0 ? tienDo <= 0 : tienDo * 2 < i);
        });

        // Quầng sáng dải cuối lớn dần khi dải đi lên.
        var lan = chan((caoManHinh - daiCuoi.getBoundingClientRect().top) / (caoManHinh * 0.8), 0, 1);
        daiCuoi.style.setProperty('--ld-lan', lan.toFixed(3));
    }

    function veKhiCuon() {
        dangChoVe = false;
        if (cheDoCuon.matches && !daXemHet) {
            veTheoCuon();
        }
        veCacMuc();
    }

    function khiCuon() {
        if (!dangChoVe) {
            dangChoVe = true;
            requestAnimationFrame(veKhiCuon);
        }
    }

    // Hiện dần các phần tử .ld-hien khi cuộn tới, mỗi phần tử một lần. Phần tử đứng cùng hàng hiện lần lượt.
    var quanSatHien = new IntersectionObserver(function (cacMuc) {
        cacMuc.forEach(function (muc) {
            if (muc.isIntersecting) {
                muc.target.classList.add('ld-da-hien');
                quanSatHien.unobserve(muc.target);
            }
        });
    }, {rootMargin: '0px 0px -8% 0px', threshold: 0.1});

    document.querySelectorAll('.ld-hien').forEach(function (phan) {
        var viTri = Array.prototype.indexOf.call(phan.parentElement.children, phan);
        phan.style.setProperty('--ld-tre', (viTri * 0.12) + 's');
        phan.classList.add('ld-cho-hien');
        quanSatHien.observe(phan);
    });

    // Màn hình hẹp tự chạy phim (không giữ lại khi cuộn vì dễ giật, thanh địa chỉ co giãn làm đổi chiều cao).
    function chonCheDo() {
        phim.dungTuChay();
        sanKhau.classList.toggle('ld-theo-cuon', cheDoCuon.matches);
        sanKhau.classList.toggle('ld-da-xem', daXemHet);
        if (cheDoCuon.matches && !daXemHet) {
            veTheoCuon();
        } else {
            xoaBoCucPhim();
            phim.veKhung(0);
            phim.batTuChay(0);
        }
    }

    window.addEventListener('scroll', khiCuon, {passive: true});
    window.addEventListener('resize', khiCuon);
    cheDoCuon.addEventListener('change', chonCheDo);
    chonCheDo();
    veCacMuc();
})();

// Trang đăng nhập: thẻ tự chạy vòng đời như landing trên màn hình hẹp. Màn hẹp ẩn thẻ, phim tự ngừng vẽ.
(function () {
    var goc = document.querySelector('[data-dn-phim]');
    if (!goc || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }
    var phim = taoPhimThe(goc);
    phim.veKhung(0);
    phim.batTuChay(0);
})();
