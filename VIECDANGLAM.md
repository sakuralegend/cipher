# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

### Làm nốt phần trước khi nộp + hướng dẫn cài đặt có ảnh (user giao 15/11/2026: "làm nốt phần trước khi nộp đi, thêm cả hướng dẫn chi tiết có ảnh nữa. Cần thì hướng dẫn tôi chụp")
**Một câu:** sửa hai chỗ nhỏ còn lại sau đợt rà giao diện và thêm ảnh thật (cửa sổ `CHAY.bat`, XAMPP Control Panel, phpMyAdmin) vào Phần VI của báo cáo, rồi dựng lại báo cáo, gói nộp, VPS.
**Coi là XONG khi:** (1) ô Sắp xếp theo không còn bị che chữ ở 768–1050px; (2) ảnh "sau khi gia hạn" thấy bảng Lịch sử gia hạn như chú thích; (3) Phần VI có ảnh từng bước của Cách 1 và Cách 2, chụp từ máy thật; (4) 3 lệnh nghiệm thu và `--tai-lieu` xanh; báo cáo, `D:\Music\cipher.zip` dựng lại; VPS cập nhật, `kiem` xanh.
**CỐ Ý KHÔNG làm:** đổi `CHAY.bat`, đổi hành vi web; xoá hay nạp lại `cipher_dev` (ảnh `CHAY.bat` chụp ở lần bấm sau, giữ dữ liệu); sửa cấu hình XAMPP của máy; đưa ảnh vào `cipher.zip` (README trong gói vẫn là chữ).

### Kế hoạch
1. `views/bo_loc.php`: dưới 1200px hai ô một hàng, 1200–1399px ba ô, từ 1400px sáu ô (ba ô ở 768–1199px thì ô chọn chỉ 208px, lựa chọn dài nhất cần 217px).
2. Chụp lại `gia_han_xong` cao hơn; dựng lại môi trường chụp như thân commit 39a46aa (CSDL tạm `cipher_baocao`, cổng 8765), xong tắt, xoá.
3. Ảnh cài đặt: cửa sổ `CHAY.bat` (chạy bản giải nén ở thư mục tạm, chờ 10 giây để giữ dữ liệu, chụp, đóng); XAMPP Control Panel (bật Apache tạm để hai dòng cùng xanh, chụp, tắt Apache, đóng); phpMyAdmin (trang tạo CSDL, tab Import, dòng báo nhập xong; trên CSDL tạm, không phải `cipher_dev`). Không chụp được cái nào thì hướng dẫn user chụp cái đó.
4. `docs/BAO_CAO.md` Phần VI: chèn ảnh theo bước; `python scripts/tao_bao_cao.py`; so PDF; đóng gói; push + `cap_nhat` + `kiem`.
- Rủi ro: bật Apache tạm trên máy user (cổng 80), `CHAY.bat` mở một tab trình duyệt. Lùi: `git revert`; ảnh cũ còn trong git.
