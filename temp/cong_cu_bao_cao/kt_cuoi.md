## 4. Kiểm tay theo kịch bản: file Excel dữ liệu mẫu

File `DU_LIEU_NHAP_TAY.xlsx` (nằm trong gói nộp) có 7 trang tính, mỗi trang là một form của web, cột xếp theo
đúng thứ tự ô trên form. Mỗi dòng ghi rõ là dòng **Đúng** (phải lưu được) hay dòng **Sai** (phải bị chặn), kết quả
mong đợi và lớp nào chặn (JavaScript hay PHP). Ô ngày là công thức tính theo hôm nay, nên mở file ngày nào cũng ra
đúng số ngày còn lại. Ngày 14/11/2026, cả 34 dòng đã được nhập vào web vừa nạp dữ liệu mẫu và đều cho đúng kết quả
mong đợi.

| Trang tính | Mở form | Dòng đúng | Dòng sai | Dòng sai kiểm điều gì |
|---|---|---|---|---|
| 1. Tổ chức cấp | Hạ tầng → Tổ chức cấp → Thêm | 2 | 2 | Tên trùng; tên để trống |
| 2. Máy chủ | Hạ tầng → Máy chủ → Thêm | 2 | 2 | IP không hợp lệ; cổng ngoài 1–65535 |
| 3. Tên miền | Hạ tầng → Tên miền → Thêm | 2 | 2 | Tên miền trùng; sai định dạng |
| 4. Chứng chỉ | Chứng chỉ → Thêm chứng chỉ, tab Nhập thủ công | 5 | 3 | Fingerprint trùng; ngày hết hạn trước ngày cấp; tên miền chưa có trong danh mục |
| 5. Gia hạn | Mở một chứng chỉ → Gia hạn | 1 | 2 | Hạn mới không sau hạn cũ; fingerprint trùng |
| 6. Người dùng | Hệ thống → Người dùng → Thêm | 1 | 3 | Mật khẩu không đủ chữ và số; tên đăng nhập trùng; hai lần nhập mật khẩu lệch nhau |
| 7. Nhập tự động | Chứng chỉ → Thêm chứng chỉ, tab Nhập tự động | 3 | 2 | Địa chỉ nội bộ 127.0.0.1; tên miền không có thật |
| **Cộng** | | **16** | **18** | |

Năm dòng đúng của trang Chứng chỉ rơi vào năm tình huống khác nhau (còn hiệu lực, sắp hết hạn ở các mốc, đã hết
hạn, chưa hiệu lực), nên nhập xong là thấy ngay trạng thái, số ngày còn lại và cảnh báo tự tính đúng. Dòng đúng của
trang Gia hạn làm chứng chỉ rời trang Cảnh báo và để lại một dòng trong Lịch sử gia hạn.

![Dòng sai của trang Gia hạn: hạn mới trước hạn hiện tại bị chặn, câu lỗi nằm ngay dưới ô](anh/gia_han_loi.jpg)

![Dòng sai của trang Nhập tự động: địa chỉ nội bộ 127.0.0.1 bị từ chối](anh/doc_noi_bo.jpg)

## 5. Kiểm trên bản chạy thật

Ngày 14/11/2026, kiểm từ máy ở ngoài, gửi yêu cầu tới <https://cert.cipher.id.vn> khi chưa đăng nhập.

| Ca kiểm | Kết quả mong đợi | Kết quả thật | Đạt |
|---|---|---|---|
| Mở trang giới thiệu | 200, hiện trang | 200 | ✅ |
| Mở trang đăng nhập | 200, hiện form | 200 | ✅ |
| Mở Dashboard khi chưa đăng nhập | Chuyển về trang đăng nhập | 302 → `/login.php` | ✅ |
| Tải báo cáo CSV khi chưa đăng nhập | Chuyển về trang đăng nhập, không có dữ liệu | 302 → `/login.php` | ✅ |
| Mở trang Nhật ký khi chưa đăng nhập | Chuyển về trang đăng nhập | 302 → `/login.php` | ✅ |
| Mở thẳng file code `/src/auth.php` | Không tìm thấy | 404 | ✅ |
| Mở thẳng file cấu hình `/config/db.php` | Không tìm thấy | 404 | ✅ |
| Mở thẳng `/database/schema.sql` | Không tìm thấy | 404 | ✅ |
| Mở thẳng `/README.md`, `/CHAY.bat` | Không tìm thấy | 404, 404 | ✅ |
| Đăng xuất bằng đường link (GET) | Từ chối, chỉ nhận nút trên trang | 405 | ✅ |
| Gửi form đăng nhập không có mã CSRF | Từ chối | 403 | ✅ |
| Mở địa chỉ `http://` | Chuyển sang `https://` | 301 → `https://cert.cipher.id.vn/` | ✅ |
| Mở địa chỉ không tồn tại | Không tìm thấy, không chạy trang khác thay | 404 | ✅ |
| Đọc header trả về của trang đầu | Đủ 5 header bảo mật, cookie phiên có `Secure`, `HttpOnly`, `SameSite=Lax` | Đủ cả 5, cookie đúng | ✅ |

