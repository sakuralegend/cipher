# Báo cáo kiểm thử: Hệ thống quản lý chứng chỉ số và thời hạn sử dụng

## 1. Kết quả

| | |
|---|---|
| **Ngày chạy** | 08/11/2026 |
| **Bộ test tự động** | **89/89 đạt**, chạy mất 22 giây |
| **Kiểm tra cú pháp** | Mọi file `.php` qua `php -l`, không lỗi |
| **Kiểm tay trên bản chạy thật** | 12/12 ca đạt (mục 4) |
| **Môi trường** | Windows 11 · XAMPP 8.2.12: PHP 8.2.12, MariaDB 10.4.32 · bản chạy thật: Ubuntu 24.04, PHP 8.3, MariaDB, Caddy |

## 2. Cách kiểm thử

Hệ thống được kiểm ở 3 lớp:

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
3. **Kiểm tay** trên bản chạy thật qua internet (mục 4), và xem giao diện bằng mắt: mọi thay đổi giao diện đều
   được chụp màn hình ở khổ máy tính và khổ điện thoại 390px trước khi giao.

**Chạy lại:** cần MySQL đang bật và CSDL `cipher_test` đã tạo (README mục 2, bước 5), rồi
`C:\xampp\php\php.exe tests\chay.php`. Mỗi test in ✅ hoặc ❌ kèm lý do; dòng cuối là `XANH: 89 test` hoặc
`ĐỎ: số test đỏ/89`.

## 3. Danh sách test theo chức năng

Tên test viết không dấu, bỏ tiền tố `test_`; tìm đúng tên đó trong `tests/chay.php` để xem code của test.
Cột phải là câu Luật của test. Cả 89 test đều **đạt** ngày 08/11/2026.

| Nhóm | Số test | Phụ trách |
|---|---|---|
| Đăng nhập, phiên, phân quyền | 11 | Thành viên 1 |
| Người dùng | 9 | Thành viên 1 |
| CSDL, ràng buộc, dữ liệu mẫu | 8 | Thành viên 1 |
| Nhật ký hoạt động (mở rộng 1) | 5 | Thành viên 1 |
| Chứng chỉ: thêm, sửa, thu hồi, xoá | 9 | Thành viên 2 |
| Gia hạn | 5 | Thành viên 2 |
| Đọc chứng chỉ thật (mở rộng 2) | 3 | Thành viên 2 |
| Danh sách, tìm kiếm, lọc, sắp xếp | 8 | Thành viên 2 |
| Danh mục: tên miền, máy chủ, tổ chức cấp | 7 | Thành viên 3 |
| Cảnh báo | 7 | Thành viên 3 |
| Dashboard, thống kê, báo cáo | 8 | Thành viên 3 |
| Giao diện, triển khai | 9 | Thành viên 3 |
| **Tổng** | **89** | |

### Đăng nhập, phiên, phân quyền (11 test, Thành viên 1)

| Test | Quy tắc được kiểm |
|---|---|
| `dang_nhap_dung_thi_vao_duoc_va_dem_lai_so_lan_sai` | Đăng nhập đúng thì vào được và số lần sai được đếm lại từ đầu. |
| `sai_mat_khau_hay_sai_ten_deu_bao_cung_mot_loi` | Sai mật khẩu, sai tên, hay đang tạm chặn đều báo đúng một câu lỗi. |
| `tai_khoan_bi_khoa_khong_dang_nhap_duoc` | Tài khoản bị Admin khoá thì nhập đúng mật khẩu cũng không vào được. |
| `sai_5_lan_thi_tam_chan_ke_ca_khi_nhap_dung` | Sai 5 lần thì tài khoản bị tạm chặn 15 phút, kể cả khi sau đó nhập đúng. |
| `het_15_phut_thi_dang_nhap_lai_duoc` | Hết thời gian tạm chặn thì đăng nhập lại được. |
| `nguoi_dung_bi_khoa_giua_phien_thi_bi_day_ra` | Người dùng bị khoá giữa phiên thì bị đẩy ra ở yêu cầu tiếp theo. |
| `nhan_vien_khong_co_quyen_admin` | Nhân viên quản trị không có quyền Admin; Admin thì có. |
| `ma_csrf_sai_bi_tu_choi` | Mã CSRF sai, thiếu, hoặc không phải chuỗi đều bị từ chối. |
| `moi_trang_quan_tri_deu_kiem_tra_dang_nhap` | Mọi file trong public/admin/ đều gọi yeuCauDangNhap() hoặc yeuCauAdmin(). |
| `moi_trang_nhan_post_deu_kiem_tra_csrf` | Mọi trang xử lý yêu cầu POST đều gọi yeuCauCsrf(). |
| `dang_xuat_xong_ve_landing` | Đăng xuất xong thì về landing (index.php), không về trang đăng nhập. |

