# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

### Báo cáo nộp Word/PDF (trả nợ "Dựng lại tài liệu nộp" trong SONO.md, hạn 16/11/2026; user giao 14/11/2026)
**Một câu:** một file báo cáo Word và một bản PDF, đủ mục (c) phân tích yêu cầu và mô tả chức năng, (d) ERD, (e) Use Case, (f) hướng dẫn cài đặt, cấu hình, sử dụng, (g) báo cáo kiểm thử, kèm bảng phân công; nội dung khớp code hôm nay (135 test).
**User đã chốt (14/11/2026):** bìa ghi Học viện An ninh nhân dân, môn Lập trình máy tính, lớp B12D57, 3 họ tên (không mã sinh viên); tên bài AI chọn = tên đề 69 nguyên văn; không có mẫu của trường nên theo 5 mục đề yêu cầu, Times New Roman 13, A4, có mục lục.
**Coi là XONG khi:** `php tests/chay.php --tai-lieu` xanh; 3 lệnh nghiệm thu xanh; PDF mở ra đủ mục, sơ đồ và ảnh giao diện mới đọc rõ (AI tự xem từng trang); không có chữ VPN, Xray; số test, 10 giây, nhãn "Ngày cấp" khớp code.
**CỐ Ý KHÔNG làm:** sửa code web, README, gói `cipher.zip`; đổi gì trên VPS (chỉ đọc header qua HTTPS); đưa `docs/HOC_TU_DAU.md` vào báo cáo (chỉ sửa 2 chỗ lệch); đưa file Word/PDF vào git.

**User bổ sung (14/11/2026):** báo cáo phải có ảnh minh hoạ, chữ không thì không trực quan ⇒ mỗi chức năng, mỗi use case chính có ảnh màn hình thật đặt ngay cạnh đoạn mô tả (có số hình, chú thích), cộng sơ đồ; hướng dẫn cài và kiểm thử cũng có ảnh.

### Kế hoạch (duyệt 14/11/2026, kèm bổ sung ảnh) và TIẾN ĐỘ, để phiên bị ngắt thì làm tiếp được
- ĐÃ XONG, đã commit (15/11): `docs/SPEC.md`, `ERD.md`, `USECASE.md`, `KIEM_THU.md`, `BAO_CAO.md` (khung báo cáo, kéo 4 file kia vào), 34 ảnh thật ở `docs/anh/`, `docs/usecase.svg`, sửa 13 chỗ lệch trong `HOC_TU_DAU.md`, `scripts/tao_bao_cao.py`. `--tai-lieu` xanh, 135 test xanh. Bản dựng thử ra 98 trang.
- CÒN LẠI: (1) xem hết các trang PDF dựng thử, sửa chỗ trình bày xấu (ERD có đủ lớn không, sơ đồ trình tự, bảng test); (2) chạy `python scripts/tao_bao_cao.py` (không tham số) để ra `D:\Music\BAO_CAO_De69.docx`, `.pdf` cạnh `cipher.zip`; (3) xoá CSDL tạm `cipher_baocao`, tắt `php -S` cổng 8765; (4) nghiệm thu, xoá dòng nợ "Dựng lại tài liệu nộp" trong SONO.md, để trống phiếu này, commit, `git push`.
- Cách dựng lại: sửa chữ trong `docs/*.md` rồi chạy `python scripts/tao_bao_cao.py`. Bảng test trong `KIEM_THU.md` và sơ đồ trong `ERD.md` sinh từ code (câu Luật, `--erd`): đổi test thì sửa tay theo dòng đỏ của `--tai-lieu`. Ảnh chụp bằng Chrome chạy ngầm trên bản web riêng nạp CSDL mẫu; công cụ chụp chỉ nằm ở thư mục tạm của phiên 14/11 (`%TEMP%\claude\...\scratchpad\cong_cu`), mất cũng không sao vì ảnh đã ở `docs/anh/`.
- Báo user khi xong: README (trong `cipher.zip`) ghi menu 4 nhóm, thực tế là 5 khu (Tổng quan, Cảnh báo, Chứng chỉ, Hạ tầng, Hệ thống); báo cáo đã ghi đúng, README chưa sửa vì sửa thì phải đóng gói lại. Lùi: `git revert` các commit của phiếu, xoá 2 file ở `D:\Music`.
