-- Cấu trúc CSDL: Hệ thống quản lý chứng chỉ số và thời hạn sử dụng.
--
-- CẢNH BÁO: file này XOÁ toàn bộ bảng rồi tạo lại. Không chạy trên CSDL có dữ liệu cần giữ.
-- File không chứa CREATE DATABASE hay USE, để nạp được vào cipher_dev lẫn cipher_test.
--
-- Cách nạp:
--   mysql -u root -e "CREATE DATABASE IF NOT EXISTS cipher_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--   mysql -u root cipher_dev < database/schema.sql
--   mysql -u root cipher_dev < database/seed.sql

SET NAMES utf8mb4;

-- Xoá theo thứ tự ngược quan hệ: bảng con trước, bảng cha sau.
DROP VIEW IF EXISTS v_certificates;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS alerts;
DROP TABLE IF EXISTS renewals;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS certificate_authorities;
DROP TABLE IF EXISTS domains;
DROP TABLE IF EXISTS servers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0, -- Admin khoá, tới khi Admin mở lại.
    -- Chặn dò mật khẩu theo tài khoản, không theo IP: web sau proxy có thể chỉ thấy một IP.
    failed_logins TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE servers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hostname VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL, -- 45 ký tự đủ cho cả IPv6.
    port SMALLINT UNSIGNED NOT NULL DEFAULT 443,
    environment ENUM('production', 'staging', 'development') NOT NULL DEFAULT 'production',
    owner VARCHAR(100) NOT NULL,
    CONSTRAINT chk_servers_port CHECK (port BETWEEN 1 AND 65535)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE domains (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain_name VARCHAR(255) NOT NULL UNIQUE,
    server_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_domains_server FOREIGN KEY (server_id) REFERENCES servers (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certificate_authorities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    country VARCHAR(100) NOT NULL,
    is_trusted TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certificates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cert_code VARCHAR(30) NOT NULL UNIQUE,
    -- Đề ghi "mã quản lý HOẶC fingerprint": mã là bắt buộc, fingerprint được trống.
    -- ascii_bin phân biệt hoa thường, để CHECK ép đúng dạng đã chuẩn hoá; nếu không,
    -- 'AB:CD' và 'abcd' là cùng một vân tay nhưng lọt qua được UNIQUE.
    fingerprint_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
    serial VARCHAR(128) NULL, -- Số sê-ri có thể rất dài, nên lưu dạng chữ.
    subject_cn VARCHAR(255) NOT NULL,
    san TEXT NULL,
    ca_id INT UNSIGNED NOT NULL,
    domain_id INT UNSIGNED NOT NULL,
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NOT NULL,
    key_algorithm VARCHAR(20) NOT NULL,
    key_size SMALLINT UNSIGNED NOT NULL,
    signature_algorithm VARCHAR(50) NOT NULL,
    is_revoked TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NOT NULL,
    CONSTRAINT chk_cert_dates CHECK (valid_to > valid_from),
    CONSTRAINT chk_cert_fingerprint CHECK (fingerprint_sha256 REGEXP '^[0-9a-f]{64}$'),
    CONSTRAINT fk_cert_ca FOREIGN KEY (ca_id) REFERENCES certificate_authorities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_cert_domain FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE RESTRICT,
    CONSTRAINT fk_cert_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE renewals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_id INT UNSIGNED NOT NULL,
    old_valid_to DATETIME NOT NULL,
    new_valid_to DATETIME NOT NULL,
    -- Gia hạn sửa trên cùng dòng certificates, nên thông tin bản cũ chỉ còn lại ở đây.
    old_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    old_serial VARCHAR(128) NULL,
    renewed_by INT UNSIGNED NOT NULL,
    renewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    note VARCHAR(255) NULL,
    CONSTRAINT chk_renewal_dates CHECK (new_valid_to > old_valid_to),
    CONSTRAINT fk_renewal_cert FOREIGN KEY (certificate_id) REFERENCES certificates (id) ON DELETE CASCADE,
    CONSTRAINT fk_renewal_user FOREIGN KEY (renewed_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alerts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_id INT UNSIGNED NOT NULL,
    valid_to DATETIME NOT NULL, -- Hạn của chu kỳ mà cảnh báo này thuộc về.
    threshold_days TINYINT UNSIGNED NOT NULL,
    status ENUM('new', 'seen') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Có valid_to: mỗi mốc tạo một lần trong một chu kỳ, và tạo lại được sau khi gia hạn.
    CONSTRAINT uq_alert_cycle UNIQUE (certificate_id, valid_to, threshold_days),
    CONSTRAINT chk_alert_threshold CHECK (threshold_days IN (30, 15, 7)),
    CONSTRAINT fk_alert_cert FOREIGN KEY (certificate_id) REFERENCES certificates (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL, -- NULL khi chưa biết là ai, ví dụ đăng nhập sai.
    action VARCHAR(50) NOT NULL,
    object_type VARCHAR(50) NULL,
    -- Cố ý không có khoá ngoại tới đối tượng: xoá chứng chỉ thì vết trong nhật ký vẫn còn.
    object_id INT UNSIGNED NULL,
    detail TEXT NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Số ngày còn lại và trạng thái được tính mỗi lần đọc, không lưu cứng, nên không thể bị cũ.
-- Thứ tự CASE là thứ tự ưu tiên: chứng chỉ đã thu hồi thì dù còn hạn cũng không được tin.
CREATE VIEW v_certificates AS
SELECT
    c.*,
    DATEDIFF(c.valid_to, NOW()) AS days_left,
    CASE
        WHEN c.is_revoked = 1 THEN 'revoked'
        WHEN c.valid_from > NOW() THEN 'not_yet_valid'
        WHEN c.valid_to < NOW() THEN 'expired'
        WHEN DATEDIFF(c.valid_to, NOW()) <= 30 THEN 'expiring'
        ELSE 'valid'
    END AS status
FROM certificates c;
