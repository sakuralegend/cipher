<?php
// Quy tắc của chứng chỉ, phần lõi của đề. Cùng cách làm với src/danh_muc.php: hàm lưu trả về mảng
// lỗi ['tên ô' => 'câu lỗi'], mảng rỗng là thành công; hàm thao tác trả về câu lỗi, hoặc null.
// Số ngày còn lại và trạng thái không tính ở đây: chúng đến từ view v_certificates.

const THUAT_TOAN_KHOA = ['RSA' => 'RSA', 'ECDSA' => 'ECDSA', 'Ed25519' => 'Ed25519', 'GOST' => 'GOST'];
const SO_DONG_MOT_TRANG = 20;

// Tên và màu hiển thị của từng trạng thái; màu là tên màu của Tabler.
const TRANG_THAI = [
    'valid' => ['nhan' => 'Còn hiệu lực', 'mau' => 'green'],
    'expiring' => ['nhan' => 'Sắp hết hạn', 'mau' => 'yellow'],
    'expired' => ['nhan' => 'Đã hết hạn', 'mau' => 'red'],
    'revoked' => ['nhan' => 'Đã thu hồi', 'mau' => 'secondary'],
    'not_yet_valid' => ['nhan' => 'Chưa hiệu lực', 'mau' => 'blue'],
];

// Cùng một vân tay có thể viết EE:7A hay ee 7a. Đưa về một dạng trước khi lưu, nếu không
// ràng buộc không trùng sẽ không phát hiện được.
function chuanHoaFingerprint(string $giaTri): string
{
    return strtolower(preg_replace('/[\s:]/', '', $giaTri));
}

function chuanHoaSerial(string $giaTri): string
{
    return strtoupper(preg_replace('/[\s:]/', '', $giaTri));
}

// Nhận dạng từ ô datetime-local (2026-11-06T10:18:41, có thể thiếu giây) hoặc từ CSDL.
// Trả về dạng để lưu CSDL, hoặc null nếu sai. Giờ được hiểu là giờ Việt Nam.
function chuanHoaNgay(string $giaTri): ?string
{
    $giaTri = str_replace('T', ' ', trim($giaTri));
    foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $dang) {
        $ngay = DateTime::createFromFormat('!' . $dang, $giaTri);
        // So lại với chuỗi gốc: PHP tự đổi 31/02 thành đầu tháng 3 mà không báo lỗi.
        if ($ngay !== false && $ngay->format($dang) === $giaTri && (int) $ngay->format('Y') >= 1000) {
            return $ngay->format('Y-m-d H:i:s');
        }
    }
    return null;
}

