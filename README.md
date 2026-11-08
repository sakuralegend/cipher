# Cipher Cert: Hệ thống quản lý chứng chỉ số và thời hạn sử dụng

Bài tập lớn **đề 69**. Web giúp quản trị viên trong tổ chức theo dõi các chứng chỉ SSL/TLS của website, máy
chủ và dịch vụ nội bộ: chứng chỉ nào còn bao nhiêu ngày, cái nào sắp hết hạn, ai đã gia hạn, khi nào. Hệ
thống tự cảnh báo theo các mốc 30, 15, 7 ngày, có thống kê bằng biểu đồ và xuất báo cáo.

- **Bản chạy thật:** <https://cert.cipher.id.vn> (tài khoản do nhóm cấp, không có trang tự đăng ký).
- **Công nghệ:** PHP 8 thuần + PDO, MariaDB/MySQL (XAMPP), HTML/CSS/JavaScript, giao diện Tabler (dựa trên
  Bootstrap 5), biểu đồ Chart.js. Không dùng framework PHP.
- **Tài liệu:** [Đặc tả chức năng](docs/SPEC.md) · [Use Case và đặc tả](docs/USECASE.md) ·
  [ERD](docs/ERD.md) · [Báo cáo kiểm thử](docs/KIEM_THU.md)

**Đọc theo thứ tự này nếu bạn mới vào:** mục 1 (hệ thống làm gì) → mục 2 (cài để chạy thử) → mục 5 (sổ
tay: mỗi luồng chạy qua file nào) → mục 6 (phần mình phụ trách).

## 1. Hệ thống làm được gì

Đề yêu cầu 19 chức năng: 9 chức năng riêng của đề (D1–D9) và 10 chức năng nền tảng (Na–Nj). Nhóm làm đủ 19,
thêm 2 chức năng mở rộng. Chi tiết từng chức năng ở [SPEC.md](docs/SPEC.md).

| Nhóm | Chức năng |
|---|---|
| Chứng chỉ | Thêm, sửa, xem, xoá (chỉ Admin), thu hồi; mỗi chứng chỉ có mã quản lý, fingerprint SHA-256, serial, tên miền, tổ chức cấp, thuật toán |
| Thời hạn | Tự tính số ngày còn lại và trạng thái: Còn hiệu lực, Sắp hết hạn, Đã hết hạn, Chưa hiệu lực, Đã thu hồi |
| Cảnh báo | Tự tạo cảnh báo ở mốc 30, 15, 7 ngày; đánh dấu đã xem; số cảnh báo mới hiện trên menu |
| Gia hạn | Cập nhật hạn mới, giữ lại hạn, fingerprint, serial cũ trong lịch sử gia hạn |
| Danh mục | Tên miền, máy chủ, tổ chức cấp (CA) |
| Thống kê, báo cáo | Dashboard; 4 biểu đồ kèm bảng số; xuất CSV và bản in |
| Người dùng | Đăng nhập, phân quyền Admin / Nhân viên quản trị, khoá tài khoản, chặn khi sai mật khẩu 5 lần |
| **Mở rộng 1** | Nhật ký hoạt động: ai làm gì, lúc nào, từ IP nào; CSDL chặn sửa, xoá nhật ký |
| **Mở rộng 2** | Đọc chứng chỉ thật từ máy chủ: gõ tên miền, hệ thống kết nối tới máy chủ, đọc chứng chỉ rồi tự điền form |

## 2. Cài đặt và chạy thử trên máy (Windows + XAMPP)

Đã chạy thử đúng các bước dưới ngày 08/11/2026 với XAMPP 8.2.12 (PHP 8.2.12, MariaDB 10.4.32).

**Bước 1. Cài và bật MySQL.** Cài XAMPP vào `C:\xampp`, mở XAMPP Control Panel, bấm **Start** ở dòng MySQL.
Không cần bật Apache.

**Bước 2. Lấy mã nguồn.** `git clone https://github.com/sakuralegend/cipher.git`, hoặc tải file nén ở nút
**Code → Download ZIP** rồi giải nén.