![Header thật do máy chủ trả về ngày 14/11/2026: Strict-Transport-Security, Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Referrer-Policy](anh/header_bao_mat.jpg)

Bản chạy thật dùng cùng dữ liệu mẫu và cùng hai tài khoản như gói nộp trong thời gian chấm bài, nên người chấm
thấy cùng một thứ ở máy mình và ở trên mạng. Ngày 14/11/2026 đã rà lại trên bản này: đủ 9 chức năng của đề 69,
3 tiêu chí và 10 chức năng nền tảng.

Hai cách cài trong hướng dẫn cũng đã được chạy thử trên máy sạch dữ liệu: bấm `CHAY.bat` (kể cả trường hợp bấm
ngay trong file nén, file sẽ dừng và bảo giải nén), và cài tay vào `htdocs` bằng Apache với phpMyAdmin. Nạp xong
có 2 tài khoản, 50 chứng chỉ đủ 5 trạng thái (6 đã hết hạn, 13 sắp hết hạn, 22 còn hiệu lực, 3 chưa hiệu lực, 6 đã
thu hồi). Số chứng chỉ theo trạng thái sẽ đổi dần theo ngày nạp.

## 6. Kiểm thử bảo mật

Đợt kiểm thử toàn diện ngày 12/11/2026 thử các kiểu tấn công thường gặp lên hệ thống đang chạy. Không tìm thấy lỗ
hổng; các điểm cần sửa về chức năng tìm ra trong đợt này đều đã sửa và có test (mục 7).

| Phép thử | Cách thử | Kết quả |
|---|---|---|
| SQL Injection | Gõ dấu nháy, `%`, `_`, câu lệnh SQL vào ô tìm kiếm, bộ lọc, tham số sắp xếp trên địa chỉ trang | Dữ liệu được hiểu đúng theo chữ; tên cột lạ về cách xếp mặc định |
| XSS | Lưu tên chứa thẻ `<script>` vào các ô chữ rồi mở mọi trang hiện tên đó | Thẻ hiện ra dạng chữ, không chạy |
| CSRF | Gửi form đổi dữ liệu không kèm mã, hoặc mã sai | Bị từ chối, dữ liệu không đổi |
| Phân quyền | Đăng nhập Nhân viên rồi gõ thẳng địa chỉ trang của Admin, gửi thẳng lệnh xoá | Nhận lỗi 403 hoặc câu từ chối, dữ liệu không đổi |
| SSRF | Cho hệ thống đọc chứng chỉ ở `127.0.0.1`, `10.0.0.5`, IPv6 giấu IPv4, cổng ngoài danh sách | Bị chặn trước khi kết nối, nhật ký ghi mức Nguy hiểm |
| Phiên | Khoá, xoá tài khoản hoặc đổi mật khẩu khi người đó đang đăng nhập ở nơi khác | Phiên kia bị đẩy về trang đăng nhập ở yêu cầu tiếp theo |
| Dò mật khẩu | Nhập sai liên tiếp 5 lần, lần 6 nhập đúng | Lần 6 vẫn bị từ chối trong 15 phút, cùng một câu báo |

![Nhân viên tự gõ địa chỉ trang Người dùng của Admin thì nhận lỗi 403](anh/nv_403.jpg)

![Lọc Nhật ký theo mức Nguy hiểm: các lần đọc địa chỉ nội bộ và đăng nhập khi đang bị chặn hiện ra ngay](anh/nhat_ky_nguy_hiem.jpg)

## 7. Lỗi phát hiện trong quá trình làm

