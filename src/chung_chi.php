<?php
// Quy tắc của chứng chỉ, phần lõi của đề. Cùng cách làm với src/danh_muc.php: hàm lưu trả về mảng
// lỗi ['tên ô' => 'câu lỗi'], mảng rỗng là thành công; hàm thao tác trả về câu lỗi, hoặc null.
// Số ngày còn lại và trạng thái không tính ở đây: chúng đến từ view v_certificates.

const THUAT_TOAN_KHOA = ['RSA' => 'RSA', 'ECDSA' => 'ECDSA', 'Ed25519' => 'Ed25519', 'GOST' => 'GOST'];
const SO_DONG_MOT_TRANG = 20;

// Tên và màu hiển thị của từng trạng thái; màu là tên màu của Tabler.
// Thứ tự ở đây là mức gấp: danh sách, hàng nút trạng thái đều xếp theo thứ tự này.
const TRANG_THAI = [
    'expired' => ['nhan' => 'Đã hết hạn', 'mau' => 'red'],
    'expiring' => ['nhan' => 'Sắp hết hạn', 'mau' => 'yellow'],
    'valid' => ['nhan' => 'Còn hiệu lực', 'mau' => 'green'],
    'not_yet_valid' => ['nhan' => 'Chưa hiệu lực', 'mau' => 'blue'],
    'revoked' => ['nhan' => 'Đã thu hồi', 'mau' => 'secondary'],
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

// Dùng chung cho form sửa và form gia hạn. $d: fingerprint, serial đã chuẩn hoá.
function kiemTraVanTayVaSerial(array $d): array
{
    $loi = [];
    if ($d['fingerprint_sha256'] !== '' && preg_match('/^[0-9a-f]{64}$/D', $d['fingerprint_sha256']) !== 1) {
        $loi['fingerprint_sha256'] = 'Fingerprint SHA-256 phải đủ 64 ký tự hex, có thể kèm dấu hai chấm.';
    }
    if ($d['serial'] !== '' && preg_match('/^[0-9A-F]{1,128}$/D', $d['serial']) !== 1) {
        $loi['serial'] = 'Serial gồm ký tự hex, tối đa 128 ký tự, có thể kèm dấu hai chấm.';
    }
    return $loi;
}

// $d: dữ liệu đã qua chuẩn hoá ở luuChungChi().
function kiemTraChungChi(PDO $pdo, array $d): array
{
    $loi = kiemTraVanTayVaSerial($d);
    if (preg_match('/^[A-Za-z0-9._-]{1,30}$/D', $d['cert_code']) !== 1) {
        $loi['cert_code'] = 'Mã quản lý là bắt buộc, tối đa 30 ký tự: chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.';
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

// ---------- Tìm kiếm, lọc ----------

// Đọc bộ lọc từ địa chỉ trang. Giá trị sai bị coi như chưa chọn, vì đây chỉ là bộ lọc: người dùng
// sửa tay địa chỉ thì vẫn thấy trang, không thấy lỗi. Trả về chuỗi; '' nghĩa là không lọc.
function docBoLoc(array $nguon): array
{
    // Địa chỉ có thể chứa mảng (tu_khoa[]=x) hoặc byte không phải UTF-8.
    $doc = function (string $khoa) use ($nguon): string {
        $giaTri = $nguon[$khoa] ?? '';
        return is_string($giaTri) && mb_check_encoding($giaTri, 'UTF-8') ? trim($giaTri) : '';
    };
    $maSo = function (string $khoa) use ($doc): string {
        $so = filter_var($doc($khoa), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $so === false ? '' : (string) $so;
    };
    $ngay = function (string $khoa) use ($doc): string {
        return chuanHoaNgay($doc($khoa) . ' 00:00:00') === null ? '' : $doc($khoa);
    };

    return [
        'tu_khoa' => mb_substr($doc('tu_khoa'), 0, 255),
        'trang_thai' => array_key_exists($doc('trang_thai'), TRANG_THAI) ? $doc('trang_thai') : '',
        'ca_id' => $maSo('ca_id'),
        'domain_id' => $maSo('domain_id'),
        'server_id' => $maSo('server_id'),
        'het_han_tu' => $ngay('het_han_tu'),
        'het_han_den' => $ngay('het_han_den'),
    ];
}

// Dựng phần WHERE từ kết quả của docBoLoc(). Trả về [câu SQL, các giá trị cho dấu ?].
// Thống kê và báo cáo cũng lọc bằng hàm này, để mọi màn hình hiểu bộ lọc giống nhau.
function dieuKienLoc(array $loc): array
{
    $dieuKien = [];
    $thamSo = [];
    $tuKhoa = $loc['tu_khoa'] ?? '';

    if ($tuKhoa !== '') {
        // Thoát % và _, nếu không người dùng gõ _ sẽ khớp mọi chứng chỉ.
        $mau = '%' . addcslashes($tuKhoa, '\\%_') . '%';
        $cot = ['c.cert_code LIKE ?', 'c.subject_cn LIKE ?', 'c.san LIKE ?'];
        $thamSo = [$mau, $mau, $mau];

        // Fingerprint, serial lưu ở dạng đã chuẩn hoá, nên từ khoá phải qua cùng phép chuẩn hoá,
        // để dán EE:7A vẫn tìm thấy ee7a. Từ khoá không phải hex thì không thể khớp hai cột này.
        $hex = chuanHoaFingerprint($tuKhoa);
        if (ctype_xdigit($hex)) {
            $cot[] = 'c.fingerprint_sha256 LIKE ?';
            $cot[] = 'c.serial LIKE ?';
            array_push($thamSo, "%$hex%", '%' . strtoupper($hex) . '%');
        }
        $dieuKien[] = '(' . implode(' OR ', $cot) . ')';
    }

    $bangGiaTri = [
        'trang_thai' => 'c.status = ?',
        'ca_id' => 'c.ca_id = ?',
        'domain_id' => 'c.domain_id = ?',
        'server_id' => 'd.server_id = ?',
        'het_han_tu' => 'c.valid_to >= ?',
        // Cộng một ngày để gồm cả ngày cuối: chứng chỉ hết hạn lúc 23:59 ngày đó vẫn được tính.
        'het_han_den' => 'c.valid_to < DATE_ADD(?, INTERVAL 1 DAY)',
    ];
    foreach ($bangGiaTri as $khoa => $cauSql) {
        if (($loc[$khoa] ?? '') !== '') {
            $dieuKien[] = $cauSql;
            $thamSo[] = $loc[$khoa];
        }
    }

    return [$dieuKien === [] ? '' : 'WHERE ' . implode(' AND ', $dieuKien), $thamSo];
}

// Danh sách và phép đếm đọc cùng nguồn này, để số trang luôn khớp với số dòng khi có lọc.
const NGUON_CHUNG_CHI = 'v_certificates c
                         JOIN certificate_authorities a ON a.id = c.ca_id
                         JOIN domains d ON d.id = c.domain_id
                         JOIN servers s ON s.id = d.server_id';

function demChungChi(PDO $pdo, array $loc = []): int
{
    [$where, $thamSo] = dieuKienLoc($loc);
    $cau = $pdo->prepare('SELECT COUNT(*) FROM ' . NGUON_CHUNG_CHI . " $where");
    $cau->execute($thamSo);
    return (int) $cau->fetchColumn();
}

// Mặc định dùng cho hàng nút: đếm theo các bộ lọc khác, bỏ qua trạng thái đang chọn, để bấm sang nút
// khác biết trước được bao nhiêu dòng. $giuTrangThai = true dùng cho thống kê: tổng phải khớp các
// biểu đồ khác đang lọc. Trạng thái không có dòng nào vẫn có mặt với số 0.
function demTheoTrangThai(PDO $pdo, array $loc = [], bool $giuTrangThai = false): array
{
    [$where, $thamSo] = dieuKienLoc($giuTrangThai ? $loc : ['trang_thai' => ''] + $loc);
    $cau = $pdo->prepare('SELECT c.status, COUNT(*) FROM ' . NGUON_CHUNG_CHI . " $where GROUP BY c.status");
    $cau->execute($thamSo);
    $dem = $cau->fetchAll(PDO::FETCH_KEY_PAIR);

    $ketQua = [];
    foreach (array_keys(TRANG_THAI) as $trangThai) {
        $ketQua[$trangThai] = (int) ($dem[$trangThai] ?? 0);
    }
    return $ketQua;
}

// ---------- Sắp xếp ----------

// Tên cột trên địa chỉ trang => biểu thức SQL. Tên cột không truyền được bằng dấu ?, nên chỉ những
// khoá viết sẵn ở đây mới vào được câu SQL; giá trị người dùng gửi lên không bao giờ được ghép vào.
const COT_SAP_XEP = [
    'ma' => 'c.cert_code',
    'ten' => 'c.subject_cn',
    'to_chuc' => 'a.name',
    'het_han' => 'c.valid_to',
    'con_lai' => 'c.valid_to',
    'trang_thai' => null, // Tính ở mucGapSql(), vì lấy từ thứ tự của TRANG_THAI.
];

// Giá trị sai bị coi như chưa chọn, giống docBoLoc(). Trả về ['sap_xep' => khoá hoặc '', 'chieu' => 'tang' | 'giam'].
function docSapXep(array $nguon): array
{
    $cot = $nguon['sap_xep'] ?? '';
    if (!is_string($cot) || !array_key_exists($cot, COT_SAP_XEP)) {
        return ['sap_xep' => '', 'chieu' => 'tang'];
    }
    return ['sap_xep' => $cot, 'chieu' => ($nguon['chieu'] ?? '') === 'giam' ? 'giam' : 'tang'];
}

// Dãy trạng thái lấy từ khoá của TRANG_THAI viết trong code, không có dữ liệu người dùng.
function mucGapSql(): string
{
    return "FIELD(c.status, '" . implode("', '", array_keys(TRANG_THAI)) . "')";
}

// Mặc định: việc gấp lên đầu, đã thu hồi xuống cuối; trong mỗi nhóm, hạn gần hơn đứng trước.
// Mọi cách xếp đều kết thúc bằng id để các dòng bằng nhau không đổi chỗ giữa hai trang.
function menhDeSapXep(array $sapXep): string
{
    $cot = $sapXep['sap_xep'] ?? '';
    $chieu = ($sapXep['chieu'] ?? '') === 'giam' ? 'DESC' : 'ASC';
    if ($cot === '') {
        return mucGapSql() . ', c.valid_to, c.id';
    }
    if ($cot === 'trang_thai') {
        return mucGapSql() . " $chieu, c.valid_to, c.id";
    }
    if ($cot === 'con_lai') {
        // Chứng chỉ đã thu hồi không có số ngày còn lại, nên luôn nằm cuối dù xếp chiều nào.
        return "c.is_revoked, c.valid_to $chieu, c.id";
    }
    return COT_SAP_XEP[$cot] . " $chieu, c.valid_to, c.id";
}

// Địa chỉ một trang kèm lọc và cách xếp hiện tại; $doi đổi riêng vài khoá, giá trị '' là bỏ khoá.
// Danh sách, thống kê và hai trang báo cáo cùng đọc một bộ tham số này.
function diaChiCoLoc(string $trang, array $loc, array $sapXep = [], array $doi = []): string
{
    $thamSo = [...$loc, ...(($sapXep['sap_xep'] ?? '') === '' ? [] : $sapXep), ...$doi];
    $thamSo = array_filter($thamSo, fn($giaTri): bool => $giaTri !== '');
    return url($trang . ($thamSo === [] ? '' : '?' . http_build_query($thamSo)));
}

const COT_CHUNG_CHI = 'SELECT c.*, a.name AS ca_name, d.domain_name, s.hostname AS server_name FROM ';

// Toàn bộ kết quả, không chia trang, cho báo cáo: báo cáo chỉ có một phần là báo cáo sai.
function tatCaChungChi(PDO $pdo, array $loc = [], array $sapXep = []): array
{
    [$where, $thamSo] = dieuKienLoc($loc);
    $cau = $pdo->prepare(COT_CHUNG_CHI . NGUON_CHUNG_CHI . " $where ORDER BY " . menhDeSapXep($sapXep));
    $cau->execute($thamSo);
    return $cau->fetchAll();
}

function danhSachChungChi(PDO $pdo, int $trang, array $loc = [], array $sapXep = []): array
{
    [$where, $thamSo] = dieuKienLoc($loc);
    $cau = $pdo->prepare(COT_CHUNG_CHI . NGUON_CHUNG_CHI . " $where
                          ORDER BY " . menhDeSapXep($sapXep) . '
                          LIMIT ? OFFSET ?');
    $viTri = 1;
    foreach ($thamSo as $giaTri) {
        $cau->bindValue($viTri++, $giaTri);
    }
    // LIMIT, OFFSET phải gắn kiểu số; truyền qua execute() sẽ thành chuỗi và CSDL từ chối.
    $cau->bindValue($viTri++, SO_DONG_MOT_TRANG, PDO::PARAM_INT);
    $cau->bindValue($viTri, (max($trang, 1) - 1) * SO_DONG_MOT_TRANG, PDO::PARAM_INT);
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

// ---------- Gia hạn ----------

// Gia hạn sửa trên cùng dòng chứng chỉ, nên thông tin cũ được chép sang renewals trước (QD-11).
// Trả về mảng lỗi theo ô; lỗi không thuộc ô nào nằm ở khoá 'chung'. Mảng rỗng là thành công.
// $d: valid_to, fingerprint_sha256, serial, note.
function giaHanChungChi(PDO $pdo, int $id, array $d, int $idNguoiThucHien): array
{
    $hanMoi = chuanHoaNgay($d['valid_to']);
    $moi = [
        'fingerprint_sha256' => chuanHoaFingerprint($d['fingerprint_sha256']),
        'serial' => chuanHoaSerial($d['serial']),
    ];
    $ghiChu = trim($d['note']);

    // Giao dịch: lịch sử và chứng chỉ phải đổi cùng nhau, lỗi ở bước nào thì không bước nào có hiệu lực.
    $pdo->beginTransaction();
    try {
        // FOR UPDATE giữ dòng này tới khi xong, để hai người gia hạn cùng lúc không chép nhầm hạn cũ.
        $cau = $pdo->prepare('SELECT valid_to, fingerprint_sha256, serial, is_revoked FROM certificates WHERE id = ? FOR UPDATE');
        $cau->execute([$id]);
        $cu = $cau->fetch();

        $loi = kiemTraGiaHan($cu, $hanMoi, $moi, $ghiChu);
        if ($loi !== []) {
            $pdo->rollBack();
            return $loi;
        }

        $pdo->prepare('INSERT INTO renewals (certificate_id, old_valid_to, new_valid_to, old_fingerprint, old_serial, renewed_by, note)
                       VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$id, $cu['valid_to'], $hanMoi, $cu['fingerprint_sha256'], $cu['serial'], $idNguoiThucHien, $ghiChu === '' ? null : $ghiChu]);

        // Ô trống lưu thành NULL chứ không giữ giá trị cũ: gia hạn là cấp lại, nên fingerprint cũ
        // không còn đúng với chứng chỉ mới. Giá trị cũ vẫn nằm trong lịch sử.
        $pdo->prepare('UPDATE certificates SET valid_to = ?, fingerprint_sha256 = ?, serial = ? WHERE id = ?')
            ->execute([$hanMoi, $moi['fingerprint_sha256'] === '' ? null : $moi['fingerprint_sha256'], $moi['serial'] === '' ? null : $moi['serial'], $id]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        return loiNeuTrung($e, 'fingerprint_sha256', 'Fingerprint này đã có ở chứng chỉ khác.');
    }
    return [];
}

// $cu: dòng chứng chỉ hiện tại, false nếu không còn. $hanMoi: null nếu sai dạng ngày.
function kiemTraGiaHan(array|false $cu, ?string $hanMoi, array $moi, string $ghiChu): array
{
    if ($cu === false) {
        return ['chung' => 'Chứng chỉ không còn tồn tại.'];
    }
    if ($cu['is_revoked']) {
        return ['chung' => 'Chứng chỉ đã thu hồi không gia hạn được. Muốn dùng lại, hãy thêm chứng chỉ mới.'];
    }
    $loi = kiemTraVanTayVaSerial($moi);
    // So sánh chuỗi đúng thứ tự thời gian, vì đều ở dạng Y-m-d H:i:s.
    if ($hanMoi === null || $hanMoi <= $cu['valid_to'] || $hanMoi <= date('Y-m-d H:i:s')) {
        $loi['valid_to'] = 'Ngày hết hạn mới phải sau hạn hiện tại và sau thời điểm này.';
    }
    if (mb_strlen($ghiChu) > 255) {
        $loi['note'] = 'Ghi chú tối đa 255 ký tự.';
    }
    return $loi;
}

// ---------- Cảnh báo ----------

// Tạo cảnh báo theo mốc gần nhất đã chạm. Gọi khi mở Dashboard hoặc trang Cảnh báo, nên chạy lặp
// lại liên tục: dòng đã có bị UNIQUE (chứng chỉ, hạn, mốc) chặn và giữ nguyên, kể cả trạng thái đã xem.
function taoCanhBao(PDO $pdo): void
{
    $pdo->exec("INSERT INTO alerts (certificate_id, valid_to, threshold_days)
                SELECT id, valid_to, CASE WHEN days_left <= 7 THEN 7 WHEN days_left <= 15 THEN 15 ELSE 30 END
                FROM v_certificates
                WHERE status = 'expiring'
                ON DUPLICATE KEY UPDATE alerts.id = alerts.id");
}

// Chỉ cảnh báo của chu kỳ hiện tại (hạn của cảnh báo trùng hạn của chứng chỉ). Giữ chứng chỉ đã hết
// hạn vì đó là trường hợp gấp nhất; bỏ chứng chỉ đã thu hồi vì không còn việc gì để xử lý.
const CANH_BAO_DANG_XET = "alerts a
                           JOIN v_certificates c ON c.id = a.certificate_id AND c.valid_to = a.valid_to
                           WHERE c.status IN ('expiring', 'expired')";

// Cảnh báo chưa xem lên đầu, trong đó chứng chỉ gần hết hạn nhất đứng trước.
function danhSachCanhBao(PDO $pdo): array
{
    return $pdo->query('SELECT a.id, a.threshold_days, a.status AS alert_status, a.created_at,
                               c.id AS certificate_id, c.cert_code, c.subject_cn, c.valid_to, c.days_left, c.status
                        FROM ' . CANH_BAO_DANG_XET . "
                        ORDER BY a.status = 'new' DESC, c.valid_to, a.threshold_days")->fetchAll();
}

function demCanhBaoMoi(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM ' . CANH_BAO_DANG_XET . " AND a.status = 'new'")->fetchColumn();
}

// "Đã xem" tính cho mọi người dùng: bảng alerts chỉ có một trạng thái cho mỗi cảnh báo.
function danhDauDaXem(PDO $pdo, int $id): ?string
{
    $cau = $pdo->prepare("UPDATE alerts SET status = 'seen' WHERE id = ? AND status = 'new'");
    $cau->execute([$id]);
    return $cau->rowCount() === 0 ? 'Cảnh báo đã được đánh dấu trước đó, hoặc không còn tồn tại.' : null;
}

// ---------- Dashboard, thống kê ----------

const SO_DONG_CAN_XU_LY = 10;

// Bảng "Cần xử lý ngay": chỉ chứng chỉ sắp hết hạn, gần hạn nhất trước. Chứng chỉ đã quá hạn chỉ hiện
// ở thẻ số, để chứng chỉ quá hạn lâu không che mất việc còn cứu được.
function chungChiSapHetHan(PDO $pdo): array
{
    return array_slice(danhSachChungChi($pdo, 1, ['trang_thai' => 'expiring']), 0, SO_DONG_CAN_XU_LY);
}

// Khoá viết sẵn => cột SQL. Như COT_SAP_XEP, chỉ các khoá này mới vào được câu SQL.
const NHOM_THONG_KE = ['to_chuc' => 'a.name', 'thuat_toan' => 'c.key_algorithm'];

// [tên nhóm => số chứng chỉ], nhiều nhất trước. Đếm mọi chứng chỉ khớp bộ lọc, kể cả đã thu hồi,
// để tổng khớp với biểu đồ trạng thái; muốn bỏ đã thu hồi thì dùng bộ lọc.
function demTheoNhom(PDO $pdo, string $nhom, array $loc = []): array
{
    $cot = NHOM_THONG_KE[$nhom];
    [$where, $thamSo] = dieuKienLoc($loc);
    $cau = $pdo->prepare("SELECT $cot, COUNT(*) AS so FROM " . NGUON_CHUNG_CHI . " $where GROUP BY $cot ORDER BY so DESC, $cot");
    $cau->execute($thamSo);
    return array_map('intval', $cau->fetchAll(PDO::FETCH_KEY_PAIR));
}

// ['2026-11' => số chứng chỉ hết hạn trong tháng], đủ 12 tháng kể từ tháng này, tháng không có là 0.
// Dự báo việc gia hạn sắp tới, nên bỏ chứng chỉ đã thu hồi và đã quá hạn.
// $homNay chỉ truyền khi test, để thử được ngày 31.
function hetHanTheoThang(PDO $pdo, array $loc = [], ?string $homNay = null): array
{
    $bayGio = new DateTimeImmutable($homNay ?? 'now');
    // Tính từ ngày đầu tháng: cộng tháng từ ngày 31 sẽ nhảy cóc qua tháng ngắn hơn.
    $dauThang = $bayGio->modify('first day of this month')->setTime(0, 0);
    $ketQua = [];
    for ($i = 0; $i < 12; $i++) {
        $ketQua[$dauThang->modify("+$i month")->format('Y-m')] = 0;
    }

    [$where, $thamSo] = dieuKienLoc($loc);
    $cau = $pdo->prepare("SELECT DATE_FORMAT(c.valid_to, '%Y-%m'), COUNT(*) FROM " . NGUON_CHUNG_CHI . ' '
        . ($where === '' ? 'WHERE' : "$where AND") . ' c.is_revoked = 0 AND c.valid_to >= ? AND c.valid_to < ?
        GROUP BY 1');
    $cau->execute([...$thamSo, $bayGio->format('Y-m-d H:i:s'), $dauThang->modify('+12 month')->format('Y-m-d H:i:s')]);
    foreach ($cau->fetchAll(PDO::FETCH_KEY_PAIR) as $thang => $so) {
        $ketQua[$thang] = (int) $so;
    }
    return $ketQua;
}

// ---------- Báo cáo ----------

const COT_CSV = ['Mã quản lý', 'Tên chính', 'Tên phụ', 'Tổ chức cấp', 'Tên miền', 'Máy chủ', 'Ngày bắt đầu', 'Ngày hết hạn',
    'Còn lại', 'Trạng thái', 'Thuật toán khoá', 'Độ dài khoá', 'Thuật toán ký', 'Fingerprint SHA-256', 'Serial'];

// Excel chạy ô bắt đầu bằng = + - @ như công thức, nên dữ liệu người dùng nhập có thể thành lệnh khi
// người khác mở file. Thêm dấu nháy đơn phía trước để Excel coi đó là chữ. Tab, xuống dòng đầu ô cũng
// bị Excel bỏ qua, nên xét sau khi bỏ khoảng trắng đầu ô.
function oCsvAnToan(?string $giaTri): string
{
    $giaTri = (string) $giaTri;
    return preg_match('/^\s*[=+\-@]/', $giaTri) === 1 ? "'" . $giaTri : $giaTri;
}

// Một dòng CSV theo đúng thứ tự COT_CSV, từ một dòng của tatCaChungChi().
function dongCsv(array $cc): array
{
    return array_map('oCsvAnToan', [
        $cc['cert_code'], $cc['subject_cn'], $cc['san'], $cc['ca_name'], $cc['domain_name'], $cc['server_name'],
        // Năm-tháng-ngày: mọi máy hiểu đúng, bảng tính tự hiện theo định dạng của máy đó. Dạng 09/11/2026
        // bị máy định dạng Mỹ đọc thành 11 tháng 9. Không kèm giờ, vì ngày kèm giờ dài hơn cột và hiện #####.
        date('Y-m-d', strtotime($cc['valid_from'])), date('Y-m-d', strtotime($cc['valid_to'])),
        moTaSoNgay($cc), TRANG_THAI[$cc['status']]['nhan'], $cc['key_algorithm'], (string) $cc['key_size'],
        $cc['signature_algorithm'], $cc['fingerprint_sha256'], $cc['serial'],
    ]);
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
