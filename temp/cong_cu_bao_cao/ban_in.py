"""Ảnh bản in: Chrome in trang bao_cao_in.php ra PDF (đúng kiểu giấy in, nền trắng) rồi đổi trang đầu thành ảnh."""
import subprocess
import sys
from pathlib import Path

import fitz

sys.path.insert(0, str(Path(__file__).parent))
from chup import Phien, GOC, CHROME, TAM, them_sid

ad = Phien("admin")
pdf = TAM / "ban_in.pdf"
url = GOC + them_sid("/admin/bao_cao_in.php?trang_thai=expiring", ad.sid)
subprocess.run([CHROME, "--headless=new", "--disable-gpu", "--no-first-run", f"--user-data-dir={TAM / 'chrome_ho_so'}",
                "--no-pdf-header-footer", "--virtual-time-budget=3000", f"--print-to-pdf={pdf}", url], capture_output=True, timeout=120)
tai_lieu = fitz.open(pdf)
print("so trang ban in:", len(tai_lieu))
anh = tai_lieu[0].get_pixmap(dpi=150)
# Cắt phần giấy trắng thừa phía dưới.
from PIL import Image, ImageChops
hinh = Image.frombytes("RGB", (anh.width, anh.height), anh.samples)
khung = ImageChops.difference(hinh, Image.new("RGB", hinh.size, (255, 255, 255))).getbbox()
hinh = hinh.crop((0, 0, hinh.width, min(khung[3] + 40, hinh.height)))
# Viền mảnh để thấy mép tờ giấy trên nền trắng của báo cáo.
co_vien = Image.new("RGB", (hinh.width + 4, hinh.height + 4), (150, 150, 150))
co_vien.paste(hinh, (2, 2))
co_vien.save(Path(r"d:\Desktop\cipher\docs\anh\ban_in.jpg"), quality=90, optimize=True)
print(co_vien.size)
