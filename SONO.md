# 💳 SỔ NỢ KỸ THUẬT
> Nợ = việc BIẾT phải làm mà cố ý hoãn. MỘT nợ = MỘT dòng. Cột Hết hạn BẮT BUỘC.
> Trả xong thì XOÁ dòng (lịch sử ở `git log`). Không ghi vào đây: sự thật môi trường đã quyết
> "để yên" (ở bảng BÁC).

| Nợ | Vì sao chưa trả | Hết hạn |
|---|---|---|
| Đọc cert thật phải chặn IP nội bộ, kể cả với người đã đăng nhập: VPS có AnkiConnect `127.0.0.1:8765` không mật khẩu, không chặn thì thành công cụ dò cổng | Chức năng mở rộng, chỉ làm nếu còn thời gian (QD-18) | Trước khi làm chức năng này; bỏ nếu không làm |
| Self-SNI: chạy Xray thử ở cổng khác, đo xem Caddy chết có kéo VPN chết không; bật PROXY protocol trên cổng nội bộ riêng để web thấy IP thật (không thì chặn dò mật khẩu khoá tất cả); đổi 443; nhập lại link Shadowrocket, v2rayN; sửa `VPS_CHUNG.md` | Không làm trước khi nộp và chấm xong (QD-08) | 18–22/11/2026, hoặc ngay sau khi chấm nếu muộn hơn |
| Đường vào quản trị VPS khi đang ở Nga, phòng VPN, SSH, Telegram cùng bị chặn | Đợi xong self-SNI (QD-08) | Trước ngày bay, cuối 11/2026 |
| Đường dự phòng VPN: cổng khác với cấu hình kiểu cũ; quyết định có cần VPS thứ hai | Đợi xong self-SNI (QD-08) | Trước ngày bay, cuối 11/2026 |
| Thử VPN từ mạng Nga thật; từ Việt Nam không đo được DPI Nga | Chưa ở Nga | Tuần đầu sau khi sang Nga |
| Báo cáo phải nêu rõ phân chia nhiệm vụ từng thành viên (đề mục I.4): user cung cấp khi viết báo cáo | User làm hết, chốt cách chia sau | 14–15/11/2026 |
| Sổ tay 1–2 trang: luồng chính và câu dễ bị hỏi, để user hiểu sơ bộ trước khi nộp | Chức năng chưa đủ | 16/11/2026 |
| Đóng gói mã nguồn nộp bằng file nén: `git archive` kèm `export-ignore` trong `.gitattributes`, loại `.claude/`, `CLAUDE.md`, các sổ, `docs/BAN_GIAO.md`, `deploy/`, `scripts/`, `ban_do_test.py`; mở file nén kiểm tra không còn chữ VPN (bỏ qua `inter.css`: mã base64 của font trùng ngẫu nhiên chuỗi này, vô hại); IP VPS chỉ được có ở `database/seed.sql` (máy chủ của chứng chỉ thật, IP này ai tra DNS cũng thấy) | Chưa đến lúc nộp | 16/11/2026 |
| Nâng tài liệu nộp (QD-17): đặc tả từng use case (tác nhân, điều kiện, luồng chính, ngoại lệ), sơ đồ UML chuẩn thay `flowchart`, xuất ảnh ERD và Use Case | Bản hiện tại đủ làm danh sách kiểm tra; chức năng còn có thể đổi | 14–15/11/2026 |
| Đề đòi "file SQL tạo CSDL": hướng dẫn cài phải nói rõ tạo CSDL trước, hoặc thêm file cài một bước | `schema.sql` cố ý không `CREATE DATABASE` (QD-14) | Khi viết hướng dẫn cài, 14–15/11/2026 |
| Đổi lại mật khẩu `admin` trên VPS thành mật khẩu mạnh: đang là `admin` để user dễ thử, web ra internet (QD-05) | User yêu cầu tạm thời 06/11/2026 | Trước khi nhập dữ liệu thật, muộn nhất trước khi nộp 17/11/2026 |
| Có cách khởi tạo sạch cho vận hành thật (0 chứng chỉ, chỉ vai trò và một Admin): `seed.sql` đang trộn tài khoản với dữ liệu mẫu | Bài nộp cần dữ liệu mẫu trước; test 0 chứng chỉ đã có | Sau khi chấm, trước khi dùng thật |
| Làm mới ngày dữ liệu mẫu trên VPS để đủ mốc 30/15/7: ngày tính lúc nạp nên lệch dần. Không chạy `schema.sql` trên VPS (mất tài khoản, QD-14); sao lưu trước. Bản sao lưu cũ `/root/cipher-truoc-demo-20261107.sql` xoá khi không cần | Chưa đến lúc nộp | 16/11/2026 |
| Đổi hoặc khoá tài khoản demo đã đưa giảng viên | Chưa chấm bài | Ngay sau khi chấm, trước khi bay |
| Thử chạy web trong `htdocs` bằng Apache (cách giảng viên có thể cài) | Chưa có web | Ngày kiểm thử 14–15/11/2026 |
| Chấm điểm chứng chỉ: bảng `security_findings` riêng hay tính từ cột thuật toán | Chức năng mở rộng, chỉ làm nếu còn thời gian (QD-18) | Trước khi làm chức năng này; bỏ nếu không làm |