**Bước 3. Tạo CSDL và nạp dữ liệu mẫu.** Mở **Command Prompt** (cmd), không dùng PowerShell vì PowerShell không
hiểu dấu `<`. Chuyển vào thư mục mã nguồn rồi chạy lần lượt:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE cipher_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
C:\xampp\mysql\bin\mysql.exe -u root cipher_dev < database\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root cipher_dev < database\seed.sql
C:\xampp\mysql\bin\mysql.exe -u root cipher_dev < database\seed_demo.sql
```

| File | Nội dung |
|---|---|
| `database/schema.sql` | Tạo 9 bảng, view `v_certificates`, 2 trigger chặn sửa, xoá nhật ký. **Xoá sạch bảng cũ** trước khi tạo, nên không chạy trên CSDL có dữ liệu cần giữ. Cố ý không có lệnh `CREATE DATABASE`, vì cùng file này còn dùng để nạp CSDL test |
| `database/seed.sql` | 2 tài khoản, danh mục, 9 chứng chỉ đủ mọi trạng thái và mốc cảnh báo |
| `database/seed_demo.sql` | Không bắt buộc. Thêm 41 chứng chỉ cho đủ 50, để biểu đồ và phân trang có số liệu |

Ngày trong dữ liệu mẫu tính theo thời điểm nạp, nên vài tuần sau trạng thái sẽ lệch dần. Muốn có lại đủ
các mốc thì chạy lại cả 3 lệnh nạp (lệnh `CREATE DATABASE` thì bỏ qua, vì CSDL đã có).

**Bước 4. Chạy web.** Vẫn trong thư mục mã nguồn:

```bat
C:\xampp\php\php.exe -S localhost:8000 -t public
```

Mở <http://localhost:8000>. Trang đầu là trang giới thiệu, bấm **Đăng nhập**.

| Tài khoản | Mật khẩu | Vai trò |
|---|---|---|
| `admin` | `Cipher@2026` | Admin, toàn quyền |
| `nhanvien` | `Cipher@2026` | Nhân viên quản trị: không xoá chứng chỉ, không quản lý người dùng, không xem nhật ký |

Hai tài khoản này chỉ có ở máy cài từ `seed.sql`; bản chạy thật trên mạng dùng mật khẩu khác.

**Bước 5 (cho người sửa code). Chạy bộ test.** Bộ test cần CSDL riêng `cipher_test` và tự nạp lại CSDL đó
trước mỗi test, không đụng `cipher_dev`:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE cipher_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
C:\xampp\php\php.exe tests\chay.php
```

Dòng cuối phải là `XANH: 89 test`. Kết quả chi tiết ở [KIEM_THU.md](docs/KIEM_THU.md).

### Cấu hình

| Muốn đổi | Cách làm |
|---|---|
| Tên CSDL, tài khoản, mật khẩu MySQL | Tạo file `config/config.local.php` (file này không đưa lên git): `<?php return ['user' => 'ten', 'pass' => 'matkhau', 'dbname' => 'cipher_dev'];`. Mặc định là `root`, không mật khẩu, CSDL `cipher_dev` ở `127.0.0.1:3306` |
| Múi giờ | Không cần đổi. Code tự đặt `Asia/Ho_Chi_Minh` cho PHP và `+07:00` cho CSDL ở [config/db.php](config/db.php), vì XAMPP mặc định dùng giờ Đức, lệch là sai số ngày còn lại |
| Đọc chứng chỉ thật | Cần PHP có tiện ích `openssl` và máy có mạng ra ngoài. XAMPP bật sẵn `openssl` |

## 3. Dùng hệ thống

Menu dọc bên trái chia 4 nhóm:

| Nhóm | Mục | Ai thấy |
|---|---|---|
| Tổng quan | Dashboard, Cảnh báo (kèm số cảnh báo mới) | Mọi người |
| Chứng chỉ | Danh sách chứng chỉ, Thống kê và báo cáo | Mọi người |
| Hạ tầng | Tên miền, Máy chủ, Tổ chức cấp | Mọi người |
| Hệ thống | Người dùng, Nhật ký | Chỉ Admin |

