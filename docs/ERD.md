# ERD: Hệ thống quản lý chứng chỉ số và thời hạn sử dụng

Nguồn sự thật là [`database/schema.sql`](../database/schema.sql). CSDL có 9 bảng, 1 view và 2 trigger. Mục 1 tóm tắt
vai trò của từng bảng, mục 2 là sơ đồ ERD, các mục sau nói về ràng buộc, quy tắc khi xoá, trigger và dữ liệu tính ra.

## 1. Các thực thể và vai trò

| Bảng | Lưu gì | Quan hệ chính |
|---|---|---|
| `roles` | Vai trò: Admin, Nhân viên quản trị | Một vai trò có nhiều người dùng |
| `users` | Tài khoản: tên đăng nhập, mật khẩu dạng băm, họ tên, trạng thái khoá, số lần sai, thời điểm xoá | Thuộc một vai trò; là người tạo chứng chỉ, người gia hạn, người làm trong nhật ký |
| `servers` | Máy chủ: tên, IP, cổng, môi trường, người phụ trách | Một máy chủ chứa nhiều tên miền |
| `domains` | Tên miền | Thuộc một máy chủ; có nhiều chứng chỉ |
| `certificate_authorities` | Tổ chức cấp chứng chỉ (CA): tên, quốc gia, có tin cậy không | Cấp nhiều chứng chỉ |
| `certificates` | Chứng chỉ: mã quản lý, fingerprint, serial, tên chính, tên phụ, ngày cấp (`valid_from`), ngày hết hạn (`valid_to`), thuật toán, cờ thu hồi | Thuộc một tên miền, một tổ chức cấp, một người tạo |
| `renewals` | Lịch sử gia hạn: hạn cũ, hạn mới, fingerprint và serial cũ, người gia hạn, ghi chú | Mỗi dòng thuộc một chứng chỉ |
| `alerts` | Cảnh báo theo mốc 30, 15, 7 ngày; trạng thái mới hoặc đã xem | Mỗi dòng thuộc một chứng chỉ, trong một chu kỳ hạn |
| `audit_logs` | Nhật ký hoạt động: ai, làm gì, với đối tượng nào, lúc nào, từ IP nào | Người làm được trống (đăng nhập sai) |

## 2. Sơ đồ thực thể – liên kết (ERD)

Sơ đồ dưới đây **sinh từ chính CSDL** vừa nạp `schema.sql` (lệnh `php tests/chay.php --erd`), không vẽ tay, nên lúc
nào cũng đúng bằng code. Chữ trong ngoặc kép cạnh một cột là chú thích của cột đó trong `schema.sql`; bản in của báo cáo
lược các chú thích này để sơ đồ đủ lớn, ý nghĩa cột có ở mục 1 và mục 3.

Cách đọc: mỗi khung là một bảng; `PK` là khoá chính, `FK` là khoá ngoại, `UK` là không được trùng. Đường nối là
quan hệ một - nhiều, chữ trên đường là tên cột khoá ngoại: `||` phía cha nghĩa là bắt buộc có đúng một, `|o` là
không hoặc một (cột được để trống), `o{` phía con là không, một hoặc nhiều.

```mermaid
erDiagram
    certificates ||--o{ alerts : "certificate_id"
    users |o--o{ audit_logs : "user_id"
    certificate_authorities ||--o{ certificates : "ca_id"
    users ||--o{ certificates : "created_by"
    domains ||--o{ certificates : "domain_id"
    servers ||--o{ domains : "server_id"
    certificates ||--o{ renewals : "certificate_id"
    users ||--o{ renewals : "renewed_by"
    roles ||--o{ users : "role_id"
    alerts {
        int id PK
        int certificate_id FK
        datetime valid_to "Hạn của chu kỳ mà cảnh báo này thuộc về."
        tinyint threshold_days
        enum status
        datetime created_at
    }
    audit_logs {
        int id PK
        int user_id FK "NULL khi chưa biết là ai, ví dụ đăng nhập sai."
        varchar action
        varchar object_type
        int object_id
        text detail
        varchar ip
        datetime created_at
    }
    certificates {
        int id PK
        varchar cert_code UK
        char fingerprint_sha256 UK
        varchar serial "Số sê-ri có thể rất dài, nên lưu dạng chữ."
        varchar subject_cn
        text san
        int ca_id FK
        int domain_id FK
        datetime valid_from
        datetime valid_to
        varchar key_algorithm
        smallint key_size
        varchar signature_algorithm
        tinyint is_revoked
        int created_by FK
    }
    certificate_authorities {
        int id PK
        varchar name UK
        varchar country
        tinyint is_trusted
    }
    domains {
        int id PK
        varchar domain_name UK
        int server_id FK
    }
    renewals {
        int id PK
        int certificate_id FK
        datetime old_valid_to
        datetime new_valid_to
        char old_fingerprint
        varchar old_serial
        int renewed_by FK
        datetime renewed_at
        varchar note
    }
    roles {
        int id PK
        varchar name UK
    }
    servers {
        int id PK
        varchar hostname
        varchar ip_address "45 ký tự đủ cho cả IPv6."
        smallint port
        enum environment
        varchar owner
    }
    users {
        int id PK
        varchar username "Không trùng giữa tài khoản còn dùng: xem username_dang_dung."
        varchar password_hash
        varchar full_name
        int role_id FK
        tinyint is_locked "Admin khoá, tới khi Admin mở lại."
        tinyint failed_logins
        datetime locked_until
        datetime deleted_at "Lúc Admin xoá tài khoản; NULL là còn dùng."
        varchar username_dang_dung UK "Tên đăng nhập của tài khoản còn dùng."
    }
```

