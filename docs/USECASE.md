# Use Case — Hệ thống quản lý chứng chỉ số và thời hạn sử dụng

Hệ thống có hai tác nhân, và không có trang đăng ký công khai: Admin tạo mọi tài khoản.

- **Admin:** toàn quyền.
- **Nhân viên quản trị:** quản lý chứng chỉ và danh mục, nhưng không quản lý người dùng và không xoá chứng chỉ.

Mô tả chi tiết từng chức năng nằm ở [SPEC.md](SPEC.md).

```mermaid
flowchart LR
    NV(["Nhân viên quản trị"])
    AD(["Admin"])

    subgraph HT["Hệ thống quản lý chứng chỉ số"]
        direction TB
        UC01(["Đăng nhập, đăng xuất"])
        UC02(["Đổi mật khẩu, sửa thông tin cá nhân"])
        UC03(["Xem Dashboard"])
        UC04(["Xem, tìm kiếm, lọc chứng chỉ"])
        UC05(["Thêm, sửa chứng chỉ"])
        UC06(["Thu hồi chứng chỉ"])
        UC07(["Gia hạn chứng chỉ, xem lịch sử"])
        UC08(["Quản lý tên miền, máy chủ, tổ chức cấp"])
        UC09(["Xem và xử lý cảnh báo"])
        UC10(["Xem thống kê, xuất báo cáo"])
        UC11(["Xoá chứng chỉ"])
        UC12(["Quản lý người dùng, phân quyền"])
    end

    NV --- UC01
    NV --- UC02
    NV --- UC03
    NV --- UC04
    NV --- UC05
    NV --- UC06
    NV --- UC07
    NV --- UC08
    NV --- UC09
    NV --- UC10

    AD --- UC01
    AD --- UC02
    AD --- UC03
    AD --- UC04
    AD --- UC05
    AD --- UC06
    AD --- UC07
    AD --- UC08
    AD --- UC09
    AD --- UC10
    AD --- UC11
    AD --- UC12
```

## Bảng phân quyền

| Thao tác | Admin | Nhân viên quản trị | Khách chưa đăng nhập |
|---|---|---|---|
| Xem trang giới thiệu, đăng nhập | ✅ | ✅ | ✅ |
| Dashboard, xem và lọc chứng chỉ, cảnh báo, thống kê, báo cáo | ✅ | ✅ | ❌ |
| Thêm, sửa, thu hồi, gia hạn chứng chỉ | ✅ | ✅ | ❌ |
| Thêm, sửa, xoá tên miền, máy chủ, tổ chức cấp | ✅ | ✅ | ❌ |
| Xoá chứng chỉ | ✅ | ❌ | ❌ |
| Quản lý người dùng | ✅ | ❌ | ❌ |

Quyền được kiểm tra trong PHP ở mọi yêu cầu, không chỉ bằng cách ẩn nút trên giao diện.
