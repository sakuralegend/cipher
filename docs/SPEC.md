# Đặc tả chức năng — Hệ thống quản lý chứng chỉ số và thời hạn sử dụng

**Đề tài 69.** Quản lý chứng chỉ số SSL/TLS của website, máy chủ và các dịch vụ trong tổ chức: theo dõi
thời hạn, cảnh báo trước khi hết hạn, lưu lịch sử gia hạn, thống kê và xuất báo cáo.

**Người dùng:** quản trị viên trong tổ chức, xem [USECASE.md](USECASE.md). **Dữ liệu:** xem [ERD.md](ERD.md).

Đề yêu cầu 19 chức năng: 9 chức năng riêng của đề (mã D) và 10 chức năng nền tảng (mã N).

## 1. Chức năng riêng của đề

| Mã | Chức năng | Người dùng | Đầu vào | Kiểm tra | Kết quả |
|---|---|---|---|---|---|
| D1 | Quản lý danh sách chứng chỉ | Admin, Nhân viên; chỉ Admin được xoá | Mã quản lý, fingerprint, serial, tên chính, tên phụ, tổ chức cấp, tên miền, thuật toán, độ dài khoá | Xem mục 3.1 | Chứng chỉ được thêm, sửa hoặc xoá; danh sách có phân trang |
| D2 | Quản lý tên miền và máy chủ | Admin, Nhân viên | Máy chủ: tên, IP, cổng, môi trường, người phụ trách. Tên miền: tên, máy chủ | Xem mục 3.2 | Bản ghi được thêm, sửa, xoá; xem được chứng chỉ của từng tên miền |
| D3 | Lưu thông tin tổ chức cấp | Admin, Nhân viên | Tên, quốc gia, có được tin cậy không | Tên không trùng; không xoá khi còn chứng chỉ | Danh sách tổ chức cấp để chọn khi thêm chứng chỉ |
| D4 | Quản lý ngày cấp và ngày hết hạn | Admin, Nhân viên | Ngày bắt đầu, ngày hết hạn | Ngày hết hạn phải sau ngày bắt đầu | Hiển thị hai ngày và số ngày còn hiệu lực, tự tính |
| D5 | Theo dõi trạng thái | Admin, Nhân viên | Không nhập; thu hồi bằng nút riêng | Xem mục 4 | Mỗi chứng chỉ có một trạng thái, tự tính khi xem |
| D6 | Cảnh báo sắp hết hạn | Admin, Nhân viên | Đánh dấu đã xem | Xem mục 5 | Danh sách cảnh báo theo mốc 30, 15, 7 ngày; số cảnh báo mới trên Dashboard |
| D7 | Quản lý lịch sử gia hạn | Admin, Nhân viên | Ngày hết hạn mới, fingerprint và serial mới, ghi chú | Xem mục 6 | Chứng chỉ được cập nhật; thêm một dòng lịch sử giữ thông tin cũ |
| D8 | Thống kê theo trạng thái | Admin, Nhân viên | Khoảng thời gian, bộ lọc | Giá trị lọc hợp lệ | Bảng số liệu và biểu đồ Chart.js |
| D9 | Xuất báo cáo | Admin, Nhân viên | Bộ lọc đang chọn | Giá trị lọc hợp lệ | File CSV và bản in |

**Tiêu chí của đề, ánh xạ vào hệ thống:**

| Tiêu chí | Cách đáp ứng |
|---|---|
| Mỗi chứng chỉ có mã quản lý hoặc fingerprint | `cert_code` bắt buộc và không trùng; `fingerprint_sha256` tuỳ chọn, nếu có thì không trùng |
| Tự động tính số ngày còn hiệu lực | View `v_certificates`, cột `days_left` |
| Cảnh báo theo mốc 30, 15, 7 ngày | Bảng `alerts`, quy tắc ở mục 5 |

## 2. Chức năng nền tảng

