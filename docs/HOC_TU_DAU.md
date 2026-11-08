# Hiểu Cipher Cert từ con số 0

Tài liệu này dành cho thành viên nhóm chưa quen làm web. [SPEC](SPEC.md) và [USECASE](USECASE.md) viết cho người
đã biết các khái niệm, nên đọc như đọc hợp đồng: đúng nhưng khó hiểu. Ở đây đi ngược lại: học từng **viên gạch**
(một khái niệm nhỏ) trước, mỗi viên gạch trả lời ba câu:

1. **Vấn đề gì** khiến người ta phải nghĩ ra nó?
2. **Có những cách nào** để giải, và vì sao **chọn cách này** chứ không phải cách khác?
3. Nó nằm **ở đâu** trong Cipher Cert?

Sau đó ghép dần các viên gạch thành từng chức năng, rồi thành bức tranh toàn cảnh của cả hệ thống.

**Cần biết trước:** dùng máy tính, từng học lập trình cơ bản (biến, hàm, `if`, vòng lặp). Không cần biết web, SQL
hay mật mã.

**Không cần nhớ hết.** Mục tiêu là hiểu *vì sao*: hiểu vì sao rồi thì chi tiết tự nhớ, và khi bị hỏi một câu lạ
vẫn suy ra được câu trả lời.

## Lịch đọc gợi ý (một tuần)