Đổi mật khẩu, sửa họ tên ở menu tên mình góc trên bên phải → **Tài khoản của tôi**. Nút mặt trời, mặt trăng
đổi giao diện sáng, tối.

**Các việc hay làm:**

1. **Thêm chứng chỉ của một website có thật:** Danh sách chứng chỉ → **Thêm chứng chỉ** → tab **Đọc từ máy
   chủ** → gõ tên miền (ví dụ `cert.cipher.id.vn`), chọn cổng → **Đọc chứng chỉ**. Hệ thống điền sẵn các ô;
   kiểm tra lại rồi bấm **Lưu**. Tên miền hoặc tổ chức cấp chưa có trong danh mục thì thêm vào danh mục trước,
   rồi đọc lại.
2. **Thêm chứng chỉ bằng tay:** cùng form, tab **Nhập thủ công**. Ô Tên miền và Tổ chức cấp gõ được và có gợi
   ý, nhưng chỉ nhận tên đã có trong danh mục.
3. **Xử lý cảnh báo:** mục **Cảnh báo** → xem chứng chỉ sắp hết hạn → gia hạn hoặc bấm **Đã xem**.
4. **Gia hạn:** mở một chứng chỉ → **Gia hạn** → nhập ngày hết hạn mới (fingerprint, serial mới nếu có) →
   **Gia hạn**. Thông tin cũ vào bảng Lịch sử gia hạn ngay dưới.
5. **Xuất báo cáo:** Danh sách chứng chỉ hoặc Thống kê → chọn bộ lọc → **Xuất CSV** (mở bằng Excel) hoặc
   **Bản in** (in hoặc lưu PDF bằng lệnh In của trình duyệt).

## 4. Cấu trúc thư mục

```
cipher/
├── config/db.php      Cửa duy nhất tới CSDL: hàm ketNoiCsdl(), múi giờ, chế độ strict
├── database/          schema.sql (bảng, view, trigger) · seed.sql · seed_demo.sql
├── src/               Toàn bộ quy tắc nghiệp vụ, không in HTML
│   ├── khoi_dong.php  Mọi trang gọi đầu tiên: nạp CSDL, đăng nhập, nhật ký, phiên; hàm e(), url(), ô form
│   ├── auth.php       Đăng nhập, đăng xuất, phiên, phân quyền, mã CSRF
│   ├── chung_chi.php  Chứng chỉ, lọc, sắp xếp, gia hạn, cảnh báo, thống kê, CSV, đọc chứng chỉ thật
│   ├── danh_muc.php   Máy chủ, tên miền, tổ chức cấp
│   ├── nguoi_dung.php Người dùng, vai trò, đổi mật khẩu
│   └── nhat_ky.php    Ghi và đọc nhật ký hoạt động
├── public/            Thư mục duy nhất mở ra web; mỗi file là một trang
│   ├── index.php      Trang giới thiệu (landing)
│   ├── login.php, logout.php
│   ├── admin/         Các trang sau đăng nhập
│   └── assets/        app.css, app.js; Tabler, Chart.js, font Inter (kèm LICENSE)
├── views/             Mảnh giao diện dùng chung: đầu trang và menu, bộ lọc, nhãn trạng thái...
├── tests/chay.php     Bộ test: chạy bằng php tests/chay.php
└── docs/              Tài liệu
```

**Quy ước để đọc code nhanh:**

- Trang trong `public/` chỉ nhận dữ liệu, gọi hàm trong `src/`, rồi hiện kết quả. Quy tắc nằm trong `src/`,
  nên muốn biết "hệ thống kiểm tra gì" thì đọc `src/`.
- Tên hàm, tên biến bằng tiếng Việt không dấu: `luuChungChi()` là lưu chứng chỉ, `$loi` là lỗi. Mỗi hàm có
  chú thích tiếng Việt giải thích **vì sao** làm vậy.