Mỗi lỗi khi sửa đều kèm đúng một test bắt lỗi đó, để lỗi không quay lại.

| Ngày | Lỗi | Nguyên nhân | Test giữ lỗi không quay lại |
|---|---|---|---|
| 06/11/2026 | Gửi form có ký tự sai mã hoá làm trang lỗi 500 | CSDL chế độ strict từ chối byte không phải UTF-8 | `o_form_sai_ma_hoa_hoac_la_mang_duoc_coi_la_trong` |
| 07/11/2026 | Ngày 31 thì biểu đồ 12 tháng mất tháng 2 | Trong PHP, 31/01 cộng 1 tháng ra 03/03 | `het_han_theo_thang_khong_nhay_coc_khi_hom_nay_la_ngay_31` |
| 08/11/2026 | Tên miền bị bẻ dòng giữa chữ trên điện thoại | Cột hẹp, trình duyệt tự bẻ ở chỗ bất kỳ | `ten_mien_chi_xuong_dong_o_dau_cham` |
| 09/11/2026 | Chứng chỉ đã thu hồi vẫn sửa được, lách được luật chặn gia hạn | Form Sửa không xét cờ thu hồi | `chung_chi_da_thu_hoi_khong_sua_duoc` |
| 12/11/2026 | Chứng chỉ còn 4 ngày hiện cả cảnh báo "7 ngày" lẫn "15 ngày", chuông đếm thừa | Trang hiện mọi mốc đã tạo trong chu kỳ | `moi_chung_chi_chi_hien_mot_canh_bao_la_moc_gap_nhat` |
| 12/11/2026 | Hạn rơi vào hôm nay ghi "Quá hạn 0 ngày" | Câu mô tả chỉ xét dấu của số ngày | `chung_chi_het_han_trong_hom_nay_ghi_het_han_hom_nay` |
| 12/11/2026 | Mật khẩu "abcdefgh" được chấp nhận | Chỉ kiểm độ dài | `mat_khau_moi_phai_co_ca_chu_va_so` |
| 12/11/2026 | Đổi mật khẩu xong, phiên ở máy khác vẫn dùng tiếp được | Phiên chỉ nhớ mã người dùng | `doi_mat_khau_dang_xuat_phien_khac_giu_phien_dang_doi` |
| 12/11/2026 | 20/50 chứng chỉ mẫu ra hạng C | Dữ liệu mẫu đặt thời hạn 365–730 ngày, trái luật 200 ngày | `du_lieu_mau_chi_chung_chi_co_y_moi_ra_hang_c_hoac_f` |
| 13/11/2026 | Bấm `CHAY.bat` ngay trong file nén thì báo "Xong" rồi web không chạy | Chương trình nén chỉ bung riêng file đó ra thư mục tạm | `chay_bat_dung_khi_chay_trong_file_nen_hoac_nap_hong` |
| 14/11/2026 | Máy chủ ở xa bị báo "không trả lời" dù vẫn chạy | Chỉ chờ 5 giây, trong khi bắt tay TLS có lúc mất đúng 5 giây | `doc_chung_chi_cho_du_lau_cho_may_chu_o_xa` |

## 8. Giới hạn, chưa kiểm

- **Phần JavaScript** (kiểm tra form trên trình duyệt, biểu đồ, thanh tiến trình, dán ngày) không có test tự động
  chạy trong trình duyệt; test chỉ kiểm rằng đoạn xử lý có mặt, còn hành vi thì kiểm bằng tay. Mọi quy tắc dữ liệu
  đều được PHP kiểm lại và có test.
- **Đọc chứng chỉ thật** kết nối ra internet: test kiểm phần chặn địa chỉ và phần đổi chứng chỉ thành giá trị
  các ô bằng chứng chỉ mẫu, không kết nối mạng thật, để test không phụ thuộc mạng. Phần kết nối thật đã thử bằng
  tay: nhập hàng loạt 50 site thật ngày 09/11/2026 đọc mất 41 giây, lưu được 50/50; ngày 14/11/2026 đọc lại
  github.com, vnexpress.net, expired.badssl.com đều được.
- Chưa kiểm hiệu năng với số lượng lớn (hàng chục nghìn chứng chỉ) và nhiều người dùng cùng lúc. Sắp xếp theo độ
  an toàn chấm hạng bằng PHP trên toàn bộ kết quả lọc, hợp với vài trăm chứng chỉ.