| Mã | Chức năng | Cách đáp ứng |
|---|---|---|
| Na | Đăng nhập, đăng xuất | Mật khẩu băm bằng `password_hash`; tài khoản bị khoá không đăng nhập được; sai 5 lần thì tạm chặn tài khoản 15 phút; đổi mã phiên sau khi đăng nhập |
| Nb | Quản lý người dùng | Admin thêm, sửa, khoá, mở khoá, xoá và gán vai trò; xem mục 3.3 |
| Nc | CRUD đầy đủ | Chứng chỉ, tên miền, máy chủ, tổ chức cấp, người dùng |
| Nd | Tìm kiếm, lọc | Từ khoá trong mã, tên chính, tên phụ, serial, fingerprint (viết hoa hay có dấu `:` đều tìm được). Lọc theo trạng thái, tổ chức cấp, tên miền, máy chủ, khoảng ngày hết hạn |
| Ne | Thống kê, báo cáo | Bảng và biểu đồ, xem D8, D9 |
| Nf | Kiểm tra dữ liệu bằng JavaScript và PHP | JavaScript báo lỗi nhanh trên form; PHP kiểm tra lại mọi dữ liệu; ràng buộc CSDL là lớp chặn cuối |
| Ng | Khoá chính, khoá ngoại, quan hệ | 9 bảng, xem ERD |
| Nh | Dashboard tổng quan | Số chứng chỉ theo trạng thái, số cảnh báo mới, danh sách sắp hết hạn |
| Ni | Bảo mật | Xem mục 7 |
| Nj | Dữ liệu mẫu | `database/seed.sql`, đủ mọi trạng thái và mốc cảnh báo |

## 3. Quy tắc kiểm tra dữ liệu

### 3.1. Chứng chỉ

| Trường | Quy tắc |
|---|---|
| Mã quản lý | Bắt buộc, tối đa 30 ký tự, không trùng |
| Fingerprint SHA-256 | Không bắt buộc. Bỏ dấu `:` và khoảng trắng, đổi về chữ thường, phải đúng 64 ký tự hex, không trùng |
| Tên chính | Bắt buộc, tối đa 255 ký tự |
| Tổ chức cấp, tên miền | Bắt buộc, chọn từ danh sách |
| Ngày bắt đầu, ngày hết hạn | Bắt buộc, ngày hết hạn phải sau ngày bắt đầu |
| Thuật toán khoá, độ dài khoá, thuật toán ký | Bắt buộc; độ dài khoá là số dương |

- Form sửa dùng để sửa thông tin nhập sai và không ghi lịch sử. Chứng chỉ được cấp lại thì phải dùng chức năng Gia hạn.
- Xoá chứng chỉ phải xác nhận, và xoá kèm cảnh báo cùng lịch sử gia hạn của nó.
- Thu hồi phải xác nhận và không hoàn tác được; dữ liệu vẫn được giữ.

### 3.2. Tên miền, máy chủ

- IP phải là IPv4 hoặc IPv6 hợp lệ. Cổng từ 1 đến 65535. Môi trường là `production`, `staging` hoặc `development`.
- Tên miền đúng định dạng và không trùng.
- Máy chủ còn tên miền, tên miền còn chứng chỉ thì không xoá được; hệ thống báo rõ lý do.

### 3.3. Người dùng

- Tên đăng nhập bắt buộc, 3–50 ký tự gồm chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang; không trùng.
- Mật khẩu 8–72 ký tự. Admin đặt lại được mật khẩu cho người khác mà không cần mật khẩu cũ, vì hệ thống không có chức năng quên mật khẩu.
- Mở khoá thì xoá luôn trạng thái tạm chặn do nhập sai mật khẩu.
- Người dùng đã có lịch sử thao tác không xoá được, chỉ khoá được; hệ thống báo rõ lý do. Người chưa có lịch sử thì xoá được.
- Admin không tự khoá, tự xoá, hay tự đổi vai trò của mình, để hệ thống luôn còn ít nhất một Admin.
- Đổi mật khẩu phải nhập đúng mật khẩu cũ.

## 4. Trạng thái chứng chỉ

Hệ thống xét lần lượt từ trên xuống và lấy dòng đầu tiên đúng:

| Trạng thái | Điều kiện | Vì sao ở vị trí này |
|---|---|---|
| Đã thu hồi | Đã bấm thu hồi | Dù còn hạn cũng không được tin |
| Chưa hiệu lực | Chưa tới ngày bắt đầu | Chưa dùng được |
| Hết hạn | Đã qua ngày hết hạn | |
| Sắp hết hạn | Còn tối đa 30 ngày | |
| Còn hiệu lực | Các trường hợp còn lại | |