- Hàm lưu trả về mảng lỗi `['tên ô' => 'câu lỗi']`; mảng rỗng là thành công. Hàm thao tác (xoá, thu hồi) trả
  về câu lỗi, hoặc `null` nếu thành công.
- Thành công thì trang lưu một thông báo rồi chuyển hướng (`datThongBao()` rồi `chuyenHuong()`), để bấm F5
  không gửi lại form.
- Dữ liệu được kiểm tra ở 3 lớp: JavaScript báo lỗi nhanh trên form ([app.js](public/assets/app.js)), PHP kiểm
  tra lại tất cả (`src/`), CSDL chặn lần cuối bằng `UNIQUE`, `CHECK`, khoá ngoại ([schema.sql](database/schema.sql)).

## 5. Sổ tay: mỗi luồng chính chạy qua đâu

Đọc mục nào thì mở đúng file, đúng hàm ghi ở đó.

### 5.1. Mọi trang quản trị: kiểm tra đăng nhập, quyền, CSRF

1. Dòng đầu mọi file trong `public/admin/` nạp [src/khoi_dong.php](src/khoi_dong.php): mở kết nối, bắt đầu
   phiên bằng `batDauPhien()` (cookie `HttpOnly`, `SameSite=Lax`).
2. Ngay sau đó gọi `yeuCauDangNhap()` hoặc `yeuCauAdmin()` ([src/auth.php](src/auth.php)). Hai hàm này đọc
   lại người dùng từ CSDL **ở mỗi yêu cầu**: ai bị khoá giữa chừng thì bị đẩy ra ngay. Chưa đăng nhập thì về
   `login.php`; không phải Admin mà vào trang của Admin thì nhận lỗi 403.
3. Mọi form đổi dữ liệu gửi bằng POST kèm mã CSRF (`maCsrf()`); trang nhận gọi `yeuCauCsrf()` trước khi làm
   gì. Mã sai thì từ chối, để trang web lạ không mượn phiên của mình gửi lệnh.
4. Mọi dữ liệu in ra trang đều qua hàm `e()` (chống chèn mã độc). Mọi câu SQL có dữ liệu người dùng đều dùng
   prepared statement (chống SQL Injection).

Có test bắt quy tắc này cho **mọi** file: `test_moi_trang_quan_tri_deu_kiem_tra_dang_nhap`,
`test_moi_trang_nhan_post_deu_kiem_tra_csrf`. Thêm trang mới mà quên một trong hai là test đỏ.

### 5.2. Đăng nhập, đăng xuất

| Bước | File | Hàm |
|---|---|---|
| Người dùng gửi form | [public/login.php](public/login.php) | `yeuCauCsrf()` |
| Kiểm tra tên, mật khẩu, khoá, tạm chặn | [src/auth.php](src/auth.php) | `dangNhap()` |
| Sai mật khẩu: tăng đếm, đủ 5 lần thì chặn 15 phút | [src/auth.php](src/auth.php) | `ghiNhanSaiMatKhau()` |
| Đúng: đổi mã phiên, lưu id người dùng | [src/auth.php](src/auth.php) | `ghiNhanDangNhap()` |
| Ghi nhật ký đăng nhập đúng hoặc sai | [src/nhat_ky.php](src/nhat_ky.php) | `ghiNhatKy()` |
| Đăng xuất (chỉ bằng POST), về trang giới thiệu | [public/logout.php](public/logout.php) | `dangXuat()` |

Sai tên, sai mật khẩu hay đang bị tạm chặn đều báo **cùng một câu**, và khi tên không tồn tại vẫn chạy
`password_verify` trên một mã băm giả, để kẻ dò không biết tên đăng nhập nào có thật.

### 5.3. Thêm, sửa chứng chỉ

