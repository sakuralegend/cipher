# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

### Sửa sau đợt kiểm thử 12/11/2026 (user giao "tiến hành như đề xuất")
- P1 không đổi bảng: cảnh báo một dòng/chứng chỉ · "Hết hạn hôm nay" · dữ liệu mẫu gắn đúng tên miền · mật khẩu có chữ và số · bảng điện thoại có dấu kéo ngang · đổi mật khẩu đẩy phiên khác ra · xoá danh mục chỉ Admin · chú thích hạng A/B/C/F · thanh tiến trình nhập hàng loạt
- P2 đổi bảng, commit riêng: xoá người dùng = đánh dấu đã xoá; không đăng nhập được; nhật ký, chứng chỉ giữ tên kèm "(đã xoá)"; tên đăng nhập dùng lại được
- P3 VPS (user cho 12/11): deploy, sao lưu rồi thêm cột users, thêm HSTS + CSP vào khối cert của Caddy
### Coi là XONG khi
- 115 test cũ + test mới xanh; chụp 1440 và 390 các trang bị đổi; VPS qua bước `kiem`, không lỗi CSP ở mọi trang
### CỐ Ý KHÔNG làm
- Không ẩn cột trên điện thoại (luật commit 2d07974) · không xoá hẳn dòng users · không đụng Xray, cổng 443
### Kế hoạch (duyệt 12/11/2026)
- P1 → commit · P2 schema + QD-26 → commit · P3 VPS: sao lưu, ALTER, Caddy validate/reload, `kiem`, Chrome đọc lỗi CSP
