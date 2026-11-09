<?php
// Chạy test: php tests/chay.php
// Không dùng thư viện. Trước MỖI test, CSDL cipher_test được nạp lại từ đầu để các test không
// ảnh hưởng nhau. Không bao giờ đụng cipher_dev. Thêm test: viết hàm tên test_..., tự được chạy.

// Khi web nằm trong htdocs, file này mở được từ trình duyệt; test chỉ được chạy từ dòng lệnh.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/nhat_ky.php';
require __DIR__ . '/../src/nguoi_dung.php';
require __DIR__ . '/../src/danh_muc.php';
require __DIR__ . '/../src/chung_chi.php';

function napLaiCsdl(PDO $pdo): void
{
    // Chặn cuối: schema.sql xoá sạch bảng, nên tuyệt đối không được chạy nhầm CSDL.
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'cipher_test') {
        throw new RuntimeException('Từ chối nạp lại: không phải CSDL cipher_test');
    }
    chayFileSql($pdo, 'schema.sql');
    chayFileSql($pdo, 'seed.sql');
}

function chayFileSql(PDO $pdo, string $tenFile): void
{
    $sql = file_get_contents(__DIR__ . '/../database/' . $tenFile);
    // Chạy từng câu, vì gửi cả file một lần thì lỗi ở câu sau có thể bị bỏ qua không báo.
    foreach (preg_split('/;[ \t]*\r?\n/', $sql) as $cauLenh) {
        if (trim($cauLenh) !== '') {
            $pdo->exec($cauLenh);
        }
    }
}

function kiem(bool $dieuKien, string $thongBao): void
{
    if (!$dieuKien) {
        throw new RuntimeException($thongBao);
    }
}

// Đạt khi CSDL từ chối vì vi phạm ràng buộc (SQLSTATE 23000), hoặc vì trigger chặn ($maLoi = '45000').
// Lỗi loại khác, như sai cú pháp, vẫn làm test đỏ, để test không xanh giả.
function phaiBiTuChoi(PDO $pdo, string $sql, string $thongBao, string $maLoi = '23000'): void
{
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        kiem($e->getCode() === $maLoi, "$thongBao: lỗi không phải loại mong đợi ($maLoi): " . $e->getMessage());
        return;
    }
    throw new RuntimeException("$thongBao: CSDL không từ chối");
}

function themChungChi(PDO $pdo, string $ma, string $fingerprint): void
{
    $pdo->exec("INSERT INTO certificates (cert_code, fingerprint_sha256, subject_cn, ca_id, domain_id,
                    valid_from, valid_to, key_algorithm, key_size, signature_algorithm, created_by)
                VALUES ('$ma', $fingerprint, 'a.congty.example', 1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR),
                    'RSA', 2048, 'sha256', 1)");
}

function trangThai(PDO $pdo, string $ma): array
{
    return $pdo->query("SELECT status, days_left FROM v_certificates WHERE cert_code = '$ma'")->fetch();
}

// Luật: Kết nối CSDL luôn ở múi giờ Việt Nam và chế độ strict, PHP cũng ở múi giờ Việt Nam.
// Vì sao: PHP XAMPP mặc định Europe/Berlin; CSDL XAMPP không strict nên âm thầm cắt dữ liệu.
// Đỏ nghĩa là: số ngày còn lại có thể sai, hoặc dữ liệu sai lọt vào mà không báo lỗi.
// Đừng: sửa bằng php.ini hay my.ini, vì máy giảng viên và VPS có cấu hình khác.
// Nhóm: CSDL, ràng buộc, dữ liệu mẫu (Ng, Nj)
function test_ket_noi_luon_o_mui_gio_viet_nam_va_che_do_strict(PDO $pdo): void
{
    kiem(date_default_timezone_get() === 'Asia/Ho_Chi_Minh', 'PHP sai múi giờ');
    // Kết nối mới, vì seed.sql đã tự SET time_zone trên $pdo và sẽ che lỗi của ketNoiCsdl().
    $ketNoiMoi = ketNoiCsdl('cipher_test');
    kiem($ketNoiMoi->query('SELECT @@session.time_zone')->fetchColumn() === '+07:00', 'CSDL sai múi giờ');
    phaiBiTuChoiDoQuaDai($ketNoiMoi);
}

function phaiBiTuChoiDoQuaDai(PDO $pdo): void
{
    $maQuaDai = str_repeat('X', 31);
    try {
        themChungChi($pdo, $maQuaDai, 'NULL');
    } catch (PDOException $e) {
        return;
    }
    throw new RuntimeException('Chuỗi quá dài vẫn được lưu: chế độ strict chưa bật');
}

// Luật: Trong một chu kỳ, mỗi mốc của một chứng chỉ chỉ có một cảnh báo.
// Vì sao: Việc tạo cảnh báo chạy lặp lại, không có ràng buộc thì người dùng nhận trùng liên tục.
// Đỏ nghĩa là: uq_alert_cycle đã mất hoặc sai cột.
// Đừng: chuyển việc chặn trùng sang PHP, vì hai lần chạy cùng lúc vẫn tạo trùng được.
// Nhóm: Cảnh báo (D6)
function test_khong_tao_canh_bao_trung_trong_cung_chu_ky(PDO $pdo): void
{
    $them = "INSERT INTO alerts (certificate_id, valid_to, threshold_days)
             SELECT id, valid_to, 30 FROM certificates WHERE cert_code = 'CERT-001'";
    $pdo->exec($them);
    phaiBiTuChoi($pdo, $them, 'Cảnh báo trùng');
}

