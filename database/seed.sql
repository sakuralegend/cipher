-- Dữ liệu mẫu tối thiểu. Nạp SAU schema.sql, vào CSDL vừa tạo lại. Chạy lần hai sẽ báo trùng khoá.
-- Ngày tính theo NOW(): nạp lại trước khi nộp hoặc demo là có lại đủ mốc 30/15/7.
--
-- Hai tài khoản đều có mật khẩu Cipher@2026. PHẢI đổi khi lên VPS.
-- Tên miền dùng đuôi .example và IP dải 192.0.2.x, là các dải dành riêng cho ví dụ.
-- Fingerprint là giá trị giả, sinh bằng SHA2() để đúng định dạng 64 ký tự.

SET NAMES utf8mb4;
SET time_zone = '+07:00';

INSERT INTO roles (id, name) VALUES
    (1, 'Admin'),
    (2, 'Nhân viên quản trị');

INSERT INTO users (id, username, password_hash, full_name, role_id) VALUES
    (1, 'admin', '$2y$10$EHEJOeJC57qPKDb.wGU5b.SVbUx2J0vJdfshHa16aQ7c45PeH.XPu', 'Quản trị hệ thống', 1),
    (2, 'nhanvien', '$2y$10$EHEJOeJC57qPKDb.wGU5b.SVbUx2J0vJdfshHa16aQ7c45PeH.XPu', 'Nhân viên quản trị', 2);

INSERT INTO servers (id, hostname, ip_address, port, environment, owner) VALUES
    (1, 'web-01', '192.0.2.10', 443, 'production', 'Phòng Hạ tầng'),
    (2, 'mail-01', '192.0.2.20', 465, 'production', 'Phòng Hạ tầng'),
    (3, 'test-01', '192.0.2.30', 8443, 'staging', 'Phòng Phát triển'),
    (4, 'beta-01', '192.0.2.40', 8443, 'development', 'Phòng Phát triển');

-- beta.congty.example không gắn chứng chỉ nào, để thử chặn xoá máy chủ chỉ còn tên miền.
INSERT INTO domains (id, domain_name, server_id) VALUES
    (1, 'congty.example', 1),
    (2, 'portal.congty.example', 1),
    (3, 'mail.congty.example', 2),
    (4, 'test.congty.example', 3),
    (5, 'beta.congty.example', 4);

INSERT INTO certificate_authorities (id, name, country, is_trusted) VALUES
    (1, 'Let''s Encrypt', 'Hoa Kỳ', 1),
    (2, 'DigiCert', 'Hoa Kỳ', 1),
    (3, 'CA nội bộ', 'Việt Nam', 0);

INSERT INTO certificates
    (id, cert_code, fingerprint_sha256, serial, subject_cn, san, ca_id, domain_id,
     valid_from, valid_to, key_algorithm, key_size, signature_algorithm, is_revoked, created_by)
VALUES
    (1, 'CERT-001', SHA2('CERT-001', 256), '03A1F0010001', 'congty.example', 'www.congty.example', 2, 1,
     DATE_SUB(NOW(), INTERVAL 165 DAY), DATE_ADD(NOW(), INTERVAL 200 DAY), 'RSA', 2048, 'sha256WithRSAEncryption', 0, 1),
    (2, 'CERT-002', SHA2('CERT-002', 256), '03A1F0010002', 'portal.congty.example', NULL, 1, 2,
     DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), 'ECDSA', 256, 'ecdsa-with-SHA256', 0, 1),
    (3, 'CERT-003', SHA2('CERT-003', 256), '03A1F0010003', 'mail.congty.example', 'smtp.congty.example', 1, 3,
     DATE_SUB(NOW(), INTERVAL 75 DAY), DATE_ADD(NOW(), INTERVAL 15 DAY), 'RSA', 2048, 'sha256WithRSAEncryption', 0, 1),
    (4, 'CERT-004', SHA2('CERT-004', 256), '03A1F0010004', 'test.congty.example', NULL, 1, 4,
     DATE_SUB(NOW(), INTERVAL 83 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY), 'RSA', 2048, 'sha256WithRSAEncryption', 0, 1),
    (5, 'CERT-005', SHA2('CERT-005', 256), '03A1F0010005', 'cu.congty.example', NULL, 3, 4,
     DATE_SUB(NOW(), INTERVAL 375 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 'RSA', 1024, 'sha1WithRSAEncryption', 0, 1),
    (6, 'CERT-006', SHA2('CERT-006', 256), '03A1F0010006', 'lo.congty.example', NULL, 2, 2,
     DATE_SUB(NOW(), INTERVAL 265 DAY), DATE_ADD(NOW(), INTERVAL 100 DAY), 'RSA', 2048, 'sha256WithRSAEncryption', 1, 1),
    (7, 'CERT-007', SHA2('CERT-007', 256), '03A1F0010007', 'moi.congty.example', NULL, 2, 2,
     DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 370 DAY), 'ECDSA', 384, 'ecdsa-with-SHA384', 0, 1),
    (8, 'CERT-008', SHA2('CERT-008', 256), '03A1F0010008', 'shop.congty.example', NULL, 2, 1,
     DATE_SUB(NOW(), INTERVAL 65 DAY), DATE_ADD(NOW(), INTERVAL 300 DAY), 'RSA', 4096, 'sha256WithRSAEncryption', 0, 1);

-- CERT-008 đã gia hạn một lần: dòng chứng chỉ giữ thông tin mới, bản cũ nằm ở đây.
INSERT INTO renewals (certificate_id, old_valid_to, new_valid_to, old_fingerprint, old_serial, renewed_by, renewed_at, note) VALUES
    (8, DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_ADD(NOW(), INTERVAL 300 DAY), SHA2('CERT-008-cu', 256), '03A1F0000008', 2,
     DATE_SUB(NOW(), INTERVAL 65 DAY), 'Gia hạn hằng năm');

-- Cảnh báo lấy đúng valid_to hiện tại của chứng chỉ, như cách hệ thống sẽ tạo thật.
INSERT INTO alerts (certificate_id, valid_to, threshold_days)
SELECT id, valid_to, 30 FROM certificates WHERE cert_code = 'CERT-002';
INSERT INTO alerts (certificate_id, valid_to, threshold_days)
SELECT id, valid_to, 15 FROM certificates WHERE cert_code = 'CERT-003';
INSERT INTO alerts (certificate_id, valid_to, threshold_days)
SELECT id, valid_to, 7 FROM certificates WHERE cert_code = 'CERT-004';
