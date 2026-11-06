# 🎯 VIỆC ĐANG LÀM
> Phiếu bị GHI ĐÈ ở việc kế tiếp. Xong việc thì để trống, chừa đúng dòng tiêu đề.

## Deploy lên `https://cert.cipher.id.vn:8443`

**Một câu:** luôn có bản chạy thật trên VPS chung, không ảnh hưởng VPN và các dịch vụ đang chạy.
**User đã chốt:** subdomain `cert` (QD-15) · cổng 8443, thêm khối Caddy, có trần RAM, user riêng (QD-07) · không đổi Xray, 443 (QD-08).
**Coi là XONG khi:** landing, đăng nhập chạy qua HTTPS; 28 test xanh trên MariaDB 10.11; `xray` vẫn giữ 443; `anki-bot`, container `anki`, slushai vẫn chạy; đã đo RAM trước và sau.
**CỐ Ý KHÔNG làm:** đổi Xray, cổng 443 · sửa `helper-bot` · cron · web ở `cipher.id.vn`.
**Đã đo 06/11/2026:** RAM khả dụng 1,1 Gi · đĩa còn 4,8 G · chưa có PHP, MariaDB · `cert` đã trỏ đúng · `dungmoi.sh` của slushai ghi đè Caddyfile nếu chạy lại.

### Kế hoạch (duyệt 06/11/2026; riêng phiếu này AI chạy lệnh qua SSH, có sửa `VPS_CHUNG.md`)
`deploy/` trong repo: script cài và khối Caddy · sửa `QUYETDINH.md`, `SONO.md`, `CLAUDE.md`.
