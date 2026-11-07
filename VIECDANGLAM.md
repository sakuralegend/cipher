# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Làm trang đăng nhập đồng bộ với landing

**Một câu:** `public/login.php` đang là khung Tabler mặc định, nút xanh, nền sáng (chụp web thật 07/11/2026); đổi cho cùng phong cách landing.
**User đã chốt:** muốn xử lý trang login và đuôi `:8443` ở phiên sau; chưa chốt kiểu dáng login.
**Coi là XONG khi:** user duyệt qua ảnh sáng, tối, điện thoại; đăng nhập, sai mật khẩu, bị khoá tạm vẫn chạy đúng.
**CỐ Ý KHÔNG làm:** đổi logic đăng nhập, chặn dò mật khẩu · đụng 443 hay Xray (QD-08).
**Câu đang chờ user:** đuôi `:8443`. Bỏ hẳn đuôi là phải có 443, mà Xray đang giữ: chỉ làm được bằng self-SNI, không trước khi nộp và chấm xong (QD-08, `VPS_CHUNG.md`). AI đề xuất: giữ `:8443` tới lúc chấm xong, làm theo nợ self-SNI 18-22/11. Muốn sớm hơn thì phải xét đưa `cert` qua dịch vụ trung gian đổi cổng: chưa đo, phải chuyển DNS khỏi pavietnam, cần hỏi trước.
**Đã có sẵn:** cổng 80 đã đá sang 8443 (đo 07/11/2026), nên gõ `cert.cipher.id.vn` không đuôi vẫn vào được · `.ld-nut-chinh`, `--ld-net` và nền tối của landing trong `app.css`.

### Kế hoạch — CHƯA TRÌNH, CẤM CODE. Việc đầu phiên mới: hỏi câu đang chờ, `kehoach.md`, chờ duyệt. Toàn cảnh: `git log --grep "Bàn giao phiên" -1`.
