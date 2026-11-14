"""Chụp để rà lỗi giao diện: máy tính 1440 và 1024, điện thoại 390. Ghép ảnh điện thoại 3 tấm một hàng cho dễ xem."""
import sys
from pathlib import Path

from PIL import Image

sys.path.insert(0, str(Path(__file__).parent))
from chup import Phien, chup, ANH

ad = Phien("admin")
NHOM = sys.argv[1:] or ["may", "hep", "dt"]

if "may" in NHOM:
    for ten, duong, h in [
        ("ra_ds_loc_1440", "/admin/chung_chi.php?xep=het_han.tang", 620),
        ("ra_tk_loc_1440", "/admin/thong_ke.php?xep=", 520),
        ("ra_dashboard_1440", "/admin/index.php", 900),
        ("ra_nhat_ky_1440", "/admin/nhat_ky.php", 620),
        ("ra_them_1440", "/admin/chung_chi_form.php", 1000),
        ("ra_sua_1440", "/admin/chung_chi_form.php?id=10", 1000),
        ("ra_to_chuc_form_1440", "/admin/to_chuc_cap_form.php", 620),
        ("ra_ten_mien_form_1440", "/admin/ten_mien_form.php", 520),
        ("ra_to_chuc_1440", "/admin/to_chuc_cap.php", 620),
    ]:
        chup(ten, duong, ad, w=1440, h=h, ti_le=1)

if "hep" in NHOM:
    for ten, duong, h in [
        ("ra_ds_loc_1024", "/admin/chung_chi.php?xep=het_han.tang", 700),
        ("ra_dashboard_1024", "/admin/index.php", 900),
        ("ra_them_1024", "/admin/chung_chi_form.php", 1000),
        ("ra_chi_tiet_1024", "/admin/chung_chi_xem.php?id=10", 1000),
        ("ra_nhat_ky_1024", "/admin/nhat_ky.php", 700),
    ]:
        chup(ten, duong, ad, w=1024, h=h, ti_le=1)

if "dt" in NHOM:
    trang = [
        ("landing", "/", 1500), ("dang_nhap", "/login.php", 800), ("ds_loc", "/admin/chung_chi.php?xep=het_han.tang", 1500),
        ("them", "/admin/chung_chi_form.php", 1900), ("tu_dong", "/admin/chung_chi_form.php?che_do=tu_dong", 1500), ("gia_han", "/admin/chung_chi_gia_han.php?id=10", 1300),
        ("chi_tiet", "/admin/chung_chi_xem.php?id=10", 2300), ("canh_bao", "/admin/canh_bao.php", 1100), ("thong_ke", "/admin/thong_ke.php", 1900),
        ("nhat_ky", "/admin/nhat_ky.php", 1500), ("may_chu", "/admin/may_chu.php", 900), ("may_chu_form", "/admin/may_chu_form.php", 1200),
        ("nguoi_dung", "/admin/nguoi_dung.php", 700), ("nguoi_dung_form", "/admin/nguoi_dung_form.php", 1200), ("tai_khoan", "/admin/tai_khoan.php", 1500),
    ]
    cac_anh = []
    for ten, duong, h in trang:
        cac_anh.append(Image.open(chup("ra_dt_" + ten, duong, ad if duong.startswith("/admin") else None, h=h, dt=True)).convert("RGB"))
    for i in range(0, len(cac_anh), 3):
        nhom = cac_anh[i:i + 3]
        cao = max(a.height for a in nhom)
        tam = Image.new("RGB", (sum(a.width for a in nhom) + 20 * (len(nhom) + 1), cao + 40), (120, 120, 120))
        x = 20
        for a in nhom:
            tam.paste(a, (x, 20))
            x += a.width + 20
        tam.save(ANH / f"ra_dt_ghep_{i // 3 + 1}.png")
        print("ghep", i // 3 + 1, tam.size)