### Người dùng (9 test, Thành viên 1)

| Test | Quy tắc được kiểm |
|---|---|
| `du_lieu_nguoi_dung_sai_bi_tu_choi` | Dữ liệu người dùng sai bị từ chối đúng ô: tên, họ tên, vai trò, mật khẩu ngắn, dài, nhập lại lệch. |
| `ten_dang_nhap_trung_bao_loi_ro_rang` | Tên đăng nhập trùng được báo lỗi rõ ràng, không làm hỏng trang. |
| `mat_khau_luon_luu_dang_bam` | Mật khẩu luôn được lưu dạng băm, cả khi thêm, Admin đặt lại, và tự đổi. |
| `admin_khong_tu_khoa_tu_xoa_tu_doi_vai_tro` | Admin không tự khoá, tự xoá, hay tự đổi vai trò của mình. |
| `xoa_nguoi_co_lich_su_bao_chi_co_the_khoa` | Xoá người có lịch sử thì báo "chỉ có thể khoá"; người chưa có lịch sử thì xoá được. |
| `mo_khoa_xoa_ca_trang_thai_tam_chan` | Mở khoá xoá luôn trạng thái tạm chặn, và người dùng vào được ngay. |
| `doi_mat_khau_phai_dung_mat_khau_cu` | Đổi mật khẩu phải nhập đúng mật khẩu cũ, và mật khẩu mới phải hợp lệ. |
| `trang_quan_ly_nguoi_dung_chi_danh_cho_admin` | Mọi trang quản lý người dùng đều gọi yeuCauAdmin(), không chỉ yeuCauDangNhap(). |
| `khong_xoa_duoc_nguoi_dung_da_co_lich_su` | Người dùng đã có lịch sử không xoá được; người chưa có lịch sử thì xoá được. |

### CSDL, ràng buộc, dữ liệu mẫu (8 test, Thành viên 1)

| Test | Quy tắc được kiểm |
|---|---|
| `ket_noi_luon_o_mui_gio_viet_nam_va_che_do_strict` | Kết nối CSDL luôn ở múi giờ Việt Nam và chế độ strict, PHP cũng ở múi giờ Việt Nam. |
| `fingerprint_khong_duoc_trung` | Hai chứng chỉ không được có cùng fingerprint. |
| `ma_quan_ly_bat_buoc_con_fingerprint_duoc_trong_nhung_phai_chuan_hoa` | Mã quản lý là bắt buộc; fingerprint được trống, nhưng nếu có phải ở dạng đã chuẩn hoá. |
| `khong_xoa_duoc_danh_muc_con_du_lieu_phu_thuoc` | Không xoá được vai trò, máy chủ, tên miền, CA khi còn dữ liệu phụ thuộc. |
| `xoa_chung_chi_xoa_kem_canh_bao_va_lich_su` | Xoá chứng chỉ thì xoá kèm cảnh báo và lịch sử gia hạn của nó. |
| `du_lieu_mau_co_du_moc_va_trang_thai` | Dữ liệu mẫu có đủ mốc 30/15/7, mọi trạng thái, lịch sử gia hạn và tài khoản đăng nhập được. |
| `du_lieu_demo_du_50_chung_chi_du_trang_thai_va_khong_nap_trung` | seed_demo.sql nạp được sau seed.sql, đủ 50 chứng chỉ, đủ mọi trạng thái và ba mốc cảnh báo; chạy lần hai không thêm gì. |
| `o_form_sai_ma_hoa_hoac_la_mang_duoc_coi_la_trong` | Ô form không phải chuỗi UTF-8 hợp lệ được coi là trống, nên báo lỗi đúng ô thay vì làm chết trang. |

### Nhật ký hoạt động (mở rộng 1) (5 test, Thành viên 1)