| Bước | File | Hàm |
|---|---|---|
| Form thêm (không có `?id=`) và sửa (có `?id=`) | [public/admin/chung_chi_form.php](public/admin/chung_chi_form.php) | |
| Gợi ý mã kế tiếp `CERT-051` | [src/chung_chi.php](src/chung_chi.php) | `maChungChiKeTiep()` |
| Ô gõ Tên miền, Tổ chức cấp: đổi tên thành mã | [src/danh_muc.php](src/danh_muc.php) | `idTheoTen()` |
| Chuẩn hoá fingerprint, serial, ngày | [src/chung_chi.php](src/chung_chi.php) | `chuanHoaFingerprint()`, `chuanHoaSerial()`, `chuanHoaNgay()` |
| Kiểm tra từng ô | [src/chung_chi.php](src/chung_chi.php) | `kiemTraChungChi()` |
| Ghi CSDL; trùng mã hoặc fingerprint thì báo đúng ô | [src/chung_chi.php](src/chung_chi.php) | `luuChungChi()` |

Form sửa chỉ để sửa thông tin nhập sai, không ghi lịch sử. Chứng chỉ được cấp lại thì dùng Gia hạn (5.6).

### 5.4. Đọc chứng chỉ thật từ máy chủ (mở rộng 2)

| Bước | Hàm trong [src/chung_chi.php](src/chung_chi.php) |
|---|---|
| Tách tên máy và cổng từ chữ người dùng gõ | `phanTichDiaChi()` |
| Hỏi DNS lấy mọi IP của tên miền | `phanGiaiDiaChi()` |
| Chặn cổng ngoài danh sách TLS, chặn nếu **bất kỳ** IP nào là nội bộ | `kiemTraDiaChiDoc()`, `laDiaChiNoiBo()` |
| Kết nối TLS tới đúng IP vừa kiểm, lấy chứng chỉ | `docChungChiTuMayChu()` |
| Đổi chứng chỉ thành giá trị các ô của form | `giaTriTuChungChi()` |
| Tự chọn tên miền, tổ chức cấp nếu trùng tên; báo nếu đã có chứng chỉ cùng fingerprint | `chonTenMienChoDiaChi()`, `chonToChucCap()`, `chungChiTheoFingerprint()` |
| Gộp các bước trên và ghi nhật ký, đọc được hay không cũng ghi | `docChungChiChoForm()` |

Vì sao chặn địa chỉ nội bộ: web chạy chung máy với các dịch vụ khác. Không chặn thì người đăng nhập có thể
nhờ web kết nối tới `127.0.0.1` hoặc mạng nội bộ để dò xem có gì (kiểu tấn công này gọi là SSRF). Kết nối tới
đúng IP đã kiểm, không tra DNS lần hai, để kẻ xấu không kịp đổi tên miền sang `127.0.0.1` giữa hai lần tra.

### 5.5. Trạng thái và số ngày còn lại

Không lưu trong bảng. View `v_certificates` ([database/schema.sql](database/schema.sql)) tính lại mỗi lần đọc
bằng `DATEDIFF(valid_to, NOW())` và một câu `CASE`: đã thu hồi → chưa hiệu lực → đã hết hạn → sắp hết hạn (còn
tối đa 30 ngày) → còn hiệu lực. Nhờ vậy trạng thái không bao giờ bị cũ. Mọi danh sách đều đọc từ view này.

### 5.6. Gia hạn

| Bước | File | Hàm |
|---|---|---|
| Form gia hạn | [public/admin/chung_chi_gia_han.php](public/admin/chung_chi_gia_han.php) | |
| Mở giao dịch, khoá dòng chứng chỉ (`SELECT ... FOR UPDATE`) | [src/chung_chi.php](src/chung_chi.php) | `giaHanChungChi()` |
| Chặn chứng chỉ đã thu hồi; hạn mới phải sau hạn cũ và sau hiện tại | [src/chung_chi.php](src/chung_chi.php) | `kiemTraGiaHan()` |
| Chép hạn, fingerprint, serial cũ vào `renewals`, rồi cập nhật chứng chỉ, ghi nhật ký, `COMMIT` | [src/chung_chi.php](src/chung_chi.php) | `giaHanChungChi()` |
| Xem lịch sử | [public/admin/chung_chi_xem.php](public/admin/chung_chi_xem.php) | `lichSuGiaHan()` |

