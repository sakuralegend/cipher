# Use Case: Hệ thống quản lý chứng chỉ số và thời hạn sử dụng

Tài liệu gồm: tác nhân và phân quyền (mục 1), sơ đồ use case (mục 2), đặc tả từng use case kèm ảnh màn hình
(mục 3), sơ đồ trình tự của 4 luồng chính (mục 4). Quy tắc chi tiết của từng chức năng nằm ở [SPEC.md](SPEC.md);
file và hàm thực hiện từng luồng nằm ở mục Luồng xử lý của các chức năng chính trong [BAO_CAO.md](BAO_CAO.md).

## 1. Tác nhân và phân quyền

| Tác nhân | Là ai | Làm được |
|---|---|---|
| **Nhân viên quản trị** | Người theo dõi chứng chỉ hằng ngày | Quản lý chứng chỉ và danh mục (không xoá), xử lý cảnh báo, xem thống kê, xuất báo cáo |
| **Admin** | Người quản lý hệ thống | Mọi việc của Nhân viên, thêm: xoá chứng chỉ, xoá danh mục, quản lý người dùng, xem nhật ký |
| **Máy chủ bên ngoài** (tác nhân phụ) | Website, máy chủ thư... đang chạy chứng chỉ cần theo dõi | Trả chứng chỉ khi hệ thống kết nối tới để đọc (UC05a) |

Không có trang đăng ký công khai: Admin tạo mọi tài khoản. Khách chưa đăng nhập chỉ xem được trang giới thiệu
và trang đăng nhập.

| Thao tác | Admin | Nhân viên quản trị | Khách |
|---|---|---|---|
| Xem trang giới thiệu, đăng nhập | ✅ | ✅ | ✅ |
| Dashboard, xem và lọc chứng chỉ, cảnh báo, thống kê, báo cáo | ✅ | ✅ | ❌ |
| Thêm, sửa, thu hồi, gia hạn chứng chỉ; đọc chứng chỉ từ máy chủ | ✅ | ✅ | ❌ |
| Thêm, sửa tên miền, máy chủ, tổ chức cấp | ✅ | ✅ | ❌ |
| Xoá chứng chỉ; xoá tên miền, máy chủ, tổ chức cấp | ✅ | ❌ | ❌ |
| Quản lý người dùng, xem nhật ký | ✅ | ❌ | ❌ |

Quyền được kiểm tra trong PHP ở mọi yêu cầu, không chỉ bằng cách ẩn nút: Nhân viên tự gõ địa chỉ trang của
Admin thì nhận lỗi 403, tự gửi lệnh xoá thì bị từ chối.

## 2. Sơ đồ use case tổng quát

![Sơ đồ use case UML của hệ thống Cipher Cert](usecase.svg)

Cách đọc theo ký hiệu UML:

- **Đường liền** nối tác nhân với use case: tác nhân đó dùng use case đó.
- **Mũi tên tam giác rỗng** từ Admin lên Nhân viên quản trị (kế thừa): Admin làm được mọi use case của Nhân
  viên, nên chỉ cần nối Admin với 3 use case riêng của mình.
- **«include»** (bao gồm): use case gốc luôn chạy use case kia. Mở Dashboard (UC03) hay trang Cảnh báo (UC09)
  đều luôn tạo các cảnh báo còn thiếu trước khi hiện.
- **«extend»** (mở rộng): use case kia là một nhánh tuỳ chọn của use case gốc. Khi thêm chứng chỉ (UC05),
  người dùng **có thể** đọc chứng chỉ từ máy chủ để tự điền form, hoặc bỏ qua và nhập tay.

| Mã | Use case | Tác nhân | Chức năng của đề |
|---|---|---|---|
| UC01 | Đăng nhập, đăng xuất | Nhân viên, Admin | Na |
| UC02 | Quản lý tài khoản cá nhân | Nhân viên, Admin | Na |
| UC03 | Xem Dashboard | Nhân viên, Admin | Nh |
| UC04 | Xem, tìm kiếm, lọc chứng chỉ | Nhân viên, Admin | D1, D4, D5, Nd, Mở rộng 3 |
| UC05 | Thêm, sửa chứng chỉ | Nhân viên, Admin | D1, D4, Nc, Nf |
| UC05a | Đọc chứng chỉ từ máy chủ | Nhân viên, Admin, Máy chủ bên ngoài | Mở rộng 2 |
| UC06 | Thu hồi chứng chỉ | Nhân viên, Admin | D5 |
| UC07 | Gia hạn chứng chỉ, xem lịch sử | Nhân viên, Admin | D7 |
| UC08 | Quản lý tên miền, máy chủ, tổ chức cấp | Nhân viên, Admin (xoá: chỉ Admin) | D2, D3, Nc |
| UC09 | Xem, xử lý cảnh báo | Nhân viên, Admin | D6 |
| UC10 | Xem thống kê, xuất báo cáo | Nhân viên, Admin | D8, D9, Ne |
| UC11 | Xoá chứng chỉ | Admin | D1 |
| UC12 | Quản lý người dùng | Admin | Nb |
| UC13 | Xem nhật ký hoạt động | Admin | Mở rộng 1 |

## 3. Đặc tả use case

Mọi use case trừ UC01 đều có chung **điều kiện trước**: người dùng đã đăng nhập và tài khoản không bị khoá;
chưa đăng nhập thì bị chuyển về trang đăng nhập. Mọi thao tác đổi dữ liệu đều kiểm tra mã CSRF; mã sai (ví dụ
để trang mở quá lâu) thì hệ thống báo "Phiên làm việc đã hết hạn" và không đổi gì. Hai ngoại lệ chung này
không nhắc lại ở từng use case.

Đánh số luồng: `3a` là nhánh rẽ ở bước 3 của luồng chính.

### UC01. Đăng nhập, đăng xuất

- **Tác nhân:** Nhân viên quản trị, Admin.
- **Mục đích:** vào hệ thống bằng tài khoản được cấp; rời hệ thống an toàn.
- **Điều kiện trước:** có tài khoản do Admin tạo.
- **Kết quả:** đăng nhập thì có phiên làm việc mới và vào Dashboard; đăng xuất thì phiên bị huỷ. Cả hai đều ghi
  một dòng nhật ký.

![Trang đăng nhập](anh/dang_nhap.jpg)

**Luồng chính**