| Ngày | Đọc | Sau khi đọc, bạn trả lời được |
|---|---|---|
| 1 | [Phần A](#phần-a-bài-toán-vì-sao-cần-hệ-thống-này) | Chứng chỉ số là gì, vì sao nó hết hạn, vì sao tổ chức cần một hệ thống để theo dõi |
| 2 | [Phần B](#phần-b-viên-gạch-của-một-trang-web), [Phần C](#phần-c-phía-máy-chủ-php) | Một trang web chạy thế nào từ lúc gõ địa chỉ tới lúc hiện ra; PHP làm gì |
| 3 | [Phần D](#phần-d-cơ-sở-dữ-liệu) | Dữ liệu được cất thế nào, vì sao chia thành 9 bảng |
| 4 | [Phần E](#phần-e-nối-php-với-cơ-sở-dữ-liệu-an-toàn), [Phần F](#phần-f-đăng-nhập-phiên-và-bảo-mật) | Hệ thống chống những kiểu tấn công nào, chặn bằng cách nào |
| 5 | [Phần G](#phần-g-cách-tư-duy-nhóm-đã-xây-hệ-thống-theo-thứ-tự-nào) | Nếu phải làm lại từ đầu, làm theo thứ tự nào và vì sao |
| 6 | [Phần H](#phần-h-ghép-gạch-từng-chức-năng) | Từng chức năng: vì sao có nó, vì sao làm theo cách này |
| 7 | [Phần I](#phần-i-bức-tranh-toàn-cảnh), [Phụ lục](#phụ-lục-bảng-thuật-ngữ) | Kể lại toàn bộ hệ thống trong 5 phút |

Cuối mỗi phần có mục **Tự kiểm tra**: bấm vào câu hỏi để xem đáp án. Đừng xem đáp án trước khi tự trả lời.

**Cách đọc code kèm theo:** mỗi bài ghi tên file và tên hàm, ví dụ `luuChungChi()` trong
[src/chung_chi.php](../src/chung_chi.php). Mở file đó trên GitHub, bấm `Ctrl + F` gõ tên hàm. Code có chú thích
tiếng Việt giải thích *vì sao* từng chỗ. Tên hàm, tên biến viết tiếng Việt không dấu: `luuChungChi` là "lưu chứng
chỉ", `$loi` là "lỗi", `kiemTra` là "kiểm tra".

---

## Phần A. Bài toán: vì sao cần hệ thống này

Trước khi học kỹ thuật, phải hiểu **vấn đề** mà hệ thống giải. Không hiểu vấn đề thì mọi lựa chọn kỹ thuật phía
sau đều trông như tuỳ hứng.

### A1. Khi bạn mở một trang web có ổ khoá

Bạn ngồi quán cà phê, dùng wifi chung, mở trang ngân hàng để chuyển tiền. Mọi thứ bạn gửi đi (tên đăng nhập, mật
khẩu, số tiền) đi qua cục phát wifi của quán, qua nhà mạng, qua vài máy chủ trung gian rồi mới tới ngân hàng. Bất
kỳ ai ở các chặng đó đều có thể **đọc trộm** hoặc **sửa** dữ liệu, nếu dữ liệu đi dạng chữ thường.

Hai vấn đề cần giải:

1. **Bí mật:** người ở giữa không đọc được.
2. **Đúng người:** mình đang nói chuyện với ngân hàng thật, không phải kẻ giả làm ngân hàng.

Giao thức **HTTPS** giải cả hai. Địa chỉ bắt đầu bằng `https://` và trình duyệt hiện biểu tượng ổ khoá là dấu hiệu
đang dùng HTTPS. Chữ S là "secure" (an toàn): HTTP thường cộng thêm một lớp mã hoá tên là **TLS**.

### A2. Mã hoá và cặp khoá: trực giác, không cần toán

**Cách đơn giản nhất** là hai bên dùng chung một mật khẩu để khoá và mở dữ liệu (gọi là mã hoá *đối xứng*). Vấn đề:
bạn và ngân hàng chưa từng gặp nhau, làm sao thống nhất mật khẩu chung mà người ở giữa không nghe được? Gửi mật
khẩu qua mạng thì chính mật khẩu bị nghe trộm.

**Cách giải là cặp khoá** (mã hoá *bất đối xứng*). Hình dung một hòm thư có khe:

- **Khoá công khai** giống cái khe hòm thư: ai cũng bỏ thư vào được. Ngân hàng phát khoá này cho cả thế giới.
- **Khoá riêng** giống chìa mở hòm: chỉ ngân hàng giữ. Thư bỏ vào bằng khoá công khai chỉ mở được bằng khoá riêng.

Thực tế, mã hoá bất đối xứng chậm, nên trình duyệt chỉ dùng nó lúc mở đầu: hai bên trao đổi vài con số theo một cách
đặc biệt để **cùng tính ra một mật khẩu tạm** mà người nghe trộm ở giữa không tính được. Từ đó hai bên dùng mật khẩu
tạm đó (mã hoá đối xứng, nhanh hơn nhiều) cho phần còn lại của cuộc trò chuyện.

Cặp khoá còn làm được một việc nữa: **chữ ký số**. Ai có khoá riêng thì "ký" được lên dữ liệu; ai có khoá công khai
thì kiểm được chữ ký đó có đúng không, và dữ liệu có bị sửa sau khi ký không. Không có khoá riêng thì không giả được
chữ ký.

### A3. Vấn đề còn lại: khoá công khai này có đúng là của ngân hàng không?

Kẻ ngồi giữa (ví dụ một cục phát wifi giả) có thể đưa cho bạn **khoá công khai của hắn** và nói "đây là khoá của
ngân hàng". Bạn mã hoá bằng khoá đó, và hắn đọc được hết. Cặp khoá chỉ giải được vấn đề bí mật, chưa giải được vấn
đề **đúng người**.

Ngoài đời, vấn đề "người này có đúng là anh A không" được giải bằng **căn cước công dân**: một cơ quan mà ai cũng
tin (công an) xác nhận "ảnh này, tên này, số này là của anh A", có dấu đỏ không giả được, và có **ngày hết hạn**.

Trên mạng làm y hệt:

| Ngoài đời | Trên mạng |
|---|---|
| Căn cước công dân | **Chứng chỉ số** (certificate) |
| Ghi tên, ảnh, số căn cước | Ghi tên miền (ví dụ `cert.cipher.id.vn`) và khoá công khai của website |
| Công an cấp | **Tổ chức cấp chứng chỉ** (CA, Certificate Authority), ví dụ Let's Encrypt, DigiCert |
| Dấu đỏ | **Chữ ký số** của CA trên chứng chỉ |
| Ai cũng tin công an | Trình duyệt và hệ điều hành có sẵn danh sách các CA đáng tin |
| Ngày hết hạn | Ngày hết hạn của chứng chỉ |
| Mất căn cước thì báo huỷ | **Thu hồi** chứng chỉ khi lộ khoá riêng |

Khi bạn mở trang ngân hàng, máy chủ gửi chứng chỉ của nó. Trình duyệt kiểm: chứng chỉ có được một CA đáng tin ký
không, tên miền trên chứng chỉ có khớp địa chỉ đang mở không, còn hạn không. Đủ cả ba thì hiện ổ khoá. Thiếu một là
trình duyệt hiện cảnh báo đỏ chặn cả trang.

> **Đây là toàn bộ lý do tồn tại của Cipher Cert:** mỗi website, máy chủ thư, dịch vụ nội bộ của một tổ chức đều
> có chứng chỉ, mỗi cái một hạn. Hệ thống giúp không để cái nào hết hạn mà không ai biết.

### A4. Đọc một chứng chỉ thật

Đây là chứng chỉ thật của chính web nhóm mình, đọc ngày 08/11/2026 (bạn có thể tự đọc: mở
<https://cert.cipher.id.vn>, bấm vào ổ khoá cạnh địa chỉ, chọn xem chứng chỉ):

| Thông tin | Giá trị | Nghĩa | Cột trong CSDL |
|---|---|---|---|
| Tên chính (CN, Common Name) | `cert.cipher.id.vn` | Tên miền chứng chỉ này bảo vệ | `subject_cn` |
| Tên phụ (SAN, Subject Alternative Name) | chỉ có `cert.cipher.id.vn` (trùng tên chính, nên hệ thống không ghi lại) | Các tên miền khác cũng dùng chứng chỉ này | `san` |
| Tổ chức cấp | Let's Encrypt | CA đã ký | `ca_id` (trỏ sang bảng tổ chức cấp) |
| Ngày bắt đầu | 06/11/2026 | Trước ngày này chứng chỉ chưa có hiệu lực | `valid_from` |
| Ngày hết hạn | 04/02/2027 | Sau ngày này trình duyệt chặn | `valid_to` |
| Thuật toán khoá | ECDSA, 256 bit | Loại cặp khoá và độ dài | `key_algorithm`, `key_size` |
| Số sê-ri (serial) | một dãy số hex | CA đánh số cho từng chứng chỉ nó cấp | `serial` |
| Dấu vân tay (fingerprint SHA-256) | 64 ký tự hex | "Vân tay" của cả chứng chỉ, xem dưới | `fingerprint_sha256` |

Vài thuật ngữ cần hiểu kỹ hơn:

**Tên phụ (SAN).** Một công ty có `congty.vn`, `www.congty.vn`, `mail.congty.vn`. Mua ba chứng chỉ thì tốn và phải
theo dõi ba hạn. Nên một chứng chỉ được phép ghi nhiều tên miền: một tên chính và danh sách tên phụ. Còn có dạng
`*.congty.vn` (gọi là *wildcard*, ký tự đại diện): một chứng chỉ dùng cho mọi tên miền con. Vì vậy hệ thống cho
phép tên miền bắt đầu bằng `*.`.

**Dấu vân tay (fingerprint).** Là kết quả của một **hàm băm** chạy trên toàn bộ chứng chỉ. Hàm băm là hàm biến dữ
liệu dài bao nhiêu cũng được thành một chuỗi ngắn cố định, với hai tính chất: cùng đầu vào luôn ra cùng kết quả, và
đầu vào chỉ khác một chữ thì kết quả khác hẳn. Giống vân tay người: hai chứng chỉ khác nhau thì vân tay khác nhau,
nên dùng vân tay để **nhận diện** một chứng chỉ.

*Vì sao SHA-256 chứ không phải MD5 hay SHA-1?* MD5 và SHA-1 là các hàm băm cũ đã bị phá: người ta tạo được hai dữ
liệu khác nhau cho cùng một kết quả băm. Năm 2012, mã độc Flame giả được chứng chỉ của Microsoft nhờ điểm yếu của
MD5. Năm 2017, Google công bố tạo được hai file PDF khác nhau có cùng SHA-1. Hai hàm băm đã bị phá thì không còn
dùng để nhận diện được nữa. SHA-256 hiện chưa bị phá, và là loại trình duyệt đang dùng.

**Mã quản lý.** Đề yêu cầu "mỗi chứng chỉ có mã quản lý **hoặc** fingerprint". Nhóm làm mã quản lý (`CERT-001`...)
là **bắt buộc**, còn fingerprint là **tuỳ chọn**. Vì sao không bắt buộc fingerprint?

- Người nhập tay thường không có sẵn fingerprint (phải đọc từ file chứng chỉ).
- Gia hạn là cấp chứng chỉ mới, nên fingerprint **đổi** sau mỗi lần gia hạn. Nếu dùng fingerprint làm mã nhận diện
  thì cứ gia hạn là chứng chỉ "đổi danh tính". Mã quản lý thì giữ nguyên suốt đời.

**Thuật toán khoá.** Cặp khoá có nhiều loại, hệ thống lưu để biết chứng chỉ nào dùng loại gì:

| Loại | Đặc điểm | Vì sao có trong danh sách |
|---|---|---|
| RSA | Lâu đời nhất, phổ biến nhất. Cần khoá dài (2048 bit trở lên) | Phần lớn chứng chỉ hiện nay |
| ECDSA | Dựa trên đường cong elliptic: khoá ngắn hơn mà an toàn tương đương (ECDSA 256 bit ≈ RSA 3072 bit) | Let's Encrypt, Cloudflare dùng nhiều |
| Ed25519 | Một loại đường cong elliptic mới, nhanh | Dùng nhiều cho SSH, đang dần có ở chứng chỉ |
| GOST | Chuẩn mật mã quốc gia của Nga | Để hệ thống phát triển tiếp theo hướng mật mã sau này |

**Độ dài khoá** càng lớn thì càng khó phá, nhưng chỉ so được trong cùng một loại thuật toán. RSA dưới 2048 bit hiện
bị coi là yếu.

### A5. Vì sao chứng chỉ phải có hạn, và hạn ngày càng ngắn

Nếu chứng chỉ dùng mãi được thì đỡ phải gia hạn. Vì sao lại cố tình cho nó hết hạn?

- **Khoá riêng có thể bị lộ mà không ai biết.** Hạn ngắn giới hạn thời gian kẻ trộm khoá dùng được nó.
- **Thông tin có thể sai đi.** Công ty bán tên miền, đổi chủ; chứng chỉ cũ vẫn ghi tên chủ cũ.
- **Thuật toán cũ cần được thay.** Khi một thuật toán bị coi là yếu, hạn ngắn bảo đảm các chứng chỉ dùng nó tự biến
  mất sau một thời gian.
- **Thu hồi không đáng tin hoàn toàn.** Cơ chế báo "chứng chỉ này đã bị thu hồi" hoạt động không đều ở các trình
  duyệt, nên hạn ngắn là lớp bảo vệ chắc hơn.

Vì vậy hạn chứng chỉ ngày càng ngắn: trước đây vài năm; từ 2020 tối đa 398 ngày (khoảng 13 tháng); Let's Encrypt chỉ
cấp 90 ngày (chứng chỉ của web nhóm mình: 06/11/2026 → 04/02/2027, đúng 90 ngày). Năm 2025, CA/Browser Forum (nơi
các CA và hãng trình duyệt thống nhất luật chơi) đã quyết định rút dần hạn tối đa xuống 200 ngày (từ 2026), 100 ngày
(từ 2027) và **47 ngày** (từ 2029).

Hệ quả: **hạn càng ngắn, càng phải gia hạn nhiều lần, càng dễ quên**. Một tổ chức có 50 chứng chỉ hạn 47 ngày thì
trung bình mỗi tuần có vài chứng chỉ cần gia hạn.

### A6. Quên gia hạn thì sao

Chứng chỉ hết hạn thì trình duyệt hiện trang cảnh báo đỏ chặn khách. Các ứng dụng gọi nhau qua mạng (ứng dụng điện
thoại gọi máy chủ, máy chủ gọi máy chủ) thì từ chối kết nối luôn, không hỏi ai. Vài vụ nổi tiếng:

- **03/02/2020:** Microsoft Teams ngừng hoạt động mấy tiếng vì một chứng chỉ quên gia hạn.
- **12/2018:** một chứng chỉ hết hạn trong phần mềm của Ericsson làm mạng di động O2 (Anh) và SoftBank (Nhật) mất dịch
  vụ cho hàng chục triệu thuê bao.

Các công ty này không thiếu kỹ sư giỏi. Họ thiếu một chỗ **nhìn thấy tất cả chứng chỉ và hạn của chúng**, và một cơ
chế **nhắc trước**.

### A7. Từ vấn đề tới yêu cầu của đề

Bây giờ đọc lại đề 69 sẽ thấy mỗi chức năng là lời giải cho một vấn đề ở trên:

| Vấn đề thật | Chức năng trong đề | Trong Cipher Cert |
|---|---|---|
| Không biết tổ chức có bao nhiêu chứng chỉ, ở đâu | 1. Quản lý danh sách chứng chỉ | Danh sách, thêm, sửa, xoá |
| Chứng chỉ gắn với website nào, máy chủ nào, ai phụ trách | 2. Quản lý tên miền và máy chủ | Danh mục Tên miền, Máy chủ |
| Ai cấp, có đáng tin không | 3. Lưu thông tin tổ chức cấp | Danh mục Tổ chức cấp |
| Còn bao lâu nữa hết hạn | 4. Ngày cấp, ngày hết hạn · tiêu chí "tự tính số ngày còn hiệu lực" | Số ngày còn lại tự tính |
| Cái nào còn dùng được, cái nào hỏng | 5. Theo dõi trạng thái | 5 trạng thái tự tính |
| Quên gia hạn | 6. Cảnh báo · tiêu chí "mốc 30, 15, 7 ngày" | Trang Cảnh báo, số trên menu |
| Ai gia hạn lúc nào, bản cũ là gì | 7. Lịch sử gia hạn | Bảng lịch sử gia hạn |
| Báo cáo cho sếp: tình hình chung ra sao | 8. Thống kê · 9. Xuất báo cáo | Dashboard, Thống kê, CSV, bản in |
| Nhận diện một chứng chỉ không nhầm | Tiêu chí "mã quản lý hoặc fingerprint" | Mã quản lý bắt buộc, fingerprint tuỳ chọn |

Ngoài 9 chức năng riêng, đề có 10 yêu cầu chung cho mọi đề (đăng nhập, phân quyền, bảo mật, kiểm tra dữ liệu...).
Chúng là những viên gạch ở phần B đến F.

### Tự kiểm tra phần A

<details><summary>1. Cặp khoá đã giải được vấn đề bí mật, vì sao còn cần chứng chỉ?</summary>

Vì cặp khoá không cho biết khoá công khai đó **của ai**. Kẻ ở giữa có thể đưa khoá của hắn. Chứng chỉ là lời xác
nhận của một CA đáng tin: "khoá công khai này đúng là của tên miền này", kèm chữ ký số không giả được.
</details>

<details><summary>2. Vì sao hệ thống bắt buộc mã quản lý mà không bắt buộc fingerprint?</summary>

Fingerprint đổi sau mỗi lần gia hạn (gia hạn là cấp chứng chỉ mới), và người nhập tay thường không có sẵn. Mã quản lý
do mình đặt, giữ nguyên suốt đời chứng chỉ.
</details>

<details><summary>3. Vì sao hạn chứng chỉ ngày càng ngắn lại làm hệ thống này cần thiết hơn?</summary>

Hạn ngắn thì số lần gia hạn tăng, số lần có thể quên tăng theo. Với hạn 47 ngày, một tổ chức vài chục chứng chỉ có
việc gia hạn gần như mỗi tuần; không có chỗ theo dõi chung và nhắc trước thì sớm muộn sẽ quên.
</details>

---

## Phần B. Viên gạch của một trang web

### B1. Trình duyệt và máy chủ nói chuyện thế nào

Mọi trang web đều là một cuộc hỏi đáp giữa hai máy:

```
  Trình duyệt (máy bạn)                         Máy chủ (máy của web)
        │                                               │
        │ ── Yêu cầu: "cho tôi trang /login.php" ─────► │
        │                                               │  chạy code, tạo trang
        │ ◄──── Trả lời: mã 200 + nội dung HTML ─────── │
        │                                               │
     hiện trang
```

Ngôn ngữ của cuộc hỏi đáp này là **HTTP**. Mỗi lần bạn mở trang, bấm nút, gửi form là **một yêu cầu** và **một trả
lời**. Xong là hết: máy chủ không "nhớ" bạn giữa hai yêu cầu (điểm này quan trọng, phần F sẽ quay lại).

Một **địa chỉ** (URL) có các phần:

```
https://cert.cipher.id.vn/admin/chung_chi.php?trang_thai=expiring
└─┬─┘   └──────┬────────┘└──────────┬───────┘└────────┬────────┘
giao thức   tên miền          đường dẫn tới file     tham số
                              (trang nào)          (lọc "sắp hết hạn")
```

Phần sau dấu `?` là **tham số**: thông tin kèm theo yêu cầu. Ở đây là "lọc các chứng chỉ sắp hết hạn". Vì tham số
nằm ngay trên địa chỉ, **ai cũng sửa được** bằng cách gõ lại địa chỉ. Ghi nhớ điều này.

### B2. Hai loại yêu cầu: GET và POST

| | GET | POST |
|---|---|---|
| Dùng khi | **Xem**: mở trang, lọc, tìm | **Thay đổi dữ liệu**: thêm, sửa, xoá, đăng nhập |
| Dữ liệu nằm ở | Trên địa chỉ (sau dấu `?`) | Trong thân yêu cầu, không hiện trên địa chỉ |
| Mở lại, tải lại | Thoải mái | Trình duyệt hỏi "gửi lại form?" |

**Vì sao mọi thao tác đổi dữ liệu phải dùng POST?**

- Trình duyệt, công cụ tìm kiếm, phần mềm xem trước link có thể **tự mở** các link GET mà người dùng không bấm. Nếu
  "xoá chứng chỉ" là một link GET, chỉ cần một con bot đi qua là xoá sạch.
- Trang web lạ có thể nhúng một link GET vào ảnh (`<img src="...xoa?id=5">`). Người đang đăng nhập chỉ cần mở trang
  lạ đó là trình duyệt tự gửi yêu cầu xoá. POST thì không nhúng vào ảnh được (và còn được bảo vệ thêm bằng mã CSRF,
  xem phần F).

Cipher Cert theo đúng quy tắc này, kể cả đăng xuất: [public/logout.php](../public/logout.php) từ chối yêu cầu GET.

### B3. Mã trạng thái: máy chủ trả lời kèm một con số

| Mã | Nghĩa | Ví dụ trong Cipher Cert |
|---|---|---|
| 200 | Thành công | Mở trang Dashboard |
| 301, 302 | "Đi chỗ khác": trình duyệt tự chuyển sang địa chỉ mới | Chưa đăng nhập mà mở Dashboard thì bị chuyển về trang đăng nhập |
| 403 | Cấm: biết bạn là ai, nhưng bạn không có quyền | Nhân viên tự gõ địa chỉ trang Người dùng |
| 404 | Không tìm thấy | Mở thẳng file code `/src/auth.php` |
| 405 | Sai loại yêu cầu | Đăng xuất bằng GET |

[Báo cáo kiểm thử](KIEM_THU.md#4-kiểm-tay-trên-bản-chạy-thật) có bảng 12 ca nhóm đã thử trên web thật, mỗi ca kèm
mã trả về.

### B4. Ba lớp của một trang: HTML, CSS, JavaScript

Trang web trình duyệt nhận được gồm ba thứ, như một ngôi nhà:

| | Vai trò | Ví von |
|---|---|---|
| **HTML** | Nội dung và cấu trúc: tiêu đề, bảng, ô nhập, nút | Khung nhà, tường, cửa |
| **CSS** | Hình thức: màu, cỡ chữ, bố cục, giao diện tối | Sơn, nội thất |
| **JavaScript** (JS) | Hành vi chạy ngay trên trình duyệt: hỏi lại trước khi xoá, vẽ biểu đồ, báo lỗi form tức thì | Điện, công tắc |

Một ô nhập trong HTML trông thế này:

```html
<label for="subject_cn">Tên chính (CN)</label>
<input id="subject_cn" name="subject_cn" required maxlength="255">
```

`required` nghĩa là bắt buộc nhập. Khi bấm Lưu, JavaScript trong [app.js](../public/assets/app.js) đọc các thuộc tính
này và tô đỏ ô sai ngay, không cần chờ máy chủ.

### B5. Vì sao dùng Tabler để làm giao diện

Tự viết CSS cho cả một khu quản trị (bảng, form, menu, nút, giao diện tối, khổ điện thoại) mất hàng tuần. Có sẵn
những bộ giao diện miễn phí làm việc này. Đề cũng cho phép dùng Bootstrap và thư viện hỗ trợ.

| Phương án | Được | Mất |
|---|---|---|
| Tự viết CSS từ đầu | Toàn quyền | Rất lâu; khó đẹp và đồng đều; phải tự lo khổ điện thoại |
| Bootstrap trần (bộ khung CSS phổ biến nhất) | Phổ biến, nhiều tài liệu | Trông "mặc định", phải tự làm thẻ số, biểu đồ, menu quản trị |
| AdminLTE 4, CoreUI (giao diện quản trị dựng trên Bootstrap) | Nhiều thành phần sẵn | Phong cách cũ hơn, giao diện tối kém hơn |
| **Tabler** (cũng dựng trên Bootstrap 5) | Thiết kế hiện đại, có giao diện tối sẵn, nhiều thành phần cho trang quản trị | File CSS nặng gấp đôi AdminLTE |

Nhóm chọn **Tabler** (quyết định QD-13). Phần CSS nặng hơn chấp nhận được, vì web chỉ tải một lần rồi trình duyệt giữ
lại.

**Vì sao chép Tabler vào thư mục của web, không dùng link CDN?** CDN là máy chủ công cộng phát sẵn các thư viện, chỉ
cần một dòng link. Nhưng nếu hôm demo mạng chập chờn, hoặc máy giảng viên chạy không có internet, thì trang mất toàn
bộ giao diện. Chép vào `public/assets/` thì web chạy được kể cả không có mạng. Cùng lý do với Chart.js và font Inter.

### B6. Vì sao vẽ biểu đồ bằng Chart.js

| Phương án | Được | Mất |
|---|---|---|
| Tự vẽ bằng SVG | Không thêm thư viện | Phải tự lo trục, nhãn, chú thích, rê chuột |
| D3.js | Vẽ được mọi thứ | Rất phức tạp, quá sức cho 4 biểu đồ cột |
| **Chart.js** | Vài dòng là có biểu đồ đẹp; không cần công cụ build | Ít tuỳ biến hơn D3 |

Đề và SPEC đã ghi Chart.js từ đầu, và 4 biểu đồ cột không cần gì hơn (QD-20).

### B7. Nguyên tắc vàng: không tin bất cứ thứ gì từ trình duyệt

Mọi thứ chạy trên trình duyệt đều nằm trong tay người dùng:

- Bấm `F12` là sửa được HTML: xoá thuộc tính `required`, hiện lại nút đã ẩn.
- Tắt JavaScript là mọi kiểm tra bằng JS biến mất.
- Không cần trình duyệt nữa: công cụ như `curl` gửi được yêu cầu bất kỳ, với dữ liệu bất kỳ.

Vì vậy, trong Cipher Cert:

- JavaScript kiểm tra form chỉ để **tiện cho người dùng** (báo lỗi nhanh). **PHP kiểm tra lại toàn bộ** ở máy chủ.
- Ẩn nút Xoá với Nhân viên chỉ để giao diện gọn. **Quyền được kiểm ở máy chủ**: Nhân viên tự gửi lệnh xoá vẫn bị từ
  chối.
- Tham số trên địa chỉ có giá trị lạ thì bị **coi như không có**, trang vẫn chạy bình thường.

Đề yêu cầu "kiểm tra dữ liệu bằng cả JavaScript và PHP" chính vì nguyên tắc này: JS để nhanh, PHP để chắc.

### Tự kiểm tra phần B

<details><summary>1. Vì sao nút Xoá không được là một đường link bình thường?</summary>

Link là yêu cầu GET, có thể bị bot, phần mềm xem trước link, hay một thẻ ảnh trên trang lạ tự mở mà người dùng không
bấm. Thao tác đổi dữ liệu phải dùng POST, kèm mã CSRF.
</details>

<details><summary>2. Đã kiểm tra form bằng JavaScript rồi, sao PHP còn kiểm tra lại?</summary>

Vì JavaScript chạy trên máy người dùng: tắt được, sửa được, bỏ qua được (gửi thẳng yêu cầu bằng `curl`). Chỉ kiểm tra
ở máy chủ mới chắc chắn.
</details>

<details><summary>3. Vì sao thư viện được chép vào thư mục của web chứ không dùng CDN?</summary>

Để web chạy được khi không có mạng hoặc mạng chập chờn lúc demo, và không phụ thuộc máy chủ của người khác.
</details>

---

## Phần C. Phía máy chủ: PHP

### C1. PHP làm gì

Trình duyệt chỉ hiểu HTML, CSS, JS. Nhưng nội dung trang Dashboard thay đổi mỗi ngày (số chứng chỉ, số ngày còn lại),
nên không thể viết sẵn một file HTML cố định. Cần một chương trình ở máy chủ **tạo HTML mới mỗi lần có yêu cầu**, dựa
trên dữ liệu hiện tại. **PHP** là ngôn ngữ làm việc đó.

```
Trình duyệt ── yêu cầu /admin/index.php ──► Máy chủ chạy file index.php:
                                              1. hỏi CSDL: có bao nhiêu chứng chỉ mỗi trạng thái?
                                              2. ghép kết quả vào HTML
Trình duyệt ◄────── HTML đã ghép số ──────── 3. gửi HTML về
```

Người dùng không bao giờ thấy code PHP, chỉ thấy HTML mà PHP tạo ra.

### C2. Vì sao PHP thuần, không dùng framework

**Framework** là bộ khung có sẵn cách tổ chức code, ví dụ Laravel cho PHP, ASP.NET cho C#. Nó làm sẵn nhiều thứ
(định tuyến địa chỉ, đăng nhập, truy vấn CSDL) nhưng bắt người dùng học cách làm của nó.

| Phương án | Được | Mất |
|---|---|---|
| C# / ASP.NET | Mạnh, có kiểu dữ liệu chặt | Đề bắt buộc PHP + MySQL: loại ngay |
| Laravel (framework PHP) | Nhiều thứ làm sẵn | Phải học thêm cả framework; nhiều "phép màu" ẩn, khó giải thích từng dòng; nặng so với web vài chục trang |
| **PHP thuần + PDO** | Đọc file nào hiểu file đó; ít thứ phải học; chạy ở mọi nơi có PHP | Phải tự làm vài thứ (đăng nhập, CSRF), nhưng chính vì tự làm nên hiểu rõ |

Nhóm chọn PHP thuần (QD-02): làm trong 10 ngày, web vài chục trang, không cần framework nặng.

### C3. Mỗi trang một file

Trong Cipher Cert, mỗi địa chỉ ứng với đúng một file: `/admin/canh_bao.php` là file
[public/admin/canh_bao.php](../public/admin/canh_bao.php).

Cách khác là dùng một **bộ định tuyến** (router): mọi yêu cầu đi vào một file duy nhất, file đó đọc địa chỉ rồi quyết
định chạy đoạn code nào. Địa chỉ đẹp hơn (`/canh-bao` thay vì `/admin/canh_bao.php`), nhưng phải cấu hình máy chủ web
để chuyển mọi địa chỉ về một file (với Apache là file `.htaccess`). Cấu hình đó khác nhau giữa các máy chủ web, dễ
hỏng khi giảng viên cài theo cách khác.

Nhóm chọn mỗi trang một file (QD-16): chạy được ngay với `php -S` ở máy mình, với Caddy trên máy chủ thật, với Apache
trong XAMPP, không cần cấu hình gì thêm.

### C4. Một yêu cầu đi qua file PHP thế nào

Mọi trang trong khu quản trị đi theo cùng một khuôn. Ví dụ rút gọn từ trang Cảnh báo:

```php
<?php
require __DIR__ . '/../../src/khoi_dong.php';   // 1. Nạp phần dùng chung: CSDL, phiên, đăng nhập
require __DIR__ . '/../../src/chung_chi.php';   //    và các hàm về chứng chỉ

$pdo = ketNoiCsdl();                            // 2. Mở kết nối CSDL
$nguoi = yeuCauDangNhap($pdo);                  // 3. Chưa đăng nhập thì đá về trang đăng nhập

taoCanhBao($pdo);                               // 4. Gọi hàm nghiệp vụ trong src/
$danhSach = danhSachCanhBao($pdo);              //    lấy dữ liệu cần hiện

require __DIR__ . '/../../views/dau_trang.php'; // 5. In HTML: đầu trang, menu...
?>
<table> ... in từng dòng của $danhSach ... </table>
```

Năm bước này lặp lại ở mọi trang. Đọc được một trang là đọc được tất cả.

### C5. Vì sao tách code thành nhiều thư mục

```
cipher/
├── public/   Thư mục DUY NHẤT mở ra web. Mỗi file là một trang.
├── src/      Quy tắc nghiệp vụ: kiểm tra, lưu, tính toán. Không in HTML.
├── views/    Mảnh giao diện dùng chung: đầu trang và menu, bộ lọc, nhãn trạng thái.
├── config/   Kết nối CSDL.
├── database/ File SQL tạo bảng và dữ liệu mẫu.
└── tests/    Bộ test.
```

Có hai lý do:

**1. An toàn.** Máy chủ web chỉ được trỏ vào `public/`. Không ai mở được `src/auth.php` hay `config/db.php` từ trình
duyệt (thử thì nhận 404). Nếu để tất cả chung một thư mục, chỉ cần máy chủ cấu hình sai một lần là lộ mã nguồn.

**2. Test được.** Quy tắc nằm trong `src/` dưới dạng hàm, không dính HTML, nên bộ test gọi thẳng được: "gọi
`luuChungChi()` với ngày hết hạn trước ngày bắt đầu, phải nhận lỗi". Nếu quy tắc viết lẫn trong trang HTML thì muốn
test phải giả lập cả trình duyệt.

Nhờ cách tách này, câu hỏi "hệ thống kiểm tra những gì?" chỉ cần đọc `src/`.

### C6. Lưu xong thì chuyển trang (Post/Redirect/Get)

Bạn điền form thêm chứng chỉ, bấm Lưu (POST). Nếu máy chủ trả lời thẳng bằng trang "Đã thêm", thì khi bạn bấm F5 tải
lại, trình duyệt **gửi lại form** và thêm chứng chỉ lần hai.

Cách chữa: lưu xong, máy chủ không trả trang mà trả lệnh "đi tới trang danh sách" (mã 302). Trình duyệt tự mở trang
danh sách bằng GET. Bấm F5 lúc này chỉ tải lại danh sách. Câu "Đã thêm chứng chỉ" được cất tạm vào phiên rồi hiện một
lần ở trang sau.

Trong code: `datThongBao()` rồi `chuyenHuong()`, cả hai ở [src/khoi_dong.php](../src/khoi_dong.php).

### C7. Hàm `e()` và kiểu tấn công chèn mã (XSS)

Giả sử ai đó đặt tên chính của chứng chỉ là:

```
<script>gửi cookie của người xem tới máy kẻ xấu</script>
```

Nếu PHP in thẳng tên này vào HTML, trình duyệt của **mọi người xem** danh sách sẽ chạy đoạn script đó. Đây là kiểu
tấn công **XSS** (cross-site scripting, chèn mã vào trang).

Cách chặn: trước khi in, đổi các ký tự đặc biệt của HTML thành dạng "chỉ để hiển thị": `<` thành `&lt;`, `>` thành
`&gt;`... Trình duyệt hiện đúng chữ `<script>` lên màn hình chứ không chạy. PHP có sẵn hàm `htmlspecialchars` làm
việc này; Cipher Cert gói nó thành hàm ngắn `e()` và **mọi dữ liệu in ra trang đều đi qua `e()`**.

### Tự kiểm tra phần C

<details><summary>1. Vì sao người dùng không đọc được code PHP dù trang web chạy bằng PHP?</summary>

PHP chạy ở máy chủ và chỉ gửi về HTML nó tạo ra. Thêm nữa, máy chủ web chỉ mở thư mục `public/`, nên các file trong
`src/`, `config/` không tải về được.
</details>

<details><summary>2. Lưu form xong, vì sao không hiện luôn trang "Đã lưu" mà phải chuyển sang trang khác?</summary>

Để bấm F5 không gửi lại form (lưu hai lần). Trang sau mở bằng GET nên tải lại vô hại.
</details>

<details><summary>3. Vì sao quy tắc kiểm tra nằm trong src/ chứ không viết thẳng trong trang?</summary>

Để test gọi thẳng được hàm mà không cần trình duyệt, để mọi trang dùng chung một quy tắc, và để muốn biết hệ thống
kiểm tra gì chỉ cần đọc một chỗ.
</details>

---

## Phần D. Cơ sở dữ liệu

### D1. Vì sao cần cơ sở dữ liệu

Có thể lưu chứng chỉ vào một file Excel hay file văn bản. Vấn đề xuất hiện ngay:

- Hai người cùng sửa một lúc thì ghi đè lên nhau.
- Không gì ngăn gõ "ngày hết hạn" là chữ "abc", hay hai chứng chỉ trùng mã.
- Muốn hỏi "các chứng chỉ sắp hết hạn của máy chủ web-01" phải tự viết code đọc và lọc cả file.

**Cơ sở dữ liệu** (CSDL) là phần mềm chuyên cất dữ liệu, giải cả ba: cho nhiều người dùng cùng lúc an toàn, tự từ
chối dữ liệu sai luật, và có ngôn ngữ hỏi đáp **SQL** để lấy đúng dữ liệu cần.

Cipher Cert dùng **MariaDB**. Đề yêu cầu MySQL trong XAMPP, nhưng thật ra XAMPP cài sẵn MariaDB: một nhánh tách ra từ
MySQL, dùng cùng ngôn ngữ, cùng công cụ. Dùng MariaDB cả ở máy mình lẫn máy chủ thật thì ít lệch nhau nhất (QD-09).

### D2. Bảng, dòng, cột, kiểu dữ liệu

CSDL chia dữ liệu thành các **bảng**, giống các sheet Excel. Mỗi **dòng** là một bản ghi (một chứng chỉ), mỗi **cột**
là một thuộc tính (mã, tên, ngày hết hạn). Khác Excel: mỗi cột có **kiểu dữ liệu** cố định, CSDL từ chối giá trị sai
kiểu.

| Kiểu | Dùng cho | Ví dụ cột |
|---|---|---|
| `INT` | Số nguyên | `id`, `key_size` |
| `VARCHAR(n)` | Chữ, tối đa n ký tự | `cert_code` (tối đa 30) |
| `TEXT` | Chữ dài | `san` (danh sách tên phụ) |
| `DATETIME` | Ngày giờ | `valid_from`, `valid_to` |
| `ENUM(...)` | Chỉ một trong vài giá trị cho sẵn | `environment`: production, staging, development |
| `TINYINT(1)` | Có/không | `is_revoked` (0 hoặc 1) |

**Vì sao ngày lưu bằng `DATETIME` chứ không lưu dạng chữ "04/02/2027"?** Chữ thì không so sánh đúng được ("10/01"
đứng trước "09/12" theo thứ tự chữ cái) và không tính được "còn bao nhiêu ngày". Kiểu `DATETIME` thì CSDL hiểu là ngày
giờ, so sánh và tính toán được.

**Vì sao số sê-ri lưu bằng `VARCHAR` chứ không phải số?** Sê-ri chứng chỉ có thể dài tới 40 chữ số hex, vượt quá số
nguyên lớn nhất mà CSDL lưu được. Thực chất nó là một mã định danh, không ai cộng trừ nó, nên lưu dạng chữ.

### D3. Khoá chính

Mỗi bảng có một cột **khoá chính** (primary key): giá trị duy nhất để chỉ đích danh một dòng. Cipher Cert dùng cột
`id`, số tự tăng 1, 2, 3...

*Vì sao không dùng luôn mã quản lý `CERT-001` làm khoá chính?* Vì mã quản lý **sửa được** (người dùng gõ nhầm rồi
sửa). Khoá chính thì các bảng khác trỏ tới (xem D4); nếu nó đổi thì mọi chỗ trỏ tới phải đổi theo. `id` là con số
không ai nhìn thấy, không ai có lý do sửa.

### D4. Khoá ngoại và quan hệ: vì sao chia thành nhiều bảng

**Cách ngây thơ:** một bảng chứng chỉ, mỗi dòng ghi luôn tên máy chủ, địa chỉ IP, tên tổ chức cấp, quốc gia của tổ
chức cấp...

| mã | tên miền | máy chủ | IP máy chủ | tổ chức cấp | quốc gia |
|---|---|---|---|---|---|
| CERT-001 | congty.example | web-01 | 192.0.2.10 | DigiCert | Hoa Kỳ |
| CERT-002 | portal.congty.example | web-01 | 192.0.2.10 | Digicert | Hoa Kỳ |
| CERT-003 | mail.congty.example | web01 | 192.0.2.11 | DigiCert | Mỹ |

Nhìn ba dòng đã thấy ba vấn đề:

1. **Gõ lặp, gõ lệch:** "DigiCert" và "Digicert", "web-01" và "web01", "Hoa Kỳ" và "Mỹ". Thống kê "theo tổ chức cấp"
   sẽ đếm thành hai tổ chức.
2. **Sửa một chỗ phải sửa trăm chỗ:** máy chủ web-01 đổi IP thì phải sửa mọi dòng chứng chỉ của nó; sót một dòng là dữ
   liệu mâu thuẫn.
3. **Không có chỗ cho thứ chưa có chứng chỉ:** muốn ghi máy chủ mới trước khi có chứng chỉ thì không có dòng nào để ghi.

**Cách giải: mỗi loại "thứ" một bảng, nối với nhau bằng số id.** Máy chủ ở bảng `servers`, tên miền ở bảng `domains`,
tổ chức cấp ở bảng `certificate_authorities`. Bảng chứng chỉ không ghi tên tổ chức cấp, chỉ ghi `ca_id = 3`, nghĩa là
"tổ chức cấp có id 3". Cột như `ca_id` gọi là **khoá ngoại** (foreign key): nó trỏ sang khoá chính của bảng khác.

```
servers (máy chủ)        domains (tên miền)                   certificates (chứng chỉ)
┌────┬──────────┐        ┌────┬───────────────────┬─────────┐ ┌────┬───────────┬───────────┐
│ id │ hostname │        │ id │ domain_name       │server_id│ │ id │ cert_code │ domain_id │
├────┼──────────┤        ├────┼───────────────────┼─────────┤ ├────┼───────────┼───────────┤
│  1 │ web-01   │◄───────┤  1 │ congty.example    │    1    │◄┤  1 │ CERT-001  │     1     │
│    │          │◄───────┤  2 │ portal.congty...  │    1    │◄┤  2 │ CERT-002  │     2     │
└────┴──────────┘        └────┴───────────────────┴─────────┘ └────┴───────────┴───────────┘
 một máy chủ có NHIỀU tên miền         một tên miền có NHIỀU chứng chỉ
```

Đây gọi là quan hệ **một – nhiều**: một máy chủ có nhiều tên miền, một tên miền có nhiều chứng chỉ. Kỹ thuật tách bảng
để mỗi thông tin chỉ nằm đúng một chỗ gọi là **chuẩn hoá** dữ liệu.

CSDL kiểm khoá ngoại giúp mình: không thể ghi `domain_id = 99` nếu không có tên miền id 99.

Toàn bộ 9 bảng và các đường nối vẽ ở [ERD.md](ERD.md). ERD (Entity Relationship Diagram) là sơ đồ các bảng và quan hệ
giữa chúng. Phần G sẽ kể vì sao có đúng 9 bảng này.

### D5. Xoá bản ghi "cha" thì các bản ghi "con" ra sao

Xoá máy chủ web-01 trong khi vẫn còn tên miền trỏ tới nó. Có ba cách xử lý, khai báo ngay trong CSDL:

| Cách | Làm gì | Trong Cipher Cert |
|---|---|---|
| `RESTRICT` (chặn) | Từ chối xoá cha khi còn con | Hầu hết các quan hệ |
| `CASCADE` (xoá dây chuyền) | Xoá cha thì xoá luôn mọi con | Chứng chỉ → cảnh báo, lịch sử gia hạn |
| `SET NULL` | Xoá cha, con giữ lại nhưng mất liên kết | Không dùng |

**Vì sao phần lớn là chặn?** Xoá dây chuyền một máy chủ sẽ kéo theo mọi tên miền của nó, rồi mọi chứng chỉ của các tên
miền đó: một cú bấm nhầm mất hàng trăm dòng. Chặn thì an toàn: hệ thống báo "Máy chủ này còn tên miền", người dùng
phải tự chuyển hoặc xoá từng thứ, biết rõ mình đang làm gì.

**Vì sao cảnh báo và lịch sử gia hạn thì xoá dây chuyền?** Chúng không có nghĩa gì khi chứng chỉ không còn. Giữ lại chỉ
thành rác.

Trong code, hàm `xoaHoacBaoConDuLieu()` ở [config/db.php](../config/db.php) không tự đếm "còn con không" trước khi
xoá, mà cứ xoá và để CSDL quyết định: CSDL từ chối thì báo lý do. Lợi ích: sau này thêm một bảng mới có khoá ngoại thì
quy tắc vẫn đúng mà không phải sửa hàm.

### D6. UNIQUE, CHECK, NOT NULL: lớp chặn cuối cùng

| Ràng buộc | Nghĩa | Ví dụ |
|---|---|---|
| `NOT NULL` | Bắt buộc có giá trị | `cert_code`, `valid_to` |
| `UNIQUE` | Không trùng với dòng khác | `cert_code`, `fingerprint_sha256`, `domain_name` |
| `CHECK` | Phải thoả điều kiện | `valid_to > valid_from`; cổng từ 1 đến 65535; mốc cảnh báo chỉ là 30, 15 hoặc 7 |

**PHP đã kiểm tra rồi, sao CSDL còn kiểm lần nữa?** Có hai lý do:

1. **Hai yêu cầu cùng lúc.** Hai người cùng thêm chứng chỉ mã `CERT-051`. Cả hai cùng hỏi "đã có CERT-051 chưa?",
   cùng nhận "chưa", cùng lưu. PHP của mỗi người đều kiểm đúng, nhưng kết quả vẫn trùng. Chỉ CSDL, nơi xử lý lần lượt
   từng lệnh ghi, mới chặn được người thứ hai.
2. **Có đường ghi không đi qua PHP:** người quản trị gõ lệnh thẳng vào CSDL, một đoạn code viết sau này quên kiểm.

Cách làm trong Cipher Cert là **ba lớp**: JavaScript báo nhanh trên trình duyệt → PHP kiểm đầy đủ và báo lỗi dễ hiểu
→ CSDL chặn lần cuối. Khi CSDL từ chối vì trùng, PHP bắt lỗi đó và đổi thành câu dễ hiểu cho đúng ô (hàm
`loiNeuTrung()`).

### D7. NULL khác chuỗi rỗng

`NULL` nghĩa là "không có giá trị", khác với chuỗi rỗng `''` (có giá trị, và giá trị đó không có chữ nào). Phân biệt
này quan trọng với `UNIQUE`: CSDL cho phép **nhiều dòng NULL**, nhưng chỉ **một dòng chuỗi rỗng**.

Fingerprint là tuỳ chọn. Nếu ô trống lưu thành `''` thì chứng chỉ thứ hai không nhập fingerprint sẽ bị báo "trùng
fingerprint" với chứng chỉ thứ nhất. Nên Cipher Cert đổi ô trống thành `NULL` trước khi lưu.

### D8. Chữ tiếng Việt, chữ hoa và chữ thường

**Bộ ký tự `utf8mb4`.** Máy tính lưu chữ bằng số; bộ ký tự là bảng quy đổi. `utf8mb4` chứa được mọi chữ trên đời, kể
cả tiếng Việt có dấu. Có một bẫy: trong MySQL, bộ tên `utf8` chỉ là bản thiếu (không chứa được một số ký tự như emoji),
nên phải dùng `utf8mb4` mới đủ.

**Fingerprint phải đúng một dạng.** Cùng một vân tay có thể viết `EE:7A...` hay `ee7a...`. Nếu lưu nguyên như người
dùng gõ, `UNIQUE` sẽ coi đó là hai giá trị khác nhau và để lọt trùng. Cách làm hai lớp:

- PHP **chuẩn hoá** trước khi lưu: bỏ dấu `:` và khoảng trắng, đổi về chữ thường (`chuanHoaFingerprint()`).
- CSDL dùng kiểu so sánh `ascii_bin` (phân biệt hoa thường) kèm `CHECK` chỉ nhận đúng 64 ký tự `0-9a-f`. Lỡ có đoạn
  code nào quên chuẩn hoá thì CSDL từ chối, không để lọt.

### D9. View: tính chứ không lưu

Số ngày còn lại và trạng thái của chứng chỉ **thay đổi mỗi ngày** dù không ai sửa gì. Có hai cách:

| Phương án | Được | Mất |
|---|---|---|
| Lưu cột `days_left`, `status` trong bảng, mỗi đêm chạy một lệnh cập nhật lại | Đọc nhanh | Cần lịch chạy nền (cron); quên chạy hoặc chạy lỗi là số liệu cũ mà không ai biết |
| **Tính lại mỗi lần đọc** | Luôn đúng theo ngày giờ hiện tại; không cần chạy nền | Tốn chút tính toán mỗi lần đọc (không đáng kể với vài nghìn dòng) |

Cipher Cert chọn tính lại mỗi lần đọc, bằng một **view**. View là một "bảng ảo": trông như bảng, đọc như bảng, nhưng
thực chất là một câu truy vấn chạy mỗi lần có người đọc. View `v_certificates` trong
[database/schema.sql](../database/schema.sql) gồm mọi cột của bảng chứng chỉ, cộng thêm hai cột tính ra:

```sql
DATEDIFF(c.valid_to, NOW()) AS days_left,                  -- số ngày từ hôm nay tới hạn
CASE
    WHEN c.is_revoked = 1 THEN 'revoked'                    -- đã thu hồi
    WHEN c.valid_from > NOW() THEN 'not_yet_valid'          -- chưa tới ngày bắt đầu
    WHEN c.valid_to < NOW() THEN 'expired'                  -- đã qua hạn
    WHEN DATEDIFF(c.valid_to, NOW()) <= 30 THEN 'expiring'  -- còn tối đa 30 ngày
    ELSE 'valid'                                            -- còn lại
END AS status
```

Mọi trang đều đọc chứng chỉ từ view này, nên mọi nơi thấy cùng một trạng thái. Thứ tự các dòng `WHEN` có ý nghĩa,
xem [H3](#h3-trạng-thái-và-số-ngày-còn-lại).

### D10. Trigger: CSDL tự phản ứng

**Trigger** là đoạn lệnh CSDL tự chạy khi có sự kiện trên một bảng (thêm, sửa, xoá dòng). Cipher Cert dùng 2 trigger
trên bảng nhật ký: mọi lệnh sửa hoặc xoá dòng nhật ký đều bị từ chối, kể cả lệnh gõ thẳng vào CSDL. Lý do ở
[H11](#h11-nhật-ký-hoạt-động-chức-năng-mở-rộng-1).

### D11. Giao dịch: tất cả hoặc không gì cả

Chuyển khoản từ tài khoản A sang B gồm hai bước: trừ tiền A, cộng tiền B. Nếu máy chủ sập giữa hai bước, tiền biến
mất. **Giao dịch** (transaction) gói nhiều lệnh thành một khối: hoặc mọi lệnh đều có hiệu lực, hoặc (nếu có lỗi) quay
lại như chưa làm gì.

Gia hạn chứng chỉ cũng có hai bước: chép thông tin cũ sang bảng lịch sử, rồi cập nhật hạn mới. Nếu bước hai lỗi (ví dụ
fingerprint mới trùng với chứng chỉ khác) mà bước một đã chạy, thì lịch sử có một dòng "đã gia hạn" giả. Nên
`giaHanChungChi()` gói cả hai trong một giao dịch: `BEGIN` ... `COMMIT`, lỗi thì `ROLLBACK` (quay lại).

Để có khoá ngoại và giao dịch, các bảng dùng bộ máy lưu trữ **InnoDB** của MariaDB (bộ máy cũ MyISAM không có cả hai).

### D12. SQL cơ bản

Bốn lệnh làm phần lớn công việc, ví dụ với bảng của Cipher Cert:

```sql
-- Đọc: các chứng chỉ sắp hết hạn, gần hạn nhất trước
SELECT cert_code, subject_cn, days_left FROM v_certificates
WHERE status = 'expiring' ORDER BY valid_to;

-- Thêm một tên miền thuộc máy chủ id 1
INSERT INTO domains (domain_name, server_id) VALUES ('shop.congty.example', 1);

-- Sửa: thu hồi chứng chỉ id 5
UPDATE certificates SET is_revoked = 1 WHERE id = 5;

-- Xoá tên miền id 7 (CSDL sẽ từ chối nếu còn chứng chỉ trỏ tới nó)
DELETE FROM domains WHERE id = 7;
```

Lấy dữ liệu từ nhiều bảng cùng lúc dùng `JOIN` (nối bảng theo khoá ngoại):

```sql
-- Mỗi chứng chỉ kèm tên tổ chức cấp
SELECT c.cert_code, a.name
FROM certificates c JOIN certificate_authorities a ON a.id = c.ca_id;
```

### Tự kiểm tra phần D

<details><summary>1. Vì sao không ghi thẳng tên tổ chức cấp vào mỗi dòng chứng chỉ?</summary>

Gõ lặp dễ gõ lệch ("DigiCert", "Digicert"), làm thống kê sai; đổi tên phải sửa mọi dòng. Tách ra bảng riêng thì mỗi
tổ chức cấp chỉ ghi một lần, chứng chỉ chỉ trỏ tới bằng id.
</details>

<details><summary>2. Vì sao xoá máy chủ còn tên miền thì bị chặn, còn xoá chứng chỉ thì cảnh báo và lịch sử bị xoá theo?</summary>

Xoá dây chuyền máy chủ có thể mất hàng loạt dữ liệu chỉ vì một cú bấm nhầm, nên chặn và bắt người dùng xử lý từng
phần. Cảnh báo và lịch sử gia hạn không còn ý nghĩa khi chứng chỉ đã mất, giữ lại chỉ thành rác.
</details>

<details><summary>3. Trạng thái chứng chỉ không lưu trong bảng. Vậy nó đến từ đâu, và vì sao làm vậy?</summary>

Từ view `v_certificates`, tính lại mỗi lần đọc dựa trên ngày hiện tại. Lưu cứng thì phải có lịch chạy nền cập nhật mỗi
ngày; quên hay lỗi là số liệu sai mà không ai biết.
</details>

<details><summary>4. PHP đã kiểm tra mã quản lý không trùng, sao vẫn cần UNIQUE trong CSDL?</summary>

Hai người lưu cùng lúc đều qua được kiểm tra của PHP. Chỉ CSDL xử lý lần lượt từng lệnh ghi mới chặn được người thứ
hai. Thêm nữa, có những đường ghi không đi qua PHP.
</details>

---

## Phần E. Nối PHP với cơ sở dữ liệu an toàn

### E1. Một cửa duy nhất tới CSDL

PHP nói chuyện với CSDL qua **PDO**, bộ công cụ có sẵn trong PHP để kết nối nhiều loại CSDL bằng cùng một cách viết.
Trong Cipher Cert, **chỉ có một hàm** mở kết nối: `ketNoiCsdl()` ở [config/db.php](../config/db.php). Mọi trang, mọi
test đều gọi hàm này, không ai tự tạo kết nối riêng.

Vì sao gom về một cửa? Vì mỗi kết nối cần cài đặt đúng mấy thứ (múi giờ, chế độ nghiêm ngặt, cách báo lỗi). Nếu mười
trang tự mở kết nối thì chỉ cần một trang quên một dòng là trang đó tính sai ngày mà không ai biết. Một cửa thì sửa một
chỗ là đúng cho tất cả. Máy khác (như máy chủ thật) cần tên đăng nhập CSDL khác thì tạo file
`config/config.local.php`; file này không đưa lên git để mật khẩu CSDL không lộ.

### E2. SQL Injection: kiểu tấn công nổi tiếng nhất

Ô tìm kiếm chứng chỉ. Cách viết **sai** là ghép thẳng chữ người dùng gõ vào câu SQL:

```php
// SAI: đừng bao giờ làm thế này
$sql = "SELECT * FROM certificates WHERE cert_code = '" . $_GET['ma'] . "'";
```

Người dùng bình thường gõ `CERT-001`, câu SQL thành `... WHERE cert_code = 'CERT-001'`. Nhưng kẻ xấu gõ:

```
' OR '1'='1
```

Câu SQL thành `... WHERE cert_code = '' OR '1'='1'`. Điều kiện `'1'='1'` luôn đúng, nên trả về **mọi** chứng chỉ. Gõ
khéo hơn nữa thì đọc được bảng người dùng, hoặc xoá cả bảng. Kiểu tấn công này gọi là **SQL Injection** (tiêm SQL):
dữ liệu người dùng bị CSDL hiểu nhầm thành **lệnh**.

**Cách chặn tận gốc: prepared statement** (câu lệnh chuẩn bị trước). Câu SQL được gửi trước, với dấu `?` ở chỗ cần
điền; dữ liệu gửi **riêng** sau:

```php
// ĐÚNG: câu lệnh và dữ liệu đi hai đường riêng
$cau = $pdo->prepare('SELECT * FROM certificates WHERE cert_code = ?');
$cau->execute([$ma]);
```

CSDL đã biết cấu trúc câu lệnh từ trước, nên dù dữ liệu chứa dấu nháy, chữ `OR` hay gì đi nữa, nó vẫn chỉ là **giá trị
để so sánh**, không bao giờ thành lệnh.

| Phương án | Vì sao không đủ / vì sao chọn |
|---|---|
| Lọc bỏ dấu nháy trong dữ liệu | Dễ sót trường hợp (có nhiều cách viết dấu nháy), và làm hỏng dữ liệu hợp lệ (tên có dấu nháy) |
| Tự "thoát" ký tự đặc biệt bằng tay | Chỉ cần một chỗ quên là thủng |
| **Prepared statement** | Tách hẳn lệnh và dữ liệu, không phụ thuộc người viết có nhớ thoát hay không |

Cipher Cert dùng prepared statement cho **mọi** câu SQL có dữ liệu người dùng. Thêm một chi tiết trong `ketNoiCsdl()`:
`PDO::ATTR_EMULATE_PREPARES => false`, nghĩa là dùng prepared statement **thật** của CSDL, không để PDO tự giả lập
bằng cách ghép chuỗi.

### E3. Chỗ không dùng được dấu `?`: tên cột để sắp xếp

Danh sách chứng chỉ cho bấm tiêu đề cột để sắp xếp, địa chỉ có tham số `?sap_xep=het_han`. Câu SQL cần
`ORDER BY c.valid_to`. Nhưng dấu `?` chỉ thay được **giá trị**, không thay được **tên cột**. Ghép thẳng tham số vào câu
SQL thì lại thành SQL Injection.

Cách làm: một **danh sách trắng** viết cứng trong code, hằng `COT_SAP_XEP` ở
[src/chung_chi.php](../src/chung_chi.php):

```php
const COT_SAP_XEP = [
    'ma' => 'c.cert_code',
    'het_han' => 'c.valid_to',
    ...
];
```

Tham số trên địa chỉ chỉ dùng để **tra** trong danh sách này. Có trong danh sách thì lấy tên cột viết sẵn; không có thì
về cách xếp mặc định. Giá trị người dùng gửi lên không bao giờ được ghép vào câu SQL.

### E4. Tìm kiếm và ký tự đại diện

Tìm "chứa chữ portal" trong SQL dùng `LIKE '%portal%'`, trong đó `%` nghĩa là "bất kỳ chuỗi nào", `_` nghĩa là "bất
kỳ một ký tự nào". Người dùng gõ `_` để tìm tên có dấu gạch dưới thì lại khớp **mọi** chứng chỉ. Nên `dieuKienLoc()`
thoát các ký tự `%`, `_` trước khi tìm, để chúng được hiểu đúng là chữ.

Một chi tiết nữa: fingerprint lưu ở dạng đã chuẩn hoá (`ee7a...`), nên người dùng dán `EE:7A` vào ô tìm kiếm thì từ
khoá cũng được chuẩn hoá theo cùng cách trước khi tìm.

### E5. Chế độ nghiêm ngặt

MariaDB trong XAMPP mặc định **không nghiêm ngặt**: lưu chuỗi dài hơn cột cho phép thì nó **lặng lẽ cắt bớt** thay vì
báo lỗi. Dữ liệu hỏng mà không ai biết. `ketNoiCsdl()` bật chế độ nghiêm ngặt (`STRICT_ALL_TABLES`) cho mọi kết nối:
dữ liệu sai thì CSDL từ chối và báo lỗi rõ ràng.

### E6. Múi giờ: lệch một chút là sai cả ngày

Số ngày còn lại tính bằng "hạn trừ bây giờ". Nhưng "bây giờ" là mấy giờ, theo múi giờ nào? Đo ngày 06/11/2026:

- PHP trong XAMPP mặc định dùng giờ **Đức** (`Europe/Berlin`), chậm hơn Việt Nam 6 tiếng.
- MariaDB trong XAMPP chưa có bảng tên múi giờ, nên không hiểu chữ `Asia/Ho_Chi_Minh`.

Hệ quả: lúc 3 giờ sáng ở Việt Nam, PHP vẫn nghĩ là hôm qua, số ngày còn lại lệch một ngày, chứng chỉ hết hạn hôm nay
vẫn hiện "còn hiệu lực". Cách sửa nằm trong code, không dựa cài đặt của máy:

```php
date_default_timezone_set('Asia/Ho_Chi_Minh');   // PHP
$pdo->exec("SET time_zone = '+07:00'");          // CSDL: dùng độ lệch thay vì tên
```

Vì sao đặt trong code chứ không sửa file cấu hình của máy? Vì code chạy ở nhiều máy (máy bạn, máy giảng viên, máy chủ
thật), mỗi máy cấu hình một kiểu. Đặt trong code thì máy nào cũng đúng.

### Tự kiểm tra phần E

<details><summary>1. Giải thích SQL Injection cho một người không biết lập trình.</summary>

Hệ thống ghép chữ người dùng gõ vào một câu lệnh gửi cho CSDL. Kẻ xấu gõ chữ có dạng của lệnh, CSDL hiểu nhầm phần đó
là lệnh và làm theo, ví dụ trả về toàn bộ dữ liệu. Prepared statement gửi câu lệnh và dữ liệu riêng, nên dữ liệu không
bao giờ bị hiểu là lệnh.
</details>

<details><summary>2. Vì sao tên cột sắp xếp phải dùng danh sách trắng?</summary>

Dấu `?` của prepared statement không thay được tên cột. Muốn an toàn thì chỉ cho phép các tên cột viết sẵn trong code;
giá trị người dùng chỉ dùng để chọn trong danh sách, không bao giờ được ghép vào câu SQL.
</details>

<details><summary>3. Vì sao múi giờ đặt trong code mà không sửa cấu hình máy?</summary>

Code chạy trên nhiều máy với cấu hình khác nhau (XAMPP mặc định giờ Đức). Đặt trong code thì ở máy nào số ngày còn lại
cũng đúng, không phụ thuộc người cài có nhớ sửa cấu hình hay không.
</details>

---

## Phần F. Đăng nhập, phiên và bảo mật

### F1. HTTP không nhớ gì: cookie và phiên

Như B1 đã nói, mỗi yêu cầu HTTP độc lập. Bạn đăng nhập xong rồi mở trang Dashboard: với máy chủ, đó là một yêu cầu mới
toanh, nó không biết bạn vừa đăng nhập. Cần cách để trình duyệt "nhắc" máy chủ mình là ai ở mỗi yêu cầu.

**Cookie** là mẩu dữ liệu nhỏ máy chủ nhờ trình duyệt cất, rồi trình duyệt tự gửi kèm mọi yêu cầu sau tới cùng web đó.

Vậy cất gì vào cookie?

| Phương án | Vấn đề |
|---|---|
| Cất `user_id=1` vào cookie | Cookie nằm trên máy người dùng, sửa được. Đổi thành `user_id=2` là thành người khác |
| Cất tên và mật khẩu vào cookie | Mật khẩu đi theo mọi yêu cầu, nằm trên máy, dễ lộ |
| **Phiên (session):** cookie chỉ chứa một mã ngẫu nhiên dài; thông tin thật (`user_id`) cất ở **máy chủ**, tra theo mã đó | Mã ngẫu nhiên dài không đoán được; muốn giả phải đánh cắp đúng mã |

Cipher Cert dùng phiên của PHP. Cookie tên `cipher_sid` chỉ chứa mã phiên. Đăng nhập đúng thì máy chủ ghi
`$_SESSION['user_id'] = 1` vào phiên.

### F2. Bảo vệ cookie phiên

Ai có mã phiên là đăng nhập được như chủ nó. Nên `batDauPhien()` ở [src/auth.php](../src/auth.php) bật thêm:

| Cài đặt | Chặn được gì |
|---|---|
| `HttpOnly` | JavaScript không đọc được cookie. Lỡ có lỗi XSS (C7) thì mã độc cũng không lấy được mã phiên |
| `SameSite=Lax` | Trình duyệt không gửi cookie kèm yêu cầu POST đến từ trang web khác (chống CSRF, xem F7) |
| `Secure` (khi chạy HTTPS) | Cookie chỉ đi qua kết nối mã hoá, không lộ trên wifi công cộng |
| `use_strict_mode` | Từ chối mã phiên do trình duyệt tự bịa ra |

**Đổi mã phiên ngay sau khi đăng nhập** (`session_regenerate_id` trong `ghiNhanDangNhap()`). Vì sao? Kẻ xấu có thể cài
sẵn một mã phiên vào trình duyệt nạn nhân (ví dụ qua máy dùng chung) trước khi nạn nhân đăng nhập. Nếu mã không đổi,
nạn nhân đăng nhập xong thì kẻ xấu dùng đúng mã đó là vào được. Kiểu tấn công này gọi là *session fixation* (cố định
phiên). Đổi mã sau khi đăng nhập thì mã cũ vô dụng.

### F3. Lưu mật khẩu: băm chậm, không mã hoá

CSDL có thể bị lộ (sao lưu để nhầm chỗ, máy chủ bị xâm nhập). Khi đó mật khẩu không được đọc ra được, nhất là vì người
dùng hay dùng chung một mật khẩu cho nhiều nơi.

| Phương án | Vấn đề |
|---|---|
| Lưu mật khẩu nguyên văn | Lộ CSDL là lộ hết mật khẩu |
| Mã hoá (có khoá giải mã) | Khoá giải mã thường nằm cùng máy chủ; lộ máy chủ là giải được hết |
| Băm bằng MD5, SHA-256 | Hàm băm một chiều, không giải ngược được. Nhưng chúng được thiết kế để **nhanh**: máy tính thường thử được hàng tỷ mật khẩu mỗi giây, mật khẩu ngắn bị dò ra trong vài phút. Hai người cùng mật khẩu ra cùng mã băm |
| **`password_hash()` (thuật toán bcrypt)** | Cố tình **chậm** (mất vài chục mili giây mỗi lần): người dùng thật không thấy, kẻ dò thì chậm đi hàng triệu lần. Tự thêm **muối** (chuỗi ngẫu nhiên) cho mỗi mật khẩu, nên hai người cùng mật khẩu vẫn ra mã băm khác nhau |

Khi đăng nhập, `password_verify()` băm lại mật khẩu vừa gõ với cùng muối rồi so. Không ai, kể cả Admin, đọc được mật
khẩu của người khác. Vì vậy hệ thống không có "gửi lại mật khẩu": Admin chỉ đặt được mật khẩu **mới** cho người quên.

*Vì sao mật khẩu tối đa 72?* bcrypt chỉ dùng 72 **byte** đầu của mật khẩu, phần sau bị bỏ qua mà không báo gì. Cho
phép dài hơn thì người dùng tưởng mật khẩu 100 ký tự của mình mạnh, thật ra chỉ phần đầu có tác dụng. Chặn ở 72 byte
cho trung thực. Chú ý giới hạn tính theo byte: chữ không dấu là 1 byte, chữ có dấu tiếng Việt tốn 2–3 byte, nên mật
khẩu có dấu được ít ký tự hơn (câu báo lỗi ghi rõ "tối đa 72 ký tự không dấu").

### F4. Chặn dò mật khẩu

Kẻ xấu viết chương trình thử hàng nghìn mật khẩu cho tài khoản `admin`. Cách chặn trong `dangNhap()` và
`ghiNhanSaiMatKhau()`:

**Sai 5 lần thì tạm chặn tài khoản 15 phút.** Người dùng thật gõ nhầm 1–2 lần không sao; kẻ dò chỉ thử được 5 mật khẩu
mỗi 15 phút, tức khoảng 480 mật khẩu mỗi ngày thay vì hàng triệu. Đăng nhập đúng thì đếm lại từ 0, để những lần gõ nhầm
rải rác không dồn lại thành bị chặn.

*Vì sao chặn theo tài khoản, không theo địa chỉ IP?* Web trên máy chủ thật đứng sau một lớp chuyển tiếp, có lúc mọi
khách đều hiện cùng một IP. Chặn theo IP thì chặn nhầm cả người dùng thật. Kẻ dò cũng dễ đổi IP. Chặn theo tài khoản
thì đổi IP bao nhiêu cũng vô ích.

**Mọi kiểu sai đều báo cùng một câu:** "Sai tên đăng nhập hoặc mật khẩu...". Nếu báo "tên không tồn tại" và "sai mật
khẩu" khác nhau, kẻ dò biết ngay tên nào có thật để tập trung dò. Đang bị tạm chặn mà gõ đúng mật khẩu cũng nhận cùng
câu đó, vì nếu báo khác thì kẻ dò biết vừa thử trúng.

**Mã băm giả.** Khi tên đăng nhập không tồn tại, hệ thống vẫn chạy `password_verify()` trên một mã băm giả (hằng
`MA_BAM_GIA`). Vì sao? `password_verify` tốn vài chục mili giây. Nếu tên không tồn tại mà trả lời ngay, kẻ dò đo **thời
gian trả lời** là biết tên nào có thật, dù câu báo lỗi giống nhau.

### F5. Bị khoá giữa chừng thì sao

Admin khoá một tài khoản đang đăng nhập. Nếu hệ thống chỉ kiểm lúc đăng nhập thì người đó vẫn dùng tiếp tới khi tự
đăng xuất. Nên `nguoiDungHienTai()` **đọc lại người dùng từ CSDL ở mỗi yêu cầu**, kèm điều kiện "chưa bị khoá". Bị khoá
thì ngay yêu cầu tiếp theo bị đẩy về trang đăng nhập.

Hệ thống có hai kiểu "không vào được", đừng nhầm:

| | Tạm chặn | Khoá |
|---|---|---|
| Do ai | Hệ thống tự làm khi sai 5 lần | Admin bấm Khoá |
| Bao lâu | 15 phút, tự hết | Tới khi Admin mở khoá |
| Cột trong CSDL | `locked_until` | `is_locked` |

### F6. Phân quyền: kiểm ở máy chủ, ở mọi yêu cầu

Hai vai trò: **Admin** làm được mọi thứ; **Nhân viên quản trị** không quản lý người dùng, không xem nhật ký, không xoá
chứng chỉ (chỉ thu hồi được).

Nguyên tắc B7: ẩn nút trên giao diện **không phải** là phân quyền. Nên:

- Đầu mọi trang quản trị gọi `yeuCauDangNhap()` (cần đăng nhập) hoặc `yeuCauAdmin()` (cần là Admin). Không đủ quyền thì
  nhận lỗi 403.
- Với thao tác nhạy cảm, quyền kiểm **ngay trong hàm nghiệp vụ**: `xoaChungChi()` tự kiểm người gọi có phải Admin
  không. Nhờ vậy dù sau này có trang mới gọi hàm này mà quên kiểm quyền, hàm vẫn từ chối.
- Có test **quét mọi file** trong `public/admin/`: file nào thiếu dòng kiểm đăng nhập là test đỏ. Thêm trang mới mà
  quên thì biết ngay.

*Vì sao Nhân viên không được xoá chứng chỉ?* Xoá làm mất luôn lịch sử gia hạn và cảnh báo. Việc thường ngày của nhân
viên khi chứng chỉ không dùng nữa là **thu hồi**: chứng chỉ ngừng được theo dõi nhưng dữ liệu vẫn còn để tra lại.

*Vì sao không có trang tự đăng ký?* (QD-03) Đây là hệ thống nội bộ của một tổ chức: người lạ không có lý do tạo tài
khoản. Có trang đăng ký là mở thêm một cửa cho kẻ xấu. Admin tạo mọi tài khoản.

### F7. CSRF: mượn tay người đang đăng nhập

Bạn đang đăng nhập Cipher Cert ở một tab. Ở tab khác, bạn mở một trang web lạ. Trang đó có một form ẩn tự gửi tới
`cert.cipher.id.vn/admin/chung_chi_thao_tac.php` với lệnh "thu hồi chứng chỉ số 5". Trình duyệt gửi yêu cầu **kèm
cookie phiên của bạn**, nên máy chủ tưởng chính bạn bấm. Kiểu tấn công này gọi là **CSRF** (cross-site request forgery,
giả mạo yêu cầu từ trang khác).

Cách chặn: mỗi phiên có một **mã CSRF** ngẫu nhiên, chỉ trang của chính Cipher Cert biết, được nhúng ẩn vào mọi form.
Khi nhận POST, `yeuCauCsrf()` so mã gửi lên với mã trong phiên; sai hoặc thiếu thì từ chối (403). Trang lạ không đọc
được trang của mình nên không biết mã, form giả của nó không có mã đúng.

Có hai lớp: mã CSRF, và cookie `SameSite=Lax` (trình duyệt không gửi cookie kèm POST từ trang khác). Một lớp hỏng vẫn
còn lớp kia.

### F8. Bảng tổng hợp: mối đe doạ và cách chặn

| Mối đe doạ | Cách chặn | Ở đâu |
|---|---|---|
| SQL Injection | Prepared statement; danh sách trắng cho tên cột | Mọi hàm trong `src/`, `COT_SAP_XEP` |
| XSS (chèn mã vào trang) | Mọi dữ liệu in ra qua `e()`; cookie `HttpOnly` | `src/khoi_dong.php`, `batDauPhien()` |
| CSRF (giả mạo yêu cầu) | Mã CSRF ở mọi form; `SameSite=Lax`; đổi dữ liệu chỉ bằng POST | `yeuCauCsrf()` |
| Dò mật khẩu | Tạm chặn sau 5 lần sai; cùng câu lỗi; mã băm giả | `dangNhap()` |
| Lộ CSDL làm lộ mật khẩu | `password_hash()` (bcrypt, có muối) | `src/nguoi_dung.php` |
| Đánh cắp phiên | `HttpOnly`, `Secure`, đổi mã phiên khi đăng nhập | `batDauPhien()`, `ghiNhanDangNhap()` |
| Vượt quyền | Kiểm quyền ở mọi trang và trong hàm nghiệp vụ; test quét mọi file | `yeuCauAdmin()`, `xoaChungChi()` |
| Đọc trộm mã nguồn | Máy chủ web chỉ mở thư mục `public/` | Cấu hình máy chủ |
| Mượn máy chủ dò mạng nội bộ (SSRF) | Chặn địa chỉ nội bộ khi đọc chứng chỉ thật | Xem [H12](#h12-đọc-chứng-chỉ-thật-từ-máy-chủ-chức-năng-mở-rộng-2) |

*Vì sao làm bảo mật kỹ vậy cho một bài tập?* (QD-05) Vì web chạy **thật** trên internet, ai cũng mở được, và các chương trình
dò lỗ hổng tự động quét liên tục mọi địa chỉ trên mạng, không chọn ai. Làm cho đủ điểm thì sẽ bị phá thật.

### Tự kiểm tra phần F

<details><summary>1. Vì sao cookie chỉ chứa mã phiên, không chứa luôn id người dùng?</summary>

Cookie nằm trên máy người dùng nên sửa được: đổi id là thành người khác. Mã phiên là chuỗi ngẫu nhiên dài không đoán
được; thông tin thật nằm ở máy chủ.
</details>

<details><summary>2. Vì sao không lưu mật khẩu bằng SHA-256?</summary>

SHA-256 quá nhanh: kẻ có CSDL thử được hàng tỷ mật khẩu mỗi giây. bcrypt cố tình chậm và có muối riêng cho từng mật
khẩu, nên dò chậm đi hàng triệu lần và hai người cùng mật khẩu không cùng mã băm.
</details>

<details><summary>3. Tên đăng nhập không tồn tại, vì sao hệ thống vẫn mất công chạy password_verify?</summary>

Để thời gian trả lời giống như khi tên có thật. Nếu trả lời nhanh hơn, kẻ dò đo thời gian là biết tên nào tồn tại.
</details>

<details><summary>4. Đã ẩn nút Xoá với Nhân viên, vì sao còn kiểm quyền trong hàm xoaChungChi()?</summary>

Ẩn nút chỉ là giao diện; ai cũng gửi được yêu cầu xoá trực tiếp. Kiểm trong hàm thì mọi đường gọi tới đều bị kiểm,
kể cả trang viết sau này quên kiểm.
</details>

---

## Phần G. Cách tư duy: nhóm đã xây hệ thống theo thứ tự nào

Các phần trước là viên gạch. Phần này trả lời: **có gạch rồi, xây theo thứ tự nào?** Đây là câu hỏi quan trọng nhất
nếu sau này bạn phải tự làm một hệ thống khác.

### G0. Nguyên tắc chung

1. **Hiểu bài toán trước, công nghệ sau.** Phần A đi trước phần B là vì vậy.
2. **Dữ liệu trước giao diện.** Giao diện đổi được dễ dàng; cấu trúc dữ liệu đổi về sau rất đau (phải chuyển dữ liệu
   cũ sang).
3. **Quy tắc viết ra giấy trước khi viết code.** Viết "chứng chỉ đã thu hồi không gia hạn được" thành câu rõ ràng thì
   code chỉ là dịch câu đó. Không viết ra thì mỗi trang hiểu một kiểu.
4. **Lúc nào cũng có một bản chạy được.** Làm từng lát mỏng hoàn chỉnh (CSDL + quy tắc + trang + test của một chức
   năng), không làm cả tầng CSDL cho mọi chức năng rồi mới tới giao diện.
5. **Mỗi quyết định ghi lại lý do.** "Chọn A thay vì B vì C". Sáu tháng sau nhìn code sẽ không nhớ vì sao.

### G1. Bước 1: đọc đề, chọn đề, tách chức năng

**Chọn đề (QD-01).** Nhóm cân nhắc mấy đề: 63 (quét địa chỉ IP), 16 (quản lý kho), 64 (phân quyền RBAC), 69 (chứng
chỉ số). Chọn **69** vì gần ngành mật mã nhất, khối lượng vừa sức hơn 63 và 16, và có hướng phát triển tiếp lâu dài
(hạn chứng chỉ ngày càng ngắn, chuyển sang thuật toán mới).

**Tách chức năng.** Đề có 9 chức năng riêng và 10 yêu cầu chung, tổng **19 chức năng bắt buộc**. Nhóm đánh mã cho từng
cái (D1–D9, Na–Nj, xem [SPEC](SPEC.md)) để nói chuyện cho gọn và để kiểm tra không sót.

**Chức năng mở rộng để sau.** Nhóm có ý tưởng thêm (nhật ký, đọc chứng chỉ thật, chấm điểm an toàn), nhưng quyết định:
chỉ làm sau khi đủ 19 chức năng bắt buộc. Vì sao? Điểm chấm theo đề. Một chức năng mở rộng xịn không bù được một chức
năng bắt buộc bị thiếu. Thứ tự cắt nếu thiếu giờ cũng định trước: chấm điểm an toàn bỏ trước, rồi tới đọc chứng chỉ
thật. Kết quả: làm được 2 trong 3, chấm điểm an toàn chưa làm.

### G2. Bước 2: ai dùng hệ thống

Trả lời "ai dùng, mỗi người được làm gì" trước khi vẽ bất cứ màn hình nào. Kết quả là phần **tác nhân và phân quyền**
trong [USECASE](USECASE.md):

- Người theo dõi chứng chỉ hằng ngày → **Nhân viên quản trị**.
- Người quản lý cả hệ thống, cấp tài khoản → **Admin**.
- Không có người ngoài tự đăng ký (QD-03).

Hai vai trò là đủ. Thêm vai trò (ví dụ "chỉ xem") là thêm một cột trong mọi bảng phân quyền, thêm test, thêm chỗ có thể
sai, trong khi đề không yêu cầu.

### G3. Bước 3: thiết kế dữ liệu (ERD)

Mẹo cơ bản: **danh từ trong đề thường thành bảng, động từ thường thành chức năng.** Đọc đề và gạch chân danh từ: chứng
chỉ, tên miền, máy chủ, tổ chức cấp, lịch sử gia hạn, cảnh báo, người dùng. Cộng vai trò (từ bước 2) và nhật ký (chức
năng mở rộng), ra **9 bảng**:

| Bảng | Vì sao cần riêng |
|---|---|
| `roles` | Danh sách vai trò; người dùng trỏ tới, không gõ chữ "Admin" vào từng người |
| `users` | Tài khoản đăng nhập |
| `servers` | Máy chủ: IP, cổng, môi trường, người phụ trách. Một máy chủ chứa nhiều tên miền |
| `domains` | Tên miền, thuộc một máy chủ. Một tên miền có nhiều chứng chỉ |
| `certificate_authorities` | Tổ chức cấp. Tách ra để thống kê "theo tổ chức cấp" không bị gõ lệch (D4) |
| `certificates` | Chứng chỉ: trung tâm của hệ thống |
| `renewals` | Lịch sử gia hạn: mỗi lần gia hạn một dòng, giữ hạn, fingerprint, serial cũ |
| `alerts` | Cảnh báo đã tạo, để biết mốc nào đã cảnh báo và ai đã xem |
| `audit_logs` | Nhật ký hoạt động |

**Thiết kế ban đầu không đúng hết, và đó là bình thường.** Một ví dụ thật: bản thiết kế đầu tiên của bảng `alerts` có
khoá `UNIQUE (certificate_id, threshold_days)`, nghĩa là "mỗi chứng chỉ, mỗi mốc chỉ cảnh báo một lần". Nghe hợp lý.
Nhưng khi chuyển bản thiết kế sơ bộ thành file SQL thật, nhóm thử nghĩ tới trường hợp gia hạn và nhận ra: chứng chỉ được gia hạn thì sang chu kỳ mới, đến gần hạn mới lại phải cảnh
báo ở mốc 30 lần nữa. Khoá cũ chặn mất cảnh báo đó. Sửa thành `UNIQUE (certificate_id, valid_to, threshold_days)`: thêm
hạn vào khoá, nên mỗi **chu kỳ hạn** có bộ cảnh báo riêng. Phát hiện ra lỗi này lúc còn trên giấy thì sửa một dòng;
phát hiện sau khi có dữ liệu thật thì phải chuyển cả bảng.

**Quyết định lớn nhất ở bước này: cái gì tính, cái gì lưu.** Trạng thái và số ngày còn lại thay đổi theo thời gian nên
**tính** (view, D9). Thông tin cũ trước khi gia hạn thì không tính lại được nên **lưu** (bảng `renewals`).

### G4. Bước 4: viết đặc tả trước khi code

[SPEC](SPEC.md) ghi các quy tắc thành câu: mã quản lý tối đa 30 ký tự, ngày hết hạn phải sau ngày bắt đầu, trạng thái
xét theo thứ tự nào, cảnh báo tạo khi nào, gia hạn chặn trường hợp nào. Viết ra giấy có hai lợi ích:

- Lộ ra các trường hợp chưa nghĩ tới ("gia hạn chứng chỉ đã hết hạn có được không?", "đã thu hồi rồi thì sao?").
- Cả nhóm hiểu giống nhau; code, test và tài liệu cùng dựa trên một nguồn.

### G5. Bước 5: test viết cùng lúc với code

**Test** là đoạn code tự kiểm tra code khác: "gọi hàm này với dữ liệu này, kết quả phải là thế này". Bộ test của Cipher
Cert ở [tests/chay.php](../tests/chay.php), hiện có 89 test, chạy mất khoảng 20 giây.

Vài cách làm đáng học:

- **Test trên CSDL thật**, không giả lập. Trước mỗi test, CSDL test được nạp lại từ đầu. Nhờ vậy các ràng buộc
  `UNIQUE`, khoá ngoại, trigger, view đều được kiểm thật, và các test không ảnh hưởng nhau.
- **Mỗi test có 4 dòng chú thích:** *Luật* (quy tắc gì), *Vì sao* (lý do có quy tắc), *Đỏ nghĩa là* (test hỏng thì cái
  gì đang sai), *Đừng* (cách sửa sai hay gặp). Đọc chú thích là hiểu quy tắc mà không cần đọc code.
- **Thấy đỏ một lần.** Test mới viết phải được cho chạy hỏng một lần (khi code chưa có, hoặc cố tình làm sai) rồi mới
  viết code cho nó xanh. Nếu không, có thể test luôn xanh vì viết sai, không bắt được lỗi gì.
- **Mỗi lỗi sửa kèm đúng một test bắt lỗi đó**, để lỗi không quay lại. Ví dụ: ngày 31 thì biểu đồ 12 tháng mất tháng 2
  (trong PHP, 31/01 cộng một tháng ra 03/03); sửa xong có ngay test giả lập hôm nay là ngày 31.

Danh sách đầy đủ ở [Báo cáo kiểm thử](KIEM_THU.md).

### G6. Bước 6: thứ tự xây thật

Lịch sử commit cho thấy thứ tự nhóm đã làm. Chú ý **mỗi bước dựa trên bước trước**:

| Ngày | Làm gì | Vì sao ở vị trí này |
|---|---|---|
| 06/11 | CSDL 9 bảng, dữ liệu mẫu, ERD, cửa kết nối PDO, bộ test đầu tiên | Mọi thứ khác đọc ghi vào CSDL |
| 06/11 | Use Case và đặc tả 19 chức năng | Chốt quy tắc trước khi viết trang |
| 06/11 | Khung giao diện Tabler, đăng nhập, đăng xuất, phân quyền | Mọi trang quản trị đều cần biết ai đang dùng |
| 06/11 | Quản lý người dùng, tài khoản cá nhân, trang giới thiệu | Có tài khoản thì mới thử được phân quyền |
| 06/11 | **Đưa web lên máy chủ thật** ngay trong ngày đầu | Phát hiện sớm khác biệt giữa máy mình và máy chủ; lúc nào cũng có link chạy thật để xem |
| 06/11 | Máy chủ, tên miền, tổ chức cấp | Chứng chỉ phải trỏ tới chúng, nên phải có trước |
| 06/11 | Chứng chỉ: danh sách, chi tiết, thêm, sửa, thu hồi, xoá | Trung tâm của đề, cần danh mục đã có |
| 07/11 | Cảnh báo 30/15/7, gia hạn, tìm kiếm và lọc | Cần có chứng chỉ trước |
| 07/11 | Dữ liệu mẫu 50 chứng chỉ | Đủ dữ liệu để thấy phân trang, biểu đồ có nghĩa |
| 07/11 | Dashboard, thống kê, xuất CSV, bản in → **đủ 19 chức năng** | Tổng hợp từ mọi thứ trước đó, nên làm sau cùng |
| 07/11 | Giao diện dễ nhìn hơn, trang giới thiệu, trang đăng nhập mới | Chức năng đủ rồi mới chăm chút hình thức |
| 07/11 | Nhật ký hoạt động (mở rộng 1) | Chỉ làm sau khi đủ 19 chức năng |
| 08/11 | Giao diện thống nhất (menu dọc, tên Cipher Cert), đọc chứng chỉ thật (mở rộng 2) | Như trên |
| 08/11 | Tài liệu nộp | Chức năng đã chốt thì viết một lượt, khỏi sửa đi sửa lại |

Nhìn bảng sẽ thấy quy luật: **cái gì được nhiều thứ khác dựa vào thì làm trước**. Không thể làm cảnh báo khi chưa có
chứng chỉ, không thể có chứng chỉ khi chưa có tên miền, không thể phân quyền khi chưa có đăng nhập.

### G7. Bước 7: ghi lại quyết định

Mỗi khi chọn giữa hai cách, nhóm ghi một dòng vào sổ quyết định: **chọn gì, thay vì gì, vì sao, khi nào thì xem lại**.
Ví dụ: "Gia hạn sửa trên cùng dòng, lưu thông tin cũ ở `renewals`, thay vì mỗi lần gia hạn tạo một dòng chứng chỉ
mới, vì đơn giản và danh sách mỗi chứng chỉ chỉ một dòng; xem lại khi cần tra bản cũ như một chứng chỉ độc lập."

Toàn bộ tài liệu này dựa vào sổ đó. Mã QD-xx nhắc trong tài liệu là số dòng trong sổ.

### Tự kiểm tra phần G

<details><summary>1. Vì sao thiết kế CSDL trước khi làm giao diện?</summary>

Giao diện đổi dễ; cấu trúc dữ liệu đổi về sau thì phải chuyển cả dữ liệu cũ. Thêm nữa, mọi trang đều đọc ghi CSDL, nên
CSDL là thứ được nhiều thứ dựa vào nhất.
</details>

<details><summary>2. Kể một lỗi thiết kế được phát hiện khi còn trên giấy.</summary>

Bảng cảnh báo ban đầu có khoá "mỗi chứng chỉ, mỗi mốc một lần". Thử nghĩ tới trường hợp gia hạn mới thấy: sang chu kỳ hạn mới phải
cảnh báo lại cùng mốc. Sửa khoá thành (chứng chỉ, hạn, mốc).
</details>

<details><summary>3. Vì sao đưa web lên máy chủ thật ngay ngày đầu, khi mới có trang đăng nhập?</summary>

Để phát hiện sớm khác biệt giữa máy mình và máy chủ (phiên bản PHP, cấu hình, quyền file), khi còn ít code thì dễ sửa;
và lúc nào cũng có một link chạy thật để xem và thử trên điện thoại.
</details>

---

## Phần H. Ghép gạch: từng chức năng

Mỗi mục dưới đây theo cùng một khuôn: **vấn đề** → **các phương án** → **chọn gì, vì sao** → **code ở đâu**. Các viên
gạch ở phần B–F được nhắc lại bằng mã mục (ví dụ "D9" là bài view ở phần D). Muốn xem từng bước bấm gì trên màn hình
thì đọc [USECASE](USECASE.md); muốn biết luồng chạy qua file nào thì đọc
[README mục 5](../README.md#5-sổ-tay-mỗi-luồng-chính-chạy-qua-đâu).

### H1. Danh mục: tên miền, máy chủ, tổ chức cấp

**Vấn đề.** Chứng chỉ phải gắn với tên miền nó bảo vệ, máy chủ đặt nó, tổ chức cấp nó. Ba thứ này lặp lại ở nhiều
chứng chỉ.

**Lựa chọn.** Mỗi thứ một bảng và một trang quản lý riêng (chuẩn hoá, D4). Chứng chỉ chỉ trỏ tới bằng id.

**Các quyết định nhỏ và lý do:**

- **Tên miền lưu chữ thường.** Tên miền không phân biệt hoa thường (`CERT.vn` và `cert.vn` là một). Lưu nguyên như gõ
  thì thành hai dòng cho cùng một tên miền.
- **Cho phép dạng `*.congty.vn`** vì chứng chỉ wildcard có thật (A4).
- **IP kiểm bằng hàm có sẵn `filter_var`**, nhận cả IPv4 lẫn IPv6, tự loại giá trị vô lý như `999.1.1.1`. Tự viết biểu
  thức kiểm IP thì rất dễ sai.
- **Không xoá được khi còn dữ liệu con** (D5): máy chủ còn tên miền, tên miền hoặc tổ chức cấp còn chứng chỉ. Hệ thống
  báo rõ lý do, ví dụ "Tổ chức này còn chứng chỉ, hãy chuyển hoặc xoá chứng chỉ trước."
- **Danh sách tên miền, tổ chức cấp hiện số chứng chỉ đang gắn**, để biết trước cái nào xoá được.

Code: [src/danh_muc.php](../src/danh_muc.php) (`luuMayChu()`, `luuTenMien()`, `luuToChuc()`, các hàm xoá).

### H2. Thêm, sửa chứng chỉ

**Vấn đề.** Đây là chỗ dữ liệu đi vào hệ thống. Dữ liệu sai ở đây thì mọi thứ phía sau (trạng thái, cảnh báo, thống
kê) sai theo.

**Các quyết định và lý do:**

- **Gợi ý sẵn mã kế tiếp** (`CERT-051`): người dùng khỏi phải tra mã lớn nhất đang có. Hàm `maChungChiKeTiep()` chỉ xét
  các mã đúng dạng `CERT-số`, nên ai đó tự đặt mã kiểu `WEB-1` cũng không làm hỏng phép tính.
- **Chuẩn hoá trước khi kiểm tra và lưu**: fingerprint, serial bỏ dấu `:` và khoảng trắng (D8); ngày nhận cả dạng có
  giây và không có giây.
- **Ô trống lưu thành NULL** (D7), để nhiều chứng chỉ cùng không có fingerprint mà không bị báo trùng.
- **Kiểm tra ba lớp** (D6): JavaScript, PHP (`kiemTraChungChi()`), CSDL. Lỗi hiện **ngay dưới ô sai**, và form giữ
  nguyên những gì người dùng đã gõ. Mất hết dữ liệu chỉ vì sai một ô là trải nghiệm tệ nhất của một form.
- **Kiểm ngày cẩn thận.** PHP có thói quen tự "sửa" ngày vô lý: 31/02 thành 03/03 mà không báo gì. `chuanHoaNgay()` đổi
  ngày rồi so lại với chuỗi gốc; không khớp nghĩa là ngày vô lý, báo lỗi.

**Ô tên miền, tổ chức cấp: gõ có gợi ý, nhưng không tự tạo.** Đây là một quyết định có cân nhắc:

| Phương án | Được | Mất |
|---|---|---|
| Ô chọn (dropdown) | Chắc chắn chọn đúng | Danh sách dài thì cuộn mệt |
| Ô gõ tự do, tên mới thì tự tạo danh mục | Nhanh | Gõ nhầm một chữ là đẻ ra một tên miền rác; danh mục bẩn dần |
| **Ô gõ có gợi ý, chỉ nhận tên đã có** | Nhanh như gõ, chắc như chọn | Tên mới phải thêm vào danh mục trước (có link ngay dưới ô) |

**Sửa không ghi lịch sử, gia hạn mới ghi.** Form Sửa là để sửa chỗ **nhập sai**. Chứng chỉ được **cấp lại** thì dùng
Gia hạn (H5) để giữ lịch sử. Tách hai việc này để lịch sử gia hạn chỉ chứa những lần gia hạn thật.

**Form không có ô "thu hồi".** Hàm `luuChungChi()` cố ý không đụng cột `is_revoked`. Thu hồi chỉ đi qua một đường duy
nhất (`thuHoiChungChi()`), nên không ai "bỏ thu hồi" được bằng form Sửa.

Code: [src/chung_chi.php](../src/chung_chi.php) (`luuChungChi()`, `kiemTraChungChi()`),
[public/admin/chung_chi_form.php](../public/admin/chung_chi_form.php).

### H3. Trạng thái và số ngày còn lại

**Vấn đề.** Đề yêu cầu "tự động tính số ngày còn hiệu lực" và "theo dõi trạng thái".

**Lựa chọn:** tính trong view mỗi lần đọc, không lưu (D9). 5 trạng thái, xét **lần lượt từ trên xuống**, lấy dòng đầu
tiên đúng:

| Thứ tự | Trạng thái | Điều kiện | Vì sao đứng ở vị trí này |
|---|---|---|---|
| 1 | Đã thu hồi | Đã bấm thu hồi | Dù còn hạn cũng không được tin, nên phải thắng mọi trạng thái khác |
| 2 | Chưa hiệu lực | Chưa tới ngày bắt đầu | Chưa dùng được, dù hạn còn xa |
| 3 | Đã hết hạn | Đã qua ngày hết hạn | |
| 4 | Sắp hết hạn | Còn tối đa 30 ngày | 30 là mốc cảnh báo xa nhất của đề |
| 5 | Còn hiệu lực | Các trường hợp còn lại | |

Nếu đảo thứ tự, ví dụ xét "còn hạn" trước "đã thu hồi", thì chứng chỉ bị lộ khoá vẫn hiện "Còn hiệu lực": sai đúng ở
chỗ nguy hiểm nhất.

**Bẫy nhỏ:** chứng chỉ hết hạn lúc 8 giờ sáng nay, lúc 10 giờ có số ngày còn lại là 0 (cùng ngày) nhưng trạng thái đã
là "Đã hết hạn". Vì vậy giao diện luôn dựa vào **trạng thái** để tô màu, không tự suy từ số ngày.

### H4. Cảnh báo theo mốc 30, 15, 7 ngày

**Vấn đề.** Đề yêu cầu cảnh báo trước khi hết hạn theo ba mốc.

**Câu hỏi 1: khi nào tạo cảnh báo?**

| Phương án | Được | Mất |
|---|---|---|
| Lịch chạy nền (cron) mỗi đêm | Tạo đúng giờ, kể cả khi không ai mở web | Thêm một thứ phải cài và theo dõi trên máy chủ; chạy hỏng thì im lặng; máy giảng viên chấm bài không có cron |
| **Tạo khi có người mở Dashboard hoặc trang Cảnh báo** | Không cần gì thêm; chạy được ở mọi máy | Không ai mở web thì không có cảnh báo mới |

Chọn cách thứ hai. Lý do quyết định: cảnh báo **chỉ hiện trong web** (hệ thống không gửi email). Không ai mở web thì có
tạo sẵn cũng không ai thấy. Lúc có người mở, cảnh báo được tạo ngay trước khi hiện, nên người xem luôn thấy đủ.

**Câu hỏi 2: chạy đi chạy lại mỗi lần mở trang có sinh cảnh báo trùng không?** Không, nhờ khoá `UNIQUE (certificate_id,
valid_to, threshold_days)` của bảng `alerts`. Hàm `taoCanhBao()` là một câu `INSERT ... SELECT` lấy mọi chứng chỉ Sắp
hết hạn từ view; dòng nào đã có thì CSDL bỏ qua (`ON DUPLICATE KEY`), giữ nguyên cả trạng thái đã xem. Nhờ vậy gọi bao
nhiêu lần cũng an toàn. Tính chất "làm lại nhiều lần vẫn ra cùng kết quả" rất đáng giá: không cần lo gọi thừa.

**Câu hỏi 3: chứng chỉ thêm vào khi chỉ còn 5 ngày thì tạo mấy cảnh báo?** Chỉ **một**, ở mốc 7 (mốc gần nhất đã chạm).
Tạo cả ba mốc 30, 15, 7 cùng lúc thì người dùng nhận ba thông báo cho một việc, và hai cái đầu đã lỗi thời.

**Câu hỏi 4: gia hạn xong thì sao?** Hạn đổi, nên khoá `(chứng chỉ, hạn, mốc)` thành mới: chu kỳ mới có bộ cảnh báo
riêng (câu chuyện ở G3). Cảnh báo của chu kỳ cũ vẫn giữ trong CSDL nhưng không hiện nữa.

**Các quyết định hiển thị:**

- **Chứng chỉ đã hết hạn vẫn hiện cảnh báo**: đó là trường hợp gấp nhất. **Đã thu hồi thì không**: không còn việc gì để
  xử lý.
- **"Đã xem" tính chung cho mọi người.** Một người đánh dấu thì mọi người thấy đã xem. Làm riêng từng người thì cần thêm
  một bảng nối người dùng với cảnh báo; với nhóm quản trị nhỏ cùng xử lý một danh sách, dùng chung đơn giản hơn và
  tránh hai người cùng xử lý một việc.
- **Số cảnh báo mới nằm cạnh mục Cảnh báo trên menu**, thấy ở mọi trang. Lúc đầu con số này là một ô trên Dashboard,
  nhưng như vậy chỉ thấy khi đang ở Dashboard.

Code: `taoCanhBao()`, `danhSachCanhBao()`, `demCanhBaoMoi()`, `danhDauDaXem()` trong
[src/chung_chi.php](../src/chung_chi.php).

### H5. Gia hạn và lịch sử gia hạn

**Vấn đề.** Đề yêu cầu "quản lý lịch sử gia hạn". Gia hạn thật ngoài đời là CA cấp một chứng chỉ **mới** (fingerprint
mới, serial mới, hạn mới) để thay cái cũ.

**Câu hỏi lớn: lưu lần gia hạn thế nào?** (QD-11)

| Phương án | Được | Mất |
|---|---|---|
| Mỗi lần gia hạn thêm một dòng chứng chỉ mới | Bản cũ còn nguyên như một chứng chỉ độc lập | Danh sách đầy bản cũ; cảnh báo, thống kê phải lọc bản cũ ra; phải nối "bản này thay bản kia" |
| **Sửa trên cùng dòng, chép thông tin cũ sang bảng `renewals`** | Mỗi chứng chỉ chỉ một dòng; mã quản lý giữ nguyên; lịch sử vẫn đủ | Không tra được bản cũ như một chứng chỉ đầy đủ (chỉ có hạn, fingerprint, serial cũ) |

Chọn cách thứ hai vì đơn giản và đúng cách người quản trị nghĩ: "chứng chỉ của cổng thanh toán" là một thứ, được gia
hạn nhiều lần, không phải nhiều thứ.

**Các quyết định và lý do:**

- **Giao dịch** (D11): chép lịch sử và cập nhật chứng chỉ đi cùng nhau hoặc không cái nào.
- **Khoá dòng khi gia hạn** (`SELECT ... FOR UPDATE`): hai người cùng bấm gia hạn một chứng chỉ, người sau phải đợi người
  trước xong. Không khoá thì cả hai cùng đọc hạn cũ, cùng chép hạn cũ vào lịch sử, lịch sử sai.
- **Hạn mới phải sau hạn hiện tại và sau thời điểm này.** Gia hạn mà hạn mới sớm hơn thì vô nghĩa; hạn mới đã ở quá khứ
  thì gia hạn xong vẫn hết hạn.
- **Được gia hạn chứng chỉ đã hết hạn** (quên rồi mới gia hạn là chuyện thường), nhưng **chặn chứng chỉ đã thu hồi**:
  thu hồi nghĩa là không được tin nữa, muốn dùng lại thì thêm chứng chỉ mới.
- **Để trống fingerprint, serial mới thì lưu NULL, không giữ giá trị cũ.** Giữ giá trị cũ thì chứng chỉ mới mang vân tay
  của chứng chỉ cũ: thông tin sai. Giá trị cũ vẫn nằm trong lịch sử.
- **Gia hạn không đổi ngày bắt đầu.** Cần sửa ngày bắt đầu thì dùng form Sửa.

Code: `giaHanChungChi()`, `kiemTraGiaHan()`, `lichSuGiaHan()` trong [src/chung_chi.php](../src/chung_chi.php).

### H6. Thu hồi và xoá: vì sao cần cả hai

| | Thu hồi | Xoá |
|---|---|---|
| Ý nghĩa | Chứng chỉ không còn được tin (lộ khoá, ngừng dùng) | Nhập nhầm, muốn gỡ hẳn |
| Dữ liệu | Giữ nguyên, lịch sử còn | Mất luôn cảnh báo và lịch sử gia hạn |
| Hoàn tác | Không | Không |
| Ai làm được | Admin, Nhân viên | Chỉ Admin |

Chỉ có xoá thì mỗi lần chứng chỉ ngừng dùng là mất dấu vết. Chỉ có thu hồi thì dữ liệu nhập nhầm nằm mãi trong hệ
thống. Hai việc khác nhau nên cần hai nút. Cả hai đều **hỏi lại** trước khi làm (hộp xác nhận), vì không hoàn tác được.

### H7. Danh sách, tìm kiếm, lọc, sắp xếp

**Phân trang 20 dòng.** Hiện 5.000 dòng một lúc thì trang tải chậm và không ai đọc hết. 20 dòng vừa một màn hình.

**Mặc định xếp theo mức gấp:** đã hết hạn → sắp hết hạn → còn hiệu lực → chưa hiệu lực → đã thu hồi; trong mỗi nhóm,
hạn gần hơn đứng trước. Mở danh sách là thấy ngay việc cần làm. Xếp theo mã hay theo ngày thêm thì việc gấp có thể
nằm ở trang 7.

**Mọi cách xếp đều kết thúc bằng id.** Hai chứng chỉ cùng hạn thì CSDL có thể trả về theo thứ tự bất kỳ, mỗi lần một
kiểu. Khi phân trang, một dòng có thể hiện ở cả trang 1 lẫn trang 2, dòng khác thì biến mất. Thêm id vào cuối để thứ
tự luôn cố định.

**Hàng nút trạng thái có số đếm** ("Sắp hết hạn 13"): bấm là lọc, và biết trước sẽ ra bao nhiêu dòng.

**Bộ lọc nằm trên địa chỉ trang** (GET, B2). Lợi ích: gửi link cho đồng nghiệp là họ thấy đúng danh sách đã lọc; bấm
Xuất CSV thì báo cáo theo đúng bộ lọc đó. Giá trị lọc vô lý (do sửa tay địa chỉ) thì bị coi như chưa chọn (B7).

**Lọc "hết hạn đến ngày X" tính trọn ngày X.** Chứng chỉ hết hạn lúc 23:59 ngày X vẫn được tính, vì người dùng chọn một
**ngày**, không chọn một giờ.

Code: `docBoLoc()`, `dieuKienLoc()`, `menhDeSapXep()`, `danhSachChungChi()` trong
[src/chung_chi.php](../src/chung_chi.php).

### H8. Dashboard

**Vấn đề.** Đăng nhập vào là phải biết ngay tình hình và việc cần làm.

- **6 thẻ số:** tổng số và số chứng chỉ ở từng trạng thái. Bấm thẻ là mở danh sách đã lọc theo trạng thái đó.
- **Bảng "Cần xử lý ngay":** tối đa 10 chứng chỉ **sắp hết hạn** gần nhất, mỗi dòng có nút Gia hạn.
- **2 biểu đồ nhỏ:** theo trạng thái, và số chứng chỉ hết hạn trong 12 tháng tới.

*Vì sao bảng "Cần xử lý ngay" không gồm chứng chỉ đã hết hạn?* Chứng chỉ đã quá hạn từ lâu (có thể không ai dùng nữa)
sẽ chiếm hết 10 dòng, che mất những chứng chỉ còn cứu được trước khi hết hạn. Số chứng chỉ đã hết hạn vẫn hiện ở thẻ
số, bấm vào là xem được.

### H9. Thống kê

**4 biểu đồ, mỗi cái trả lời một câu hỏi khác nhau:**

| Biểu đồ | Câu hỏi nó trả lời |
|---|---|
| Theo trạng thái | Tình hình chung: bao nhiêu cái ổn, bao nhiêu cái có vấn đề? (đề yêu cầu "thống kê theo trạng thái") |
| Hết hạn trong 12 tháng tới | Tháng nào sẽ bận gia hạn? Bỏ qua chứng chỉ đã thu hồi và đã quá hạn, vì đây là **dự báo** việc sắp tới |
| Theo tổ chức cấp | Phụ thuộc vào CA nào nhiều nhất? (một CA gặp sự cố thì ảnh hưởng bao nhiêu chứng chỉ) |
| Theo thuật toán khoá | Còn bao nhiêu chứng chỉ dùng thuật toán cũ cần thay? |

**Mỗi biểu đồ có bảng số ngay dưới.** Biểu đồ cho thấy hình dạng nhưng không đọc được số chính xác; người dùng màn hình
đọc chữ (người khiếm thị) không xem được hình; bản in không có rê chuột. Bảng số giải cả ba.

**Dùng chung bộ lọc với danh sách**, để "thống kê các chứng chỉ của máy chủ web-01" chỉ là chọn bộ lọc.

**Trục số giữ nguyên khi lọc.** Lúc đầu, lọc xong thì trục co theo số liệu mới: cột dài nhất lúc nào cũng chạm đỉnh khung,
không so được với lúc chưa lọc. Bây giờ trần trục tính trên **toàn bộ** chứng chỉ (`tranBieuDoThongKe()`); lọc thì cột
ngắn lại trên cùng một thước đo.

### H10. Xuất báo cáo: CSV và bản in

**Vì sao CSV mà không phải file Excel (.xlsx)?** Tạo file Excel cần thêm thư viện. CSV là văn bản thuần, mỗi dòng một
chứng chỉ, các ô cách nhau bằng dấu phẩy; PHP có sẵn hàm ghi; Excel và Google Sheets đều mở được.

Ba chi tiết nhỏ nhưng quan trọng:

1. **Dấu BOM ở đầu file.** Thiếu nó, Excel đoán sai bảng mã và tiếng Việt hiện thành chữ lỗi.
2. **Ô bắt đầu bằng `=`, `+`, `-`, `@` được thêm dấu nháy phía trước.** Excel coi các ô đó là **công thức** và chạy
   chúng. Kẻ xấu đặt tên chứng chỉ là một công thức độc hại thì ai mở báo cáo bằng Excel là dính. Kiểu tấn công này
   gọi là *CSV injection*. Dấu nháy làm Excel coi ô đó là chữ.
3. **Ngày ghi dạng năm-tháng-ngày (`2027-02-04`), không kèm giờ.** Dạng `04/02/2027` bị máy cài định dạng Mỹ đọc thành
   ngày 2 tháng 4. Kèm giờ thì ô dài hơn cột, Excel hiện `#####`. Lỗi này nhóm gặp thật khi thử và đã sửa (có test).

**Vì sao "bản in" mà không tạo file PDF?** Tạo PDF cần thêm thư viện. Mọi trình duyệt đã có sẵn lệnh In → Lưu thành PDF.
Nên hệ thống chỉ cần một trang được trình bày cho đẹp khi in (bảng số, kèm mô tả bộ lọc đã chọn), người dùng tự in hoặc
lưu PDF.

**Báo cáo lấy đủ mọi dòng khớp bộ lọc, không chia trang** (`tatCaChungChi()`). Báo cáo chỉ có trang 1 là báo cáo sai.

### H11. Nhật ký hoạt động (chức năng mở rộng 1)

**Vì sao thêm chức năng này, dù đề không yêu cầu?** Một ngày chứng chỉ quan trọng biến mất khỏi hệ thống. Ai xoá? Lúc
nào? Không có nhật ký thì không trả lời được. Với hệ thống quản lý thứ liên quan an ninh, khả năng **truy vết** là cơ
bản. Ý tưởng lấy từ đề 65 (quản lý nhật ký).

**Các quyết định và lý do:**

- **Ghi ở đâu?** Ngay trong các hàm nghiệp vụ trong `src/` (sau khi thao tác thành công), không phải ở từng trang. Nếu
  ghi ở trang, thêm một trang mới mà quên ghi là có lỗ hổng. Ghi trong hàm thì trang nào gọi hàm cũng được ghi.
- **Gia hạn ghi nhật ký trong cùng giao dịch:** gia hạn lỗi thì nhật ký cũng không có dòng "đã gia hạn".
- **Ghi tên đối tượng lúc đó** (ví dụ mã chứng chỉ), vì đối tượng có thể bị đổi tên hoặc xoá sau này. Nhật ký phải đọc
  được mãi.
- **Cột mã đối tượng cố ý không có khoá ngoại.** Có khoá ngoại thì hoặc không xoá được chứng chỉ (vì nhật ký còn trỏ
  tới), hoặc xoá chứng chỉ thì nhật ký bị xoá theo. Cả hai đều sai.
- **Ngược lại, cột người làm có khoá ngoại kiểu chặn.** Hệ quả cố ý: người đã có dòng nhật ký (kể cả chỉ đăng nhập)
  không xoá được nữa, chỉ khoá được. Dấu vết luôn còn người làm.
- **Không ai sửa, xoá được nhật ký.** Web không có nút sửa xoá, và CSDL có 2 trigger từ chối mọi lệnh sửa, xoá dòng
  (D10), kể cả lệnh gõ thẳng vào CSDL. Nói rõ giới hạn: người có toàn quyền CSDL vẫn dọn được cả bảng bằng lệnh
  `TRUNCATE` hoặc gỡ trigger; nhưng bảng trống trơn thì nhìn là biết đã bị dọn.
- **Ghi cả đăng nhập sai**, với người làm để trống (chưa biết là ai): nhiều dòng đăng nhập sai liên tiếp là dấu hiệu
  đang bị dò mật khẩu.
- **Ghi địa chỉ IP lấy từ máy chủ web**, không đọc header `X-Forwarded-For` mà khách tự gõ được để giả IP.
- **Chỉ Admin xem được.** Nhật ký chứa hoạt động của mọi người, kể cả IP.

Code: `ghiNhatKy()` trong [src/nhat_ky.php](../src/nhat_ky.php); trigger trong
[database/schema.sql](../database/schema.sql).

### H12. Đọc chứng chỉ thật từ máy chủ (chức năng mở rộng 2)

**Vì sao thêm chức năng này?** Nhập tay một chứng chỉ là gõ hơn chục ô, trong đó fingerprint dài 64 ký tự và serial dài
hàng chục ký tự. Gõ sai một ký tự là dữ liệu sai mà không ai biết. Mà chứng chỉ đang chạy trên máy chủ thì máy chủ đưa
nó cho bất kỳ ai kết nối tới (A3). Vậy để hệ thống tự kết nối và đọc.

**Cách hoạt động:** người dùng gõ tên miền (ví dụ `cert.cipher.id.vn`), chọn cổng, bấm Đọc. Hệ thống kết nối TLS tới máy
chủ đó, nhận chứng chỉ, tách từng thông tin ra điền vào form. Người dùng kiểm lại rồi bấm Lưu như bình thường; mọi quy
tắc kiểm tra ở H2 vẫn áp dụng.

**Nguy hiểm lớn nhất: SSRF.** Chức năng này cho phép người dùng bảo **máy chủ của mình** kết nối tới một địa chỉ do họ
chọn. Máy chủ web thường chạy chung với các dịch vụ nội bộ chỉ nghe ở địa chỉ `127.0.0.1` (chính máy đó) hoặc trong mạng
nội bộ, và thường không đặt mật khẩu vì nghĩ "người ngoài không với tới được". Nếu cho đọc chứng chỉ ở `127.0.0.1:cổng
bất kỳ`, người dùng mượn được máy chủ để dò xem bên trong có gì. Kiểu tấn công này gọi là **SSRF** (server-side request
forgery, mượn máy chủ gửi yêu cầu).

**Các lớp chặn và lý do:**

| Lớp chặn | Vì sao cần |
|---|---|
| **Chỉ cho các cổng TLS phổ biến** (443, 8443, 465, 993...) | Cho cổng tuỳ ý thì chức năng thành công cụ quét cổng |
| **Tra DNS lấy mọi IP của tên miền, chặn nếu bất kỳ IP nào là nội bộ** (127.x, 10.x, 192.168.x, kể cả IPv4 giấu trong IPv6) | Kẻ xấu tạo tên miền trỏ tới `127.0.0.1`; kiểm tên miền thôi thì không thấy |
| **Kết nối tới đúng IP vừa kiểm**, không tra DNS lần nữa | Kẻ xấu có thể đổi bản ghi DNS sang `127.0.0.1` ngay giữa lúc kiểm và lúc kết nối (gọi là *DNS rebinding*) |
| **Chờ tối đa 5 giây** | Máy chủ không trả lời thì không treo trang mãi |
| **Ghi nhật ký mọi lần đọc**, đọc được hay không | Biết ai đã cho máy chủ kết nối đi đâu |

**Cố ý không kiểm chứng chỉ có hợp lệ không.** Trình duyệt từ chối chứng chỉ hết hạn, tự ký, sai tên. Nhưng chức năng
này **phải** đọc được cả những chứng chỉ đó, vì chúng chính là thứ cần đưa vào theo dõi.

**Tự chọn tên miền và tổ chức cấp nếu trùng tên, nhưng không tự tạo.** Cùng lý do với H2: tự tạo là đẻ ra danh mục rác.
Chưa có thì để trống, kèm gợi ý thêm vào danh mục. Tên miền cha cũng được nhận: đọc `cert.cipher.id.vn` mà danh mục có
`cipher.id.vn` thì tự chọn `cipher.id.vn`.

**Báo trùng ngay.** Chứng chỉ vừa đọc đã có trong hệ thống (cùng fingerprint) thì báo trước, kèm link, khỏi bấm Lưu rồi
mới bị từ chối.

**Không cần JavaScript.** Nút Đọc gửi form về chính trang đó, máy chủ đọc rồi trả form đã điền. Trình duyệt tắt
JavaScript vẫn dùng được.

Code: chuỗi hàm `phanTichDiaChi()` → `phanGiaiDiaChi()` → `kiemTraDiaChiDoc()` → `docChungChiTuMayChu()` →
`giaTriTuChungChi()`, gộp lại trong `docChungChiChoForm()`, tất cả ở [src/chung_chi.php](../src/chung_chi.php).

### H13. Giao diện

- **Khu quản trị ưu tiên dễ nhìn, mặc định sáng; phần đẹp dồn vào trang giới thiệu** (QD-21). Khu quản trị là nơi dùng
  hằng ngày, cần đọc nhanh, ít mỏi mắt. Trang giới thiệu là nơi gây ấn tượng lần đầu.
- **Menu dọc chia 4 nhóm** (Tổng quan, Chứng chỉ, Hạ tầng, Hệ thống). Mười mấy mục xếp một hàng thì khó tìm; chia nhóm
  theo việc thì nhìn là biết mục nằm đâu. Nhóm Hệ thống (Người dùng, Nhật ký) chỉ hiện với Admin.
- **Tên ngắn "Cipher Cert"** trên logo, menu, tab trình duyệt; tên đề tài đầy đủ ở trang giới thiệu, trang đăng nhập và
  báo cáo (QD-24). Tên đầy đủ quá dài cho một tab trình duyệt.
- **Sáng và tối**, nhớ lựa chọn khi chuyển trang và mở lại trình duyệt.
- **Dùng được trên điện thoại** (khổ 390px như iPhone): bảng ẩn bớt cột phụ thay vì bắt kéo ngang, menu thu vào một nút.
  Quản trị viên hay xem cảnh báo trên điện thoại.
- **Trang giới thiệu** cho người lần đầu vào: hệ thống làm gì, có nút Đăng nhập. Đăng xuất xong cũng về đây.
- **Hiệu ứng chuyển động viết bằng CSS và JavaScript thuần**, không dùng thư viện hiệu ứng (QD-23), chỉ ở trang giới
  thiệu và trang đăng nhập. Máy bật chế độ "giảm chuyển động" thì hiệu ứng đứng yên.
- **Font Inter tải về cùng web** (QD-22): dễ đọc với chữ nhỏ và tiếng Việt, không phụ thuộc mạng.

### Tự kiểm tra phần H

<details><summary>1. Vì sao cảnh báo được tạo khi có người mở trang, không dùng lịch chạy nền?</summary>

Cảnh báo chỉ hiện trong web, không gửi email; không ai mở web thì tạo sẵn cũng không ai thấy. Tạo lúc mở trang thì
không cần cài thêm gì, chạy được ở mọi máy, và khoá UNIQUE bảo đảm chạy lại không sinh trùng.
</details>

<details><summary>2. Vì sao gia hạn sửa trên cùng dòng chứng chỉ thay vì thêm dòng mới?</summary>

Để mỗi chứng chỉ chỉ có một dòng, mã quản lý giữ nguyên, danh sách và thống kê không lẫn bản cũ. Thông tin bản cũ cần
giữ (hạn, fingerprint, serial) được chép sang bảng lịch sử trước khi sửa, trong cùng một giao dịch.
</details>

<details><summary>3. Vì sao bảng "Cần xử lý ngay" trên Dashboard bỏ qua chứng chỉ đã hết hạn?</summary>

Chứng chỉ quá hạn lâu sẽ chiếm hết chỗ, che mất những chứng chỉ sắp hết hạn còn cứu được. Số chứng chỉ đã hết hạn vẫn
hiện ở thẻ số.
</details>

<details><summary>4. Chức năng đọc chứng chỉ thật nguy hiểm ở đâu, chặn bằng gì?</summary>

Nó cho người dùng bảo máy chủ kết nối tới địa chỉ tuỳ ý, có thể dùng để dò dịch vụ nội bộ (SSRF). Chặn bằng: chỉ cổng
TLS trong danh sách; tra mọi IP của tên miền, chặn nếu bất kỳ IP nào nội bộ; kết nối đúng IP đã kiểm (chống DNS
rebinding); giới hạn 5 giây; ghi nhật ký.
</details>

<details><summary>5. Vì sao cột mã đối tượng trong nhật ký không có khoá ngoại, còn cột người làm thì có?</summary>

Mã đối tượng không có khoá ngoại để xoá chứng chỉ vẫn giữ được dòng nhật ký về nó. Người làm có khoá ngoại kiểu chặn
để không xoá được người đã có hoạt động: dấu vết luôn còn tên người làm.
</details>

---

## Phần I. Bức tranh toàn cảnh

### I1. Toàn bộ hệ thống trong một hình

```
                         NGƯỜI DÙNG (máy tính hoặc điện thoại)
                                       │
                 ┌─────────────────────┴─────────────────────┐
                 │ Trình duyệt                               │
                 │  HTML: nội dung   CSS (Tabler): hình thức │
                 │  JavaScript (app.js): kiểm form nhanh,    │
                 │  hỏi lại trước khi xoá, vẽ biểu đồ        │
                 │  (Chart.js), đổi sáng/tối                 │
                 │  Cookie: chỉ giữ mã phiên cipher_sid      │
                 └─────────────────────┬─────────────────────┘
                                       │ HTTPS (TLS, chứng chỉ Let's Encrypt)
                                       │ yêu cầu GET / POST, trả lời HTML
                 ┌─────────────────────┴─────────────────────┐
                 │ Máy chủ web (Caddy trên máy chủ thật,     │
                 │ php -S ở máy mình). Chỉ mở thư mục public/│
                 └─────────────────────┬─────────────────────┘
                                       │ chạy file PHP
                 ┌─────────────────────┴─────────────────────┐
                 │ PHP                                       │
                 │  public/*.php   trang: nhận yêu cầu, gọi  │
                 │                 hàm, in HTML              │
                 │  src/khoi_dong  phiên, e(), url(), form   │
                 │  src/auth       đăng nhập, quyền, CSRF    │
                 │  src/chung_chi  chứng chỉ, cảnh báo, gia  │
                 │                 hạn, thống kê, đọc cert   │
                 │  src/danh_muc   máy chủ, tên miền, CA     │
                 │  src/nguoi_dung người dùng, mật khẩu      │
                 │  src/nhat_ky    ghi và đọc nhật ký        │
                 │  views/         đầu trang, menu, bộ lọc   │
                 └─────────────────────┬─────────────────────┘
                                       │ PDO, prepared statement
                                       │ một cửa: config/db.php
                 ┌─────────────────────┴─────────────────────┐
                 │ MariaDB                                   │
                 │  9 bảng, khoá chính, khoá ngoại           │
                 │  UNIQUE, CHECK: lớp chặn cuối             │
                 │  view v_certificates: trạng thái, số ngày │
                 │  2 trigger: chặn sửa, xoá nhật ký         │
                 └───────────────────────────────────────────┘

      Riêng chức năng đọc chứng chỉ thật: PHP ──TLS──► máy chủ bên ngoài (chỉ địa chỉ công khai)
```

### I2. Một yêu cầu đi trọn vòng: gia hạn một chứng chỉ

Theo dõi một thao tác duy nhất từ đầu đến cuối. Mỗi bước ghi mã bài học liên quan; nếu bước nào bạn chưa hiểu, quay lại
đúng bài đó.

1. **Trình duyệt mở form gia hạn** `chung_chi_gia_han.php?id=10` bằng GET (B1, B2). Cookie `cipher_sid` tự đi kèm (F1).
2. **Máy chủ web** nhận yêu cầu, thấy file nằm trong `public/` thì giao cho PHP (C5).
3. **PHP nạp `khoi_dong.php`**: mở phiên theo mã trong cookie (F1, F2).
4. **`yeuCauDangNhap()`** đọc lại người dùng từ CSDL; chưa đăng nhập hoặc đã bị khoá thì chuyển về trang đăng nhập (F5,
   F6).
5. **PHP in form**, nhúng ẩn mã CSRF, mọi dữ liệu in ra qua `e()` (C7, F7). Trình duyệt hiện form.
6. **Người dùng nhập hạn mới, bấm Gia hạn.** JavaScript kiểm nhanh ô ngày (B4, B7). Trình duyệt gửi POST kèm mã CSRF.
7. **`yeuCauCsrf()`** so mã CSRF; sai thì 403 (F7).
8. **`giaHanChungChi()`** chuẩn hoá dữ liệu (D8), mở giao dịch, khoá dòng chứng chỉ bằng `SELECT ... FOR UPDATE` (D11,
   H5). Mọi câu SQL dùng prepared statement (E2).
9. **`kiemTraGiaHan()`**: đã thu hồi? hạn mới có sau hạn cũ và sau bây giờ (theo giờ Việt Nam, E6)? Sai thì quay lại,
   trả lỗi theo ô.
10. **Chép hạn, fingerprint, serial cũ** vào `renewals`; **cập nhật** chứng chỉ; **ghi nhật ký** "Gia hạn" (H11); rồi
    `COMMIT`. CSDL kiểm `CHECK (new_valid_to > old_valid_to)` và `UNIQUE` fingerprint lần cuối (D6).
11. **Lưu thông báo, chuyển trang** về chi tiết chứng chỉ (C6). Trình duyệt mở trang chi tiết bằng GET.
12. **Trang chi tiết đọc từ view** `v_certificates`: hạn mới, trạng thái mới, số ngày còn lại mới, tính ngay lúc đọc
    (D9). Bảng lịch sử có thêm một dòng.
13. **Lần tới có người mở Dashboard**, `taoCanhBao()` thấy hạn đã đổi, chu kỳ mới bắt đầu: khi tới gần hạn mới, mốc 30
    sẽ được cảnh báo lại (H4).

Nếu kể lại được 13 bước này bằng lời của mình, bạn đã hiểu gần hết hệ thống.

### I3. Ở máy mình và trên máy chủ thật

| | Ở máy mình (XAMPP) | Trên máy chủ thật (cert.cipher.id.vn) |
|---|---|---|
| Hệ điều hành | Windows | Ubuntu (Linux) |
| Máy chủ web | `php -S` có sẵn trong PHP, đủ dùng cho một người | Caddy: chạy liên tục, tự xin và tự gia hạn chứng chỉ Let's Encrypt cho chính web này |
| Chạy PHP | Cùng tiến trình với `php -S` | PHP-FPM: một dịch vụ riêng, chạy nhiều yêu cầu cùng lúc |
| CSDL | MariaDB 10.4 trong XAMPP | MariaDB, giới hạn bộ nhớ vì máy chủ dùng chung |
| Cấu hình CSDL | Mặc định trong `config/db.php` | File `config/config.local.php` riêng, không đưa lên git |
| Cập nhật code | Sửa trực tiếp | Đẩy lên GitHub, máy chủ kéo bản mới về |

Cùng một mã nguồn chạy ở cả hai nơi mà không sửa gì, nhờ: mỗi trang một file (C3), múi giờ đặt trong code (E6), cấu
hình riêng tách ra file không đưa lên git (E1).

Có một điểm thú vị: chính web quản lý chứng chỉ này cũng có chứng chỉ, do Let's Encrypt cấp, hạn 90 ngày, được Caddy tự
gia hạn. Nhóm thêm nó vào dữ liệu mẫu như một chứng chỉ thật cần theo dõi.

### I4. Những gì hệ thống cố ý KHÔNG làm, và vì sao

Biết vì sao **không** làm cũng quan trọng như biết vì sao làm.

| Không làm | Vì sao |
|---|---|
| Gửi cảnh báo qua email | Cần máy chủ thư và cấu hình riêng, dễ bị đánh dấu thư rác; đề chỉ yêu cầu cảnh báo, không yêu cầu kênh. Hướng phát triển sau này |
| Tự động gia hạn chứng chỉ (giao thức ACME) | Cần quyền cài đặt trên máy chủ của từng website; hệ thống này chỉ **theo dõi**, không điều khiển máy chủ khác |
| Quét cả dải mạng để tìm chứng chỉ | Rủi ro SSRF lớn hơn nhiều (H12), và quét mạng người khác có thể vi phạm pháp luật |
| Xác thực hai lớp (mã OTP) | Thêm độ phức tạp đáng kể; ngoài phạm vi đề |
| Trang tự đăng ký | Hệ thống nội bộ, Admin cấp tài khoản (QD-03) |
| Quên mật khẩu qua email | Không có email (như trên); Admin đặt lại mật khẩu mới |
| Chấm điểm an toàn chứng chỉ | Chức năng mở rộng thứ ba, xếp cuối khi thiếu giờ (G1). Hướng phát triển: cảnh báo RSA dưới 2048 bit, thuật toán ký SHA-1, chứng chỉ tự ký |

### I5. Ôn tập: 20 câu "vì sao"

Thử trả lời từng câu bằng lời của mình trước khi mở mục được chỉ.

| # | Câu hỏi | Xem |
|---|---|---|
| 1 | Vì sao cần chứng chỉ số, cặp khoá chưa đủ sao? | A3 |
| 2 | Vì sao chứng chỉ phải có hạn, và hạn ngắn dần thì liên quan gì tới hệ thống này? | A5 |
| 3 | Vì sao mã quản lý bắt buộc còn fingerprint thì tuỳ chọn? | A4 |
| 4 | Vì sao đổi dữ liệu phải dùng POST? | B2 |
| 5 | JavaScript đã kiểm form, vì sao PHP kiểm lại? | B7 |
| 6 | Vì sao PHP thuần, không framework? | C2 |
| 7 | Vì sao chỉ thư mục `public/` mở ra web? | C5 |
| 8 | Vì sao tách tên miền, máy chủ, tổ chức cấp ra bảng riêng? | D4 |
| 9 | Vì sao xoá máy chủ còn tên miền bị chặn? | D5 |
| 10 | PHP đã kiểm trùng, vì sao CSDL còn `UNIQUE`? | D6 |
| 11 | Vì sao trạng thái không lưu trong bảng? | D9 |
| 12 | SQL Injection là gì, chặn bằng gì? | E2 |
| 13 | Vì sao múi giờ đặt trong code? | E6 |
| 14 | Vì sao cookie chỉ chứa mã phiên? | F1 |
| 15 | Vì sao băm mật khẩu bằng bcrypt mà không dùng SHA-256? | F3 |
| 16 | Vì sao chặn dò mật khẩu theo tài khoản, không theo IP? | F4 |
| 17 | Vì sao cảnh báo tạo khi mở trang, không dùng cron? | H4 |
| 18 | Vì sao gia hạn cần giao dịch và khoá dòng? | H5, D11 |
| 19 | Vì sao cần cả thu hồi lẫn xoá? | H6 |
| 20 | Chức năng đọc chứng chỉ thật nguy hiểm ở đâu? | H12 |

---

## Phụ lục: bảng thuật ngữ

| Thuật ngữ | Nghĩa ngắn | Bài |
|---|---|---|
| bcrypt | Thuật toán băm mật khẩu cố tình chậm, có muối; PHP dùng qua `password_hash()` | F3 |
| CA (tổ chức cấp chứng chỉ) | Đơn vị được trình duyệt tin, ký xác nhận chứng chỉ. Ví dụ Let's Encrypt, DigiCert | A3 |
| Chuẩn hoá dữ liệu | Tách bảng để mỗi thông tin chỉ nằm một chỗ | D4 |
| Chữ ký số | Dấu xác nhận tạo bằng khoá riêng, ai có khoá công khai cũng kiểm được, không giả được | A2 |
| Chứng chỉ số | "Căn cước" của website: tên miền + khoá công khai + chữ ký của CA + hạn dùng | A3 |
| CN (Common Name) | Tên chính chứng chỉ bảo vệ | A4 |
| Cookie | Mẩu dữ liệu trình duyệt cất giúp web và tự gửi kèm mỗi yêu cầu | F1 |
| Cron | Lịch chạy lệnh tự động trên máy chủ (hệ thống này không dùng) | H4 |
| CSDL (cơ sở dữ liệu) | Phần mềm chuyên cất và truy vấn dữ liệu | D1 |
| CSRF | Trang lạ mượn phiên của người đang đăng nhập để gửi lệnh | F7 |
| CSV | File bảng dạng văn bản, các ô cách nhau bằng dấu phẩy, Excel mở được | H10 |
| CSV injection | Ô CSV bắt đầu bằng `=` bị Excel chạy như công thức | H10 |
| DNS | Hệ thống đổi tên miền thành địa chỉ IP | H12 |
| DNS rebinding | Đổi bản ghi DNS giữa lúc kiểm và lúc kết nối để lách chặn | H12 |
| ECDSA | Loại cặp khoá dựa trên đường cong elliptic, khoá ngắn mà mạnh | A4 |
| ERD | Sơ đồ các bảng và quan hệ giữa chúng | D4 |
| Fingerprint (dấu vân tay) | Kết quả băm SHA-256 của cả chứng chỉ, dùng để nhận diện | A4 |
| Framework | Bộ khung code có sẵn, ví dụ Laravel | C2 |
| GET, POST | Hai loại yêu cầu HTTP: xem và thay đổi dữ liệu | B2 |
| Giao dịch (transaction) | Gói nhiều lệnh CSDL: tất cả có hiệu lực hoặc không gì cả | D11 |
| Hàm băm | Hàm biến dữ liệu bất kỳ thành chuỗi ngắn cố định, không đảo ngược được | A4 |
| HTTP, HTTPS | Ngôn ngữ hỏi đáp giữa trình duyệt và máy chủ; HTTPS là bản có mã hoá TLS | A1, B1 |
| HttpOnly | Cài đặt cookie: JavaScript không đọc được | F2 |
| InnoDB | Bộ máy lưu trữ của MariaDB hỗ trợ khoá ngoại và giao dịch | D11 |
| Khoá chính | Cột định danh duy nhất một dòng (`id`) | D3 |
| Khoá công khai, khoá riêng | Cặp khoá: khoá công khai phát cho mọi người, khoá riêng chỉ chủ giữ | A2 |
| Khoá ngoại | Cột trỏ tới khoá chính của bảng khác | D4 |
| Let's Encrypt | CA miễn phí, cấp chứng chỉ 90 ngày | A5 |
| Mã CSRF | Mã ngẫu nhiên nhúng trong form để chặn CSRF | F7 |
| Mã trạng thái HTTP | Con số máy chủ trả kèm: 200, 302, 403, 404, 405... | B3 |
| MariaDB | Hệ CSDL tách từ MySQL, có sẵn trong XAMPP | D1 |
| Muối (salt) | Chuỗi ngẫu nhiên trộn vào mật khẩu trước khi băm | F3 |
| NULL | "Không có giá trị", khác chuỗi rỗng | D7 |
| PDO | Bộ công cụ PHP để kết nối CSDL | E1 |
| Phiên (session) | Thông tin người dùng cất ở máy chủ, tra theo mã trong cookie | F1 |
| Post/Redirect/Get | Lưu xong thì chuyển trang, để F5 không gửi lại form | C6 |
| Prepared statement | Câu SQL gửi trước, dữ liệu gửi riêng sau; chặn SQL Injection | E2 |
| RSA | Loại cặp khoá lâu đời, phổ biến nhất | A4 |
| SAN (Subject Alternative Name) | Danh sách tên phụ một chứng chỉ bảo vệ thêm | A4 |
| SameSite | Cài đặt cookie: không gửi kèm yêu cầu đến từ trang khác | F2 |
| Serial (số sê-ri) | Số CA đánh cho từng chứng chỉ nó cấp | A4 |
| Session fixation | Cài sẵn mã phiên vào trình duyệt nạn nhân trước khi họ đăng nhập | F2 |
| SHA-256 | Hàm băm hiện dùng cho fingerprint; MD5, SHA-1 đã bị phá | A4 |
| SQL | Ngôn ngữ hỏi đáp với CSDL | D12 |
| SQL Injection | Dữ liệu người dùng bị CSDL hiểu nhầm thành lệnh | E2 |
| SSRF | Mượn máy chủ kết nối tới địa chỉ nội bộ để dò | H12 |
| Tabler | Bộ giao diện quản trị dựng trên Bootstrap 5 | B5 |
| Test | Code tự kiểm tra code khác | G5 |
| Thu hồi | Tuyên bố chứng chỉ không còn được tin dù chưa hết hạn | A3, H6 |
| TLS | Lớp mã hoá của HTTPS | A1 |
| Trigger | Lệnh CSDL tự chạy khi có sự kiện trên bảng | D10 |
| UNIQUE, CHECK, NOT NULL | Ràng buộc CSDL: không trùng, phải thoả điều kiện, bắt buộc có | D6 |
| utf8mb4 | Bộ ký tự đủ cho mọi chữ, kể cả tiếng Việt | D8 |
| View | Bảng ảo, thực chất là câu truy vấn chạy mỗi lần đọc | D9 |
| Wildcard | Chứng chỉ dạng `*.congty.vn`, dùng cho mọi tên miền con | A4 |
| XSS | Chèn mã script vào trang để chạy trên trình duyệt người xem | C7 |
