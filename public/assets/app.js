// Menu quản trị hai tầng (dau_trang.php): nút data-qt-doi="thanh" nở/thu thanh khu, "phu" thu/mở bảng phụ.
// Trạng thái ghi lên thẻ html (lớp qt-no-thanh, qt-thu-phu) và nhớ trong localStorage; đoạn script nhỏ trong <head>
// đọc lại trước khi vẽ trang, nên chuyển trang menu không nhảy.
(function () {
    var LOP = {thanh: 'qt-no-thanh', phu: 'qt-thu-phu'};
    var html = document.documentElement;

    function capNhatNut() {
        document.querySelectorAll('[data-qt-doi]').forEach(function (nut) {
            var lop = LOP[nut.dataset.qtDoi];
            // "Đang mở rộng": thanh khu khi có lớp nở, bảng phụ khi không có lớp thu.
            var moRong = nut.dataset.qtDoi === 'thanh' ? html.classList.contains(lop) : !html.classList.contains(lop);
            nut.setAttribute('aria-expanded', String(moRong));
        });
    }

    document.querySelectorAll('[data-qt-doi]').forEach(function (nut) {
        nut.addEventListener('click', function () {
            var lop = LOP[nut.dataset.qtDoi];
            var co = html.classList.toggle(lop);
            try {
                localStorage.setItem(lop, co ? '1' : '0');
            } catch (loi) {
                // Trình duyệt chặn lưu trữ thì menu vẫn đổi được trong trang hiện tại.
            }
            capNhatNut();
        });
    });
    capNhatNut();
})();

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

// Ô ngày (type date, datetime-local) nhận chữ dán vào, ví dụ chép từ file Excel dữ liệu mẫu (user 14/11/2026: chọn ngày bằng
// lịch rất lâu). Đọc được: ngày/tháng/năm với dấu / - . (24/11/2026, 24-11-2026), năm-tháng-ngày (2026-11-24), có hoặc
// không có giờ:phút[:giây]; thiếu giờ thì lấy 00:00:00. Đọc không được thì để trình duyệt xử lý như thường.
// Trả về giá trị đúng dạng của ô ('2026-11-24' hoặc '2026-11-24T00:00:00'), hoặc null.
function docNgayDan(chu, coGio) {
    var s = String(chu).trim();
    var m = s.match(/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})(?:[ T,]+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/);
    var ngay, thang, nam;
    if (m) {
        ngay = +m[1];
        thang = +m[2];
        nam = +m[3];
    } else {
        m = s.match(/^(\d{4})[\/.\-](\d{1,2})[\/.\-](\d{1,2})(?:[ T,]+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/);
        if (!m) {
            return null;
        }
        nam = +m[1];
        thang = +m[2];
        ngay = +m[3];
    }
    var gio = +(m[4] || 0);
    var phut = +(m[5] || 0);
    var giay = +(m[6] || 0);
    // Date tự đổi 31/02 thành 03/03: so lại từng phần để bắt ngày không có thật.
    var d = new Date(nam, thang - 1, ngay);
    if (d.getFullYear() !== nam || d.getMonth() !== thang - 1 || d.getDate() !== ngay || gio > 23 || phut > 59 || giay > 59) {
        return null;
    }
    function hai(so) {
        return (so < 10 ? '0' : '') + so;
    }
    var giaTri = nam + '-' + hai(thang) + '-' + hai(ngay);
    return coGio ? giaTri + 'T' + hai(gio) + ':' + hai(phut) + ':' + hai(giay) : giaTri;
}

