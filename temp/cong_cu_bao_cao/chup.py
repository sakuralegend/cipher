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


def chup(ten, duong, phien=None, w=1280, h=800, dt=False, ti_le=1.5, cho=3000, an_menu=False):
    """Chụp GOC+duong thành anh/<ten>.png. dt=True: khổ điện thoại 390px qua /__khung.
    an_menu=True: chụp qua /__khung đúng cỡ w x h với hai tầng menu bị ẩn (ảnh thẻ của landing)."""
    sid = phien.sid if phien else ""
    dich = them_sid(duong, sid)
    if an_menu:
        url = GOC + "/__khung?" + urllib.parse.urlencode({"u": dich, "w": w, "h": h, "an": 1})
        cua_so = f"{w},{h}"
    elif dt:
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


class Cdp:
    """Điều khiển Chrome chạy ngầm qua cổng gỡ lỗi (Chrome DevTools Protocol), cho trang phải gõ, chọn rồi mới chụp
    (phpMyAdmin trong hướng dẫn cài đặt). WebSocket viết tay bằng thư viện chuẩn, để không phải cài thêm gì.
    Dùng: c = Cdp(w=1280, h=800); c.mo(url); c.js("..."); c.chup("ten"); c.dong()"""

    def __init__(self, w=1280, h=800, ti_le=1.5, ngon_ngu="en-US", cong=9333):
        import json, os, socket, time
        self.json, self.os, self.time = json, os, time
        self.tt = subprocess.Popen([CHROME, "--headless=new", f"--remote-debugging-port={cong}", "--remote-allow-origins=*",
                                    "--disable-gpu", "--hide-scrollbars", "--no-first-run", f"--lang={ngon_ngu}",
                                    f"--user-data-dir={TAM / 'chrome_cdp'}", f"--force-device-scale-factor={ti_le}",
                                    f"--window-size={w},{h}", "about:blank"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        ds = None
        for _ in range(60):
            try:
                ds = json.load(urllib.request.urlopen(f"http://127.0.0.1:{cong}/json"))
                break
            except OSError:
                time.sleep(0.25)
        duong = next(d for d in ds if d["type"] == "page")["webSocketDebuggerUrl"].split(f":{cong}", 1)[1]
        self.s = socket.create_connection(("127.0.0.1", cong))
        khoa = __import__("base64").b64encode(os.urandom(16)).decode()
        self.s.sendall((f"GET {duong} HTTP/1.1\r\nHost: 127.0.0.1:{cong}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n"
                        f"Sec-WebSocket-Key: {khoa}\r\nSec-WebSocket-Version: 13\r\n\r\n").encode())
        dem = b""
        while b"\r\n\r\n" not in dem:
            dem += self.s.recv(4096)
        self.du = dem.split(b"\r\n\r\n", 1)[1]
        self.so = 0

    def _doc(self, n):
        while len(self.du) < n:
            self.du += self.s.recv(65536)
        d, self.du = self.du[:n], self.du[n:]
        return d

    def _nhan(self):
        import struct
        tin = b""
        while True:
            b1, b2 = self._doc(2)
            n = b2 & 0x7F
            if n == 126:
                n = struct.unpack(">H", self._doc(2))[0]
            elif n == 127:
                n = struct.unpack(">Q", self._doc(8))[0]
            than = self._doc(n)
            if b1 & 0x0F in (0, 1, 2):
                tin += than
                if b1 & 0x80:
                    return tin.decode("utf-8", "replace")

    def lenh(self, phuong_thuc, **tham_so):
        import struct
        self.so += 1
        d = self.json.dumps({"id": self.so, "method": phuong_thuc, "params": tham_so}).encode()
        mat = self.os.urandom(4)
        n = len(d)
        dau = bytes([0x81]) + (bytes([0x80 | n]) if n < 126 else bytes([0x80 | 126]) + struct.pack(">H", n) if n < 65536
                               else bytes([0x80 | 127]) + struct.pack(">Q", n))
        self.s.sendall(dau + mat + bytes(b ^ mat[i % 4] for i, b in enumerate(d)))
        while True:
            tl = self.json.loads(self._nhan())
            if tl.get("id") == self.so:
                return tl.get("result", tl)

    def mo(self, url, cho=3.0):
        self.lenh("Page.navigate", url=url)
        self.time.sleep(cho)

    def js(self, ma, cho=0.5):
        kq = self.lenh("Runtime.evaluate", expression=ma, awaitPromise=True, returnByValue=True)
        self.time.sleep(cho)
        return kq.get("result", {}).get("value")

    def chup(self, ten):
        import base64
        ra = ANH / f"{ten}.png"
        ra.write_bytes(base64.b64decode(self.lenh("Page.captureScreenshot", format="png")["data"]))
        print("da chup", ra.name)
        return ra

    def dong(self):
        self.s.close()
        self.tt.terminate()
