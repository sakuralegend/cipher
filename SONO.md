# 💳 SỔ NỢ KỸ THUẬT
> Nợ = việc BIẾT phải làm mà cố ý hoãn. MỘT nợ = MỘT dòng. Cột Hết hạn BẮT BUỘC.
> Trả xong thì XOÁ dòng (lịch sử ở `git log`). Không ghi vào đây: sự thật môi trường đã quyết
> "để yên" (ở bảng BÁC).

| Nợ | Vì sao chưa trả | Hết hạn |
|---|---|---|
| Đường vào quản trị VPS khi đang ở Nga, phòng VPN, SSH, Telegram cùng bị chặn | Chưa đến lượt, self-SNI vừa xong 07/11/2026 | Trước ngày bay, cuối 11/2026 |
| Điền họ tên, mã sinh viên 3 thành viên vào bảng phân công `README.md` mục 6 (AI đã chia đều theo nhóm chức năng 08/11/2026, đang để "Thành viên 1, 2, 3") | User cung cấp tên sau | 14–15/11/2026 |
| Đóng gói mã nguồn nộp bằng file nén: `git archive` kèm `export-ignore` trong `.gitattributes`, loại `.claude/`, `CLAUDE.md`, các sổ, `docs/BAN_GIAO.md`, `deploy/`, `scripts/`, `temp/`, `ban_do_test.py`; mở file nén kiểm tra không còn chữ VPN (bỏ qua `inter.css`: mã base64 của font trùng ngẫu nhiên chuỗi này, vô hại); IP VPS chỉ được có ở `database/seed.sql` (máy chủ của chứng chỉ thật, IP này ai tra DNS cũng thấy) | Chưa đến lúc nộp | 16/11/2026 |
| Bản Word/PDF của tài liệu nộp: dựng từ `README.md` và `docs/`, xuất sơ đồ Mermaid (ERD, trình tự, trạng thái) thành ảnh vì Word không vẽ được; số test trong README và `docs/KIEM_THU.md` phải khớp lần chạy cuối | Tài liệu `.md` xong 08/11/2026, user chốt làm Word/PDF sau | 16/11/2026 |
| Đổi lại mật khẩu `admin` trên VPS thành mật khẩu mạnh: đang là `admin` để user dễ thử, web ra internet (QD-05) | User yêu cầu tạm thời 06/11/2026 | Trước khi nhập dữ liệu thật, muộn nhất trước khi nộp 17/11/2026 |
| Có cách khởi tạo sạch cho vận hành thật (0 chứng chỉ, chỉ vai trò và một Admin): `seed.sql` đang trộn tài khoản với dữ liệu mẫu | Bài nộp cần dữ liệu mẫu trước; test 0 chứng chỉ đã có | Sau khi chấm, trước khi dùng thật |
| Nạp lại dữ liệu mẫu trên VPS (09/11/2026 đã xoá sạch để user nhập site thật, chỉ giữ `users`, `roles`; bản demo trước đó ở `/root/cipher-truoc-kiem-thu-thuc-te-20261109-140313.sql`), ngày mới để đủ mốc 30/15/7: ngày tính lúc nạp nên lệch dần; dọn nhật ký bằng `TRUNCATE TABLE audit_logs` (trigger chặn DELETE, không chặn TRUNCATE). Không chạy `schema.sql` trên VPS (mất tài khoản, QD-14); sao lưu trước. Bản sao lưu cũ `/root/cipher-truoc-demo-20261107.sql` và `/root/cipher-truoc-nhat-ky-20261107-191023.sql` xoá khi không cần | Chưa đến lúc nộp | 16/11/2026 |
| Đổi hoặc khoá tài khoản demo đã đưa giảng viên | Chưa chấm bài | Ngay sau khi chấm, trước khi bay |
| Thử chạy web trong `htdocs` bằng Apache (cách giảng viên có thể cài); thử luôn đọc chứng chỉ thật ở đó (XAMPP có openssl, đo 08/11/2026) | Để chung ngày kiểm thử | Ngày kiểm thử 14–15/11/2026 |
| Chấm điểm chứng chỉ: bảng `security_findings` riêng hay tính từ cột thuật toán | Chức năng mở rộng, chỉ làm nếu còn thời gian (QD-18) | Trước khi làm chức năng này; bỏ nếu không làm |
