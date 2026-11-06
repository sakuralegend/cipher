# CLAUDE.md — luật làm việc trong repo này (đọc trước khi làm bất cứ gì)

Khởi tạo bằng skill `quy-trinh-lam-viec` v1.0 · Vai user: **HIỂU** · Mức: **NHẸ**

## Dự án trong 3 dòng
- Làm gì, cho ai: bài tập lớn đề 69 "Xây dựng hệ thống quản lý chứng chỉ số và thời hạn sử dụng" (tên này dùng trong báo cáo và trên web; `cipher` là tên repo và tên miền), cho quản trị viên trong tổ chức; sau khi nộp, web còn làm lớp ngụy trang self-SNI cho VPN (QD-08).
- Stack: PHP 8 thuần + PDO · MariaDB · HTML/CSS/JS, Tabler (trên Bootstrap 5, QD-13), Chart.js · Chạy: `C:\xampp\php\php.exe -S localhost:8000 -t public` (bật MySQL trong XAMPP) · Deploy: chưa có, dựng ở phiếu ngày 7/11 (user tự chạy, AI chỉ nhắc)
- Hạn / mốc: cổng tối 11/11/2026 (đủ 19 chức năng, chạy trên VPS) · nộp 17/11/2026 · user sang Nga khoảng cuối 11/2026. Đề và lịch: `docs/BAN_GIAO.md` (ảnh chụp, không cập nhật).

## Người dùng
User phải hiểu và bảo vệ được mọi dòng code khi vấn đáp. Ưu tiên code đơn giản, giải thích được.
Kế hoạch nói rõ cách tiếp cận và vì sao chọn; nghiệm thu có mục "Hiểu để bảo vệ".
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
   đi một cửa: CSDL → một file kết nối PDO duy nhất (tạo ở phiếu đăng nhập).
4. Quyết định hành vi ⇒ test 4 dòng (Luật · Vì sao · Đỏ nghĩa là · Đừng), thấy ĐỎ một lần, rồi
   xoá dòng QD. Sửa bug ⇒ thêm đúng một test cho bug đó.
5. Trước khi "tối ưu": đọc bảng ĐÃ ĐO RỒI BÁC ở `QUYETDINH.md`. Con số phải có ngày đo.

## Dừng lại hỏi trước khi
1. Xoá/ghi đè hàng loạt dữ liệu thật, đổi schema, migrate — việc đó đứng riêng, backup trước, kiểm sau.
2. Thêm thư viện/framework mới.
3. Đụng hạ tầng hay repo ngoài phạm vi việc được giao.
4. 🔴 Bất cứ thay đổi nào trên VPS `161.248.146.56`: máy chạy chung VPN, bot Anki, slushai, bot Telegram. Đọc `D:\Desktop\slushai\VPS_CHUNG.md` trước; đo RAM trước và sau khi cài; chạy đủ lệnh kiểm trong đó sau mỗi lần đụng. Không đổi Xray hay cổng 443 trước khi bài đã nộp và bảo vệ xong (QD-08).

## Lệnh nghiệm thu — chạy THẬT cuối mỗi việc
Chạy trong Git Bash, từ thư mục gốc repo:
```
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -r -n1 /c/xampp/php/php.exe -l
python ban_do_test.py --kiem
```
Bộ test: chưa có; lệnh chạy test được thêm vào đây ở phiếu đầu tiên.

## Sổ
`VIECDANGLAM.md` việc đang làm (đúng 1 việc) · `QUYETDINH.md` quyết định còn sống + ĐÃ ĐO RỒI BÁC ·
`SONO.md` nợ có hạn

## Môi trường
Windows 11 · PHP 8.2.12, MariaDB 10.4.32 (XAMPP, `C:\xampp`, không nằm trong PATH) · Python 3.14. VPS: Ubuntu 24.04.
`PYTHONIOENCODING=utf-8`; Bash tool là Git Bash; commit dài dùng `git commit -F - <<'EOF'`.
Hook commit-msg nằm ngoài git, cài một lần trên mỗi máy: `cp scripts/hook-commit-msg .git/hooks/commit-msg && chmod +x .git/hooks/commit-msg`.
Múi giờ `Asia/Ho_Chi_Minh` cho cả PHP và CSDL — lệch là sai số ngày còn lại. Đặt trong code, không dựa máy: PHP XAMPP mặc định `Europe/Berlin`; CSDL XAMPP chưa nạp bảng tên múi giờ nên phải `SET time_zone='+07:00'` (đo 06/11/2026).
