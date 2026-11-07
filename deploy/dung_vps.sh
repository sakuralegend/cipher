#!/usr/bin/env bash
# Dựng web quản lý chứng chỉ trên VPS chung. Đọc D:\Desktop\slushai\VPS_CHUNG.md trước khi sửa file này.
# Mọi thứ đều THÊM MỚI, không sửa dịch vụ có sẵn; riêng Caddyfile được nối thêm một khối.
#
# Chạy từ máy Windows, trong Git Bash, từng bước theo thứ tự:
#   ssh root@161.248.146.56 'bash -s -- <bước>' < deploy/dung_vps.sh
#
# Các bước:
#   kiem       Đo RAM, cổng, kiểm tra các dịch vụ có sẵn. Chỉ đọc, chạy lúc nào cũng được.
#   chuan_bi   Tạo user cipher và khoá chỉ-đọc, in khoá công khai để dán vào GitHub.
#   cai        Cài PHP-FPM, MariaDB có trần RAM.
#   trien_khai Lấy code, tạo CSDL, nạp dữ liệu mẫu, đặt mật khẩu ngẫu nhiên. Chỉ chạy một lần.
#   thu        Chạy bộ test trên MariaDB của VPS bằng CSDL tạm, rồi xoá CSDL đó.
#   caddy      Nối khối Caddy, kiểm tra cú pháp, nạp lại.
#   cap_nhat   Lấy code mới từ GitHub. Không đụng CSDL.
#   go         Gỡ khối Caddy và tắt PHP-FPM, MariaDB. Giữ nguyên dữ liệu.

set -euo pipefail

THU_MUC=/var/www/cipher
REPO=git@github.com:sakuralegend/cipher.git
KHOA=/home/cipher/.ssh/cipher_deploy
CADDYFILE=/etc/caddy/Caddyfile
XRAY_CONFIG=/usr/local/etc/xray/config.json
TEP_MAT_KHAU=/root/cipher-matkhau.txt

chet() { echo "DUNG: $*" >&2; exit 1; }

# Chạy lệnh dưới quyền user cipher; user này không có shell đăng nhập.
la_cipher() { runuser -u cipher -- "$@"; }

kiem() {
    date
    free -h
    df -h / | tail -1
    echo "--- Cổng 443 phải là xray, không phải caddy:"
    ss -tlnp | grep ':443 ' | grep -q xray && echo "OK xray giữ 443" || chet "443 KHÔNG còn là xray"
    echo "--- Qua 443 phải ra web thật (Xray chuyển sang Caddy); hỏng là VPN cũng không nhận kết nối mới:"
    [ "$(curl -s -m 15 -o /dev/null -w '%{http_code}' --resolve cert.cipher.id.vn:443:127.0.0.1 https://cert.cipher.id.vn/)" = 200 ] \
        && echo "OK web qua 443" || chet "Web qua 443 KHÔNG trả 200"
    echo "--- Xray gửi PROXY protocol (xver 1) thì Caddy phải nhận ở 8443, lệch là web qua 443 hỏng:"
    if grep -q '"xver": *[12]' "$XRAY_CONFIG" && ! grep -q 'proxy_protocol' "$CADDYFILE"; then
        chet "Xray gửi PROXY protocol nhưng Caddyfile thiếu khối nhận. Chạy bước caddy để thêm lại."
    fi
    echo "OK Xray và Caddy khớp về PROXY protocol"
    echo "--- Dịch vụ có sẵn:"
    systemctl is-active anki-bot slushai caddy
    docker ps --format '{{.Names}}' | grep -qx anki && echo "OK container anki" || chet "mất container anki"
    echo "--- Dịch vụ của web này (chưa cài thì báo inactive):"
    systemctl is-active php8.3-fpm mariadb || true
    ps -eo rss,comm --sort=-rss | grep -E 'mariadbd|php-fpm' | head -6 || true
}

chuan_bi() {
    id cipher >/dev/null 2>&1 || useradd --system --create-home --home-dir /home/cipher --shell /usr/sbin/nologin cipher
    install -d -m 700 -o cipher -g cipher /home/cipher/.ssh /home/cipher/sessions
    [ -f "$KHOA" ] || la_cipher ssh-keygen -q -t ed25519 -N '' -C 'cipher-deploy-vps' -f "$KHOA"
    ssh-keyscan -t ed25519 github.com 2>/dev/null > /home/cipher/.ssh/known_hosts
    chown cipher:cipher /home/cipher/.ssh/known_hosts
    echo "--- Dán dòng dưới vào GitHub: repo cipher, Settings, Deploy keys, KHÔNG tick Allow write access:"
    cat "$KHOA.pub"
}

