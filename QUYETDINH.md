# 📌 QUYẾT ĐỊNH (`QD-nn`)
> Một quyết định = MỘT dòng, ≤ 250 ký tự, mới nhất trên cùng. Sức chứa: 20 dòng sổ · 12 dòng BÁC.
> Toàn văn bàn bạc = thân commit: `git log --grep QD-nn`. Đây chỉ là mục lục phần CÒN SỐNG.
> Chạm trần ⇒ cho một mục chết, CẤM nén chữ. Mục được chết khi: là hành vi ⇒ đã thành test 4 dòng
> và đã thấy đỏ; hoặc lý do đã chép vào đúng chỗ người ta chắc chắn đọc lúc cần.
> Ghi QD khi: (1) chọn A thay B mà 6 tháng sau nhìn code không tự thấy lý do · (2) cố ý làm trái
> luật `CLAUDE.md` · (3) chạm schema / dữ liệu thật · (4) khai tử hoặc đóng băng một thứ.
> Sửa bug thường, chỉnh câu chữ: không ghi — commit có thân là đủ.

## 📏 ĐÃ ĐO RỒI BÁC — đọc trước khi "tối ưu" bất cứ gì
> Lật một dòng phải ĐO LẠI ra số khác. Đầy ⇒ giết dòng có ngày đo CŨ NHẤT.

| Hướng nghe hợp lý | Phán quyết | Vì (số liệu + ngày đo) |
|---|---|---|
| Cài Nginx làm máy chủ web trên VPS | BÁC | Caddy giữ 80 + 8443, Xray giữ 443 (`ss -ltnp`, đo 06/11/2026) |

## 🗂️ Sổ
| QD | Chọn | Thay vì | Vì | Hết hạn khi (trạng thái đo được, không dùng ngày tuyệt đối) |
|---|---|---|---|---|
| QD-14 | `schema.sql` DROP rồi tạo lại, không `CREATE DATABASE`/`USE` | File tự tạo CSDL | Nạp được vào cả `cipher_dev`, `cipher_test`; chạy lại trước bảo vệ. Chạy nhầm trên VPS là mất dữ liệu | Có dữ liệu thật cần giữ trên VPS: khi đó phải đổi sang migrate |
| QD-13 | Giao diện Tabler 1.7.3 (MIT), tải về repo, không CDN | AdminLTE 4, CoreUI | Hợp dark mode và phong cách user muốn; không CDN để demo không phụ thuộc mạng. Đổi lại CSS nặng gấp đôi AdminLTE | Đã bảo vệ xong và muốn đổi giao diện |
| QD-12 | Test bằng script PHP thuần `tests/chay.php` | PHPUnit | Không thêm thư viện, giải thích được từng dòng khi vấn đáp | Bộ test quá lớn để script tự chạy còn dễ đọc |
| QD-11 | Gia hạn sửa trên cùng dòng, lưu fingerprint + serial cũ ở `renewals` | Mỗi lần gia hạn một dòng chứng chỉ | Đơn giản khi vấn đáp, danh sách mỗi chứng chỉ một dòng | Cần tra cứu bản cũ như chứng chỉ độc lập |
| QD-10 | Repo ở `D:\Desktop\cipher`, chạy bằng `php -S` | Đặt trong `htdocs` | User muốn để ở Desktop; không dựa `.htaccess` | Bài đã nộp; trước đó phải thử chạy trong `htdocs` |
| QD-09 | MariaDB 10.11 trên VPS, 10.4 ở XAMPP | MySQL 8 | XAMPP thực chất là MariaDB nên ít lệch; báo cáo ghi rõ | Giảng viên yêu cầu đúng MySQL |
| QD-08 | Self-SNI cho VLESS Reality sau khi bảo vệ, thử Xray song song trước | Đổi 443 trước khi nộp | Lỗi không được làm hỏng cả VPN lẫn bài nộp | Đã đổi 443 xong và VPN chạy ổn |
| QD-07 | VPS chung: thêm khối Caddy, cổng 8443, PHP + CSDL có trần RAM, user riêng | VPS riêng, cổng 443 | Không tốn thêm, không đụng VPN; RAM còn 1 084 MB (06/11/2026) | Chuyển sang VPS khác |
| QD-06 | Làm phần bắt buộc trước, cổng kiểm tra tối 11/11 | Làm song song mở rộng | Một mình, thời gian ngắn; điểm chấm theo đề 69 | Cổng 11/11 đã qua |
| QD-05 | Bảo mật làm thật: prepared statements, phân quyền, chặn ~5 lần sai | Làm cho đủ điểm | Web thật trên internet, chung máy với VPN và bot | Web ngừng chạy trên internet |
| QD-04 | Deploy VPS từ 7/11 | Deploy cuối kỳ | Lúc nào cũng có bản chạy được; demo bằng domain có HTTPS | Bài đã nộp |
| QD-03 | Không có trang đăng ký công khai, admin tạo tài khoản | Cho tự đăng ký | Hệ thống nội bộ; bớt bề mặt tấn công | Hệ thống mở cho người ngoài |
| QD-02 | Back-end PHP thuần | C# / ASP.NET, framework PHP | Đề bắt buộc PHP + MySQL; framework nặng khó giải thích khi vấn đáp | Đã bảo vệ xong và muốn phát triển tiếp |
| QD-01 | Đề 69 + mở rộng 65/66/63 rút gọn | Đề 63, 16, 64 | Gần ngành mật mã nhất, nhẹ hơn 63/16, phát triển tiếp được | Đổi hướng phát triển sau khi nộp |
