# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Sắp xếp lại danh sách chứng chỉ cho đỡ loạn

**Một câu:** mới vào trang Chứng chỉ là thấy ngay việc gấp, chứng chỉ đã thu hồi không lẫn với chứng chỉ đang hoạt động.
**User đã chốt 07/11/2026:** mặc định xếp theo mức gấp · hàng nút trạng thái có số · làm mờ dòng thu hồi · thu gọn bộ lọc · bấm tiêu đề cột để đổi cách xếp.
**Coi là XONG khi:** thứ tự mặc định là Đã hết hạn, Sắp hết hạn, Còn hiệu lực, Chưa hiệu lực, Đã thu hồi; bấm nút trạng thái và tiêu đề cột hoạt động, chuyển trang vẫn giữ lọc và cách xếp; chữ dòng mờ đọc được ở cả sáng lẫn tối.
**CỐ Ý KHÔNG làm:** Dashboard (phiếu sau) · đổi các trang danh sách khác · nhớ cách xếp giữa các lần vào.

### Kế hoạch (trình 07/11/2026) — CHỜ DUYỆT, CẤM CODE
1. `src/chung_chi.php`: `TRANG_THAI` xếp theo mức gấp, `docSapXep()`, `danhSachChungChi()` nhận cách xếp từ danh sách cột cho phép, `demTheoTrangThai()`.
2. `public/admin/chung_chi.php`: hàng nút trạng thái, tiêu đề cột bấm được, bộ lọc thu gọn (ô từ khoá luôn hiện), liên kết giữ lọc và cách xếp. `app.css`: dòng thu hồi mờ.
3. `docs/SPEC.md` ghi thứ tự mặc định. `tests/chay.php`: sửa phần kiểm thứ tự của test danh sách cũ, thêm 3 test.
Không đổi schema, không thêm thư viện, không QD mới. Lùi lại: `git revert`, rồi `cap_nhat`.
