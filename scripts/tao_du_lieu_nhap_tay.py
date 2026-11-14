"""Tạo DU_LIEU_NHAP_TAY.xlsx ở gốc repo: dữ liệu mẫu để giảng viên tự nhập tay vào web (user chốt 14/11/2026).
Chạy: python scripts/tao_du_lieu_nhap_tay.py   (chỉ dùng thư viện chuẩn, không cần cài gì)

Mỗi trang tính là một form của web, cột theo đúng thứ tự ô trên form; dòng Đúng phải lưu được, dòng Sai phải bị chặn
đúng câu ghi ở cột Kết quả mong đợi. Ô ngày là công thức =TODAY()+N: mở file ngày nào cũng ra đúng số ngày còn lại.
Giá trị chọn để không trùng 50 chứng chỉ mẫu (database/seed.sql, seed_demo.sql). Sửa dữ liệu thì chạy lại script và
nhập thử lại toàn bộ trên web đã nạp dữ liệu mẫu (cách đã làm 14/11/2026: script gửi từng dòng vào web chạy trên máy).

File .xlsx là file nén chứa XML (chuẩn Office Open XML), nên viết được bằng zipfile. Khoá "o" của mỗi cột là tên ô
trên form, để script nhập thử biết gửi giá trị vào ô nào."""
import hashlib
import sys
import zipfile
from pathlib import Path
from xml.sax.saxutils import escape

RA = Path(__file__).resolve().parent.parent / "DU_LIEU_NHAP_TAY.xlsx"


def van_tay(ma):
    """Fingerprint giả 64 hex, cố định theo mã, không trùng dữ liệu mẫu."""
    return hashlib.sha256(("nhap-tay-" + ma).encode()).hexdigest()


VAN_TAY_CERT_001 = "d4226d74f67254a42a8d54fcb32e7dadc0727009d33f825060d12e902b5dc68c"  # của CERT-001 trong seed.sql

