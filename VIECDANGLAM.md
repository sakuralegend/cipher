# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Làm lại giao diện toàn web theo phong cách tối giản nền tối (dark SaaS minimalism, kiểu betterstack.com)

**Một câu:** user chốt 09/11/2026: CHỈ giao diện tối, bỏ sáng và nút đổi (lật QD-21) · một màu nhấn tím-chàm `#7C87F7` trên nền `#0B0C14`, thẻ `#171926`, chữ `#C9D3EE`/`#939DB8`, viền 1px mờ (logo cyan đổi theo) · landing có ảnh chụp hệ thống nghiêng 3D mờ dần vào nền · giữ Inter (QD-22).
**Cách làm:** TỪNG BƯỚC, mỗi bước deploy lên VPS rồi DỪNG cho user xem trên iPhone (khổ 390px), duyệt mới sang bước sau. Bước 1 = phần đầu landing. User chọn làm ngay cả khu quản trị, không chờ cổng 11/11.
**Đúng là:** nhìn ra cùng tinh thần với ảnh preview Better Stack · nhãn trạng thái, biểu đồ đọc rõ trên nền tối · 19 chức năng chạy y như cũ, nghiệm thu xanh sau mỗi bước.
**CỐ Ý KHÔNG làm:** đổi bố cục hay luồng chức năng · thêm thư viện, font trả phí (Helvetica Now) · bỏ Tabler (QD-13) · bản in báo cáo vẫn nền trắng · hiệu ứng ngoài landing, đăng nhập (QD-23).

### Kế hoạch (duyệt 09/11/2026, hero phương án B: chữ giữa, ảnh nghiêng dưới; bỏ mọi hiệu ứng cuộn, phim thẻ tự chạy; ảnh = khung giữ chỗ, chụp ở B6)
✅ B1 phần đầu landing, biến `--cc-*` + vùng `.ld-vung` trong `app.css` · ✅ logo "vòng thời hạn" (`$thuongHieu`), bỏ nền chấm cả web, bỏ 3 hình minh hoạ vẽ tay (thẻ tính năng chờ ảnh thật kiểu thẻ Tracing của Better Stack) · ✅ màu trạng thái `--cc-xanh/vang/cam/do` theo Better Stack, thẻ chỉ tô chi tiết nhỏ (phương án C; B5 áp cho nhãn, biểu đồ quản trị) (CHỜ USER XEM) · B2 phần còn lại landing (mục ảnh thành hàng chữ + ảnh kiểu Better Stack, bỏ `dashboard-*.webp`) · B3 đăng nhập · B4 bỏ giao diện sáng cả web (`dau_trang.php`, `--cc-*` lên `:root` cho cả web, logo; in vẫn trắng; QD-25 thay QD-21, sửa test màu landing nhắc QD-21) · B5 quản trị: menu, bảng, form, nhãn, biểu đồ (`app.js`) · B6 chụp ảnh thật vào khung giữ chỗ, rà 390px. Mỗi bước: nghiệm thu, commit, deploy, DỪNG chờ user xem.
