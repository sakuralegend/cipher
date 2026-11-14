"""Chụp toàn bộ ảnh giao diện cho báo cáo. Chạy: python -I chup_het.py [nhom ...]
Nhóm: cong_khai, xem, nhan_vien, nhap_lieu, doc, nhat_ky. Không ghi nhóm thì chạy hết theo thứ tự."""
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


for ten in NHOM:
    print("==", ten)
    globals()[ten]()
