# 💳 SỔ NỢ KỸ THUẬT
> Nợ = việc BIẾT phải làm mà cố ý hoãn. MỘT nợ = MỘT dòng. Cột Hết hạn BẮT BUỘC.
> Trả xong thì XOÁ dòng (lịch sử ở `git log`). Không ghi vào đây: sự thật môi trường đã quyết
> "để yên" (ở bảng BÁC). Hai vùng riêng (user chốt 14/11/2026): bài nộp và web; VPN và hạ tầng VPS chung.

## Bài nộp và web Cipher Cert

| Nợ | Vì sao chưa trả | Hết hạn |
|---|---|---|
| Dựng lại tài liệu nộp một lần rồi làm bản Word/PDF. SPEC, ERD, USECASE, KIEM_THU đã xoá 09/11/2026 (bản cũ làm khung: `git show 4f8e340:docs/SPEC.md`). README từ 13/11/2026 chỉ dành cho người chấm (CHAY.bat, cài tay, dùng hệ thống): mục 1, 4, 5 (sổ tay luồng code), 6 (phân công: chỉ họ tên Nguyễn Đức Trung, Phan Tuấn Anh, Nguyễn Chí Kiên, không mã sinh viên, user chốt 14/11/2026) của bản cũ `git show 50721c2:README.md` phải vào báo cáo. Rà `git log` từ `21ae7cb` (lần sửa tài liệu cuối, 09/11/2026), viết lại đoạn chữ cho khớp hành vi mới, chạy `php tests/chay.php --tai-lieu` tới khi xanh (số test, bảng KIEM_THU, tên hàm, link, không chữ VPN). Word/PDF: dựng từ `README.md` và `docs/`, xuất sơ đồ Mermaid (ERD, trình tự, trạng thái) thành ảnh vì Word không vẽ được; số test trong `docs/KIEM_THU.md` phải khớp lần chạy cuối. Báo cáo mục (f) hướng dẫn cài: chép phần cài của README; ghi 5 header bảo mật của máy chủ thật (CSP, HSTS, X-Frame-Options, nosniff, Referrer-Policy) kèm ảnh header lấy từ VPS, vì gói nộp không có `deploy/` | User chốt 09/11/2026: tài liệu nộp chỉ dựng một lần trước khi nộp | 16/11/2026 |
| Sau khi chấm: đổi mật khẩu `admin`, `nhanvien` trên VPS (từ 14/11/2026 là `Cipher@2026`, công khai trong README và file nén, QD-05); chấm muộn thì trước đó chạy lại bước `nap_mau` cho đủ mốc 30/15/7. Xoá sao lưu khi không cần: `/root/cipher-truoc-*.sql` (bản `cipher-truoc-nap-mau-*` giữ 51 site thật user nhập và tài khoản NCK) và `/etc/caddy/Caddyfile.truoc-header-20261113-001003` | User chốt 14/11/2026: web thật đồng nhất với bản nộp, kể cả mật khẩu, tới khi chấm xong | Ngay sau khi chấm, trước khi bay |
| Có cách khởi tạo sạch cho vận hành thật (0 chứng chỉ, chỉ vai trò và một Admin): `seed.sql` đang trộn tài khoản với dữ liệu mẫu | Bài nộp cần dữ liệu mẫu trước; test 0 chứng chỉ đã có | Sau khi chấm, trước khi dùng thật |

## VPN và hạ tầng VPS chung (không đưa vào tài liệu nộp)

| Nợ | Vì sao chưa trả | Hết hạn |
|---|---|---|
| Đường vào quản trị VPS khi đang ở Nga, phòng VPN, SSH, Telegram cùng bị chặn | Chưa đến lượt, self-SNI vừa xong 07/11/2026 | Trước ngày bay, cuối 11/2026 |