# Cột: (nhãn trên web, tên ô trên form, kiểu, độ rộng). Kiểu: chu, so, ngay (công thức TODAY), ma (font đều).
# Dòng: (Đúng/Sai, [giá trị theo cột], kết quả mong đợi, ai chặn). Giá trị cột ngày là số ngày cộng vào hôm nay.
TRANG = [
    {
        "ten": "1. Tổ chức cấp",
        "mo": "Hạ tầng → Tổ chức cấp → Thêm tổ chức cấp",
        "cot": [("Tên tổ chức", "name", "chu", 24), ("Quốc gia", "country", "chu", 14),
                ("Tin cậy", "is_trusted", "chu", 10)],
        "dong": [
            ("Đúng", ["Thử nghiệm CA", "Việt Nam", "Bỏ tick"],
             "Thêm được. Danh sách Tổ chức cấp có dòng mới, cột Tin cậy: Không. Trang tính 4 dùng tổ chức này.", ""),
            ("Đúng", ["Google Trust Services", "Hoa Kỳ", "Tick"], "Thêm được, tin cậy.", ""),
            ("Sai", ["DigiCert", "Hoa Kỳ", "Tick"],
             "Không lưu. Ô Tên tổ chức báo: \"Tên tổ chức này đã tồn tại.\"", "PHP (cần CSDL)"),
            ("Sai", ["", "Việt Nam", "Tick"],
             "Không lưu. Ô Tên tổ chức báo: \"Tên tổ chức là bắt buộc, tối đa 100 ký tự.\"", "JS ngay khi bấm, PHP chặn lại"),
        ],
    },
    {
        "ten": "2. Máy chủ",
        "mo": "Hạ tầng → Máy chủ → Thêm máy chủ",
        "cot": [("Tên máy chủ", "hostname", "ma", 14), ("Địa chỉ IP", "ip_address", "ma", 16), ("Cổng", "port", "so", 8),
                ("Môi trường", "environment", "chu", 14), ("Người phụ trách", "owner", "chu", 20)],
        "dong": [
            ("Đúng", ["lab-01", "203.0.113.50", 443, "Thử nghiệm", "Phòng Thí nghiệm"],
             "Thêm được. Trang tính 3 gắn tên miền vào máy chủ này.", ""),
            ("Đúng", ["lab-02", "2001:db8::50", 8443, "Phát triển", "Phòng Thí nghiệm"], "Thêm được (nhận cả IPv6).", ""),
            ("Sai", ["lab-03", "300.1.1.1", 443, "Thử nghiệm", "Phòng Thí nghiệm"],
             "Không lưu. Ô Địa chỉ IP báo: \"Địa chỉ IP không hợp lệ.\"", "PHP"),
            ("Sai", ["lab-04", "203.0.113.51", 70000, "Thử nghiệm", "Phòng Thí nghiệm"],
             "Không lưu. Ô Cổng báo: \"Cổng phải là số từ 1 đến 65535.\"", "JS ngay khi bấm, PHP chặn lại"),
        ],
    },
    {
        "ten": "3. Tên miền",
        "mo": "Hạ tầng → Tên miền → Thêm tên miền",
        "cot": [("Tên miền", "domain_name", "ma", 24), ("Máy chủ", "server_id", "ma", 12)],
        "dong": [
            ("Đúng", ["lab.congty.example", "lab-01"], "Thêm được. Trang tính 4 dùng tên miền này.", ""),
            ("Đúng", ["*.lab.congty.example", "lab-01"], "Thêm được (tên miền đại diện, bắt đầu bằng *.).", ""),
            ("Sai", ["mail.congty.example", "lab-01"], "Không lưu. Ô Tên miền báo: \"Tên miền này đã tồn tại.\"",
             "PHP (cần CSDL)"),
            ("Sai", ["lab congty", "lab-01"],
             "Không lưu. Ô Tên miền báo: \"Tên miền không hợp lệ. Ví dụ đúng: cert.cipher.id.vn hoặc *.cipher.id.vn\"",
             "JS ngay khi bấm, PHP chặn lại"),
        ],
    },
    {
        "ten": "4. Chứng chỉ",
        "mo": "Chứng chỉ → Thêm chứng chỉ (mở sẵn tab Nhập thủ công)",
        "cot": [("Mã quản lý", "cert_code", "ma", 11), ("Tên chính (CN)", "subject_cn", "chu", 22),
                ("Tên miền", "ten_mien", "chu", 22), ("Tổ chức cấp", "to_chuc_cap", "chu", 14),
                ("Ngày bắt đầu", "valid_from", "ngay", 17), ("Ngày hết hạn", "valid_to", "ngay", 17),
                ("Thuật toán khoá", "key_algorithm", "chu", 10), ("Độ dài khoá (bit)", "key_size", "so", 10),
                ("Thuật toán ký", "signature_algorithm", "chu", 24), ("Fingerprint SHA-256", "fingerprint_sha256", "ma", 30),
                ("Serial", "serial", "ma", 18)],
        "dong": [
            ("Đúng", ["CERT-101", "shop.congty.example", "shop.congty.example", "Let's Encrypt", -80, 10, "RSA", 2048,
                      "sha256WithRSAEncryption", van_tay("CERT-101"), "0A1B2C3D4E5F6071"],
             "Thêm được. Sắp hết hạn, Còn 10 ngày. Trang Cảnh báo có dòng mốc 15 ngày.", ""),
            ("Đúng", ["CERT-102", "beta.congty.example", "beta.congty.example", "ZeroSSL", -85, 5, "ECDSA", 256,
                      "ecdsa-with-SHA256", van_tay("CERT-102"), "11223344556677"],
             "Thêm được. Sắp hết hạn, Còn 5 ngày. Cảnh báo mốc 7 ngày.", ""),
            ("Đúng", ["CERT-103", "www.congty.example", "www.congty.example", "DigiCert", -30, 150, "RSA", 2048,
                      "sha256WithRSAEncryption", van_tay("CERT-103"), ""],
             "Thêm được. Còn hiệu lực, Còn 150 ngày. Không có cảnh báo.", ""),
            ("Đúng", ["CERT-104", "test.congty.example", "test.congty.example", "Sectigo", -100, -3, "RSA", 2048,
                      "sha256WithRSAEncryption", "", ""],
             "Thêm được. Đã hết hạn (quá hạn 3 ngày). Fingerprint, Serial để trống vẫn lưu được.", ""),
            ("Đúng", ["CERT-105", "lab.congty.example", "lab.congty.example", "Thử nghiệm CA", -20, 25, "RSA", 2048,
                      "sha256WithRSAEncryption", van_tay("CERT-105"), "ABCDEF0123"],
             "Thêm được (dùng tên miền, tổ chức cấp vừa thêm ở trang 1 và 3). Sắp hết hạn, Còn 25 ngày, cảnh báo mốc 30.", ""),
            ("Sai", ["CERT-106", "mail.congty.example", "mail.congty.example", "GlobalSign", -10, 60, "RSA", 2048,
                     "sha256WithRSAEncryption", VAN_TAY_CERT_001, ""],
             "Không lưu. Ô Fingerprint báo: \"Fingerprint này đã có ở chứng chỉ khác.\" (trùng CERT-001)", "PHP (cần CSDL)"),
            ("Sai", ["CERT-107", "portal.congty.example", "portal.congty.example", "Let's Encrypt", 10, -5, "RSA", 2048,
                     "sha256WithRSAEncryption", van_tay("CERT-107"), ""],
             "Không lưu. Ô Ngày hết hạn báo: \"Ngày hết hạn phải sau ngày bắt đầu.\"", "JS ngay khi nhập, PHP chặn lại"),
            ("Sai", ["CERT-108", "khongco.example", "khongco.example", "Let's Encrypt", -10, 60, "RSA", 2048,
                     "sha256WithRSAEncryption", van_tay("CERT-108"), ""],
             "Không lưu. Ô Tên miền báo: \"Chưa có tên miền \"khongco.example\" trong danh mục.\" (chỉ nhận tên đã có)",
             "JS ngay khi nhập, PHP chặn lại"),
        ],
    },
    {
        "ten": "5. Gia hạn",
        "mo": "Danh sách chứng chỉ → bấm mã ở cột 1 → nút Gia hạn",
        "cot": [("Chứng chỉ cần mở", "cert_code", "ma", 12), ("Ngày hết hạn mới", "valid_to", "ngay", 17),
                ("Ghi chú", "note", "chu", 18), ("Fingerprint SHA-256 mới", "fingerprint_sha256", "ma", 30),
                ("Serial mới", "serial", "ma", 16)],
        "dong": [
            ("Đúng", ["CERT-101", 100, "Gia hạn thử", van_tay("CERT-101-moi"), "0A1B2C3D4E5F6072"],
             "Gia hạn được. Còn hiệu lực, Còn 100 ngày. Bảng Lịch sử gia hạn có dòng hạn cũ (hôm nay + 10 ngày). "
             "CERT-101 không còn ở trang Cảnh báo.", ""),
            ("Sai", ["CERT-102", 2, "", "", ""],
             "Không lưu. Ô Ngày hết hạn mới báo: \"Ngày hết hạn mới phải sau hạn hiện tại và sau thời điểm này.\"",
             "JS ngay khi bấm, PHP chặn lại"),
            ("Sai", ["CERT-103", 400, "", VAN_TAY_CERT_001, ""],
             "Không lưu. Ô Fingerprint báo: \"Fingerprint này đã có ở chứng chỉ khác.\" (trùng CERT-001)", "PHP (cần CSDL)"),
        ],
    },
    {
        "ten": "6. Người dùng",
        "mo": "Hệ thống → Người dùng → Thêm người dùng (chỉ Admin)",
        "cot": [("Tên đăng nhập", "username", "ma", 14), ("Họ tên", "full_name", "chu", 22),
                ("Vai trò", "role_id", "chu", 18), ("Mật khẩu", "password", "ma", 14),
                ("Nhập lại mật khẩu", "password2", "ma", 14)],
        "dong": [
            ("Đúng", ["giangvien", "Giảng viên chấm bài", "Nhân viên quản trị", "ChamBai2026", "ChamBai2026"],
             "Thêm được. Đăng xuất, đăng nhập giangvien / ChamBai2026: không có khu Hệ thống (Người dùng, Nhật ký), "
             "không có nút Xoá ở chứng chỉ.", ""),
            ("Sai", ["giangvien2", "Giảng viên 2", "Nhân viên quản trị", "12345678", "12345678"],
             "Không lưu. Ô Mật khẩu báo: \"Mật khẩu phải có ít nhất 8 ký tự, gồm cả chữ và số.\"",
             "JS ngay khi bấm, PHP chặn lại"),
            ("Sai", ["admin", "Trùng tên", "Admin", "ChamBai2026", "ChamBai2026"],
             "Không lưu. Ô Tên đăng nhập báo: \"Tên đăng nhập này đã tồn tại.\"", "PHP (cần CSDL)"),
            ("Sai", ["giangvien3", "Giảng viên 3", "Nhân viên quản trị", "ChamBai2026", "ChamBai2025"],
             "Không lưu. Ô Nhập lại mật khẩu báo: \"Hai lần nhập mật khẩu không giống nhau.\"",
             "JS ngay khi nhập, PHP chặn lại"),
        ],
    },
    {
        "ten": "7. Nhập tự động",
        "mo": "Chứng chỉ → Thêm chứng chỉ → tab Nhập tự động (cần máy có internet)",
        "cot": [("Khung", "khung", "chu", 16), ("Địa chỉ", "dia_chi", "ma", 30), ("Cổng", "cong", "so", 8)],
        "dong": [
            ("Đúng", ["Đọc một địa chỉ", "github.com", 443],
             "Đọc được: thẻ tóm tắt github.com, Còn N ngày (tuỳ chứng chỉ GitHub đang dùng), các ô điền sẵn. "
             "Bấm Thêm chứng chỉ thì lưu, tự tạo tên miền github.com và máy chủ nếu chưa có.", ""),
            ("Đúng", ["Đọc một địa chỉ", "expired.badssl.com", 443],
             "Đọc được, thẻ tóm tắt báo Đã hết hạn (chứng chỉ thử nghiệm hết hạn từ 2015).", ""),
            ("Sai", ["Đọc một địa chỉ", "127.0.0.1", 443],
             "Không đọc. Ô địa chỉ báo: \"127.0.0.1 là địa chỉ nội bộ. Chỉ đọc được máy chủ có địa chỉ công khai.\" "
             "(chặn để không ai mượn web dò mạng nội bộ)", "PHP"),
            ("Sai", ["Đọc một địa chỉ", "khongtontai.invalid", 443],
             "Không đọc. Ô địa chỉ báo: \"Không tìm thấy địa chỉ IP của khongtontai.invalid. Kiểm tra lại tên miền.\"", "PHP"),
            ("Đúng", ["Nhập hàng loạt", "github.com\nvnexpress.net\nself-signed.badssl.com\nkhongtontai.invalid", 443],
             "Thanh tiến trình chạy, rồi bảng xem trước \"Lưu được 3/4\": github.com, vnexpress.net, self-signed.badssl.com tick "
             "sẵn (chứng chỉ tự ký vẫn đọc được); khongtontai.invalid báo Không đọc được, không tick được. Bấm Lưu các dòng "
             "đã chọn. (Nếu đã thêm github.com ở dòng 1 thì dòng đó báo Đã có trong hệ thống, còn 2/4.)", ""),
        ],
    },
]

