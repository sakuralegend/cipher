"""Công cụ chụp ảnh giao diện cho báo cáo (chỉ dùng thư viện chuẩn + Chrome có sẵn).
Web chạy ở GOC bằng php -S với router.php (nhận mã phiên qua ?__sid=)."""
import http.cookiejar
import re
import subprocess
import sys
import urllib.parse
import urllib.request
from pathlib import Path

GOC = "http://127.0.0.1:8765"
TAM = Path(__file__).resolve().parent.parent
ANH = TAM / "anh"
WEB = TAM / "web" / "public"
CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"


class Phien:
    """Một phiên đăng nhập: giữ cookie, gửi GET/POST kèm mã CSRF lấy từ trang."""

    def __init__(self, ten=None, mat_khau="Cipher@2026"):
        self.lo = http.cookiejar.CookieJar()
        self.mo = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.lo))
        if ten:
            html = self.get("/login.php")
            self.post("/login.php", {"username": ten, "password": mat_khau}, html)

    @property
    def sid(self):
        return next((c.value for c in self.lo if c.name == "cipher_sid"), "")

    def get(self, duong):
        with self.mo.open(GOC + duong) as tl:
            self.url_cuoi = tl.geturl()
            return tl.read().decode("utf-8", "replace")

    def csrf(self, html):
        m = re.search(r'name="csrf" value="([0-9a-f]+)"', html)
        return m.group(1) if m else ""

    def post(self, duong, du_lieu, html_co_csrf=None, trang_lay_csrf=None):
        """POST; mã CSRF lấy từ html_co_csrf, hoặc tải trang_lay_csrf (mặc định chính trang đó) để lấy."""
        if html_co_csrf is None:
            html_co_csrf = self.get(trang_lay_csrf or duong)
        d = dict(du_lieu)
        d["csrf"] = self.csrf(html_co_csrf)
        than = urllib.parse.urlencode(d, doseq=True).encode()
        try:
            with self.mo.open(GOC + duong, than) as tl:
                self.url_cuoi = tl.geturl()
                return tl.read().decode("utf-8", "replace")
        except urllib.error.HTTPError as e:
            self.url_cuoi = duong
            return e.read().decode("utf-8", "replace")


def them_sid(duong, sid):
    if not sid:
        return duong
    return duong + ("&" if "?" in duong else "?") + "__sid=" + sid


def chup(ten, duong, phien=None, w=1280, h=800, dt=False, ti_le=1.5, cho=3000):
    """Chụp GOC+duong thành anh/<ten>.png. dt=True: khổ điện thoại 390px qua /__khung."""
    sid = phien.sid if phien else ""
    dich = them_sid(duong, sid)
    if dt:
        w_khung, h_khung = 390, h
        url = GOC + "/__khung?" + urllib.parse.urlencode({"u": dich, "w": w_khung, "h": h_khung})
        w, ti_le = 390, 2
        cua_so = f"{max(w_khung, 500)},{h_khung}"
    else:
        url = GOC + dich
        cua_so = f"{w},{h}"
    ra = ANH / f"{ten}.png"
    lenh = [CHROME, "--headless=new", "--disable-gpu", "--hide-scrollbars", "--no-first-run", "--lang=vi-VN", "--accept-lang=vi-VN",
            f"--user-data-dir={TAM / 'chrome_ho_so'}", f"--force-device-scale-factor={ti_le}",
            f"--window-size={cua_so}", f"--virtual-time-budget={cho}", f"--screenshot={ra}", url]
    subprocess.run(lenh, capture_output=True, timeout=120)
    if dt:
        from PIL import Image
        anh = Image.open(ra)
        anh.crop((0, 0, int(390 * ti_le), anh.height)).save(ra)
    print("da chup", ra.name, "<-", duong)
    return ra


def luu_html(ten, html):
    """Lưu một trang trả về sau POST (form có lỗi, kết quả đọc) để Chrome mở lại và chụp."""
    thu_muc = WEB / "__kq"
    thu_muc.mkdir(exist_ok=True)
    (thu_muc / f"{ten}.html").write_text(html, encoding="utf-8")
    return f"/__kq/{ten}.html"
