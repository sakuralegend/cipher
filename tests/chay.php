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

function napLaiCsdl(PDO $pdo): void
{
    // Chặn cuối: schema.sql xoá sạch bảng, nên tuyệt đối không được chạy nhầm CSDL.
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'cipher_test') {
        throw new RuntimeException('Từ chối nạp lại: không phải CSDL cipher_test');
    }
    foreach (['schema.sql', 'seed.sql'] as $tenFile) {
        $sql = file_get_contents(__DIR__ . '/../database/' . $tenFile);
        // Chạy từng câu, vì gửi cả file một lần thì lỗi ở câu sau có thể bị bỏ qua không báo.
        foreach (preg_split('/;[ \t]*\r?\n/', $sql) as $cauLenh) {
            if (trim($cauLenh) !== '') {
                $pdo->exec($cauLenh);
            }
        }
    }
}

function kiem(bool $dieuKien, string $thongBao): void
{
    if (!$dieuKien) {
        throw new RuntimeException($thongBao);
    }
}

// Đạt khi CSDL từ chối vì vi phạm ràng buộc (SQLSTATE 23000). Lỗi loại khác, như sai cú pháp,
// vẫn làm test đỏ, để test không xanh giả.
function phaiBiTuChoi(PDO $pdo, string $sql, string $thongBao): void
{
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        kiem($e->getCode() === '23000', "$thongBao: lỗi không phải ràng buộc: " . $e->getMessage());
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
function test_khong_xoa_duoc_nguoi_dung_da_co_lich_su(PDO $pdo): void
{
    phaiBiTuChoi($pdo, 'DELETE FROM users WHERE id = 1', 'Người đã tạo chứng chỉ');
    phaiBiTuChoi($pdo, 'DELETE FROM users WHERE id = 2', 'Người đã gia hạn');

    $themNguoi = "INSERT INTO users (id, username, password_hash, full_name, role_id)
                  VALUES (3, 'moi', 'x', 'Người mới', 2)";
    $pdo->exec($themNguoi);
    $pdo->exec("INSERT INTO audit_logs (user_id, action) VALUES (3, 'login')");
    phaiBiTuChoi($pdo, 'DELETE FROM users WHERE id = 3', 'Người có nhật ký');

    $pdo->exec('DELETE FROM audit_logs WHERE user_id = 3');
    $pdo->exec('DELETE FROM users WHERE id = 3');
    kiem((int) $pdo->query('SELECT COUNT(*) FROM users WHERE id = 3')->fetchColumn() === 0, 'Người mới phải xoá được');
}

// Luật: Không xoá được vai trò, máy chủ, tên miền, CA khi còn dữ liệu phụ thuộc.
// Vì sao: Nếu CSDL cho xoá dây chuyền, xoá một máy chủ có thể làm mất hàng loạt chứng chỉ.
// Đỏ nghĩa là: một khoá ngoại đã đổi khỏi RESTRICT.
// Đừng: đổi sang CASCADE cho tiện; người dùng phải chuyển hoặc xoá dữ liệu con trước.
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
// Đừng: viết ngày cố định trong seed, vì các mốc sẽ trôi trước buổi bảo vệ.
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
function test_moi_trang_quan_tri_deu_kiem_tra_dang_nhap(PDO $pdo): void
{
    $soTrang = 0;
    $cacFile = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../public/admin', FilesystemIterator::SKIP_DOTS));
    foreach ($cacFile as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $soTrang++;
        // Bỏ chú thích trước khi tìm, để một dòng chú thích không qua mặt được test.
        $code = implode('', array_map(
            fn($manh) => is_array($manh) && in_array($manh[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : (is_array($manh) ? $manh[1] : $manh),
            token_get_all(file_get_contents($file->getPathname()))
        ));
        kiem(preg_match('/\byeuCau(DangNhap|Admin)\(/', $code) === 1, $file->getFilename() . ' chưa kiểm tra đăng nhập');
    }
    kiem($soTrang > 0, 'Không tìm thấy trang quản trị nào, đường dẫn trong test có thể đã sai');
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