// $d: dữ liệu đã qua chuẩn hoá ở luuChungChi().
function kiemTraChungChi(PDO $pdo, array $d): array
{
    $loi = [];
    if (preg_match('/^[A-Za-z0-9._-]{1,30}$/D', $d['cert_code']) !== 1) {
        $loi['cert_code'] = 'Mã quản lý là bắt buộc, tối đa 30 ký tự: chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.';
    }
    if ($d['fingerprint_sha256'] !== '' && preg_match('/^[0-9a-f]{64}$/D', $d['fingerprint_sha256']) !== 1) {
        $loi['fingerprint_sha256'] = 'Fingerprint SHA-256 phải đủ 64 ký tự hex, có thể kèm dấu hai chấm.';
    }
    if ($d['serial'] !== '' && preg_match('/^[0-9A-F]{1,128}$/D', $d['serial']) !== 1) {
        $loi['serial'] = 'Serial gồm ký tự hex, tối đa 128 ký tự, có thể kèm dấu hai chấm.';
    }
    if ($d['subject_cn'] === '' || mb_strlen($d['subject_cn']) > 255) {
        $loi['subject_cn'] = 'Tên chính là bắt buộc, tối đa 255 ký tự.';
    }
    if (mb_strlen($d['san']) > 5000) {
        $loi['san'] = 'Tên phụ tối đa 5000 ký tự.';
    }
    if (timToChuc($pdo, (int) $d['ca_id']) === null) {
        $loi['ca_id'] = 'Hãy chọn tổ chức cấp.';
    }
    if (timTenMien($pdo, (int) $d['domain_id']) === null) {
        $loi['domain_id'] = 'Hãy chọn tên miền.';
    }
    if ($d['valid_from'] === null) {
        $loi['valid_from'] = 'Ngày bắt đầu không hợp lệ.';
    }
    if ($d['valid_to'] === null) {
        $loi['valid_to'] = 'Ngày hết hạn không hợp lệ.';
    } elseif ($d['valid_from'] !== null && $d['valid_to'] <= $d['valid_from']) {
        // So sánh chuỗi đúng thứ tự thời gian, vì cả hai đều ở dạng Y-m-d H:i:s.
        $loi['valid_to'] = 'Ngày hết hạn phải sau ngày bắt đầu.';
    }
    if (!array_key_exists($d['key_algorithm'], THUAT_TOAN_KHOA)) {
        $loi['key_algorithm'] = 'Hãy chọn thuật toán khoá.';
    }
    if (filter_var($d['key_size'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
        $loi['key_size'] = 'Độ dài khoá phải là số dương.';
    }
    if ($d['signature_algorithm'] === '' || mb_strlen($d['signature_algorithm']) > 50) {
        $loi['signature_algorithm'] = 'Thuật toán ký là bắt buộc, tối đa 50 ký tự.';
    }
    return $loi;
}

// $id là null khi thêm mới. $idNguoiTao chỉ dùng khi thêm.
// Cố ý không có is_revoked ở đây: thu hồi chỉ đi qua thuHoiChungChi(), nên form sửa không hoàn tác được.
function luuChungChi(PDO $pdo, ?int $id, array $d, int $idNguoiTao): array
{
    $d['fingerprint_sha256'] = chuanHoaFingerprint($d['fingerprint_sha256']);
    $d['serial'] = chuanHoaSerial($d['serial']);
    $d['valid_from'] = chuanHoaNgay($d['valid_from']);
    $d['valid_to'] = chuanHoaNgay($d['valid_to']);

    $loi = kiemTraChungChi($pdo, $d);
    if ($loi !== []) {
        return $loi;
    }

    // Ô trống lưu thành NULL: UNIQUE cho phép nhiều NULL, nhưng chỉ một chuỗi rỗng.
    $giaTri = [
        $d['cert_code'],
        $d['fingerprint_sha256'] === '' ? null : $d['fingerprint_sha256'],
        $d['serial'] === '' ? null : $d['serial'],
        $d['subject_cn'],
        $d['san'] === '' ? null : $d['san'],
        (int) $d['ca_id'],
        (int) $d['domain_id'],
        $d['valid_from'],
        $d['valid_to'],
        $d['key_algorithm'],
        (int) $d['key_size'],
        $d['signature_algorithm'],
    ];

    try {
        if ($id === null) {
            $pdo->prepare('INSERT INTO certificates (cert_code, fingerprint_sha256, serial, subject_cn, san, ca_id, domain_id,
                               valid_from, valid_to, key_algorithm, key_size, signature_algorithm, created_by)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([...$giaTri, $idNguoiTao]);
        } else {
            $pdo->prepare('UPDATE certificates SET cert_code = ?, fingerprint_sha256 = ?, serial = ?, subject_cn = ?, san = ?,
                               ca_id = ?, domain_id = ?, valid_from = ?, valid_to = ?, key_algorithm = ?, key_size = ?,
                               signature_algorithm = ?
                           WHERE id = ?')
                ->execute([...$giaTri, $id]);
        }
    } catch (PDOException $e) {
        // Bảng có hai cột UNIQUE: tên khoá trong câu lỗi cho biết cột nào bị trùng.
        if (str_contains($e->getMessage(), 'fingerprint_sha256')) {
            return loiNeuTrung($e, 'fingerprint_sha256', 'Fingerprint này đã có ở chứng chỉ khác.');
        }
        return loiNeuTrung($e, 'cert_code', 'Mã quản lý này đã tồn tại.');
    }
    return [];
}

function demChungChi(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM certificates')->fetchColumn();
}

// Sắp theo ngày hết hạn tăng dần, nên chứng chỉ đã hết hạn và sắp hết hạn nằm trên cùng.
function danhSachChungChi(PDO $pdo, int $trang): array
{
    $cau = $pdo->prepare('SELECT c.*, a.name AS ca_name, d.domain_name
                          FROM v_certificates c
                          JOIN certificate_authorities a ON a.id = c.ca_id
                          JOIN domains d ON d.id = c.domain_id
                          ORDER BY c.valid_to, c.id
                          LIMIT ? OFFSET ?');
    $cau->bindValue(1, SO_DONG_MOT_TRANG, PDO::PARAM_INT);
    $cau->bindValue(2, (max($trang, 1) - 1) * SO_DONG_MOT_TRANG, PDO::PARAM_INT);
    $cau->execute();
    return $cau->fetchAll();
}

function timChungChi(PDO $pdo, int $id): ?array
{
    $cau = $pdo->prepare('SELECT c.*, a.name AS ca_name, d.domain_name, u.full_name AS created_by_name
                          FROM v_certificates c
                          JOIN certificate_authorities a ON a.id = c.ca_id
                          JOIN domains d ON d.id = c.domain_id
                          JOIN users u ON u.id = c.created_by
                          WHERE c.id = ?');
    $cau->execute([$id]);
    return $cau->fetch() ?: null;
}

function lichSuGiaHan(PDO $pdo, int $idChungChi): array
{
    $cau = $pdo->prepare('SELECT r.*, u.full_name AS renewed_by_name
                          FROM renewals r JOIN users u ON u.id = r.renewed_by
                          WHERE r.certificate_id = ?
                          ORDER BY r.renewed_at DESC, r.id DESC');
    $cau->execute([$idChungChi]);
    return $cau->fetchAll();
}

// Chỉ xét mã đúng dạng CERT-số, nên mã tự đặt như WEB-1 không làm hỏng phép tính.
function maChungChiKeTiep(PDO $pdo): string
{
    $lonNhat = $pdo->query("SELECT MAX(CAST(SUBSTRING(cert_code, 6) AS UNSIGNED)) FROM certificates
                            WHERE cert_code REGEXP '^CERT-[0-9]+$'")->fetchColumn();
    return sprintf('CERT-%03d', (int) $lonNhat + 1);
}

function thuHoiChungChi(PDO $pdo, int $id): ?string
{
    $cau = $pdo->prepare('UPDATE certificates SET is_revoked = 1 WHERE id = ? AND is_revoked = 0');
    $cau->execute([$id]);
    return $cau->rowCount() === 0 ? 'Chứng chỉ đã được thu hồi trước đó, hoặc không còn tồn tại.' : null;
}

// Quy tắc quyền nằm ngay trong hàm, để không trang nào gọi được mà bỏ qua. Nhân viên dùng Thu hồi,
// vì xoá làm mất cả lịch sử gia hạn.
function xoaChungChi(PDO $pdo, int $id, array $nguoi): ?string
{
    if (!laAdmin($nguoi)) {
        return 'Chỉ Admin được xoá chứng chỉ. Bạn có thể dùng Thu hồi.';
    }
    return xoaHoacBaoConDuLieu($pdo, 'certificates', $id, 'Chứng chỉ này còn dữ liệu liên quan nên chưa xoá được.');
}

// Câu hiển thị số ngày. Chứng chỉ đã thu hồi không còn ý nghĩa đếm ngày.
function moTaSoNgay(array $chungChi): string
{
    if ($chungChi['status'] === 'revoked') {
        return 'Không áp dụng';
    }
    $soNgay = (int) $chungChi['days_left'];
    if ($chungChi['status'] === 'expired') {
        return 'Quá hạn ' . max(abs($soNgay), 0) . ' ngày';
    }
    return 'Còn ' . $soNgay . ' ngày';
}
