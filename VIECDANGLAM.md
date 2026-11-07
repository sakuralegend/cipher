# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Chấm "Còn hiệu lực" trên Dashboard có vòng sáng lan ra

**Một câu:** chấm xanh lá của thẻ số "Còn hiệu lực" trên Dashboard có một vòng mờ tỏa ra rồi tan, khoảng 2 giây một lần, như đèn "đang chạy".
**User đã chốt (07/11/2026):** chỉ chấm Còn hiệu lực · kiểu vòng sáng lan ra, không nhấp nháy tắt/bật · chỉ thẻ số Dashboard. Phiếu login v2 đã đóng (user duyệt, đang chạy trên VPS ở cc3dfe2).
**Coi là XONG khi:** user thấy hiệu ứng trên iPhone; các chấm khác đứng yên; máy bật giảm chuyển động và bản in thì đứng yên.
**CỐ Ý KHÔNG làm:** hiệu ứng cho nhãn trạng thái trong danh sách chứng chỉ, bảng "Cần xử lý ngay"; chấm khác màu.
**Đã có sẵn:** vòng lặp `TRANG_THAI` ở `public/admin/index.php:31`, chấm là `.badge.badge-empty` của Tabler.

### Kế hoạch — CHỜ DUYỆT
1. `public/admin/index.php`: thêm lớp `cham-song` cho chấm khi khoá là `valid`.
2. `app.css` (mục Thẻ số Dashboard): `.cham-song` có vòng sáng bằng `box-shadow` chạy lặp 2 giây; tắt khi giảm chuyển động và khi in.
- Test: "Chỉ chấm Còn hiệu lực trên Dashboard có vòng sáng, và tắt khi giảm chuyển động".
- Kiểm: ba lệnh nghiệm thu; chụp Dashboard sáng/tối; deploy + kiem để user xem trên iPhone.
- Lùi: `git revert` một commit.