Cả chuỗi nằm trong **một giao dịch**: lỗi ở bước nào thì không bước nào có hiệu lực.

### 5.7. Cảnh báo

| Bước | File | Hàm |
|---|---|---|
| Mở Dashboard hoặc trang Cảnh báo thì tạo cảnh báo còn thiếu | [public/admin/index.php](public/admin/index.php), [public/admin/canh_bao.php](public/admin/canh_bao.php) | `taoCanhBao()` |
| Danh sách cảnh báo của chu kỳ hiện tại; số cảnh báo mới trên menu | [src/chung_chi.php](src/chung_chi.php) | `danhSachCanhBao()`, `demCanhBaoMoi()` |
| Bấm Đã xem | [public/admin/chung_chi_thao_tac.php](public/admin/chung_chi_thao_tac.php) | `danhDauDaXem()` |

`taoCanhBao()` là một câu `INSERT ... SELECT` lấy mọi chứng chỉ Sắp hết hạn từ view, chọn mốc gần nhất đã
chạm (7, 15 hoặc 30). Khoá `UNIQUE (certificate_id, valid_to, threshold_days)` của bảng `alerts` bảo đảm mỗi
mốc chỉ tạo một lần trong một chu kỳ, nên chạy lại bao nhiêu lần cũng không sinh thêm dòng. Gia hạn đổi
`valid_to`, nên các mốc được tính lại từ đầu. Không cần chạy lịch nền (cron), vì cảnh báo chỉ hiện trong web.

### 5.8. Thu hồi, xoá chứng chỉ

Cả hai đi qua [public/admin/chung_chi_thao_tac.php](public/admin/chung_chi_thao_tac.php):

- `thuHoiChungChi()`: đặt `is_revoked = 1`, giữ nguyên dữ liệu, không hoàn tác được (form sửa không có ô này).
- `xoaChungChi()`: chỉ Admin. Quyền kiểm tra **ngay trong hàm**, nên nhân viên gửi lệnh thẳng tới trang cũng bị
  từ chối. Xoá kèm cảnh báo và lịch sử gia hạn (`ON DELETE CASCADE`).

### 5.9. Danh sách, tìm kiếm, lọc, sắp xếp

[public/admin/chung_chi.php](public/admin/chung_chi.php) → trong [src/chung_chi.php](src/chung_chi.php):
`docBoLoc()` đọc bộ lọc từ địa chỉ trang (giá trị sai coi như chưa chọn) → `dieuKienLoc()` dựng điều kiện SQL
có tham số → `docSapXep()`, `menhDeSapXep()` chỉ nhận tên cột trong danh sách viết sẵn → `danhSachChungChi()`
lấy 20 dòng một trang. Mặc định xếp theo mức gấp (`mucGapSql()`): đã hết hạn, sắp hết hạn, còn hiệu lực,
chưa hiệu lực, đã thu hồi. Hàng nút trạng thái có số đếm lấy từ `demTheoTrangThai()`.

### 5.10. Thống kê và xuất báo cáo

- [public/admin/thong_ke.php](public/admin/thong_ke.php): 4 biểu đồ dùng chung bộ lọc với danh sách:
  `demTheoTrangThai()`, `hetHanTheoThang()` (12 tháng tới), `demTheoNhom()` (theo tổ chức cấp, theo thuật
  toán). Trần trục tính trên toàn bộ chứng chỉ (`tranBieuDoThongKe()`), nên lọc thì cột ngắn lại trên cùng một
  thước. Biểu đồ vẽ bằng Chart.js trong [app.js](public/assets/app.js); mỗi biểu đồ có bảng số ngay dưới.
