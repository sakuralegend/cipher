"""Đóng gói bài nộp theo yêu cầu bàn giao (user gửi 16/11/2026): ba thư mục, nén một lần, tên theo số thứ tự nhóm.

    Nhom10.zip
    └── Nhom10/
        ├── 1_CoSoDuLieu/cipher_dev.sql   Một file: tạo CSDL cipher_dev, rồi nội dung schema.sql, seed.sql, seed_demo.sql
        ├── 2_Web/cipher/                 Toàn bộ code (git archive HEAD), giữ database/ để CHAY_WEB.bat chạy được ngay
        └── 3_BaoCao/                     BAO_CAO_De69.docx và .pdf (dựng trước bằng scripts/tao_bao_cao.py)

Chạy: python scripts/dong_goi_nop.py [thư mục ra, mặc định D:\\Music]. Chạy sau khi đã commit (gói lấy code từ HEAD)
và sau khi dựng báo cáo. Ghi đè file nén cũ cùng tên.
"""
import io
import subprocess
import sys
import zipfile
from pathlib import Path

REPO = Path(__file__).resolve().parent.parent
RA = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(r"D:\Music")
GOC = "Nhom10"                                    # Số thứ tự nhóm (user báo 16/11/2026).
THU_TU_SQL = ("schema", "seed", "seed_demo")      # Thứ tự nạp bắt buộc, giống vòng for trong CHAY_WEB.bat.
BAO_CAO = ("BAO_CAO_De69.docx", "BAO_CAO_De69.pdf")

DAU_SQL = """-- Cơ sở dữ liệu của Cipher Cert (đề 69), gộp sẵn để nhập MỘT lần.
-- phpMyAdmin: bấm tab Import (Nhập) ở trang chủ, không cần chọn hay tạo CSDL trước, chọn file này, bấm Import.
-- Dòng lệnh: mysql -u root < cipher_dev.sql
-- File tự tạo CSDL cipher_dev nếu chưa có, XOÁ bảng cũ rồi tạo lại, nạp 2 tài khoản và 50 chứng chỉ mẫu.
-- Nội dung là ba file schema.sql, seed.sql, seed_demo.sql trong thư mục database của web, nối theo đúng thứ tự đó.

CREATE DATABASE IF NOT EXISTS cipher_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cipher_dev;
"""


def main():
    kq = subprocess.run(["git", "-C", str(REPO), "archive", "--format=zip", "HEAD"], capture_output=True, check=True)
    web = zipfile.ZipFile(io.BytesIO(kq.stdout))
    # SQL lấy từ cùng bản git archive với thư mục web, nên hai nơi luôn cùng nội dung.
    sql = DAU_SQL + "".join(
        f"\n-- ============ database/{ten}.sql ============\n\n" + web.read(f"database/{ten}.sql").decode("utf-8")
        for ten in THU_TU_SQL)
    for ten in BAO_CAO:
        if not (RA / ten).exists():
            sys.exit(f"Thiếu {RA / ten}: dựng báo cáo trước bằng python scripts/tao_bao_cao.py")

    file_nen = RA / f"{GOC}.zip"
    tam = file_nen.with_suffix(".tmp")
    with zipfile.ZipFile(tam, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr(f"{GOC}/1_CoSoDuLieu/cipher_dev.sql", sql)
        for muc in web.infolist():
            if not muc.is_dir():
                z.writestr(f"{GOC}/2_Web/cipher/{muc.filename}", web.read(muc))
        for ten in BAO_CAO:
            z.write(RA / ten, f"{GOC}/3_BaoCao/{ten}")
    tam.replace(file_nen)                          # Ghi xong mới thay: lỗi giữa chừng thì file nén cũ còn nguyên.

    with zipfile.ZipFile(file_nen) as z:
        assert z.testzip() is None
        for thu_muc in ("1_CoSoDuLieu", "2_Web", "3_BaoCao"):
            print(thu_muc, sum(n.startswith(f"{GOC}/{thu_muc}/") for n in z.namelist()), "file")
    print(file_nen, file_nen.stat().st_size // 1024, "KB")


if __name__ == "__main__":
    main()
