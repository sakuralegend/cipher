"""Chụp toàn bộ ảnh giao diện cho báo cáo. Chạy: python -I chup_het.py [nhom ...]
Nhóm: cong_khai, xem, nhan_vien, nhap_lieu, doc, nhat_ky. Không ghi nhóm thì chạy hết theo thứ tự.
Nhóm cai_dat (chỉ chạy khi gọi tên): hai ảnh phpMyAdmin của hướng dẫn cài đặt, cần Apache của XAMPP đang bật.
Nhóm landing (chỉ chạy khi gọi tên): chụp lại 9 ảnh public/assets/landing/*.webp; chạy TRƯỚC nhóm nhap_lieu, vì nhóm
đó gia hạn CERT-013 là chứng chỉ của hai ảnh thẻ Chi tiết và Gia hạn."""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from chup import Phien, chup, luu_html

NHOM = sys.argv[1:] or ["cong_khai", "xem", "nhan_vien", "nhap_lieu", "doc", "nhat_ky"]


def cong_khai():
    chup("landing", "/", h=860)
    chup("dang_nhap", "/login.php", h=760)
    khach = Phien()
    html = khach.post("/login.php", {"username": "admin", "password": "sai-mat-khau"})
    chup("dang_nhap_sai", luu_html("dang_nhap_sai", html), h=760)


def xem():
    ad = Phien("admin")
    chup("dashboard", "/admin/index.php", ad, h=900)
    chup("dashboard_dt", "/admin/index.php", ad, h=860, dt=True)
    chup("danh_sach", "/admin/chung_chi.php", ad, h=900)
    chup("danh_sach_loc", "/admin/chung_chi.php?tu_khoa=congty&trang_thai=expiring&xep=an_toan.tang", ad, h=900)
    chup("danh_sach_dt", "/admin/chung_chi.php", ad, h=860, dt=True)
    chup("chi_tiet", "/admin/chung_chi_xem.php?id=10", ad, h=1250)
    chup("chi_tiet_hang_f", "/admin/chung_chi_xem.php?id=5", ad, h=1100)
    chup("chi_tiet_thu_hoi", "/admin/chung_chi_xem.php?id=6", ad, h=900)
    chup("them_thu_cong", "/admin/chung_chi_form.php", ad, h=1000)
    chup("nhap_tu_dong", "/admin/chung_chi_form.php?che_do=tu_dong", ad, h=900)
    chup("gia_han", "/admin/chung_chi_gia_han.php?id=10", ad, h=860)
    chup("canh_bao", "/admin/canh_bao.php", ad, h=900)
    chup("thong_ke", "/admin/thong_ke.php", ad, h=1750)
    chup("ban_in", "/admin/bao_cao_in.php?trang_thai=expiring", ad, h=1000)
    chup("ten_mien", "/admin/ten_mien.php", ad, h=800)
    chup("may_chu", "/admin/may_chu.php", ad, h=800)
    chup("may_chu_form", "/admin/may_chu_form.php", ad, h=760)
    chup("to_chuc_cap", "/admin/to_chuc_cap.php", ad, h=760)
    chup("nguoi_dung", "/admin/nguoi_dung.php", ad, h=620)
    chup("nguoi_dung_form", "/admin/nguoi_dung_form.php", ad, h=820)
    chup("tai_khoan", "/admin/tai_khoan.php", ad, h=900)


def nhan_vien():
    nv = Phien("nhanvien")
    chup("nv_may_chu", "/admin/may_chu.php", nv, h=700)
    chup("nv_403", "/admin/nguoi_dung.php", nv, h=520)
    chup("nv_chi_tiet", "/admin/chung_chi_xem.php?id=10", nv, h=700)