- [public/admin/bao_cao_csv.php](public/admin/bao_cao_csv.php): `tatCaChungChi()` lấy đủ kết quả (không chia
  trang), `dongCsv()` và `oCsvAnToan()` vô hiệu hoá ô bắt đầu bằng `=`, `+`, `-`, `@` để Excel không chạy thành
  công thức. Có BOM UTF-8 để Excel hiện đúng tiếng Việt.
- [public/admin/bao_cao_in.php](public/admin/bao_cao_in.php): bản in cùng bộ lọc, ghi rõ bộ lọc đã chọn.

### 5.11. Nhật ký hoạt động (mở rộng 1)

- Ghi: các hàm thêm, sửa, xoá trong `src/` tự gọi `ghiNhatKy()` ([src/nhat_ky.php](src/nhat_ky.php)) ngay sau khi
  thành công, nên không trang nào quên ghi. Xoá đi qua `xoaVaGhiNhatKy()` để kịp lưu tên đối tượng trước khi
  nó mất. IP lấy từ `REMOTE_ADDR`, không đọc header `X-Forwarded-For` vì khách tự gõ được header đó.
- Chặn sửa, xoá: 2 trigger `nhat_ky_cam_sua`, `nhat_ky_cam_xoa` trong [schema.sql](database/schema.sql) từ chối
  mọi lệnh `UPDATE`, `DELETE` trên `audit_logs`, kể cả lệnh gõ thẳng vào CSDL.
- Xem: [public/admin/nhat_ky.php](public/admin/nhat_ky.php), chỉ Admin; `docLocNhatKy()`, `layNhatKy()`.

### 5.12. Người dùng, tài khoản của tôi

- [public/admin/nguoi_dung.php](public/admin/nguoi_dung.php), [nguoi_dung_form.php](public/admin/nguoi_dung_form.php),
  [nguoi_dung_thao_tac.php](public/admin/nguoi_dung_thao_tac.php): chỉ Admin. Hàm trong
  [src/nguoi_dung.php](src/nguoi_dung.php): `kiemTraNguoiDung()`, `themNguoiDung()`, `suaNguoiDung()`,
  `khoaNguoiDung()`, `moKhoaNguoiDung()`, `xoaNguoiDung()`. Admin không tự khoá, tự xoá, tự đổi vai trò của
  mình, để hệ thống luôn còn một Admin. Người đã có dòng nhật ký thì chỉ khoá được, không xoá được.
- [public/admin/tai_khoan.php](public/admin/tai_khoan.php): ai cũng dùng được; `suaHoTen()`, `doiMatKhau()`
  (phải nhập đúng mật khẩu cũ). Mật khẩu luôn lưu dạng băm bằng `password_hash()`.

### 5.13. Câu hay được hỏi

| Câu hỏi | Trả lời ngắn | Xem ở |
|---|---|---|
| Chống SQL Injection thế nào? | Mọi câu SQL có dữ liệu người dùng dùng prepared statement của PDO; tên cột để sắp xếp chỉ lấy từ danh sách viết sẵn | `config/db.php`, `menhDeSapXep()` |
| Chống XSS thế nào? | Mọi dữ liệu in ra qua `e()` = `htmlspecialchars` | `src/khoi_dong.php` |
| Mật khẩu lưu thế nào? | Băm bằng `password_hash()`, kiểm tra bằng `password_verify()`; không lưu, không gửi lại mật khẩu gốc | `src/auth.php`, `src/nguoi_dung.php` |
| Số ngày còn lại tính ở đâu? | View `v_certificates`, tính mỗi lần đọc, không lưu | `database/schema.sql` |
| Sao không có cron tạo cảnh báo? | Cảnh báo chỉ hiện trong web, nên tạo lúc có người mở trang là đủ; `UNIQUE` chặn tạo trùng | `taoCanhBao()` |
| Hai người gia hạn cùng lúc thì sao? | Giao dịch + `SELECT ... FOR UPDATE`: người sau chờ người trước xong, rồi thấy hạn mới | `giaHanChungChi()` |
| Kiểm tra dữ liệu bằng JS và PHP? | JS chỉ giúp báo lỗi nhanh (có thể bị tắt); PHP kiểm tra lại tất cả; CSDL chặn lần cuối | `app.js`, `src/`, `schema.sql` |
| Sao tên miền, tổ chức cấp gõ được mà không tự tạo? | Gõ nhầm một chữ sẽ đẻ ra danh mục rác; chỉ nhận tên đã có, chưa có thì báo kèm link thêm | `idTheoTen()` |
| Sao Nhân viên không xoá được chứng chỉ? | Xoá làm mất cả lịch sử gia hạn; Nhân viên dùng Thu hồi, dữ liệu vẫn còn | `xoaChungChi()` |

