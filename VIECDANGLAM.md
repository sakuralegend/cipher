# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Nhật ký hoạt động (chức năng mở rộng 1)

**Một câu:** mọi thao tác thay đổi dữ liệu, đăng nhập (đúng, sai), đăng xuất đều để lại một dòng: ai, làm gì, với đối tượng nào, lúc nào; Admin xem ở trang Nhật ký; không ai sửa hay xoá được.
**User đã chốt (07/11/2026):** ghi tất cả · chỉ Admin xem · chặn sửa/xoá cả ở CSDL bằng trigger (bước VPS hỏi riêng lúc deploy).
**Coi là XONG khi:** thao tác thật trên VPS hiện đúng dòng trong Nhật ký; lệnh sửa/xoá nhật ký thẳng vào CSDL bị từ chối; nhân viên không vào được trang.
**CỐ Ý KHÔNG làm:** lưu giá trị trước/sau từng trường; dữ liệu mẫu nhật ký; xuất CSV nhật ký; lấy IP thật trên VPS (khách đi qua lớp chuyển tiếp ở cổng 443 nên IP luôn là 127.0.0.1, đổi cấu hình 443 phải hỏi).
**Đã có sẵn:** bảng `audit_logs` (không cần đổi cột); khoá ngoại tới `users` là RESTRICT nên ai đã có nhật ký thì chỉ khoá được, không xoá được (đã có test và thông báo).

### Kế hoạch — CHỜ DUYỆT
1. `database/schema.sql`: 2 trigger từ chối UPDATE và DELETE trên `audit_logs` (mỗi trigger một câu, chạy được bằng bộ nạp của test).
2. `src/nhat_ky.php` (mới, cùng kiểu các file nghiệp vụ auth/chung_chi/danh_muc/nguoi_dung): `ghiNhatKy()` tự đọc người dùng từ phiên và IP; bảng nhãn hành động tiếng Việt; `layNhatKy()` lọc theo người, hành động, khoảng ngày, phân trang 50 dòng.
3. Gọi `ghiNhatKy()` ngay trong các hàm đang ghi dữ liệu ở `src/` (chứng chỉ, danh mục, người dùng, đăng nhập/đăng xuất), không rải ở từng trang, để không trang nào quên ghi. Đăng nhập sai: người để trống, chi tiết là tên đã gõ.
4. `public/admin/nhat_ky.php` (mới, chỉ Admin) + mục menu "Nhật ký" cạnh "Người dùng" trong `views/dau_trang.php`.
5. Test: sửa `test_khong_xoa_duoc_nguoi_dung_da_co_lich_su` (hết xoá được nhật ký, dùng người mới chưa có nhật ký); 3 test mới: mọi thao tác đều ghi · CSDL từ chối sửa/xoá · chỉ Admin vào trang. `docs/SPEC.md` mục 8 ghi chi tiết.
6. VPS (hỏi lại trước khi chạy): sao lưu CSDL → tạo 2 trigger bằng root → `cap_nhat` → `kiem`.
- Lùi: `git revert`; trên VPS `DROP TRIGGER` hai cái, dữ liệu không mất.
