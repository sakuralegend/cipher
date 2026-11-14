-- Dữ liệu mẫu dài để xem thử và demo: thêm 41 chứng chỉ (CERT-010 đến CERT-050), cộng với 9 chứng chỉ
-- của seed.sql là đủ 50. Nạp SAU seed.sql. Tách riêng vì test dựa vào đúng 9 chứng chỉ của seed.sql.
--
-- Cách nạp:
--   mysql -u root cipher_dev < database/seed_demo.sql
--
-- File chỉ THÊM, không xoá hay sửa dòng có sẵn. Cả file nằm trong một giao dịch: chạy lần hai sẽ báo
-- trùng khoá và không dòng nào được thêm. Muốn nạp lại thì nạp lại schema.sql và seed.sql trước.
--
-- Ngày tính theo NOW(), nên trạng thái chỉ đúng lúc vừa nạp; nạp lại trước khi nộp hoặc demo.
-- Danh mục được tra theo tên chứ không theo id, vì CSDL đang chạy có thể đã thêm dòng khác.
-- Cảnh báo cố ý không chèn sẵn: hệ thống tự tạo khi mở Dashboard hoặc trang Cảnh báo.

SET NAMES utf8mb4;
SET time_zone = '+07:00';

START TRANSACTION;

INSERT INTO servers (hostname, ip_address, port, environment, owner) VALUES
    ('web-02', '192.0.2.11', 443, 'production', 'Phòng Hạ tầng'),
    ('app-01', '192.0.2.12', 443, 'production', 'Phòng Phát triển'),
    ('gateway-01', '192.0.2.13', 443, 'production', 'Phòng An ninh mạng'),
    ('noibo-01', '192.0.2.21', 8443, 'production', 'Phòng Công nghệ thông tin'),
    ('devops-01', '192.0.2.31', 8443, 'staging', 'Phòng Phát triển');

INSERT INTO domains (domain_name, server_id)
SELECT ten, (SELECT MIN(id) FROM servers WHERE hostname = may) FROM (
    SELECT 'www.congty.example' AS ten, 'web-01' AS may
    UNION ALL SELECT 'shop.congty.example', 'web-02'
    UNION ALL SELECT 'pay.congty.example', 'web-02'
    UNION ALL SELECT 'cdn.congty.example', 'web-02'
    UNION ALL SELECT 'api.congty.example', 'app-01'
    UNION ALL SELECT 'crm.congty.example', 'app-01'
    UNION ALL SELECT '*.apps.congty.example', 'app-01'
    UNION ALL SELECT 'sso.congty.example', 'gateway-01'
    UNION ALL SELECT 'remote.congty.example', 'gateway-01'
    UNION ALL SELECT 'erp.congty.example', 'noibo-01'
    UNION ALL SELECT 'hr.congty.example', 'noibo-01'
    UNION ALL SELECT 'wiki.congty.example', 'noibo-01'
    UNION ALL SELECT 'files.congty.example', 'noibo-01'
    UNION ALL SELECT 'git.congty.example', 'devops-01'
    UNION ALL SELECT 'ci.congty.example', 'devops-01'
    UNION ALL SELECT 'monitor.congty.example', 'devops-01'
    UNION ALL SELECT '*.dev.congty.example', 'beta-01'
) AS danh_sach;

INSERT INTO certificate_authorities (name, country, is_trusted) VALUES
    ('Sectigo', 'Hoa Kỳ', 1),
    ('GlobalSign', 'Bỉ', 1),
    ('ZeroSSL', 'Áo', 1),
    ('CA thử nghiệm GOST', 'Việt Nam', 0);

