# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Làm trang đăng nhập đồng bộ với landing

**Một câu:** `public/login.php` đang là khung Tabler mặc định, nút xanh, nền sáng (chụp web thật 07/11/2026); đổi cho cùng phong cách landing.
**User đã chốt:** muốn xử lý trang login; chưa chốt kiểu dáng.
**Coi là XONG khi:** user duyệt qua ảnh sáng, tối, điện thoại; đăng nhập, sai mật khẩu, bị khoá tạm vẫn chạy đúng.
**CỐ Ý KHÔNG làm:** đổi logic đăng nhập, chặn dò mật khẩu.
**Đã có sẵn:** `.ld-nut-chinh`, `--ld-net` và nền tối của landing trong `app.css`.

### Kế hoạch (duyệt 07/11/2026, user chọn B)
- A (đề xuất): cả trang luôn nền tối như hero (chấm lưới, quầng xanh), thẻ form giữa, nút `.ld-nut-chinh`; bỏ nút đổi giao diện ở trang này.
- B: chia đôi, trái nền tối kèm khẩu hiệu, phải là form theo sáng/tối; điện thoại thì nửa trái thành dải mỏng. Thêm khoảng gấp đôi CSS.
- Cả hai: góc trên trái có link "← Trang giới thiệu" về `index.php`; giữ nguyên tên ô, CSRF, `$loi`.
- File: `public/login.php` (chỉ HTML), `app.css` (mục Đăng nhập, gộp nền chấm lưới đang lặp ở hero và dải cuối thành một quy tắc), `views/dau_trang.php` (chú thích `$coThanhRieng`).
- Kiểm: ba lệnh nghiệm thu; chụp 1440 và 390 px, sáng và tối; đăng nhập đúng, sai mật khẩu, bị tạm chặn bằng trình duyệt thật. Không thêm test, không QD (chỉ đổi giao diện).
- Lùi: `git revert` một commit, không đụng CSDL.
- **Trạng thái 07/11:** code xong, nghiệm thu xanh, đã commit; đăng nhập đúng/sai/tạm chặn thử bằng curl đạt. CHỜ user duyệt ảnh, rồi deploy + đóng phiếu.