HUONG_DAN = [
    "Dữ liệu mẫu để thử nhập tay: Hệ thống quản lý chứng chỉ số và thời hạn sử dụng",
    "",
    "1. Chạy web theo README (Cách 1: bấm CHAY.bat; Cách 2: cài tay), đăng nhập admin / Cipher@2026.",
    "2. Nhập lần lượt từng trang tính, theo đúng số thứ tự: trang sau dùng dữ liệu trang trước tạo ra",
    "   (chứng chỉ chọn tên miền, tổ chức cấp phải có sẵn trong danh mục).",
    "3. Mỗi trang tính ghi form cần mở ở dòng đầu. Chép từng ô của một dòng vào form, bấm nút lưu, so với cột Kết quả mong đợi.",
    "4. Dòng Đúng phải lưu được; dòng Sai phải bị chặn đúng ô, đúng câu. Cột Ai chặn cho biết lỗi bắt ở trình duyệt",
    "   (JavaScript, báo ngay khi bấm) hay ở máy chủ (PHP, cần CSDL, ví dụ trùng tên). PHP luôn kiểm lại mọi ô.",
    "",
    "Ngày tự tính theo hôm nay (công thức =TODAY()+N): mở file ngày nào cũng ra đúng số ngày còn lại và đúng mốc cảnh báo.",
    "Ô ngày trên web dán được chữ ngày/tháng/năm: chép ô ngày trong file rồi dán thẳng vào ô ngày của form.",
    "Chạy bằng CHAY.bat thì cửa sổ đen ghi từng thao tác kèm mức [THONG TIN] / [CANH BAO] / [NGUY HIEM]; lần bị từ chối là [CANH BAO] kèm lý do.",
    "Muốn nhập lại từ đầu: bấm CHAY.bat và chọn C để nạp lại dữ liệu mẫu (xoá mọi thứ đã nhập).",
    "Trang 7 cần máy có internet. badssl.com là máy chủ thử nghiệm ở Mỹ, đôi khi chậm vài giây: nếu một dòng báo không trả lời thì đọc lại.",
]