1. Người dùng mở trang giới thiệu, bấm **Đăng nhập**.
2. Hệ thống hiện form tên đăng nhập, mật khẩu.
3. Người dùng nhập rồi bấm **Đăng nhập**.
4. Hệ thống tìm tài khoản, so mật khẩu với mã băm đã lưu.
5. Hệ thống đặt lại số lần sai về 0, đổi mã phiên, ghi nhật ký "Đăng nhập", chuyển tới Dashboard.
6. Khi muốn rời đi, người dùng bấm biểu tượng tài khoản ở đáy menu, chọn **Đăng xuất**.
7. Hệ thống ghi nhật ký "Đăng xuất", huỷ phiên và cookie, về trang giới thiệu.

**Luồng thay thế**

- 1a. Người dùng đã đăng nhập mà mở trang đăng nhập: hệ thống chuyển thẳng tới Dashboard.

**Ngoại lệ**

- 4a. Sai tên đăng nhập hoặc sai mật khẩu: hệ thống báo "Sai tên đăng nhập hoặc mật khẩu. Sai quá 5 lần, tài
  khoản bị tạm chặn 15 phút.", tăng số lần sai, ghi nhật ký "Đăng nhập sai" (mức Cảnh báo). Câu báo giống hệt
  nhau, để người dò không biết tên nào có thật.
- 4b. Sai đến lần thứ 5: tài khoản bị tạm chặn 15 phút. Trong thời gian đó nhập đúng mật khẩu vẫn nhận đúng câu
  báo ở 4a, và nhật ký ghi "Đăng nhập khi đang bị chặn" (mức Nguy hiểm). Hết 15 phút thì đăng nhập lại được.
- 4c. Tài khoản bị Admin khoá: người nhập đúng mật khẩu nhận câu "Tài khoản đã bị khoá. Hãy liên hệ Admin."
- 5a. Người dùng bị khoá, bị xoá, hoặc mật khẩu bị đổi ở nơi khác trong lúc đang dùng: ở yêu cầu tiếp theo, hệ
  thống đẩy họ về trang đăng nhập.
- 6a. Có trang lạ cố đăng xuất người dùng bằng một đường link (yêu cầu GET): hệ thống từ chối, chỉ nhận nút
  Đăng xuất trên trang.

![Đăng nhập sai: sai tên hay sai mật khẩu đều nhận cùng một câu báo](anh/dang_nhap_sai.jpg)

### UC02. Quản lý tài khoản cá nhân

- **Tác nhân:** Nhân viên quản trị, Admin.
- **Mục đích:** tự sửa họ tên, tự đổi mật khẩu.
- **Kết quả:** họ tên hoặc mật khẩu mới được lưu (mật khẩu ở dạng băm); một dòng nhật ký.

**Luồng chính**

1. Người dùng bấm biểu tượng tài khoản ở đáy menu, chọn **Tài khoản và mật khẩu**.
2. Hệ thống hiện tên đăng nhập, vai trò, họ tên, và form đổi mật khẩu.
3. Người dùng nhập mật khẩu cũ, mật khẩu mới, nhập lại mật khẩu mới, bấm lưu.
4. Hệ thống kiểm tra mật khẩu cũ đúng, mật khẩu mới có ít nhất 8 ký tự gồm cả chữ và số, và hai lần nhập khớp nhau.
5. Hệ thống lưu mật khẩu mới dạng băm, ghi nhật ký "Đổi mật khẩu", báo thành công. Các phiên đăng nhập khác của
   người đó (máy khác, trình duyệt khác) bị đăng xuất; phiên đang đổi giữ nguyên.

**Luồng thay thế**

- 3a. Người dùng sửa họ tên thay vì đổi mật khẩu: hệ thống kiểm tra họ tên không trống, tối đa 100 ký tự, lưu
  và ghi nhật ký.

**Ngoại lệ**

- 4a. Mật khẩu cũ sai, mật khẩu mới không đủ 8 ký tự, thiếu chữ hoặc thiếu số, quá dài, hoặc hai lần nhập lệch
  nhau: hệ thống báo lỗi ngay dưới ô sai, không đổi gì.

![Trang Tài khoản và mật khẩu: sửa họ tên và đổi mật khẩu của chính mình](anh/tai_khoan.jpg)

### UC03. Xem Dashboard

- **Tác nhân:** Nhân viên quản trị, Admin.
- **Mục đích:** nắm nhanh tình hình chứng chỉ và việc cần làm ngay.
- **Kết quả:** người dùng thấy số liệu tổng quan; các cảnh báo còn thiếu được tạo.

**Luồng chính**

1. Người dùng đăng nhập xong, hoặc bấm khu **Tổng quan** trên menu.
2. Hệ thống tạo các cảnh báo còn thiếu (use case «include» *Tạo cảnh báo theo mốc*, xem UC09).
3. Hệ thống hiện 6 thẻ số: tổng số chứng chỉ và số chứng chỉ ở từng trạng thái (Đã hết hạn, Sắp hết hạn, Còn
   hiệu lực, Chưa hiệu lực, Đã thu hồi).
4. Hệ thống hiện bảng **Cần xử lý ngay**: tối đa 10 chứng chỉ sắp hết hạn gần nhất, mỗi dòng có số ngày còn
   lại, ngày hết hạn và nút Gia hạn.
5. Hệ thống hiện 2 biểu đồ: theo trạng thái, và số chứng chỉ hết hạn trong 12 tháng tới.

**Luồng thay thế**

- 3a. Người dùng bấm một thẻ số: hệ thống mở danh sách chứng chỉ đã lọc theo trạng thái đó (UC04).
- 4a. Không có chứng chỉ nào sắp hết hạn: bảng hiện "Không có chứng chỉ nào sắp hết hạn."
- 4b. Bấm Gia hạn ở một dòng: chuyển sang UC07.
- 4c. Hệ thống chưa có chứng chỉ nào: các thẻ hiện 0, bảng và biểu đồ trống, không báo lỗi.

![Dashboard: 6 thẻ số, bảng Cần xử lý ngay và 2 biểu đồ; số đỏ trên chuông là số cảnh báo mới](anh/dashboard.jpg)

### UC04. Xem, tìm kiếm, lọc chứng chỉ

- **Tác nhân:** Nhân viên quản trị, Admin.
- **Mục đích:** tìm một chứng chỉ, hoặc xem nhóm chứng chỉ theo điều kiện.
- **Kết quả:** danh sách khớp điều kiện; trang chi tiết của chứng chỉ được chọn, kèm đánh giá an toàn.

**Luồng chính**

