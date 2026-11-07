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
        ghiNhatKyLuu($pdo, $id, 'chung_chi');
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
    if ($cau->rowCount() === 0) {
        return 'Chứng chỉ đã được thu hồi trước đó, hoặc không còn tồn tại.';
    }
    ghiNhatKy($pdo, 'thu_hoi', 'chung_chi', $id, tenDoiTuong($pdo, 'chung_chi', $id));
    return null;
}

// Quy tắc quyền nằm ngay trong hàm, để không trang nào gọi được mà bỏ qua. Nhân viên dùng Thu hồi,
// vì xoá làm mất cả lịch sử gia hạn.
function xoaChungChi(PDO $pdo, int $id, array $nguoi): ?string
{
    if (!laAdmin($nguoi)) {
        return 'Chỉ Admin được xoá chứng chỉ. Bạn có thể dùng Thu hồi.';
    }
    return xoaVaGhiNhatKy($pdo, 'chung_chi', $id, 'Chứng chỉ này còn dữ liệu liên quan nên chưa xoá được.');
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

        // Ghi trong cùng giao dịch: gia hạn hỏng thì nhật ký cũng không có dòng "đã gia hạn".
        ghiNhatKy($pdo, 'gia_han', 'chung_chi', $id, tenDoiTuong($pdo, 'chung_chi', $id) . ', hạn mới ' . date('d/m/Y', strtotime($hanMoi)));
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

// Trần trục số của bốn biểu đồ Thống kê, tính trên TOÀN BỘ chứng chỉ, không theo bộ lọc: lọc thì cột ngắn lại trên
// cùng một thước nên so được với lúc chưa lọc (user yêu cầu 08/11/2026: trục co theo dữ liệu lọc thì cột dài nhất
// lúc nào cũng kín khung, mất giá trị so sánh). Lọc chỉ bớt chứng chỉ, nên số khi lọc không bao giờ vượt trần này.
function tranBieuDoThongKe(PDO $pdo): array
{
    $lonNhat = fn(array $so): int => $so === [] ? 0 : max($so);
    return [
        'trang_thai' => $lonNhat(demTheoTrangThai($pdo, [], true)),
        'thang' => $lonNhat(hetHanTheoThang($pdo)),
        'to_chuc' => $lonNhat(demTheoNhom($pdo, 'to_chuc')),
        'thuat_toan' => $lonNhat(demTheoNhom($pdo, 'thuat_toan')),
    ];
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

// Nhãn trục biểu đồ tháng: '2026-11' thành '11/26', ngắn để nhãn nằm ngang, không phải xoay nghiêng.
// Dùng ở Thống kê và Dashboard.
function nhanTrucThang(array $theoThang): array
{
    return array_map(fn(string $thang): string => substr($thang, 5) . '/' . substr($thang, 2, 2), array_keys($theoThang));
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

// Tên miền in trong bảng: chỉ được xuống dòng ngay sau dấu chấm. Để trình duyệt tự bẻ thì cột hẹp trên
// điện thoại ra "pay.congt" / "y.example" (chụp 08/11/2026 ở 390px). <wbr> là chỗ được phép xuống dòng.
// Trả về HTML đã thoát ký tự, nên in thẳng, không bọc e() thêm lần nữa.
function tenMienNgatDong(string $ten): string
{
    $cacPhan = array_map(fn(string $phan): string => htmlspecialchars($phan, ENT_QUOTES, 'UTF-8'), explode('.', $ten));
    return implode('.<wbr>', $cacPhan);
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

// ---------- Đọc chứng chỉ từ máy chủ (chức năng mở rộng 2) ----------
// Luồng: phanTichDiaChi() -> phanGiaiDiaChi() -> kiemTraDiaChiDoc() -> kết nối TLS tới đúng IP đã kiểm
// -> giaTriTuChungChi(). Web chạy chung máy với dịch vụ chỉ nghe ở 127.0.0.1 và không có mật khẩu,
// nên mọi địa chỉ nội bộ đều bị chặn, kể cả với người đã đăng nhập (SSRF: mượn máy chủ đi dò mạng nội bộ).

// Chỉ các cổng TLS phổ biến (user chốt 08/11/2026): người đăng nhập không dùng được web để quét cổng tuỳ ý.
// STARTTLS (25, 587) không có: phải nói giao thức thư trước khi bắt tay TLS.
const CONG_DOC_CHUNG_CHI = [
    443 => '443 · HTTPS', 8443 => '8443 · HTTPS phụ', 9443 => '9443 · HTTPS phụ', 465 => '465 · SMTP qua TLS',
    993 => '993 · IMAP qua TLS', 995 => '995 · POP3 qua TLS', 636 => '636 · LDAP qua TLS', 853 => '853 · DNS qua TLS',
    5061 => '5061 · SIP qua TLS',
];
const GIAY_CHO_DOC_CHUNG_CHI = 5;

// true nếu không được kết nối tới IP này: nội bộ, loopback, link-local, dành riêng, CGNAT, hoặc không phải IP.
// IPv6 có thể chứa IPv4 bên trong (::ffff:127.0.0.1, ::ffff:7f00:1, ::127.0.0.1, 64:ff9b::7f00:1): tách ra xét
// như IPv4, vì kết nối tới các dạng đó có thể đi tới đúng IPv4 bên trong.
function laDiaChiNoiBo(string $ip): bool
{
    $nhiPhan = @inet_pton($ip);
    if ($nhiPhan === false) {
        return true;
    }
    if (strlen($nhiPhan) === 16) {
        if ($nhiPhan === str_repeat("\0", 16) || $nhiPhan === str_repeat("\0", 15) . "\1") {
            return true; // :: và ::1
        }
        $dau12 = substr($nhiPhan, 0, 12);
        if ($dau12 === str_repeat("\0", 10) . "\xff\xff" || $dau12 === str_repeat("\0", 12)
            || $dau12 === "\x00\x64\xff\x9b" . str_repeat("\0", 8)) {
            return laDiaChiNoiBo(inet_ntop(substr($nhiPhan, 12)));
        }
        // fe80::/10 (link-local): xét thẳng, không dựa vào việc bản PHP này có xếp nó vào "dành riêng" hay không.
        if (ord($nhiPhan[0]) === 0xfe && (ord($nhiPhan[1]) & 0xc0) === 0x80) {
            return true;
        }
    }
    if (filter_var(inet_ntop($nhiPhan), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return true;
    }
    // 100.64.0.0/10 (CGNAT, mạng nội bộ của nhà mạng): filter_var không coi là nội bộ.
    return strlen($nhiPhan) === 4 && ord($nhiPhan[0]) === 100 && (ord($nhiPhan[1]) & 0xc0) === 64;
}

// Tách "https://cert.cipher.id.vn:8443/abc", "cert.cipher.id.vn", "[2001:db8::1]" thành tên máy và cổng.
// Cổng gõ kèm địa chỉ thắng cổng chọn trong danh sách. Trả về ['host' => ..., 'cong' => ...] hoặc ['loi' => ...].
function phanTichDiaChi(string $nhap, int $congChon): array
{
    $nhap = trim($nhap);
    if ($nhap === '') {
        return ['loi' => 'Hãy nhập tên miền hoặc địa chỉ máy chủ.'];
    }
    // Không có "://" thì thêm vào, để parse_url hiểu phần đầu là tên máy chứ không phải đường dẫn.
    $phan = parse_url(str_contains($nhap, '://') ? $nhap : 'tls://' . $nhap);
    $host = strtolower(trim((string) ($phan['host'] ?? ''), '[]'));
    $cong = isset($phan['port']) ? (int) $phan['port'] : $congChon;
    $laIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
    if ($host === '' || (!$laIp && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false)) {
        return ['loi' => 'Địa chỉ không hợp lệ. Ví dụ đúng: cert.cipher.id.vn'];
    }
    return ['host' => $host, 'cong' => $cong];
}

// Mọi IP (v4 trước, v6 sau) mà tên máy trỏ tới. Hỏi thẳng DNS, không qua file hosts của máy chủ.
function phanGiaiDiaChi(string $host): array
{
    if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
        return [$host];
    }
    $v4 = [];
    $v6 = [];
    foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $banGhi) {
        if (isset($banGhi['ip'])) {
            $v4[] = $banGhi['ip'];
        } elseif (isset($banGhi['ipv6'])) {
            $v6[] = $banGhi['ipv6'];
        }
    }
    return [...$v4, ...$v6];
}

// Câu lỗi nếu không được đọc, null nếu được. Tách khỏi phần kết nối để test không cần mạng.
// Chặn khi BẤT KỲ IP nào là nội bộ: tên miền trỏ cả IP công khai lẫn 127.0.0.1 vẫn bị chặn.
function kiemTraDiaChiDoc(string $host, int $cong, array $cacIp): ?string
{
    if (!array_key_exists($cong, CONG_DOC_CHUNG_CHI)) {
        return "Cổng $cong không nằm trong các cổng TLS được phép đọc: " . implode(', ', array_keys(CONG_DOC_CHUNG_CHI)) . '.';
    }
    if ($cacIp === []) {
        return "Không tìm thấy địa chỉ IP của $host. Kiểm tra lại tên miền.";
    }
    foreach ($cacIp as $ip) {
        if (laDiaChiNoiBo($ip)) {
            return ($host === $ip ? "$ip là địa chỉ nội bộ." : "$host trỏ tới địa chỉ nội bộ ($ip).")
                . ' Chỉ đọc được máy chủ có địa chỉ công khai.';
        }
    }
    return null;
}

// Kết nối và lấy chứng chỉ máy chủ đang dùng. Trả về ['chung_chi' => OpenSSLCertificate, 'host', 'cong']
// hoặc ['loi' => ...]. Kết nối tới đúng IP vừa kiểm, gửi tên miền qua SNI: nếu tra DNS lần nữa khi kết nối,
// kẻ xấu có thể đổi bản ghi sang 127.0.0.1 ngay giữa hai lần tra (DNS rebinding).
// Không kiểm chứng chỉ có hợp lệ không: hết hạn, tự ký, sai tên vẫn phải đọc được, vì đó là thứ cần theo dõi.
function docChungChiTuMayChu(string $nhap, int $congChon): array
{
    $diaChi = phanTichDiaChi($nhap, $congChon);
    if (isset($diaChi['loi'])) {
        return $diaChi;
    }
    ['host' => $host, 'cong' => $cong] = $diaChi;
    $cacIp = phanGiaiDiaChi($host);
    $loi = kiemTraDiaChiDoc($host, $cong, $cacIp);
    if ($loi !== null) {
        return ['loi' => $loi];
    }

    $ip = $cacIp[0];
    $ngucCanh = stream_context_create(['ssl' => [
        'capture_peer_cert' => true,
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
        'SNI_enabled' => filter_var($host, FILTER_VALIDATE_IP) === false, // SNI chỉ nhận tên miền, không nhận IP.
        'peer_name' => $host,
    ]]);
    $dich = str_contains($ip, ':') ? "[$ip]" : $ip;
    $ketNoi = @stream_socket_client("ssl://$dich:$cong", $maLoi, $thongBaoLoi, GIAY_CHO_DOC_CHUNG_CHI,
        STREAM_CLIENT_CONNECT, $ngucCanh);
    if ($ketNoi === false) {
        return ['loi' => "Không đọc được chứng chỉ ở $host:$cong. Máy chủ không trả lời trong "
            . GIAY_CHO_DOC_CHUNG_CHI . ' giây, từ chối kết nối, hoặc không dùng TLS ở cổng này.'];
    }
    $chungChi = stream_context_get_params($ketNoi)['options']['ssl']['peer_certificate'] ?? null;
    fclose($ketNoi);
    if (!$chungChi instanceof OpenSSLCertificate) {
        return ['loi' => "Máy chủ $host:$cong không gửi chứng chỉ."];
    }
    return ['chung_chi' => $chungChi, 'host' => $host, 'cong' => $cong];
}

// Giá trị các ô của form chứng chỉ, từ một chứng chỉ (đối tượng hoặc chuỗi PEM). Ngày theo giờ Việt Nam
// (múi giờ đặt ở config/db.php). Thêm 'ben_cap': các tên của tổ chức cấp (O, CN) để tự chọn trong danh mục.
// Thuật toán khoá ngoài RSA, ECDSA (Ed25519, GOST) để trống cho người dùng chọn: bản PHP này không báo được.
function giaTriTuChungChi(OpenSSLCertificate|string $chungChi): array
{
    $p = openssl_x509_parse($chungChi);
    $khoa = openssl_pkey_get_details(openssl_pkey_get_public($chungChi)) ?: [];
    $thuatToan = match ($khoa['type'] ?? null) {
        OPENSSL_KEYTYPE_RSA => 'RSA',
        OPENSSL_KEYTYPE_EC => 'ECDSA',
        default => '',
    };

    // Một chứng chỉ có thể có nhiều CN; lấy cái đầu. Không có CN (chứng chỉ mới hay vậy) thì lấy tên phụ đầu tiên.
    $tenPhu = [];
    foreach (explode(',', $p['extensions']['subjectAltName'] ?? '') as $muc) {
        if (str_starts_with(trim($muc), 'DNS:')) {
            $tenPhu[] = substr(trim($muc), 4);
        }
    }
    $cn = $p['subject']['CN'] ?? ($tenPhu[0] ?? '');
    $cn = is_array($cn) ? (string) reset($cn) : (string) $cn;
    $benCap = array_values(array_filter([$p['issuer']['O'] ?? null, $p['issuer']['CN'] ?? null], 'is_string'));

    return [
        'subject_cn' => $cn,
        'san' => implode(', ', array_values(array_diff($tenPhu, [$cn]))),
        'valid_from' => date('Y-m-d H:i:s', $p['validFrom_time_t']),
        'valid_to' => date('Y-m-d H:i:s', $p['validTo_time_t']),
        'key_algorithm' => $thuatToan,
        'key_size' => isset($khoa['bits']) ? (string) $khoa['bits'] : '',
        'signature_algorithm' => (string) ($p['signatureTypeLN'] ?? ''),
        'fingerprint_sha256' => openssl_x509_fingerprint($chungChi, 'sha256'),
        'serial' => strtoupper((string) ($p['serialNumberHex'] ?? '')),
        'ben_cap' => $benCap,
    ];
}

// Tên miền quản lý cho địa chỉ vừa đọc: trùng hẳn, nếu không thì tên miền cha dài nhất
// ("cert.cipher.id.vn" thuộc "cipher.id.vn"). $cacTenMien: [mã => tên]. Không có thì null.
function chonTenMienChoDiaChi(array $cacTenMien, string $host): ?int
{
    $chon = null;
    $doDai = -1;
    foreach ($cacTenMien as $id => $ten) {
        $ten = strtolower((string) $ten);
        if (($host === $ten || str_ends_with($host, '.' . $ten)) && strlen($ten) > $doDai) {
            $chon = (int) $id;
            $doDai = strlen($ten);
        }
    }
    return $chon;
}

// Tổ chức cấp trùng tên với O hoặc CN của bên cấp; không trùng thì null (để trống, không tự tạo).
function chonToChucCap(array $cacToChuc, array $benCap): ?int
{
    foreach ($benCap as $ten) {
        $id = idTheoTen($cacToChuc, $ten);
        if ($id !== null) {
            return $id;
        }
    }
    return null;
}

// Việc của nút "Đọc chứng chỉ" ở form Thêm: đọc, chuyển thành giá trị các ô, tự chọn tên miền và tổ chức cấp,
// rồi ghi một dòng Nhật ký dù đọc được hay không (để biết ai đã cho máy chủ kết nối đi đâu).
// Trả về ['loi' => ...] hoặc ['gia_tri' => [ô => giá trị], 'host', 'cong', 'ben_cap', 'trung' => chứng chỉ trùng|null].
function docChungChiChoForm(PDO $pdo, string $nhap, int $congChon, array $cacTenMien, array $cacToChuc): array
{
    $ketQua = docChungChiTuMayChu($nhap, $congChon);
    if (isset($ketQua['loi'])) {
        ghiNhatKy($pdo, 'doc_chung_chi', null, null, mb_substr(trim($nhap), 0, 200) . ': ' . $ketQua['loi']);
        return $ketQua;
    }
    $doc = giaTriTuChungChi($ketQua['chung_chi']);
    $giaTri = array_diff_key($doc, ['ben_cap' => true]);
    $giaTri['domain_id'] = (string) (chonTenMienChoDiaChi($cacTenMien, $ketQua['host']) ?? '');
    $giaTri['ca_id'] = (string) (chonToChucCap($cacToChuc, $doc['ben_cap']) ?? '');
    ghiNhatKy($pdo, 'doc_chung_chi', null, null, "{$ketQua['host']}:{$ketQua['cong']}: {$doc['subject_cn']}, hết hạn "
        . date('d/m/Y', strtotime($doc['valid_to'])));
    return [
        'gia_tri' => $giaTri,
        'host' => $ketQua['host'],
        'cong' => $ketQua['cong'],
        'ben_cap' => $doc['ben_cap'],
        'trung' => chungChiTheoFingerprint($pdo, $doc['fingerprint_sha256']),
    ];
}

// Chứng chỉ đã lưu có fingerprint này (id, mã quản lý), null nếu chưa có: báo trước khi người dùng bấm Lưu.
function chungChiTheoFingerprint(PDO $pdo, string $fingerprint): ?array
{
    $cau = $pdo->prepare('SELECT id, cert_code FROM certificates WHERE fingerprint_sha256 = ?');
    $cau->execute([chuanHoaFingerprint($fingerprint)]);
    return $cau->fetch() ?: null;
}