// Luật: Sau khi gia hạn, chứng chỉ vẫn tạo lại được cảnh báo ở cùng mốc.
// Vì sao: Thiết kế gốc UNIQUE (certificate_id, threshold_days) làm chu kỳ sau không còn cảnh báo.
// Đỏ nghĩa là: chứng chỉ đã gia hạn sẽ hết hạn mà không ai được báo, và không có lỗi nào hiện ra.
// Đừng: bỏ valid_to khỏi UNIQUE, hay xoá cảnh báo cũ khi gia hạn để lách.
// Nhóm: Cảnh báo (D6)
function test_sau_gia_han_van_canh_bao_lai_duoc_cung_moc(PDO $pdo): void
{
    $them = "INSERT INTO alerts (certificate_id, valid_to, threshold_days)
             SELECT id, valid_to, 30 FROM certificates WHERE cert_code = 'CERT-001'";
    $pdo->exec($them);
    $pdo->exec("UPDATE certificates SET valid_to = DATE_ADD(valid_to, INTERVAL 1 YEAR) WHERE cert_code = 'CERT-001'");
    $pdo->exec($them);
    $soCanhBao = $pdo->query("SELECT COUNT(*) FROM alerts a JOIN certificates c ON c.id = a.certificate_id
                              WHERE c.cert_code = 'CERT-001'")->fetchColumn();
    kiem((int) $soCanhBao === 2, "Mong 2 cảnh báo, có $soCanhBao");
}

// Luật: Số ngày và trạng thái tính từ ngày tháng, ưu tiên thu hồi > chưa hiệu lực > hết hạn > sắp hết hạn.
// Vì sao: Đây là tiêu chí của đề; mốc 30 ngày phải là "sắp hết hạn", 31 ngày thì chưa.
// Đỏ nghĩa là: công thức trong v_certificates bị sửa, hoặc sai thứ tự ưu tiên.
// Đừng: lưu trạng thái thành cột, hay tính lại ở PHP thành công thức thứ hai.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_trang_thai_va_so_ngay_tinh_tu_valid_to(PDO $pdo): void
{
    $mong = [
        'CERT-001' => ['valid', 200],
        'CERT-002' => ['expiring', 30],
        'CERT-003' => ['expiring', 15],
        'CERT-004' => ['expiring', 7],
        'CERT-005' => ['expired', -10],
        'CERT-006' => ['revoked', 100],
        'CERT-007' => ['not_yet_valid', 370],
    ];
    foreach ($mong as $ma => [$status, $soNgay]) {
        $thucTe = trangThai($pdo, $ma);
        kiem($thucTe['status'] === $status, "$ma: mong $status, có {$thucTe['status']}");
        kiem((int) $thucTe['days_left'] === $soNgay, "$ma: mong $soNgay ngày, có {$thucTe['days_left']}");
    }

    $pdo->exec("UPDATE certificates SET valid_to = DATE_ADD(NOW(), INTERVAL 31 DAY) WHERE cert_code = 'CERT-002'");
    kiem(trangThai($pdo, 'CERT-002')['status'] === 'valid', 'Còn 31 ngày phải là valid');

    $pdo->exec("UPDATE certificates SET is_revoked = 1 WHERE cert_code = 'CERT-005'");
    kiem(trangThai($pdo, 'CERT-005')['status'] === 'revoked', 'Thu hồi phải được ưu tiên hơn hết hạn');
}

// Luật: Hai chứng chỉ không được có cùng fingerprint.
// Vì sao: Fingerprint nhận diện duy nhất một chứng chỉ; trùng tức là nhập một chứng chỉ hai lần.
// Đỏ nghĩa là: UNIQUE trên fingerprint_sha256 đã mất.
// Đừng: chỉ kiểm tra trùng ở PHP.
// Nhóm: CSDL, ràng buộc, dữ liệu mẫu (Ng, Nj)
function test_fingerprint_khong_duoc_trung(PDO $pdo): void
{
    phaiBiTuChoi(
        $pdo,
        "UPDATE certificates SET fingerprint_sha256 = SHA2('CERT-001', 256) WHERE cert_code = 'CERT-002'",
        'Fingerprint trùng'
    );
}

// Luật: Mã quản lý là bắt buộc; fingerprint được trống, nhưng nếu có phải ở dạng đã chuẩn hoá.
// Vì sao: Đề ghi "mã HOẶC fingerprint"; dạng 'AB:CD' và 'abcd' sẽ lọt qua UNIQUE nếu không ép.
// Đỏ nghĩa là: giảng viên không thêm được chứng chỉ khi thiếu fingerprint, hoặc UNIQUE bị lách.
// Đừng: bỏ chk_cert_fingerprint, hay đổi cột sang collation không phân biệt hoa thường.
// Nhóm: CSDL, ràng buộc, dữ liệu mẫu (Ng, Nj)
function test_ma_quan_ly_bat_buoc_con_fingerprint_duoc_trong_nhung_phai_chuan_hoa(PDO $pdo): void
{
    themChungChi($pdo, 'CERT-101', 'NULL');
    themChungChi($pdo, 'CERT-102', 'NULL');

    phaiBiTuChoi($pdo, "UPDATE certificates SET cert_code = NULL WHERE cert_code = 'CERT-101'", 'Mã trống');
    phaiBiTuChoi($pdo, "UPDATE certificates SET cert_code = 'CERT-001' WHERE cert_code = 'CERT-101'", 'Mã trùng');

    $inHoa = strtoupper(hash('sha256', 'x'));
    phaiBiTuChoi($pdo, "UPDATE certificates SET fingerprint_sha256 = '$inHoa' WHERE cert_code = 'CERT-101'", 'Chữ hoa');
    phaiBiTuChoi($pdo, "UPDATE certificates SET fingerprint_sha256 = 'AB:CD' WHERE cert_code = 'CERT-101'", 'Có dấu hai chấm');
}

// Luật: Người dùng đã có lịch sử không xoá được; người chưa có lịch sử thì xoá được.
// Vì sao: Xoá người sẽ mất dấu vết ai đã tạo, gia hạn, thao tác gì. Đề đòi "xoá" nên vẫn phải xoá được người mới.
// Đỏ nghĩa là: một khoá ngoại tới users đã đổi khỏi RESTRICT, nên có thể mất lịch sử.
// Đừng: đổi sang CASCADE hay SET NULL để cho xoá; người có lịch sử thì dùng khoá tài khoản.
// Nhóm: Người dùng (Nb)
function test_khong_xoa_duoc_nguoi_dung_da_co_lich_su(PDO $pdo): void
{
    phaiBiTuChoi($pdo, 'DELETE FROM users WHERE id = 1', 'Người đã tạo chứng chỉ');
    phaiBiTuChoi($pdo, 'DELETE FROM users WHERE id = 2', 'Người đã gia hạn');

    $themNguoi = "INSERT INTO users (id, username, password_hash, full_name, role_id)
                  VALUES (3, 'moi', 'x', 'Người mới', 2)";
    $pdo->exec($themNguoi);
    $pdo->exec("INSERT INTO audit_logs (user_id, action) VALUES (3, 'login')");
    phaiBiTuChoi($pdo, 'DELETE FROM users WHERE id = 3', 'Người có nhật ký');

    // Nhật ký không xoá được (trigger), nên thử xoá bằng một người khác chưa có nhật ký nào.
    $pdo->exec("INSERT INTO users (id, username, password_hash, full_name, role_id)
                VALUES (4, 'moi2', 'x', 'Người mới 2', 2)");
    $pdo->exec('DELETE FROM users WHERE id = 4');
    kiem((int) $pdo->query('SELECT COUNT(*) FROM users WHERE id = 4')->fetchColumn() === 0, 'Người chưa có lịch sử phải xoá được');
}

// Luật: Không xoá được vai trò, máy chủ, tên miền, CA khi còn dữ liệu phụ thuộc.
// Vì sao: Nếu CSDL cho xoá dây chuyền, xoá một máy chủ có thể làm mất hàng loạt chứng chỉ.
// Đỏ nghĩa là: một khoá ngoại đã đổi khỏi RESTRICT.
// Đừng: đổi sang CASCADE cho tiện; người dùng phải chuyển hoặc xoá dữ liệu con trước.
// Nhóm: CSDL, ràng buộc, dữ liệu mẫu (Ng, Nj)
function test_khong_xoa_duoc_danh_muc_con_du_lieu_phu_thuoc(PDO $pdo): void
{
    // Dùng người và máy chủ không có dữ liệu khác, để chắc chính khoá ngoại đang xét là thứ chặn xoá.
    $pdo->exec("INSERT INTO roles (id, name) VALUES (3, 'Vai trò thử')");
    $pdo->exec("INSERT INTO users (username, password_hash, full_name, role_id) VALUES ('thu', 'x', 'Người thử', 3)");
    phaiBiTuChoi($pdo, 'DELETE FROM roles WHERE id = 3', 'Vai trò còn người dùng');
    phaiBiTuChoi($pdo, 'DELETE FROM servers WHERE id = 4', 'Máy chủ còn tên miền');
    phaiBiTuChoi($pdo, 'DELETE FROM domains WHERE id = 1', 'Tên miền còn chứng chỉ');
    phaiBiTuChoi($pdo, 'DELETE FROM certificate_authorities WHERE id = 1', 'CA còn chứng chỉ');
}

// Luật: Xoá chứng chỉ thì xoá kèm cảnh báo và lịch sử gia hạn của nó.
// Vì sao: Cảnh báo và lịch sử không còn ý nghĩa khi không còn chứng chỉ; vết thao tác nằm ở audit_logs.
// Đỏ nghĩa là: không xoá được chứng chỉ, hoặc còn dữ liệu mồ côi.
// Đừng: gắn khoá ngoại từ audit_logs tới chứng chỉ, vì nhật ký phải sống lâu hơn đối tượng.
// Nhóm: CSDL, ràng buộc, dữ liệu mẫu (Ng, Nj)
function test_xoa_chung_chi_xoa_kem_canh_bao_va_lich_su(PDO $pdo): void
{
    $pdo->exec("INSERT INTO alerts (certificate_id, valid_to, threshold_days)
                SELECT id, valid_to, 30 FROM certificates WHERE cert_code = 'CERT-008'");
    $pdo->exec("DELETE FROM certificates WHERE cert_code = 'CERT-008'");
    kiem((int) $pdo->query('SELECT COUNT(*) FROM alerts WHERE certificate_id = 8')->fetchColumn() === 0, 'Còn cảnh báo');
    kiem((int) $pdo->query('SELECT COUNT(*) FROM renewals WHERE certificate_id = 8')->fetchColumn() === 0, 'Còn lịch sử');
}

// Luật: Dữ liệu mẫu có đủ mốc 30/15/7, mọi trạng thái, lịch sử gia hạn và tài khoản đăng nhập được.
// Vì sao: Giảng viên kiểm thử bằng dữ liệu mẫu (yêu cầu j của đề); thiếu mốc thì cảnh báo không hiện ra.
// Đỏ nghĩa là: seed.sql bị sửa làm mất một trường hợp demo.
// Đừng: viết ngày cố định trong seed, vì các mốc sẽ trôi trước khi giảng viên chấm.
// Nhóm: CSDL, ràng buộc, dữ liệu mẫu (Ng, Nj)
function test_du_lieu_mau_co_du_moc_va_trang_thai(PDO $pdo): void
{
    $trangThaiCo = $pdo->query('SELECT DISTINCT status FROM v_certificates')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['valid', 'expiring', 'expired', 'revoked', 'not_yet_valid'] as $status) {
        kiem(in_array($status, $trangThaiCo, true), "Thiếu trạng thái $status");
    }

    $soNgayCo = $pdo->query('SELECT days_left FROM v_certificates WHERE is_revoked = 0')->fetchAll(PDO::FETCH_COLUMN);
    foreach ([30, 15, 7] as $moc) {
        kiem(in_array($moc, array_map('intval', $soNgayCo), true), "Thiếu mốc $moc ngày");
    }

    kiem((int) $pdo->query('SELECT COUNT(*) FROM renewals')->fetchColumn() >= 1, 'Thiếu lịch sử gia hạn');
    kiem((int) $pdo->query("SELECT COUNT(*) FROM certificates c JOIN domains d ON d.id = c.domain_id
                            WHERE d.domain_name = 'cert.cipher.id.vn'")->fetchColumn() === 1, 'Thiếu chứng chỉ thật của web');
    kiem((int) $pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn() === 2, 'Phải có đúng 2 vai trò');

    foreach ($pdo->query('SELECT username, password_hash FROM users')->fetchAll() as $nguoi) {
        kiem(password_verify('Cipher@2026', $nguoi['password_hash']), "{$nguoi['username']} sai mật khẩu mẫu");
    }
}

function saiLienTiep(PDO $pdo, string $ten, int $soLan): void
{
    for ($i = 0; $i < $soLan; $i++) {
        dangNhap($pdo, $ten, 'mat-khau-sai');
    }
}

// Luật: Đăng nhập đúng thì vào được và số lần sai được đếm lại từ đầu.
// Vì sao: Nếu không đếm lại, người dùng thật sẽ dần bị chặn vì những lần gõ nhầm rải rác.
// Đỏ nghĩa là: dangNhap() hỏng, hoặc không còn đặt lại failed_logins.
// Đừng: đếm lại khi sai mật khẩu, vì kẻ dò sẽ không bao giờ bị chặn.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_dang_nhap_dung_thi_vao_duoc_va_dem_lai_so_lan_sai(PDO $pdo): void
{
    saiLienTiep($pdo, 'admin', 3);
    $ketQua = dangNhap($pdo, 'admin', 'Cipher@2026');
    kiem(($ketQua['id'] ?? null) === 1, 'Đăng nhập đúng phải trả về id');
    $soLanSai = $pdo->query('SELECT failed_logins FROM users WHERE id = 1')->fetchColumn();
    kiem((int) $soLanSai === 0, "Phải đếm lại về 0, đang là $soLanSai");
}

// Luật: Sai mật khẩu, sai tên, hay đang tạm chặn đều báo đúng một câu lỗi.
// Vì sao: Câu lỗi khác nhau cho kẻ dò biết tên đăng nhập nào tồn tại, hay mật khẩu vừa thử có đúng không.
// Đỏ nghĩa là: thông báo lỗi đã bị tách theo từng trường hợp.
// Đừng: viết câu thân thiện hơn cho riêng trường hợp đang tạm chặn.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_sai_mat_khau_hay_sai_ten_deu_bao_cung_mot_loi(PDO $pdo): void
{
    $saiMatKhau = dangNhap($pdo, 'admin', 'mat-khau-sai');
    $saiTen = dangNhap($pdo, 'khong-ton-tai', 'mat-khau-sai');
    kiem(($saiMatKhau['loi'] ?? null) === LOI_DANG_NHAP, 'Sai mật khẩu phải báo lỗi chung');
    kiem(($saiTen['loi'] ?? null) === LOI_DANG_NHAP, 'Sai tên phải báo lỗi chung');
}

// Luật: Tài khoản bị Admin khoá thì nhập đúng mật khẩu cũng không vào được.
// Vì sao: Đề yêu cầu đăng nhập phải kiểm tra trạng thái tài khoản (chức năng a).
// Đỏ nghĩa là: dangNhap() đã bỏ bước kiểm tra is_locked.
// Đừng: báo "đã bị khoá" khi mật khẩu sai, vì sẽ lộ tình trạng tài khoản cho người ngoài.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_tai_khoan_bi_khoa_khong_dang_nhap_duoc(PDO $pdo): void
{
    $pdo->exec('UPDATE users SET is_locked = 1 WHERE id = 2');
    $dungMatKhau = dangNhap($pdo, 'nhanvien', 'Cipher@2026');
    kiem(!isset($dungMatKhau['id']), 'Tài khoản khoá không được đăng nhập');
    kiem($dungMatKhau['loi'] !== LOI_DANG_NHAP, 'Đúng mật khẩu phải được báo là tài khoản bị khoá');
    kiem(dangNhap($pdo, 'nhanvien', 'mat-khau-sai')['loi'] === LOI_DANG_NHAP, 'Sai mật khẩu chỉ báo lỗi chung');
}

// Luật: Sai 5 lần thì tài khoản bị tạm chặn 15 phút, kể cả khi sau đó nhập đúng.
// Vì sao: QD-05 yêu cầu chặn dò mật khẩu thật, vì web chạy trên internet.
// Đỏ nghĩa là: kẻ dò có thể thử mật khẩu không giới hạn.
// Đừng: chặn theo IP, vì web sau proxy có thể chỉ thấy một IP và sẽ khoá tất cả mọi người.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_sai_5_lan_thi_tam_chan_ke_ca_khi_nhap_dung(PDO $pdo): void
{
    saiLienTiep($pdo, 'admin', 4);
    $chuaChan = $pdo->query('SELECT locked_until FROM users WHERE id = 1')->fetchColumn();
    kiem($chuaChan === null, 'Sai 4 lần chưa được chặn');

    saiLienTiep($pdo, 'admin', 1);
    $soPhut = $pdo->query('SELECT TIMESTAMPDIFF(SECOND, NOW(), locked_until) FROM users WHERE id = 1')->fetchColumn();
    kiem($soPhut > 14 * 60 && $soPhut <= 15 * 60, "Phải chặn 15 phút, còn $soPhut giây");

    $nhapDung = dangNhap($pdo, 'admin', 'Cipher@2026');
    kiem(!isset($nhapDung['id']), 'Đang chặn thì nhập đúng cũng không được vào');
    kiem($nhapDung['loi'] === LOI_DANG_NHAP, 'Đang chặn không được tiết lộ mật khẩu đúng');
}

// Luật: Hết thời gian tạm chặn thì đăng nhập lại được.
// Vì sao: Đây là chặn tạm, người dùng thật không cần Admin mở khoá.
// Đỏ nghĩa là: tài khoản bị khoá vĩnh viễn sau 5 lần sai, ai cũng có thể cố tình khoá Admin.
// Đừng: bỏ so sánh locked_until với NOW().
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_het_15_phut_thi_dang_nhap_lai_duoc(PDO $pdo): void
{
    saiLienTiep($pdo, 'admin', 5);
    $pdo->exec('UPDATE users SET locked_until = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE id = 1');
    kiem(isset(dangNhap($pdo, 'admin', 'Cipher@2026')['id']), 'Hết hạn chặn phải đăng nhập được');
}

// Luật: Người dùng bị khoá giữa phiên thì bị đẩy ra ở yêu cầu tiếp theo.
// Vì sao: Khoá tài khoản phải có tác dụng ngay, không đợi người đó tự đăng xuất.
// Đỏ nghĩa là: nguoiDungHienTai() không còn đọc is_locked từ CSDL.
// Đừng: lưu thông tin người dùng trong phiên để tiết kiệm một truy vấn.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_nguoi_dung_bi_khoa_giua_phien_thi_bi_day_ra(PDO $pdo): void
{
    $_SESSION = ['user_id' => 2];
    kiem(nguoiDungHienTai($pdo) !== null, 'Đang đăng nhập phải đọc được người dùng');
    $pdo->exec('UPDATE users SET is_locked = 1 WHERE id = 2');
    kiem(nguoiDungHienTai($pdo) === null, 'Bị khoá phải trả về null');
}

// Luật: Nhân viên quản trị không có quyền Admin; Admin thì có.
// Vì sao: Đề yêu cầu phân quyền theo vai trò; chỉ Admin được quản lý người dùng và xoá chứng chỉ.
// Đỏ nghĩa là: tên vai trò trong seed.sql hoặc hàm laAdmin() đã đổi, có thể mở quyền cho nhân viên.
// Đừng: kiểm tra quyền bằng cách chỉ ẩn nút trên giao diện.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_nhan_vien_khong_co_quyen_admin(PDO $pdo): void
{
    $_SESSION = ['user_id' => 2];
    kiem(!laAdmin(nguoiDungHienTai($pdo)), 'Nhân viên không được là Admin');
    $_SESSION = ['user_id' => 1];
    kiem(laAdmin(nguoiDungHienTai($pdo)), 'Admin phải có quyền Admin');
}

// Luật: Mã CSRF sai, thiếu, hoặc không phải chuỗi đều bị từ chối.
// Vì sao: Không có mã này, trang web lạ có thể gửi lệnh thay người đang đăng nhập.
// Đỏ nghĩa là: thao tác thay đổi dữ liệu có thể bị giả mạo.
// Đừng: so sánh bằng ==, vì để lộ mã qua thời gian trả lời.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_ma_csrf_sai_bi_tu_choi(PDO $pdo): void
{
    $_SESSION = [];
    kiem(!csrfHopLe(''), 'Phiên chưa có mã thì chuỗi rỗng phải bị từ chối');
    $ma = maCsrf();
    kiem(strlen($ma) === 64, 'Mã phải đủ 32 byte ngẫu nhiên');
    kiem(csrfHopLe($ma), 'Mã đúng phải được chấp nhận');
    kiem(!csrfHopLe('sai'), 'Mã sai phải bị từ chối');
    kiem(!csrfHopLe(null), 'Thiếu mã phải bị từ chối');
    kiem(!csrfHopLe([$ma]), 'Mảng phải bị từ chối');
}

// Luật: Mọi file trong public/admin/ đều gọi yeuCauDangNhap() hoặc yeuCauAdmin().
// Vì sao: Quên gọi ở một trang thì trang đó mở cho cả internet mà không có lỗi nào hiện ra.
// Đỏ nghĩa là: có trang quản trị vừa thêm mà chưa kiểm tra đăng nhập.
// Đừng: bỏ qua test này cho trang "chỉ đọc"; dữ liệu chứng chỉ cũng là thông tin nội bộ.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_moi_trang_quan_tri_deu_kiem_tra_dang_nhap(PDO $pdo): void
{
    $soTrang = 0;
    $cacFile = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../public/admin', FilesystemIterator::SKIP_DOTS));
    foreach ($cacFile as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $soTrang++;
        kiem(preg_match('/\byeuCau(DangNhap|Admin)\(/', codeKhongChuThich($file->getPathname())) === 1, $file->getFilename() . ' chưa kiểm tra đăng nhập');
    }
    kiem($soTrang > 0, 'Không tìm thấy trang quản trị nào, đường dẫn trong test có thể đã sai');
}

// Bỏ chú thích trước khi tìm, để một dòng chú thích không qua mặt được test quét code.
function codeKhongChuThich(string $duongDan): string
{
    return implode('', array_map(
        fn($manh) => is_array($manh) && in_array($manh[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : (is_array($manh) ? $manh[1] : $manh),
        token_get_all(file_get_contents($duongDan))
    ));
}

function duLieuNguoiMoi(array $thayDoi = []): array
{
    return array_merge([
        'username' => 'nguoi.moi',
        'full_name' => 'Người Mới',
        'role_id' => 2,
        'password' => 'MatKhau@123',
        'password2' => 'MatKhau@123',
    ], $thayDoi);
}

// Luật: Dữ liệu người dùng sai bị từ chối đúng ô: tên, họ tên, vai trò, mật khẩu ngắn, dài, nhập lại lệch.
// Vì sao: Đề yêu cầu kiểm tra bằng cả JavaScript và PHP; JavaScript có thể bị bỏ qua nên PHP là lớp thật.
// Đỏ nghĩa là: một quy tắc trong kiemTraNguoiDung() đã mất hoặc báo sai ô.
// Đừng: chỉ dựa vào thuộc tính required, pattern trên form.
// Nhóm: Người dùng (Nb)
function test_du_lieu_nguoi_dung_sai_bi_tu_choi(PDO $pdo): void
{
    $truongHop = [
        'username' => ['ab', 'có dấu', "hop.le\n", str_repeat('a', 51)],
        'full_name' => ['', str_repeat('a', 101)],
        'role_id' => [0, 99],
        'password' => ['ngan', str_repeat('a', 73)],
    ];
    foreach ($truongHop as $o => $cacGiaTriSai) {
        foreach ($cacGiaTriSai as $giaTri) {
            $thayDoi = $o === 'password' ? ['password' => $giaTri, 'password2' => $giaTri] : [$o => $giaTri];
            $loi = themNguoiDung($pdo, duLieuNguoiMoi($thayDoi));
            kiem(isset($loi[$o]), "Ô $o phải báo lỗi với giá trị: " . json_encode($giaTri));
        }
    }
    $lech = themNguoiDung($pdo, duLieuNguoiMoi(['password2' => 'KhacHan@123']));
    kiem(isset($lech['password']), 'Nhập lại lệch phải báo lỗi');
    kiem((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 2, 'Dữ liệu sai không được lưu');
}

// Luật: Tên đăng nhập trùng được báo lỗi rõ ràng, không làm hỏng trang.
// Vì sao: CSDL là nơi chặn trùng; nếu không bắt lỗi, người dùng thấy trang lỗi thay vì câu dễ hiểu.
// Đỏ nghĩa là: loiTrungTenDangNhap() không còn nhận ra mã lỗi trùng khoá.
// Đừng: bỏ UNIQUE và chỉ SELECT kiểm tra trước, vì hai yêu cầu cùng lúc vẫn tạo trùng được.
// Nhóm: Người dùng (Nb)
function test_ten_dang_nhap_trung_bao_loi_ro_rang(PDO $pdo): void
{
    kiem(isset(themNguoiDung($pdo, duLieuNguoiMoi(['username' => 'admin']))['username']), 'Thêm trùng phải báo lỗi');
    kiem(isset(suaNguoiDung($pdo, 2, duLieuNguoiMoi(['username' => 'admin', 'password' => '', 'password2' => '']), 1)['username']), 'Sửa trùng phải báo lỗi');
}

// Luật: Mật khẩu luôn được lưu dạng băm, cả khi thêm, Admin đặt lại, và tự đổi.
// Vì sao: Đề yêu cầu mật khẩu được mã hoá; lộ CSDL cũng không lộ mật khẩu.
// Đỏ nghĩa là: một đường ghi mật khẩu đang lưu bản gốc.
// Đừng: viết thêm câu UPDATE password_hash ở trang; hãy gọi capNhatMatKhau().
// Nhóm: Người dùng (Nb)
function test_mat_khau_luon_luu_dang_bam(PDO $pdo): void
{
    $doc = fn() => $pdo->query("SELECT password_hash FROM users WHERE username = 'nguoi.moi'")->fetchColumn();

    kiem(themNguoiDung($pdo, duLieuNguoiMoi()) === [], 'Phải thêm được');
    kiem(password_verify('MatKhau@123', $doc()), 'Thêm: phải lưu bản băm đúng');

    $id = (int) $pdo->query("SELECT id FROM users WHERE username = 'nguoi.moi'")->fetchColumn();
    suaNguoiDung($pdo, $id, duLieuNguoiMoi(['password' => 'DatLai@123', 'password2' => 'DatLai@123']), 1);
    kiem(password_verify('DatLai@123', $doc()), 'Admin đặt lại: phải lưu bản băm đúng');

    $truocKhiSua = $doc();
    suaNguoiDung($pdo, $id, duLieuNguoiMoi(['password' => '', 'password2' => '']), 1);
    kiem($doc() === $truocKhiSua, 'Để trống mật khẩu khi sửa phải giữ nguyên');

    doiMatKhau($pdo, $id, 'DatLai@123', 'TuDoi@1234', 'TuDoi@1234');
    kiem(password_verify('TuDoi@1234', $doc()), 'Tự đổi: phải lưu bản băm đúng');
}

// Luật: Admin không tự khoá, tự xoá, hay tự đổi vai trò của mình.
// Vì sao: Nhờ ba quy tắc này, hệ thống luôn còn ít nhất một Admin; không có cách nào cứu nếu mất hết.
// Đỏ nghĩa là: Admin có thể khoá mình khỏi hệ thống.
// Đừng: chỉ ẩn nút trên giao diện; yêu cầu POST có thể gửi thẳng.
// Nhóm: Người dùng (Nb)
function test_admin_khong_tu_khoa_tu_xoa_tu_doi_vai_tro(PDO $pdo): void
{
    kiem(khoaNguoiDung($pdo, 1, 1) !== null, 'Không được tự khoá');
    // Dùng Admin chưa có lịch sử, để chắc quy tắc này chặn, không phải khoá ngoại chặn hộ.
    themNguoiDung($pdo, duLieuNguoiMoi(['username' => 'admin2', 'role_id' => 1]));
    $idAdmin2 = (int) $pdo->query("SELECT id FROM users WHERE username = 'admin2'")->fetchColumn();
    kiem(xoaNguoiDung($pdo, $idAdmin2, $idAdmin2) !== null, 'Không được tự xoá');
    kiem(timNguoiDung($pdo, $idAdmin2) !== null, 'Admin tự xoá thất bại thì tài khoản phải còn');
    $doiVaiTro = suaNguoiDung($pdo, 1, duLieuNguoiMoi(['username' => 'admin', 'role_id' => 2, 'password' => '', 'password2' => '']), 1);
    kiem(isset($doiVaiTro['role_id']), 'Không được tự đổi vai trò');

    $dong = timNguoiDung($pdo, 1);
    kiem((int) $dong['is_locked'] === 0 && (int) $dong['role_id'] === 1 && $dong['full_name'] !== 'Người Mới', 'Tài khoản Admin không được thay đổi');

    kiem(khoaNguoiDung($pdo, 2, 1) === null, 'Khoá người khác phải được');
    kiem((int) timNguoiDung($pdo, 2)['is_locked'] === 1, 'Người bị khoá phải có is_locked = 1');
}

// Luật: Xoá người có lịch sử thì báo "chỉ có thể khoá"; người chưa có lịch sử thì xoá được.
// Vì sao: Đề đòi chức năng xoá, nhưng xoá người đã thao tác sẽ mất dấu vết.
// Đỏ nghĩa là: xoaNguoiDung() không còn nhận ra lỗi khoá ngoại, trang sẽ báo lỗi hệ thống.
// Đừng: đếm lịch sử bằng truy vấn riêng, vì thêm bảng mới là quên cập nhật.
// Nhóm: Người dùng (Nb)
function test_xoa_nguoi_co_lich_su_bao_chi_co_the_khoa(PDO $pdo): void
{
    $loi = xoaNguoiDung($pdo, 2, 1);
    kiem($loi !== null && str_contains($loi, 'chỉ có thể khoá'), 'Phải báo chỉ có thể khoá');
    kiem(timNguoiDung($pdo, 2) !== null, 'Người có lịch sử phải còn');

    themNguoiDung($pdo, duLieuNguoiMoi());
    $id = (int) $pdo->query("SELECT id FROM users WHERE username = 'nguoi.moi'")->fetchColumn();
    kiem(xoaNguoiDung($pdo, $id, 1) === null, 'Người chưa có lịch sử phải xoá được');
    kiem(timNguoiDung($pdo, $id) === null, 'Đã xoá thì không còn');
}

// Luật: Mở khoá xoá luôn trạng thái tạm chặn, và người dùng vào được ngay.
// Vì sao: Không có việc này, Admin bấm mở khoá mà người dùng vẫn phải đợi 15 phút.
// Đỏ nghĩa là: moKhoaNguoiDung() chỉ đổi is_locked.
// Đừng: bắt Admin chạy lệnh SQL để cứu tài khoản lúc demo.
// Nhóm: Người dùng (Nb)
function test_mo_khoa_xoa_ca_trang_thai_tam_chan(PDO $pdo): void
{
    saiLienTiep($pdo, 'nhanvien', 5);
    $pdo->exec('UPDATE users SET is_locked = 1 WHERE id = 2');
    moKhoaNguoiDung($pdo, 2);
    kiem(isset(dangNhap($pdo, 'nhanvien', 'Cipher@2026')['id']), 'Mở khoá xong phải đăng nhập được ngay');
}

// Luật: Đổi mật khẩu phải nhập đúng mật khẩu cũ, và mật khẩu mới phải hợp lệ.
// Vì sao: Người khác ngồi vào máy đang đăng nhập không được chiếm luôn tài khoản.
// Đỏ nghĩa là: doiMatKhau() đã bỏ bước kiểm tra mật khẩu cũ.
// Đừng: dùng hàm này cho Admin đặt lại mật khẩu người khác; đó là việc của suaNguoiDung().
// Nhóm: Người dùng (Nb)
function test_doi_mat_khau_phai_dung_mat_khau_cu(PDO $pdo): void
{
    kiem(isset(doiMatKhau($pdo, 2, 'sai', 'MoiHan@1234', 'MoiHan@1234')['password_cu']), 'Sai mật khẩu cũ phải báo lỗi');
    kiem(isset(doiMatKhau($pdo, 2, 'Cipher@2026', 'ngan', 'ngan')['password']), 'Mật khẩu mới ngắn phải báo lỗi');
    kiem(isset(dangNhap($pdo, 'nhanvien', 'Cipher@2026')['id']), 'Đổi thất bại thì mật khẩu cũ phải còn dùng được');
    kiem(doiMatKhau($pdo, 2, 'Cipher@2026', 'MoiHan@1234', 'MoiHan@1234') === [], 'Đúng mật khẩu cũ phải đổi được');
    kiem(isset(dangNhap($pdo, 'nhanvien', 'MoiHan@1234')['id']), 'Phải đăng nhập được bằng mật khẩu mới');
}

// Luật: Mọi trang quản lý người dùng đều gọi yeuCauAdmin(), không chỉ yeuCauDangNhap().
// Vì sao: Quên một trang thì nhân viên có thể tự nâng quyền hay xoá người khác, và không có lỗi nào hiện ra.
// Đỏ nghĩa là: có trang nguoi_dung*.php đang mở cho nhân viên.
// Đừng: dựa vào việc menu đã ẩn mục Người dùng.
// Nhóm: Người dùng (Nb)
function test_trang_quan_ly_nguoi_dung_chi_danh_cho_admin(PDO $pdo): void
{
    $cacTrang = glob(__DIR__ . '/../public/admin/nguoi_dung*.php');
    kiem(count($cacTrang) === 3, 'Mong 3 trang, tìm thấy ' . count($cacTrang));
    foreach ($cacTrang as $trang) {
        kiem(preg_match('/\byeuCauAdmin\(/', codeKhongChuThich($trang)) === 1, basename($trang) . ' chưa gọi yeuCauAdmin()');
    }
}

function mayChuHopLe(array $thayDoi = []): array
{
    return array_merge([
        'hostname' => 'web-02',
        'ip_address' => '192.0.2.50',
        'port' => '443',
        'environment' => 'production',
        'owner' => 'Phòng Hạ tầng',
    ], $thayDoi);
}

// Luật: Máy chủ sai IP, cổng, môi trường, hay thiếu thông tin bắt buộc đều bị từ chối đúng ô.
// Vì sao: Đề yêu cầu kiểm tra dữ liệu ở PHP; cột ENUM và kiểu số không báo được lỗi dễ hiểu.
// Đỏ nghĩa là: một quy tắc trong kiemTraMayChu() đã mất.
// Đừng: chỉ dựa vào pattern, type="number" trên form, vì JavaScript có thể bị bỏ qua.
// Nhóm: Danh mục: tên miền, máy chủ, tổ chức cấp (D2, D3, Nc)
function test_may_chu_sai_ip_cong_moi_truong_bi_tu_choi(PDO $pdo): void
{
    $truongHop = [
        'hostname' => ['', 'có dấu', "web\n"],
        'ip_address' => ['', '999.1.1.1', 'abc', '1.2.3'],
        'port' => ['', '0', '65536', '-1', '80a', '1.5'],
        'environment' => ['', 'khac'],
        'owner' => ['', str_repeat('a', 101)],
    ];
    foreach ($truongHop as $o => $cacGiaTriSai) {
        foreach ($cacGiaTriSai as $giaTri) {
            $loi = luuMayChu($pdo, null, mayChuHopLe([$o => $giaTri]));
            kiem(isset($loi[$o]), "Ô $o phải báo lỗi với giá trị: " . json_encode($giaTri));
        }
    }
    $truoc = (int) $pdo->query('SELECT COUNT(*) FROM servers')->fetchColumn();
    kiem(luuMayChu($pdo, null, mayChuHopLe()) === [], 'Máy chủ hợp lệ phải thêm được');
    kiem(luuMayChu($pdo, null, mayChuHopLe(['ip_address' => '2001:db8::1'])) === [], 'IPv6 phải được nhận');
    kiem((int) $pdo->query('SELECT COUNT(*) FROM servers')->fetchColumn() === $truoc + 2, 'Phải có thêm đúng 2 dòng');
}

// Luật: Tên miền hợp lệ gồm cả wildcard thì được nhận, và lưu chữ thường; sai định dạng thì bị từ chối.
// Vì sao: Quy tắc quá chặt sẽ chặn tên miền thật; quá lỏng thì dữ liệu rác lọt vào.
// Đỏ nghĩa là: biểu thức trong tenMienHopLe() đã đổi, hoặc không còn chuyển về chữ thường.
// Đừng: nới lỏng để cho qua một ca lạ mà không thêm ca đó vào danh sách dưới đây.
// Nhóm: Danh mục: tên miền, máy chủ, tổ chức cấp (D2, D3, Nc)
function test_ten_mien_hop_le_duoc_nhan_va_sai_dinh_dang_bi_tu_choi(PDO $pdo): void
{
    $hopLe = ['cert.cipher.id.vn', '*.cipher.id.vn', 'congty.example', 'a.co', 'xn--bcher-kva.example', 'a-b.c-d.org'];
    foreach ($hopLe as $ten) {
        kiem(tenMienHopLe($ten), "Phải nhận: $ten");
    }
    $sai = ['', 'localhost', 'cert', '1.2.3.4', '1.2.3.44', '-a.vn', 'a-.vn', 'a..vn', '.vn', 'a.vn.', 'a b.vn', '*.*.vn', 'a.*.vn', '*', "a.vn\n", 'có-dấu.vn', 'a.v', str_repeat('a', 64) . '.vn', str_repeat('a.', 126) . 'vn'];
    foreach ($sai as $ten) {
        kiem(!tenMienHopLe($ten), 'Phải từ chối: ' . json_encode($ten));
    }

    kiem(luuTenMien($pdo, null, ['domain_name' => 'API.Cipher.ID.vn', 'server_id' => 1]) === [], 'Phải thêm được');
    $luu = $pdo->query("SELECT domain_name FROM domains WHERE domain_name LIKE 'api.%'")->fetchColumn();
    kiem($luu === 'api.cipher.id.vn', "Phải lưu chữ thường, đang là $luu");
}

// Luật: Tên miền trùng và tên tổ chức trùng được báo lỗi đúng ô, không làm hỏng trang.
// Vì sao: CSDL là nơi chặn trùng; không bắt lỗi thì người dùng thấy trang lỗi thay vì câu dễ hiểu.
// Đỏ nghĩa là: loiNeuTrung() không còn nhận ra mã lỗi, hoặc mất UNIQUE trong schema.
// Đừng: bỏ UNIQUE và chỉ SELECT kiểm tra trước, vì hai yêu cầu cùng lúc vẫn tạo trùng được.
// Nhóm: Danh mục: tên miền, máy chủ, tổ chức cấp (D2, D3, Nc)
function test_ten_mien_trung_va_ten_to_chuc_trung_bao_loi_ro_rang(PDO $pdo): void
{
    kiem(isset(luuTenMien($pdo, null, ['domain_name' => 'CONGTY.example', 'server_id' => 1])['domain_name']), 'Thêm trùng tên miền, kể cả khác hoa thường');
    kiem(isset(luuTenMien($pdo, 2, ['domain_name' => 'congty.example', 'server_id' => 1])['domain_name']), 'Sửa thành trùng tên miền');
    kiem(luuTenMien($pdo, 1, ['domain_name' => 'congty.example', 'server_id' => 2]) === [], 'Giữ nguyên tên của chính nó thì không tính là trùng');

    kiem(isset(luuToChuc($pdo, null, ['name' => 'DigiCert', 'country' => 'X', 'is_trusted' => 1])['name']), 'Thêm trùng tên tổ chức');
    kiem(isset(luuToChuc($pdo, 1, ['name' => 'DigiCert', 'country' => 'X', 'is_trusted' => 1])['name']), 'Sửa thành trùng tên tổ chức');
    kiem(isset(luuToChuc($pdo, null, ['name' => '', 'country' => 'X', 'is_trusted' => 1])['name']), 'Thiếu tên tổ chức');
    kiem(isset(luuToChuc($pdo, null, ['name' => 'Y', 'country' => '', 'is_trusted' => 1])['country']), 'Thiếu quốc gia');

    kiem(luuToChuc($pdo, 1, ['name' => "Let's Encrypt", 'country' => 'Hoa Kỳ', 'is_trusted' => 0]) === [], 'Bỏ tick tin cậy phải lưu được');
    kiem((int) timToChuc($pdo, 1)['is_trusted'] === 0, 'Bỏ tick phải thành không tin cậy');
}

// Luật: Tên miền phải gắn với một máy chủ có thật.
// Vì sao: Không kiểm tra trước thì khoá ngoại từ chối và người dùng thấy trang lỗi.
// Đỏ nghĩa là: luuTenMien() đã bỏ bước kiểm tra máy chủ.
// Đừng: cho phép tên miền không có máy chủ; cột server_id là bắt buộc.
// Nhóm: Danh mục: tên miền, máy chủ, tổ chức cấp (D2, D3, Nc)
function test_ten_mien_phai_gan_voi_may_chu_co_that(PDO $pdo): void
{
    foreach ([0, 999, ''] as $idSai) {
        $loi = luuTenMien($pdo, null, ['domain_name' => 'moi.example', 'server_id' => $idSai]);
        kiem(isset($loi['server_id']), 'Máy chủ sai phải báo lỗi: ' . json_encode($idSai));
    }
}

// Luật: Xoá danh mục còn dữ liệu con thì báo lý do rõ ràng, và dữ liệu vẫn còn.
// Vì sao: Xoá dây chuyền có thể làm mất hàng loạt chứng chỉ; báo lỗi hệ thống thì người dùng không biết phải làm gì.
// Đỏ nghĩa là: xoaHoacBaoConDuLieu() không còn nhận ra lỗi khoá ngoại.
// Đừng: đếm dữ liệu con bằng truy vấn riêng, vì thêm bảng mới là quên cập nhật.
// Nhóm: Danh mục: tên miền, máy chủ, tổ chức cấp (D2, D3, Nc)
function test_xoa_danh_muc_con_du_lieu_con_bao_ly_do_ro_rang(PDO $pdo): void
{
    kiem(str_contains((string) xoaMayChu($pdo, 4), 'còn tên miền'), 'Máy chủ còn tên miền');
    kiem(timMayChu($pdo, 4) !== null, 'Máy chủ phải còn');
    kiem(str_contains((string) xoaTenMien($pdo, 1), 'còn chứng chỉ'), 'Tên miền còn chứng chỉ');
    kiem(timTenMien($pdo, 1) !== null, 'Tên miền phải còn');
    kiem(str_contains((string) xoaToChuc($pdo, 1), 'còn chứng chỉ'), 'Tổ chức còn chứng chỉ');
    kiem(timToChuc($pdo, 1) !== null, 'Tổ chức phải còn');
}

// Luật: Xoá bản ghi không tồn tại thì báo lỗi, không báo đã xoá.
// Vì sao: Hai người cùng mở trang, một người xoá trước; người sau thấy "đã xoá" sẽ tưởng chính mình vừa xoá.
// Đỏ nghĩa là: xoaHoacBaoConDuLieu() không còn xét số dòng bị xoá.
// Đừng: bỏ qua trường hợp này vì "kết quả vẫn là không còn dòng đó"; thông báo phải đúng sự thật.
// Nhóm: Danh mục: tên miền, máy chủ, tổ chức cấp (D2, D3, Nc)
function test_xoa_ban_ghi_khong_ton_tai_bao_loi(PDO $pdo): void
{
    kiem(xoaMayChu($pdo, 9999) !== null, 'Máy chủ không tồn tại');
    kiem(xoaTenMien($pdo, 0) !== null, 'Tên miền không tồn tại');
    kiem(xoaToChuc($pdo, 9999) !== null, 'Tổ chức không tồn tại');
    kiem(xoaNguoiDung($pdo, 9999, 1) !== null, 'Người dùng không tồn tại');
}

// Luật: Ô form không phải chuỗi UTF-8 hợp lệ được coi là trống, nên báo lỗi đúng ô thay vì làm chết trang.
// Vì sao: Chế độ strict khiến CSDL từ chối byte sai mã (đã gặp lỗi 500 khi thử ngày 06/11/2026).
// Đỏ nghĩa là: chuoiPost() không còn kiểm tra mã hoá hoặc kiểu dữ liệu.
// Đừng: tắt strict để CSDL tự cắt dữ liệu; lỗi sẽ thành âm thầm.
// Nhóm: CSDL, ràng buộc, dữ liệu mẫu (Ng, Nj)
function test_o_form_sai_ma_hoa_hoac_la_mang_duoc_coi_la_trong(PDO $pdo): void
{
    $_POST = ['sai_ma' => "Ph\xF2ng A", 'mang' => ['x'], 'dung' => 'Phòng A'];
    kiem(chuoiPost('sai_ma') === '', 'Byte không phải UTF-8 phải thành chuỗi rỗng');
    kiem(chuoiPost('mang') === '', 'Mảng phải thành chuỗi rỗng');
    kiem(chuoiPost('dung') === 'Phòng A', 'Tiếng Việt đúng mã phải giữ nguyên');
    kiem(chuoiPost('khong_co') === '', 'Ô không gửi phải thành chuỗi rỗng');
    $_POST = [];
}

// Luật: Danh mục không còn dữ liệu con thì xoá được.
// Vì sao: Đề yêu cầu CRUD đầy đủ; chặn xoá chỉ áp dụng khi còn dữ liệu phụ thuộc.
// Đỏ nghĩa là: một hàm xoá đang nhắm sai bảng hoặc luôn từ chối.
// Đừng: thay xoá thật bằng cột ẩn để lách khoá ngoại.
// Nhóm: Danh mục: tên miền, máy chủ, tổ chức cấp (D2, D3, Nc)
function test_danh_muc_khong_con_du_lieu_con_thi_xoa_duoc(PDO $pdo): void
{
    kiem(xoaTenMien($pdo, 5) === null, 'Tên miền không có chứng chỉ phải xoá được');
    kiem(timTenMien($pdo, 5) === null, 'Đã xoá thì không còn');
    kiem(xoaMayChu($pdo, 4) === null, 'Máy chủ hết tên miền phải xoá được');
    kiem(timMayChu($pdo, 4) === null, 'Đã xoá thì không còn');

    luuToChuc($pdo, null, ['name' => 'Tổ chức thử', 'country' => 'X', 'is_trusted' => 1]);
    $id = (int) $pdo->query("SELECT id FROM certificate_authorities WHERE name = 'Tổ chức thử'")->fetchColumn();
    kiem(xoaToChuc($pdo, $id) === null, 'Tổ chức không có chứng chỉ phải xoá được');
    kiem(timToChuc($pdo, $id) === null, 'Đã xoá thì không còn');
}

function chungChiHopLe(array $thayDoi = []): array
{
    return array_merge([
        'cert_code' => 'CERT-100',
        'fingerprint_sha256' => '',
        'serial' => '',
        'subject_cn' => 'moi.congty.example',
        'san' => '',
        'ca_id' => '1',
        'domain_id' => '1',
        'valid_from' => '2026-01-01T00:00:00',
        'valid_to' => '2027-01-01T00:00:00',
        'key_algorithm' => 'RSA',
        'key_size' => '2048',
        'signature_algorithm' => 'sha256WithRSAEncryption',
    ], $thayDoi);
}

// Luật: Chứng chỉ có dữ liệu sai bị từ chối đúng ô, và không được lưu.
// Vì sao: Đề yêu cầu kiểm tra dữ liệu ở PHP; JavaScript có thể bị bỏ qua.
// Đỏ nghĩa là: một quy tắc trong kiemTraChungChi() đã mất hoặc báo sai ô.
// Đừng: chuyển việc kiểm tra về chỉ dựa vào thuộc tính HTML hay ràng buộc CSDL.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_chung_chi_du_lieu_sai_bi_tu_choi_dung_o(PDO $pdo): void
{
    $truongHop = [
        'cert_code' => ['', 'có dấu', str_repeat('A', 31), "A1\n"],
        'fingerprint_sha256' => ['abc', str_repeat('z', 64), str_repeat('a', 63), str_repeat('a', 65)],
        'serial' => ['xyz', str_repeat('A', 129)],
        'subject_cn' => ['', str_repeat('a', 256)],
        'san' => [str_repeat('a', 5001)],
        'ca_id' => ['', '0', '999'],
        'domain_id' => ['', '0', '999'],
        'valid_from' => ['', 'abc', '2026-02-31T00:00:00', '0000-01-01T00:00:00'],
        'valid_to' => ['', 'abc', '2026-13-01T00:00:00'],
        'key_algorithm' => ['', 'DES'],
        'key_size' => ['', '0', '-1', '65536', 'abc'],
        'signature_algorithm' => ['', str_repeat('a', 51)],
    ];
    $truoc = demChungChi($pdo);
    foreach ($truongHop as $o => $cacGiaTriSai) {
        foreach ($cacGiaTriSai as $giaTri) {
            $loi = luuChungChi($pdo, null, chungChiHopLe([$o => $giaTri]), 1);
            kiem(isset($loi[$o]), "Ô $o phải báo lỗi với giá trị: " . json_encode(mb_substr($giaTri, 0, 20)));
        }
    }
    kiem(demChungChi($pdo) === $truoc, 'Dữ liệu sai không được lưu');
    kiem(luuChungChi($pdo, null, chungChiHopLe(), 1) === [], 'Dữ liệu đúng phải lưu được');
}

// Luật: Fingerprint và serial được chuẩn hoá trước khi lưu; ô trống lưu thành NULL.
// Vì sao: EE:7A và ee7a là cùng một vân tay; không chuẩn hoá thì kiểm tra trùng không phát hiện được.
// Đỏ nghĩa là: chuanHoaFingerprint(), chuanHoaSerial() đã đổi, hoặc lưu chuỗi rỗng thay vì NULL.
// Đừng: lưu nguyên dạng người dùng dán vào.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_fingerprint_va_serial_duoc_chuan_hoa_truoc_khi_luu(PDO $pdo): void
{
    $vanTay = 'EE:7A:1D:35:56:85:B8:8E:42:56:0A:57:53:0A:F5:72:A7:7D:D1:93:E2:7E:E7:BA:61:6C:22:58:69:C5:00:01';
    kiem(luuChungChi($pdo, null, chungChiHopLe(['fingerprint_sha256' => $vanTay, 'serial' => '06:6a b8']), 1) === [], 'Dạng có dấu hai chấm phải được nhận');
    $dong = $pdo->query("SELECT fingerprint_sha256, serial FROM certificates WHERE cert_code = 'CERT-100'")->fetch();
    kiem($dong['fingerprint_sha256'] === 'ee7a1d355685b88e42560a57530af572a77dd193e27ee7ba616c225869c50001', 'Fingerprint phải chuẩn hoá');
    kiem($dong['serial'] === '066AB8', 'Serial phải chuẩn hoá');

    kiem(luuChungChi($pdo, null, chungChiHopLe(['cert_code' => 'CERT-101']), 1) === [], 'Không có fingerprint phải lưu được');
    kiem(luuChungChi($pdo, null, chungChiHopLe(['cert_code' => 'CERT-102']), 1) === [], 'Nhiều chứng chỉ không có fingerprint phải lưu được');
    $dong = $pdo->query("SELECT fingerprint_sha256, serial, san FROM certificates WHERE cert_code = 'CERT-102'")->fetch();
    kiem($dong['fingerprint_sha256'] === null && $dong['serial'] === null && $dong['san'] === null, 'Ô trống phải lưu thành NULL');
}

// Luật: Mã quản lý trùng và fingerprint trùng được báo lỗi đúng ô, kể cả khi viết khác dạng.
// Vì sao: Bảng có hai cột UNIQUE; báo nhầm ô thì người dùng không biết phải sửa gì.
// Đỏ nghĩa là: phần phân biệt cột trong luuChungChi() đã hỏng.
// Đừng: bỏ UNIQUE và chỉ SELECT kiểm tra trước, vì hai yêu cầu cùng lúc vẫn tạo trùng được.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_ma_quan_ly_va_fingerprint_trung_bao_loi_ro_rang(PDO $pdo): void
{
    $loiMa = luuChungChi($pdo, null, chungChiHopLe(['cert_code' => 'CERT-001']), 1);
    kiem(isset($loiMa['cert_code']), 'Mã trùng phải báo ở ô mã');

    $vanTay = strtoupper(implode(':', str_split(hash('sha256', 'CERT-001'), 2)));
    $loiVanTay = luuChungChi($pdo, null, chungChiHopLe(['fingerprint_sha256' => $vanTay]), 1);
    kiem(isset($loiVanTay['fingerprint_sha256']), 'Fingerprint trùng khác dạng phải báo ở ô fingerprint');

    $giuNguyen = chungChiHopLe(['cert_code' => 'CERT-001', 'fingerprint_sha256' => hash('sha256', 'CERT-001')]);
    kiem(luuChungChi($pdo, 1, $giuNguyen, 1) === [], 'Sửa chứng chỉ, giữ nguyên mã của chính nó thì không tính là trùng');
}

// Luật: Ngày hết hạn phải sau ngày bắt đầu; bằng nhau cũng bị từ chối.
// Vì sao: Đây là chức năng 4 của đề; nếu không kiểm tra, CSDL từ chối và người dùng thấy trang lỗi.
// Đỏ nghĩa là: so sánh ngày trong kiemTraChungChi() đã đổi.
// Đừng: so sánh chuỗi trước khi chuanHoaNgay() đưa về cùng một dạng.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_ngay_het_han_phai_sau_ngay_bat_dau(PDO $pdo): void
{
    $truocDo = luuChungChi($pdo, null, chungChiHopLe(['valid_from' => '2027-01-02T00:00', 'valid_to' => '2027-01-01T00:00']), 1);
    kiem(isset($truocDo['valid_to']), 'Hết hạn trước ngày bắt đầu phải báo lỗi');
    $bangNhau = luuChungChi($pdo, null, chungChiHopLe(['valid_from' => '2027-01-01T00:00', 'valid_to' => '2027-01-01T00:00:00']), 1);
    kiem(isset($bangNhau['valid_to']), 'Hai ngày bằng nhau phải báo lỗi, kể cả khi viết khác dạng');

    // Giữ đúng giây của ngày thật khi sửa.
    kiem(luuChungChi($pdo, null, chungChiHopLe(['valid_from' => '2026-11-06T10:18:41', 'valid_to' => '2027-02-04T10:18:40']), 1) === [], 'Phải lưu được');
    $giay = $pdo->query("SELECT valid_to FROM certificates WHERE cert_code = 'CERT-100'")->fetchColumn();
    kiem($giay === '2027-02-04 10:18:40', "Phải giữ nguyên giây, đang là $giay");
}

// Luật: Nhân viên không xoá được chứng chỉ; Admin thì được.
// Vì sao: Xoá làm mất cả lịch sử gia hạn; nhân viên dùng Thu hồi. User chốt 06/11/2026.
// Đỏ nghĩa là: xoaChungChi() đã bỏ kiểm tra quyền, nhân viên có thể xoá bằng lệnh gửi thẳng.
// Đừng: chỉ ẩn nút Xoá trên giao diện.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_nhan_vien_khong_xoa_duoc_chung_chi_admin_thi_duoc(PDO $pdo): void
{
    $_SESSION = ['user_id' => 2];
    $nhanVien = nguoiDungHienTai($pdo);
    kiem(xoaChungChi($pdo, 1, $nhanVien) !== null, 'Nhân viên phải bị từ chối');
    kiem(timChungChi($pdo, 1) !== null, 'Chứng chỉ phải còn');

    $_SESSION = ['user_id' => 1];
    $admin = nguoiDungHienTai($pdo);
    kiem(xoaChungChi($pdo, 1, $admin) === null, 'Admin phải xoá được');
    kiem(timChungChi($pdo, 1) === null, 'Đã xoá thì không còn');
    kiem(xoaChungChi($pdo, 1, $admin) !== null, 'Xoá lần hai phải báo không còn tồn tại');
    $_SESSION = [];
}

// Luật: Thu hồi giữ nguyên dữ liệu, đổi trạng thái, và không hoàn tác được qua form sửa.
// Vì sao: Chứng chỉ thật đã thu hồi thì không thể dùng lại; muốn dùng thì cấp chứng chỉ mới.
// Đỏ nghĩa là: luuChungChi() đang ghi is_revoked, hoặc thuHoiChungChi() đã đổi.
// Đừng: thêm nút bỏ thu hồi; nhập nhầm thì tạo chứng chỉ mới.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_thu_hoi_giu_du_lieu_va_khong_hoan_tac_duoc(PDO $pdo): void
{
    kiem(thuHoiChungChi($pdo, 1) === null, 'Phải thu hồi được');
    $sau = timChungChi($pdo, 1);
    kiem($sau !== null && $sau['status'] === 'revoked' && $sau['cert_code'] === 'CERT-001', 'Dữ liệu phải còn và đổi trạng thái');
    kiem(thuHoiChungChi($pdo, 1) !== null, 'Thu hồi lần hai phải báo lỗi');
    kiem(thuHoiChungChi($pdo, 9999) !== null, 'Chứng chỉ không tồn tại phải báo lỗi');

    luuChungChi($pdo, 1, chungChiHopLe(['cert_code' => 'CERT-001', 'is_revoked' => 0]), 1);
    kiem(timChungChi($pdo, 1)['status'] === 'revoked', 'Form sửa không được hoàn tác thu hồi');
    kiem(moTaSoNgay(timChungChi($pdo, 1)) === 'Không áp dụng', 'Đã thu hồi thì không đếm ngày');
}

// Luật: Chứng chỉ đã thu hồi không sửa được, kể cả gửi thẳng tới máy chủ; trang chi tiết không hiện nút Sửa, form sửa đẩy về trang chi tiết.
// Vì sao: User 09/11/2026: thu hồi là trạng thái cuối, bản ghi chỉ còn để tra cứu. Cho sửa thì lách được luật chặn gia hạn (đổi vân tay, ngày, tên miền thành chứng chỉ khác) và mất dấu thông tin lúc thu hồi.
// Đỏ nghĩa là: luuChungChi() ghi đè bản ghi đã thu hồi, hoặc trang chi tiết, form sửa lại mở đường sửa.
// Đừng: Chỉ ẩn nút; gõ thẳng chung_chi_form.php?id= vẫn phải bị chặn ở PHP. Gõ sai trước khi thu hồi thì Admin xoá rồi thêm lại.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_chung_chi_da_thu_hoi_khong_sua_duoc(PDO $pdo): void
{
    kiem(thuHoiChungChi($pdo, 1) === null, 'Phải thu hồi được');
    $loi = luuChungChi($pdo, 1, chungChiHopLe(['cert_code' => 'CERT-001', 'subject_cn' => 'doi-ten.congty.example']), 1);
    kiem($loi !== [], 'Sửa chứng chỉ đã thu hồi phải bị từ chối');
    kiem(timChungChi($pdo, 1)['subject_cn'] === 'congty.example', 'Dữ liệu chứng chỉ đã thu hồi phải giữ nguyên');
    kiem(luuChungChi($pdo, 2, chungChiHopLe(['cert_code' => 'CERT-002', 'subject_cn' => 'portal2.congty.example']), 1) === [],
        'Chứng chỉ chưa thu hồi vẫn sửa được');

    $xem = codeKhongChuThich(__DIR__ . '/../public/admin/chung_chi_xem.php');
    kiem(preg_match('/<\?php if \(!\$cc\[\'is_revoked\'\]\): \?>\s*(?:(?!endif).)*?chung_chi_form\.php\?id=/s', $xem) === 1,
        'Nút Sửa ở trang chi tiết phải nằm trong khối chỉ hiện khi chưa thu hồi');
    $ds = codeKhongChuThich(__DIR__ . '/../public/admin/chung_chi.php');
    kiem(preg_match('/<\?php if \(\$dong\[\'status\'\] !== \'revoked\'\): \?>\s*<a[^>]*chung_chi_form\.php\?id=/', $ds) === 1,
        'Nút Sửa ở danh sách phải ẩn với chứng chỉ đã thu hồi');
    $form = codeKhongChuThich(__DIR__ . '/../public/admin/chung_chi_form.php');
    kiem(preg_match('/if \(\$giaTri\[\'is_revoked\'\]\) \{\s*datThongBao\(/', $form) === 1, 'Form sửa chứng chỉ đã thu hồi phải báo rồi đẩy về');
}

// Luật: Danh sách chia trang 20 dòng, chứng chỉ gấp nhất lên đầu, và hai trang không trùng dòng.
// Vì sao: Mục đích hệ thống là không để sót chứng chỉ sắp hết hạn; dữ liệu mẫu có 50 dòng.
// Đỏ nghĩa là: LIMIT, OFFSET trong danhSachChungChi() đã đổi.
// Đừng: lấy hết rồi cắt bằng PHP, vì dữ liệu lớn sẽ chậm.
// Nhóm: Danh sách, tìm kiếm, lọc, sắp xếp (Nd)
function test_danh_sach_phan_trang_va_chung_chi_gan_het_han_len_dau(PDO $pdo): void
{
    for ($i = 200; $i < 225; $i++) {
        $loi = luuChungChi($pdo, null, chungChiHopLe(['cert_code' => "CERT-$i", 'valid_to' => sprintf('2030-01-01T00:00:%02d', $i - 200)]), 1);
        kiem($loi === [], "Dữ liệu thử CERT-$i phải lưu được");
    }
    $trang1 = danhSachChungChi($pdo, 1);
    $trang2 = danhSachChungChi($pdo, 2);
    kiem(count($trang1) === SO_DONG_MOT_TRANG, 'Trang 1 phải đủ 20 dòng');
    kiem(count($trang2) === demChungChi($pdo) - SO_DONG_MOT_TRANG, 'Trang 2 phải có phần còn lại');
    kiem($trang1[0]['cert_code'] === 'CERT-005', 'Chứng chỉ đã hết hạn phải lên đầu');
    kiem(array_intersect(array_column($trang1, 'id'), array_column($trang2, 'id')) === [], 'Hai trang không được trùng dòng');
    kiem(danhSachChungChi($pdo, 0) === $trang1, 'Số trang không hợp lệ phải về trang 1');
}

// Luật: Mã kế tiếp không trùng mã đã có, và không bị mã tự đặt làm hỏng.
// Vì sao: Form điền sẵn mã này; trùng thì người dùng bị báo lỗi ngay khi chưa nhập gì sai.
// Đỏ nghĩa là: maChungChiKeTiep() đã đổi.
// Đừng: tính từ số dòng trong bảng, vì đã xoá dòng thì sẽ ra mã trùng.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_ma_ke_tiep_khong_trung_ma_da_co(PDO $pdo): void
{
    kiem(maChungChiKeTiep($pdo) === 'CERT-010', 'Dữ liệu mẫu tới CERT-009 thì kế tiếp là CERT-010');
    luuChungChi($pdo, null, chungChiHopLe(['cert_code' => 'WEB-999']), 1);
    kiem(maChungChiKeTiep($pdo) === 'CERT-010', 'Mã tự đặt không được ảnh hưởng');
    luuChungChi($pdo, null, chungChiHopLe(['cert_code' => 'CERT-099']), 1);
    kiem(maChungChiKeTiep($pdo) === 'CERT-100', 'Qua hai chữ số phải vẫn đúng');
    $pdo->exec("DELETE FROM certificates WHERE cert_code = 'CERT-001'");
    kiem(maChungChiKeTiep($pdo) === 'CERT-100', 'Xoá dòng khác không được làm mã bị dùng lại');
}

// Luật: Mọi trang xử lý yêu cầu POST đều gọi yeuCauCsrf().
// Vì sao: Quên một trang thì trang web lạ có thể gửi lệnh thay người đang đăng nhập, và không có lỗi nào hiện ra.
// Đỏ nghĩa là: có trang vừa thêm đọc POST mà chưa kiểm tra mã CSRF.
// Đừng: bỏ qua cho trang "ít quan trọng"; đăng xuất cũng phải có mã.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_moi_trang_nhan_post_deu_kiem_tra_csrf(PDO $pdo): void
{
    $soTrang = 0;
    $cacFile = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../public', FilesystemIterator::SKIP_DOTS));
    foreach ($cacFile as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $code = codeKhongChuThich($file->getPathname());
        if (preg_match('/\$_POST|chuoiPost\(|REQUEST_METHOD/', $code) === 1) {
            $soTrang++;
            kiem(str_contains($code, 'yeuCauCsrf('), $file->getFilename() . ' nhận POST nhưng chưa kiểm tra CSRF');
        }
    }
    kiem($soTrang >= 5, "Mong ít nhất 5 trang nhận POST, tìm thấy $soTrang: đường dẫn trong test có thể đã sai");
}

function datHanSauSoNgay(PDO $pdo, string $ma, int $soNgay): void
{
    $pdo->exec("UPDATE certificates SET valid_to = DATE_ADD(NOW(), INTERVAL $soNgay DAY) WHERE cert_code = '$ma'");
}

// Các mốc cảnh báo của một chứng chỉ trong chu kỳ hiện tại, tăng dần.
function cacMocCanhBao(PDO $pdo, string $ma): array
{
    $cau = $pdo->query("SELECT a.threshold_days FROM alerts a
                        JOIN certificates c ON c.id = a.certificate_id AND c.valid_to = a.valid_to
                        WHERE c.cert_code = '$ma' ORDER BY a.threshold_days");
    return array_map('intval', $cau->fetchAll(PDO::FETCH_COLUMN));
}

function maCuaCanhBaoDangHien(PDO $pdo): array
{
    return array_column(danhSachCanhBao($pdo), 'cert_code');
}

// Mã chứng chỉ khớp bộ lọc, đã sắp. Đồng thời kiểm tra phép đếm khớp danh sách.
function maKhopBoLoc(PDO $pdo, array $nguon): array
{
    $loc = docBoLoc($nguon);
    $ma = array_column(danhSachChungChi($pdo, 1, $loc), 'cert_code');
    kiem(demChungChi($pdo, $loc) === count($ma), 'Phép đếm phải khớp danh sách khi có lọc');
    sort($ma);
    return $ma;
}

function duLieuGiaHan(array $doi = []): array
{
    return [...['valid_to' => '2031-01-01T00:00:00', 'fingerprint_sha256' => '', 'serial' => '', 'note' => ''], ...$doi];
}

// Luật: Chứng chỉ chỉ nhận cảnh báo ở mốc gần nhất đã chạm: tối đa 7, 15, 30 ngày.
// Vì sao: Thêm chứng chỉ còn 5 ngày mà tạo cả ba mốc thì người dùng nhận ba cảnh báo cho một việc.
// Đỏ nghĩa là: biểu thức CASE trong taoCanhBao() đã đổi, chứng chỉ có thể nhận sai mốc.
// Đừng: tạo đủ các mốc đã qua để "cho chắc".
// Nhóm: Cảnh báo (D6)
function test_chung_chi_con_5_ngay_chi_tao_canh_bao_moc_7(PDO $pdo): void
{
    // Mỗi lần đổi hạn là một chu kỳ mới, nên cùng một chứng chỉ thử được mọi ranh giới.
    foreach ([5 => [7], 7 => [7], 8 => [15], 15 => [15], 16 => [30], 30 => [30], 31 => []] as $soNgay => $mongDoi) {
        datHanSauSoNgay($pdo, 'CERT-001', $soNgay);
        taoCanhBao($pdo);
        kiem(cacMocCanhBao($pdo, 'CERT-001') === $mongDoi, "Còn $soNgay ngày phải có mốc " . json_encode($mongDoi));
    }
}

// Luật: Chạy lại việc tạo cảnh báo không sinh thêm dòng và không đổi trạng thái đã xem.
// Vì sao: Việc này chạy mỗi lần mở Dashboard; sai thì cảnh báo đã xem cứ hiện lại là mới.
// Đỏ nghĩa là: phần ON DUPLICATE KEY trong taoCanhBao() đã đổi.
// Đừng: dùng INSERT IGNORE, vì nó nuốt cả các lỗi khác ngoài lỗi trùng.
// Nhóm: Cảnh báo (D6)
function test_tao_canh_bao_chay_lai_khong_sinh_them_dong(PDO $pdo): void
{
    $dem = fn(): int => (int) $pdo->query('SELECT COUNT(*) FROM alerts')->fetchColumn();
    datHanSauSoNgay($pdo, 'CERT-001', 5);
    taoCanhBao($pdo);
    $truoc = $dem();
    $pdo->exec("UPDATE alerts SET status = 'seen'");
    taoCanhBao($pdo);
    kiem($dem() === $truoc, 'Chạy lại không được sinh thêm dòng');
    kiem(demCanhBaoMoi($pdo) === 0, 'Chạy lại không được đổi đã xem thành mới');
}

// Luật: Chỉ chứng chỉ Sắp hết hạn mới được tạo cảnh báo.
// Vì sao: Chứng chỉ đã thu hồi, đã hết hạn, chưa hiệu lực thì mốc đếm ngược không còn ý nghĩa.
// Đỏ nghĩa là: điều kiện status trong taoCanhBao() đã đổi, cảnh báo sẽ nhiễu.
// Đừng: lọc theo days_left thay cho status, vì thu hồi và chưa hiệu lực vẫn có days_left nhỏ.
// Nhóm: Cảnh báo (D6)
function test_khong_tao_canh_bao_cho_chung_chi_khong_o_trang_thai_sap_het_han(PDO $pdo): void
{
    datHanSauSoNgay($pdo, 'CERT-006', 5); // Đã thu hồi.
    datHanSauSoNgay($pdo, 'CERT-007', 10); // Chưa hiệu lực: bắt đầu sau 5 ngày.
    taoCanhBao($pdo);
    foreach (['CERT-001', 'CERT-005', 'CERT-006', 'CERT-007'] as $ma) {
        kiem(cacMocCanhBao($pdo, $ma) === [], "$ma không được có cảnh báo");
    }
}

// Luật: Trang Cảnh báo bỏ chu kỳ cũ và chứng chỉ đã thu hồi, nhưng giữ chứng chỉ đã hết hạn.
// Vì sao: User chốt 07/11/2026: hết hạn là trường hợp gấp nhất; thu hồi thì không còn việc để xử lý.
// Đỏ nghĩa là: điều kiện trong CANH_BAO_DANG_XET đã đổi; số cảnh báo mới có thể đếm sai.
// Đừng: xoá cảnh báo cũ để ẩn; cảnh báo cũ phải được giữ theo SPEC.
// Nhóm: Cảnh báo (D6)
function test_trang_canh_bao_an_chu_ky_cu_va_chung_chi_thu_hoi_nhung_giu_het_han(PDO $pdo): void
{
    kiem(maCuaCanhBaoDangHien($pdo) === ['CERT-004', 'CERT-003', 'CERT-002'], 'Chứng chỉ gần hết hạn nhất phải lên đầu');

    thuHoiChungChi($pdo, 3);
    kiem(!in_array('CERT-003', maCuaCanhBaoDangHien($pdo), true), 'Đã thu hồi phải bị ẩn');

    // Hạn mới vẫn trong 30 ngày, để cảnh báo bị ẩn đúng vì khác chu kỳ, không phải vì đổi trạng thái.
    datHanSauSoNgay($pdo, 'CERT-002', 20);
    kiem(!in_array('CERT-002', maCuaCanhBaoDangHien($pdo), true), 'Chu kỳ cũ phải bị ẩn');

    $pdo->exec("UPDATE alerts a JOIN certificates c ON c.id = a.certificate_id
                SET a.valid_to = DATE_SUB(NOW(), INTERVAL 1 DAY), c.valid_to = DATE_SUB(NOW(), INTERVAL 1 DAY)
                WHERE c.cert_code = 'CERT-004'");
    kiem(maCuaCanhBaoDangHien($pdo) === ['CERT-004'], 'Đã hết hạn phải vẫn hiện');
    kiem(demCanhBaoMoi($pdo) === 1, 'Số cảnh báo mới phải khớp danh sách');
    kiem((int) $pdo->query('SELECT COUNT(*) FROM alerts')->fetchColumn() === 3, 'Cảnh báo cũ phải được giữ');
}

// Luật: Đánh dấu đã xem làm giảm số cảnh báo mới, cảnh báo vẫn còn trong danh sách.
// Vì sao: Đây là thao tác của chức năng 6; ẩn mất thì người dùng không xem lại được.
// Đỏ nghĩa là: danhDauDaXem() đã đổi, hoặc danh sách đang lọc bỏ cảnh báo đã xem.
// Đừng: xoá dòng cảnh báo khi đã xem; UNIQUE sẽ không còn gì để chặn và cảnh báo sẽ tạo lại.
// Nhóm: Cảnh báo (D6)
function test_danh_dau_da_xem_lam_giam_so_canh_bao_moi(PDO $pdo): void
{
    kiem(demCanhBaoMoi($pdo) === 3, 'Dữ liệu mẫu có 3 cảnh báo mới');
    $dau = danhSachCanhBao($pdo)[0];
    kiem(danhDauDaXem($pdo, (int) $dau['id']) === null, 'Phải đánh dấu được');
    kiem(demCanhBaoMoi($pdo) === 2, 'Số cảnh báo mới phải giảm');

    $danhSach = danhSachCanhBao($pdo);
    kiem(count($danhSach) === 3, 'Cảnh báo đã xem vẫn phải còn trong danh sách');
    kiem($danhSach[2]['id'] === $dau['id'] && $danhSach[2]['alert_status'] === 'seen', 'Đã xem phải xuống cuối');
    kiem(danhDauDaXem($pdo, (int) $dau['id']) !== null, 'Đánh dấu lần hai phải báo lỗi');
    kiem(danhDauDaXem($pdo, 9999) !== null, 'Cảnh báo không tồn tại phải báo lỗi');
}

// Luật: Gia hạn chép hạn, fingerprint, serial cũ vào lịch sử rồi cập nhật chứng chỉ; ô trống lưu NULL.
// Vì sao: Gia hạn sửa trên cùng dòng (QD-11), nên lịch sử là nơi duy nhất còn thông tin cũ.
// Đỏ nghĩa là: giaHanChungChi() đã đổi, thông tin bản cũ có thể mất vĩnh viễn.
// Đừng: giữ fingerprint cũ khi ô trống; nó không còn đúng với chứng chỉ đã cấp lại.
// Nhóm: Gia hạn (D7)
function test_gia_han_luu_thong_tin_cu_va_cap_nhat_chung_chi(PDO $pdo): void
{
    $cu = timChungChi($pdo, 1);
    $vanTayMoi = str_repeat('a', 64);
    $loi = giaHanChungChi($pdo, 1, duLieuGiaHan(['fingerprint_sha256' => strtoupper($vanTayMoi), 'serial' => 'ab:cd', 'note' => ' Cấp lại ']), 2);
    kiem($loi === [], 'Phải gia hạn được: ' . json_encode($loi));

    $moi = timChungChi($pdo, 1);
    kiem($moi['valid_to'] === '2031-01-01 00:00:00' && $moi['fingerprint_sha256'] === $vanTayMoi && $moi['serial'] === 'ABCD', 'Chứng chỉ phải nhận thông tin mới, đã chuẩn hoá');

    $lichSu = lichSuGiaHan($pdo, 1);
    kiem(count($lichSu) === 1, 'Phải có một dòng lịch sử');
    kiem($lichSu[0]['old_valid_to'] === $cu['valid_to'] && $lichSu[0]['new_valid_to'] === $moi['valid_to'], 'Lịch sử phải có hạn cũ và hạn mới');
    kiem($lichSu[0]['old_fingerprint'] === $cu['fingerprint_sha256'] && $lichSu[0]['old_serial'] === $cu['serial'], 'Lịch sử phải giữ fingerprint, serial cũ');
    kiem((int) $lichSu[0]['renewed_by'] === 2 && $lichSu[0]['note'] === 'Cấp lại', 'Lịch sử phải có người thực hiện và ghi chú');

    kiem(giaHanChungChi($pdo, 1, duLieuGiaHan(['valid_to' => '2032-01-01T00:00:00']), 2) === [], 'Gia hạn lần hai phải được');
    $sau = timChungChi($pdo, 1);
    kiem($sau['fingerprint_sha256'] === null && $sau['serial'] === null, 'Ô trống phải lưu NULL');
    kiem(lichSuGiaHan($pdo, 1)[0]['old_fingerprint'] === $vanTayMoi, 'Giá trị vừa bị bỏ phải nằm trong lịch sử');

    // Được gia hạn chứng chỉ đã hết hạn.
    kiem(giaHanChungChi($pdo, 5, duLieuGiaHan(), 2) === [], 'Chứng chỉ đã hết hạn phải gia hạn được');
    kiem(timChungChi($pdo, 5)['status'] === 'valid', 'Gia hạn xong phải về còn hiệu lực');
}

// Luật: Sau khi gia hạn, cảnh báo cũ biến khỏi trang và các mốc được tính lại từ đầu.
// Vì sao: Cảnh báo của hạn cũ không còn đúng; hạn mới vẫn phải được báo khi tới mốc.
// Đỏ nghĩa là: chứng chỉ đã gia hạn sẽ hết hạn mà không ai được báo, và không có lỗi nào hiện ra.
// Đừng: bắt người dùng đánh dấu đã xem cảnh báo cũ, hay xoá chúng khi gia hạn.
// Nhóm: Gia hạn (D7)
function test_sau_gia_han_cac_moc_canh_bao_tinh_lai_tu_dau(PDO $pdo): void
{
    kiem(cacMocCanhBao($pdo, 'CERT-004') === [7], 'Dữ liệu mẫu: CERT-004 ở mốc 7');
    $hanMoi = date('Y-m-d H:i:s', strtotime('+10 days'));
    kiem(giaHanChungChi($pdo, 4, duLieuGiaHan(['valid_to' => $hanMoi]), 1) === [], 'Phải gia hạn được');
    kiem(cacMocCanhBao($pdo, 'CERT-004') === [], 'Chu kỳ mới chưa có cảnh báo');

    taoCanhBao($pdo);
    kiem(cacMocCanhBao($pdo, 'CERT-004') === [15], 'Chu kỳ mới phải có mốc 15');
    $dong = array_values(array_filter(danhSachCanhBao($pdo), fn(array $d): bool => $d['cert_code'] === 'CERT-004'));
    kiem(count($dong) === 1 && $dong[0]['alert_status'] === 'new', 'Trang chỉ hiện cảnh báo mới của chu kỳ mới');
}

// Luật: Chứng chỉ đã thu hồi hoặc không tồn tại không gia hạn được, và không để lại lịch sử.
// Vì sao: User chốt: chứng chỉ đã thu hồi không dùng lại; muốn dùng thì thêm chứng chỉ mới.
// Đỏ nghĩa là: kiemTraGiaHan() đã bỏ kiểm tra, gửi lệnh thẳng sẽ gia hạn được chứng chỉ đã thu hồi.
// Đừng: chỉ ẩn nút Gia hạn trên giao diện.
// Nhóm: Gia hạn (D7)
function test_chan_gia_han_chung_chi_da_thu_hoi(PDO $pdo): void
{
    $truoc = timChungChi($pdo, 6)['valid_to'];
    kiem(isset(giaHanChungChi($pdo, 6, duLieuGiaHan(), 1)['chung']), 'Đã thu hồi phải bị chặn');
    kiem(timChungChi($pdo, 6)['valid_to'] === $truoc && lichSuGiaHan($pdo, 6) === [], 'Không được đổi gì');
    kiem(isset(giaHanChungChi($pdo, 9999, duLieuGiaHan(), 1)['chung']), 'Không tồn tại phải báo lỗi');
    kiem(!$pdo->inTransaction(), 'Phải kết thúc giao dịch');
}

// Luật: Hạn mới phải sau cả hạn hiện tại lẫn thời điểm gia hạn; dữ liệu sai bị từ chối đúng ô.
// Vì sao: Chứng chỉ đã hết hạn được gia hạn; không xét thời điểm hiện tại thì gia hạn xong vẫn hết hạn.
// Đỏ nghĩa là: kiemTraGiaHan() đã đổi; sai ngày hết hạn là sai tiêu chí chính của đề.
// Đừng: chỉ dựa vào chk_renewal_dates; nó không biết thời điểm hiện tại, và người dùng sẽ thấy trang lỗi.
// Nhóm: Gia hạn (D7)
function test_han_moi_phai_sau_han_cu_va_sau_hien_tai(PDO $pdo): void
{
    $hanHienTai = timChungChi($pdo, 1)['valid_to'];
    $hanCu5 = timChungChi($pdo, 5)['valid_to'];
    $truongHop = [
        [1, ['valid_to' => $hanHienTai], 'valid_to', 'Bằng hạn hiện tại'],
        [1, ['valid_to' => date('Y-m-d H:i:s', strtotime($hanHienTai . ' -1 day'))], 'valid_to', 'Trước hạn hiện tại'],
        // Sau hạn cũ của chứng chỉ đã hết hạn, nhưng vẫn ở quá khứ.
        [5, ['valid_to' => date('Y-m-d H:i:s', strtotime($hanCu5 . ' +1 day'))], 'valid_to', 'Vẫn ở quá khứ'],
        [1, ['valid_to' => ''], 'valid_to', 'Trống'],
        [1, ['valid_to' => '2031-02-31T00:00:00'], 'valid_to', 'Ngày không có thật'],
        [1, ['fingerprint_sha256' => 'abc'], 'fingerprint_sha256', 'Fingerprint sai'],
        [1, ['serial' => 'xyz'], 'serial', 'Serial sai'],
        [1, ['note' => str_repeat('a', 256)], 'note', 'Ghi chú quá dài'],
    ];
    foreach ($truongHop as [$id, $doi, $o, $moTa]) {
        $loi = giaHanChungChi($pdo, $id, duLieuGiaHan($doi), 1);
        kiem(isset($loi[$o]), "$moTa phải báo lỗi ở ô $o");
    }
    kiem(lichSuGiaHan($pdo, 1) === [] && lichSuGiaHan($pdo, 5) === [], 'Dữ liệu sai không được ghi lịch sử');
    kiem(timChungChi($pdo, 1)['valid_to'] === $hanHienTai, 'Dữ liệu sai không được đổi chứng chỉ');
}

// Luật: Gia hạn lỗi giữa chừng thì không bước nào có hiệu lực.
// Vì sao: Fingerprint trùng chỉ bị phát hiện ở bước cập nhật, sau khi lịch sử đã ghi; thiếu giao dịch thì còn dòng lịch sử giả.
// Đỏ nghĩa là: giao dịch trong giaHanChungChi() đã mất hoặc không rollBack.
// Đừng: SELECT kiểm tra trùng trước thay cho giao dịch, vì hai yêu cầu cùng lúc vẫn lọt.
// Nhóm: Gia hạn (D7)
function test_gia_han_loi_giua_chung_thi_khong_doi_gi(PDO $pdo): void
{
    $truoc = timChungChi($pdo, 1);
    $vanTayCuaNguoiKhac = strtoupper(hash('sha256', 'CERT-002'));
    $loi = giaHanChungChi($pdo, 1, duLieuGiaHan(['fingerprint_sha256' => $vanTayCuaNguoiKhac]), 1);
    kiem(isset($loi['fingerprint_sha256']), 'Fingerprint trùng phải báo đúng ô');
    kiem(lichSuGiaHan($pdo, 1) === [], 'Không được còn dòng lịch sử');
    kiem(timChungChi($pdo, 1)['valid_to'] === $truoc['valid_to'], 'Hạn không được đổi');
    kiem(!$pdo->inTransaction(), 'Phải kết thúc giao dịch');
}

// Luật: Từ khoá chứa %, _, dấu gạch ngược, tiếng Việt được hiểu đúng theo chữ, không thành ký tự đại diện.
// Vì sao: Đo 06/11/2026: LIKE '%_%' khớp cả 9 chứng chỉ, người dùng tưởng đã lọc mà thực ra thấy tất cả.
// Đỏ nghĩa là: addcslashes trong dieuKienLoc() đã mất, hoặc cột ascii báo lỗi với từ khoá có dấu.
// Đừng: bỏ các ký tự đặc biệt khỏi từ khoá; mã quản lý được phép chứa dấu gạch dưới.
// Nhóm: Danh sách, tìm kiếm, lọc, sắp xếp (Nd)
function test_tu_khoa_co_ky_tu_dac_biet_khong_khop_tat_ca(PDO $pdo): void
{
    foreach (['%', '_', '\\', ':', 'tiếng việt'] as $tuKhoa) {
        kiem(maKhopBoLoc($pdo, ['tu_khoa' => $tuKhoa]) === [], "Từ khoá $tuKhoa không được khớp gì");
    }
    luuChungChi($pdo, null, chungChiHopLe(['cert_code' => 'A_1']), 1);
    kiem(maKhopBoLoc($pdo, ['tu_khoa' => '_']) === ['A_1'], 'Dấu gạch dưới phải khớp đúng theo chữ');
    kiem(maKhopBoLoc($pdo, ['tu_khoa' => 'cert-001']) === ['CERT-001'], 'Tìm được theo mã, không phân biệt hoa thường');
    kiem(maKhopBoLoc($pdo, ['tu_khoa' => 'smtp']) === ['CERT-003'], 'Tìm được theo tên phụ');
    kiem(maKhopBoLoc($pdo, ['tu_khoa' => 'mail.congty']) === ['CERT-003'], 'Tìm được theo tên chính');
}

// Luật: Tìm fingerprint và serial được dù viết hoa, viết thường hay có dấu hai chấm.
// Vì sao: Đo 06/11/2026: cột fingerprint phân biệt hoa thường nên tìm EE7A không ra; công cụ thường in dạng EE:7A.
// Đỏ nghĩa là: từ khoá không còn qua chuẩn hoá trong dieuKienLoc(), người dùng tưởng chứng chỉ không có trong hệ thống.
// Đừng: đổi kiểu cột fingerprint; ràng buộc CHECK cần nó phân biệt hoa thường.
// Nhóm: Danh sách, tìm kiếm, lọc, sắp xếp (Nd)
function test_tim_fingerprint_duoc_du_viet_hoa_hay_co_dau_hai_cham(PDO $pdo): void
{
    foreach (['EE:7A:1D:35', 'ee7a1d35', 'EE 7A 1D 35'] as $tuKhoa) {
        kiem(in_array('CERT-009', maKhopBoLoc($pdo, ['tu_khoa' => $tuKhoa]), true), "Fingerprint $tuKhoa phải tìm thấy CERT-009");
    }
    foreach (['066AB80900C6', '06:6a:b8:09:00:c6'] as $tuKhoa) {
        kiem(in_array('CERT-009', maKhopBoLoc($pdo, ['tu_khoa' => $tuKhoa]), true), "Serial $tuKhoa phải tìm thấy CERT-009");
    }
}

// Luật: Các bộ lọc kết hợp với nhau bằng VÀ; giá trị lọc sai bị coi như chưa chọn; ngày cuối được tính trọn.
// Vì sao: Đây là chức năng nền tảng d; thống kê, báo cáo phiếu sau dùng lại đúng bộ lọc này.
// Đỏ nghĩa là: docBoLoc() hoặc dieuKienLoc() đã đổi; số liệu báo cáo sẽ sai theo.
// Đừng: ghép giá trị lọc thẳng vào câu SQL.
// Nhóm: Danh sách, tìm kiếm, lọc, sắp xếp (Nd)
function test_cac_bo_loc_ket_hop_va_gia_tri_loc_sai_bi_bo_qua(PDO $pdo): void
{
    kiem(maKhopBoLoc($pdo, ['trang_thai' => 'expired']) === ['CERT-005'], 'Lọc theo trạng thái');
    kiem(maKhopBoLoc($pdo, ['ca_id' => '3']) === ['CERT-005'], 'Lọc theo tổ chức cấp');
    kiem(maKhopBoLoc($pdo, ['domain_id' => '4']) === ['CERT-004', 'CERT-005'], 'Lọc theo tên miền');
    kiem(maKhopBoLoc($pdo, ['domain_id' => '4', 'trang_thai' => 'expired']) === ['CERT-005'], 'Hai bộ lọc phải kết hợp bằng VÀ');

    $mayChu = (int) $pdo->query('SELECT server_id FROM domains WHERE id = 6')->fetchColumn();
    $mongDoi = $pdo->query("SELECT c.cert_code FROM certificates c JOIN domains d ON d.id = c.domain_id
                            WHERE d.server_id = $mayChu ORDER BY c.cert_code")->fetchAll(PDO::FETCH_COLUMN);
    kiem($mongDoi !== [] && maKhopBoLoc($pdo, ['server_id' => (string) $mayChu]) === $mongDoi, 'Lọc theo máy chủ');

    $pdo->exec("UPDATE certificates SET valid_to = '2031-03-15 23:59:59' WHERE cert_code = 'CERT-001'");
    kiem(maKhopBoLoc($pdo, ['het_han_tu' => '2031-03-15', 'het_han_den' => '2031-03-15']) === ['CERT-001'], 'Ngày cuối phải được tính trọn');
    kiem(maKhopBoLoc($pdo, ['het_han_tu' => '2031-03-16']) === [], 'Lọc từ ngày phải loại ngày trước đó');

    $tatCa = demChungChi($pdo);
    $giaTriSai = ['tu_khoa' => ['a'], 'trang_thai' => 'abc', 'ca_id' => '1 OR 1=1', 'domain_id' => '0',
                  'server_id' => '-1', 'het_han_tu' => '2031-02-31', 'het_han_den' => "2031-01-01' --"];
    kiem(array_filter(docBoLoc($giaTriSai), fn(string $g): bool => $g !== '') === [], 'Giá trị sai phải bị coi như chưa chọn');
    kiem(count(maKhopBoLoc($pdo, $giaTriSai)) === $tatCa, 'Giá trị sai không được làm mất dòng');
}

// Mã chứng chỉ theo đúng thứ tự trả về của một cách xếp.
function maTheoCachXep(PDO $pdo, array $nguon): array
{
    return array_column(danhSachChungChi($pdo, 1, [], docSapXep($nguon)), 'cert_code');
}

// Luật: Danh sách mặc định xếp theo mức gấp, đã thu hồi xuống cuối; trong mỗi nhóm, hạn gần hơn đứng trước.
// Vì sao: User chốt 07/11/2026: xếp thuần theo ngày làm chứng chỉ đã thu hồi nằm lẫn giữa chứng chỉ đang hoạt động.
// Đỏ nghĩa là: thứ tự trong TRANG_THAI hoặc nhánh mặc định của menhDeSapXep() đã đổi.
// Đừng: viết lại thứ tự mức gấp ở chỗ khác; hàng nút và danh sách cùng lấy từ TRANG_THAI.
// Nhóm: Danh sách, tìm kiếm, lọc, sắp xếp (Nd)
function test_danh_sach_mac_dinh_xep_theo_muc_gap_thu_hoi_cuoi_cung(PDO $pdo): void
{
    chayFileSql($pdo, 'seed_demo.sql');
    $danhSach = [...danhSachChungChi($pdo, 1), ...danhSachChungChi($pdo, 2), ...danhSachChungChi($pdo, 3)];
    kiem(count($danhSach) === 50, 'Ba trang phải đủ 50 dòng');

    $thuTu = array_flip(array_keys(TRANG_THAI));
    kiem($thuTu['expired'] === 0 && $thuTu['revoked'] === 4, 'Đã hết hạn phải gấp nhất, đã thu hồi phải cuối');
    for ($i = 1; $i < count($danhSach); $i++) {
        [$truoc, $sau] = [$danhSach[$i - 1], $danhSach[$i]];
        $dungThuTu = $thuTu[$truoc['status']] < $thuTu[$sau['status']]
            || ($truoc['status'] === $sau['status'] && $truoc['valid_to'] <= $sau['valid_to']);
        kiem($dungThuTu, "{$truoc['cert_code']} không được đứng trước {$sau['cert_code']}");
    }
}

// Luật: Bấm tiêu đề cột đổi được cách xếp cả hai chiều; cột Còn lại luôn để đã thu hồi ở cuối; giá trị lạ về mặc định.
// Vì sao: Tên cột không truyền được bằng dấu ?, nên giá trị từ địa chỉ trang mà lọt vào câu SQL là bị chèn mã.
// Đỏ nghĩa là: COT_SAP_XEP, docSapXep() hoặc menhDeSapXep() đã đổi; nặng nhất là giá trị người dùng đã vào được câu SQL.
// Đừng: ghép $_GET vào ORDER BY, kể cả sau khi "lọc ký tự lạ".
// Nhóm: Danh sách, tìm kiếm, lọc, sắp xếp (Nd)
function test_sap_xep_theo_cot_va_gia_tri_la_ve_mac_dinh(PDO $pdo): void
{
    $maTang = maTheoCachXep($pdo, ['sap_xep' => 'ma']);
    kiem($maTang[0] === 'CERT-001' && end($maTang) === 'CERT-009', 'Xếp theo mã tăng dần');
    kiem(maTheoCachXep($pdo, ['sap_xep' => 'ma', 'chieu' => 'giam']) === array_reverse($maTang), 'Xếp theo mã giảm dần');
    kiem(maTheoCachXep($pdo, ['sap_xep' => 'ten'])[0] === 'CERT-009', 'Xếp theo tên: cert.cipher.id.vn đứng đầu');
    kiem(maTheoCachXep($pdo, ['sap_xep' => 'to_chuc'])[0] === 'CERT-005', 'Xếp theo tổ chức cấp: CA nội bộ đứng đầu');
    kiem(maTheoCachXep($pdo, ['sap_xep' => 'het_han', 'chieu' => 'giam'])[0] === 'CERT-007', 'Xếp theo hạn giảm dần');
    kiem(maTheoCachXep($pdo, ['sap_xep' => 'trang_thai', 'chieu' => 'giam'])[0] === 'CERT-006', 'Trạng thái giảm dần: đã thu hồi lên đầu');

    foreach (['tang', 'giam'] as $chieu) {
        $ma = maTheoCachXep($pdo, ['sap_xep' => 'con_lai', 'chieu' => $chieu]);
        kiem(end($ma) === 'CERT-006', "Cột Còn lại, chiều $chieu: đã thu hồi phải ở cuối");
    }
    kiem(maTheoCachXep($pdo, ['sap_xep' => 'con_lai', 'chieu' => 'giam'])[0] === 'CERT-007', 'Cột Còn lại giảm dần: còn lâu nhất lên đầu');

    $macDinh = array_column(danhSachChungChi($pdo, 1), 'cert_code');
    foreach ([['sap_xep' => 'c.id; DROP TABLE users'], ['sap_xep' => ['ma']], ['sap_xep' => 'valid_to'], []] as $giaTriLa) {
        kiem(docSapXep($giaTriLa) === ['sap_xep' => '', 'chieu' => 'tang'], 'Cột lạ phải về mặc định');
        kiem(maTheoCachXep($pdo, $giaTriLa) === $macDinh, 'Cột lạ phải cho thứ tự mặc định');
    }
    kiem(docSapXep(['sap_xep' => 'ma', 'chieu' => 'DESC; --']) === ['sap_xep' => 'ma', 'chieu' => 'tang'], 'Chiều lạ phải về tăng dần');
}

// Luật: Số trên hàng nút trạng thái khớp số dòng thật, theo đúng thứ tự mức gấp, và tôn trọng các bộ lọc khác.
// Vì sao: Số lệch danh sách làm người dùng tin sai về số chứng chỉ gấp, mà không có lỗi nào hiện ra.
// Đỏ nghĩa là: demTheoTrangThai() đã đổi, hoặc không còn dùng chung dieuKienLoc() với danh sách.
// Đừng: đếm bằng PHP từ trang đang hiện, vì danh sách đã bị chia trang.
// Nhóm: Danh sách, tìm kiếm, lọc, sắp xếp (Nd)
function test_so_tren_nut_trang_thai_khop_danh_sach_va_giu_bo_loc_khac(PDO $pdo): void
{
    chayFileSql($pdo, 'seed_demo.sql');
    $so = demTheoTrangThai($pdo);
    kiem(array_keys($so) === array_keys(TRANG_THAI), 'Phải đủ mọi trạng thái, đúng thứ tự mức gấp');
    kiem(array_sum($so) === 50, 'Tổng các nút phải bằng tổng chứng chỉ');
    foreach ($so as $trangThai => $soDong) {
        kiem($soDong === demChungChi($pdo, ['trang_thai' => $trangThai]), "Số của $trangThai phải khớp danh sách");
    }

    // Đang chọn một trạng thái và lọc theo tổ chức cấp: số vẫn đếm mọi trạng thái, trong tổ chức đó.
    $idToChuc = (int) $pdo->query("SELECT id FROM certificate_authorities WHERE name = 'CA nội bộ'")->fetchColumn();
    $loc = docBoLoc(['ca_id' => (string) $idToChuc, 'trang_thai' => 'expired']);
    $soLoc = demTheoTrangThai($pdo, $loc);
    kiem(array_sum($soLoc) === demChungChi($pdo, ['ca_id' => (string) $idToChuc]), 'Số phải theo bộ lọc khác, bỏ qua trạng thái đang chọn');
    kiem($soLoc['expired'] === demChungChi($pdo, $loc), 'Số của nút đang chọn phải khớp danh sách đang hiện');
    kiem($soLoc['not_yet_valid'] === 0, 'Trạng thái không có dòng nào phải hiện số 0');
}

// Luật: Khi chưa có chứng chỉ nào, danh sách, số đếm, cảnh báo và mã kế tiếp vẫn đúng, không lỗi.
// Vì sao: User chỉ ra 07/11/2026: web đi vào hoạt động thật bắt đầu từ 0 dòng, còn các test khác đều chạy trên dữ liệu mẫu.
// Đỏ nghĩa là: một phép tính đang giả định có dữ liệu; người dùng đầu tiên sẽ gặp lỗi ngay khi vào web.
// Đừng: chèn sẵn dữ liệu vào CSDL thật để né trường hợp này.
// Nhóm: Danh sách, tìm kiếm, lọc, sắp xếp (Nd)
function test_he_thong_chua_co_chung_chi_van_chay_dung(PDO $pdo): void
{
    $pdo->exec('DELETE FROM certificates');
    kiem(demChungChi($pdo) === 0 && danhSachChungChi($pdo, 1) === [], 'Danh sách phải rỗng');
    kiem(array_sum(demTheoTrangThai($pdo)) === 0 && array_keys(demTheoTrangThai($pdo)) === array_keys(TRANG_THAI), 'Mọi nút trạng thái phải có số 0');
    taoCanhBao($pdo);
    kiem(danhSachCanhBao($pdo) === [] && demCanhBaoMoi($pdo) === 0, 'Không được có cảnh báo');
    kiem(maChungChiKeTiep($pdo) === 'CERT-001', 'Chứng chỉ đầu tiên phải có mã CERT-001');
    kiem(danhSachChungChi($pdo, 1, docBoLoc(['tu_khoa' => 'a', 'trang_thai' => 'expired']), docSapXep(['sap_xep' => 'con_lai'])) === [], 'Lọc và xếp trên danh sách rỗng không được lỗi');

    kiem(chungChiSapHetHan($pdo) === [] && tatCaChungChi($pdo) === [], 'Dashboard và báo cáo phải rỗng');
    kiem(demTheoNhom($pdo, 'to_chuc') === [] && demTheoNhom($pdo, 'thuat_toan') === [], 'Thống kê nhóm phải rỗng');
    $theoThang = hetHanTheoThang($pdo);
    kiem(count($theoThang) === 12 && array_sum($theoThang) === 0, 'Vẫn đủ 12 tháng, mọi tháng là 0');
}

// Luật: Bảng Cần xử lý ngay chỉ gồm chứng chỉ sắp hết hạn, tối đa 10 dòng, gần hạn nhất trước.
// Vì sao: Chứng chỉ quá hạn lâu đứng đầu sẽ che mất các chứng chỉ còn cứu được; chúng đã có thẻ số riêng.
// Đỏ nghĩa là: chungChiSapHetHan() đã đổi, người quản trị có thể không thấy chứng chỉ sắp hết hạn gấp nhất.
// Đừng: gộp chứng chỉ đã quá hạn vào bảng này.
// Nhóm: Dashboard, thống kê, báo cáo (D8, D9, Ne, Nh)
function test_dashboard_chi_liet_ke_10_chung_chi_sap_het_han_gan_nhat(PDO $pdo): void
{
    chayFileSql($pdo, 'seed_demo.sql');
    $dong = chungChiSapHetHan($pdo);
    kiem(count($dong) === 10, 'Phải đúng 10 dòng dù có 13 chứng chỉ sắp hết hạn');
    kiem(array_unique(array_column($dong, 'status')) === ['expiring'], 'Chỉ gồm chứng chỉ sắp hết hạn');
    $han = array_column($dong, 'valid_to');
    $daSap = $han;
    sort($daSap);
    kiem($han === $daSap && $dong[0]['cert_code'] === 'CERT-010', 'Gần hạn nhất phải đứng đầu');
}

// Luật: Thống kê theo tổ chức cấp và thuật toán có tổng khớp số chứng chỉ, nhiều nhất trước, và theo bộ lọc.
// Vì sao: Các biểu đồ cùng một trang mà tổng lệch nhau thì người đọc không tin được số nào.
// Đỏ nghĩa là: demTheoNhom() không còn dùng chung dieuKienLoc(), hoặc demTheoTrangThai() bỏ sót trạng thái đang chọn.
// Đừng: nhận tên cột từ địa chỉ trang; chỉ khoá trong NHOM_THONG_KE mới vào được câu SQL.
// Nhóm: Dashboard, thống kê, báo cáo (D8, D9, Ne, Nh)
function test_thong_ke_to_chuc_va_thuat_toan_khop_tong_va_theo_bo_loc(PDO $pdo): void
{
    chayFileSql($pdo, 'seed_demo.sql');
    foreach ([[], docBoLoc(['trang_thai' => 'revoked']), docBoLoc(['tu_khoa' => 'sso'])] as $loc) {
        $tong = demChungChi($pdo, $loc);
        kiem($tong > 0, 'Dữ liệu thử phải có dòng');
        foreach (['to_chuc', 'thuat_toan'] as $nhom) {
            $so = demTheoNhom($pdo, $nhom, $loc);
            kiem(array_sum($so) === $tong, "Tổng của $nhom phải bằng $tong");
            $daSap = $so;
            arsort($daSap);
            kiem(array_values($so) === array_values($daSap), "$nhom phải xếp nhiều nhất trước");
        }
        kiem(array_sum(demTheoTrangThai($pdo, $loc, true)) === $tong, 'Biểu đồ trạng thái phải khớp tổng, kể cả khi đang chọn một trạng thái');
    }
    kiem(demTheoNhom($pdo, 'thuat_toan')['GOST'] === 1, 'Đếm đúng theo thuật toán');
}

// Luật: Biểu đồ hết hạn theo tháng đủ 12 tháng liên tiếp, không tính đã thu hồi và đã quá hạn.
// Vì sao: Biểu đồ dùng để dự báo việc gia hạn; chứng chỉ đã thu hồi không cần gia hạn nữa.
// Đỏ nghĩa là: hetHanTheoThang() đã đổi, dự báo khối lượng gia hạn sẽ sai.
// Đừng: bỏ các tháng có số 0; trục thời gian thiếu tháng làm người đọc hiểu sai.
// Nhóm: Dashboard, thống kê, báo cáo (D8, D9, Ne, Nh)
function test_het_han_theo_thang_du_12_thang_bo_thu_hoi_va_da_qua_han(PDO $pdo): void
{
    $homNay = date('Y-m-d H:i:s');
    $thangNay = date('Y-m') . '-01';
    $pdo->exec('DELETE FROM certificates');
    $themHan = function (string $ma, string $han, int $thuHoi = 0) use ($pdo): void {
        kiem(luuChungChi($pdo, null, chungChiHopLe(['cert_code' => $ma, 'valid_from' => '2020-01-01T00:00', 'valid_to' => $han]), 1) === [], "Phải lưu được $ma");
        $pdo->exec("UPDATE certificates SET is_revoked = $thuHoi WHERE cert_code = '$ma'");
    };
    $trongThang3 = date('Y-m-d\TH:i', strtotime("$thangNay +2 month +5 day"));
    $themHan('A', $trongThang3);
    $themHan('B', $trongThang3);
    $themHan('THU-HOI', $trongThang3, 1);
    $themHan('QUA-HAN', date('Y-m-d\TH:i', strtotime('-1 day')));
    $themHan('NGOAI-12-THANG', date('Y-m-d\TH:i', strtotime("$thangNay +12 month +1 day")));

    $so = hetHanTheoThang($pdo, [], $homNay);
    kiem(count($so) === 12 && array_key_first($so) === date('Y-m'), 'Phải đủ 12 tháng, bắt đầu từ tháng này');
    kiem($so[date('Y-m', strtotime("$thangNay +2 month"))] === 2, 'Không được tính chứng chỉ đã thu hồi');
    kiem(array_sum($so) === 2, 'Không được tính đã quá hạn và ngoài 12 tháng');
    kiem(array_sum(hetHanTheoThang($pdo, docBoLoc(['tu_khoa' => 'zzz']), $homNay)) === 0, 'Phải theo bộ lọc');
}

// Luật: Hôm nay là ngày 31 thì 12 tháng vẫn liên tiếp, không nhảy cóc qua tháng ngắn.
// Vì sao: Trong PHP, 31/01 cộng 1 tháng ra 03/03, nên tháng 2 biến mất khỏi biểu đồ mà không có lỗi nào.
// Đỏ nghĩa là: hetHanTheoThang() không còn tính từ ngày đầu tháng.
// Đừng: dùng strtotime('+1 month') từ ngày hiện tại.
// Nhóm: Dashboard, thống kê, báo cáo (D8, D9, Ne, Nh)
function test_het_han_theo_thang_khong_nhay_coc_khi_hom_nay_la_ngay_31(PDO $pdo): void
{
    $thang = array_keys(hetHanTheoThang($pdo, [], '2027-01-31 12:00:00'));
    $mongDoi = ['2027-01', '2027-02', '2027-03', '2027-04', '2027-05', '2027-06', '2027-07', '2027-08', '2027-09', '2027-10', '2027-11', '2027-12'];
    kiem($thang === $mongDoi, 'Sai dãy tháng: ' . implode(', ', $thang));
    kiem(count(array_keys(hetHanTheoThang($pdo, [], '2026-12-31 23:59:59'))) === 12, 'Qua năm mới vẫn đủ 12 tháng');
}

// Luật: Ô CSV bắt đầu bằng = + - @ bị vô hiệu hoá, kể cả khi có khoảng trắng đầu ô; dữ liệu thường giữ nguyên.
// Vì sao: Tên chứng chỉ do người dùng nhập; Excel chạy ô đó như công thức khi người khác mở báo cáo.
// Đỏ nghĩa là: oCsvAnToan() đã đổi, báo cáo CSV có thể chạy lệnh trên máy người mở.
// Đừng: xoá các ký tự này khỏi dữ liệu; báo cáo phải giữ đúng nội dung.
// Nhóm: Dashboard, thống kê, báo cáo (D8, D9, Ne, Nh)
function test_csv_vo_hieu_hoa_cong_thuc_va_giu_nguyen_du_lieu_thuong(PDO $pdo): void
{
    foreach (['=1+1', '+1', '-1', '@SUM(A1)', " =1", "\t=1", "\r\n=1"] as $giaTri) {
        kiem(oCsvAnToan($giaTri) === "'" . $giaTri, 'Phải vô hiệu hoá: ' . json_encode($giaTri));
    }
    foreach (['CERT-001', 'a=b', 'Let\'s Encrypt', 'tiếng Việt', '', '1024'] as $giaTri) {
        kiem(oCsvAnToan($giaTri) === $giaTri, 'Phải giữ nguyên: ' . json_encode($giaTri, JSON_UNESCAPED_UNICODE));
    }
    kiem(oCsvAnToan(null) === '', 'NULL thành ô trống');

    $pdo->exec("UPDATE certificates SET subject_cn = '=cmd()' WHERE cert_code = 'CERT-001'");
    $dong = dongCsv(timDongBaoCao($pdo, 'CERT-001'));
    kiem(count($dong) === count(COT_CSV), 'Số ô phải khớp số cột');
    kiem($dong[0] === 'CERT-001' && $dong[1] === "'=cmd()", 'Mọi ô phải qua lớp an toàn');
    kiem($dong[9] === 'Còn hiệu lực', 'Trạng thái phải là chữ tiếng Việt');
}

// Luật: Ngày trong CSV ở dạng năm-tháng-ngày, không kèm giờ.
// Vì sao: User mở CSV thấy ##### vì ngày kèm giờ dài hơn cột; dạng 09/11/2026 còn bị máy định dạng Mỹ đọc thành 11 tháng 9.
// Đỏ nghĩa là: dongCsv() đã đổi dạng ngày, hạn chứng chỉ trong báo cáo có thể bị đọc sai mà không báo gì.
// Đừng: đổi sang dạng ngày/tháng/năm cho quen mắt; bảng tính tự hiện theo định dạng của máy người mở.
// Nhóm: Dashboard, thống kê, báo cáo (D8, D9, Ne, Nh)
function test_csv_ngay_dang_nam_thang_ngay_khong_nham_ngay_thang(PDO $pdo): void
{
    $pdo->exec("UPDATE certificates SET valid_from = '2026-02-03 08:30:00', valid_to = '2027-09-11 23:59:59' WHERE cert_code = 'CERT-001'");
    $dong = dongCsv(timDongBaoCao($pdo, 'CERT-001'));
    kiem($dong[6] === '2026-02-03', 'Sai ngày bắt đầu: ' . $dong[6]);
    kiem($dong[7] === '2027-09-11', 'Sai ngày hết hạn: ' . $dong[7]);
}

function timDongBaoCao(PDO $pdo, string $ma): array
{
    foreach (tatCaChungChi($pdo) as $dong) {
        if ($dong['cert_code'] === $ma) {
            return $dong;
        }
    }
    throw new RuntimeException("Không có $ma");
}

// Luật: Báo cáo lấy đủ kết quả, không bị chia trang, theo đúng bộ lọc và cách xếp đang chọn.
// Vì sao: Báo cáo chỉ có 20 dòng đầu mà không báo gì thì người đọc tin là đủ.
// Đỏ nghĩa là: tatCaChungChi() đã có LIMIT, hoặc không dùng chung dieuKienLoc(), menhDeSapXep() với danh sách.
// Đừng: gọi danhSachChungChi() lặp từng trang; dữ liệu đổi giữa chừng sẽ trùng hoặc thiếu dòng.
// Nhóm: Dashboard, thống kê, báo cáo (D8, D9, Ne, Nh)
function test_xuat_bao_cao_lay_du_ket_qua_khong_chia_trang_va_theo_bo_loc(PDO $pdo): void
{
    chayFileSql($pdo, 'seed_demo.sql');
    kiem(count(tatCaChungChi($pdo)) === 50, 'Phải đủ 50 dòng, không dừng ở 20');
    $loc = docBoLoc(['trang_thai' => 'revoked']);
    $thuHoi = tatCaChungChi($pdo, $loc);
    kiem(count($thuHoi) === demChungChi($pdo, $loc) && array_unique(array_column($thuHoi, 'status')) === ['revoked'], 'Phải theo bộ lọc');

    $giam = docSapXep(['sap_xep' => 'ma', 'chieu' => 'giam']);
    kiem(tatCaChungChi($pdo, [], $giam)[0]['cert_code'] === 'CERT-050', 'Phải theo cách xếp đang chọn');
    kiem(array_column(array_slice(tatCaChungChi($pdo), 0, 20), 'id') === array_column(danhSachChungChi($pdo, 1), 'id'), 'Thứ tự mặc định phải giống danh sách trên web');
    kiem(!empty(tatCaChungChi($pdo)[0]['server_name']), 'Báo cáo phải có tên máy chủ');
}

// Luật: seed_demo.sql nạp được sau seed.sql, đủ 50 chứng chỉ, đủ mọi trạng thái và ba mốc cảnh báo; chạy lần hai không thêm gì.
// Vì sao: File này nạp vào web đang chạy để xem thử và demo; nạp hỏng giữa chừng thì danh mục thừa nằm lại trong CSDL.
// Đỏ nghĩa là: một dòng dữ liệu sai ràng buộc, hoặc dòng tra tên danh mục không khớp nên âm thầm bị bỏ.
// Đừng: gộp vào seed.sql, vì nhiều test dựa vào đúng 9 chứng chỉ của file đó.
// Nhóm: CSDL, ràng buộc, dữ liệu mẫu (Ng, Nj)
function test_du_lieu_demo_du_50_chung_chi_du_trang_thai_va_khong_nap_trung(PDO $pdo): void
{
    chayFileSql($pdo, 'seed_demo.sql');
    kiem(demChungChi($pdo) === 50, 'Phải đủ 50 chứng chỉ, đang có ' . demChungChi($pdo));

    $trangThaiCo = $pdo->query('SELECT DISTINCT status FROM v_certificates')->fetchAll(PDO::FETCH_COLUMN);
    kiem(array_diff(array_keys(TRANG_THAI), $trangThaiCo) === [], 'Thiếu trạng thái: ' . implode(', ', array_diff(array_keys(TRANG_THAI), $trangThaiCo)));

    taoCanhBao($pdo);
    $cacMoc = array_map('intval', $pdo->query('SELECT DISTINCT threshold_days FROM alerts ORDER BY 1')->fetchAll(PDO::FETCH_COLUMN));
    kiem($cacMoc === [7, 15, 30], 'Phải có đủ ba mốc cảnh báo');
    kiem((int) $pdo->query('SELECT COUNT(*) FROM renewals')->fetchColumn() === 7, 'Phải có 6 dòng gia hạn mới cộng 1 của seed.sql');

    $soMayChu = (int) $pdo->query('SELECT COUNT(*) FROM servers')->fetchColumn();
    try {
        chayFileSql($pdo, 'seed_demo.sql');
        throw new LogicException('Nạp lần hai phải báo trùng khoá');
    } catch (PDOException $e) {
        // Giống trình mysql thoát khi gặp lỗi: giao dịch chưa COMMIT bị huỷ.
        $pdo->rollBack();
    }
    kiem((int) $pdo->query('SELECT COUNT(*) FROM servers')->fetchColumn() === $soMayChu, 'Nạp lần hai không được để lại máy chủ thừa');
    kiem(demChungChi($pdo) === 50, 'Nạp lần hai không được thêm chứng chỉ');
}

// Luật: Landing công khai và CSS, JS của nó không nhắc đến VPN, Xray, VLESS, Reality.
// Vì sao: Web còn làm lớp ngụy trang cho máy chủ VPN sau khi nộp (QD-08); ai cũng mở được trang này và tải được các file đó.
// Đỏ nghĩa là: Có chữ, kể cả trong chú thích, làm lộ mục đích của máy chủ cho người ngoài và cho giảng viên.
// Đừng: Xoá từ khoá khỏi danh sách để test xanh; hãy bỏ chữ đó khỏi file công khai.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_landing_cong_khai_khong_nhac_den_vpn(PDO $pdo): void
{
    foreach (['public/index.php', 'public/assets/app.css', 'public/assets/app.js'] as $tep) {
        $noiDung = file_get_contents(__DIR__ . '/../' . $tep);
        kiem(!preg_match('/\b(vpn|xray|vless|reality)\b/i', $noiDung), "$tep có chữ làm lộ mục đích của máy chủ");
    }
}

// Luật: Landing không dùng màu xanh chủ đạo mặc định của Tabler (btn-primary, --tblr-primary).
// Vì sao: User 07/11/2026: nút đăng nhập "chưa ăn khớp với trang, mà vẫn là nút của tabler"; landing có bộ màu riêng (QD-21).
// Đỏ nghĩa là: Có thành phần mới lấy màu mặc định của khung giao diện, trang lại trông như mẫu có sẵn.
// Đừng: Đổi tên lớp để né test; dùng .ld-nut-chinh hoặc cặp màu --ld-net. Màu trạng thái xanh lá, vàng, đỏ vẫn dùng được.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_landing_khong_dung_mau_chu_dao_mac_dinh_cua_tabler(PDO $pdo): void
{
    $trang = file_get_contents(__DIR__ . '/../public/index.php');
    kiem(!str_contains($trang, 'btn-primary'), 'index.php còn nút btn-primary của Tabler');

    // Phần CSS của landing nằm cuối app.css, bắt đầu từ chú thích "Landing:".
    $css = file_get_contents(__DIR__ . '/../public/assets/app.css');
    $cssLanding = substr($css, (int) strpos($css, '/* Landing:'));
    kiem(!str_contains($cssLanding, '--tblr-primary'), 'CSS của landing còn dùng màu --tblr-primary');
}

// Luật: Font nhúng sẵn trong inter.css và trang luôn giữ chỗ thanh cuộn, để đầu trang đứng yên khi chuyển trang.
// Vì sao: User 07/11/2026: chuyển menu "rung lắc rất rõ, gây đau mắt". Đo được: font về sau làm menu nhảy 11-13px, thanh cuộn lệch 7-8px.
// Đỏ nghĩa là: Ai đó tách font ra file riêng hoặc bỏ phần giữ chỗ thanh cuộn; menu sẽ rung lại mỗi lần bấm.
// Đừng: Đổi về file woff2 với font-display: swap vì "gọn hơn"; đó chính là nguyên nhân gây rung.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_dau_trang_dung_yen_khi_chuyen_trang_nho_nhung_font_va_giu_cho_thanh_cuon(PDO $pdo): void
{
    $font = file_get_contents(__DIR__ . '/../public/assets/inter/inter.css');
    kiem(substr_count($font, '@font-face') === substr_count($font, 'url("data:font/woff2;base64,'), 'inter.css phải nhúng mọi font ngay trong file');
    kiem(str_contains(file_get_contents(__DIR__ . '/../views/dau_trang.php'), 'inter/inter.css'), 'dau_trang.php phải nạp inter.css');

    $css = file_get_contents(__DIR__ . '/../public/assets/app.css');
    kiem(!str_contains($css, '@font-face'), 'app.css không được khai báo font riêng');
    kiem(str_contains($css, 'overflow-y: scroll'), 'app.css phải luôn giữ chỗ thanh cuộn');
}

// Luật: Chuyển cảnh giữa hai trang chỉ bật ở landing và trang đăng nhập; trang quản trị chuyển trang tức thì.
// Vì sao: User 07/11/2026 muốn hiệu ứng khi bấm Đăng nhập; trang quản trị dùng hằng ngày nên ưu tiên nhanh, dễ nhìn (QD-21).
// Đỏ nghĩa là: @view-transition lọt vào app.css (áp cho mọi trang), hoặc thêm/bớt trang bật $coChuyenCanh.
// Đừng: Chuyển khối @view-transition vào app.css cho "gọn"; mỗi lần bấm menu quản trị sẽ chạy hiệu ứng.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_chuyen_canh_chi_bat_o_landing_va_dang_nhap(PDO $pdo): void
{
    kiem(!str_contains(file_get_contents(__DIR__ . '/../public/assets/app.css'), '@view-transition'), 'app.css không được bật chuyển cảnh cho mọi trang');
    kiem(str_contains(file_get_contents(__DIR__ . '/../views/dau_trang.php'), '@view-transition'), 'dau_trang.php phải có khối chuyển cảnh bật theo $coChuyenCanh');

    $cacTrang = [];
    $thuMuc = realpath(__DIR__ . '/../public');
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($thuMuc)) as $file) {
        if ($file->getExtension() === 'php' && preg_match('/\$coChuyenCanh\s*=\s*true/', codeKhongChuThich($file->getPathname()))) {
            $cacTrang[] = str_replace('\\', '/', substr($file->getPathname(), strlen($thuMuc) + 1));
        }
    }
    sort($cacTrang);
    kiem($cacTrang === ['index.php', 'login.php'], 'Trang bật chuyển cảnh phải đúng là index.php và login.php, đang là: ' . implode(', ', $cacTrang));
}

// Luật: app.css và app.js được nạp qua urlTep(), địa chỉ kèm ?v= là thời điểm sửa file.
// Vì sao: Máy chủ không gửi Cache-Control; sau lần deploy 07/11/2026 Safari trên iPhone có thể giữ CSS cũ hàng giờ, ghép với HTML mới là trang vỡ.
// Đỏ nghĩa là: Có chỗ nạp hai file này bằng url() trần, hoặc urlTep() không còn gắn thời điểm sửa file.
// Đừng: Gắn số phiên bản viết tay (?v=2); quên tăng là lỗi quay lại.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_css_js_cua_he_thong_co_so_phien_ban_theo_file(PDO $pdo): void
{
    $giaoDien = file_get_contents(__DIR__ . '/../views/dau_trang.php') . file_get_contents(__DIR__ . '/../views/cuoi_trang.php');
    foreach (['assets/app.css', 'assets/app.js'] as $tep) {
        kiem(str_contains($giaoDien, "urlTep('$tep')"), "$tep phải nạp qua urlTep()");
        kiem(!str_contains($giaoDien, "url('$tep')"), "$tep còn nạp bằng url() không có số phiên bản");
    }
    $khoiDong = codeKhongChuThich(__DIR__ . '/../src/khoi_dong.php');
    kiem(preg_match('/function urlTep\(.*?filemtime/s', $khoiDong) === 1, 'urlTep() phải gắn thời điểm sửa file (filemtime)');
}

// Luật: Chỉ chấm "Còn hiệu lực" trên thẻ số Dashboard có vòng sáng lan ra (cham-song); máy giảm chuyển động thì đứng yên.
// Vì sao: User 07/11/2026 muốn chấm "đang hoạt động" có hiệu ứng; nhiều chấm cùng động thì rối và kéo mắt khỏi chứng chỉ cần xử lý (QD-21).
// Đỏ nghĩa là: cham-song lan sang trang khác hoặc gắn cho trạng thái khác, hoặc mất phần tắt hiệu ứng khi giảm chuyển động.
// Đừng: Gắn cham-song vào nhan_trang_thai.php cho "đồng bộ"; danh sách 50 dòng cùng nhấp nháy.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_chi_cham_con_hieu_luc_tren_dashboard_co_vong_sang(PDO $pdo): void
{
    $cacTrang = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath(__DIR__ . '/..'))) as $file) {
        $duong = str_replace('\\', '/', $file->getPathname());
        if ($file->getExtension() === 'php' && !str_contains($duong, '/tests/') && str_contains(codeKhongChuThich($file->getPathname()), 'cham-song')) {
            $cacTrang[] = basename(dirname($duong)) . '/' . $file->getFilename();
        }
    }
    kiem($cacTrang === ['admin/index.php'], 'cham-song chỉ được dùng ở Dashboard, đang ở: ' . implode(', ', $cacTrang));
    kiem(preg_match("/\\\$khoa === 'valid' \\? ' cham-song'/", file_get_contents(__DIR__ . '/../public/admin/index.php')) === 1, 'cham-song phải chỉ gắn cho trạng thái valid');

    $css = file_get_contents(__DIR__ . '/../public/assets/app.css');
    kiem(preg_match('/prefers-reduced-motion: reduce\)\s*\{[^@]*\.cham-song/s', $css) === 1, 'Phải tắt vòng sáng khi máy bật giảm chuyển động');
}

// Luật: Đăng xuất xong thì về landing (index.php), không về trang đăng nhập.
// Vì sao: User 07/11/2026: "Khi đăng xuất thì hãy về trang landing".
// Đỏ nghĩa là: logout.php chuyển hướng đi chỗ khác, hoặc không còn gọi dangXuat() trước khi chuyển.
// Đừng: Đổi về login.php vì "tiện đăng nhập lại"; landing đã có nút Đăng nhập.
// Nhóm: Đăng nhập, phiên, phân quyền (Na, Ni)
function test_dang_xuat_xong_ve_landing(PDO $pdo): void
{
    $code = codeKhongChuThich(__DIR__ . '/../public/logout.php');
    kiem(preg_match("/dangXuat\(\\\$pdo\);\s*chuyenHuong\('index\.php'\);/", $code) === 1, 'logout.php phải gọi dangXuat() rồi chuyển về index.php');
}

// Dòng nhật ký mới nhất: [hành động, loại đối tượng, mã đối tượng, người làm].
function dongNhatKyCuoi(PDO $pdo): array
{
    $dong = $pdo->query('SELECT action, object_type, object_id, user_id FROM audit_logs ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_NUM);
    if ($dong === false) {
        return [];
    }
    return [$dong[0], $dong[1], $dong[2] === null ? null : (int) $dong[2], $dong[3] === null ? null : (int) $dong[3]];
}

function idTheoCot(PDO $pdo, string $bang, string $cot, string $giaTri): int
{
    $cau = $pdo->prepare("SELECT id FROM $bang WHERE $cot = ?");
    $cau->execute([$giaTri]);
    return (int) $cau->fetchColumn();
}

// Luật: Mọi thao tác thay đổi dữ liệu, đăng nhập đúng, đăng nhập sai và đăng xuất đều ghi một dòng nhật ký: ai, làm gì, với đối tượng nào.
// Vì sao: Chức năng mở rộng 1, user chốt 07/11/2026 "ghi tất cả"; ghi ngay trong hàm ở src/ để không trang nào quên ghi.
// Đỏ nghĩa là: một hàm ghi dữ liệu trong src/ không còn gọi ghiNhatKy(), hoặc ghi sai hành động, đối tượng, người làm.
// Đừng: chuyển việc ghi ra từng trang trong public/; thêm trang mới là quên ghi.
// Nhóm: Nhật ký hoạt động (mở rộng 1) (Mở rộng 1)
function test_moi_thao_tac_deu_ghi_nhat_ky(PDO $pdo): void
{
    $admin = ['id' => 1, 'role_name' => VAI_TRO_ADMIN];
    $_SESSION['user_id'] = 1;
    $mong = function (string $buoc, string $hanhDong, ?string $loai, ?int $idDoiTuong, ?int $nguoi) use ($pdo): void {
        $dong = dongNhatKyCuoi($pdo);
        kiem($dong === [$hanhDong, $loai, $idDoiTuong, $nguoi], "$buoc: nhật ký ghi " . json_encode($dong));
        kiem(isset(HANH_DONG[$hanhDong]), "$buoc: hành động $hanhDong chưa có nhãn tiếng Việt");
    };

    kiem(luuChungChi($pdo, null, chungChiHopLe(), 1) === [], 'Thêm chứng chỉ phải thành công');
    $idCc = idTheoCot($pdo, 'certificates', 'cert_code', 'CERT-100');
    $mong('Thêm chứng chỉ', 'them', 'chung_chi', $idCc, 1);
    kiem(luuChungChi($pdo, $idCc, chungChiHopLe(['subject_cn' => 'doi.congty.example']), 1) === [], 'Sửa chứng chỉ phải thành công');
    $mong('Sửa chứng chỉ', 'sua', 'chung_chi', $idCc, 1);
    kiem(giaHanChungChi($pdo, $idCc, duLieuGiaHan(), 1) === [], 'Gia hạn phải thành công');
    $mong('Gia hạn', 'gia_han', 'chung_chi', $idCc, 1);
    kiem(thuHoiChungChi($pdo, $idCc) === null, 'Thu hồi phải thành công');
    $mong('Thu hồi', 'thu_hoi', 'chung_chi', $idCc, 1);
    kiem(xoaChungChi($pdo, $idCc, $admin) === null, 'Xoá chứng chỉ phải thành công');
    $mong('Xoá chứng chỉ', 'xoa', 'chung_chi', $idCc, 1);

    kiem(luuMayChu($pdo, null, mayChuHopLe()) === [], 'Thêm máy chủ phải thành công');
    $idMc = idTheoCot($pdo, 'servers', 'hostname', 'web-02');
    $mong('Thêm máy chủ', 'them', 'may_chu', $idMc, 1);
    kiem(luuMayChu($pdo, $idMc, mayChuHopLe(['owner' => 'Phòng khác'])) === [], 'Sửa máy chủ phải thành công');
    $mong('Sửa máy chủ', 'sua', 'may_chu', $idMc, 1);
    kiem(luuTenMien($pdo, null, ['domain_name' => 'moi.congty.example', 'server_id' => (string) $idMc]) === [], 'Thêm tên miền phải thành công');
    $idTm = idTheoCot($pdo, 'domains', 'domain_name', 'moi.congty.example');
    $mong('Thêm tên miền', 'them', 'ten_mien', $idTm, 1);
    kiem(xoaTenMien($pdo, $idTm) === null, 'Xoá tên miền phải thành công');
    $mong('Xoá tên miền', 'xoa', 'ten_mien', $idTm, 1);
    kiem(xoaMayChu($pdo, $idMc) === null, 'Xoá máy chủ phải thành công');
    $mong('Xoá máy chủ', 'xoa', 'may_chu', $idMc, 1);
    kiem(luuToChuc($pdo, null, ['name' => 'CA Mới', 'country' => 'Việt Nam', 'is_trusted' => '1']) === [], 'Thêm tổ chức cấp phải thành công');
    $idTc = idTheoCot($pdo, 'certificate_authorities', 'name', 'CA Mới');
    $mong('Thêm tổ chức cấp', 'them', 'to_chuc_cap', $idTc, 1);
    kiem(xoaToChuc($pdo, $idTc) === null, 'Xoá tổ chức cấp phải thành công');
    $mong('Xoá tổ chức cấp', 'xoa', 'to_chuc_cap', $idTc, 1);

    kiem(themNguoiDung($pdo, duLieuNguoiMoi()) === [], 'Thêm người dùng phải thành công');
    $idNd = idTheoCot($pdo, 'users', 'username', 'nguoi.moi');
    $mong('Thêm người dùng', 'them', 'nguoi_dung', $idNd, 1);
    kiem(suaNguoiDung($pdo, $idNd, duLieuNguoiMoi(['full_name' => 'Tên Khác', 'password' => '', 'password2' => '']), 1) === [], 'Sửa người dùng phải thành công');
    $mong('Sửa người dùng', 'sua', 'nguoi_dung', $idNd, 1);
    kiem(khoaNguoiDung($pdo, $idNd, 1) === null, 'Khoá phải thành công');
    $mong('Khoá', 'khoa', 'nguoi_dung', $idNd, 1);
    moKhoaNguoiDung($pdo, $idNd);
    $mong('Mở khoá', 'mo_khoa', 'nguoi_dung', $idNd, 1);
    // Người mới chưa tự làm gì nên chưa có dòng nào đứng tên họ: vẫn xoá được.
    kiem(xoaNguoiDung($pdo, $idNd, 1) === null, 'Xoá người dùng chưa có lịch sử phải thành công');
    $mong('Xoá người dùng', 'xoa', 'nguoi_dung', $idNd, 1);
    kiem(suaHoTen($pdo, 1, 'Quản trị viên') === [], 'Sửa họ tên phải thành công');
    $mong('Sửa họ tên của mình', 'sua', 'nguoi_dung', 1, 1);
    kiem(doiMatKhau($pdo, 1, 'Cipher@2026', 'MatKhauMoi@1', 'MatKhauMoi@1') === [], 'Đổi mật khẩu phải thành công');
    $mong('Đổi mật khẩu', 'doi_mat_khau', 'nguoi_dung', 1, 1);

    unset($_SESSION['user_id']);
    dangNhap($pdo, 'nhanvien', 'mat-khau-sai');
    $mong('Đăng nhập sai mật khẩu', 'dang_nhap_sai', 'nguoi_dung', 2, null);
    dangNhap($pdo, 'khong-ton-tai', 'mat-khau-sai');
    $mong('Đăng nhập sai tên', 'dang_nhap_sai', null, null, null);
    kiem(isset(dangNhap($pdo, 'nhanvien', 'Cipher@2026')['id']), 'Đăng nhập đúng phải thành công');
    $mong('Đăng nhập đúng', 'dang_nhap', 'nguoi_dung', 2, 2);

    // dangXuat() còn xoá cookie và phiên của trình duyệt; ở dòng lệnh không có, nên bỏ qua cảnh báo của hai việc đó.
    $_SESSION['user_id'] = 2;
    @dangXuat($pdo);
    $mong('Đăng xuất', 'dang_xuat', 'nguoi_dung', 2, 2);

    // Đọc chứng chỉ bị chặn vẫn phải ghi: địa chỉ nội bộ bị từ chối trước khi kết nối, nên test không cần mạng.
    $_SESSION['user_id'] = 1;
    kiem(isset(docChungChiChoForm($pdo, '127.0.0.1', 443)['loi']), 'Đọc 127.0.0.1 phải bị chặn');
    $mong('Đọc chứng chỉ bị chặn', 'doc_chung_chi', null, null, 1);
    unset($_SESSION['user_id']);
}

// Luật: CSDL từ chối UPDATE và DELETE trên audit_logs (trigger), kể cả lệnh gõ thẳng vào CSDL; chỉ TRUNCATE dọn được cả bảng.
// Vì sao: User 07/11/2026 chọn chặn ở CSDL: không ai lén sửa hay xoá một dòng để giấu việc mình làm. Dọn trước demo dùng TRUNCATE (đo 07/11/2026: trigger không chặn lệnh này).
// Đỏ nghĩa là: trigger trong schema.sql bị mất, đổi bảng, hoặc ai đó đã nới cho sửa/xoá.
// Đừng: thêm nút xoá nhật ký trên web, hay sửa trigger để "cho Admin xoá".
// Nhóm: Nhật ký hoạt động (mở rộng 1) (Mở rộng 1)
function test_csdl_tu_choi_sua_xoa_nhat_ky(PDO $pdo): void
{
    $pdo->exec("INSERT INTO audit_logs (user_id, action) VALUES (1, 'dang_nhap')");
    phaiBiTuChoi($pdo, "UPDATE audit_logs SET action = 'khac'", 'Sửa nhật ký', '45000');
    phaiBiTuChoi($pdo, 'DELETE FROM audit_logs', 'Xoá nhật ký', '45000');
    $pdo->exec('TRUNCATE TABLE audit_logs');
    kiem((int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn() === 0, 'TRUNCATE phải dọn được nhật ký trước demo');
}

// Luật: Trang Nhật ký và Người dùng chỉ Admin vào được, và nhóm "Hệ thống" (hai mục đó) trên menu chỉ hiện với Admin.
// Vì sao: User 07/11/2026 chốt chỉ Admin xem nhật ký; quản lý tài khoản là việc của Admin. Menu dọc 08/11/2026 gom hai mục vào nhóm Hệ thống.
// Đỏ nghĩa là: Một trong hai trang không gọi yeuCauAdmin(), hoặc link tới nó nằm ngoài khối laAdmin() trong đầu trang.
// Đừng: chỉ giấu link trên menu; người biết địa chỉ vẫn mở được trang.
// Nhóm: Nhật ký hoạt động (mở rộng 1) (Mở rộng 1)
function test_nhom_he_thong_tren_menu_va_hai_trang_cua_no_chi_danh_cho_admin(PDO $pdo): void
{
    $dauTrang = codeKhongChuThich(__DIR__ . '/../views/dau_trang.php');
    kiem(preg_match('/if \(\$nguoi !== null && laAdmin\(\$nguoi\)\) \{([^}]*)\}/', $dauTrang, $m) === 1, 'Đầu trang thiếu khối laAdmin() thêm nhóm Hệ thống');
    foreach (['nhat_ky', 'nguoi_dung'] as $trang) {
        $tep = __DIR__ . "/../public/admin/$trang.php";
        kiem(preg_match('/\byeuCauAdmin\(/', codeKhongChuThich($tep)) === 1, "$trang.php chưa gọi yeuCauAdmin()");
        kiem(substr_count($dauTrang, "admin/$trang.php") === 1, "Đầu trang phải có đúng một link tới $trang.php");
        kiem(str_contains($m[1], "admin/$trang.php"), "Link $trang.php phải nằm trong khối laAdmin()");
    }
    kiem(str_contains($m[1], "'Hệ thống'"), 'Hai mục chỉ dành cho Admin phải nằm trong nhóm Hệ thống');
}

// Luật: Tab trình duyệt ghi "Tên trang | Cipher Cert"; tên thương hiệu khai báo một chỗ (TEN_THUONG_HIEU).
// Vì sao: User 08/11/2026 chốt brand ngắn Cipher Cert và dấu | (QD-24); tên trang đứng trước để mở nhiều tab vẫn phân biệt được.
// Đỏ nghĩa là: Ai đó đổi lại dấu phân cách, đảo thứ tự, hoặc viết cứng tên ở đầu trang thay vì dùng hằng.
// Đừng: Viết "Cipher Cert" thẳng vào từng trang; đổi tên lần sau sẽ sót.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_tab_trinh_duyet_ghi_ten_trang_roi_ten_thuong_hieu(PDO $pdo): void
{
    $khoiDong = codeKhongChuThich(__DIR__ . '/../src/khoi_dong.php');
    kiem(str_contains($khoiDong, "const TEN_THUONG_HIEU = 'Cipher Cert';"), 'Thiếu hằng TEN_THUONG_HIEU = Cipher Cert');
    $dauTrang = codeKhongChuThich(__DIR__ . '/../views/dau_trang.php');
    kiem(str_contains($dauTrang, "\$tieuDeTab ??= \$tieuDe . ' | ' . TEN_THUONG_HIEU;"), 'Tab mặc định phải là "Tên trang | Cipher Cert"');
    kiem(str_contains($dauTrang, '<title><?= e($tieuDeTab) ?></title>'), 'Thẻ title phải in $tieuDeTab');
    kiem(!str_contains($dauTrang, 'Cipher Cert'), 'Đầu trang không được viết cứng tên thương hiệu');
}

// Luật: Tên miền trong bảng chỉ được xuống dòng ngay sau dấu chấm, và vẫn được thoát ký tự HTML.
// Vì sao: Chụp 08/11/2026 ở 390px: cột hẹp làm trình duyệt bẻ giữa chữ ("pay.congt" / "y.example").
// Đỏ nghĩa là: tenMienNgatDong() không chèn <wbr> sau dấu chấm, hoặc in thẳng ký tự HTML (lỗ XSS), hoặc bảng quay lại dùng text-break.
// Đừng: Bọc kết quả thêm e(): <wbr> sẽ hiện thành chữ trên trang.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_ten_mien_chi_xuong_dong_o_dau_cham(PDO $pdo): void
{
    kiem(tenMienNgatDong('pay.congty.example') === 'pay.<wbr>congty.<wbr>example', 'Phải chèn <wbr> sau mỗi dấu chấm');
    kiem(tenMienNgatDong('<b>.vn') === '&lt;b&gt;.<wbr>vn', 'Phải thoát ký tự HTML trong tên miền');
    foreach (['public/admin/index.php', 'public/admin/chung_chi.php'] as $tep) {
        $code = file_get_contents(__DIR__ . '/../' . $tep);
        kiem(str_contains($code, "tenMienNgatDong(\$dong['subject_cn'])"), "$tep phải in tên chính bằng tenMienNgatDong()");
        kiem(!preg_match('/text-break[^>]*>\s*<\?= e\(\$dong\[.subject_cn.\]\)/', $code), "$tep còn để trình duyệt bẻ tên miền giữa chữ");
    }
}

// Luật: Web có địa chỉ không đuôi cổng: cổng 80, Alt-Svc và cả địa chỉ gõ kèm :8443 đều dẫn về địa chỉ không đuôi, dữ liệu mẫu ghi cổng 443.
// Vì sao: Caddy vẫn nghe 8443 nên tự quảng bá cổng này qua Alt-Svc, và người mở link cũ sẽ thấy lại đuôi nếu không bị đá về.
// Đỏ nghĩa là: ai đó đã đưa đuôi :8443 trở lại, hoặc bỏ dòng xoá Alt-Svc trong cấu hình Caddy.
// Đừng: sửa test cho xanh bằng cách thêm lại đuôi; gói nộp không có thư mục deploy/ nên phần đọc file đó được bỏ qua.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_web_co_dia_chi_khong_duoi_cong(PDO $pdo): void
{
    $cong = $pdo->query("SELECT port FROM servers WHERE hostname = 'cipher-vps'")->fetchColumn();
    kiem((int) $cong === 443, 'Máy chủ thật của web phải ghi cổng 443');

    $tep = __DIR__ . '/../deploy/caddy-cipher.conf';
    if (!is_file($tep)) {
        return;
    }
    $caddy = file_get_contents($tep);
    kiem(str_contains($caddy, 'redir https://cert.cipher.id.vn{uri} permanent'), 'Cổng 80 phải đá về địa chỉ không đuôi');
    kiem(!str_contains($caddy, 'redir https://cert.cipher.id.vn:8443'), 'Không chuyển hướng nào được dẫn tới đuôi :8443');
    kiem(str_contains($caddy, '-Alt-Svc'), 'Phải xoá Alt-Svc để không quảng bá cổng 8443');
    kiem(str_contains($caddy, '@duoi_cu header Host cert.cipher.id.vn:8443'), 'Phải nhận ra địa chỉ gõ kèm :8443');
    kiem(str_contains($caddy, 'redir @duoi_cu https://cert.cipher.id.vn{uri} permanent'), 'Địa chỉ kèm :8443 phải bị đá về không đuôi');
}

// Chứng chỉ thật của cert.cipher.id.vn (Let's Encrypt, ECDSA P-256), đọc ngày 08/11/2026. Dữ liệu công khai:
// ai kết nối tới web cũng nhận được. Dùng làm mẫu để test phần đọc chứng chỉ mà không cần mạng.
const CHUNG_CHI_MAU_PEM = <<<'PEM'
-----BEGIN CERTIFICATE-----
MIIDkTCCAxegAwIBAgISBmq4CQDG3JA797gixwFve6O/MAoGCCqGSM49BAMDMDMx
CzAJBgNVBAYTAlVTMRYwFAYDVQQKEw1MZXQncyBFbmNyeXB0MQwwCgYDVQQDEwNZ
RTEwHhcNMjYxMTA2MDMxODQxWhcNMjcwMjA0MDMxODQwWjAcMRowGAYDVQQDExFj
ZXJ0LmNpcGhlci5pZC52bjBZMBMGByqGSM49AgEGCCqGSM49AwEHA0IABJpnXDFB
4sQnqEMlHBdo8JFgQWu9SF88ADgcOswxtLI/mqasuQPYbwj4rL36F3Yjs+49WE1x
EEiOvukY9mev472jggIgMIICHDAOBgNVHQ8BAf8EBAMCB4AwEwYDVR0lBAwwCgYI
KwYBBQUHAwEwDAYDVR0TAQH/BAIwADAdBgNVHQ4EFgQUmdMQMRuhMapRQCa+dlSM
+XTOhwAwHwYDVR0jBBgwFoAUuyDKRwv+1+Wc+Y8JKqOMN0WxvNgwMwYIKwYBBQUH
AQEEJzAlMCMGCCsGAQUFBzAChhdodHRwOi8veWUxLmkubGVuY3Iub3JnLzAcBgNV
HREEFTATghFjZXJ0LmNpcGhlci5pZC52bjATBgNVHSAEDDAKMAgGBmeBDAECATAu
BgNVHR8EJzAlMCOgIaAfhh1odHRwOi8veWUxLmMubGVuY3Iub3JnLzY0LmNybDCC
AQ0GCisGAQQB1nkCBAIEgf4EgfsA+QB3ANbVjanQF1Pzakqgx1dJAq/rx9ws04zZ
92TIDIkZHp8CAAABoa8TK9IAAAQDAEgwRgIhAPwj5vcRzHCPHYpJEHrYfXqjLPlh
fJaJKEIjNWXZbu5PAiEA/ZzVahDcj11wKkqzdVRAGY4VOZXUvphutBowoGbaUXUA
fgCOykcLrN5q86IGsKR6hLdG/h/Gv5U+JeabTuQCSPPG6AAAAaGvEy3UAAgAAAUA
Oe+inQQDAEcwRQIgTX9KQPzHhsbKqTxzib0nTBFzNZv2S+2JVXwUjBNpo9MCIQCl
FRPZrHpqWoRdIn5rXeq1w9twKwj0+neRDeXdW8LrYDAKBggqhkjOPQQDAwNoADBl
AjBOhfgpcNTz7SqtIC+/yA22YPBGYWxyVmIfKsmt8w0ge3gUyBeCLKhUbSStGzQ5
LrICMQDCuN1v0Bjrdb/31GLjz1R/qPPh5HZYcNSf7m0eA+HBQPei0zExjMy6lh0h
nG2YRWE=
-----END CERTIFICATE-----
PEM;

// Luật: Đọc chứng chỉ chỉ kết nối tới địa chỉ công khai và cổng TLS trong danh sách; chặn nếu BẤT KỲ IP nào của tên miền là nội bộ.
// Vì sao: VPS chạy chung dịch vụ chỉ nghe ở 127.0.0.1, không mật khẩu; không chặn thì người đăng nhập mượn web dò mạng nội bộ (SSRF).
// Đỏ nghĩa là: Có một dạng địa chỉ nội bộ lọt qua (kể cả IPv4 viết trong IPv6), hoặc cổng ngoài danh sách được đọc.
// Đừng: Chỉ kiểm IP đầu tiên của tên miền, hay tra DNS lần nữa lúc kết nối; kẻ xấu đổi bản ghi giữa hai lần tra.
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_doc_chung_chi_chan_dia_chi_noi_bo_va_cong_ngoai_danh_sach(PDO $pdo): void
{
    $noiBo = ['127.0.0.1', '127.8.8.8', '10.0.0.1', '172.16.0.1', '172.31.255.255', '192.168.1.1', '169.254.169.254',
        '100.64.0.1', '0.0.0.0', '::1', '::', '::ffff:127.0.0.1', '::ffff:7f00:1', '::127.0.0.1', '64:ff9b::7f00:1',
        '::ffff:10.0.0.1', 'fe80::1', 'fc00::1', 'fd12:3456::1', 'khong-phai-ip'];
    foreach ($noiBo as $ip) {
        kiem(laDiaChiNoiBo($ip), "$ip phải bị coi là nội bộ");
    }
    foreach (['8.8.8.8', '161.248.146.56', '2606:4700::1111', '::ffff:8.8.8.8'] as $ip) {
        kiem(!laDiaChiNoiBo($ip), "$ip là địa chỉ công khai, phải được đọc");
    }
    kiem(kiemTraDiaChiDoc('a.example', 443, ['8.8.8.8']) === null, 'Địa chỉ công khai ở cổng 443 phải được đọc');
    kiem(kiemTraDiaChiDoc('a.example', 443, ['8.8.8.8', '127.0.0.1']) !== null, 'Tên miền có một IP nội bộ phải bị chặn');
    kiem(kiemTraDiaChiDoc('a.example', 8765, ['8.8.8.8']) !== null, 'Cổng ngoài danh sách (AnkiConnect 8765) phải bị chặn');
    kiem(kiemTraDiaChiDoc('a.example', 22, ['8.8.8.8']) !== null, 'Cổng SSH phải bị chặn');
    kiem(kiemTraDiaChiDoc('a.example', 443, []) !== null, 'Không tra được IP thì không đọc');
    kiem(phanTichDiaChi('https://Cert.Cipher.id.vn:8443/duong-dan', 443) === ['host' => 'cert.cipher.id.vn', 'cong' => 8443], 'Phải tách tên máy và cổng từ địa chỉ dán vào');
    kiem(isset(docChungChiTuMayChu('127.0.0.1:443', 443)['loi']), '127.0.0.1 phải bị chặn trước khi kết nối');
    kiem(isset(docChungChiTuMayChu('[::1]', 443)['loi']), '::1 phải bị chặn trước khi kết nối');
}

// Luật: Chứng chỉ đọc được điền đúng từng ô của form: tên chính, ngày theo giờ Việt Nam, thuật toán, độ dài khoá, ký, fingerprint, serial.
// Vì sao: User 08/11/2026 muốn đọc xong chỉ việc xem lại rồi Lưu; ô điền sai là lưu sai ngày hết hạn, sai cảnh báo.
// Đỏ nghĩa là: Ngày lệch múi giờ (giờ UTC thay vì +07:00), thuật toán hay fingerprint lấy sai trường của chứng chỉ.
// Đừng: Sửa giá trị mong đợi theo kết quả mới cho xanh; so lại với "openssl x509 -text" của chứng chỉ mẫu trước.
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_chung_chi_doc_duoc_dien_dung_cac_o(PDO $pdo): void
{
    $gt = giaTriTuChungChi(CHUNG_CHI_MAU_PEM);
    $mong = [
        'subject_cn' => 'cert.cipher.id.vn',
        'san' => '', // Tên phụ duy nhất trùng tên chính nên không lặp lại.
        'valid_from' => '2026-11-06 10:18:41', // 03:18:41 UTC
        'valid_to' => '2027-02-04 10:18:40',
        'key_algorithm' => 'ECDSA',
        'key_size' => '256',
        'signature_algorithm' => 'ecdsa-with-SHA384',
        'fingerprint_sha256' => 'ee7a1d355685b88e42560a57530af572a77dd193e27ee7ba616c225869c55543',
        'serial' => '066AB80900C6DC903BF7B822C7016F7BA3BF',
        'ben_cap' => ["Let's Encrypt", 'YE1'],
    ];
    foreach ($mong as $o => $giaTri) {
        kiem($gt[$o] === $giaTri, "Ô $o: mong " . json_encode($giaTri) . ', nhận ' . json_encode($gt[$o]));
    }
}

// ISRG Root X1, CA gốc của Let's Encrypt (hết hạn 2035), lấy từ bộ CA của XAMPP ngày 09/11/2026; vân tay SHA-256
// 96bcec06…df08c6 khớp bản công bố. Có trong kho CA gốc của mọi máy, nên làm mẫu "chuỗi tin cậy" không cần mạng.
const CA_GOC_ISRG_PEM = <<<'PEM'
-----BEGIN CERTIFICATE-----
MIIFazCCA1OgAwIBAgIRAIIQz7DSQONZRGPgu2OCiwAwDQYJKoZIhvcNAQELBQAwTzELMAkGA1UE
BhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2VhcmNoIEdyb3VwMRUwEwYDVQQD
EwxJU1JHIFJvb3QgWDEwHhcNMTUwNjA0MTEwNDM4WhcNMzUwNjA0MTEwNDM4WjBPMQswCQYDVQQG
EwJVUzEpMCcGA1UEChMgSW50ZXJuZXQgU2VjdXJpdHkgUmVzZWFyY2ggR3JvdXAxFTATBgNVBAMT
DElTUkcgUm9vdCBYMTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK3oJHP0FDfzm54r
Vygch77ct984kIxuPOZXoHj3dcKi/vVqbvYATyjb3miGbESTtrFj/RQSa78f0uoxmyF+0TM8ukj1
3Xnfs7j/EvEhmkvBioZxaUpmZmyPfjxwv60pIgbz5MDmgK7iS4+3mX6UA5/TR5d8mUgjU+g4rk8K
b4Mu0UlXjIB0ttov0DiNewNwIRt18jA8+o+u3dpjq+sWT8KOEUt+zwvo/7V3LvSye0rgTBIlDHCN
Aymg4VMk7BPZ7hm/ELNKjD+Jo2FR3qyHB5T0Y3HsLuJvW5iB4YlcNHlsdu87kGJ55tukmi8mxdAQ
4Q7e2RCOFvu396j3x+UCB5iPNgiV5+I3lg02dZ77DnKxHZu8A/lJBdiB3QW0KtZB6awBdpUKD9jf
1b0SHzUvKBds0pjBqAlkd25HN7rOrFleaJ1/ctaJxQZBKT5ZPt0m9STJEadao0xAH0ahmbWnOlFu
hjuefXKnEgV4We0+UXgVCwOPjdAvBbI+e0ocS3MFEvzG6uBQE3xDk3SzynTnjh8BCNAw1FtxNrQH
usEwMFxIt4I7mKZ9YIqioymCzLq9gwQbooMDQaHWBfEbwrbwqHyGO0aoSCqI3Haadr8faqU9GY/r
OPNk3sgrDQoo//fb4hVC1CLQJ13hef4Y53CIrU7m2Ys6xt0nUW7/vGT1M0NPAgMBAAGjQjBAMA4G
A1UdDwEB/wQEAwIBBjAPBgNVHRMBAf8EBTADAQH/MB0GA1UdDgQWBBR5tFnme7bl5AFzgAiIyBpY
9umbbjANBgkqhkiG9w0BAQsFAAOCAgEAVR9YqbyyqFDQDLHYGmkgJykIrGF1XIpu+ILlaS/V9lZL
ubhzEFnTIZd+50xx+7LSYK05qAvqFyFWhfFQDlnrzuBZ6brJFe+GnY+EgPbk6ZGQ3BebYhtF8GaV
0nxvwuo77x/Py9auJ/GpsMiu/X1+mvoiBOv/2X/qkSsisRcOj/KKNFtY2PwByVS5uCbMiogziUwt
hDyC3+6WVwW6LLv3xLfHTjuCvjHIInNzktHCgKQ5ORAzI4JMPJ+GslWYHb4phowim57iaztXOoJw
TdwJx4nLCgdNbOhdjsnvzqvHu7UrTkXWStAmzOVyyghqpZXjFaH3pO3JLF+l+/+sKAIuvtd7u+Nx
e5AW0wdeRlN8NwdCjNPElpzVmbUq4JUagEiuTDkHzsxHpFKVK7q4+63SM1N95R1NbdWhscdCb+ZA
JzVcoyi3B43njTOQ5yOf+1CceWxG1bQVs5ZufpsMljq4Ui0/1lvh+wjChP4kqKOJ2qxq4RgqsahD
YVvTH9w7jXbyLeiNdd8XM2w9U/t7y0Ff/9yi0GE44Za4rF2LN9d11TPAmRGunUHBcnWEvgJBQl9n
JEiU0Zsnvgc/ubhPgXRR4Xq37Z0j4r7g1SgEEzwxA57demyPxgcYxn/eR44/KJ4EBs+lVDR3veyJ
m+kXQ99b21/+jh5Xos1AnX5iItreGCc=
-----END CERTIFICATE-----
PEM;

// Những gì một lần đọc trả về (nguonTuKetQuaDoc), dựng tay để test kế hoạch danh mục không cần mạng.
// IP 203.0.113.x là dải dành riêng cho ví dụ, không trùng máy chủ nào trong seed.sql.
function nguonMau(array $thayDoi = []): array
{
    return $thayDoi + ['host' => 'shop.example.org', 'cong' => 443, 'cac_ip' => ['203.0.113.5'], 'cn' => 'shop.example.org',
        'ben_cap' => ['GlobalSign nv-sa', 'GlobalSign GCC R46 OV TLS CA 2025'], 'hang' => 'GlobalSign', 'quoc_gia' => 'Bỉ',
        'tin_cay' => true, 'chu' => 'Shop JSC'];
}

function demDanhMuc(PDO $pdo): int
{
    return (int) $pdo->query('SELECT (SELECT COUNT(*) FROM servers) + (SELECT COUNT(*) FROM domains)
                                   + (SELECT COUNT(*) FROM certificate_authorities)')->fetchColumn();
}

// Luật: Đọc xong chỉ tính kế hoạch: tên miền (trùng hẳn hoặc tên miền cha), tổ chức cấp có sẵn thì chọn, chưa có thì ghi "sẽ tạo"; trước khi bấm Lưu không bản ghi nào được tạo.
// Vì sao: User 09/11/2026 lật chốt "không tự tạo" của 08/11 để nhập 50 site thật cho nhanh, nhưng đọc thử rồi bỏ thì danh mục vẫn phải sạch.
// Đỏ nghĩa là: Chọn nhầm tên miền ngắn hơn khi có tên khớp dài hơn, phân biệt hoa thường, đổi ô gõ mà vẫn tạo theo tên cũ, hoặc keHoachDanhMuc() đã INSERT.
// Đừng: Tạo bản ghi ngay trong keHoachDanhMuc() hay idTheoTen() cho "tiện"; chỉ luuChungChiKemDanhMuc() được tạo.
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_doc_roi_bo_khong_tao_gi_va_tu_chon_danh_muc_co_san(PDO $pdo): void
{
    $tenMien = [3 => 'cipher.id.vn', 4 => 'cert.cipher.id.vn', 5 => 'id.vn'];
    kiem(chonTenMienChoDiaChi($tenMien, 'cert.cipher.id.vn') === 4, 'Trùng hẳn phải được chọn');
    kiem(chonTenMienChoDiaChi($tenMien, 'mail.cipher.id.vn') === 3, 'Phải chọn tên miền cha dài nhất');
    kiem(chonTenMienChoDiaChi($tenMien, 'xcipher.id.vn') === 5, '"xcipher.id.vn" không thuộc "cipher.id.vn"');
    kiem(chonTenMienChoDiaChi($tenMien, 'example.com') === null, 'Không khớp thì để trống');
    $toChuc = [1 => "Let's Encrypt", 2 => 'DigiCert'];
    kiem(chonToChucCap($toChuc, ["let's encrypt", 'YE1']) === 1, 'Trùng tên không phân biệt hoa thường phải được chọn');
    kiem(chonToChucCap($toChuc, ['Acme Corp', 'DigiCert']) === 2, 'Trùng tên thứ hai cũng được');

    $truoc = demDanhMuc($pdo);
    $kh = keHoachDanhMuc($pdo, nguonMau());
    kiem($kh['ten_mien_moi'] === 'shop.example.org' && $kh['may_chu_moi']['ip_address'] === '203.0.113.5'
        && $kh['hang_moi']['name'] === 'GlobalSign', 'Chưa có thì kế hoạch phải ghi sẽ tạo tên miền, máy chủ, hãng');
    $co = keHoachDanhMuc($pdo, nguonMau(['host' => 'cert.cipher.id.vn', 'cac_ip' => ['161.248.146.56'], 'hang' => "Let's Encrypt"]));
    kiem($co['domain_id'] === 6 && $co['ca_id'] === 1 && moTaSeTao($co) === [], 'Có sẵn thì chọn, không định tạo gì');
    kiem(demDanhMuc($pdo) === $truoc, 'Đọc (tính kế hoạch) không được tạo bản ghi nào');

    $doiO = keHoachTheoONhap($kh, 'cert.cipher.id.vn', "Let's Encrypt");
    kiem($doiO['ten_mien_moi'] === null && $doiO['may_chu_moi'] === null && $doiO['hang_moi'] === null,
        'Người dùng đổi ô sang tên có sẵn thì không tạo theo tên cũ');
    kiem(keHoachTheoONhap($kh, ' Shop.Example.org ', 'globalsign')['hang_moi'] !== null, 'Giữ đúng tên (khác hoa thường) thì vẫn tạo');
}

// Luật: Tổ chức cấp ghi ở tầng hãng: mọi cách viết ô O (và ô CN) của cùng một hãng ra một tên gọn; hãng lạ giữ nguyên ô O.
// Vì sao: User 09/11/2026: "Let's Encrypt chỉ có một"; ghi theo chứng chỉ trung gian (YE1, R10) hay theo biến thể (SSL Corp và SSL Corporation, đo 72 site) thì Thống kê theo tổ chức cấp tách cột vô nghĩa.
// Đỏ nghĩa là: Một biến thể đã đo bị tách thành hãng riêng, hoặc mẫu khớp giữa chữ ("Pineapple" thành Apple).
// Đừng: Khớp bằng str_contains, hay đổi tên các hãng người dùng đã nhập trong danh mục.
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_ten_hang_gom_ve_tang_hang(PDO $pdo): void
{
    $mong = [
        ["Let's Encrypt", 'YE1', "Let's Encrypt"],
        ['GlobalSign nv-sa', 'GlobalSign GCC R46 OV TLS CA 2025', 'GlobalSign'],
        ['SSL Corp', 'SSL.com SSL Intermediate CA ECC R2', 'SSL.com'],
        ['SSL Corporation', 'SSL.com TLS Issuing RSA CA R1', 'SSL.com'],
        ['COMODO CA Limited', 'COMODO RSA Domain Validation Secure Server CA', 'Sectigo'],
        ['Sectigo Limited', 'Sectigo Public Server Authentication CA DV E36', 'Sectigo'],
        ['DigiCert Inc', 'GeoTrust TLS RSA CA G1', 'DigiCert'],
        ['Google Trust Services', 'WE2', 'Google Trust Services'],
        ['The Ministry of Digital Development and Communications', 'Russian Trusted Sub CA', 'Russian Trusted CA'],
        ['Pineapple Hosting', 'PH CA', 'Pineapple Hosting'],
        ['', 'R3', 'R3'],
        ['', '', 'Không rõ'],
    ];
    foreach ($mong as [$o, $cn, $hang]) {
        kiem(tenHang($o, $cn) === $hang, "O=\"$o\", CN=\"$cn\": mong \"$hang\", nhận \"" . tenHang($o, $cn) . '"');
    }
    kiem(tenQuocGia('be') === 'Bỉ' && tenQuocGia('US') === 'Hoa Kỳ' && tenQuocGia('ZZ') === 'ZZ' && tenQuocGia('') === 'Không rõ',
        'Mã quốc gia phải ra tên tiếng Việt, mã lạ giữ nguyên');
}

// Luật: Hãng mới tạo là tin cậy khi có chứng chỉ bên cấp (không tính chứng chỉ lá) trong chuỗi kiểm được với kho CA gốc của máy; chỉ có chứng chỉ lá thì không tin cậy.
// Vì sao: User 09/11/2026 chọn tự đoán thay cho mặc định "có": sberbank.ru (CA của Nga), tự ký, gốc lạ phải ra không tin cậy (đo VPS và XAMPP 09/11/2026).
// Đỏ nghĩa là: Kiểm cả chứng chỉ lá (lá hết hạn làm hãng đáng tin ra không tin), bỏ openssl.cafile (XAMPP ra không tin hết), hoặc máy chạy test không có kho CA gốc.
// Đừng: Kiểm bằng verify_peer lúc kết nối: chứng chỉ hết hạn, sai tên vẫn phải đọc được.
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_hang_tin_cay_khi_chuoi_kiem_duoc_voi_kho_ca_goc(PDO $pdo): void
{
    kiem(laChuoiTinCay([CHUNG_CHI_MAU_PEM, CA_GOC_ISRG_PEM]), 'Chuỗi có CA gốc trong kho phải tin cậy');
    kiem(!laChuoiTinCay([CHUNG_CHI_MAU_PEM]), 'Chỉ có chứng chỉ lá thì không tin cậy');
    kiem(!laChuoiTinCay([CHUNG_CHI_MAU_PEM, CHUNG_CHI_MAU_PEM]), 'Bên cấp không tới được CA gốc thì không tin cậy');
    kiem(!laChuoiTinCay([CA_GOC_ISRG_PEM]), 'Chứng chỉ lá không được tính, kể cả khi nó nằm trong kho');
}

// Luật: Bấm Lưu thì tạo đủ máy chủ, tên miền, tổ chức cấp còn thiếu cùng chứng chỉ, mỗi thứ một dòng nhật ký; hỏng ở bước nào thì không bước nào được ghi.
// Vì sao: User 09/11/2026 muốn đọc xong bấm Lưu là xong; tạo dở dang để lại tên miền mồ côi, lần sau đọc lại thì tưởng đã có.
// Đỏ nghĩa là: Thiếu giao dịch hoặc rollBack, hay một bước không qua hàm lưu có sẵn (mất kiểm tra hoặc mất nhật ký).
// Đừng: INSERT thẳng vào bảng trong luuChungChiKemDanhMuc(); dùng luuMayChu/luuTenMien/luuToChuc/luuChungChi.
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_luu_tao_du_chuoi_trong_mot_giao_dich(PDO $pdo): void
{
    $_SESSION['user_id'] = 1;
    $loi = luuChungChiKemDanhMuc($pdo, keHoachDanhMuc($pdo, nguonMau()), chungChiHopLe(['domain_id' => '', 'ca_id' => '']), 1);
    kiem($loi === [], 'Lưu phải thành công: ' . json_encode($loi, JSON_UNESCAPED_UNICODE));
    $dong = $pdo->query("SELECT d.domain_name, s.ip_address, s.port, s.owner, a.name, a.country, a.is_trusted
                         FROM certificates c JOIN domains d ON d.id = c.domain_id JOIN servers s ON s.id = d.server_id
                         JOIN certificate_authorities a ON a.id = c.ca_id WHERE c.cert_code = 'CERT-100'")->fetch();
    kiem($dong === ['domain_name' => 'shop.example.org', 'ip_address' => '203.0.113.5', 'port' => 443, 'owner' => 'Shop JSC',
        'name' => 'GlobalSign', 'country' => 'Bỉ', 'is_trusted' => 1], 'Chứng chỉ phải gắn đúng chuỗi vừa tạo: ' . json_encode($dong, JSON_UNESCAPED_UNICODE));
    $nhatKy = $pdo->query("SELECT object_type FROM audit_logs WHERE action = 'them' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    kiem($nhatKy === ['may_chu', 'ten_mien', 'to_chuc_cap', 'chung_chi'], 'Mỗi thứ tạo ra một dòng nhật ký: ' . implode(', ', $nhatKy));

    $truoc = demDanhMuc($pdo) + (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    $hong = nguonMau(['host' => 'hong.example.org', 'cac_ip' => ['203.0.113.9'], 'hang' => 'Hãng Thử', 'ben_cap' => ['Hãng Thử']]);
    // Mã quản lý trùng CERT-100 vừa thêm: chứng chỉ, bước cuối cùng, bị từ chối sau khi đã tạo máy chủ, tên miền, hãng.
    $loi = luuChungChiKemDanhMuc($pdo, keHoachDanhMuc($pdo, $hong), chungChiHopLe(['domain_id' => '', 'ca_id' => '']), 1);
    kiem(isset($loi['cert_code']), 'Mã trùng phải bị từ chối');
    $sau = demDanhMuc($pdo) + (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    kiem($sau === $truoc, 'Hỏng ở bước chứng chỉ thì máy chủ, tên miền, hãng, nhật ký phải được trả lại hết');
    unset($_SESSION['user_id']);
}

// Luật: Tên miền mới tìm máy chủ theo IP: trùng IP với máy chủ có sẵn thì dùng chung, không tạo máy chủ mới.
// Vì sao: User 09/11/2026 chọn "mỗi IP một máy chủ": nhiều tên miền trên một IP (như VPS này) là một máy chủ thật.
// Đỏ nghĩa là: Mỗi tên miền đẻ một máy chủ, hoặc tra máy chủ chỉ theo IP đầu mà bỏ các IP còn lại của tên miền.
// Đừng: Gộp theo tên máy chủ; tên máy chủ tự tạo lấy theo tên miền nên luôn khác nhau.
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_hai_ten_mien_cung_ip_chi_mot_may_chu(PDO $pdo): void
{
    $kh = keHoachDanhMuc($pdo, nguonMau(['host' => 'a.example.org', 'cac_ip' => ['203.0.113.1', '192.0.2.10']]));
    kiem($kh['server_id'] === 1 && $kh['may_chu_moi'] === null, 'IP trùng máy chủ web-01 (dù không phải IP đầu) thì dùng chung');

    $soMayChu = fn(): int => (int) $pdo->query('SELECT COUNT(*) FROM servers')->fetchColumn();
    $truoc = $soMayChu();
    $_SESSION['user_id'] = 1;
    foreach (['b.example.org' => 'CERT-101', 'c.example.org' => 'CERT-102'] as $host => $ma) {
        $kh = keHoachDanhMuc($pdo, nguonMau(['host' => $host]));
        $loi = luuChungChiKemDanhMuc($pdo, $kh, chungChiHopLe(['cert_code' => $ma, 'domain_id' => '', 'ca_id' => '']), 1);
        kiem($loi === [], "Lưu $host phải thành công: " . json_encode($loi, JSON_UNESCAPED_UNICODE));
    }
    unset($_SESSION['user_id']);
    kiem($soMayChu() === $truoc + 1, 'Hai tên miền mới cùng IP 203.0.113.5 chỉ được tạo một máy chủ');
    kiem((int) $pdo->query("SELECT COUNT(*) FROM certificate_authorities WHERE name = 'GlobalSign'")->fetchColumn() === 1,
        'Dòng sau phải dùng lại hãng dòng trước vừa tạo');
}

// Luật: Đọc lại tên miền đã có mà IP khác: dùng tên miền và máy chủ cũ, chỉ ghi chú ip_khac; IP máy chủ chỉ đổi khi người dùng bấm Cập nhật.
// Vì sao: Chứng chỉ gắn với tên miền, không với IP; site sau CDN đổi IP theo phút (shopee.vn đổi trong 40 giây, đo 09/11/2026). User chốt không có dịch vụ chạy ngầm dò IP.
// Đỏ nghĩa là: IP mới đẻ máy chủ hoặc tên miền mới, hoặc IP máy chủ bị tự sửa lúc đọc.
// Đừng: Đề xuất xoá máy chủ cũ khi IP đổi; chứng chỉ, gia hạn, cảnh báo đều gắn vào nó.
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_doc_lai_ten_mien_da_co_ip_khac_chi_ghi_chu(PDO $pdo): void
{
    $nguon = nguonMau(['host' => 'cert.cipher.id.vn', 'cac_ip' => ['203.0.113.77'], 'hang' => "Let's Encrypt"]);
    $truoc = demDanhMuc($pdo);
    $kh = keHoachDanhMuc($pdo, $nguon);
    kiem($kh['domain_id'] === 6 && $kh['server_id'] === 5 && $kh['ten_mien_moi'] === null && $kh['may_chu_moi'] === null,
        'Tên miền đã có thì dùng luôn tên miền và máy chủ của nó');
    kiem($kh['ip_khac'] === ['server_id' => 5, 'hostname' => 'cipher-vps', 'ip_cu' => '161.248.146.56', 'ip_moi' => '203.0.113.77'],
        'IP khác phải được ghi chú: ' . json_encode($kh['ip_khac']));
    $ip = fn(): string => (string) $pdo->query('SELECT ip_address FROM servers WHERE id = 5')->fetchColumn();
    kiem($ip() === '161.248.146.56' && demDanhMuc($pdo) === $truoc, 'Đọc không được sửa IP hay tạo gì');
    kiem(keHoachDanhMuc($pdo, nguonMau(['host' => 'cert.cipher.id.vn', 'cac_ip' => ['203.0.113.77', '161.248.146.56']]))['ip_khac'] === null,
        'IP đã lưu vẫn nằm trong các IP vừa tra thì không ghi chú');

    $_SESSION['user_id'] = 1;
    kiem(capNhatIpMayChu($pdo, 5, '203.0.113.77') === null && $ip() === '203.0.113.77', 'Bấm Cập nhật thì IP máy chủ đổi');
    kiem(keHoachDanhMuc($pdo, $nguon)['ip_khac'] === null, 'Cập nhật rồi thì hết ghi chú');
    unset($_SESSION['user_id']);
}

// Luật: Hãng đã có trong danh mục (tên gọn hoặc tên gốc O/CN, không phân biệt hoa thường) thì dùng lại; không tạo bản mới, không đổi mức tin cậy người dùng đã đặt.
// Vì sao: Danh mục nhập tay trước 09/11/2026 có thể ghi "GlobalSign nv-sa"; tạo thêm "GlobalSign" là tách đôi một hãng trong Thống kê.
// Đỏ nghĩa là: Chỉ tra theo tên gọn, hoặc lúc lưu ghi đè is_trusted theo kết quả kiểm chuỗi.
// Đừng: Sửa hãng có sẵn cho khớp tên gọn; đó là dữ liệu của người dùng.
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_hang_co_san_duoc_dung_lai_khong_doi_tin_cay(PDO $pdo): void
{
    $pdo->exec("INSERT INTO certificate_authorities (name, country, is_trusted) VALUES ('globalsign NV-SA', 'Bỉ', 0)");
    $id = (int) $pdo->query("SELECT id FROM certificate_authorities WHERE name = 'globalsign NV-SA'")->fetchColumn();
    $kh = keHoachDanhMuc($pdo, nguonMau());
    kiem($kh['ca_id'] === $id && $kh['hang_moi'] === null, 'Tên gốc O trùng (khác hoa thường) thì dùng lại hãng có sẵn');
    kiem(keHoachDanhMuc($pdo, nguonMau(['hang' => 'DigiCert', 'ben_cap' => ['DigiCert Inc']]))['ca_id'] === 2, 'Tên gọn trùng thì dùng lại');
    $_SESSION['user_id'] = 1;
    kiem(luuChungChiKemDanhMuc($pdo, $kh, chungChiHopLe(['domain_id' => '', 'ca_id' => (string) $id]), 1) === [], 'Lưu phải thành công');
    unset($_SESSION['user_id']);
    kiem((int) $pdo->query("SELECT is_trusted FROM certificate_authorities WHERE id = $id")->fetchColumn() === 0,
        'Mức tin cậy người dùng đặt phải giữ nguyên dù chuỗi kiểm được');
}

// Luật: Nhập hàng loạt nhận tối đa 50 địa chỉ (tách theo xuống dòng, dấu cách, phẩy, chấm phẩy; bỏ trùng); bước Lưu chỉ nhận số thứ tự dòng, dữ liệu chứng chỉ lấy từ phiên.
// Vì sao: User 09/11/2026 chọn xem trước rồi mới lưu; nếu bước Lưu nhận dữ liệu từ trình duyệt thì ai cũng gửi được chứng chỉ, IP, "tin cậy" giả. Trần 50 để một lượt không treo PHP quá vài phút.
// Đỏ nghĩa là: Trang đọc thêm ô POST nào ngoài hanh_dong, dia_chi, cong, chon; hoặc trần, cách tách địa chỉ bị đổi.
// Đừng: Gửi kết quả đọc qua ô ẩn trong form cho "khỏi dùng phiên".
// Nhóm: Đọc chứng chỉ thật (mở rộng 2) (Mở rộng 2)
function test_nhap_hang_loat_chi_luu_du_lieu_doc_trong_phien(PDO $pdo): void
{
    kiem(tachDanhSachDiaChi("a.vn\r\nB.vn, b.vn;c.vn  d.vn:465\n\n") === ['dia_chi' => ['a.vn', 'B.vn', 'c.vn', 'd.vn:465']],
        'Phải tách đúng và bỏ trùng không phân biệt hoa thường');
    kiem(isset(tachDanhSachDiaChi(" \n ")['loi']), 'Ô trống phải báo lỗi');
    $nhieu = array_map(fn(int $i): string => "s$i.example.org", range(1, 51));
    kiem(isset(tachDanhSachDiaChi(implode("\n", $nhieu))['loi']), '51 địa chỉ phải bị từ chối');
    kiem(count(tachDanhSachDiaChi(implode("\n", array_slice($nhieu, 0, 50)))['dia_chi'] ?? []) === 50, '50 địa chỉ được nhận');

    $trang = codeKhongChuThich(__DIR__ . '/../public/admin/chung_chi_nhap.php');
    preg_match_all('/\$_POST\[\'(\w+)\'\]/', $trang, $post);
    preg_match_all('/chuoiPost\(\'(\w+)\'\)/', $trang, $chuoi);
    $o = array_values(array_unique([...$post[1], ...$chuoi[1]]));
    sort($o);
    kiem($o === ['chon', 'cong', 'dia_chi', 'hanh_dong'], 'Trang chỉ được nhận các ô hanh_dong, dia_chi, cong, chon; đang nhận: ' . implode(', ', $o));
    kiem(str_contains($trang, '$daDoc = $_SESSION[\'nhap_hang_loat\'];'), 'Bước Lưu phải lấy kết quả đọc từ phiên');
}

// Luật: Ô gõ Tên miền, Tổ chức cấp chỉ nhận tên có trong danh mục (không phân biệt hoa thường); tên lạ không lưu được.
// Vì sao: Hai ô đổi từ ô chọn sang ô gõ có gợi ý (08/11/2026); gõ tự do mà lưu được thì danh mục và thống kê vỡ.
// Đỏ nghĩa là: idTheoTen() trả về mã cho tên không có, hoặc luuChungChi() nhận chứng chỉ không có tên miền, tổ chức cấp.
// Đừng: Chỉ dựa vào kiểm tra của trình duyệt (data-trong-danh-sach); tắt JavaScript là qua.
// Nhóm: Chứng chỉ: thêm, sửa, thu hồi, xoá (D1, D4, D5, Nf)
function test_o_go_ten_mien_to_chuc_cap_chi_nhan_ten_co_trong_danh_muc(PDO $pdo): void
{
    $danhMuc = [7 => "Let's Encrypt", 9 => 'DigiCert'];
    kiem(idTheoTen($danhMuc, "  LET'S ENCRYPT ") === 7, 'Tên có trong danh mục phải ra đúng mã');
    kiem(idTheoTen($danhMuc, 'Lets Encrypt') === null, 'Tên gần giống không được tính là có');
    kiem(idTheoTen($danhMuc, '') === null, 'Ô trống không ra mã nào');

    $loi = luuChungChi($pdo, null, chungChiHopLe(['ca_id' => '', 'domain_id' => '']), 1);
    kiem(isset($loi['ca_id'], $loi['domain_id']), 'Tên lạ (mã rỗng) phải bị từ chối ở cả hai ô');
    kiem((int) $pdo->query("SELECT COUNT(*) FROM certificates WHERE cert_code = 'CERT-100'")->fetchColumn() === 0, 'Không được lưu');
}

// Luật: Bốn biểu đồ Thống kê giữ trần trục theo toàn bộ chứng chỉ; lọc thì cột ngắn lại trên cùng một thước.
// Vì sao: User 08/11/2026: trục co theo dữ liệu lọc thì cột dài nhất lúc nào cũng kín khung, "không có giá trị so sánh".
// Đỏ nghĩa là: Trần tính theo dữ liệu đã lọc, một biểu đồ quên truyền 'tran', hoặc app.js không dùng nó cho trục số.
// Đừng: Đổi suggestedMax thành max; max cắt đúng số lẻ (22) thay vì làm tròn lên mốc đẹp như lúc chưa lọc.
// Nhóm: Dashboard, thống kê, báo cáo (D8, D9, Ne, Nh)
function test_bieu_do_thong_ke_giu_tran_truc_khi_loc(PDO $pdo): void
{
    $tran = tranBieuDoThongKe($pdo);
    kiem($tran['trang_thai'] === max(demTheoTrangThai($pdo, [], true)), 'Trần trạng thái phải là số lớn nhất khi chưa lọc');
    kiem($tran['to_chuc'] === max(demTheoNhom($pdo, 'to_chuc')), 'Trần tổ chức cấp phải là số lớn nhất khi chưa lọc');
    foreach ([['trang_thai' => 'expired'], ['ca_id' => '2'], ['tu_khoa' => 'mail']] as $nguon) {
        $loc = docBoLoc($nguon);
        kiem(max(demTheoTrangThai($pdo, $loc, true)) <= $tran['trang_thai'], 'Số khi lọc không được vượt trần');
        kiem(max(demTheoNhom($pdo, 'thuat_toan', $loc) ?: [0]) <= $tran['thuat_toan'], 'Số khi lọc không được vượt trần');
    }
    $trang = codeKhongChuThich(__DIR__ . '/../public/admin/thong_ke.php');
    foreach (['trang_thai', 'thang', 'to_chuc', 'thuat_toan'] as $bieuDo) {
        kiem(str_contains($trang, "\$tran['$bieuDo']"), "Biểu đồ $bieuDo phải truyền trần trục");
    }
    kiem(str_contains($trang, '$tran = tranBieuDoThongKe($pdo);'), 'Trần phải lấy từ tranBieuDoThongKe(), không theo bộ lọc');
    kiem(str_contains(file_get_contents(__DIR__ . '/../public/assets/app.js'), 'suggestedMax: duLieu.tran'), 'app.js phải dùng trần cho trục số');
}

// Luật: Caddy nhận PROXY protocol ở 8443 chỉ từ 127.0.0.1 (Xray), kết nối khác nhận như cũ; bước kiem báo lỗi khi Xray gửi mà Caddy không nhận.
// Vì sao: User 08/11/2026 muốn Nhật ký có IP thật thay cho 127.0.0.1; khối này nằm ở phần chung của Caddyfile, bị ghi đè là web qua 443 hỏng và VPN không nhận kết nối mới.
// Đỏ nghĩa là: Script deploy mất khối nhận, tin PROXY protocol từ mọi nơi (ai cũng giả được IP), chặn khách slushai vào thẳng 8443, hoặc bước kiem bỏ dòng bắt lệch.
// Đừng: Đọc IP từ header X-Forwarded-For cho nhanh; khách tự gõ được header đó.
// Nhóm: Nhật ký hoạt động (mở rộng 1) (Mở rộng 1)
function test_caddy_nhan_proxy_protocol_chi_tu_xray(PDO $pdo): void
{
    $tep = __DIR__ . '/../deploy/dung_vps.sh';
    if (!is_file($tep)) {
        return; // Gói nộp không có thư mục deploy/.
    }
    $script = file_get_contents($tep);
    kiem(preg_match('/proxy_protocol \{\s*\\\\t\\\\t\\\\t\\\\tallow 127\.0\.0\.1\/32\s*\\\\t\\\\t\\\\t\\\\tfallback_policy skip/', $script) === 1,
        'Khối nhận phải chỉ tin 127.0.0.1 và nhận như cũ mọi kết nối khác (fallback_policy skip)');
    kiem(preg_match('/fallback_policy skip\s*\\\\t\\\\t\\\\t\}\s*\\\\t\\\\t\\\\ttls\s/', $script) === 1, 'Phải giữ lớp tls ngay sau proxy_protocol, không thì 8443 mất HTTPS');
    kiem(str_contains($script, 'grep -q \'"xver": *[12]\' "$XRAY_CONFIG" && ! grep -q \'proxy_protocol\' "$CADDYFILE"'),
        'Bước kiem phải báo lỗi khi Xray gửi PROXY protocol mà Caddyfile không nhận');
    kiem(!str_contains(codeKhongChuThich(__DIR__ . '/../src/nhat_ky.php'), 'X_FORWARDED_FOR'), 'Nhật ký không được đọc IP từ header khách tự gõ được');
}

// Luật: Vạch 30, 15, 7 và "Hết hạn" trên thanh thời gian của thẻ minh hoạ nằm đúng tỉ lệ số ngày; HTML và app.js dùng chung một thang (data-ld-ngay-dau).
// Vì sao: User 09/11/2026 thấy vạch lệch tỉ lệ: bảng vị trí đặt tay cho 30→15 (15 ngày) và 15→7 (8 ngày) cùng dài 22% thanh.
// Đỏ nghĩa là: Một vạch không nằm ở (1 - ngày/ngày đầu thang) của thanh, hoặc app.js lại tự giữ một bảng vị trí riêng.
// Đừng: Kéo dãn khoảng 7 → Hết hạn cho chữ dễ đọc; muốn rộng hơn thì đổi ngày đầu thang ở một chỗ duy nhất.
// Nhóm: Giao diện, triển khai (Giao diện)
function test_vach_thanh_thoi_gian_dung_ti_le_so_ngay(PDO $pdo): void
{
    ob_start();
    require __DIR__ . '/../views/the_chung_chi.php';
    $the = ob_get_clean();
    kiem(preg_match('/data-ld-ngay-dau="(\d+)"/', $the, $m) === 1, 'Thanh thời gian phải ghi ngày đầu thang (data-ld-ngay-dau)');
    $ngayDau = (int) $m[1];
    preg_match_all('/class="ld-ray-moc[^"]*" style="left: ([\d.]+)%">([^<]+)</', $the, $cacVach, PREG_SET_ORDER);
    kiem(count($cacVach) === 4, 'Phải có đủ 4 vạch: 30, 15, 7, Hết hạn');
    foreach ($cacVach as [, $trai, $nhan]) {
        $ngay = $nhan === 'Hết hạn' ? 0 : (int) $nhan;
        $dung = (1 - $ngay / $ngayDau) * 100;
        kiem(abs((float) $trai - $dung) < 0.01, "Vạch $nhan ở $trai%, đúng tỉ lệ phải ở " . round($dung, 2) . '%');
    }
    kiem(preg_match('/--ld-ray: ([\d.]+)/', $the, $k) === 1 && abs((float) $k[1] - (1 - 7 / $ngayDau)) < 0.0001,
        'Khung tĩnh (còn 7 ngày) phải đặt kim đúng vạch 7');
    // Bỏ dòng chú thích của JS (codeKhongChuThich chỉ hiểu PHP), để chú thích nhắc tên cũ không làm test đỏ giả.
    $js = preg_replace('~^\s*//.*$~m', '', file_get_contents(__DIR__ . '/../public/assets/app.js'));
    kiem(str_contains($js, 'ray.dataset.ldNgayDau') && !str_contains($js, 'VI_TRI_RAY'), 'app.js phải đọc thang từ HTML, không giữ bảng vị trí riêng');
}

// Luật: Trang Nhật ký hiện IP ở mọi độ rộng màn hình: cột IP chỉ có từ 992px, màn hẹp hơn thì IP nằm dưới người làm.
// Vì sao: User 08/11/2026 trên iPhone không xem được IP: cột bị ẩn hẳn (d-none d-lg-table-cell), kéo bảng cũng không thấy.
// Đỏ nghĩa là: Dòng IP cho màn hẹp bị xoá, hoặc nó và cột IP không cùng mốc lg nên có khoảng độ rộng không hiện IP.
// Đừng: Bỏ d-none của cột IP cho "đơn giản"; bảng 5 cột tràn ngang trên điện thoại.
// Nhóm: Nhật ký hoạt động (mở rộng 1) (Mở rộng 1)
function test_nhat_ky_hien_ip_o_moi_do_rong_man_hinh(PDO $pdo): void
{
    $trang = file_get_contents(__DIR__ . '/../public/admin/nhat_ky.php');
    kiem(str_contains($trang, '<th class="d-none d-lg-table-cell">IP</th>'), 'Cột IP hiện từ mốc lg (992px)');
    kiem(preg_match('/class="[^"]*\bd-lg-none\b[^"]*">IP <\?= e\(\$dong\[\'ip\'\]\) \?>/', $trang) === 1,
        'Màn hẹp hơn lg phải có dòng IP thay cho cột IP');
}

// KHÔNG phải test thường: chạy riêng bằng "php tests/chay.php --tai-lieu", lúc đồng bộ tài liệu nộp lần cuối.
// User chốt 09/11/2026: README và docs/ chỉ tạo, đồng bộ một lần trước khi nộp; giữa chừng mỗi lần sửa chỉ cần chú
// thích trong code và thân commit đủ rõ. Để chung bộ test thường thì nó đỏ suốt tới lúc đó, che mất lỗi thật.
// Kiểm: số test ở README, KIEM_THU (XANH, "Cả N", Tổng, tiêu đề nhóm, bảng tổng hợp) bằng số test thật; bảng KIEM_THU
// có đúng một dòng cho mỗi test, câu trùng câu Luật; mọi `tênHàm()` được nhắc có thật; mọi link tới file trong repo
// trỏ tới file có thật; tài liệu không có chữ VPN, Xray. Không kiểm được nghĩa câu chữ: phần đó rà theo git log.
function kiemTaiLieuKhopCode(): void
{
    // Gom mọi chỗ lệch rồi báo một lượt, để sửa tài liệu một lần thay vì chạy lại từng lỗi.
    $loi = [];
    $bao = function (bool $dung, string $cau) use (&$loi): void {
        if (!$dung) {
            $loi[] = $cau;
        }
    };
    $goc = __DIR__ . '/..';
    $cacTep = ['README.md', ...array_map(fn(string $t): string => 'docs/' . basename($t), glob("$goc/docs/*.md"))];
    $cacTep = array_values(array_diff($cacTep, ['docs/BAN_GIAO.md'])); // Ảnh chụp lúc khởi động dự án, cố ý không cập nhật.
    // Máy Windows có thể lưu file với xuống dòng CRLF: đổi về LF để mẫu khớp cuối dòng ($) chạy như nhau mọi nơi.
    $doc = fn(string $duong): string => str_replace("\r\n", "\n", file_get_contents($duong));
    $noiDung = array_combine($cacTep, array_map(fn(string $t): string => $doc("$goc/$t"), $cacTep));

    // (1) và (2): số test và bảng test trong KIEM_THU. Câu Luật lấy thẳng từ file này.
    preg_match_all('/\/\/ Luật: (.+)\n(?:\/\/.*\n)*function\stest_(\w+)\(/', $doc(__FILE__), $m);
    $luat = array_combine($m[2], array_map('trim', $m[1]));
    $soTest = count(array_filter(get_defined_functions()['user'], fn(string $ten): bool => str_starts_with($ten, 'test_')));
    $bao(count($luat) === $soTest, "Có $soTest test nhưng chỉ " . count($luat) . ' test có dòng Luật ngay trên');
    // SPEC, ERD, USECASE, KIEM_THU bị xoá 09/11/2026 để dựng lại một lần trước khi nộp: chưa có thì báo thiếu.
    foreach (['docs/SPEC.md', 'docs/ERD.md', 'docs/USECASE.md', 'docs/KIEM_THU.md'] as $tep) {
        $bao(isset($noiDung[$tep]), "Chưa có $tep");
    }
    $kt = $noiDung['docs/KIEM_THU.md'] ?? '';
    // ERD trong tài liệu phải chứa đúng sơ đồ sinh từ CSDL (sinhErd), không vẽ tay.
    if (isset($noiDung['docs/ERD.md'])) {
        try {
            $pdoErd = ketNoiCsdl('cipher_test');
            napLaiCsdl($pdoErd);
            $bao(str_contains($noiDung['docs/ERD.md'], sinhErd($pdoErd)), 'docs/ERD.md khác sơ đồ sinh từ CSDL: chạy "php tests/chay.php --erd" rồi dán lại');
        } catch (PDOException) {
            $bao(false, 'Cần bật MySQL để so docs/ERD.md với CSDL');
        }
    }
    foreach (['README.md' => '/`XANH: (\d+) test`/', 'docs/KIEM_THU.md' => '/`XANH: (\d+) test`/'] as $tep => $mau) {
        $bao(preg_match($mau, $noiDung[$tep] ?? '', $so) === 1 && (int) $so[1] === $soTest, "$tep ghi XANH: " . ($so[1] ?? '?') . ", thật là $soTest");
    }
    $bao(preg_match('/Cả (\d+) test đều \*\*đạt\*\*/', $kt, $so) === 1 && (int) $so[1] === $soTest, 'KIEM_THU ghi "Cả ' . ($so[1] ?? '?') . " test\", thật là $soTest");
    $bao(preg_match('/\| \*\*Tổng\*\* \| \*\*(\d+)\*\* \|/', $kt, $so) === 1 && (int) $so[1] === $soTest, 'Dòng Tổng của KIEM_THU ghi ' . ($so[1] ?? '?') . ", thật là $soTest");

    // Nhóm của từng test lấy từ dòng "// Nhóm: Tên nhóm (mã)" ngay trên function (ban_do_test.py --kiem bắt thiếu).
    preg_match_all('/^\/\/ Nhóm: (.+) \([^()]+\)\nfunction\stest_(\w+)\(/m', $doc(__FILE__), $m);
    $nhomCua = array_combine($m[2], $m[1]);
    // Test kiểm cấu hình máy chủ triển khai riêng của nhóm có nhắc VPN: tài liệu nộp không được có chữ này (a44b624),
    // nên không có dòng trong bảng nhưng vẫn được đếm vào nhóm của nó và vào tổng.
    $khongLietKe = array_flip(['landing_cong_khai_khong_nhac_den_vpn', 'caddy_nhan_proxy_protocol_chi_tu_xray']);
    foreach ($noiDung as $tep => $chu) {
        $bao(preg_match('/\bVPN\b|Xray/i', $chu) !== 1, "$tep có chữ VPN hoặc Xray; tài liệu nộp không được nhắc hạ tầng này");
    }
    $dongTest = [];
    foreach (preg_split('/^### /m', $kt) as $phan) {
        if (preg_match('/^(.+?) \((\d+) test,/', $phan, $nhom) !== 1) {
            continue;
        }
        preg_match_all('/^\| `(\w+)` \| (.+) \|$/m', $phan, $dong, PREG_SET_ORDER);
        $soNhom = count(array_keys($nhomCua, $nhom[1], true));
        $bao($soNhom === (int) $nhom[2], "Nhóm \"{$nhom[1]}\" ghi {$nhom[2]} test nhưng đếm được $soNhom");
        $bao(preg_match('/^\| ' . preg_quote($nhom[1], '/') . ' \| (\d+) \|/m', $kt, $tongNhom) === 1 && (int) $tongNhom[1] === $soNhom,
            "Bảng tổng hợp ghi nhóm \"{$nhom[1]}\" là " . ($tongNhom[1] ?? '?') . " test, đếm được $soNhom");
        foreach ($dong as [, $ten, $cau]) {
            $bao(!isset($dongTest[$ten]), "Test $ten có hai dòng trong KIEM_THU");
            $bao(($nhomCua[$ten] ?? $nhom[1]) === $nhom[1], "Test $ten nằm ở nhóm \"{$nhom[1]}\" của KIEM_THU nhưng dòng Nhóm trong code ghi \"" . ($nhomCua[$ten] ?? '') . '"');
            $dongTest[$ten] = $cau;
        }
    }
    foreach (array_diff_key($luat, $khongLietKe) as $ten => $cau) {
        $bao(isset($dongTest[$ten]), "KIEM_THU thiếu dòng cho test_$ten");
        $bao(!isset($dongTest[$ten]) || $dongTest[$ten] === $cau, "Câu của $ten trong KIEM_THU khác câu Luật trong code:\n   KIEM_THU: " . ($dongTest[$ten] ?? '') . "\n   Code:     $cau");
    }
    $thua = array_diff(array_keys($dongTest), array_keys(array_diff_key($luat, $khongLietKe)));
    $bao($thua === [], 'KIEM_THU còn dòng cho test không còn tồn tại: ' . implode(', ', $thua));

    // (3) Tên hàm: tìm "function tên(" trong code PHP và JS của repo, hoặc là hàm có sẵn của PHP.
    $code = '';
    foreach (['config', 'src', 'public', 'views'] as $thuMuc) {
        $duyet = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$goc/$thuMuc", FilesystemIterator::SKIP_DOTS));
        foreach ($duyet as $tep) {
            if (in_array($tep->getExtension(), ['php', 'js'], true) && !str_contains($tep->getPathname(), 'tabler')
                && !str_contains($tep->getPathname(), 'chartjs')) {
                $code .= file_get_contents($tep->getPathname());
            }
        }
    }
    $coSan = get_defined_functions()['internal'];
    foreach ($noiDung as $tep => $chu) {
        preg_match_all('/`([A-Za-z_]\w*)\(\)`/', $chu, $ham);
        foreach (array_unique($ham[1]) as $ten) {
            $bao(in_array(strtolower($ten), $coSan, true) || preg_match('/function ' . $ten . '\s*\(/', $code) === 1,
                "$tep nhắc hàm $ten() nhưng code không có hàm này");
        }
    }

    // (4) Link tới file trong repo, tính theo thư mục của tài liệu chứa link. Bỏ link ra ngoài và phần #neo.
    foreach ($noiDung as $tep => $chu) {
        preg_match_all('/\]\(([^)\s]+)\)/', $chu, $link);
        foreach (array_unique($link[1]) as $dich) {
            $duong = explode('#', $dich)[0];
            if ($duong === '' || preg_match('/^[a-z]+:/i', $duong) === 1) {
                continue;
            }
            $bao(file_exists($goc . '/' . dirname($tep) . '/' . rawurldecode($duong)), "$tep có link tới $dich nhưng file không tồn tại");
        }
    }
    kiem($loi === [], count($loi) . " chỗ tài liệu lệch code:
   - " . implode("
   - ", $loi));
}

// Sơ đồ ERD dạng Mermaid, sinh từ chính CSDL vừa nạp schema.sql (user chốt 09/11/2026): ERD dán vào tài liệu lúc nào
// cũng đúng bằng code. Chạy "php tests/chay.php --erd" (cần MySQL), dán kết quả vào docs/ERD.md trong khối ```mermaid;
// --tai-lieu so lại. Chú thích một cột = chú thích cuối dòng ("-- ...") của cột đó trong schema.sql; dòng chú thích
// nằm riêng phía trên một cột là lý do thiết kế, dành cho SPEC, không đưa vào sơ đồ cho gọn.
function sinhErd(PDO $pdo): string
{
    $chuThich = [];
    $bang = null;
    foreach (file(__DIR__ . '/../database/schema.sql', FILE_IGNORE_NEW_LINES) as $dong) {
        if (preg_match('/^CREATE TABLE (\w+)/', $dong, $m) === 1) {
            $bang = $m[1];
        } elseif (str_starts_with($dong, ')')) {
            $bang = null;
        } elseif ($bang !== null && preg_match('/^\s+(\w+)\s.*?--\s*(.+)$/', $dong, $m) === 1) {
            // Mermaid đặt chú thích trong ngoặc kép: đổi ngoặc kép trong câu thành ngoặc đơn.
            $chuThich[$bang][$m[1]] = str_replace('"', "'", trim($m[2]));
        }
    }

    $csdl = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $cau = $pdo->prepare("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
                          WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, COLUMN_NAME");
    $cau->execute([$csdl]);
    $khoaNgoai = $cau->fetchAll();
    $laKhoaNgoai = array_flip(array_map(fn(array $k): string => $k['TABLE_NAME'] . '.' . $k['COLUMN_NAME'], $khoaNgoai));

    // Chỉ bảng thật: v_certificates là view tính từ certificates, không phải thực thể riêng.
    $cau = $pdo->prepare("SELECT c.TABLE_NAME, c.COLUMN_NAME, c.DATA_TYPE, c.IS_NULLABLE, c.COLUMN_KEY
                          FROM information_schema.COLUMNS c
                          JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
                          WHERE c.TABLE_SCHEMA = ? AND t.TABLE_TYPE = 'BASE TABLE'
                          ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION");
    $cau->execute([$csdl]);
    $cacBang = [];
    $choPhepTrong = [];
    foreach ($cau->fetchAll() as $c) {
        $ten = $c['TABLE_NAME'] . '.' . $c['COLUMN_NAME'];
        $choPhepTrong[$ten] = $c['IS_NULLABLE'] === 'YES';
        $khoa = array_filter([$c['COLUMN_KEY'] === 'PRI' ? 'PK' : null, isset($laKhoaNgoai[$ten]) ? 'FK' : null,
            $c['COLUMN_KEY'] === 'UNI' ? 'UK' : null]);
        $cacBang[$c['TABLE_NAME']][] = "        {$c['DATA_TYPE']} {$c['COLUMN_NAME']}" . ($khoa !== [] ? ' ' . implode(', ', $khoa) : '')
            . (isset($chuThich[$c['TABLE_NAME']][$c['COLUMN_NAME']]) ? ' "' . $chuThich[$c['TABLE_NAME']][$c['COLUMN_NAME']] . '"' : '');
    }

    $ra = ['erDiagram'];
    // Một khoá ngoại là một quan hệ 1-n; cột khoá ngoại được trống thì phía cha là "không hoặc một".
    foreach ($khoaNgoai as $k) {
        $cha = $choPhepTrong[$k['TABLE_NAME'] . '.' . $k['COLUMN_NAME']] ? '|o' : '||';
        $ra[] = "    {$k['REFERENCED_TABLE_NAME']} $cha--o{ {$k['TABLE_NAME']} : \"{$k['COLUMN_NAME']}\"";
    }
    foreach ($cacBang as $bang => $cacCot) {
        $ra[] = "    $bang {";
        array_push($ra, ...$cacCot);
        $ra[] = '    }';
    }
    return implode("\n", $ra) . "\n";
}

// Chế độ sinh ERD (xem sinhErd): nạp lại cipher_test rồi in sơ đồ, không chạy test thường.
if (in_array('--erd', $argv, true)) {
    $pdo = ketNoiCsdl('cipher_test');
    napLaiCsdl($pdo);
    echo sinhErd($pdo);
    exit(0);
}

// Chế độ kiểm tài liệu (xem kiemTaiLieuKhopCode): chỉ cần CSDL để so ERD, không chạy test thường.
if (in_array('--tai-lieu', $argv, true)) {
    try {
        kiemTaiLieuKhopCode();
        echo "XANH: tài liệu khớp code
";
        exit(0);
    } catch (RuntimeException $e) {
        echo "ĐỎ: {$e->getMessage()}
";
        exit(1);
    }
}

$pdo = ketNoiCsdl('cipher_test');
$soLoi = 0;
$danhSachTest = array_filter(
    get_defined_functions()['user'],
    fn(string $ten): bool => str_starts_with($ten, 'test_')
);

foreach ($danhSachTest as $ten) {
    try {
        napLaiCsdl($pdo);
        $ten($pdo);
        echo "✅ $ten\n";
    } catch (Throwable $e) {
        $soLoi++;
        echo "❌ $ten\n   {$e->getMessage()}\n";
    }
}

echo $soLoi === 0 ? "\nXANH: " . count($danhSachTest) . " test\n" : "\nĐỎ: $soLoi/" . count($danhSachTest) . " test\n";
exit($soLoi === 0 ? 0 : 1);
