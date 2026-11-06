# BÀN GIAO — Bài tập lớn đề 69: Hệ thống quản lý chứng chỉ số

> Tổng hợp từ phiên lên kế hoạch trên claude.ai (05–06/11/2026). Đặt file này ở `docs/` rồi bảo
> Claude Code: *"Khởi tạo dự án theo quy trình của tôi. Vai HIỂU, mức NHẸ. Đọc hết `docs/` trước."*
> Khi khởi tạo: mục 6 chuyển thành dòng `QUYETDINH.md`, mục 9 thành phiếu `VIECDANGLAM.md` đầu tiên.
> Sau đó file này là **ảnh chụp lúc bàn giao** — không cập nhật nữa, nguồn sự thật là các sổ.

## 1. Bối cảnh

- Làm **một mình**. Hạn nộp **17/11/2026**; còn 10 ngày làm việc (06–16/11).
- Có VPS Ubuntu (2 GB RAM, 16 GB SSD) đang chạy; thuê được domain.
- User là sinh viên ngành an ninh mạng, sắp du học Nga ngành mật mã ⇒ chọn đề 69 vì gần mật mã
  nhất và có thể phát triển tiếp nhiều năm.
- Vấn đáp: **mọi thành viên phải nắm rõ toàn bộ quy trình, chức năng và mã code** ⇒ vai HIỂU.

## 2. Yêu cầu chung bắt buộc (trích đề)

**Công nghệ:** Front-end HTML5, CSS3, JavaScript · Back-end **PHP** · CSDL **MySQL (XAMPP)** · PHP xử
lý yêu cầu từ giao diện, truy vấn CSDL. Được dùng Bootstrap và thư viện JS hỗ trợ, nhưng phải hiểu
và tự triển khai được chức năng cốt lõi.

**10 chức năng nền tảng:**
a) Đăng nhập/đăng xuất: xác thực, mật khẩu mã hoá, kiểm tra trạng thái tài khoản ·
b) Quản lý người dùng: thêm, sửa, xoá, khoá; phân quyền theo vai trò ·
c) CRUD đầy đủ · d) Tìm kiếm theo từ khoá, lọc theo tiêu chí nghiệp vụ ·
e) Thống kê, báo cáo: bảng + biểu đồ · f) Kiểm tra dữ liệu bằng **cả JavaScript và PHP** ·
g) Bảng có khoá chính, khoá ngoại, quan hệ phù hợp · h) Dashboard tổng quan ·
i) Bảo mật: Prepared Statements/PDO hoặc MySQLi, chống SQL Injection, kiểm tra quyền, xử lý phiên ·
j) Dữ liệu mẫu để giảng viên kiểm thử.

**Bàn giao:** a) mã nguồn · b) file SQL tạo CSDL + dữ liệu mẫu · c) tài liệu phân tích yêu cầu, mô tả
chức năng · d) ERD · e) sơ đồ chức năng hoặc Use Case · f) hướng dẫn cài đặt, cấu hình, sử dụng ·
g) báo cáo kết quả kiểm thử.

## 3. Đề 69 (trích đề)

**Yêu cầu:** Quản lý chứng chỉ số SSL/TLS của website, máy chủ và các dịch vụ trong tổ chức.

**Chức năng:** 1. Quản lý danh sách chứng chỉ số · 2. Quản lý tên miền và máy chủ liên quan ·
3. Lưu thông tin tổ chức cấp chứng chỉ · 4. Quản lý ngày cấp và ngày hết hạn · 5. Theo dõi trạng thái
chứng chỉ · 6. Cảnh báo chứng chỉ sắp hết hạn · 7. Quản lý lịch sử gia hạn · 8. Thống kê chứng chỉ
theo trạng thái · 9. Xuất báo cáo chứng chỉ.

**Tiêu chí:** mỗi chứng chỉ có mã quản lý hoặc dấu vân tay (fingerprint) · tự động tính số ngày còn
hiệu lực · cảnh báo theo mốc **30, 15 và 7 ngày** trước khi hết hạn.

## 4. Phạm vi

