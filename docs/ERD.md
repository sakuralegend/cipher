# ERD — Hệ thống quản lý chứng chỉ số và thời hạn sử dụng

Nguồn sự thật là [`database/schema.sql`](../database/schema.sql). Sơ đồ này vẽ lại để dễ đọc. GitHub
hiển thị trực tiếp; VS Code cần tiện ích xem Mermaid.

```mermaid
erDiagram
    roles ||--o{ users : "gán cho"
    users ||--o{ certificates : "tạo"
    users ||--o{ renewals : "thực hiện"
    users |o--o{ audit_logs : "gây ra"
    servers ||--o{ domains : "chứa"
    domains ||--o{ certificates : "được bảo vệ bởi"
    certificate_authorities ||--o{ certificates : "cấp"
    certificates ||--o{ renewals : "có lịch sử"
    certificates ||--o{ alerts : "phát sinh"

    roles {
        int id PK
        varchar name UK
    }
    users {
        int id PK
        varchar username UK
        varchar password_hash
        varchar full_name
        int role_id FK
        tinyint is_locked
        tinyint failed_logins
        datetime locked_until
    }
    servers {
        int id PK
        varchar hostname
        varchar ip_address
        smallint port
        enum environment
        varchar owner
    }
    domains {
        int id PK
        varchar domain_name UK
        int server_id FK
    }
    certificate_authorities {
        int id PK
        varchar name UK
        varchar country
        tinyint is_trusted
    }
    certificates {
        int id PK
        varchar cert_code UK
        char fingerprint_sha256 UK "được trống"
        varchar serial
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
    alerts {
        int id PK
        int certificate_id FK
        datetime valid_to "UNIQUE cùng certificate_id, threshold_days"
        tinyint threshold_days "30, 15 hoặc 7"
        enum status
        datetime created_at
    }
    audit_logs {
        int id PK
        int user_id FK "được trống"
        varchar action
        varchar object_type
        int object_id "cố ý không có khoá ngoại"
        text detail
        varchar ip
        datetime created_at
    }
```

## Khi xoá bản ghi cha

| Quan hệ | Khi xoá cha | Vì sao |
|---|---|---|
| `certificates` → `renewals`, `alerts` | Xoá kèm | Không còn ý nghĩa khi không còn chứng chỉ |
| Các quan hệ còn lại | Chặn | Không để mất dữ liệu hàng loạt, giữ dấu vết người thao tác |

## Dữ liệu tính ra, không lưu

View `v_certificates` gồm mọi cột của `certificates`, cộng thêm:

- `days_left`: số ngày từ hôm nay đến `valid_to`, âm nếu đã hết hạn.
- `status`, theo thứ tự ưu tiên:

| Giá trị | Nghĩa | Điều kiện |
|---|---|---|
| `revoked` | Đã thu hồi | `is_revoked = 1` |
| `not_yet_valid` | Chưa hiệu lực | `valid_from` sau hiện tại |
| `expired` | Đã hết hạn | `valid_to` trước hiện tại |
| `expiring` | Sắp hết hạn | Còn tối đa 30 ngày |
| `valid` | Còn hiệu lực | Các trường hợp còn lại |

Giao diện dựa vào `status`, không suy từ `days_left`: chứng chỉ hết hạn sáng nay vẫn có `days_left = 0`.
