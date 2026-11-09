# CLAUDE.md — luật làm việc trong repo này (đọc trước khi làm bất cứ gì)

Khởi tạo bằng skill `quy-trinh-lam-viec` v1.0 · Vai user: **DUYỆT** (QD-17) · Mức: **NHẸ**

## Dự án trong 3 dòng
- Làm gì, cho ai: bài tập lớn đề 69 "Xây dựng hệ thống quản lý chứng chỉ số và thời hạn sử dụng" (tên này dùng trong báo cáo, landing và trang đăng nhập; tên ngắn trên logo, menu, tab là `Cipher Cert`, QD-24; `cipher` là tên repo và tên miền), cho quản trị viên trong tổ chức; từ 07/11/2026 web còn làm lớp ngụy trang self-SNI cho VPN (QD-08).
- Stack: PHP 8 thuần + PDO · MariaDB · HTML/CSS/JS, Tabler (trên Bootstrap 5, QD-13), Chart.js · Chạy: `C:\xampp\php\php.exe -S localhost:8000 -t public` (bật MySQL trong XAMPP) · Deploy: `git push`, rồi `ssh root@161.248.146.56 'bash -s -- cap_nhat' < deploy/dung_vps.sh` (user cho phép 06/11/2026: AI tự chạy đúng hai lệnh này sau mỗi phiếu rồi chạy bước `kiem`; đổi schema, cấu hình VPS vẫn phải hỏi)
- Hạn / mốc: cổng tối 11/11/2026 (đủ 19 chức năng, chạy trên VPS) · nộp 17/11/2026 · user sang Nga khoảng cuối 11/2026. Đề và lịch: `docs/BAN_GIAO.md` (ảnh chụp, không cập nhật).

## Người dùng
Bài KHÔNG có vấn đáp (user chốt 06/11/2026, dòng "vấn đáp" trong `docs/BAN_GIAO.md` đã hết hiệu lực). Đề vẫn ghi "mỗi thành viên đều phải nắm rõ toàn bộ quy trình, chức năng và mã code", nhưng user chốt không ai hỏi từng dòng: chỉ cần hiểu sơ bộ, nên code phải có chú thích giải thích và báo cáo cuối việc phải tóm tắt luồng chính. Tài liệu nộp (README, docs/) KHÔNG sửa theo từng lần đổi code: tạo, đồng bộ một lần trước khi nộp (user chốt 09/11/2026, việc nằm ở `SONO.md`); vì vậy chú thích trong code và thân commit phải đủ để lúc cuối viết lại tài liệu mà không cần nhớ. Nhóm tối đa 3 người, user làm hết. Mục tiêu: sản phẩm đẹp nhất có thể, cùng bản SPEC và Use Case tốt. Câu hỏi kỹ thuật là việc của AI: đo rồi tự trả lời; không kết luận được thì trình hai phương án kèm đánh đổi.
User duyệt theo 4 mục: làm gì · rủi ro · mất bao lâu · lùi lại thế nào. Việc liên quan đến giao diện phải chụp màn hình và tự xem trước khi báo xong.
Phản biện trước khi tuân lệnh · thuật ngữ phải giải thích · đừng bắt user nhớ hộ.

## Nghe xong thì rẽ ngay — user không gõ lệnh
- Xin thêm/sửa chức năng ⇒ `.claude/commands/ycau.md` → `kehoach.md` (CHỜ DUYỆT) → code →
  `nghiemthu.md`. Cấm viết code trước khi kế hoạch được duyệt.
- Hỏi đáp / sửa lỗi vặt ⇒ đi thẳng.
- User sửa AI ("không phải thế…") ⇒ `.claude/commands/baihoc.md`.

## Luật repo
1. Lịch sử = `git log`. Commit đụng code có thân khai VÌ SAO. Xong + nghiệm thu xanh ⇒ tự commit,
   không hỏi. Không tự deploy.
2. Chống loãng: thêm phải cắt chỗ khác; file có sẵn chứa được thì cấm file mới; sổ có sức chứa
   cố định — chạm trần thì cho một mục chết, cấm nén chữ.
3. Trước khi viết: liệt kê hàm/module có sẵn dùng lại được. Không hàm trùng vai. Tài nguyên ngoài
   đi một cửa: CSDL → một file kết nối PDO duy nhất `config/db.php`, hàm `ketNoiCsdl()`.
4. Quyết định hành vi ⇒ test 4 dòng (Luật · Vì sao · Đỏ nghĩa là · Đừng), thấy ĐỎ một lần, rồi
   xoá dòng QD. Sửa bug ⇒ thêm đúng một test cho bug đó.
5. Trước khi "tối ưu": đọc bảng ĐÃ ĐO RỒI BÁC ở `QUYETDINH.md`. Con số phải có ngày đo.

## Dừng lại hỏi trước khi
1. Xoá/ghi đè hàng loạt dữ liệu thật, đổi schema, migrate — việc đó đứng riêng, backup trước, kiểm sau.
2. Thêm thư viện/framework mới.
3. Đụng hạ tầng hay repo ngoài phạm vi việc được giao.
4. 🔴 Bất cứ thay đổi nào trên VPS `161.248.146.56`: máy chạy chung VPN, bot Anki, slushai, bot Telegram. Đọc `D:\Desktop\slushai\VPS_CHUNG.md` trước; đo RAM trước và sau khi cài; chạy đủ lệnh kiểm trong đó sau mỗi lần đụng. Khách vào web qua 443 của Xray, Xray chuyển sang Caddy (QD-08): khối `cert` trong `Caddyfile` hỏng thì VPN không nhận kết nối mới; chỉ `deploy/caddy-cipher.conf` đổi thì VPS chưa đổi. Từ 08/11/2026 Xray gửi IP gốc bằng PROXY protocol (`xver` 1), Caddy nhận ở khối toàn cục `servers :8443`: mất khối đó thì web qua 443 cũng hỏng (bước `caddy` thêm lại, bước `kiem` bắt lệch). Đổi Xray hay cổng 443 phải hỏi.

## Lệnh nghiệm thu — chạy THẬT cuối mỗi việc
Chạy trong Git Bash, từ thư mục gốc repo:
```
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -r -n1 /c/xampp/php/php.exe -l
python ban_do_test.py --kiem
/c/xampp/php/php.exe tests/chay.php
```
Test cần MySQL đang bật và CSDL `cipher_test` đã tạo (xem đầu `database/schema.sql`); test tự nạp lại CSDL này trước mỗi lần chạy, không đụng `cipher_dev`.

## Sổ
`VIECDANGLAM.md` việc đang làm (đúng 1 việc) · `QUYETDINH.md` quyết định còn sống + ĐÃ ĐO RỒI BÁC ·
`SONO.md` nợ có hạn

## Môi trường
Windows 11 · PHP 8.2.12, MariaDB 10.4.32 (XAMPP, `C:\xampp`, không nằm trong PATH) · Python 3.14. VPS: Ubuntu 24.04.
`PYTHONIOENCODING=utf-8`; Bash tool là Git Bash; commit dài dùng `git commit -F - <<'EOF'`.
Hook commit-msg nằm ngoài git, cài một lần trên mỗi máy: `cp scripts/hook-commit-msg .git/hooks/commit-msg && chmod +x .git/hooks/commit-msg`.
Múi giờ `Asia/Ho_Chi_Minh` cho cả PHP và CSDL — lệch là sai số ngày còn lại. Đặt trong code, không dựa máy: PHP XAMPP mặc định `Europe/Berlin`; CSDL XAMPP chưa nạp bảng tên múi giờ nên phải `SET time_zone='+07:00'` (đo 06/11/2026).