## 3. Các ràng buộc toàn vẹn

| Ràng buộc | Ở đâu | Để làm gì |
|---|---|---|
| `UNIQUE (cert_code)`, `UNIQUE (fingerprint_sha256)` | `certificates` | Tiêu chí của đề: mỗi chứng chỉ có mã quản lý hoặc fingerprint, không trùng |
| `CHECK` fingerprint đúng 64 ký tự hex chữ thường | `certificates` | `AB:CD` và `abcd` là cùng một vân tay; ép về một dạng thì `UNIQUE` mới bắt được |
| `CHECK (valid_to > valid_from)` | `certificates` | Ngày hết hạn phải sau ngày cấp, kể cả khi lệnh đi thẳng vào CSDL |
| `CHECK (new_valid_to > old_valid_to)` | `renewals` | Gia hạn phải làm hạn dài ra |
| `UNIQUE (certificate_id, valid_to, threshold_days)`, `CHECK` mốc thuộc 30, 15, 7 | `alerts` | Mỗi mốc chỉ một cảnh báo trong một chu kỳ; gia hạn đổi `valid_to` nên tạo lại được |
| `UNIQUE (username_dang_dung)` | `users` | Cột tự tính: bằng tên đăng nhập khi tài khoản còn dùng, trống khi đã xoá. Hai tài khoản đang dùng không trùng tên, còn tên của tài khoản đã xoá thì dùng lại được |
| `CHECK` cổng từ 1 đến 65535 | `servers` | Lớp chặn cuối sau JavaScript và PHP |

## 4. Toàn vẹn tham chiếu khi xoá bản ghi cha

| Quan hệ | Khi xoá cha | Vì sao |
|---|---|---|
| `certificates` → `renewals`, `alerts` | Xoá kèm | Không còn ý nghĩa khi không còn chứng chỉ |
| Các quan hệ còn lại | Chặn | Không để mất dữ liệu hàng loạt, giữ dấu vết người thao tác |

Vì khoá ngoại chặn xoá người dùng đã có dấu vết, chức năng xoá người dùng chỉ **đánh dấu** bằng cột
`deleted_at`: dòng vẫn còn để nhật ký, chứng chỉ, lịch sử gia hạn trỏ tới, nhưng tài khoản không đăng nhập được nữa.

Cột `object_id` của `audit_logs` cố ý không có khoá ngoại: xoá chứng chỉ thì vết trong nhật ký vẫn còn.

## 5. Trigger cơ sở dữ liệu

| Trigger | Bảng | Làm gì |
|---|---|---|
| `nhat_ky_cam_sua` | `audit_logs` | Từ chối mọi lệnh `UPDATE` |
| `nhat_ky_cam_xoa` | `audit_logs` | Từ chối mọi lệnh `DELETE` |

Nhờ hai trigger này, nhật ký chỉ thêm được, kể cả khi ai đó gõ lệnh thẳng vào CSDL. `TRUNCATE TABLE audit_logs`
vẫn dọn được cả bảng, nhưng bảng trống trơn thì nhìn là biết đã bị dọn.

## 6. Thuộc tính dẫn xuất

View `v_certificates` gồm mọi cột của `certificates`, cộng thêm:

- `days_left`: số ngày từ hôm nay đến `valid_to`, âm nếu đã hết hạn.
- `status`, theo thứ tự ưu tiên:

| Giá trị | Nghĩa | Điều kiện |
|---|---|---|
| `revoked` | Đã thu hồi | `is_revoked = 1` |
| `not_yet_valid` | Chưa hiệu lực | `valid_from` (ngày cấp) sau hiện tại |
| `expired` | Đã hết hạn | `valid_to` trước hiện tại |
| `expiring` | Sắp hết hạn | Còn tối đa 30 ngày |
| `valid` | Còn hiệu lực | Các trường hợp còn lại |

Giao diện dựa vào `status`, không suy từ `days_left`: chứng chỉ hết hạn sáng nay vẫn có `days_left = 0`.

Hạng an toàn (A, B, C, F) và mức của nhật ký (Thông tin, Cảnh báo, Nguy hiểm) cũng là dữ liệu tính ra lúc xem,
bằng PHP, từ các cột đã có; không có cột hay bảng riêng.