| Test | Quy tắc được kiểm |
|---|---|
| `moi_thao_tac_deu_ghi_nhat_ky` | Mọi thao tác thay đổi dữ liệu, đăng nhập đúng, đăng nhập sai và đăng xuất đều ghi một dòng nhật ký: ai, làm gì, với đối tượng nào. |
| `csdl_tu_choi_sua_xoa_nhat_ky` | CSDL từ chối UPDATE và DELETE trên audit_logs (trigger), kể cả lệnh gõ thẳng vào CSDL; chỉ TRUNCATE dọn được cả bảng. |
| `nhom_he_thong_tren_menu_va_hai_trang_cua_no_chi_danh_cho_admin` | Trang Nhật ký và Người dùng chỉ Admin vào được, và nhóm "Hệ thống" (hai mục đó) trên menu chỉ hiện với Admin. |
| `nhat_ky_hien_ip_o_moi_do_rong_man_hinh` | Trang Nhật ký hiện IP ở mọi độ rộng màn hình: cột IP chỉ có từ 992px, màn hẹp hơn thì IP nằm dưới người làm. |
| *(test cấu hình triển khai)* | Máy chủ web chỉ nhận IP gốc của khách từ lớp chuyển tiếp chạy trên chính máy (127.0.0.1); kết nối khác không giả được IP. |

### Chứng chỉ: thêm, sửa, thu hồi, xoá (9 test, Thành viên 2)

| Test | Quy tắc được kiểm |
|---|---|
| `trang_thai_va_so_ngay_tinh_tu_valid_to` | Số ngày và trạng thái tính từ ngày tháng, ưu tiên thu hồi > chưa hiệu lực > hết hạn > sắp hết hạn. |
| `chung_chi_du_lieu_sai_bi_tu_choi_dung_o` | Chứng chỉ có dữ liệu sai bị từ chối đúng ô, và không được lưu. |
| `fingerprint_va_serial_duoc_chuan_hoa_truoc_khi_luu` | Fingerprint và serial được chuẩn hoá trước khi lưu; ô trống lưu thành NULL. |
| `ma_quan_ly_va_fingerprint_trung_bao_loi_ro_rang` | Mã quản lý trùng và fingerprint trùng được báo lỗi đúng ô, kể cả khi viết khác dạng. |
| `ngay_het_han_phai_sau_ngay_bat_dau` | Ngày hết hạn phải sau ngày bắt đầu; bằng nhau cũng bị từ chối. |
| `nhan_vien_khong_xoa_duoc_chung_chi_admin_thi_duoc` | Nhân viên không xoá được chứng chỉ; Admin thì được. |
| `thu_hoi_giu_du_lieu_va_khong_hoan_tac_duoc` | Thu hồi giữ nguyên dữ liệu, đổi trạng thái, và không hoàn tác được qua form sửa. |
| `ma_ke_tiep_khong_trung_ma_da_co` | Mã kế tiếp không trùng mã đã có, và không bị mã tự đặt làm hỏng. |
| `o_go_ten_mien_to_chuc_cap_chi_nhan_ten_co_trong_danh_muc` | Ô gõ Tên miền, Tổ chức cấp chỉ nhận tên có trong danh mục (không phân biệt hoa thường); tên lạ không lưu được. |

### Gia hạn (5 test, Thành viên 2)

| Test | Quy tắc được kiểm |
|---|---|
| `gia_han_luu_thong_tin_cu_va_cap_nhat_chung_chi` | Gia hạn chép hạn, fingerprint, serial cũ vào lịch sử rồi cập nhật chứng chỉ; ô trống lưu NULL. |
| `sau_gia_han_cac_moc_canh_bao_tinh_lai_tu_dau` | Sau khi gia hạn, cảnh báo cũ biến khỏi trang và các mốc được tính lại từ đầu. |
| `chan_gia_han_chung_chi_da_thu_hoi` | Chứng chỉ đã thu hồi hoặc không tồn tại không gia hạn được, và không để lại lịch sử. |
| `han_moi_phai_sau_han_cu_va_sau_hien_tai` | Hạn mới phải sau cả hạn hiện tại lẫn thời điểm gia hạn; dữ liệu sai bị từ chối đúng ô. |
| `gia_han_loi_giua_chung_thi_khong_doi_gi` | Gia hạn lỗi giữa chừng thì không bước nào có hiệu lực. |

### Đọc chứng chỉ thật (mở rộng 2) (3 test, Thành viên 2)