def nhap_lieu():
    ad = Phien("admin")
    # Form thêm có lỗi: PHP báo đúng ô (ngày hết hạn trước ngày cấp, fingerprint sai, tên miền lạ, mã trùng).
    html = ad.post("/admin/chung_chi_form.php", {
        "che_do": "thu_cong", "cert_code": "CERT-001", "subject_cn": "thu.congty.example", "san": "",
        "ten_mien": "khong-co.example", "to_chuc_cap": "DigiCert",
        "valid_from": "2026-11-14T00:00:00", "valid_to": "2026-11-01T00:00:00",
        "key_algorithm": "RSA", "key_size": "2048", "signature_algorithm": "sha256WithRSAEncryption",
        "fingerprint_sha256": "12:34", "serial": "", "hanh_dong": "luu"})
    chup("them_loi", luu_html("them_loi", html), h=1100)
    # Gia hạn sai: hạn mới trước hạn cũ.
    html = ad.post("/admin/chung_chi_gia_han.php?id=10", {"valid_to": "2026-11-10T00:00:00", "note": "", "fingerprint_sha256": "", "serial": ""})
    chup("gia_han_loi", luu_html("gia_han_loi", html), h=860)
    # Gia hạn đúng CERT-013 rồi xem trang chi tiết có lịch sử.
    html = ad.post("/admin/chung_chi_gia_han.php?id=13", {"valid_to": "2027-05-20T00:00:00", "note": "Cấp lại sau cảnh báo mốc 15 ngày",
                                                         "fingerprint_sha256": "", "serial": ""})
    chup("gia_han_xong", luu_html("gia_han_xong", html), h=1250)
    # Xoá tổ chức cấp còn chứng chỉ: báo lý do.
    html = ad.post("/admin/to_chuc_cap.php", {"id": "2", "hanh_dong": "xoa"})
    chup("xoa_bi_chan", luu_html("xoa_bi_chan", html), h=560)


def doc():
    ad = Phien("admin")
    trang = "/admin/chung_chi_form.php?che_do=tu_dong"
    html = ad.post("/admin/chung_chi_form.php", {"che_do": "tu_dong", "dia_chi": "github.com", "cong": "443", "hanh_dong": "doc"}, trang_lay_csrf=trang)
    chup("doc_ket_qua", luu_html("doc_ket_qua", html), h=1500)
    html = ad.post("/admin/chung_chi_form.php", {"che_do": "tu_dong", "dia_chi": "127.0.0.1", "cong": "443", "hanh_dong": "doc"}, trang_lay_csrf=trang)
    chup("doc_noi_bo", luu_html("doc_noi_bo", html), h=560)
    html = ad.post("/admin/chung_chi_form.php", {"che_do": "tu_dong", "dia_chi": "khongtontai.invalid", "cong": "443", "hanh_dong": "doc"}, trang_lay_csrf=trang)
    ds = "github.com\nwww.github.com\nvnexpress.net\nexpired.badssl.com\nkhongtontai.invalid\n10.0.0.5"
    html = ad.post("/admin/chung_chi_nhap.php", {"dia_chi": ds, "cong": "443", "hanh_dong": "doc"}, trang_lay_csrf=trang)
    chup("hang_loat_xem_truoc", luu_html("hang_loat_xem_truoc", html), h=1150)


def nhat_ky():
    ad = Phien("admin")
    # Tạo vài dòng đủ ba mức: thêm rồi xoá một tên miền, đăng nhập sai 5 lần + lần 6 khi đang bị chặn.
    ad.post("/admin/ten_mien_form.php", {"domain_name": "tam.congty.example", "server_id": "1"})
    for _ in range(6):
        Phien().post("/login.php", {"username": "nhanvien", "password": "doan-mo"})
    chup("nhat_ky", "/admin/nhat_ky.php", ad, h=1000)
    chup("nhat_ky_nguy_hiem", "/admin/nhat_ky.php?muc=nguy_hiem", ad, h=620)