1. Người dùng bấm khu **Chứng chỉ** trên menu (mục Danh sách chứng chỉ).
2. Hệ thống hiện 20 chứng chỉ một trang, xếp theo mức gấp: đã hết hạn, sắp hết hạn, còn hiệu lực, chưa hiệu
   lực, đã thu hồi; trong mỗi nhóm, hạn gần hơn đứng trước. Mỗi dòng có mã, tên chính, tổ chức cấp, ngày
   hết hạn, số ngày còn lại, trạng thái, hạng an toàn. Trên bảng là hàng nút trạng thái kèm số đếm.
3. Người dùng gõ từ khoá (tìm trong mã quản lý, tên chính, tên phụ, serial, fingerprint) và/hoặc mở **Bộ lọc** để
   chọn tổ chức cấp, tên miền, máy chủ, khoảng ngày hết hạn, cách sắp xếp, rồi bấm **Tìm**.
4. Hệ thống áp mọi điều kiện cùng lúc (phải khớp tất cả), cập nhật danh sách và số đếm trên hàng nút.
5. Người dùng bấm mã của một chứng chỉ.
6. Hệ thống hiện trang chi tiết: đầy đủ thông tin, các nút thao tác (Gia hạn, Sửa, Thu hồi; Xoá nếu là Admin),
   thẻ **Đánh giá an toàn** (hạng A, B, C hoặc F, từng phát hiện kèm nguồn quy định), và bảng lịch sử gia hạn.

**Luồng thay thế**

- 3a. Bấm một nút trạng thái: lọc theo trạng thái đó, giữ các điều kiện khác.
- 3b. Chọn ô **Sắp xếp theo**: mới thêm trước, thêm lâu nhất trước, hết hạn gần nhất trước, hết hạn xa nhất
  trước, kém an toàn trước, an toàn nhất trước. Bấm tiêu đề cột cũng đổi cách xếp, bấm lần nữa để đảo chiều.
- 3c. Fingerprint, serial gõ viết hoa, viết thường hay có dấu `:` đều tìm được.
- 4a. Không có chứng chỉ nào khớp: bảng báo "Không có chứng chỉ nào khớp bộ lọc."
- 2a. Trên điện thoại: bảng hiện đủ cột và kéo ngang được.

**Ngoại lệ**

- 4b. Địa chỉ trang bị sửa tay thành giá trị lọc sai (ví dụ trạng thái không tồn tại): hệ thống coi như chưa
  chọn điều kiện đó, vẫn hiện trang bình thường.
- 6a. Chứng chỉ không còn tồn tại (người khác vừa xoá): hệ thống báo "Chứng chỉ không còn tồn tại." và về danh
  sách.

![Danh sách chứng chỉ, mặc định xếp theo mức gấp; nút Xuất CSV và Bản in ở góc trên phải](anh/danh_sach.jpg)

![Trang chi tiết một chứng chỉ: thông tin, các nút thao tác, thẻ Đánh giá an toàn và Lịch sử gia hạn](anh/chi_tiet.jpg)

### UC05. Thêm, sửa chứng chỉ

- **Tác nhân:** Nhân viên quản trị, Admin.
- **Mục đích:** đưa một chứng chỉ vào hệ thống để theo dõi, hoặc sửa thông tin nhập sai.
- **Điều kiện trước:** nhập thủ công thì tên miền và tổ chức cấp đã có trong danh mục (UC08); đọc từ máy chủ thì
  hệ thống tạo phần còn thiếu (UC05a). Sửa thì chứng chỉ chưa bị thu hồi.
- **Kết quả:** chứng chỉ được lưu, trạng thái và số ngày còn lại tự tính; một dòng nhật ký "Thêm" hoặc "Sửa".

**Luồng chính** (thêm bằng tay)

1. Người dùng bấm **Thêm chứng chỉ** trong khu Chứng chỉ của menu.
2. Hệ thống mở trang có 2 tab, tab **Nhập thủ công** mở trước, điền sẵn mã quản lý kế tiếp (ví dụ `CERT-051`).
3. Người dùng nhập: tên chính, tên phụ, tên miền, tổ chức cấp (gõ có gợi ý), ngày cấp, ngày hết hạn,
   thuật toán khoá, độ dài khoá, thuật toán ký; fingerprint SHA-256 và serial nếu có. Ô ngày chọn bằng lịch hoặc
   dán chữ dạng ngày/tháng/năm (ví dụ `24/11/2026 00:00`). Bấm **Thêm chứng chỉ**.
4. JavaScript kiểm tra ngay trên trình duyệt: ô bắt buộc, định dạng, ngày hết hạn sau ngày cấp.
5. Hệ thống chuẩn hoá fingerprint, serial (bỏ dấu `:` và khoảng trắng), rồi kiểm tra lại toàn bộ bằng PHP theo
   quy tắc ở SPEC mục 3.1.
6. Hệ thống lưu, ghi nhật ký "Thêm", về danh sách với thông báo "Đã thêm chứng chỉ."

**Luồng thay thế**

- 2a. Người dùng chuyển sang tab **Nhập tự động**: chuyển sang UC05a.
- 1a. Sửa: từ trang chi tiết bấm **Sửa**. Form điền sẵn giá trị hiện tại, chỉ có nhập thủ công. Lưu xong về
  trang chi tiết với thông báo "Đã lưu thay đổi." Sửa không ghi lịch sử gia hạn; chứng chỉ được cấp lại thì
  phải dùng UC07.

**Ngoại lệ**

- 4a. Ô sai: JavaScript tô đỏ ô và hiện câu lỗi ngay dưới, chưa gửi đi.
- 5a. Dữ liệu sai lọt qua JavaScript (ví dụ trình duyệt tắt JavaScript): PHP báo lỗi dưới đúng ô, giữ nguyên
  những gì người dùng đã nhập.
- 5b. Mã quản lý hoặc fingerprint đã có ở chứng chỉ khác (kể cả viết khác dạng như `AB:CD` và `abcd`): báo
  "Mã quản lý này đã tồn tại." hoặc "Fingerprint này đã có ở chứng chỉ khác."
- 5c. Gõ tên miền hoặc tổ chức cấp chưa có trong danh mục: báo "Chưa có tên miền "…" trong danh mục.", kèm link
  thêm mới. Nhập thủ công thì hệ thống không tự tạo bản ghi danh mục.