document.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(function (o) {
    o.addEventListener('paste', function (suKien) {
        var giaTri = docNgayDan((suKien.clipboardData || window.clipboardData).getData('text'), o.type === 'datetime-local');
        if (giaTri === null) {
            return;
        }
        suKien.preventDefault();
        o.value = giaTri;
        // Báo cho phần kiểm tra form (data-sau: hết hạn phải sau bắt đầu) như khi người dùng tự chọn ngày.
        o.dispatchEvent(new Event('input', {bubbles: true}));
        o.dispatchEvent(new Event('change', {bubbles: true}));
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

// Nhập hàng loạt có thanh tiến trình: <form data-nhap-tung-dia-chi> chứa khối .nhap-tien-do (chung_chi_nhap.php).
// Thay vì một yêu cầu đọc cả lượt (trang đứng im tới một phút), gửi bat_dau kèm danh sách, rồi doc_mot từng lần: máy chủ
// tự đọc địa chỉ kế tiếp trong phiên và trả về đã đọc bao nhiêu. Đọc xong gửi form thật với hanh_dong=xem để hiện bảng
// xem trước. Lỗi bất kỳ (mạng, phiên hết hạn) thì gửi form như thường với hanh_dong=doc: đường cũ vẫn đọc được cả lượt.
// Chạy sau khối data-dang-cho ở trên, nên nút đã được khoá và đổi chữ khi tới đây.
document.querySelectorAll('form[data-nhap-tung-dia-chi]').forEach(function (form) {
    var khung = form.querySelector('.nhap-tien-do');
    var thanh = khung.querySelector('.progress-bar');
    var chu = khung.querySelector('[aria-live]');
    var dangChay = false;

    function gui(hanhDong) {
        var duLieu = new FormData(form);
        duLieu.set('hanh_dong', hanhDong);
        return fetch(form.action, {method: 'POST', body: duLieu, credentials: 'same-origin'}).then(function (traLoi) {
            if (!traLoi.ok) {
                throw new Error('HTTP ' + traLoi.status);
            }
            return traLoi.json();
        });
    }

    // Gửi form thật (trang vẽ lại). form.submit() không gọi lại sự kiện submit nên không lặp.
    function guiForm(hanhDong) {
        form.querySelectorAll('input[type="hidden"][name="hanh_dong"]').forEach(function (o) {
            o.remove();
        });
        var an = document.createElement('input');
        an.type = 'hidden';
        an.name = 'hanh_dong';
        an.value = hanhDong;
        form.appendChild(an);
        form.submit();
    }

    function capNhat(daDoc, tong, vuaDoc) {
        var phanTram = tong > 0 ? Math.round(daDoc * 100 / tong) : 0;
        thanh.style.width = phanTram + '%';
        thanh.setAttribute('aria-valuenow', String(phanTram));
        chu.textContent = 'Đã đọc ' + daDoc + '/' + tong + (vuaDoc ? ': ' + vuaDoc : '');
    }

    form.addEventListener('submit', function (suKien) {
        if (dangChay || suKien.defaultPrevented || !window.fetch) {
            return;
        }
        suKien.preventDefault();
        dangChay = true;
        gui('bat_dau').then(function (batDau) {
            // Danh sách sai (trống, quá 50 địa chỉ): để PHP báo lỗi dưới ô như thường.
            if (batDau.loi) {
                guiForm('doc');
                return null;
            }
            khung.hidden = false;
            capNhat(0, batDau.tong, null);
            function docTiep() {
                return gui('doc_mot').then(function (ketQua) {
                    capNhat(ketQua.da_doc, ketQua.tong, ketQua.vua_doc);
                    if (ketQua.da_doc < ketQua.tong && ketQua.vua_doc !== null) {
                        return docTiep();
                    }
                    chu.textContent = 'Đã đọc xong ' + ketQua.da_doc + '/' + ketQua.tong + ', đang dựng bảng xem trước…';
                    guiForm('xem');
                    return null;
                });
            }
            return docTiep();
        }).catch(function () {
            guiForm('doc');
        });
    });
});

// Bảng kéo ngang: gắn lớp con-trai, con-phai lên .table-responsive khi ô đầu, ô cuối của hàng tiêu đề bị che; app.css làm
// mờ mép đó. Theo dõi bằng IntersectionObserver (khung bảng làm gốc) chứ không nghe sự kiện cuộn: landing cấm hiệu ứng
// theo cuộn (test_landing_khong_co_hieu_ung_theo_cuon_phim_the_tu_chay), và cách này cũng tự đúng khi xoay màn hình.
if (window.IntersectionObserver) {
    document.querySelectorAll('.table-responsive').forEach(function (khung) {
        var oTieuDe = khung.querySelectorAll('thead tr:first-child > *');
        if (oTieuDe.length < 2) {
            return;
        }
        var dau = oTieuDe[0];
        var cuoi = oTieuDe[oTieuDe.length - 1];
        var theoDoi = new IntersectionObserver(function (cacMuc) {
            cacMuc.forEach(function (muc) {
                // Ô lọt khung gần trọn (0,98) mới coi là thấy: lệch nửa điểm ảnh do làm tròn không bật mờ mép.
                var biChe = muc.intersectionRatio < 0.98;
                khung.classList.toggle(muc.target === cuoi ? 'con-phai' : 'con-trai', biChe);
            });
        }, {root: khung, threshold: [0.98]});
        theoDoi.observe(dau);
        theoDoi.observe(cuoi);
    });
}

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

    // Web chỉ có giao diện tối (QD-25): cột chính là tím nhấn, chữ trục xám xanh, lưới và trục theo đường kẻ của
    // nền xám xanh khu quản trị. Màu trạng thái là bộ --cc-* trong app.css (theo Better Stack, user 09/11/2026);
    // canvas không đọc được biến CSS nên chép giá trị ở đây, đổi bên kia thì đổi theo.
    var mau = {chinh: '#7c87f7', chu: '#8e99b3', luoi: '#2a3040', truc: '#3a4256'};
    var MAU_TRANG_THAI = {red: '#e0687d', yellow: '#d57b16', green: '#00af79', blue: '#5b9fd6', secondary: '#8e99b3'};
    var cacBieuDo = [];
    Chart.defaults.font.family = '"Inter", "Segoe UI", system-ui, sans-serif';

    function ve() {
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
    // Canvas không tự vẽ lại khi font tải xong, nên vẽ lại một lần để chữ trên trục dùng đúng Inter.
    if (document.fonts) {
        document.fonts.ready.then(ve);
    }
})();

// Hàm phụ cho các hiệu ứng bên dưới.
function chan(giaTri, thap, cao) {
    return Math.min(cao, Math.max(thap, giaTri));
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

// Phim thẻ chứng chỉ (QD-23) ở landing. Cả "bộ phim" là một hàm veKhung(p),
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
    var LICH = [[0, 45], [0.22, 30], [0.42, 15], [0.6, 7], [0.74, 7]];
    var P_GIA_HAN = 0.76;
    var SO_NGAY_MOI = 365;
    // Vị trí trên thanh thời gian: thang tuyến tính từ ngày đầu thang (0) tới hết hạn (1), cùng con số với các vạch
    // 30, 15, 7 mà views/the_chung_chi.php đặt, nên khoảng cách đúng tỉ lệ số ngày.
    var NGAY_DAU_RAY = Number(ray.dataset.ldNgayDau);
    function viTriRay(soNgay) {
        return chan(1 - soNgay / NGAY_DAU_RAY, 0, 1);
    }

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
            viTri = viTriRay(7) * (1 - diem);
        } else {
            soNgay = Math.round(noiSuy(LICH, Math.min(p, 0.74)));
            viTri = viTriRay(soNgay);
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
    new IntersectionObserver(function (muc) {
        dangThay = muc[0].isIntersecting;
    }).observe(goc);

    function tuChay(gio) {
        if (dangThay) {
            veKhung((((gio - mocTuChay) % THOI_LUONG_TU_CHAY) + THOI_LUONG_TU_CHAY) % THOI_LUONG_TU_CHAY / THOI_LUONG_TU_CHAY);
        }
        requestAnimationFrame(tuChay);
    }

    // Vẽ ngay khung đầu phim (xanh, 45 ngày), rồi đợi hai khung hình mới bật hiệu ứng đổi màu: bật sớm thì
    // trang vừa mở sẽ loé từ khung tĩnh trong HTML (đỏ, 7 ngày) sang xanh.
    veKhung(0);
    mocTuChay = performance.now();
    requestAnimationFrame(tuChay);
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            goc.classList.add('ld-da-chay');
        });
    });
}

// Landing: khối có data-ld-phim cho phim thẻ tự chạy lặp như video. Không có hiệu ứng
// theo cuộn (user 09/11/2026: "bỏ hiệu ứng cuộn đi, chỉ giữ lại video thôi"). Máy bật giảm chuyển động thì thẻ đứng yên.
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('[data-ld-phim]').forEach(taoPhimThe);
}