| Test | Quy tắc được kiểm |
|---|---|
| `doc_chung_chi_chan_dia_chi_noi_bo_va_cong_ngoai_danh_sach` | Đọc chứng chỉ chỉ kết nối tới địa chỉ công khai và cổng TLS trong danh sách; chặn nếu BẤT KỲ IP nào của tên miền là nội bộ. |
| `chung_chi_doc_duoc_dien_dung_cac_o` | Chứng chỉ đọc được điền đúng từng ô của form: tên chính, ngày theo giờ Việt Nam, thuật toán, độ dài khoá, ký, fingerprint, serial. |
| `doc_xong_tu_chon_ten_mien_va_to_chuc_cap_khong_tu_tao` | Đọc xong thì tên miền và tổ chức cấp được tự chọn khi trùng tên (tên miền cha cũng được), không trùng thì để trống; không bao giờ tự tạo bản ghi. |

### Danh sách, tìm kiếm, lọc, sắp xếp (8 test, Thành viên 2)

| Test | Quy tắc được kiểm |
|---|---|
| `danh_sach_phan_trang_va_chung_chi_gan_het_han_len_dau` | Danh sách chia trang 20 dòng, chứng chỉ gấp nhất lên đầu, và hai trang không trùng dòng. |
| `tu_khoa_co_ky_tu_dac_biet_khong_khop_tat_ca` | Từ khoá chứa %, _, dấu gạch ngược, tiếng Việt được hiểu đúng theo chữ, không thành ký tự đại diện. |
| `tim_fingerprint_duoc_du_viet_hoa_hay_co_dau_hai_cham` | Tìm fingerprint và serial được dù viết hoa, viết thường hay có dấu hai chấm. |
| `cac_bo_loc_ket_hop_va_gia_tri_loc_sai_bi_bo_qua` | Các bộ lọc kết hợp với nhau bằng VÀ; giá trị lọc sai bị coi như chưa chọn; ngày cuối được tính trọn. |
| `danh_sach_mac_dinh_xep_theo_muc_gap_thu_hoi_cuoi_cung` | Danh sách mặc định xếp theo mức gấp, đã thu hồi xuống cuối; trong mỗi nhóm, hạn gần hơn đứng trước. |
| `sap_xep_theo_cot_va_gia_tri_la_ve_mac_dinh` | Bấm tiêu đề cột đổi được cách xếp cả hai chiều; cột Còn lại luôn để đã thu hồi ở cuối; giá trị lạ về mặc định. |
| `so_tren_nut_trang_thai_khop_danh_sach_va_giu_bo_loc_khac` | Số trên hàng nút trạng thái khớp số dòng thật, theo đúng thứ tự mức gấp, và tôn trọng các bộ lọc khác. |
| `he_thong_chua_co_chung_chi_van_chay_dung` | Khi chưa có chứng chỉ nào, danh sách, số đếm, cảnh báo và mã kế tiếp vẫn đúng, không lỗi. |

### Danh mục: tên miền, máy chủ, tổ chức cấp (7 test, Thành viên 3)

| Test | Quy tắc được kiểm |
|---|---|
| `may_chu_sai_ip_cong_moi_truong_bi_tu_choi` | Máy chủ sai IP, cổng, môi trường, hay thiếu thông tin bắt buộc đều bị từ chối đúng ô. |
| `ten_mien_hop_le_duoc_nhan_va_sai_dinh_dang_bi_tu_choi` | Tên miền hợp lệ gồm cả wildcard thì được nhận, và lưu chữ thường; sai định dạng thì bị từ chối. |
| `ten_mien_trung_va_ten_to_chuc_trung_bao_loi_ro_rang` | Tên miền trùng và tên tổ chức trùng được báo lỗi đúng ô, không làm hỏng trang. |
| `ten_mien_phai_gan_voi_may_chu_co_that` | Tên miền phải gắn với một máy chủ có thật. |
| `xoa_danh_muc_con_du_lieu_con_bao_ly_do_ro_rang` | Xoá danh mục còn dữ liệu con thì báo lý do rõ ràng, và dữ liệu vẫn còn. |
| `xoa_ban_ghi_khong_ton_tai_bao_loi` | Xoá bản ghi không tồn tại thì báo lỗi, không báo đã xoá. |
| `danh_muc_khong_con_du_lieu_con_thi_xoa_duoc` | Danh mục không còn dữ liệu con thì xoá được. |

### Cảnh báo (7 test, Thành viên 3)

