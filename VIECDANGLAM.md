# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Trang đăng nhập v2: chuyển cảnh từ landing, hiệu ứng như landing, màu navy + cyan

**Một câu:** bấm "Đăng nhập" ở landing thì thẻ chứng chỉ bay sang trang login; login có thẻ tự chạy vòng đời; phần tối của landing và login đổi sang navy + cyan.
**User đã chốt (07/11/2026):** navy + cyan · áp cho landing + login, quản trị giữ nguyên (QD-21) · thẻ bay sang · cột form hẹp. Màu trạng thái xanh/vàng/cam/đỏ giữ nguyên.
**Coi là XONG khi:** user duyệt ảnh sáng, tối, điện thoại và tự bấm thử chuyển cảnh; phim landing chạy như cũ; đăng nhập đúng/sai/tạm chặn vẫn đúng.
**CỐ Ý KHÔNG làm:** đổi logic đăng nhập; đổi màu trang quản trị; nối tiếp đúng số ngày của thẻ khi bay sang (login luôn chạy từ 90 ngày).
**Đã có sẵn:** v1 chia đôi ở commit 054d333 (chưa deploy); phim thẻ `veKhung`/`batTuChay` trong `app.js`; nền tối dùng chung một quy tắc trong `app.css`.

**Trạng thái 07/11:** code xong, nghiệm thu xanh 74/74, đã commit, chưa deploy. Thêm theo user giữa chừng: biểu tượng trong ô nhập + nút hiện mật khẩu. CHỜ user duyệt ảnh và tự bấm thử chuyển cảnh, rồi deploy + đóng phiếu.

### Kế hoạch (duyệt 07/11/2026)
1. `views/the_chung_chi.php` (mới): HTML thẻ chứng chỉ chuyển từ `index.php` ra, landing và login cùng gọi, khỏi chép 30 dòng.
2. `app.js`: tách phim thẻ khỏi khối landing thành hàm dùng chung; landing giữ cuộn như cũ, login chỉ tự chạy vòng 18 giây.
3. `app.css`: nền tối navy `#060d1c`, chấm lưới và quầng cyan; nút chính trên nền tối chuyển cyan sang xanh dương; màu trạng thái dùng được ở cả hai trang; bố cục login cột form 30rem có đầu và chân.
4. Chuyển cảnh: CSS `@view-transition` (có sẵn trong trình duyệt), chỉ bật ở landing và login qua cờ `$coChuyenCanh` trong `dau_trang.php`; thẻ mang cùng một tên ở hai trang nên bay sang. Firefox và máy giảm chuyển động thì chuyển trang thường.
5. `login.php`, `index.php`: đổi HTML theo các bước trên. Sửa dòng QD-23 "chỉ ở landing" thành "landing và login".
- Test: "Chuyển cảnh chỉ bật ở landing và đăng nhập, trang quản trị không" (QD-21).
- Kiểm: ba lệnh nghiệm thu; chụp landing ở nhiều độ cuộn trước/sau để so; chụp login sáng/tối/390 px; thử đăng nhập đúng/sai/tạm chặn.
- Lùi: `git revert` các commit của phiếu (cả 054d333 nếu bỏ hẳn), không đụng CSDL.