- **Bắt buộc:** toàn bộ mục 2 + mục 3.
- **Mở rộng** (chỉ làm sau mốc 11/11, báo cáo ghi là "chức năng mở rộng"):
  1. Nhật ký hoạt động (từ đề 65): ai thêm/sửa/xoá/gia hạn, lúc nào; không ai sửa/xoá được nhật ký.
  2. Đọc chứng chỉ thật từ `domain:cổng` bằng PHP OpenSSL (`openssl_x509_parse`,
     `openssl_x509_fingerprint`) để tự điền form (từ đề 63, bản rút gọn).
  3. Chấm điểm an toàn chứng chỉ (từ đề 66, bản rút gọn): cảnh báo SHA-1, RSA < 2048, tự ký, chuỗi
     thiếu; nhận diện thuật toán GOST.
- **Cắt nếu trễ, theo thứ tự:** chấm điểm → đọc cert thật. Giữ nhật ký (≈ nửa ngày).
- **Ngoài phạm vi trước 17/11:** tự gia hạn ACME, quét cả dải mạng, 2FA, công cụ kiểm tra SSL công
  khai trên landing (nếu làm sau này: chặn IP nội bộ để tránh SSRF).

## 5. Thiết kế sơ bộ

**Vai trò:** Admin (toàn quyền) · Nhân viên quản trị (quản lý chứng chỉ, không quản lý người dùng).
Không có trang đăng ký công khai — admin tạo tài khoản.

**CSDL (~9 bảng).** Số ngày còn hiệu lực và trạng thái hạn dùng **tính từ `valid_to`**, không lưu cứng.

| Bảng | Trường chính | Ghi chú |
|---|---|---|
| `roles` | id, name | 1–n users |
| `users` | id, username, password_hash, full_name, role_id, is_locked | FK roles |
| `servers` | id, hostname, ip_address, port, environment, owner | 1–n domains |
| `domains` | id, domain_name, server_id | FK servers |
| `certificate_authorities` | id, name, country, is_trusted | 1–n certificates |
| `certificates` | id, cert_code, fingerprint_sha256 UNIQUE, serial, subject_cn, san, ca_id, domain_id, valid_from, valid_to, key_algorithm, key_size, signature_algorithm, is_revoked, created_by | FK CA, domains, users |
| `renewals` | id, certificate_id, old_valid_to, new_valid_to, renewed_by, renewed_at, note | lịch sử gia hạn |
| `alerts` | id, certificate_id, threshold_days, created_at, status | UNIQUE (certificate_id, threshold_days) |
| `audit_logs` | id, user_id, action, object_type, object_id, detail, ip, created_at | chỉ INSERT |

Câu hỏi mở: phần chấm điểm cần bảng `security_findings` riêng hay tính thẳng từ cột thuật toán?

**Sitemap (~25 trang):**
- Công khai: `/` landing (nút Đăng nhập góc trên) · `/login` · `/logout`
- `/admin`: Dashboard · Chứng chỉ (danh sách + lọc, thêm/sửa, chi tiết + số ngày còn lại + lịch sử
  gia hạn, gia hạn, xoá/thu hồi) · Cảnh báo · Tên miền · Máy chủ · CA · Thống kê & báo cáo (biểu đồ,
  CSV, bản in) · Người dùng (chỉ Admin) · Tài khoản cá nhân
- Mở rộng: Nhật ký · Đọc cert thật · Trang cert yếu

**Ước lượng:** bắt buộc ~40 giờ · mở rộng ~8 giờ · tài liệu + kiểm thử ~10–12 giờ ⇒ ~6 giờ/ngày.

**Giao diện:**
- Khu quản trị: template admin miễn phí **Tabler hoặc AdminLTE 4** (Bootstrap 5, MIT). Chỉ lấy
  CSS/JS + 2–3 trang mẫu làm khuôn; ghi nguồn trong báo cáo.
- Landing: một trang tĩnh; bố cục nghiêng về phương án **A** (hero 2 cột + ảnh dashboard, 3 tính
  năng, 3 bước, dải kêu gọi, footer). Ảnh dashboard = chụp từ chính trang admin khi xong.
- User muốn khai thác tối đa AI cho thiết kế: làm design tokens (biến CSS) trước → trang component →
  rồi mới ghép trang. Phong cách tham chiếu gợi ý: Linear / Vercel / Cloudflare, dark mode mặc định,
  font mono cho fingerprint/serial/domain. Lưu tokens + nguyên tắc vào `CLAUDE.md` khi chốt.

**Công nghệ triển khai:** PHP 8 thuần (MVC đơn giản), PDO, Chart.js · Dev: XAMPP · VPS: Nginx +
PHP-FPM + MySQL (giới hạn `innodb_buffer_pool_size` ~128–256 MB), domain + Let's Encrypt · Cảnh báo
bằng cron hoặc tính khi tải trang · Đặt múi giờ `Asia/Ho_Chi_Minh` cho **cả PHP và MySQL** (lệch là
sai số ngày còn lại).