| Test | Quy tắc được kiểm |
|---|---|
| `khong_tao_canh_bao_trung_trong_cung_chu_ky` | Trong một chu kỳ, mỗi mốc của một chứng chỉ chỉ có một cảnh báo. |
| `sau_gia_han_van_canh_bao_lai_duoc_cung_moc` | Sau khi gia hạn, chứng chỉ vẫn tạo lại được cảnh báo ở cùng mốc. |
| `chung_chi_con_5_ngay_chi_tao_canh_bao_moc_7` | Chứng chỉ chỉ nhận cảnh báo ở mốc gần nhất đã chạm: tối đa 7, 15, 30 ngày. |
| `tao_canh_bao_chay_lai_khong_sinh_them_dong` | Chạy lại việc tạo cảnh báo không sinh thêm dòng và không đổi trạng thái đã xem. |
| `khong_tao_canh_bao_cho_chung_chi_khong_o_trang_thai_sap_het_han` | Chỉ chứng chỉ Sắp hết hạn mới được tạo cảnh báo. |
| `trang_canh_bao_an_chu_ky_cu_va_chung_chi_thu_hoi_nhung_giu_het_han` | Trang Cảnh báo bỏ chu kỳ cũ và chứng chỉ đã thu hồi, nhưng giữ chứng chỉ đã hết hạn. |
| `danh_dau_da_xem_lam_giam_so_canh_bao_moi` | Đánh dấu đã xem làm giảm số cảnh báo mới, cảnh báo vẫn còn trong danh sách. |

### Dashboard, thống kê, báo cáo (8 test, Thành viên 3)

| Test | Quy tắc được kiểm |
|---|---|
| `dashboard_chi_liet_ke_10_chung_chi_sap_het_han_gan_nhat` | Bảng Cần xử lý ngay chỉ gồm chứng chỉ sắp hết hạn, tối đa 10 dòng, gần hạn nhất trước. |
| `thong_ke_to_chuc_va_thuat_toan_khop_tong_va_theo_bo_loc` | Thống kê theo tổ chức cấp và thuật toán có tổng khớp số chứng chỉ, nhiều nhất trước, và theo bộ lọc. |
| `het_han_theo_thang_du_12_thang_bo_thu_hoi_va_da_qua_han` | Biểu đồ hết hạn theo tháng đủ 12 tháng liên tiếp, không tính đã thu hồi và đã quá hạn. |
| `het_han_theo_thang_khong_nhay_coc_khi_hom_nay_la_ngay_31` | Hôm nay là ngày 31 thì 12 tháng vẫn liên tiếp, không nhảy cóc qua tháng ngắn. |
| `bieu_do_thong_ke_giu_tran_truc_khi_loc` | Bốn biểu đồ Thống kê giữ trần trục theo toàn bộ chứng chỉ; lọc thì cột ngắn lại trên cùng một thước. |
| `csv_vo_hieu_hoa_cong_thuc_va_giu_nguyen_du_lieu_thuong` | Ô CSV bắt đầu bằng = + - @ bị vô hiệu hoá, kể cả khi có khoảng trắng đầu ô; dữ liệu thường giữ nguyên. |
| `csv_ngay_dang_nam_thang_ngay_khong_nham_ngay_thang` | Ngày trong CSV ở dạng năm-tháng-ngày, không kèm giờ. |
| `xuat_bao_cao_lay_du_ket_qua_khong_chia_trang_va_theo_bo_loc` | Báo cáo lấy đủ kết quả, không bị chia trang, theo đúng bộ lọc và cách xếp đang chọn. |

### Giao diện, triển khai (9 test, Thành viên 3)

| Test | Quy tắc được kiểm |
|---|---|
| *(test cấu hình triển khai)* | Trang giới thiệu công khai và CSS, JS của nó không nhắc tới phần mềm hạ tầng riêng chạy trên máy chủ. |
| `landing_khong_dung_mau_chu_dao_mac_dinh_cua_tabler` | Landing không dùng màu xanh chủ đạo mặc định của Tabler (btn-primary, --tblr-primary). |
| `dau_trang_dung_yen_khi_chuyen_trang_nho_nhung_font_va_giu_cho_thanh_cuon` | Font nhúng sẵn trong inter.css và trang luôn giữ chỗ thanh cuộn, để đầu trang đứng yên khi chuyển trang. |
| `chuyen_canh_chi_bat_o_landing_va_dang_nhap` | Chuyển cảnh giữa hai trang chỉ bật ở landing và trang đăng nhập; trang quản trị chuyển trang tức thì. |
| `css_js_cua_he_thong_co_so_phien_ban_theo_file` | app.css và app.js được nạp qua urlTep(), địa chỉ kèm ?v= là thời điểm sửa file. |
| `tab_trinh_duyet_ghi_ten_trang_roi_ten_thuong_hieu` | Tab trình duyệt ghi "Tên trang | Cipher Cert"; tên thương hiệu khai báo một chỗ (TEN_THUONG_HIEU). |
| `chi_cham_con_hieu_luc_tren_dashboard_co_vong_sang` | Chỉ chấm "Còn hiệu lực" trên thẻ số Dashboard có vòng sáng lan ra (cham-song); máy giảm chuyển động thì đứng yên. |
| `ten_mien_chi_xuong_dong_o_dau_cham` | Tên miền trong bảng chỉ được xuống dòng ngay sau dấu chấm, và vẫn được thoát ký tự HTML. |
| `web_co_dia_chi_khong_duoi_cong` | Web có địa chỉ không đuôi cổng: cổng 80, Alt-Svc và cả địa chỉ gõ kèm :8443 đều dẫn về địa chỉ không đuôi, dữ liệu mẫu ghi cổng 443. |

