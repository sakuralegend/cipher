# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## ERD + file SQL 9 bảng + dữ liệu mẫu tối thiểu

**Một câu:** khung dữ liệu cho cả hệ thống, dùng làm nền cho mọi chức năng và là sản phẩm bàn giao (ERD, file SQL).
**User đã chốt:** 9 bảng theo mục 5 `docs/BAN_GIAO.md` · số ngày còn lại và trạng thái hạn dùng tính từ `valid_to`, không lưu cứng · MariaDB (QD-09).
**Coi là XONG khi:** chạy file SQL trên XAMPP ra đủ 9 bảng và dữ liệu mẫu; xem được ERD; mỗi mốc 30/15/7, còn hạn, hết hạn, thu hồi đều có ít nhất một chứng chỉ mẫu.
**CỐ Ý KHÔNG làm:** giao diện, đăng nhập, cài lên VPS, Use Case và SPEC (phiếu sau), dữ liệu mẫu đủ 30–50 chứng chỉ (lịch 13/11).
**Đã đo:** trùng ĐÃ ĐO RỒI BÁC? không · hàm gần giống? không có, repo chưa có code.

### Kế hoạch — ĐÃ TRÌNH 06/11, CHƯA DUYỆT, CẤM CODE. Toàn văn: `git log --grep "Bàn giao phiên"`
Sản phẩm: `database/schema.sql` + view `v_certificates` · `database/seed.sql` (ngày theo `NOW()`) · `docs/ERD.md` (Mermaid) · `tests/chay.php` (PHP thuần, CSDL `cipher_test`).
Chờ user chốt: `alerts` thêm `valid_to` vào UNIQUE · gia hạn sửa trên cùng dòng, `renewals` lưu fingerprint + serial cũ · người có lịch sử chỉ khoá, không xoá (đề đòi "xoá": user có thể đổi).