## 6. Quyết định đã chốt (chuyển thành dòng `QUYETDINH.md`)

| Chọn | Thay vì | Vì |
|---|---|---|
| Đề 69 + mở rộng 65/66/63 rút gọn | Đề 63 (IP), 16 (kho), 64 (RBAC) | Gần ngành mật mã nhất, nhẹ hơn 63/16, phát triển tiếp được (PKI, thời hạn cert rút ngắn, chuyển đổi PQC) |
| Back-end PHP thuần | C# / ASP.NET | Đề bắt buộc PHP + MySQL; framework nặng thì khó giải thích khi vấn đáp |
| Không trang đăng ký công khai | Cho tự đăng ký | Hệ thống nội bộ; bớt bề mặt tấn công khi chạy thật trên internet |
| Deploy lên VPS từ ngày 7/11 | Deploy cuối kỳ | Lúc nào cũng có bản chạy được; demo bằng domain có HTTPS |
| Chuyển VPS sang Xray VLESS Reality "steal-oneself" **sau 17/11** | Làm trước khi nộp | Cấu hình lỗi không được ảnh hưởng bài nộp. Mô hình: Xray giữ 443, Nginx lùi về `127.0.0.1:8443` làm `dest` (bật TLS 1.3 + HTTP/2), Nginx giữ cổng 80 cho certbot |
| Bảo mật làm thật (prepared statements, phân quyền, chặn dò mật khẩu ~5 lần sai) | Làm cho đủ điểm | Web chạy thật trên internet, lại là lớp ngụy trang cho VPN — hack web là lộ máy chủ |
| Ưu tiên phần bắt buộc, cổng kiểm tra tối 11/11 | Làm song song mở rộng | Làm một mình, 10 ngày; điểm chấm theo đề 69 |

## 7. Lịch 10 ngày

- [ ] **6/11:** ERD, Use Case, SPEC; file SQL tạo 9 bảng
- [ ] **7/11:** layout + template, đăng nhập/đăng xuất, phân quyền, quản lý người dùng, landing; deploy VPS + domain + HTTPS
- [ ] **8/11:** CRUD máy chủ, tên miền, CA
- [ ] **9/11:** CRUD chứng chỉ, fingerprint, số ngày còn hiệu lực, validate JS + PHP
- [ ] **10/11:** trạng thái, cảnh báo 30/15/7, lịch sử gia hạn, tìm kiếm + lọc
- [ ] **11/11:** Dashboard, thống kê + biểu đồ, xuất báo cáo · **tối: CỔNG — đủ 19 chức năng bắt buộc, chạy trên VPS**
- [ ] **12/11:** nhật ký, đọc cert thật
- [ ] **13/11:** chấm điểm cert; dữ liệu mẫu 30–50 chứng chỉ đủ mọi trạng thái
- [ ] **14–15/11:** kiểm thử + báo cáo test, tài liệu phân tích, hướng dẫn cài đặt
- [ ] **16/11:** dự phòng, sửa lỗi, tập vấn đáp · **17/11 nộp**

## 8. Rủi ro

- Lịch không có chỗ trống — mất một ngày thì dùng 16/11, không lấn ngày kiểm thử.
- XAMPP Windows có thể thiếu/chặn OpenSSL ra mạng ⇒ đọc cert thật luôn có đường dự phòng: nhập tay
  hoặc upload file `.pem`.
- Dữ liệu demo: chuẩn bị chứng chỉ hết hạn đúng 7/15/30 ngày tính từ **ngày bảo vệ** để cảnh báo hiện ra.
- Sau khi chấm: đổi/khoá tài khoản demo đã đưa giảng viên.

## 9. Việc đầu tiên trong Claude Code

1. Khởi tạo quy trình bằng skill (vai HIỂU, mức NHẸ); lệnh nghiệm thu tối thiểu: `php -l` toàn bộ
   file PHP + bộ test + `python ban_do_test.py --kiem`.
2. Chuyển mục 6 vào `QUYETDINH.md`, mục 8 vào `SONO.md` nếu là nợ.
3. Phiếu việc đầu tiên: **ERD + file SQL 9 bảng + dữ liệu mẫu tối thiểu** (lịch 6/11) — đi đủ ba cửa.
