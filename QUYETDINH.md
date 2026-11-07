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
| QD-23 | Chuyển động landing bằng CSS + JS thuần, chỉ ở landing | Thư viện Motion, GSAP | Hiệu ứng không cần thư viện; giữ tinh thần không CDN của QD-13 và không thêm thứ phải nuôi | Đã nộp và muốn hiệu ứng phức tạp hơn |
| QD-22 | Font Inter (OFL) tải về repo, màu chữ theo GitHub | Font hệ thống, Mona Sans, CDN | So trên nội dung thật 07/11/2026: Inter dễ đọc nhất với chữ nhỏ và tiếng Việt; không CDN như QD-13 | Đã nộp và muốn đổi font |
| QD-21 | Khu quản trị ưu tiên dễ nhìn, mặc định sáng; phần đẹp dồn vào landing | Làm đẹp đều toàn web, mặc định tối | User chốt 07/11/2026: quản trị để dùng hằng ngày, landing để gây ấn tượng | Đã nộp và muốn đổi giao diện |
| QD-20 | Chart.js 4.6.1 (MIT) tải về repo, không CDN | CDN, tự vẽ SVG | Cùng lý do QD-13: demo không phụ thuộc mạng; đề và SPEC đã ghi Chart.js | Đã nộp và muốn đổi thư viện |
| QD-19 | Code ở `/var/www/cipher`, PHP-FPM + MariaDB có trần RAM, nối khối vào `Caddyfile` chung | Caddy riêng, Docker | Ít đụng dịch vụ đang chạy nhất. Chạy lại `dungmoi.sh` của slushai sẽ xoá khối này (`deploy/dung_vps.sh caddy` thêm lại) | Chuyển VPS khác |
| QD-18 | 12–13/11 ưu tiên giao diện và dữ liệu mẫu; nhật ký nếu còn giờ; đọc cert, chấm điểm có thể bỏ | Đủ 3 chức năng mở rộng | Đề không yêu cầu 3 mục đó; mục tiêu là sản phẩm đẹp (QD-17) | Bài đã nộp |
| QD-17 | Vai DUYỆT, ưu tiên sản phẩm đẹp và SPEC, Use Case tốt | Vai HIỂU, ưu tiên code dễ giải thích | User chốt 06/11/2026: bài không có vấn đáp | Giảng viên yêu cầu vấn đáp |
| QD-16 | Mỗi trang một file PHP, địa chỉ có đuôi `.php` | Router + rewrite, MVC đầy đủ | Chạy được `php -S`, Caddy, Apache mà không cần `.htaccess` | Đã nộp và muốn phát triển tiếp |
| QD-15 | Web đặt ở `cert.cipher.id.vn` | Đặt ở `cipher.id.vn`, chuyển sau | Giữ domain chính cho định hướng sau này; link và HTTPS đưa giảng viên không phải đổi | Chuyển web sang tên miền khác |
| QD-14 | `schema.sql` DROP rồi tạo lại, không `CREATE DATABASE`/`USE` | File tự tạo CSDL | Nạp được vào cả `cipher_dev`, `cipher_test`; chạy lại trước khi nộp. Chạy nhầm trên VPS là mất dữ liệu | Có dữ liệu thật cần giữ trên VPS: khi đó phải đổi sang migrate |
| QD-13 | Giao diện Tabler 1.7.3 (MIT), tải về repo, không CDN | AdminLTE 4, CoreUI | Hợp dark mode và phong cách user muốn; không CDN để demo không phụ thuộc mạng. Đổi lại CSS nặng gấp đôi AdminLTE | Đã nộp và muốn đổi giao diện |
| QD-11 | Gia hạn sửa trên cùng dòng, lưu fingerprint + serial cũ ở `renewals` | Mỗi lần gia hạn một dòng chứng chỉ | Đơn giản, danh sách mỗi chứng chỉ một dòng | Cần tra cứu bản cũ như chứng chỉ độc lập |
| QD-10 | Repo ở `D:\Desktop\cipher`, chạy bằng `php -S` | Đặt trong `htdocs` | User muốn để ở Desktop; không dựa `.htaccess` | Bài đã nộp; trước đó phải thử chạy trong `htdocs` |
| QD-09 | MariaDB 10.11 trên VPS, 10.4 ở XAMPP | MySQL 8 | XAMPP thực chất là MariaDB nên ít lệch; báo cáo ghi rõ | Giảng viên yêu cầu đúng MySQL |
| QD-08 | Làm self-SNI trước khi nộp, thử Xray song song trước, tự hoàn tác 5 phút | Đợi chấm xong; dịch vụ trung gian | User chốt 07/11/2026: VPN phải ngụy trang đúng trang không đuôi; còn 10 ngày để sửa nếu lỗi | Đã đổi 443 xong và VPN chạy ổn |
| QD-07 | VPS chung: thêm khối Caddy nghe 8443, PHP + CSDL có trần RAM, user riêng; khách vào qua 443 của Xray (QD-08) | VPS riêng, Caddy giữ 443 | Không tốn thêm; RAM còn 1 084 MB (06/11/2026) | Chuyển sang VPS khác |
| QD-05 | Bảo mật làm thật: prepared statements, phân quyền, chặn ~5 lần sai | Làm cho đủ điểm | Web thật trên internet, chung máy với VPN và bot | Web ngừng chạy trên internet |
| QD-03 | Không có trang đăng ký công khai, admin tạo tài khoản | Cho tự đăng ký | Hệ thống nội bộ; bớt bề mặt tấn công | Hệ thống mở cho người ngoài |
| QD-02 | Back-end PHP thuần | C# / ASP.NET, framework PHP | Đề bắt buộc PHP + MySQL; một mình, thời gian ngắn, không cần framework nặng | Đã nộp và muốn phát triển tiếp |
| QD-01 | Đề 69 + mở rộng 65/66/63 rút gọn | Đề 63, 16, 64 | Gần ngành mật mã nhất, nhẹ hơn 63/16, phát triển tiếp được | Đổi hướng phát triển sau khi nộp |