cai() {
    # Đặt cấu hình trước khi cài, để dịch vụ có giới hạn ngay từ lần khởi động đầu tiên.
    install -d /etc/mysql/mariadb.conf.d /etc/systemd/system/mariadb.service.d \
               /etc/systemd/system/php8.3-fpm.service.d /etc/php/8.3/fpm/conf.d /etc/php/8.3/fpm/pool.d

    cat > /etc/mysql/mariadb.conf.d/60-cipher.cnf <<'CAU_HINH'
# Máy chỉ có 1,9 Gi RAM, dùng chung với VPN và các bot: giữ MariaDB thật nhỏ.
[mysqld]
bind-address            = 127.0.0.1
innodb_buffer_pool_size = 64M
max_connections         = 20
performance_schema      = OFF
key_buffer_size         = 8M
tmp_table_size          = 16M
max_heap_table_size     = 16M
CAU_HINH

    # Vượt trần thì systemd giết chính dịch vụ này, không để Linux giết bừa xray hay bot.
    printf '[Service]\nMemoryMax=300M\n' > /etc/systemd/system/mariadb.service.d/cipher.conf
    printf '[Service]\nMemoryMax=150M\n' > /etc/systemd/system/php8.3-fpm.service.d/cipher.conf

    cat > /etc/php/8.3/fpm/conf.d/99-cipher.ini <<'CAU_HINH'
opcache.memory_consumption = 32
opcache.jit = off
expose_php = off
CAU_HINH

    systemctl daemon-reload

    # Làm mới danh sách gói: bản cũ trỏ tới phiên bản đã bị gỡ khỏi máy chủ Ubuntu (lỗi 404, đo 06/11/2026).
    apt-get update -q

    # NEEDRESTART_SUSPEND: gói needrestart có thể tự khởi động lại xray, anki-bot khi nâng cấp thư viện.
    # --no-install-recommends: không kéo thêm gói ngoài danh sách.
    DEBIAN_FRONTEND=noninteractive NEEDRESTART_SUSPEND=1 apt-get install -y --no-install-recommends \
        php8.3-fpm php8.3-mysql php8.3-mbstring mariadb-server

    cat > /etc/php/8.3/fpm/pool.d/cipher.conf <<'CAU_HINH'
[cipher]
user = cipher
group = cipher
listen = /run/php/cipher.sock
listen.owner = caddy
listen.group = caddy
listen.mode = 0660
; ondemand: lúc không có khách thì không giữ tiến trình nào, gần như không tốn RAM.
pm = ondemand
pm.max_children = 4
pm.process_idle_timeout = 30s
pm.max_requests = 200
php_admin_flag[display_errors] = off
php_admin_flag[log_errors] = on
php_admin_value[error_log] = /home/cipher/php-error.log
php_admin_value[memory_limit] = 64M
php_admin_value[session.save_path] = /home/cipher/sessions
php_admin_value[session.gc_maxlifetime] = 7200
php_admin_value[open_basedir] = /var/www/cipher:/home/cipher:/tmp
CAU_HINH

    # Tắt pool mặc định của gói cài đặt: không ai dùng, và nó giữ sẵn tiến trình tốn RAM.
    [ ! -f /etc/php/8.3/fpm/pool.d/www.conf ] || mv /etc/php/8.3/fpm/pool.d/www.conf /etc/php/8.3/fpm/pool.d/www.conf.tat

    systemctl restart mariadb php8.3-fpm
    systemctl is-active mariadb php8.3-fpm
    php -v | head -1
    mariadb -N -e 'SELECT VERSION()'
}