# Kiểu ô: 0 trống (Excel 16 không căn lề kiểu 0, đo 14/11/2026), 1 tiêu đề cột, 2 ngày giờ, 3 Đúng, 4 Sai,
# 5 chữ thường (xuống dòng, căn trên: Excel chỉ căn trên đúng khi có xuống dòng), 6 tiêu đề lớn, 7 mã (font đều), 8 chữ nghiêng.
STYLES = r"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1"><numFmt numFmtId="164" formatCode="dd\/mm\/yyyy hh:mm"/></numFmts>
<fonts count="6">
<font><sz val="11"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><name val="Calibri"/></font>
<font><b/><sz val="14"/><name val="Calibri"/></font>
<font><sz val="10"/><name val="Consolas"/></font>
<font><i/><sz val="11"/><color rgb="FF4B5563"/><name val="Calibri"/></font>
</fonts>
<fills count="5">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF4F46E5"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/></patternFill></fill>
</fills>
<borders count="2"><border/><border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right><top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom></border></borders>
<cellStyleXfs count="1"><xf/></cellStyleXfs>
<cellXfs count="9">
<xf xfId="0" numFmtId="0" fontId="0" fillId="0" borderId="0"/>
<xf xfId="0" numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
<xf xfId="0" numFmtId="164" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>
<xf xfId="0" numFmtId="0" fontId="2" fillId="3" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>
<xf xfId="0" numFmtId="0" fontId="2" fillId="4" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>
<xf xfId="0" numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
<xf xfId="0" numFmtId="0" fontId="3" fillId="0" borderId="0" applyFont="1"/>
<xf xfId="0" numFmtId="0" fontId="4" fillId="0" borderId="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
<xf xfId="0" numFmtId="0" fontId="5" fillId="0" borderId="0" applyFont="1"/>
</cellXfs>
</styleSheet>"""


def ten_cot(i):
    s, i = "", i + 1
    while i:
        i, r = divmod(i - 1, 26)
        s = chr(65 + r) + s
    return s


def o_chu(ref, chu, kieu=5):
    return f'<c r="{ref}" t="inlineStr" s="{kieu}"><is><t xml:space="preserve">{escape(str(chu))}</t></is></c>'


def o_so(ref, so):
    return f'<c r="{ref}" s="5"><v>{so}</v></c>'


def o_ngay(ref, n):
    cong_thuc = "TODAY()" if n == 0 else f"TODAY(){'+' if n > 0 else '-'}{abs(n)}"
    return f'<c r="{ref}" s="2"><f>{cong_thuc}</f></c>'


def trang_xml(cac_dong, rong, dong_bang=0):
    cols = "".join(f'<col min="{i + 1}" max="{i + 1}" width="{w}" customWidth="1"/>' for i, w in enumerate(rong))
    pane = (f'<pane ySplit="{dong_bang}" topLeftCell="A{dong_bang + 1}" activePane="bottomLeft" state="frozen"/>'
            if dong_bang else "")
    rows = "".join(f'<row r="{r + 1}">{"".join(c)}</row>' for r, c in enumerate(cac_dong))
    return ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            f'<sheetViews><sheetView workbookViewId="0">{pane}</sheetView></sheetViews>'
            f'<cols>{cols}</cols><sheetData>{rows}</sheetData>'
            '<pageSetup orientation="landscape"/></worksheet>')


def dung_trang(t):
    """Dòng 1: form cần mở; dòng 2: tiêu đề cột; từ dòng 3: dữ liệu."""
    cot = [("STT", None, "so", 5), ("Loại", None, "chu", 7)] + t["cot"] + [
        ("Kết quả mong đợi", None, "chu", 52), ("Ai chặn", None, "chu", 18)]
    dong = [[o_chu("A1", "Mở: " + t["mo"], 8)], [o_chu(f"{ten_cot(j)}2", c[0], 1) for j, c in enumerate(cot)]]
    for i, (loai, gia_tri, mong, chan) in enumerate(t["dong"]):
        r = i + 3
        o = [o_so(f"A{r}", i + 1), o_chu(f"B{r}", loai, 3 if loai == "Đúng" else 4)]
        for j, ((_, _, kieu, _), v) in enumerate(zip(t["cot"], gia_tri)):
            ref = f"{ten_cot(j + 2)}{r}"
            o.append(o_ngay(ref, v) if kieu == "ngay" else o_so(ref, v) if kieu == "so" else o_chu(ref, v, 7 if kieu == "ma" else 5))
        n = len(t["cot"]) + 2
        o += [o_chu(f"{ten_cot(n)}{r}", mong), o_chu(f"{ten_cot(n + 1)}{r}", chan)]
        dong.append(o)
    return trang_xml(dong, [c[3] for c in cot], dong_bang=2)


def tao(ra=RA):
    cac_trang = [("Hướng dẫn", trang_xml([[o_chu(f"A{i + 1}", t, 6 if i == 0 else 0)] for i, t in enumerate(HUONG_DAN)], [120]))]
    cac_trang += [(t["ten"], dung_trang(t)) for t in TRANG]
    n = len(cac_trang)
    with zipfile.ZipFile(ra, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr("[Content_Types].xml",
                   '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                   '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                   '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                   '<Default Extension="xml" ContentType="application/xml"/>'
                   '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                   '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                   + "".join(f'<Override PartName="/xl/worksheets/sheet{i + 1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' for i in range(n))
                   + '</Types>')
        z.writestr("_rels/.rels",
                   '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                   '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                   '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                   '</Relationships>')
        # fullCalcOnLoad: Excel tính lại =TODAY() mỗi lần mở; file không lưu sẵn kết quả.
        z.writestr("xl/workbook.xml",
                   '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                   '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                   '<sheets>' + "".join(f'<sheet name="{escape(t)}" sheetId="{i + 1}" r:id="rId{i + 1}"/>' for i, (t, _) in enumerate(cac_trang))
                   + '</sheets><calcPr fullCalcOnLoad="1"/></workbook>')
        z.writestr("xl/_rels/workbook.xml.rels",
                   '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                   '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                   + "".join(f'<Relationship Id="rId{i + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet{i + 1}.xml"/>' for i in range(n))
                   + f'<Relationship Id="rId{n + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                   '</Relationships>')
        z.writestr("xl/styles.xml", STYLES)
        for i, (_, xml) in enumerate(cac_trang):
            z.writestr(f"xl/worksheets/sheet{i + 1}.xml", xml)
    return ra


if __name__ == "__main__":
    print("Đã tạo", tao(Path(sys.argv[1]) if len(sys.argv) > 1 else RA))
