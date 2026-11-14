# Báo cáo kiểm thử: Hệ thống quản lý chứng chỉ số và thời hạn sử dụng

## 1. Kết quả

| Hạng mục | Kết quả |
|---|---|
| **Ngày chạy** | 14/11/2026 |
| **Bộ test tự động** | **{{TONG}}/{{TONG}} đạt**, chạy mất 41 giây |
| **Kiểm tra cú pháp** | Mọi file `.php` qua `php -l`, không lỗi |
| **Kiểm tay theo kịch bản** | 34/34 dòng của file `DU_LIEU_NHAP_TAY.xlsx` cho đúng kết quả mong đợi (mục 4) |
| **Kiểm trên bản chạy thật** | 14/14 ca đạt, đủ 5 header bảo mật (mục 5) |
| **Kiểm thử bảo mật** | SQL Injection, XSS, CSRF, phân quyền, SSRF, phiên: không tìm thấy lỗ hổng (mục 6) |
| **Môi trường** | Windows 11 · XAMPP 8.2.12: PHP 8.2.12, MariaDB 10.4.32 · bản chạy thật: Ubuntu 24.04, PHP-FPM, MariaDB 10.11, Caddy |

![Bộ test tự động chạy xong: mỗi test một dòng, dòng cuối ghi XANH kèm số test](anh/chay_test.jpg)

## 2. Cách kiểm thử

Hệ thống được kiểm ở 4 lớp:

1. **Cú pháp:** chạy `php -l` trên mọi file `.php`, bắt lỗi gõ sai trước khi chạy.
2. **Bộ test tự động** ([tests/chay.php](../tests/chay.php)), một file PHP thuần, không dùng thư viện test:
   - Mỗi test gọi thẳng hàm trong `src/` trên **CSDL MariaDB thật** tên `cipher_test`, không giả lập CSDL. Nhờ
     vậy ràng buộc `UNIQUE`, `CHECK`, khoá ngoại, trigger, view đều được kiểm thật.
   - **Trước mỗi test**, CSDL `cipher_test` được nạp lại từ `schema.sql` và `seed.sql`, nên các test không ảnh
     hưởng nhau và chạy theo thứ tự nào cũng ra cùng kết quả.
   - Một số test đọc mã nguồn để kiểm quy tắc phải đúng với **mọi** file, ví dụ mọi trang quản trị đều kiểm tra
     đăng nhập, mọi trang nhận POST đều kiểm tra mã CSRF. Thêm trang mới mà quên là test đỏ.
   - Mỗi test có 4 dòng chú thích ngay trên: **Luật** (quy tắc được kiểm), **Vì sao** (lý do có quy tắc),
     **Đỏ nghĩa là** (test đỏ thì cái gì đang hỏng), **Đừng** (cách sửa sai hay gặp). Câu Luật được chép vào
     bảng ở mục 3.
   - Mỗi test mới được viết và **cho chạy đỏ một lần** (khi code chưa có hoặc cố ý làm sai) trước khi viết code
     cho xanh, để chắc test thật sự bắt được lỗi.
3. **Kiểm tay theo kịch bản** (mục 4): nhập từng dòng của file Excel dữ liệu mẫu vào web, so với cột Kết quả
   mong đợi. Đây cũng là bảng để người chấm tự nhập thử.
4. **Kiểm trên bản chạy thật** qua internet (mục 5, 6), và xem giao diện bằng mắt: mọi thay đổi giao diện đều
   được chụp màn hình ở khổ máy tính và khổ điện thoại 390px trước khi giao.

**Chạy lại bộ test:** cần MySQL đang bật và CSDL `cipher_test` đã tạo, rồi chạy `C:\xampp\php\php.exe tests\chay.php`
từ thư mục mã nguồn đầy đủ của nhóm (gói nộp để chạy web không kèm bộ test). Mỗi test in ✅ hoặc ❌ kèm lý do; dòng
cuối là `XANH: {{TONG}} test` hoặc `ĐỎ: số test đỏ/{{TONG}} test`.

## 3. Danh sách test theo chức năng

Tên test viết không dấu, bỏ tiền tố `test_`; tìm đúng tên đó trong `tests/chay.php` để xem code của test.
Cột phải là câu Luật của test. Cả {{TONG}} test đều **đạt** ngày 14/11/2026. Nguyễn Đức Trung phụ trách
{{TV1}} test, Phan Tuấn Anh {{TV2}} test, Nguyễn Chí Kiên {{TV3}} test.

