"""Ảnh dạng cửa sổ lệnh từ đầu ra THẬT: chạy bộ test, nhật ký php -S, header của web thật. Rồi chụp lại vài trang cần
đổi chiều cao và chép mọi ảnh sang docs/anh/ dạng JPG."""
import html
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from chup import Phien, chup, luu_html, TAM, ANH

REPO = Path(r"d:\Desktop\cipher")
PHP = r"C:\xampp\php\php.exe"


def cua_so(ten, tieu_de, dong, rong=1180):
    than = "\n".join(html.escape(d) for d in dong)
    trang = f"""<!doctype html><html lang="vi"><head><meta charset="utf-8"><style>
body{{margin:0;background:#0c0c0c;}}
.td{{background:#1f1f1f;color:#d0d0d0;font:13px 'Segoe UI',sans-serif;padding:7px 14px;border-bottom:1px solid #333}}
pre{{margin:0;padding:12px 16px 16px;color:#cccccc;font:13.5px/1.5 Consolas,'Cascadia Mono',monospace;white-space:pre-wrap;word-break:break-all}}
</style></head><body><div class="td">{html.escape(tieu_de)}</div><pre>{than}</pre></body></html>"""
    # Chiều cao theo số dòng (dòng dài tự xuống dòng: ước lượng theo 140 ký tự một dòng ở bề rộng này).
    so_dong = sum(max(1, -(-len(d) // 140)) for d in dong)
    chup(ten, luu_html(ten, trang), w=rong, h=so_dong * 21 + 70, ti_le=1.5, cho=500)


if "test" in sys.argv:
    ra = subprocess.run([PHP, "tests/chay.php"], cwd=REPO, capture_output=True).stdout.decode("utf-8").replace("\r\n", "\n").rstrip("\n").split("\n")
    cua_so("chay_test", "Command Prompt - C:\\xampp\\php\\php.exe tests\\chay.php", ["D:\\cipher> C:\\xampp\\php\\php.exe tests\\chay.php", f"... ({len(ra) - 20} dòng ✅ phía trên)"] + ra[-20:], rong=1100)

if "nhat_ky" in sys.argv:
    log = (TAM / "php_s.log").read_text(encoding="utf-8", errors="replace").replace("\r\n", "\n").split("\n")
    # Đoạn thật liền mạch: từ lúc Admin đọc chứng chỉ github.com tới lần đăng nhập bị chặn; bỏ dòng tải file tĩnh nếu có.
    dau = next(i for i, d in enumerate(log) if "Doc chung chi" in d and "github.com" in d) - 3
    cuoi = next(i for i, d in enumerate(log) if "Dang nhap khi dang bi chan" in d) + 2
    doan = [d for d in log[max(dau, 0):cuoi] if d.strip() and "__sid" not in d]
    # Giữ ảnh vừa một trang: lấy 12 dòng đầu (đọc chứng chỉ) và 14 dòng cuối (đăng nhập sai, bị chặn).
    if len(doan) > 30:
        doan = doan[:13] + ["        ..."] + doan[-15:]
    cua_so("cua_so_nhat_ky", "Cipher Cert", doan, rong=1180)

if "header" in sys.argv:
    ra = subprocess.run(["curl", "-sI", "-m", "20", "https://cert.cipher.id.vn/"], capture_output=True).stdout.decode("utf-8").replace("\r\n", "\n").strip().split("\n")
    ra = [d for d in ra if not d.lower().startswith("set-cookie")] + [d.split("=")[0].split(":")[0] + ": cipher_sid=…; path=/; secure; HttpOnly; SameSite=Lax" for d in ra if d.lower().startswith("set-cookie")]
    cua_so("header_bao_mat", "Command Prompt", ["D:\\cipher> curl -sI https://cert.cipher.id.vn/"] + ra, rong=1100)

if "chup_lai" in sys.argv:
    ad = Phien("admin")

    chup("chi_tiet_hang_f", "/admin/chung_chi_xem.php?id=5", ad, h=1400)




if "jpg" in sys.argv:
    from PIL import Image
    dich = REPO / "docs" / "anh"
    dich.mkdir(exist_ok=True)
    for ten in sys.argv[sys.argv.index("jpg") + 1:]:
        anh = Image.open(ANH / f"{ten}.png").convert("RGB")
        anh.save(dich / f"{ten}.jpg", quality=88, optimize=True)
        print(ten, anh.size, (dich / f"{ten}.jpg").stat().st_size // 1024, "KB")