- 5d. Ngày hết hạn không sau ngày cấp (bằng nhau cũng không được): báo dưới ô ngày hết hạn.
- 1b. Chứng chỉ đã thu hồi: trang chi tiết không có nút Sửa; gõ thẳng địa chỉ form sửa thì bị đẩy về trang chi
  tiết với câu "Chứng chỉ đã thu hồi chỉ còn để tra cứu, không sửa được. Theo dõi chứng chỉ mới thì thêm mới."

![Trang Thêm chứng chỉ, tab Nhập thủ công mở trước, mã quản lý kế tiếp được điền sẵn](anh/them_thu_cong.jpg)

![Dữ liệu sai bị PHP từ chối đúng ô: tên miền chưa có trong danh mục, ngày hết hạn trước ngày cấp, fingerprint không đủ 64 ký tự](anh/them_loi.jpg)

### UC05a. Đọc chứng chỉ từ máy chủ (mở rộng của UC05)

- **Tác nhân:** Nhân viên quản trị, Admin. **Tác nhân phụ:** Máy chủ bên ngoài.
- **Mục đích:** lấy thông tin chứng chỉ đang chạy thật trên máy chủ, khỏi gõ tay và gõ sai.
- **Điều kiện trước:** đang ở trang Thêm chứng chỉ; máy chủ web có mạng ra ngoài.
- **Kết quả:** các ô của form được điền sẵn; một dòng nhật ký "Đọc chứng chỉ" ghi địa chỉ đã đọc và kết quả.
  Bấm Thêm thì tên miền, máy chủ, tổ chức cấp còn thiếu được tạo cùng chứng chỉ, mỗi thứ một dòng nhật ký "Thêm".

**Luồng chính**

1. Ở tab **Nhập tự động**, khung **Đọc một địa chỉ**, người dùng gõ địa chỉ (ví dụ `github.com`, có thể kèm
   `https://` hoặc `:cổng`), chọn cổng trong danh sách (mặc định 443), bấm **Đọc chứng chỉ**.
2. Hệ thống tách tên máy và cổng, hỏi DNS lấy mọi IP của tên máy.
3. Hệ thống kiểm tra cổng nằm trong danh sách cổng TLS được phép, và mọi IP đều là địa chỉ công khai.
4. Hệ thống kết nối TLS tới đúng IP vừa kiểm, gửi tên miền kèm theo, và nhận chứng chỉ máy chủ trả về. Chứng
   chỉ hết hạn, tự ký hay sai tên vẫn đọc được, vì đó chính là thứ cần theo dõi.
5. Hệ thống điền sẵn: tên chính, tên phụ, ngày cấp, ngày hết hạn (giờ Việt Nam), thuật toán khoá, độ dài
   khoá, thuật toán ký, fingerprint, serial. Tên miền và tổ chức cấp (tên hãng gọn) được tự chọn nếu đã có
   (tên miền cha cũng được: `cert.cipher.id.vn` khớp `cipher.id.vn`). Hệ thống hiện thẻ tóm tắt chứng chỉ vừa
   đọc, kèm số ngày còn lại, IP và danh sách "Sẽ tạo mới khi bấm Thêm chứng chỉ".
6. Hệ thống ghi nhật ký "Đọc chứng chỉ".
7. Người dùng kiểm tra lại, bấm **Thêm chứng chỉ**. Tiếp tục từ bước 4 của UC05; ở bước lưu, hệ thống tạo phần
   danh mục còn thiếu cùng chứng chỉ trong một giao dịch.

**Luồng thay thế**

- 1a. Nhập hàng loạt: ở khung **Nhập hàng loạt** ngay dưới, người dùng dán tối đa 50 địa chỉ, bấm **Đọc tất cả**.
  Hệ thống làm bước 2–6 cho từng địa chỉ, thanh tiến trình ghi đã đọc bao nhiêu trên tổng số, rồi hiện bảng xem
  trước có ô tick; người dùng bấm **Lưu các dòng đã chọn**, hệ thống lưu từng dòng như bước 7 và hiện kết quả từng
  dòng. Dòng trùng chứng chỉ đã có, cùng chứng chỉ với dòng phía trên, hoặc lỗi thì không tick được.
- 5a. Chưa có tên miền nào khớp: ô tên miền điền sẵn tên vừa đọc, gợi ý "sẽ được tạo khi bấm Thêm chứng chỉ";
  máy chủ cùng IP có sẵn thì dùng chung, không thì tạo máy chủ mới. Đọc bằng IP mà tên chính không phải tên miền:
  ô để trống cho người dùng chọn.
- 5b. Tổ chức cấp chưa có: ô điền sẵn tên hãng gọn, sẽ được tạo kèm quốc gia và mức tin cậy tự kiểm.
- 5c. Chứng chỉ đã có trong hệ thống (trùng fingerprint): hệ thống báo trước "Chứng chỉ này đã có trong hệ
  thống", kèm link tới chứng chỉ đó.
- 5d. Thuật toán khoá không phải RSA hay ECDSA (ví dụ Ed25519, GOST): ô thuật toán để trống cho người dùng chọn.
- 5e. Tên miền đã có nhưng IP lúc đọc khác IP đã lưu (site dùng CDN): hệ thống chỉ ghi chú, kèm nút **Cập nhật IP
  máy chủ**. Bấm thì sửa IP máy chủ đó và ghi nhật ký "Sửa"; không bấm thì không gì thay đổi.

**Ngoại lệ** (mọi trường hợp đều ghi nhật ký kèm lý do)

- 2a. Địa chỉ sai định dạng: "Địa chỉ không hợp lệ. Ví dụ đúng: cert.cipher.id.vn".
- 2b. Không tìm thấy IP của tên miền: báo kiểm tra lại tên miền; nhật ký ghi "Đọc chứng chỉ không được" (mức
  Cảnh báo).
- 3a. Cổng không nằm trong danh sách cổng TLS (443, 8443, 9443, 465, 993, 995, 636, 853, 5061): báo cổng không
  được phép. Nhờ vậy người dùng không mượn được hệ thống để quét cổng tuỳ ý.
- 3b. Bất kỳ IP nào là địa chỉ nội bộ (127.0.0.1, 10.x, 192.168.x...): "… là địa chỉ nội bộ. Chỉ đọc được
  máy chủ có địa chỉ công khai."; nhật ký ghi "Đọc địa chỉ nội bộ bị chặn" (mức Nguy hiểm).
