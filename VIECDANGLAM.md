# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

### Rà giao diện lần cuối rồi đóng gói nộp hoàn chỉnh (user giao 15/11/2026: "rà soát lỗi giao diện... báo tôi rồi làm")
**Một câu:** sửa các chỗ ô lệch cỡ, xếp chưa hợp lý tìm thấy khi rà ở 3 cỡ màn hình (1440, 1024, 390), sửa README lệch menu, chụp lại ảnh, dựng lại báo cáo và `cipher.zip`.
**Đã rà (15/11, chưa sửa gì):** G1 bộ lọc Danh sách/Thống kê ở 1024–1366px: 6 ô một hàng, ô ngày còn "dd/m", ô chọn còn "Tấ", nhãn 2 dòng làm lệch hàng · G2 bảng Danh sách ở 1024px: tên và "Quá hạn 120 ngày" gãy 3 dòng · G3 Dashboard ở ~1280px: tên chính gãy 2 dòng không đều · G4 bộ lọc Nhật ký ở 1024px: "Tất c", "dd/mm" bị cắt · G5 form chứng chỉ: hàng thuật toán 3 cột giữa các hàng 2 cột, mép ô không thẳng · G6 form Máy chủ: ô Cổng rộng bằng ô tên · G7 tên trang lệch tên menu (Tài khoản của tôi / Tài khoản và mật khẩu; Thống kê / Thống kê và báo cáo; Chứng chỉ / Danh sách chứng chỉ) · G8 nhãn "Mật khẩu" thừa dấu cách · G9 9 ảnh trang giới thiệu là giao diện 09/11 (cột Dashboard thứ tự cũ, số liệu cũ) · G10 điện thoại: bộ lọc ô to ô nhỏ xen kẽ, chữ gợi ý ô tìm bị cắt.
**Coi là XONG khi:** chụp lại 1440, 1024, 390 không còn các lỗi trên; 3 lệnh nghiệm thu và `--tai-lieu` xanh; báo cáo và `D:\Music\cipher.zip` dựng lại, mở kiểm; VPS cập nhật, bước `kiem` xanh.
**CỐ Ý KHÔNG làm:** thêm nút Thêm ở Danh sách chứng chỉ (quyết định 09/11, có test); đổi màu, font, bố cục menu; đổi hành vi nghiệp vụ.

**TRẠNG THÁI (15/11/2026, phiên 2, user: "Đọc commit và làm tiếp"):** ĐÃ XONG bước 1–3 của kế hoạch (G1–G10, đã chụp kiểm 1440, 1280, 1024, 390), 9 ảnh landing chụp lại, README 5 khu, nợ README đã xoá; 3 lệnh nghiệm thu và `--tai-lieu` xanh. CÒN: chụp lại ảnh báo cáo bị đổi (`docs/anh/`), `python scripts/tao_bao_cao.py`, xem PDF, đóng gói `D:\Music\cipher.zip`, push + `cap_nhat` + `kiem`, đóng phiếu. Web tạm cổng 8765 và CSDL `cipher_baocao` đang bật (dựng ở thư mục scratchpad của phiên), xong phải tắt, xoá. Chi tiết từng chỗ (file, dòng, class), cách dựng lại môi trường chụp ảnh và danh sách ảnh phải chụp lại: thân commit "Bàn giao phiên 14–15/11" (`git log --grep "Bàn giao phiên 14"`). Công cụ chụp ảnh: `temp/cong_cu_bao_cao/`.

### Kế hoạch (user giao phiên sau làm toàn bộ, 15/11/2026)
1. `views/bo_loc.php`, `public/admin/nhat_ky.php`: lưới bộ lọc 3 ô một hàng dưới 1400px (Nhật ký dưới 1200px); điện thoại: hai ô ngày một hàng, mỗi ô chọn một hàng; chữ gợi ý ô tìm ngắn lại.
2. `public/assets/app.css`: bảng kéo ngang không xuống dòng áp dưới 1200px (đang 992px); bảng Dashboard không gãy tên. Sửa câu Luật của `test_moi_bang_quan_tri_keo_ngang_tren_dien_thoai` theo mốc mới.
3. `public/admin/chung_chi_form.php`: Thuật toán khoá + Độ dài khoá chung nửa trái, Thuật toán ký nửa phải. `may_chu_form.php`: IP + Cổng một hàng, Môi trường + Người phụ trách một hàng. G7, G8: sửa chữ.
4. Chụp lại 9 ảnh `public/assets/landing/` và các ảnh báo cáo bị đổi; README sửa bảng menu 5 khu; `python scripts/tao_bao_cao.py`; đóng gói lại `D:\Music\cipher.zip` (lệnh ở đầu `.gitattributes`); `git push` + `cap_nhat` + `kiem`.
- Rủi ro: sửa CSS chung có thể lệch trang khác ⇒ chụp lại đủ 3 cỡ. Lùi: `git revert`, đóng gói lại. Bản web tạm cổng 8765 và CSDL `cipher_baocao` đã tắt, đã xoá 15/11; phiên sau dựng lại theo thân commit bàn giao, xong việc lại tắt, xoá.
