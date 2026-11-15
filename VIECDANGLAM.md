# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Slide thuyết trình 10 phút (mở 15/11/2026, phải xong trước buổi nộp 17/11/2026)

**Trạng thái:** user đã duyệt khung bài nói, chưa làm slide. Lúc mở phiếu, phiên Claude Code chưa thấy công cụ
Canva dù user đã bật connector, nên user mở phiên mới để thử lại.
- Phiên mới có công cụ Canva ⇒ làm slide thẳng trong Canva.
- Vẫn không có ⇒ báo user kiểm lại connector trên claude.ai (đã đăng nhập Canva, đã cho phép dùng trong Claude
  Code), hoặc làm bằng Slides artifact, xuất .pptx để user Import vào Canva. AI nghiêng về cách này vì hạn gần.

**Phân thời gian:** khoảng một nửa là demo trực tiếp trên https://cert.cipher.id.vn, slide chỉ cho phần ngoài demo.

| Phút | Phần |
|---|---|
| 0:00–1:00 | Bài toán |
| 1:00–2:00 | Phạm vi, công nghệ |
| 2:00–7:00 | Demo (không cần slide, chỉ một slide chuyển cảnh) |
| 7:00–8:30 | Thiết kế, bảo mật (chọn 3 điểm) |
| 8:30–9:30 | Kiểm thử |
| 9:30–10:00 | Hạn chế, hướng phát triển |

**7 slide + 1 slide chuyển cảnh** (mỗi slide kèm ghi chú lời nói; nền tối cùng tông web: quản trị `#222838`,
landing đen xanh, font Inter, QD-22/25; ảnh lấy từ `docs/anh/`):
1. Bìa: tên đề tài đầy đủ, brand Cipher Cert, 3 thành viên, link `cert.cipher.id.vn`.
2. Bài toán: từ 15/03/2026 chứng chỉ công khai tối đa 200 ngày, năm 2029 còn 47 ngày; hết hạn là trình duyệt chặn
   trang. Câu móc: gia hạn dày gấp 4, Excel không theo kịp.
3. Phạm vi: 19 chức năng (9 của đề 69 D1–D9, 10 nền tảng Na–Nj), 3 tiêu chí (fingerprint, tự tính số ngày,
   cảnh báo 30/15/7), 3 mở rộng (nhật ký, đọc chứng chỉ thật, chấm hạng A/B/C/F). Stack: PHP 8 thuần + PDO,
   MariaDB, Tabler, Chart.js.
4. Chuyển sang demo.
5. Thiết kế: số ngày còn lại tính mỗi lần đọc trong view `v_certificates` · cảnh báo không cần cron (`UNIQUE` chặn
   trùng) · nhật ký có 2 trigger chặn sửa, xoá kể cả SQL gõ thẳng · gia hạn trong một giao dịch `SELECT … FOR UPDATE`.
6. Bảo mật: chặn SSRF khi đọc chứng chỉ (từ chối IP nội bộ, kết nối đúng IP đã kiểm) · prepared statement ·
   5 header (HSTS, CSP, X-Frame-Options, nosniff, Referrer-Policy) · kiểm tra 3 lớp JS → PHP → CSDL.
7. Kiểm thử: 136 test tự động đạt, 34 kịch bản nhập tay (`DU_LIEU_NHAP_TAY.xlsx`), chạy bằng một lần bấm
   `CHAY_WEB.bat`.
8. Kết luận: hạn chế (chưa gửi email, chưa 2FA, chưa STARTTLS); hướng phát triển (ACME, đọc lại chứng chỉ định kỳ,
   mật mã kháng lượng tử, gắn với ngành mật mã).

**Kịch bản demo (5 phút):** Dashboard (30 giây) → đọc chứng chỉ thật, ví dụ `google.com`, tự điền rồi bấm Thêm
(1 phút 30, điểm "wow") → chấm hạng trên trang chi tiết (45 giây) → gia hạn một chứng chỉ ở mốc 7 ngày, cảnh báo
mất, lịch sử còn (1 phút) → Thống kê, xuất CSV (45 giây) → Nhật ký (30 giây). Còn thời gian thì gõ `127.0.0.1`
vào ô đọc chứng chỉ để thấy bị chặn (SSRF).

**Lưu ý đã báo user:**
- KHÔNG nhắc VPN hay lớp ngụy trang self-SNI (`SONO.md`: không đưa vào tài liệu nộp).
- Quay sẵn video demo 2–3 phút làm phương án dự phòng, cộng thêm `CHAY_WEB.bat` chạy trên máy.
- Nếu 3 người cùng trình bày thì chia theo bảng Phần VIII trong `docs/BAO_CAO.md`: Trung nói bài toán, nền tảng,
  bảo mật; Tuấn Anh demo chứng chỉ, đọc chứng chỉ thật, gia hạn; Kiên nói cảnh báo, thống kê, kết luận.

**Nguồn số liệu:** `docs/BAO_CAO.md` (Phần I, V mục 3–5, IX), `docs/SPEC.md`, `README.md`.