- 4a. Máy chủ không trả lời trong 10 giây, từ chối kết nối, hoặc không dùng TLS ở cổng đó: báo không đọc được.
- 4b. Máy chủ không gửi chứng chỉ: báo lỗi.

![Tab Nhập tự động sau khi đọc github.com: thẻ tóm tắt, các ô đã điền, khung Nhập hàng loạt ở dưới](anh/doc_ket_qua.jpg)

![Bảng xem trước của Nhập hàng loạt: dòng đọc được tick sẵn, dòng cùng chứng chỉ và dòng lỗi ghi rõ lý do](anh/hang_loat_xem_truoc.jpg)

### UC06. Thu hồi chứng chỉ

- **Tác nhân:** Nhân viên quản trị, Admin.
- **Mục đích:** đánh dấu chứng chỉ không còn được tin dù chưa hết hạn (ví dụ lộ khoá riêng).
- **Điều kiện trước:** chứng chỉ chưa bị thu hồi.
- **Kết quả:** trạng thái thành Đã thu hồi; dữ liệu và lịch sử giữ nguyên; không còn cảnh báo, không gia hạn,
  không sửa được; một dòng nhật ký "Thu hồi" (mức Cảnh báo).

**Luồng chính**

1. Ở trang chi tiết, người dùng bấm **Thu hồi**.
2. Hệ thống hỏi lại: "Thu hồi chứng chỉ … ? Không thể hoàn tác."
3. Người dùng đồng ý.
4. Hệ thống đặt cờ thu hồi, ghi nhật ký, báo "Đã thu hồi chứng chỉ."

**Luồng thay thế**

- 3a. Người dùng huỷ: không đổi gì.

**Ngoại lệ**

- 4a. Chứng chỉ đã được thu hồi trước đó hoặc không còn tồn tại (người khác vừa thao tác): báo lỗi, không đổi
  gì.
- 4b. Không có cách hoàn tác: chứng chỉ đã thu hồi không có nút Sửa, không có nút Gia hạn. Muốn dùng lại thì
  thêm chứng chỉ mới; Admin vẫn xoá được bản ghi.

![Chứng chỉ đã thu hồi chỉ còn để tra cứu: không còn nút Gia hạn, Sửa](anh/chi_tiet_thu_hoi.jpg)

### UC07. Gia hạn chứng chỉ, xem lịch sử gia hạn

- **Tác nhân:** Nhân viên quản trị, Admin.
- **Mục đích:** ghi nhận chứng chỉ đã được cấp lại, giữ dấu vết bản cũ.
- **Điều kiện trước:** chứng chỉ chưa bị thu hồi (đã hết hạn vẫn gia hạn được).
- **Kết quả:** chứng chỉ có hạn mới; bảng lịch sử thêm một dòng giữ hạn, fingerprint, serial cũ; các mốc cảnh
  báo tính lại từ đầu; một dòng nhật ký "Gia hạn".

**Luồng chính**

1. Người dùng bấm **Gia hạn** ở trang chi tiết (hoặc ở Dashboard, trang Cảnh báo).
2. Hệ thống hiện form kèm hạn hiện tại: ngày hết hạn mới, ghi chú, fingerprint mới, serial mới.
3. Người dùng nhập rồi bấm **Gia hạn**.
4. Hệ thống mở một giao dịch, khoá dòng chứng chỉ để người khác không gia hạn chen vào, kiểm tra dữ liệu.
5. Hệ thống chép hạn, fingerprint, serial cũ vào lịch sử; cập nhật chứng chỉ; ghi nhật ký; xác nhận giao dịch.
6. Hệ thống về trang chi tiết. Bảng **Lịch sử gia hạn** có dòng mới: thời điểm, hạn cũ, hạn mới, fingerprint và
   serial cũ, người thực hiện, ghi chú.

**Luồng thay thế**

- 3a. Để trống fingerprint, serial mới: chứng chỉ hiện "Chưa có", vì giá trị cũ không còn đúng với chứng chỉ
  mới; giá trị cũ vẫn nằm trong lịch sử.
- 6a. Chỉ xem lịch sử: mở trang chi tiết, bảng ở cuối trang; chưa gia hạn lần nào thì báo "Chưa gia hạn lần
  nào."

**Ngoại lệ**

- 1a. Chứng chỉ đã thu hồi: không có nút Gia hạn; gửi lệnh thẳng thì bị chặn với câu "Chứng chỉ đã thu hồi không
  gia hạn được. Muốn dùng lại, hãy thêm chứng chỉ mới."
- 4a. Ngày hết hạn mới không sau hạn hiện tại, hoặc không sau thời điểm này: báo dưới ô ngày.
- 4b. Fingerprint mới đã có ở chứng chỉ khác, hoặc sai định dạng: báo dưới ô fingerprint.
- 5a. Lỗi ở bất kỳ bước nào: huỷ cả giao dịch, không bước nào có hiệu lực (không có lịch sử thừa, chứng chỉ
  giữ hạn cũ).

![Form Gia hạn: hạn hiện tại ở trên, bên dưới là ngày hết hạn mới, ghi chú, fingerprint và serial mới](anh/gia_han.jpg)

![Sau khi gia hạn: chứng chỉ có hạn mới, bảng Lịch sử gia hạn thêm một dòng giữ thông tin cũ](anh/gia_han_xong.jpg)

### UC08. Quản lý tên miền, máy chủ, tổ chức cấp

- **Tác nhân:** Nhân viên quản trị, Admin (xoá: chỉ Admin).
- **Mục đích:** giữ danh mục để chọn khi thêm chứng chỉ.
- **Kết quả:** bản ghi danh mục được thêm, sửa hoặc xoá; một dòng nhật ký.

**Luồng chính**

1. Người dùng chọn **Tên miền**, **Máy chủ** hoặc **Tổ chức cấp** trong khu Hạ tầng.
2. Hệ thống hiện danh sách; tên miền và tổ chức cấp có kèm số chứng chỉ đang gắn.
3. Người dùng bấm thêm mới hoặc **Sửa**, nhập:
   - Máy chủ: tên máy, địa chỉ IP, cổng, môi trường (production, staging, development), người phụ trách.
   - Tên miền: tên miền (được dùng dạng `*.congty.vn`), máy chủ đặt tên miền đó.
   - Tổ chức cấp: tên, quốc gia, có được tin cậy không.
4. Hệ thống kiểm tra, lưu (tên miền lưu chữ thường), ghi nhật ký, về danh sách.

**Luồng thay thế**