def cai_dat():
    """Hai ảnh phpMyAdmin cho Phần VI của báo cáo (Cách 2). Cần Apache của XAMPP đang bật (http://localhost/phpmyadmin).
    Giao diện tiếng Anh (cookie pma_lang=en) cho khớp chữ trong hướng dẫn. Ảnh tạo CSDL vẽ lại trạng thái máy người chấm
    lúc CHƯA có cipher_dev: ẩn các CSDL có sẵn trên máy chụp (AN) khỏi cây bên trái và bảng, sửa dòng Total theo số dòng
    còn lại; không bấm Create, không đụng CSDL nào. Ba ảnh còn lại của phần cài đặt chụp bằng chup_cua_so.ps1:
      - chay_bat: conhost.exe cmd.exe /c "<bản giải nén>\\CHAY_WEB.bat", chờ 17 giây (10 giây của câu hỏi nạp lại), rồi
        -TieuDe "Cipher Cert" -Lop ConsoleWindowClass; đóng cửa sổ là tắt web.
      - xampp_control: chạy C:\\xampp\\apache\\bin\\httpd.exe và C:\\xampp\\xampp-control.exe, -MaTienTrinh <mã của
        xampp-control>; ứng dụng này không theo tỉ lệ màn hình nên ảnh ra có phần thừa, cắt còn 663x433; xong thì tắt cả hai.
      - thu_muc_giai_nen: explorer.exe <bản giải nén>, -TieuDe "cipher" -Lop CabinetWClass -VungCon UIItemsView."""
    from chup import Cdp
    pma = "http://localhost/phpmyadmin/"
    an = ["cipher_dev", "cipher_test", "cipher_baocao", "demo_xampp"]
    js_an = """
    (function (an, anNhom) {
        var n = 0;
        document.querySelectorAll('#pma_navigation_tree li').forEach(function (li) {
            var a = li.querySelector(':scope > a, :scope > a.hover_show_full');
            var ten = a ? a.textContent.trim() : '';
            if (an.indexOf(ten) >= 0 || (anNhom && li.className.indexOf('navGroup') >= 0 && li.textContent.trim().indexOf(anNhom) === 0)) {
                li.style.display = 'none'; n++;
            }
        });
        document.querySelectorAll('table tbody tr').forEach(function (tr) {
            var co = false;
            tr.querySelectorAll('a').forEach(function (a) { if (an.indexOf(a.textContent.trim()) >= 0) co = true; });
            if (co) { tr.style.display = 'none'; n++; }
        });
        return n;
    })(%s, %s)
    """
    js_dien = """
    (function () {
        document.querySelector('#text_create_db').value = 'cipher_dev';
        document.querySelector('select[name=db_collation]').value = 'utf8mb4_unicode_ci';
        var dem = document.getElementById('filter-rows-count'), con = 0;
        if (dem && dem.closest('table')) {
            dem.closest('table').querySelectorAll('tbody tr').forEach(function (tr) { if (tr.style.display !== 'none') con++; });
            dem.textContent = con;
        }
        return con;
    })()
    """
    c = Cdp(w=1280, h=640)
    try:
        c.lenh("Network.enable")
        c.lenh("Network.setCookie", name="pma_lang", value="en", domain="localhost", path="/phpmyadmin/")
        c.mo(pma + "index.php?route=/server/databases", cho=4)
        print("   an", c.js(js_an % (an, '"cipher"')), "muc; con", c.js(js_dien), "CSDL trong bang")
        c.chup("pma_tao_csdl")
        c.mo(pma + "index.php?route=/database/import&db=cipher_dev", cho=4)
        c.js(js_an % ([x for x in an if x != "cipher_dev"], "null"))
        c.chup("pma_import")
    finally:
        c.dong()


def landing():
    """9 ảnh của trang giới thiệu, cùng kích thước $anhGiaoDien trong public/index.php: 'may' 1600x1000 (cửa sổ 1440x900,
    có menu), 'dt' 780x1688 (390x844, hệ số 2), 'the' 1200x900 (chỉ phần nội dung, ẩn menu)."""
    from PIL import Image
    from chup import ANH
    dich = Path(r"d:\Desktop\cipher\public\assets\landing")
    ad = Phien("admin")
    viec = [
        ("dashboard-may", "/admin/index.php", dict(w=1440, h=900, ti_le=2), (1600, 1000)),
        ("can-xu-ly", "/admin/chung_chi.php?trang_thai=expiring", dict(w=1440, h=900, ti_le=2), (1600, 1000)),
        ("dashboard-dt", "/admin/index.php", dict(h=844, dt=True), (780, 1688)),
        ("the-danh-sach", "/admin/chung_chi.php", dict(w=1200, h=900, ti_le=1, an_menu=True), (1200, 900)),
        ("the-canh-bao", "/admin/canh_bao.php", dict(w=1200, h=900, ti_le=1, an_menu=True), (1200, 900)),
        ("the-thong-ke", "/admin/thong_ke.php", dict(w=1200, h=900, ti_le=1, an_menu=True), (1200, 900)),
        ("the-them", "/admin/chung_chi_form.php", dict(w=1200, h=900, ti_le=1, an_menu=True), (1200, 900)),
        ("the-chi-tiet", "/admin/chung_chi_xem.php?id=13", dict(w=1200, h=900, ti_le=1, an_menu=True), (1200, 900)),
        ("the-gia-han", "/admin/chung_chi_gia_han.php?id=13", dict(w=1200, h=900, ti_le=1, an_menu=True), (1200, 900)),
    ]
    for ten, duong, tham_so, co in viec:
        anh = Image.open(chup("ld_" + ten, duong, ad, **tham_so)).convert("RGB")
        if anh.size != co:
            anh = anh.resize(co, Image.LANCZOS)
        anh.save(dich / f"{ten}.webp", "WEBP", quality=82, method=6)
        print("   ->", ten + ".webp", co, (dich / f"{ten}.webp").stat().st_size // 1024, "KB")


for ten in NHOM:
    print("==", ten)
    globals()[ten]()
