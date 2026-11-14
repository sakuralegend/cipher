# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

### Chuẩn bị cho giảng viên thử tay (user chốt 14/11/2026, góp ý sau khi xem bản Excel mẫu)
- P1 Excel nhập tay: mỗi form một trang tính, dòng Đúng và Sai, ngày `=TODAY()+N`, cột theo đúng thứ tự ô trên web (Fingerprint trước Serial). Bản mẫu trang Chứng chỉ đã duyệt hình thức, 7/7 dòng khớp web.
- P2 Ô ngày giờ nhận dán chữ từ Excel (`24/11/2026 00:00`, cả `24-11-2026`, `2026-11-24 00:00`), khỏi chọn bằng lịch.
- P3 Bộ lọc thêm "Sắp xếp theo": Gần đây (mới thêm trước / cũ trước), Hạn hết (gần trước / xa trước), Độ an toàn (kém trước / tốt trước).
- P4 Cửa sổ CHAY.bat ghi nhật ký có nghĩa: mỗi trang mở (giờ, người, trang, mã trả về, thời gian xử lý) và mỗi thao tác (đăng nhập, thêm, sửa, xoá, gia hạn, đọc chứng chỉ); bỏ dòng Accepted/Closing.
- P5 Nút Xuất CSV, Bản in lên cùng dòng tiêu đề trang (góc trên phải), màn hẹp không chiếm thêm một dòng.
- P6 Menu Chứng chỉ còn 2 mục (Danh sách, Thêm). Bấm Thêm mở Nhập thủ công; thanh tab trên: Nhập thủ công | Nhập tự động. Nhập tự động = đọc một địa chỉ (trên) + nhập hàng loạt (dưới). Đảo quyết định 09/11 "Đọc từ máy chủ mở trước".
### Kế hoạch (duyệt 14/11/2026, cả P1–P6; thứ tự P2 → P4 → P5 → P6 → P1 → P3, mỗi phần một commit)
- Test: dán ngày (kiểm có xử lý), nhật ký chỉ in dưới php -S, sắp gần đây hai chiều, sắp độ an toàn chia trang không sót không lặp. QD mới: sắp độ an toàn chia trang bằng PHP. Chụp màn hình P5, P6 ở 1440 và 390.
- Cố ý KHÔNG: đổi cách đăng nhập (phiên còn sống là đúng, đã kiểm không cookie thì bị đẩy về đăng nhập); chức năng nhập file Excel vào web.