## 6. Phân công nhiệm vụ

Chia đều theo nhóm chức năng. Mỗi người phụ trách phần của mình từ CSDL, code đến tài liệu và test; cả nhóm
cùng nắm luồng chung ở mục 5.1 và 5.5.

| | Thành viên 1 | Thành viên 2 | Thành viên 3 |
|---|---|---|---|
| **Họ tên, mã SV** | *(điền)* | *(điền)* | *(điền)* |
| **Phần phụ trách** | Nền tảng và bảo mật | Chứng chỉ | Danh mục, cảnh báo, báo cáo |
| **Chức năng** | Na Đăng nhập · Nb Người dùng · Ng CSDL, khoá, quan hệ · Ni Bảo mật · Nj Dữ liệu mẫu · Mở rộng 1 Nhật ký | D1 Danh sách chứng chỉ · D4 Ngày cấp, hết hạn · D5 Trạng thái · D7 Gia hạn · Nd Tìm kiếm, lọc · Nf Kiểm tra JS + PHP · Mở rộng 2 Đọc chứng chỉ thật | D2 Tên miền, máy chủ · D3 Tổ chức cấp · D6 Cảnh báo · D8 Thống kê · D9 Xuất báo cáo · Nh Dashboard · Giao diện |
| **Use case** | UC01, UC02, UC12, UC13 | UC04, UC05, UC05a, UC06, UC07, UC11 | UC03, UC08, UC09, UC10 |
| **File chính** | `config/db.php`, `src/auth.php`, `src/nguoi_dung.php`, `src/nhat_ky.php`, `database/`, `login.php`, `logout.php`, trang Người dùng, Nhật ký, Tài khoản | `src/chung_chi.php` (phần chứng chỉ, lọc, sắp xếp, gia hạn, đọc chứng chỉ), `chung_chi*.php`, `views/bo_loc.php`, `public/assets/app.js` (kiểm tra form) | `src/danh_muc.php`, `src/chung_chi.php` (phần cảnh báo, thống kê, CSV), `index.php` (Dashboard), `canh_bao.php`, `thong_ke.php`, `bao_cao_*.php`, trang danh mục, trang giới thiệu, `views/`, `app.css` |
| **Test phụ trách** ([KIEM_THU.md](docs/KIEM_THU.md)) | 33 | 25 | 31 |
| **Tài liệu** | ERD, hướng dẫn cài đặt (README mục 2) | Đặc tả use case phần chứng chỉ, sơ đồ trình tự Thêm và Gia hạn | SPEC phần thống kê, cảnh báo, giao diện; báo cáo kiểm thử |

## 7. Thư viện và nguồn

| Thư viện | Dùng cho | Giấy phép |
|---|---|---|
| [Tabler](https://tabler.io) | Giao diện (CSS, JS), dựa trên Bootstrap 5 | MIT, [LICENSE](public/assets/tabler/LICENSE) |
| [Chart.js](https://www.chartjs.org) | Biểu đồ | MIT, [LICENSE](public/assets/chartjs/LICENSE) |
| [Inter](https://rsms.me/inter/) | Font chữ | SIL Open Font License, [LICENSE](public/assets/inter/LICENSE) |

Các thư viện được chép sẵn vào `public/assets/`, không tải từ mạng, nên web chạy được cả khi không có internet
(trừ chức năng đọc chứng chỉ thật).
