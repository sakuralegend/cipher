"""Sinh docs/ERD.md (sơ đồ lấy từ --erd) và phần bảng test của docs/KIEM_THU.md (lấy từ câu Luật, dòng Nhóm trong tests/chay.php).
Phần chữ của KIEM_THU nằm trong kt_dau.md và kt_cuoi.md cạnh script này; chạy lại script khi số test đổi."""
import re
import subprocess
import sys
from collections import OrderedDict
from pathlib import Path

REPO = Path(r"d:\Desktop\cipher")
DAY = Path(__file__).resolve().parent
PHP = r"C:\xampp\php\php.exe"

# ---------- ERD ----------
erd = subprocess.run([PHP, "tests/chay.php", "--erd"], cwd=REPO, capture_output=True).stdout.decode("utf-8").replace("\r\n", "\n")
assert erd.startswith("erDiagram"), erd[:200]
(REPO / "docs" / "ERD.md").write_text((DAY / "erd_dau.md").read_text(encoding="utf-8").replace("{{ERD}}", erd.rstrip("\n")), encoding="utf-8", newline="\n")

# ---------- KIEM_THU ----------
s = (REPO / "tests" / "chay.php").read_text(encoding="utf-8").replace("\r\n", "\n")
luat = {b: a.strip() for a, b in re.findall(r"// Luật: (.+)\n(?://.*\n)*function\stest_(\w+)\(", s)}
nhom = OrderedDict()
for ten_nhom, ma, ten in re.findall(r"^// Nhóm: (.+) \(([^()]+)\)\nfunction\stest_(\w+)\(", s, re.M):
    nhom.setdefault(ten_nhom, {"ma": ma, "test": []})["test"].append(ten)

# Thứ tự trình bày và người phụ trách (bảng phân công trong BAO_CAO.md).
THU_TU = [
    ("Đăng nhập, phiên, phân quyền", 1), ("Người dùng", 1), ("CSDL, ràng buộc, dữ liệu mẫu", 1),
    ("Nhật ký hoạt động (mở rộng 1)", 1), ("Chấm hạng an toàn (mở rộng 3)", 1),
    ("Chứng chỉ: thêm, sửa, thu hồi, xoá", 2), ("Gia hạn", 2), ("Đọc chứng chỉ thật (mở rộng 2)", 2),
    ("Danh sách, tìm kiếm, lọc, sắp xếp", 2),
    ("Danh mục: tên miền, máy chủ, tổ chức cấp", 3), ("Cảnh báo", 3), ("Dashboard, thống kê, báo cáo", 3),
    ("Giao diện, triển khai", 3),
]
assert {t for t, _ in THU_TU} == set(nhom), set(nhom) ^ {t for t, _ in THU_TU}
# Hai test kiểm cấu hình máy chủ triển khai riêng: được đếm, nhưng tài liệu nộp ghi bằng câu mô tả chung.
AN = {
    "landing_cong_khai_khong_nhac_den_vpn": "Trang giới thiệu công khai và CSS, JS của nó không nhắc tới phần mềm hạ tầng riêng chạy trên máy chủ.",
    "caddy_nhan_proxy_protocol_chi_tu_xray": "Máy chủ web chỉ nhận IP gốc của khách từ lớp chuyển tiếp chạy trên chính máy (127.0.0.1); kết nối khác không giả được IP.",
}
TEN = {1: "Nguyễn Đức Trung", 2: "Phan Tuấn Anh", 3: "Nguyễn Chí Kiên"}
tong = sum(len(n["test"]) for n in nhom.values())
theo_nguoi = {1: 0, 2: 0, 3: 0}
ra = ["| Nhóm | Số test | Mã chức năng | Phụ trách |", "|---|---|---|---|"]
for ten_nhom, nguoi in THU_TU:
    n = nhom[ten_nhom]
    theo_nguoi[nguoi] += len(n["test"])
    ra.append(f"| {ten_nhom} | {len(n['test'])} | {n['ma']} | {TEN[nguoi]} |")
ra.append(f"| **Tổng** | **{tong}** | | |")
ra.append("")
for ten_nhom, nguoi in THU_TU:
    n = nhom[ten_nhom]
    ra += [f"### {ten_nhom} ({len(n['test'])} test, {TEN[nguoi]})", "", "| Test | Quy tắc được kiểm |", "|---|---|"]
    for t in n["test"]:
        ra.append(f"| *(test cấu hình triển khai)* | {AN[t]} |" if t in AN else f"| `{t}` | {luat[t]} |")
    ra.append("")
than = (DAY / "kt_dau.md").read_text(encoding="utf-8") + "\n".join(ra) + "\n" + (DAY / "kt_cuoi.md").read_text(encoding="utf-8")
than = than.replace("{{TONG}}", str(tong))
for k, v in theo_nguoi.items():
    than = than.replace("{{TV%d}}" % k, str(v))
(REPO / "docs" / "KIEM_THU.md").write_text(than, encoding="utf-8", newline="\n")
print("tong", tong, "theo nguoi", theo_nguoi)