- 3a. Xoá (chỉ Admin): Admin bấm Xoá, xác nhận; hệ thống xoá nếu bản ghi không còn dữ liệu phụ thuộc, ghi nhật ký
  "Xoá" (mức Nguy hiểm).
- 2a. Xem chứng chỉ của một tên miền: lọc danh sách chứng chỉ theo tên miền đó (UC04).

**Ngoại lệ**

- 4a. IP không phải IPv4 hay IPv6 hợp lệ, cổng ngoài 1–65535, tên miền sai định dạng, ô bắt buộc để trống: báo
  dưới đúng ô.
- 4b. Tên miền hoặc tên tổ chức đã tồn tại: "Tên miền này đã tồn tại." / "Tên tổ chức này đã tồn tại."
- 3b. Xoá máy chủ còn tên miền, xoá tên miền hoặc tổ chức còn chứng chỉ: hệ thống báo rõ lý do (ví dụ "Tổ chức
  này còn chứng chỉ, hãy chuyển hoặc xoá chứng chỉ trước."), dữ liệu vẫn còn.
- 3c. Nhân viên quản trị không thấy nút Xoá; tự gửi lệnh xoá thì nhận câu "Chỉ Admin được xoá danh mục. Bạn vẫn
  có thể sửa."

![Danh sách máy chủ khi đăng nhập bằng Admin: mỗi dòng có nút Sửa và Xoá](anh/may_chu.jpg)

![Xoá tổ chức cấp còn chứng chỉ: hệ thống từ chối và nói rõ lý do](anh/xoa_bi_chan.jpg)

### UC09. Xem, xử lý cảnh báo

- **Tác nhân:** Nhân viên quản trị, Admin.
- **Mục đích:** không để chứng chỉ nào hết hạn mà không ai biết.
- **Kết quả:** người dùng thấy các chứng chỉ cần xử lý; cảnh báo được đánh dấu đã xem hoặc chứng chỉ được gia
  hạn.

**Luồng chính**

1. Người dùng thấy số cảnh báo mới trên biểu tượng chuông của menu, bấm vào.
2. Hệ thống tạo các cảnh báo còn thiếu (use case «include» *Tạo cảnh báo theo mốc*, bên dưới).
3. Hệ thống hiện cảnh báo của chu kỳ hiện tại, mỗi chứng chỉ một dòng ở mốc gấp nhất: chưa xem lên đầu, hạn gần
   trước; nhãn mốc càng gấp càng đỏ (7 ngày đỏ nhất, 30 ngày màu cam).
4. Người dùng xử lý: bấm **Gia hạn** (sang UC07), hoặc bấm **Đã xem** khi đã nắm thông tin.
5. Hệ thống đánh dấu đã xem, giảm số cảnh báo mới trên menu. Cảnh báo vẫn còn trong danh sách.

**Use case được bao gồm: Tạo cảnh báo theo mốc.** Hệ thống lấy mọi chứng chỉ Sắp hết hạn (còn tối đa 30 ngày)
và tạo một cảnh báo ở **mốc gần nhất** đã chạm: còn tối đa 7 ngày là mốc 7, tối đa 15 là mốc 15, còn lại là
mốc 30. Mỗi mốc chỉ tạo một lần trong một chu kỳ hạn; chạy lại bao nhiêu lần cũng không sinh thêm dòng và không
đổi trạng thái đã xem. Sau khi gia hạn, chu kỳ mới bắt đầu nên các mốc được tạo lại từ đầu.

**Luồng thay thế**

- 3a. Chứng chỉ đã hết hạn vẫn hiện cảnh báo, vì đó là trường hợp gấp nhất. Chứng chỉ đã thu hồi thì không hiện.
- 3b. Cảnh báo của chu kỳ cũ (trước lần gia hạn) và các mốc xa hơn mốc gấp nhất không hiện nữa, nhưng vẫn được
  giữ trong CSDL.
- 5a. "Đã xem" tính chung: một người đánh dấu thì mọi người đều thấy là đã xem.

**Ngoại lệ**

- 5b. Cảnh báo đã được người khác đánh dấu trước đó: báo "Cảnh báo đã được đánh dấu trước đó, hoặc không còn
  tồn tại."

![Trang Cảnh báo: mốc, số ngày còn lại, ngày hết hạn, nút Gia hạn và Đã xem](anh/canh_bao.jpg)

### UC10. Xem thống kê, xuất báo cáo

- **Tác nhân:** Nhân viên quản trị, Admin.
- **Mục đích:** nhìn bức tranh chung và lấy báo cáo để gửi hoặc lưu.
- **Kết quả:** biểu đồ và bảng số theo bộ lọc; file CSV hoặc bản in.

**Luồng chính**

1. Người dùng bấm **Thống kê và báo cáo** trong khu Tổng quan.
2. Hệ thống hiện 5 biểu đồ, mỗi biểu đồ kèm bảng số ngay dưới: theo trạng thái; hết hạn trong 12 tháng tới
   (không tính đã thu hồi và đã quá hạn); theo hạng an toàn; theo thuật toán khoá; theo tổ chức cấp.
3. Người dùng chọn bộ lọc (cùng bộ lọc với danh sách chứng chỉ). Hệ thống vẽ lại; trục số giữ nguyên thước đo
   của toàn bộ dữ liệu, nên cột ngắn lại và so được với lúc chưa lọc.
4. Người dùng bấm **Xuất CSV** ở góc trên phải. Hệ thống tải về file `bao-cao-chung-chi-<ngày>.csv` gồm đủ mọi
   chứng chỉ khớp bộ lọc (không chia trang); Excel mở đúng tiếng Việt.
5. Hoặc bấm **Bản in**: hệ thống mở trang in gồm mô tả bộ lọc, bảng số theo trạng thái và danh sách; người dùng
   in hoặc lưu PDF bằng lệnh In của trình duyệt. Bản in luôn nền trắng chữ tối.

**Luồng thay thế**

- 3a. Không chứng chỉ nào khớp bộ lọc: hiện "Không có chứng chỉ nào khớp bộ lọc."
- 4a. Xuất CSV, Bản in cũng có ở góc trên phải trang Danh sách chứng chỉ, theo bộ lọc và cách xếp đang chọn ở đó.

**Ngoại lệ**

- 4b. Ô trong CSV bắt đầu bằng `=`, `+`, `-`, `@`: hệ thống thêm dấu nháy để Excel coi là chữ, không chạy thành
  công thức.

![Trang Thống kê và báo cáo: 5 biểu đồ, mỗi biểu đồ có bảng số ngay dưới](anh/thong_ke.jpg)

![Bản in báo cáo theo bộ lọc đang chọn, nền trắng chữ tối](anh/ban_in.jpg)

### UC11. Xoá chứng chỉ

- **Tác nhân:** Admin.
- **Mục đích:** gỡ hẳn chứng chỉ nhập nhầm khỏi hệ thống.
- **Kết quả:** chứng chỉ bị xoá cùng cảnh báo và lịch sử gia hạn của nó; nhật ký vẫn còn dòng "Xoá" (mức Nguy
  hiểm) kèm mã chứng chỉ lúc đó.

**Luồng chính**

1. Ở trang chi tiết, Admin bấm **Xoá**.
2. Hệ thống hỏi lại: "Xoá chứng chỉ … cùng cảnh báo và lịch sử gia hạn?"
3. Admin đồng ý.
4. Hệ thống ghi nhật ký (kèm tên chứng chỉ trước khi mất), xoá, về danh sách với thông báo "Đã xoá chứng chỉ."

**Luồng thay thế**

- 3a. Admin huỷ: không đổi gì.

**Ngoại lệ**

- 1a. Nhân viên quản trị không thấy nút Xoá. Gửi lệnh xoá thẳng tới hệ thống thì bị từ chối: "Chỉ Admin được xoá
  chứng chỉ. Bạn có thể dùng Thu hồi."
- 4a. Chứng chỉ không còn tồn tại: "Bản ghi này không còn tồn tại."

![Cùng một chứng chỉ khi đăng nhập bằng Nhân viên quản trị: không có nút Xoá](anh/nv_chi_tiet.jpg)

### UC12. Quản lý người dùng

- **Tác nhân:** Admin.
- **Mục đích:** cấp, sửa, khoá, xoá tài khoản.
- **Kết quả:** tài khoản được thêm, sửa, khoá, mở khoá hoặc xoá; một dòng nhật ký.

**Luồng chính**

1. Admin bấm **Người dùng** trong khu Hệ thống.
2. Hệ thống hiện danh sách tài khoản còn dùng: tên đăng nhập, họ tên, vai trò, trạng thái khoá.
3. Admin bấm thêm mới, nhập tên đăng nhập, họ tên, vai trò, mật khẩu, nhập lại mật khẩu.
4. Hệ thống kiểm tra: tên đăng nhập 3–50 ký tự (chữ không dấu, số, `.`, `_`, `-`) và không trùng tài khoản còn
   dùng; họ tên bắt buộc; mật khẩu ít nhất 8 ký tự gồm cả chữ và số, hai lần nhập khớp.
5. Hệ thống lưu mật khẩu dạng băm, ghi nhật ký, về danh sách.

**Luồng thay thế**

- 3a. Sửa: để trống mật khẩu nghĩa là giữ nguyên; nhập mật khẩu mới là đặt lại (đường duy nhất cứu tài khoản quên
  mật khẩu, vì hệ thống không có chức năng quên mật khẩu).
- 3b. Khoá: tài khoản không đăng nhập được nữa và bị đẩy ra ngay nếu đang dùng.
- 3c. Mở khoá: xoá luôn trạng thái tạm chặn do sai mật khẩu, người dùng vào được ngay.
- 3d. Xoá: tài khoản được đánh dấu đã xoá, không đăng nhập được nữa, biến khỏi danh sách, phiên đang mở bị đẩy ra.
  Nhật ký, chứng chỉ, lịch sử gia hạn vẫn giữ tên người đó kèm "(đã xoá)". Tên đăng nhập đó dùng lại được cho
  tài khoản mới.

**Ngoại lệ**

- 4a. Tên đăng nhập đã có ở một tài khoản còn dùng (không phân biệt hoa thường): báo dưới ô tên đăng nhập.
- 3e. Admin tự khoá, tự xoá hoặc tự đổi vai trò của mình: bị từ chối, để hệ thống luôn còn ít nhất một Admin.
- 3f. Xoá người không còn tồn tại (người khác vừa xoá): "Người dùng không còn tồn tại."
- 1a. Nhân viên quản trị không thấy khu Hệ thống; tự gõ địa chỉ trang thì nhận lỗi 403.

![Danh sách người dùng, chỉ Admin vào được](anh/nguoi_dung.jpg)

![Form thêm người dùng: tên đăng nhập, họ tên, vai trò, mật khẩu nhập hai lần](anh/nguoi_dung_form.jpg)

### UC13. Xem nhật ký hoạt động

- **Tác nhân:** Admin.
- **Mục đích:** biết ai đã làm gì, lúc nào, từ đâu, và việc đó đáng lo tới mức nào.
- **Kết quả:** danh sách thao tác theo bộ lọc.

**Luồng chính**

1. Admin bấm **Nhật ký** trong khu Hệ thống.
2. Hệ thống hiện 50 dòng một trang, mới nhất trước. Mỗi dòng: mức (Thông tin, Cảnh báo, Nguy hiểm), thời gian,
   người làm, hành động (nhãn màu), đối tượng (có link nếu đối tượng còn tồn tại) kèm chi tiết, địa chỉ IP.
3. Admin lọc theo người làm, mức độ, hành động, khoảng ngày.
4. Hệ thống hiện kết quả, giữ bộ lọc khi chuyển trang.

**Luồng thay thế**

- 3a. Lọc người làm "Không rõ": các lần đăng nhập sai, chưa biết là ai.
- 3b. Lọc mức **Nguy hiểm**: các lần xoá, đăng nhập khi đang bị tạm chặn, đọc địa chỉ nội bộ bị chặn.
- 3c. Lọc theo người đã bị xoá: tên vẫn có trong ô lọc, dòng nhật ký ghi thêm "đã xoá" dưới tên.
- 2a. Trên điện thoại: bảng hiện đủ cột, kéo ngang để xem IP.

**Ngoại lệ**

- 1a. Nhân viên quản trị: không thấy mục Nhật ký; tự gõ địa chỉ thì nhận lỗi 403.
- 2b. Không có nút sửa hay xoá dòng nhật ký. Lệnh sửa, xoá gõ thẳng vào CSDL cũng bị trigger từ chối.

![Trang Nhật ký hoạt động với cột Mức: đăng nhập sai là Cảnh báo, đăng nhập khi đang bị chặn và đọc địa chỉ nội bộ là Nguy hiểm](anh/nhat_ky.jpg)

## 4. Sơ đồ trình tự

Sơ đồ trình tự (sequence diagram) cho thấy các bên gửi yêu cầu cho nhau theo thứ tự thời gian, từ trên xuống.
Tên trong ngoặc là hàm PHP thực hiện bước đó.

### 4.1. Đăng nhập (UC01)

```mermaid
sequenceDiagram
    autonumber
    actor ND as Người dùng
    participant TD as login.php
    participant AU as src/auth.php
    participant DB as CSDL
    ND->>TD: Gửi tên đăng nhập, mật khẩu, mã CSRF
    TD->>TD: Kiểm tra mã CSRF (yeuCauCsrf)
    TD->>AU: dangNhap(tên, mật khẩu)
    AU->>DB: SELECT users WHERE username = ? (tài khoản còn dùng)
    DB-->>AU: Mã băm, cờ khoá, hạn tạm chặn
    AU->>AU: password_verify (chạy cả khi không có tên này)
    alt Sai tên, sai mật khẩu hoặc đang tạm chặn
        AU->>DB: Tăng số lần sai, đủ 5 thì chặn 15 phút
        AU->>DB: Ghi nhật ký "Đăng nhập sai" hoặc "Đăng nhập khi đang bị chặn"
        AU-->>TD: Câu lỗi chung
        TD-->>ND: Hiện lỗi, không nói rõ sai ở đâu
    else Tài khoản bị khoá
        AU-->>TD: Lỗi "Tài khoản đã bị khoá"
        TD-->>ND: Hiện lỗi
    else Đúng
        AU->>DB: Đặt lại số lần sai, ghi nhật ký "Đăng nhập"
        AU-->>TD: id người dùng, dấu mật khẩu
        TD->>AU: ghiNhanDangNhap (đổi mã phiên)
        TD-->>ND: Chuyển tới Dashboard
    end
```

### 4.2. Thêm chứng chỉ bằng thu thập tự động từ máy chủ (UC05, UC05a)

```mermaid
sequenceDiagram
    autonumber
    actor ND as Người dùng
    participant F as chung_chi_form.php
    participant CC as src/chung_chi.php
    participant DNS as DNS
    participant MC as Máy chủ bên ngoài
    participant DB as CSDL
    ND->>F: Gõ địa chỉ, bấm Đọc chứng chỉ
    F->>CC: docChungChiChoForm
    CC->>CC: Tách tên máy, cổng (phanTichDiaChi)
    CC->>DNS: Hỏi mọi IP của tên máy (phanGiaiDiaChi)
    DNS-->>CC: Danh sách IP
    CC->>CC: Kiểm cổng, kiểm IP (kiemTraDiaChiDoc)
    alt Không được phép
        CC->>DB: Ghi nhật ký kèm lý do
        CC-->>F: Câu lỗi
        F-->>ND: Hiện lỗi dưới ô địa chỉ
    else Được phép
        CC->>MC: Kết nối TLS tới IP đã kiểm (chờ tối đa 10 giây)
        MC-->>CC: Chứng chỉ
        CC->>CC: Điền các ô (giaTriTuChungChi)
        CC->>DB: Tìm chứng chỉ trùng fingerprint, lập kế hoạch danh mục (keHoachDanhMuc)
        CC->>DB: Ghi nhật ký "Đọc chứng chỉ"
        CC-->>F: Giá trị các ô, danh sách "sẽ tạo mới"
        F-->>ND: Form đã điền sẵn, kèm thẻ tóm tắt
        ND->>F: Kiểm tra lại, bấm Thêm chứng chỉ
        F->>CC: luuChungChiKemDanhMuc
        CC->>DB: BEGIN
        CC->>DB: INSERT máy chủ, tên miền, tổ chức cấp còn thiếu
        CC->>DB: INSERT certificates, ghi nhật ký "Thêm"
        CC->>DB: COMMIT
        F-->>ND: Về danh sách, báo "Đã thêm chứng chỉ"
    end
```

### 4.3. Gia hạn chứng chỉ (UC07)

```mermaid
sequenceDiagram
    autonumber
    actor ND as Người dùng
    participant G as chung_chi_gia_han.php
    participant CC as src/chung_chi.php
    participant DB as CSDL
    ND->>G: Nhập hạn mới, fingerprint, serial, ghi chú
    G->>CC: giaHanChungChi
    CC->>DB: BEGIN (mở giao dịch)
    CC->>DB: SELECT ... FOR UPDATE (khoá dòng chứng chỉ)
    DB-->>CC: Hạn, fingerprint, serial hiện tại, cờ thu hồi
    CC->>CC: kiemTraGiaHan
    alt Đã thu hồi hoặc dữ liệu sai
        CC->>DB: ROLLBACK
        CC-->>G: Lỗi theo ô
        G-->>ND: Hiện lỗi
    else Hợp lệ
        CC->>DB: INSERT renewals (giữ thông tin cũ)
        CC->>DB: UPDATE certificates (hạn mới)
        CC->>DB: Ghi nhật ký "Gia hạn"
        CC->>DB: COMMIT
        CC-->>G: Thành công
        G-->>ND: Về trang chi tiết, lịch sử có dòng mới
    end
```

### 4.4. Sinh và xử lý cảnh báo (UC03, UC09)

Dashboard (`index.php`) chạy đúng bước 2 như trang Cảnh báo. Nút Đã xem gửi tới `chung_chi_thao_tac.php`.

```mermaid
sequenceDiagram
    autonumber
    actor ND as Người dùng
    participant T as canh_bao.php
    participant CC as src/chung_chi.php
    participant DB as CSDL
    ND->>T: Mở trang Cảnh báo hoặc Dashboard
    T->>CC: taoCanhBao
    CC->>DB: Thêm cảnh báo cho chứng chỉ Sắp hết hạn
    Note over CC,DB: Mốc gần nhất 7, 15 hoặc 30<br/>Dòng đã có thì bỏ qua (UNIQUE)
    T->>CC: danhSachCanhBao, demCanhBaoMoi
    CC->>DB: Lấy mốc gấp nhất của mỗi chứng chỉ trong chu kỳ hiện tại
    DB-->>CC: Danh sách
    T-->>ND: Bảng cảnh báo, số cảnh báo mới trên menu
    ND->>T: Bấm Đã xem
    T->>CC: danhDauDaXem
    CC->>DB: UPDATE alerts SET status = 'seen'
    T-->>ND: Số cảnh báo mới giảm đi một
```
