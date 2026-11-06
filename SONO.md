# 💳 SỔ NỢ KỸ THUẬT
> Nợ = việc BIẾT phải làm mà cố ý hoãn. MỘT nợ = MỘT dòng. Cột Hết hạn BẮT BUỘC.
> Trả xong thì XOÁ dòng (lịch sử ở `git log`). Không ghi vào đây: sự thật môi trường đã quyết
> "để yên" (ở bảng BÁC).

| Nợ | Vì sao chưa trả | Hết hạn |
|---|---|---|
| Đọc cert thật phải chặn IP nội bộ, kể cả với người đã đăng nhập: VPS có AnkiConnect `127.0.0.1:8765` không mật khẩu, không chặn thì thành công cụ dò cổng | Chức năng mở rộng, làm sau cổng 11/11 | Khi làm chức năng, 12/11/2026 |
| Self-SNI: chạy Xray thử ở cổng khác, đo xem Caddy chết có kéo VPN chết không; bật PROXY protocol trên cổng nội bộ riêng để web thấy IP thật (không thì chặn dò mật khẩu khoá tất cả); đổi 443; nhập lại link Shadowrocket, v2rayN; sửa `VPS_CHUNG.md` | Không làm trước khi bảo vệ (QD-08) | 18–22/11/2026, hoặc ngay sau buổi bảo vệ nếu muộn hơn |
| Đường vào quản trị VPS khi đang ở Nga, phòng VPN, SSH, Telegram cùng bị chặn | Đợi xong self-SNI (QD-08) | Trước ngày bay, cuối 11/2026 |
| Đường dự phòng VPN: cổng khác với cấu hình kiểu cũ; quyết định có cần VPS thứ hai | Đợi xong self-SNI (QD-08) | Trước ngày bay, cuối 11/2026 |
| Thử VPN từ mạng Nga thật; từ Việt Nam không đo được DPI Nga | Chưa ở Nga | Tuần đầu sau khi sang Nga |
| Đổi mật khẩu mẫu `Cipher@2026` ngay khi nạp `seed.sql` lên VPS, vì web ra internet | Chưa lên VPS | Phiếu deploy 7/11/2026 |
| Trên VPS: tắt `display_errors` để lỗi không lộ đường dẫn, kiểm cookie `cipher_sid` có `Secure` khi chạy HTTPS | Chưa lên VPS, `localhost` không có HTTPS | Phiếu deploy 7/11/2026 |
| Đề đòi "file SQL tạo CSDL": hướng dẫn cài phải nói rõ tạo CSDL trước, hoặc thêm file cài một bước | `schema.sql` cố ý không `CREATE DATABASE` (QD-14) | Khi viết hướng dẫn cài, 14–15/11/2026 |
| Đổi hoặc khoá tài khoản demo đã đưa giảng viên | Chưa chấm bài | Ngay sau khi chấm, trước khi bay |
| Thử chạy web trong `htdocs` bằng Apache (cách giảng viên có thể cài) | Chưa có web | Ngày kiểm thử 14–15/11/2026 |
| Chấm điểm chứng chỉ: bảng `security_findings` riêng hay tính từ cột thuật toán | Chức năng mở rộng, làm sau cổng 11/11 | Khi làm chức năng chấm điểm, 13/11/2026 |