-- Bảng tạm để mỗi chứng chỉ gọn trên một dòng.
-- cap_cach: cấp cách đây bao nhiêu ngày, số âm là chưa tới ngày cấp. con_ngay: còn bao nhiêu ngày, số âm là đã quá hạn.
-- Chứng chỉ của CA công khai có cap_cach + con_ngay <= 200: từ 15/03/2026 Baseline Requirements chỉ cho tối đa 200 ngày
-- (THOI_HAN_TOI_DA trong src/chung_chi.php); Let's Encrypt, ZeroSSL cấp 90 ngày. Chỉ CA nội bộ, CA GOST thử nghiệm (không bị
-- luật đó ràng buộc) mới dài hơn. Trước 12/11/2026 dữ liệu cấp 365–730 ngày nên 20/50 chứng chỉ ra hạng C.
-- Chứng chỉ cố ý ra hạng xấu để làm ví dụ: F là 023, 028 (chữ ký SHA-1); C là các chứng chỉ của CA nội bộ, 041 (CA GOST thử nghiệm).
CREATE TEMPORARY TABLE demo_cc (
    ma VARCHAR(30),
    cn VARCHAR(255),
    san TEXT,
    ca VARCHAR(100),
    mien VARCHAR(255),
    cap_cach INT,
    con_ngay INT,
    thuat VARCHAR(20),
    khoa SMALLINT UNSIGNED,
    ky VARCHAR(50),
    thu_hoi TINYINT(1)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO demo_cc VALUES
    -- Sắp hết hạn, mốc 7 ngày.
    ('CERT-010', 'pay.congty.example', NULL, 'DigiCert', 'pay.congty.example', 196, 2, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    ('CERT-011', 'sso.congty.example', 'login.congty.example', 'GlobalSign', 'sso.congty.example', 193, 5, 'RSA', 3072, 'sha384WithRSAEncryption', 0),
    ('CERT-012', 'ci.congty.example', NULL, 'Let''s Encrypt', 'ci.congty.example', 83, 7, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    -- Sắp hết hạn, mốc 15 ngày.
    ('CERT-013', 'crm.congty.example', NULL, 'Sectigo', 'crm.congty.example', 189, 9, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    ('CERT-014', 'git.congty.example', 'gitlab.congty.example', 'Let''s Encrypt', 'git.congty.example', 78, 12, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    ('CERT-015', 'hr.congty.example', NULL, 'CA nội bộ', 'hr.congty.example', 350, 15, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    -- Sắp hết hạn, mốc 30 ngày.
    ('CERT-016', 'cdn.congty.example', 'static.congty.example, img.congty.example', 'ZeroSSL', 'cdn.congty.example', 72, 18, 'ECDSA', 384, 'ecdsa-with-SHA384', 0),
    ('CERT-017', 'api.congty.example', NULL, 'DigiCert', 'api.congty.example', 177, 21, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    ('CERT-018', 'wiki.congty.example', NULL, 'Let''s Encrypt', 'wiki.congty.example', 65, 25, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    ('CERT-019', 'monitor.congty.example', 'grafana.congty.example', 'Let''s Encrypt', 'monitor.congty.example', 62, 28, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    -- Đã hết hạn.
    ('CERT-020', 'files.congty.example', NULL, 'CA nội bộ', 'files.congty.example', 366, -1, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    ('CERT-021', 'old.dev.congty.example', NULL, 'Let''s Encrypt', '*.dev.congty.example', 93, -3, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    ('CERT-022', 'erp.congty.example', NULL, 'Sectigo', 'erp.congty.example', 218, -20, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    ('CERT-023', 'legacy.congty.example', NULL, 'CA nội bộ', 'www.congty.example', 775, -45, 'RSA', 1024, 'sha1WithRSAEncryption', 0),
    ('CERT-024', 'remote.congty.example', NULL, 'GlobalSign', 'remote.congty.example', 318, -120, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    -- Đã thu hồi: có chứng chỉ còn xa hạn, có chứng chỉ gần hạn, để thấy thu hồi được ưu tiên hơn.
    ('CERT-025', 'shop.congty.example', NULL, 'DigiCert', 'shop.congty.example', 100, 95, 'RSA', 2048, 'sha256WithRSAEncryption', 1),
    ('CERT-026', 'api.congty.example', NULL, 'Sectigo', 'api.congty.example', 130, 65, 'RSA', 2048, 'sha256WithRSAEncryption', 1),
    ('CERT-027', 'sso.congty.example', NULL, 'Let''s Encrypt', 'sso.congty.example', 85, 5, 'RSA', 2048, 'sha256WithRSAEncryption', 1),
    ('CERT-028', 'pay.congty.example', NULL, 'GlobalSign', 'pay.congty.example', 110, 85, 'RSA', 2048, 'sha1WithRSAEncryption', 1),
    ('CERT-029', 'test.apps.congty.example', NULL, 'CA nội bộ', '*.apps.congty.example', 400, -35, 'RSA', 2048, 'sha256WithRSAEncryption', 1),
    -- Chưa hiệu lực: đã cấp sẵn cho đợt chuyển đổi sắp tới.
    ('CERT-030', 'www.congty.example', 'congty.example', 'DigiCert', 'www.congty.example', -3, 195, 'ECDSA', 384, 'ecdsa-with-SHA384', 0),
    ('CERT-031', 'next.apps.congty.example', NULL, 'Let''s Encrypt', '*.apps.congty.example', -10, 80, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    -- Còn hiệu lực.
    ('CERT-032', '*.apps.congty.example', 'apps.congty.example', 'DigiCert', '*.apps.congty.example', 163, 35, 'RSA', 4096, 'sha384WithRSAEncryption', 0),
    ('CERT-033', 'wiki.congty.example', NULL, 'ZeroSSL', 'wiki.congty.example', 50, 40, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    ('CERT-034', 'files.congty.example', 'sftp.congty.example', 'Let''s Encrypt', 'files.congty.example', 42, 48, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    ('CERT-035', '*.dev.congty.example', NULL, 'Let''s Encrypt', '*.dev.congty.example', 35, 55, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    ('CERT-036', 'erp.congty.example', NULL, 'GlobalSign', 'erp.congty.example', 133, 65, 'RSA', 3072, 'sha384WithRSAEncryption', 0),
    ('CERT-037', 'remote.congty.example', NULL, 'Sectigo', 'remote.congty.example', 123, 75, 'RSA', 4096, 'sha384WithRSAEncryption', 0),
    ('CERT-038', 'git.congty.example', NULL, 'ZeroSSL', 'git.congty.example', 5, 85, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    ('CERT-039', 'mail.congty.example', 'imap.congty.example', 'DigiCert', 'mail.congty.example', 73, 125, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    ('CERT-040', 'shop.congty.example', 'cart.congty.example', 'GlobalSign', 'shop.congty.example', 48, 150, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    ('CERT-041', 'gost.congty.example', NULL, 'CA thử nghiệm GOST', 'sso.congty.example', 180, 185, 'GOST', 256, 'GOST R 34.11-2012', 0),
    ('CERT-042', 'pay.congty.example', NULL, 'DigiCert', 'pay.congty.example', 20, 175, 'ECDSA', 384, 'ecdsa-with-SHA384', 0),
    ('CERT-043', 'crm.congty.example', NULL, 'GlobalSign', 'crm.congty.example', 35, 160, 'RSA', 2048, 'sha256WithRSAEncryption', 0),
    ('CERT-044', 'cdn.congty.example', NULL, 'Sectigo', 'cdn.congty.example', 15, 180, 'Ed25519', 256, 'ED25519', 0),
    ('CERT-045', 'api.congty.example', 'api-v2.congty.example', 'Sectigo', 'api.congty.example', 10, 185, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    ('CERT-046', 'hr.congty.example', NULL, 'GlobalSign', 'hr.congty.example', 8, 190, 'RSA', 3072, 'sha384WithRSAEncryption', 0),
    ('CERT-047', 'sso.congty.example', NULL, 'DigiCert', 'sso.congty.example', 3, 195, 'ECDSA', 256, 'ecdsa-with-SHA256', 0),
    ('CERT-048', 'monitor.congty.example', NULL, 'CA nội bộ', 'monitor.congty.example', 100, 630, 'RSA', 4096, 'sha512WithRSAEncryption', 0),
    ('CERT-049', 'ci.congty.example', NULL, 'CA nội bộ', 'ci.congty.example', 40, 690, 'Ed25519', 256, 'ED25519', 0),
    ('CERT-050', 'www.congty.example', NULL, 'Sectigo', 'www.congty.example', 30, 165, 'RSA', 4096, 'sha384WithRSAEncryption', 0);

-- Fingerprint và serial là giá trị giả, sinh từ mã để đúng định dạng và không trùng.
INSERT INTO certificates
    (cert_code, fingerprint_sha256, serial, subject_cn, san, ca_id, domain_id,
     valid_from, valid_to, key_algorithm, key_size, signature_algorithm, is_revoked, created_by)
SELECT
    t.ma, SHA2(t.ma, 256), UPPER(LEFT(SHA2(CONCAT('serial-', t.ma), 256), 24)), t.cn, t.san, a.id, d.id,
    DATE_SUB(NOW(), INTERVAL t.cap_cach DAY), DATE_ADD(NOW(), INTERVAL t.con_ngay DAY),
    t.thuat, t.khoa, t.ky, t.thu_hoi, u.id
FROM demo_cc t
JOIN certificate_authorities a ON a.name = t.ca
JOIN domains d ON d.domain_name = t.mien
JOIN users u ON u.username = 'admin';

-- Sáu chứng chỉ đã gia hạn một lần, như nhân viên làm thật: bản cũ hết hạn sau ngày cấp lại ít hôm.
INSERT INTO renewals (certificate_id, old_valid_to, new_valid_to, old_fingerprint, old_serial, renewed_by, renewed_at, note)
SELECT
    c.id, DATE_ADD(c.valid_from, INTERVAL 7 DAY), c.valid_to, SHA2(CONCAT(c.cert_code, '-cu'), 256),
    UPPER(LEFT(SHA2(CONCAT('serial-cu-', c.cert_code), 256), 24)), u.id, c.valid_from, 'Gia hạn định kỳ'
FROM certificates c
JOIN users u ON u.username = 'nhanvien'
WHERE c.cert_code IN ('CERT-010', 'CERT-013', 'CERT-036', 'CERT-039', 'CERT-042', 'CERT-046');

DROP TEMPORARY TABLE demo_cc;

COMMIT;