## 4. Kiểm tay trên bản chạy thật

Ngày 08/11/2026, kiểm từ máy ở ngoài, gửi yêu cầu tới <https://cert.cipher.id.vn> khi chưa đăng nhập.

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
| Đăng xuất bằng đường link (GET) | Từ chối, chỉ nhận nút trên trang | 405 | ✅ |
| Gửi form đăng nhập không có mã CSRF | Từ chối | 403 | ✅ |
| Mở địa chỉ `http://` | Chuyển sang `https://` | 301 → `https://cert.cipher.id.vn/` | ✅ |
| Mở địa chỉ không tồn tại | Không tìm thấy, không chạy trang khác thay | 404 | ✅ |

Ngoài ra, các lệnh cài đặt ở README mục 2 đã được chạy thử trên một CSDL trống: nạp xong có 2 tài khoản, 50 chứng
chỉ đủ 5 trạng thái (6 đã hết hạn, 13 sắp hết hạn, 22 còn hiệu lực, 3 chưa hiệu lực, 6 đã thu hồi) và 2 trigger
của nhật ký. Số chứng chỉ theo trạng thái sẽ đổi theo ngày nạp.

## 5. Lỗi phát hiện trong quá trình làm

Mỗi lỗi khi sửa đều kèm đúng một test bắt lỗi đó, để lỗi không quay lại.

| Ngày | Lỗi | Nguyên nhân | Test giữ lỗi không quay lại |
|---|---|---|---|
| 06/11/2026 | Gửi form có ký tự sai mã hoá làm trang lỗi 500 | CSDL chế độ strict từ chối byte không phải UTF-8 | `o_form_sai_ma_hoa_hoac_la_mang_duoc_coi_la_trong` |
| 07/11/2026 | Ngày 31 thì biểu đồ 12 tháng mất tháng 2 | Trong PHP, 31/01 cộng 1 tháng ra 03/03 | `het_han_theo_thang_khong_nhay_coc_khi_hom_nay_la_ngay_31` |
| 08/11/2026 | Tên miền bị bẻ dòng giữa chữ trên điện thoại ("pay.congt / y.example") | Cột hẹp, trình duyệt tự bẻ ở chỗ bất kỳ | `ten_mien_chi_xuong_dong_o_dau_cham` |
| 08/11/2026 | Trang Nhật ký trên điện thoại không xem được IP | Cột IP bị ẩn hẳn ở màn hẹp | `nhat_ky_hien_ip_o_moi_do_rong_man_hinh` |

## 6. Giới hạn, chưa kiểm

- **Phần JavaScript** (kiểm tra form trên trình duyệt, biểu đồ, hiệu ứng) không có test tự động, vì bộ test
  chạy không cần trình duyệt; phần này kiểm bằng tay. Mọi quy tắc dữ liệu đều được PHP kiểm lại và có test.
- **Chạy bằng Apache trong `htdocs`** (cách cài thường gặp với XAMPP) chưa thử; hẹn kiểm ngày 14–15/11/2026.
- **Đọc chứng chỉ thật** kết nối ra internet: test kiểm phần chặn địa chỉ và phần đổi chứng chỉ thành giá trị
  các ô bằng chứng chỉ mẫu, không kết nối mạng thật, để test không phụ thuộc mạng.
- Chưa kiểm hiệu năng với số lượng lớn (hàng chục nghìn chứng chỉ) và nhiều người dùng cùng lúc.