Trạng thái và số ngày không lưu trong bảng, mà tính mỗi lần đọc, nên luôn đúng theo ngày hiện tại.

## 5. Cảnh báo

- Chỉ tạo cảnh báo cho chứng chỉ ở trạng thái **Sắp hết hạn**.
- Tạo theo **mốc gần nhất** đã chạm: còn tối đa 7 ngày là mốc 7, tối đa 15 ngày là mốc 15, tối đa 30 ngày là mốc 30. Ví dụ thêm một chứng chỉ còn 5 ngày thì chỉ tạo cảnh báo mốc 7.
- Mỗi mốc chỉ tạo một lần trong một chu kỳ. Sau khi gia hạn, ngày hết hạn đổi nên các mốc được tính lại từ đầu.
- Việc tạo cảnh báo chạy khi mở Dashboard hoặc trang Cảnh báo. Cảnh báo chỉ hiển thị trong hệ thống, nên chỉ cần tạo khi có người xem.
- Trang Cảnh báo chỉ hiện cảnh báo của chu kỳ hiện tại. Cảnh báo cũ vẫn được giữ.
- Chứng chỉ đã hết hạn vẫn hiện cảnh báo, vì đó là trường hợp gấp nhất. Chứng chỉ đã thu hồi thì không hiện, vì không còn việc gì để xử lý.
- "Đã xem" tính chung cho mọi người dùng: một người đánh dấu thì mọi người đều thấy là đã xem.

## 6. Gia hạn

- Chặn gia hạn chứng chỉ đã thu hồi; muốn dùng lại thì thêm chứng chỉ mới.
- Ngày hết hạn mới phải sau ngày hết hạn hiện tại và sau thời điểm gia hạn. Được gia hạn chứng chỉ đã hết hạn.
- Fingerprint và serial mới là tuỳ chọn, cùng quy tắc ở mục 3.1. Để trống thì chứng chỉ hiện "Chưa có", vì giá trị cũ không còn đúng với chứng chỉ mới; giá trị cũ vẫn nằm trong lịch sử.
- Gia hạn không đổi ngày bắt đầu. Cần sửa ngày này thì dùng form Sửa; thay đổi đó không ghi vào lịch sử gia hạn.
- Lưu lịch sử và cập nhật chứng chỉ nằm trong một giao dịch: lỗi ở bước nào thì không bước nào có hiệu lực.

## 7. Yêu cầu bảo mật và vận hành

| Nội dung | Cách làm |
|---|---|
| SQL Injection | Mọi truy vấn có dữ liệu người dùng đều dùng Prepared Statements qua PDO |
| Phân quyền | Kiểm tra trong PHP ở mọi yêu cầu |
| Giả mạo yêu cầu (CSRF) | Thao tác thay đổi dữ liệu dùng POST kèm mã xác thực |
| Chèn mã độc (XSS) | Dữ liệu hiển thị đều qua `htmlspecialchars` |
| Báo cáo CSV | Có BOM UTF-8 để Excel hiển thị đúng tiếng Việt; ô bắt đầu bằng `=`, `+`, `-`, `@` được vô hiệu hoá để không chạy thành công thức |
| Múi giờ | `Asia/Ho_Chi_Minh` cho PHP và `+07:00` cho CSDL, đặt trong code |

## 8. Chức năng mở rộng

Ngoài yêu cầu của đề, chỉ làm sau khi đủ 19 chức năng. Báo cáo ghi rõ là "chức năng mở rộng".

| Chức năng | Mô tả |
|---|---|
| Nhật ký hoạt động | Ai làm gì, lúc nào; không ai sửa hay xoá được nhật ký |
| Đọc chứng chỉ thật | Nhập `tên miền:cổng`, hệ thống đọc rồi tự điền form; chặn địa chỉ IP nội bộ |
| Chấm điểm an toàn | Cảnh báo SHA-1, RSA dưới 2048 bit, chứng chỉ tự ký, chuỗi thiếu; nhận diện thuật toán GOST |

**Ngoài phạm vi:** tự gia hạn qua ACME, quét cả dải mạng, xác thực hai lớp, gửi cảnh báo qua email.