trien_khai() {
    # Chặn cuối: schema.sql xoá sạch bảng, nên tuyệt đối không chạy lại trên CSDL đang có dữ liệu.
    if mariadb -N -e "SHOW DATABASES LIKE 'cipher'" | grep -qx cipher \
        && [ -n "$(mariadb -N cipher -e 'SHOW TABLES')" ]; then
        chet "CSDL cipher đã có bảng. Không chạy lại để tránh mất dữ liệu; dùng bước cap_nhat."
    fi

    install -d -m 755 /var/www
    if [ ! -d "$THU_MUC/.git" ]; then
        install -d -m 755 -o cipher -g cipher "$THU_MUC"
        la_cipher git -c core.sshCommand="ssh -i $KHOA -o IdentitiesOnly=yes" clone "$REPO" "$THU_MUC"
        # Khai khoá ngay trong repo, không sửa ~/.ssh/config dùng chung.
        la_cipher git -C "$THU_MUC" config core.sshCommand "ssh -i $KHOA -o IdentitiesOnly=yes"
    fi

    local mk_csdl mk_admin mk_nhanvien
    mk_csdl=$(openssl rand -hex 24)
    mk_admin=$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 16)
    mk_nhanvien=$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 16)

    mariadb <<SQL
CREATE DATABASE IF NOT EXISTS cipher CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE OR REPLACE USER 'cipher'@'127.0.0.1' IDENTIFIED BY '$mk_csdl';
GRANT ALL PRIVILEGES ON cipher.* TO 'cipher'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

    # File này không vào git; quyền 640 để chỉ user cipher đọc được.
    install -m 640 -o cipher -g cipher /dev/null "$THU_MUC/config/config.local.php"
    cat > "$THU_MUC/config/config.local.php" <<PHP
<?php
return ['dbname' => 'cipher', 'user' => 'cipher', 'pass' => '$mk_csdl'];
PHP

    mariadb cipher < "$THU_MUC/database/schema.sql"
    mariadb cipher < "$THU_MUC/database/seed.sql"

    # Thay mật khẩu mẫu ngay: web ra internet. Truyền qua biến môi trường để không lộ trong danh sách tiến trình.
    MK_ADMIN="$mk_admin" MK_NHANVIEN="$mk_nhanvien" la_cipher php -r '
        require "/var/www/cipher/config/db.php";
        $pdo = ketNoiCsdl();
        $cau = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
        $cau->execute([password_hash(getenv("MK_ADMIN"), PASSWORD_DEFAULT), "admin"]);
        $cau->execute([password_hash(getenv("MK_NHANVIEN"), PASSWORD_DEFAULT), "nhanvien"]);
    '

    install -m 600 /dev/null "$TEP_MAT_KHAU"
    printf 'admin    %s\nnhanvien %s\n' "$mk_admin" "$mk_nhanvien" > "$TEP_MAT_KHAU"

    echo "--- Xong. Mật khẩu web lưu ở $TEP_MAT_KHAU, không in ra màn hình."
    mariadb -N cipher -e 'SELECT cert_code, status, days_left FROM v_certificates'
}

thu() {
    # Bộ test cần CSDL tên cipher_test; tạo tạm và luôn xoá khi kết thúc, kể cả khi test đỏ.
    trap "mariadb -e \"DROP DATABASE IF EXISTS cipher_test\"" EXIT
    mariadb <<'SQL'
CREATE DATABASE cipher_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON cipher_test.* TO 'cipher'@'127.0.0.1';
SQL
    la_cipher php "$THU_MUC/tests/chay.php"
    mariadb -e "REVOKE ALL PRIVILEGES ON cipher_test.* FROM 'cipher'@'127.0.0.1'"
}

