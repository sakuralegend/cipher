# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Đăng xuất xong thì về landing

**Một câu:** bấm Đăng xuất trong menu tài khoản thì về trang giới thiệu (`index.php`) thay vì trang đăng nhập.
**User đã chốt (07/11/2026):** "Khi đăng xuất thì hãy về trang landing".
**Coi là XONG khi:** trên VPS bấm Đăng xuất về landing, nút trên landing là "Đăng nhập" (không còn "Vào trang quản trị"); vào lại trang quản trị bị đá về login.
**CỐ Ý KHÔNG làm:** thêm lời nhắn "Đã đăng xuất"; đổi cách đăng xuất (vẫn chỉ POST kèm CSRF).
**Đã có sẵn:** `public/logout.php` gọi `dangXuat()` rồi `chuyenHuong('login.php')`.

### Kế hoạch — CHỜ DUYỆT
1. `public/logout.php`: đổi đích chuyển hướng thành `index.php`.
- Test: "Đăng xuất xong về landing" (đọc code logout.php, bỏ chú thích).
- Kiểm: ba lệnh nghiệm thu; thử bằng curl: đăng nhập, đăng xuất ra 302 về index.php, landing hiện nút "Đăng nhập", vào admin bị đá về login.
- Lùi: `git revert` một commit.
