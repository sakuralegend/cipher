# 💳 SỔ NỢ KỸ THUẬT
> Nợ = việc BIẾT phải làm mà cố ý hoãn. MỘT nợ = MỘT dòng. Cột Hết hạn BẮT BUỘC.
> Trả xong thì XOÁ dòng (lịch sử ở `git log`). Không ghi vào đây: sự thật môi trường đã quyết
> "để yên" (ở bảng BÁC).

| Nợ | Vì sao chưa trả | Hết hạn |
|---|---|---|
| Đường vào quản trị VPS khi đang ở Nga, phòng VPN, SSH, Telegram cùng bị chặn | Chưa đến lượt, self-SNI vừa xong 07/11/2026 | Trước ngày bay, cuối 11/2026 |
| Báo cáo phải nêu rõ phân chia nhiệm vụ từng thành viên (đề mục I.4): user cung cấp khi viết báo cáo | User làm hết, chốt cách chia sau | 14–15/11/2026 |
| Sổ tay 1–2 trang: luồng chính và câu dễ bị hỏi, để user hiểu sơ bộ trước khi nộp | Viết sau tài liệu nộp, khi chức năng đã chốt | 16/11/2026 |
| Đóng gói mã nguồn nộp bằng file nén: `git archive` kèm `export-ignore` trong `.gitattributes`, loại `.claude/`, `CLAUDE.md`, các sổ, `docs/BAN_GIAO.md`, `deploy/`, `scripts/`, `ban_do_test.py`; mở file nén kiểm tra không còn chữ VPN (bỏ qua `inter.css`: mã base64 của font trùng ngẫu nhiên chuỗi này, vô hại); IP VPS chỉ được có ở `database/seed.sql` (máy chủ của chứng chỉ thật, IP này ai tra DNS cũng thấy) | Chưa đến lúc nộp | 16/11/2026 |
| Nâng tài liệu nộp (QD-17): đặc tả từng use case (tác nhân, điều kiện, luồng chính, ngoại lệ), sơ đồ UML chuẩn thay `flowchart`, xuất ảnh ERD và Use Case. `docs/SPEC.md`, `docs/USECASE.md` phải theo kịp phiên 08/11: brand Cipher Cert (QD-24), menu dọc 4 nhóm, đọc chứng chỉ thật (mở rộng 2: 2 tab, chặn địa chỉ nội bộ, cổng TLS cho phép), ô gõ có gợi ý, trần trục Thống kê, IP thật trong Nhật ký | Chức năng vừa chốt 08/11/2026; viết một lượt cho khỏi sửa đi sửa lại | 14–15/11/2026 |
| Đề đòi "file SQL tạo CSDL": hướng dẫn cài phải nói rõ tạo CSDL trước, hoặc thêm file cài một bước | `schema.sql` cố ý không `CREATE DATABASE` (QD-14) | Khi viết hướng dẫn cài, 14–15/11/2026 |
| Đổi lại mật khẩu `admin` trên VPS thành mật khẩu mạnh: đang là `admin` để user dễ thử, web ra internet (QD-05) | User yêu cầu tạm thời 06/11/2026 | Trước khi nhập dữ liệu thật, muộn nhất trước khi nộp 17/11/2026 |
| Có cách khởi tạo sạch cho vận hành thật (0 chứng chỉ, chỉ vai trò và một Admin): `seed.sql` đang trộn tài khoản với dữ liệu mẫu | Bài nộp cần dữ liệu mẫu trước; test 0 chứng chỉ đã có | Sau khi chấm, trước khi dùng thật |
| Làm mới ngày dữ liệu mẫu trên VPS để đủ mốc 30/15/7: ngày tính lúc nạp nên lệch dần; dọn nhật ký bằng `TRUNCATE TABLE audit_logs` (trigger chặn DELETE, không chặn TRUNCATE). Không chạy `schema.sql` trên VPS (mất tài khoản, QD-14); sao lưu trước. Bản sao lưu cũ `/root/cipher-truoc-demo-20261107.sql` và `/root/cipher-truoc-nhat-ky-20261107-191023.sql` xoá khi không cần | Chưa đến lúc nộp | 16/11/2026 |
| Đổi hoặc khoá tài khoản demo đã đưa giảng viên | Chưa chấm bài | Ngay sau khi chấm, trước khi bay |
| Thử chạy web trong `htdocs` bằng Apache (cách giảng viên có thể cài); thử luôn đọc chứng chỉ thật ở đó (XAMPP có openssl, đo 08/11/2026) | Để chung ngày kiểm thử | Ngày kiểm thử 14–15/11/2026 |
| Chấm điểm chứng chỉ: bảng `security_findings` riêng hay tính từ cột thuật toán | Chức năng mở rộng, chỉ làm nếu còn thời gian (QD-18) | Trước khi làm chức năng này; bỏ nếu không làm |
| User thử VPN thật trên iPhone sau khi bật PROXY protocol (Xray `xver` 1, 08/11/2026); chạy thì xoá trên VPS `/root/xray-config.truoc-proxyproto-20261108-013729.json` (chứa khoá riêng Xray) và `/root/Caddyfile.truoc-proxyproto-20261108-013729`; hỏng thì lùi: chép lại bản sao lưu Xray, `systemctl restart xray` | Đổi lúc 01:40 sáng, user chưa thử | Đầu phiên sau, muộn nhất 10/11/2026 |
| Nạp lại `cipher_dev` ở máy (`schema.sql` + `seed.sql` + `seed_demo.sql`) trước khi chụp ảnh cho tài liệu: đang có dữ liệu cũ (`cipher-vps` cổng 8443, dòng nhật ký thử, mật khẩu `admin` khác seed); `cipher_test` và VPS thì đúng | Chỉ ảnh hưởng ảnh chụp ở máy | Trước khi chụp ảnh tài liệu, 14/11/2026 |