caddy_noi() {
    local khoi="$THU_MUC/deploy/caddy-cipher.conf"
    [ -f "$khoi" ] || chet "Chưa có $khoi; chạy bước trien_khai hoặc cap_nhat trước."

    if grep -q 'cert.cipher.id.vn' "$CADDYFILE"; then
        echo "Caddyfile đã có khối này."
    else
        local sao_luu
        sao_luu="$CADDYFILE.truoc-cipher-$(date +%Y%m%d-%H%M%S)"
        cp -p "$CADDYFILE" "$sao_luu"
        echo "Sao lưu: $sao_luu"
        { echo; cat "$khoi"; } >> "$CADDYFILE"
        # Sai cú pháp thì trả lại file cũ ngay; Caddy đang chạy không bị ảnh hưởng.
        if ! caddy validate --adapter caddyfile --config "$CADDYFILE"; then
            cp -p "$sao_luu" "$CADDYFILE"
            chet "Caddyfile sai cú pháp, đã khôi phục bản cũ."
        fi
    fi

    # Nhận PROXY protocol ở 8443 (08/11/2026): Xray chuyển khách từ 443 sang 127.0.0.1:8443 kèm IP gốc
    # (xver 1 trong cấu hình Xray, repo vpn-xray), để Nhật ký ghi IP thật thay cho 127.0.0.1. Chỉ tin phần
    # này từ 127.0.0.1; khách slushai vào thẳng 8443 không gửi nên fallback_policy skip: nhận như cũ.
    # Khối nằm trong phần cài đặt toàn cục (chung với slushai). dungmoi.sh của slushai ghi đè Caddyfile là
    # mất nó; khi đó Xray vẫn gửi mà Caddy không nhận: web qua 443 hỏng, VPN không nhận kết nối mới.
    # Bước kiem bắt được tình huống này.
    if ! grep -q 'proxy_protocol' "$CADDYFILE"; then
        local sao_luu_pp
        sao_luu_pp="$CADDYFILE.truoc-proxyproto-$(date +%Y%m%d-%H%M%S)"
        cp -p "$CADDYFILE" "$sao_luu_pp"
        python3 - "$CADDYFILE" <<'THEM_PROXY'
import sys
duong = sys.argv[1]
# Ghi rõ UTF-8: chú thích có tiếng Việt, không để bảng mã mặc định của máy quyết định.
s = open(duong, encoding="utf-8").read()
cu = "\thttps_port 8443\n}"
if s.count(cu) != 1:
    sys.exit("Không thấy khối toàn cục có https_port 8443; thêm khối nhận PROXY protocol bằng tay.")
moi = """\thttps_port 8443
\t# cipher: nhận PROXY protocol từ Xray (127.0.0.1) để web biết IP thật; xem deploy/dung_vps.sh của repo cipher.
\tservers :8443 {
\t\tlistener_wrappers {
\t\t\tproxy_protocol {
\t\t\t\tallow 127.0.0.1/32
\t\t\t\tfallback_policy skip
\t\t\t}
\t\t\ttls
\t\t}
\t}
}"""
open(duong, "w", encoding="utf-8").write(s.replace(cu, moi, 1))
THEM_PROXY
        if ! caddy validate --adapter caddyfile --config "$CADDYFILE"; then
            cp -p "$sao_luu_pp" "$CADDYFILE"
            chet "Khối nhận PROXY protocol làm Caddyfile sai cú pháp, đã khôi phục bản cũ."
        fi
        echo "Đã thêm khối nhận PROXY protocol. Sao lưu: $sao_luu_pp"
    fi

    # reload, không restart: giữ nguyên kết nối đang có của slushai.
    systemctl reload caddy
    sleep 2
    ss -tlnp | grep ':443 ' | grep -q xray || chet "443 KHÔNG còn là xray. Khôi phục Caddyfile từ bản sao lưu ngay."
    echo "OK xray vẫn giữ 443"
}

cap_nhat() {
    la_cipher git -C "$THU_MUC" pull --ff-only
    # PHP giữ code đã biên dịch trong bộ nhớ (opcache): nạp lại để chạy code mới.
    systemctl reload php8.3-fpm
    la_cipher git -C "$THU_MUC" log --oneline -1
}

go() {
    # Xray đang ngụy trang bằng chính web này: gỡ khối Caddy là VPN không nhận được kết nối mới.
    if grep -q '127.0.0.1:8443' /usr/local/etc/xray/config.json; then
        chet "Xray đang dùng web này làm dest. Đổi dest của Xray trước (repo vpn-xray), rồi mới gỡ web."
    fi

    local ban_goc
    ban_goc=$(ls -1t "$CADDYFILE".truoc-cipher-* 2>/dev/null | tail -1 || true)
    [ -n "$ban_goc" ] || chet "Không tìm thấy bản sao lưu Caddyfile."
    cp -p "$ban_goc" "$CADDYFILE"
    caddy validate --adapter caddyfile --config "$CADDYFILE"
    systemctl reload caddy
    systemctl disable --now php8.3-fpm mariadb
    ss -tlnp | grep ':443 ' | grep -q xray && echo "OK xray vẫn giữ 443"
}

case "${1:-}" in
    kiem) kiem ;;
    chuan_bi) chuan_bi ;;
    cai) cai ;;
    trien_khai) trien_khai ;;
    thu) thu ;;
    caddy) caddy_noi ;;
    cap_nhat) cap_nhat ;;
    go) go ;;
    *) chet "Bước không hợp lệ: ${1:-trống}. Xem danh sách ở đầu file." ;;
esac
