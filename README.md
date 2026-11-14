# Cipher Cert: Hệ thống quản lý chứng chỉ số và thời hạn sử dụng

Bài tập lớn **đề 69**. Web giúp quản trị viên trong tổ chức theo dõi các chứng chỉ SSL/TLS của website, máy
chủ và dịch vụ nội bộ: chứng chỉ nào còn bao nhiêu ngày, cái nào sắp hết hạn, ai đã gia hạn, khi nào. Hệ
thống tự cảnh báo theo các mốc 30, 15, 7 ngày, có thống kê bằng biểu đồ và xuất báo cáo.

- **Công nghệ:** PHP 8 thuần + PDO, MySQL/MariaDB (XAMPP), HTML/CSS/JavaScript, giao diện Tabler (dựa trên
  Bootstrap 5), biểu đồ Chart.js.
- **Bản chạy thật trên mạng:** <https://cert.cipher.id.vn>.
- **Tài liệu phân tích yêu cầu, ERD, Use Case, báo cáo kiểm thử:** trong file báo cáo nộp kèm.

Có hai cách chạy trên máy Windows đã cài [XAMPP](https://www.apachefriends.org) (đã thử với XAMPP 8.2.12):
**Cách 1** bấm một file là chạy; **Cách 2** cài tay từng bước theo kiểu quen thuộc của XAMPP.

## Cách 1: bấm đúp `CHAY.bat` (nhanh nhất)

1. **Giải nén** file nén ra một thư mục bất kỳ: bấm chuột phải vào file nén → **Extract All** (Giải nén).
   Không cần chép vào `htdocs`. Bấm `CHAY.bat` ngay bên trong file nén sẽ không chạy, vì chương trình nén
   chỉ bung riêng file đó ra thư mục tạm.
2. Bấm đúp **`CHAY.bat`**. Không cần mở XAMPP Control Panel trước.
3. Trình duyệt tự mở trang giới thiệu. Bấm **Đăng nhập**, dùng tài khoản ở [mục Tài khoản](#tài-khoản).

Cửa sổ đen làm thay bạn: tìm XAMPP, bật MySQL nếu chưa bật, lần đầu tự tạo CSDL `cipher_dev` và nạp dữ liệu
mẫu (50 chứng chỉ, 2 tài khoản), rồi chạy web ở <http://localhost:8000> (cổng bận thì dùng 8001, 8002...).
**Đóng cửa sổ đen là tắt web.**

- **Lần bấm sau:** cửa sổ hỏi có nạp lại dữ liệu mẫu không. Bấm `K` hoặc chờ 10 giây là giữ nguyên dữ liệu
  đang có; bấm `C` là xoá hết và nạp lại từ đầu.
- **Windows hiện "Windows protected your PC":** bấm **More info → Run anyway**. File chỉ chạy PHP và MySQL
  của XAMPP; mở bằng Notepad để đọc từng bước, mỗi bước có chú thích.
- **Cửa sổ báo LỖI:** đọc dòng LỖI, làm theo hướng dẫn trong đó, hoặc chuyển sang Cách 2.

## Cách 2: cài tay từng bước

**Bước 1. Bật Apache và MySQL.** Mở **XAMPP Control Panel**, bấm **Start** ở dòng **Apache** và dòng
**MySQL**. Cả hai chuyển sang nền xanh là được.

**Bước 2. Chép bài vào `htdocs`.** Chép cả thư mục bài vào `C:\xampp\htdocs` và đặt tên thư mục là
`cipher` (chữ thường, không dấu cách). Kiểm tra: phải có file `C:\xampp\htdocs\cipher\public\index.php`.

**Bước 3. Tạo CSDL.** Mở <http://localhost/phpmyadmin>. Bấm **New** (Mới) ở cột trái. Ô tên gõ `cipher_dev`,
ô bên cạnh chọn `utf8mb4_unicode_ci`, bấm **Create** (Tạo).

**Bước 4. Nạp dữ liệu, đúng thứ tự 3 file.** Ở cột trái bấm vào `cipher_dev`, chọn tab **Import** (Nhập),
bấm **Choose File** (Chọn tệp), chọn file, kéo xuống cuối bấm **Import** (Nhập). Làm lần lượt:

| Thứ tự | File | Nội dung |
|---|---|---|
| 1 | `database\schema.sql` | Tạo 9 bảng, view tính số ngày còn lại, 2 trigger chặn sửa, xoá nhật ký. Xoá bảng cũ nếu có |
| 2 | `database\seed.sql` | 2 tài khoản, danh mục, 9 chứng chỉ đủ mọi trạng thái và mốc cảnh báo |
| 3 | `database\seed_demo.sql` | Thêm 41 chứng chỉ cho đủ 50, để biểu đồ và phân trang có số liệu |

Mỗi file nạp xong, phpMyAdmin báo một dòng xanh: "Import has been successfully finished" (giao diện tiếng
Việt: "Việc nhập vào đã được thực hiện thành công"). Lỡ nạp sai thứ tự thì nạp lại từ file 1.

**Bước 5. Mở web.** Vào <http://localhost/cipher/public/>. Nhớ có `/public/` ở cuối: các trang web nằm trong
thư mục này, còn cấu hình và mã xử lý để ngoài (trên máy chủ thật, chúng không mở được từ trình duyệt).

## Tài khoản

| Tài khoản | Mật khẩu | Vai trò |
|---|---|---|
| `admin` | `Cipher@2026` | Admin, toàn quyền |
| `nhanvien` | `Cipher@2026` | Nhân viên quản trị: không xoá chứng chỉ, không quản lý người dùng, không xem nhật ký |

Bản chạy thật trên mạng (<https://cert.cipher.id.vn>) có cùng dữ liệu mẫu và cùng hai tài khoản này trong thời gian
chấm bài.

## Cấu hình và lỗi thường gặp

| Gặp | Làm |
|---|---|
| MySQL của máy đã đặt mật khẩu cho `root` | Tạo file `config\config.local.php` với nội dung `<?php return ['pass' => 'mật khẩu của bạn'];` rồi làm theo Cách 2. Mặc định web dùng `root` không mật khẩu, CSDL `cipher_dev` ở `127.0.0.1:3306` (xem [config/db.php](config/db.php)) |
| Trang báo lỗi kết nối CSDL | MySQL chưa bật, hoặc chưa tạo `cipher_dev`, hoặc chưa nạp đủ 3 file |
| Mở `localhost/cipher/` chỉ thấy danh sách thư mục | Thêm `/public/` vào cuối địa chỉ |
| Apache không Start được (cổng 80 bị chương trình khác giữ) | Dùng Cách 1: không cần Apache |
| Không thấy đủ cảnh báo 30, 15, 7 ngày | Ngày trong dữ liệu mẫu tính từ lúc nạp, nên lệch dần theo thời gian. Nạp lại: Cách 1 bấm `C`; Cách 2 nạp lại 3 file |
| Đọc chứng chỉ thật từ máy chủ không được | Cần máy có mạng ra internet. XAMPP bật sẵn tiện ích `openssl` của PHP |
| Múi giờ | Không cần chỉnh: code tự đặt giờ Việt Nam cho PHP và CSDL, vì XAMPP mặc định dùng giờ Đức |

## Dùng hệ thống

Menu dọc bên trái chia 4 nhóm:

| Nhóm | Mục | Ai thấy |
|---|---|---|
| Tổng quan | Dashboard, Cảnh báo (kèm số cảnh báo mới) | Mọi người |
| Chứng chỉ | Danh sách chứng chỉ, Thống kê và báo cáo | Mọi người |
| Hạ tầng | Tên miền, Máy chủ, Tổ chức cấp | Mọi người |
| Hệ thống | Người dùng, Nhật ký | Chỉ Admin |

Đổi mật khẩu, sửa họ tên ở biểu tượng người dùng cuối menu → **Tài khoản và mật khẩu**.

**Thử nhập tay:** mở **`DU_LIEU_NHAP_TAY.xlsx`** (cùng thư mục với file này). Mỗi trang tính là một form, có dòng
nhập đúng, dòng cố ý sai và kết quả mong đợi; ngày tự tính theo hôm nay. Ô ngày trên web dán được chữ ngày/tháng/năm
chép từ file.

**Các việc hay làm:**

1. **Thêm chứng chỉ bằng tay:** Chứng chỉ → **Thêm chứng chỉ** (mở sẵn tab **Nhập thủ công**). Ô Tên miền và Tổ
   chức cấp có gợi ý, chỉ nhận tên đã có trong danh mục.
2. **Thêm chứng chỉ của một website có thật:** cùng trang, tab **Nhập tự động** → khung **Đọc một địa chỉ** → gõ
   tên miền (ví dụ `github.com`), chọn cổng → **Đọc chứng chỉ**. Hệ thống điền sẵn các ô; kiểm tra lại rồi bấm
   **Thêm chứng chỉ**. Tên miền, máy chủ, tổ chức cấp chưa có thì được tạo cùng lúc.
3. **Thêm nhiều chứng chỉ một lúc:** tab **Nhập tự động** → khung **Nhập hàng loạt** ở dưới → dán tối đa 50 địa
   chỉ, mỗi dòng một → xem trước, tick những dòng muốn lưu.
4. **Xử lý cảnh báo:** mục **Cảnh báo** → xem chứng chỉ sắp hết hạn → **Gia hạn** hoặc **Đã xem**.
5. **Gia hạn:** mở một chứng chỉ → **Gia hạn** → nhập ngày hết hạn mới (fingerprint, serial mới nếu có). Thông
   tin cũ vào bảng Lịch sử gia hạn ngay dưới.
6. **Xuất báo cáo:** Danh sách chứng chỉ hoặc Thống kê → chọn bộ lọc → nút **Xuất CSV** (mở bằng Excel) hoặc
   **Bản in** (in hoặc lưu PDF bằng lệnh In của trình duyệt) ở góc trên bên phải.

## Thư viện và nguồn

| Thư viện | Dùng cho | Giấy phép |
|---|---|---|
| [Tabler](https://tabler.io) | Giao diện (CSS, JS), dựa trên Bootstrap 5 | MIT, [LICENSE](public/assets/tabler/LICENSE) |
| [Chart.js](https://www.chartjs.org) | Biểu đồ | MIT, [LICENSE](public/assets/chartjs/LICENSE) |
| [Inter](https://rsms.me/inter/) | Font chữ | SIL Open Font License, [LICENSE](public/assets/inter/LICENSE) |

Các thư viện được chép sẵn vào `public/assets/`, không tải từ mạng, nên web chạy được cả khi không có internet
(trừ chức năng đọc chứng chỉ thật).
