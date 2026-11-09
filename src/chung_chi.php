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
    // Đã thu hồi là trạng thái cuối, bản ghi chỉ còn để tra cứu (user chốt 09/11/2026): không sửa được, giống như không
    // gia hạn được. Chặn ở đây chứ không chỉ ẩn nút, vì ai cũng gõ thẳng được chung_chi_form.php?id=.
    if ($id !== null) {
        $cau = $pdo->prepare('SELECT is_revoked FROM certificates WHERE id = ?');
        $cau->execute([$id]);
        if ((int) $cau->fetchColumn() === 1) {
            return ['thu_hoi' => 'Chứng chỉ đã thu hồi chỉ còn để tra cứu, không sửa được. Theo dõi chứng chỉ mới thì thêm mới.'];
        }
    }
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

// ca_tin_cay: mức tin cậy của tổ chức cấp, chamDiem() cần.
const COT_CHUNG_CHI = 'SELECT c.*, a.name AS ca_name, a.is_trusted AS ca_tin_cay, d.domain_name, s.hostname AS server_name FROM ';

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
    $cau = $pdo->prepare('SELECT c.*, a.name AS ca_name, a.is_trusted AS ca_tin_cay, d.domain_name, u.full_name AS created_by_name
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

// Trần trục số của năm biểu đồ Thống kê, tính trên TOÀN BỘ chứng chỉ, không theo bộ lọc: lọc thì cột ngắn lại trên
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
        'hang' => $lonNhat(demTheoHang($pdo)),
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

// ---------- Chấm hạng an toàn (chức năng mở rộng 3, user duyệt 09/11/2026) ----------
// Hạng = mức nặng nhất trong các phát hiện: F nguy hiểm, C cần xử lý, B lưu ý; không vướng gì là A. Hạng chữ thay cho
// điểm 0–100 vì cộng trừ điểm không biện minh được ("vì sao lỗi này trừ 10?"). Mỗi tiêu chí lấy từ quy định có thật và
// ghi nguồn cạnh phát hiện, để người xem tự kiểm. Tính lúc xem từ các cột đã có, không lưu: không bao giờ cũ, không đổi
// schema. Cố ý KHÔNG xét hết hạn, sắp hết hạn, thu hồi: đã có trạng thái riêng, xét nữa là tính một lỗi hai lần.
const HANG_AN_TOAN = [
    'A' => ['nhan' => 'A · Tốt', 'mau' => 'green'],
    'B' => ['nhan' => 'B · Lưu ý', 'mau' => 'blue'],
    'C' => ['nhan' => 'C · Cần xử lý', 'mau' => 'yellow'],
    'F' => ['nhan' => 'F · Nguy hiểm', 'mau' => 'red'],
];
// Thứ tự nặng nhẹ khi lấy mức nặng nhất; 'i' là ghi chú, không hạ hạng.
const MUC_NANG = ['F' => 4, 'C' => 3, 'B' => 2, 'i' => 1];

// Thời hạn tối đa (ngày) của chứng chỉ TLS công khai theo ngày cấp, Baseline Requirements của CA/Browser Forum:
// 825 ngày từ 01/03/2018, 398 từ 01/09/2020, rồi lộ trình ballot SC-081v3 (thông qua 11/04/2025): 200 từ 15/03/2026,
// 100 từ 15/03/2027, 47 từ 15/03/2029. Cấp trước 01/03/2018 thì không xét: luật khi đó tính theo tháng, và các chứng chỉ
// đó nay đều đã hết hạn. Mốc mới nhất đứng đầu.
const THOI_HAN_TOI_DA = ['2029-03-15' => 47, '2027-03-15' => 100, '2026-03-15' => 200, '2020-09-01' => 398, '2018-03-01' => 825];

// Số ngày tối đa được phép với chứng chỉ cấp ngày $ngayCap ('Y-m-d H:i:s'); null nếu cấp trước mọi mốc.
function thoiHanToiDa(string $ngayCap): ?int
{
    foreach (THOI_HAN_TOI_DA as $tuNgay => $soNgay) {
        if ($ngayCap >= $tuNgay) {
            return $soNgay;
        }
    }
    return null;
}

// true nếu tên trong chứng chỉ ($ten, có thể là *.a.b) liên quan tên miền quản lý: trùng, là tên miền con của nó
// (tên miền quản lý có thể là tên miền cha, chonTenMienChoDiaChi), hoặc wildcard phủ đúng một tầng (*.a.b phủ x.a.b).
function tenLienQuanTenMien(string $ten, string $tenMien): bool
{
    $ten = strtolower(trim($ten));
    $tenMien = strtolower(preg_replace('/^\*\./', '', trim($tenMien)));
    $goc = preg_replace('/^\*\./', '', $ten);
    if ($goc === $tenMien || str_ends_with($goc, '.' . $tenMien)) {
        return true;
    }
    return str_starts_with($ten, '*.') && str_ends_with($tenMien, '.' . $goc)
        && !str_contains(substr($tenMien, 0, -strlen($goc) - 1), '.');
}

// Chấm một chứng chỉ. $cc là một dòng của timChungChi()/danh sách: cần key_algorithm, key_size, signature_algorithm,
// valid_from, valid_to, subject_cn, san, domain_name, ca_tin_cay. Trả về ['hang' => 'A'|'B'|'C'|'F',
// 'phat_hien' => [['muc' => 'F'|'C'|'B'|'i', 'noi_dung' => ..., 'nguon' => ...], ...]] xếp nặng trước.
function chamDiem(array $cc): array
{
    $ds = [];
    $them = function (string $muc, string $noiDung, string $nguon) use (&$ds): void {
        $ds[] = ['muc' => $muc, 'noi_dung' => $noiDung, 'nguon' => $nguon];
    };
    $ky = (string) $cc['signature_algorithm'];
    $thuatToan = (string) $cc['key_algorithm'];
    $doDai = (int) $cc['key_size'];

    // Hàm băm của chữ ký: SHA-1, MD5 đã có cách tạo va chạm, kẻ xấu làm giả được chứng chỉ cùng chữ ký.
    if (preg_match('/sha-?1(?!\d)|md5|md2/i', $ky) === 1) {
        $them('F', "Chữ ký dùng hàm băm yếu ($ky): đã tạo được va chạm, chứng chỉ có thể bị làm giả. Trình duyệt từ chối.",
            'CA/Browser Forum Baseline Requirements: cấm SHA-1 từ 01/01/2016');
    }
    if ($thuatToan === 'RSA' && $doDai < 2048) {
        $them('F', "Khoá RSA $doDai bit: dưới mức tối thiểu 2048 bit, phá được bằng máy tính đủ mạnh.",
            'CA/Browser Forum Baseline Requirements (RSA tối thiểu 2048 bit); NIST SP 800-131A');
    } elseif ($thuatToan === 'RSA' && $doDai < 3072) {
        $them('B', "Khoá RSA $doDai bit: đủ an toàn tới hết 2030, sau đó cần từ 3072 bit (hoặc ECDSA P-256).",
            'NIST SP 800-57 phần 1 và SP 800-131A');
    } elseif ($thuatToan === 'ECDSA' && $doDai < 256) {
        $them('F', "Khoá ECDSA $doDai bit: dưới đường cong P-256 tối thiểu.", 'CA/Browser Forum Baseline Requirements (P-256, P-384, P-521)');
    }
    // GOST: chuẩn mật mã của Nga, trình duyệt phổ thông không hỗ trợ; không phải yếu, nhưng chỉ dùng được ở Nga.
    if ($thuatToan === 'GOST' || preg_match('/gost|1\.2\.643/i', $ky) === 1) {
        $them('B', 'Dùng thuật toán GOST (chuẩn mật mã Nga): chỉ trình duyệt hỗ trợ GOST (Yandex Browser...) mở được, Chrome, Firefox thì không.',
            'GOST R 34.10-2012, GOST R 34.11-2012; danh sách thuật toán được phép trong Baseline Requirements');
    }
    if (!(int) $cc['ca_tin_cay']) {
        $them('C', 'Tổ chức cấp không được tin cậy (tự ký, gốc lạ, hoặc thiếu chứng chỉ trung gian lúc đọc): trình duyệt sẽ báo lỗi.',
            'Kho CA gốc của trình duyệt (Mozilla Root Store, Chrome Root Program)');
    }
    $cacTen = array_values(array_filter(array_map('trim', [$cc['subject_cn'], ...explode(',', (string) $cc['san'])])));
    if (!array_filter($cacTen, fn(string $ten): bool => tenLienQuanTenMien($ten, (string) $cc['domain_name']))) {
        $them('C', "Không tên nào trong chứng chỉ thuộc tên miền quản lý {$cc['domain_name']}: gắn nhầm tên miền, hoặc máy chủ đang dùng chứng chỉ của tên khác.",
            'CA/Browser Forum Baseline Requirements: tên miền phải nằm trong subjectAltName');
    }
    $toiDa = thoiHanToiDa((string) $cc['valid_from']);
    $soNgay = (int) floor((strtotime($cc['valid_to']) - strtotime($cc['valid_from'])) / 86400);
    if ($toiDa !== null && strtotime($cc['valid_to']) - strtotime($cc['valid_from']) > $toiDa * 86400) {
        $them('C', "Thời hạn $soNgay ngày, dài hơn mức tối đa $toiDa ngày cho chứng chỉ cấp ngày " . date('d/m/Y', strtotime($cc['valid_from']))
            . ': tổ chức cấp không theo luật chung, hoặc nhập sai ngày.', 'CA/Browser Forum Baseline Requirements; ballot SC-081v3 (lộ trình 200, 100, 47 ngày)');
    }
    if (array_filter($cacTen, fn(string $ten): bool => str_starts_with($ten, '*.'))) {
        $them('i', 'Chứng chỉ wildcard (*.): lộ khoá thì mọi tên miền con cùng bị ảnh hưởng. Chỉ ghi chú, không hạ hạng.',
            'Khuyến nghị chung; Baseline Requirements cho phép wildcard');
    }

    usort($ds, fn(array $a, array $b): int => MUC_NANG[$b['muc']] <=> MUC_NANG[$a['muc']]);
    $nangNhat = $ds !== [] ? $ds[0]['muc'] : 'i';
    return ['hang' => $nangNhat === 'i' ? 'A' : $nangNhat, 'phat_hien' => $ds];
}

// Số chứng chỉ theo hạng, đủ 4 hạng theo thứ tự A, B, C, F (hạng không có là 0), cùng bộ lọc với danh sách.
// Hạng tính bằng PHP (chamDiem) nên phải lấy cả danh sách; vài trăm chứng chỉ vẫn nhanh.
function demTheoHang(PDO $pdo, array $loc = []): array
{
    $so = array_fill_keys(array_keys(HANG_AN_TOAN), 0);
    foreach (tatCaChungChi($pdo, $loc) as $cc) {
        $so[chamDiem($cc)['hang']]++;
    }
    return $so;
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

// Kết nối và lấy chứng chỉ máy chủ đang dùng. Trả về ['chung_chi' => OpenSSLCertificate, 'chuoi' => [lá, bên cấp...],
// 'host', 'cong', 'cac_ip' => mọi IP vừa tra]
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
        'capture_peer_cert_chain' => true, // Cả chứng chỉ bên cấp, để kiểm hãng có đáng tin không (laChuoiTinCay).
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
    $thamSo = stream_context_get_params($ketNoi)['options']['ssl'];
    fclose($ketNoi);
    $chungChi = $thamSo['peer_certificate'] ?? null;
    if (!$chungChi instanceof OpenSSLCertificate) {
        return ['loi' => "Máy chủ $host:$cong không gửi chứng chỉ."];
    }
    return ['chung_chi' => $chungChi, 'chuoi' => $thamSo['peer_certificate_chain'] ?? [$chungChi],
        'host' => $host, 'cong' => $cong, 'cac_ip' => $cacIp];
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

// Tổ chức cấp trùng tên (không phân biệt hoa thường) với một trong các tên đưa vào; không trùng thì null.
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

// ---------- Đọc xong thì tự lo danh mục (user chốt 09/11/2026, thay cho "không tự tạo" của 08/11) ----------
// Đọc chỉ ghi lại "nguồn" (những gì vừa đọc được) rồi tính "kế hoạch": cái gì đã có trong danh mục thì dùng,
// cái gì chưa có thì SẼ tạo. Chỉ khi bấm Lưu mới tạo, trong một giao dịch: đọc thử rồi bỏ thì danh mục sạch.

// Tổ chức cấp ghi ở tầng HÃNG, không theo từng chứng chỉ trung gian (YE1, R10, WE2...), để Thống kê theo tổ chức
// cấp gom đúng (user chốt 09/11/2026). Một hãng có nhiều cách viết ô O: gộp về một tên gọn; hãng không có ở đây
// giữ nguyên ô O. Mẫu khớp từ đầu một từ, không phân biệt hoa thường. GeoTrust, RapidSSL, Thawte là thương hiệu
// của DigiCert; COMODO, USERTrust là tên cũ và gốc của Sectigo; Starfield của GoDaddy (đo 72 site thật 09/11/2026).
// Tên gọn dài quá 24 ký tự bị rút gọn trên trục biểu đồ (app.js), nên giữ ngắn.
const HANG_CHUNG_CHI = [
    "Let's Encrypt" => ["let's encrypt", 'internet security research group'],
    'Google Trust Services' => ['google trust services'],
    'DigiCert' => ['digicert', 'geotrust', 'rapidssl', 'thawte', 'symantec'],
    'GlobalSign' => ['globalsign'],
    'Sectigo' => ['sectigo', 'comodo', 'usertrust'],
    'SSL.com' => ['ssl corp', 'ssl.com'],
    'Entrust' => ['entrust'],
    'GoDaddy' => ['godaddy', 'starfield'],
    'Amazon' => ['amazon'],
    'Microsoft' => ['microsoft'],
    'Apple' => ['apple'],
    'Cloudflare' => ['cloudflare'],
    'ZeroSSL' => ['zerossl'],
    'Certum' => ['certum', 'unizeto', 'asseco'],
    'Buypass' => ['buypass'],
    // CA nhà nước Nga (sberbank.ru, gosuslugi một phần): ô O là tên bộ, dài và không ai nhận ra.
    'Russian Trusted CA' => ['the ministry of digital development', 'russian trusted'],
];

// Mã quốc gia trong chứng chỉ (ô C) ra tên tiếng Việt, cùng cách ghi với danh mục ("Hoa Kỳ"). Mã lạ giữ nguyên.
const TEN_QUOC_GIA = [
    'US' => 'Hoa Kỳ', 'BE' => 'Bỉ', 'GB' => 'Anh', 'DE' => 'Đức', 'FR' => 'Pháp', 'NL' => 'Hà Lan', 'PL' => 'Ba Lan',
    'NO' => 'Na Uy', 'ES' => 'Tây Ban Nha', 'IT' => 'Ý', 'CH' => 'Thuỵ Sĩ', 'AT' => 'Áo', 'SE' => 'Thuỵ Điển',
    'RU' => 'Nga', 'CN' => 'Trung Quốc', 'JP' => 'Nhật Bản', 'KR' => 'Hàn Quốc', 'TW' => 'Đài Loan', 'HK' => 'Hồng Kông',
    'SG' => 'Singapore', 'IN' => 'Ấn Độ', 'VN' => 'Việt Nam', 'CA' => 'Canada', 'AU' => 'Úc', 'TR' => 'Thổ Nhĩ Kỳ',
    'BM' => 'Bermuda',
];

// Tên hãng gọn từ ô O, rồi ô CN, của bên cấp. Không khớp hãng nào thì lấy nguyên ô O, không có O thì CN.
function tenHang(string $o, string $cn = ''): string
{
    foreach ([$o, $cn] as $ten) {
        foreach (HANG_CHUNG_CHI as $hang => $cacMau) {
            foreach ($cacMau as $mau) {
                if (preg_match('/(^|\W)' . preg_quote($mau, '/') . '/iu', $ten) === 1) {
                    return $hang;
                }
            }
        }
    }
    $ten = trim($o) !== '' ? trim($o) : trim($cn);
    return $ten !== '' ? mb_substr($ten, 0, 100) : 'Không rõ';
}

function tenQuocGia(string $ma): string
{
    $ma = strtoupper(trim($ma));
    return TEN_QUOC_GIA[$ma] ?? ($ma !== '' ? $ma : 'Không rõ');
}

// Hãng đáng tin khi có ít nhất một chứng chỉ BÊN CẤP trong chuỗi (bỏ chứng chỉ lá, phần tử 0) kiểm được tới một
// CA gốc trong kho của máy: kho của hệ điều hành trên VPS, hoặc openssl.cafile (XAMPP có sẵn bộ của curl).
// Không kiểm chứng chỉ lá: lá hết hạn (expired.badssl.com) vẫn do hãng đáng tin cấp. Tự ký, gốc lạ, CA của Bộ
// Phát triển số Nga không có trong kho nên ra không tin cậy (đo 11 site trên VPS và XAMPP, 09/11/2026).
// Site chỉ gửi chứng chỉ lá thì ra không tin cậy dù hãng đáng tin: hiếm, sửa tay được ở trang Tổ chức cấp.
function laChuoiTinCay(array $chuoi): bool
{
    $kho = (string) ini_get('openssl.cafile');
    foreach (array_slice(array_values($chuoi), 1) as $chungChi) {
        if (openssl_x509_checkpurpose($chungChi, X509_PURPOSE_ANY, $kho !== '' ? [$kho] : []) === true) {
            return true;
        }
    }
    return false;
}

// Những gì vừa đọc được, đủ để tính kế hoạch danh mục mà không phải kết nối lại. Giữ trong phiên từ lúc Đọc tới
// lúc Lưu, không nhận lại từ trình duyệt: không ai sửa được "IP vừa đọc" hay "hãng đáng tin" bằng cách gửi form giả.
function nguonTuKetQuaDoc(array $ketQua, array $doc): array
{
    $p = openssl_x509_parse($ketQua['chung_chi']);
    // Một ô có thể lặp (hai O): lấy giá trị đầu.
    $o = fn(array $phan, string $khoa): string => is_array($phan[$khoa] ?? null) ? (string) reset($phan[$khoa]) : (string) ($phan[$khoa] ?? '');
    $chu = trim($o($p['subject'], 'O'));
    return [
        'host' => $ketQua['host'],
        'cong' => $ketQua['cong'],
        'cac_ip' => $ketQua['cac_ip'],
        'cn' => strtolower($doc['subject_cn']),
        'ben_cap' => $doc['ben_cap'],
        'hang' => tenHang($o($p['issuer'], 'O'), $o($p['issuer'], 'CN')),
        'quoc_gia' => tenQuocGia($o($p['issuer'], 'C')),
        'tin_cay' => laChuoiTinCay($ketQua['chuoi']),
        'chu' => $chu !== '' ? mb_substr($chu, 0, 100) : 'Bên ngoài',
    ];
}

// Kế hoạch danh mục cho một lần đọc, tính theo CSDL lúc gọi (nhập hàng loạt tính lại từng dòng lúc Lưu, nên dòng sau
// dùng lại cái dòng trước vừa tạo). Tên miền tra trước: đã có (trùng hẳn hoặc tên miền cha) thì dùng luôn cùng máy
// chủ của nó. Chứng chỉ gắn với tên miền, không gắn với IP: site sau CDN đổi IP theo phút và theo nơi hỏi (đo
// 09/11/2026), nên IP khác chỉ ghi vào 'ip_khac' để người dùng tự quyết, không tạo gì. Chỉ khi tên miền chưa có
// mới tìm máy chủ theo IP: trùng thì dùng chung, không thì tạo (mỗi IP một máy chủ, user chốt 09/11/2026).
// Trả về: 'domain_id', 'server_id', 'ca_id' là mã có sẵn hoặc null; 'ten_mien_moi', 'may_chu_moi', 'hang_moi' là
// cái sẽ tạo hoặc null; 'ip_khac' => ['server_id', 'hostname', 'ip_cu', 'ip_moi'] hoặc null.
function keHoachDanhMuc(PDO $pdo, array $nguon): array
{
    $kh = ['domain_id' => null, 'server_id' => null, 'ca_id' => null,
        'ten_mien_moi' => null, 'may_chu_moi' => null, 'hang_moi' => null, 'ip_khac' => null];
    $ipDau = $nguon['cac_ip'][0] ?? '';
    // Đọc bằng IP thì không có tên miền: lấy tên chính của chứng chỉ.
    $ten = filter_var($nguon['host'], FILTER_VALIDATE_IP) === false ? $nguon['host'] : $nguon['cn'];

    $kh['domain_id'] = chonTenMienChoDiaChi(array_column(danhSachTenMien($pdo), 'domain_name', 'id'), $ten);
    if ($kh['domain_id'] !== null) {
        $mayChu = timMayChu($pdo, (int) timTenMien($pdo, $kh['domain_id'])['server_id']);
        $kh['server_id'] = (int) $mayChu['id'];
        if ($ipDau !== '' && !in_array($mayChu['ip_address'], $nguon['cac_ip'], true)) {
            $kh['ip_khac'] = ['server_id' => (int) $mayChu['id'], 'hostname' => $mayChu['hostname'],
                'ip_cu' => $mayChu['ip_address'], 'ip_moi' => $ipDau];
        }
    } elseif (tenMienHopLe($ten)) {
        $kh['ten_mien_moi'] = $ten;
        $mayChu = mayChuTheoIp($pdo, $nguon['cac_ip']);
        if ($mayChu !== null) {
            $kh['server_id'] = (int) $mayChu['id'];
        } else {
            $kh['may_chu_moi'] = ['hostname' => preg_replace('/^\*\./', '', $ten), 'ip_address' => $ipDau,
                'port' => (string) $nguon['cong'], 'environment' => 'production', 'owner' => $nguon['chu']];
        }
    }

    // Tra cả tên hãng gọn lẫn tên gốc: danh mục nhập tay trước đây có thể ghi "DigiCert Inc" hay "YE1".
    $kh['ca_id'] = chonToChucCap(array_column(danhSachToChuc($pdo), 'name', 'id'), [$nguon['hang'], ...$nguon['ben_cap']]);
    if ($kh['ca_id'] === null) {
        $kh['hang_moi'] = ['name' => $nguon['hang'], 'country' => $nguon['quoc_gia'], 'is_trusted' => $nguon['tin_cay']];
    }
    return $kh;
}

// Các dòng "sẽ tạo mới" để người dùng đọc trước khi bấm Lưu (form một site và bảng xem trước hàng loạt).
function moTaSeTao(array $keHoach): array
{
    $ds = [];
    if ($keHoach['ten_mien_moi'] !== null) {
        $mc = $keHoach['may_chu_moi'];
        $ds[] = 'Tên miền ' . $keHoach['ten_mien_moi'] . ($mc === null ? ', trên máy chủ có sẵn cùng IP' : '');
        if ($mc !== null) {
            $ds[] = "Máy chủ {$mc['hostname']} ({$mc['ip_address']}, cổng {$mc['port']}, phụ trách: {$mc['owner']})";
        }
    }
    if ($keHoach['hang_moi'] !== null) {
        $h = $keHoach['hang_moi'];
        $ds[] = "Tổ chức cấp {$h['name']} ({$h['country']}, " . ($h['is_trusted'] ? 'tin cậy' : 'không tin cậy') . ')';
    }
    return $ds;
}

// Form một site: người dùng có thể đổi ô Tên miền, Tổ chức cấp sau khi đọc. Chỉ giữ việc tạo mới khi ô còn đúng tên
// kế hoạch định tạo; đổi sang tên có sẵn thì dùng tên đó, đổi sang tên lạ khác thì không tạo (luuChungChi báo lỗi).
// Mã có sẵn trong kế hoạch bị bỏ: ô người dùng gõ quyết định, không để kết quả đọc lặng lẽ thay chữ họ gõ.
function keHoachTheoONhap(array $keHoach, string $tenMienGo, string $toChucGo): array
{
    $keHoach['domain_id'] = null;
    $keHoach['ca_id'] = null;
    if ($keHoach['ten_mien_moi'] === null || strtolower(trim($tenMienGo)) !== $keHoach['ten_mien_moi']) {
        $keHoach['ten_mien_moi'] = null;
        $keHoach['may_chu_moi'] = null;
    }
    if ($keHoach['hang_moi'] === null || mb_strtolower(trim($toChucGo)) !== mb_strtolower($keHoach['hang_moi']['name'])) {
        $keHoach['hang_moi'] = null;
    }
    return $keHoach;
}

// Lưu chứng chỉ cùng những gì kế hoạch nói sẽ tạo, trong MỘT giao dịch: hỏng ở bước nào thì không bước nào được
// ghi, kể cả dòng nhật ký. Dùng lại các hàm lưu của từng danh mục, nên kiểm tra và nhật ký y như khi thêm tay.
// $d là các ô chứng chỉ. Ô domain_id, ca_id còn trống thì lấy mã có sẵn trong kế hoạch, hoặc mã bản vừa tạo.
// Trả về mảng lỗi như luuChungChi().
function luuChungChiKemDanhMuc(PDO $pdo, array $keHoach, array $d, int $idNguoiTao): array
{
    $d['domain_id'] = ($d['domain_id'] ?? '') !== '' ? $d['domain_id'] : (string) ($keHoach['domain_id'] ?? '');
    $d['ca_id'] = ($d['ca_id'] ?? '') !== '' ? $d['ca_id'] : (string) ($keHoach['ca_id'] ?? '');
    // Hàm lưu ghi nhật ký ngay sau INSERT, nên lastInsertId() lúc đó là của nhật ký: tra mã theo tên vừa lưu.
    $pdo->beginTransaction();
    try {
        if ($keHoach['ten_mien_moi'] !== null) {
            if ($keHoach['may_chu_moi'] !== null) {
                $loi = luuMayChu($pdo, null, $keHoach['may_chu_moi']);
                if ($loi !== []) {
                    $pdo->rollBack();
                    return ['ten_mien' => "Không tạo được máy chủ {$keHoach['may_chu_moi']['hostname']}: " . implode(' ', $loi)];
                }
                $cau = $pdo->prepare('SELECT MAX(id) FROM servers WHERE hostname = ? AND ip_address = ?');
                $cau->execute([$keHoach['may_chu_moi']['hostname'], $keHoach['may_chu_moi']['ip_address']]);
                $keHoach['server_id'] = (int) $cau->fetchColumn();
            }
            $loi = luuTenMien($pdo, null, ['domain_name' => $keHoach['ten_mien_moi'], 'server_id' => (string) $keHoach['server_id']]);
            if ($loi !== []) {
                $pdo->rollBack();
                return ['ten_mien' => "Không tạo được tên miền {$keHoach['ten_mien_moi']}: " . implode(' ', $loi)];
            }
            $cau = $pdo->prepare('SELECT id FROM domains WHERE domain_name = ?');
            $cau->execute([$keHoach['ten_mien_moi']]);
            $d['domain_id'] = (string) $cau->fetchColumn();
        }
        if ($keHoach['hang_moi'] !== null) {
            $loi = luuToChuc($pdo, null, $keHoach['hang_moi']);
            if ($loi !== []) {
                $pdo->rollBack();
                return ['to_chuc_cap' => "Không tạo được tổ chức cấp {$keHoach['hang_moi']['name']}: " . implode(' ', $loi)];
            }
            $cau = $pdo->prepare('SELECT id FROM certificate_authorities WHERE name = ?');
            $cau->execute([$keHoach['hang_moi']['name']]);
            $d['ca_id'] = (string) $cau->fetchColumn();
        }
        $loi = luuChungChi($pdo, null, $d, $idNguoiTao);
        if ($loi !== []) {
            $pdo->rollBack();
            return $loi;
        }
        $pdo->commit();
        return [];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Nút "Cập nhật IP máy chủ" khi đọc lại tên miền đã có mà IP khác: sửa đúng dòng máy chủ đó, có ghi nhật ký.
// Chỉ chạy khi người dùng bấm; không có gì tự dò IP định kỳ (user chốt 09/11/2026: không dịch vụ chạy ngầm).
function capNhatIpMayChu(PDO $pdo, int $idMayChu, string $ipMoi): ?string
{
    $mayChu = timMayChu($pdo, $idMayChu);
    if ($mayChu === null) {
        return 'Máy chủ không còn tồn tại.';
    }
    $loi = luuMayChu($pdo, $idMayChu, ['ip_address' => $ipMoi, 'port' => (string) $mayChu['port']] + $mayChu);
    return $loi === [] ? null : implode(' ', $loi);
}

// Việc của nút "Đọc chứng chỉ" (form Thêm và Nhập hàng loạt): đọc, chuyển thành giá trị các ô, tính kế hoạch danh
// mục, rồi ghi một dòng Nhật ký dù đọc được hay không (để biết ai đã cho máy chủ kết nối đi đâu).
// Trả về ['loi' => ...] hoặc ['gia_tri' => [ô => giá trị], 'host', 'cong', 'ben_cap', 'nguon', 'ke_hoach',
// 'trung' => chứng chỉ trùng fingerprint|null].
function docChungChiChoForm(PDO $pdo, string $nhap, int $congChon): array
{
    $ketQua = docChungChiTuMayChu($nhap, $congChon);
    if (isset($ketQua['loi'])) {
        ghiNhatKy($pdo, 'doc_chung_chi', null, null, mb_substr(trim($nhap), 0, 200) . ': ' . $ketQua['loi']);
        return $ketQua;
    }
    $doc = giaTriTuChungChi($ketQua['chung_chi']);
    $nguon = nguonTuKetQuaDoc($ketQua, $doc);
    $keHoach = keHoachDanhMuc($pdo, $nguon);
    $giaTri = array_diff_key($doc, ['ben_cap' => true]);
    $giaTri['domain_id'] = (string) ($keHoach['domain_id'] ?? '');
    $giaTri['ca_id'] = (string) ($keHoach['ca_id'] ?? '');
    ghiNhatKy($pdo, 'doc_chung_chi', null, null, "{$ketQua['host']}:{$ketQua['cong']}: {$doc['subject_cn']}, hết hạn "
        . date('d/m/Y', strtotime($doc['valid_to'])));
    return [
        'gia_tri' => $giaTri,
        'host' => $ketQua['host'],
        'cong' => $ketQua['cong'],
        'ben_cap' => $doc['ben_cap'],
        'nguon' => $nguon,
        'ke_hoach' => $keHoach,
        'trung' => chungChiTheoFingerprint($pdo, $doc['fingerprint_sha256']),
    ];
}

// Nhập hàng loạt: tối đa 50 địa chỉ mỗi lượt, mỗi địa chỉ chờ tối đa GIAY_CHO_DOC_CHUNG_CHI giây (tệ nhất ~4 phút).
const SO_DIA_CHI_TOI_DA = 50;

// Tách ô dán thành danh sách địa chỉ: cách nhau bởi xuống dòng, dấu cách, phẩy hoặc chấm phẩy; bỏ dòng trống,
// bỏ trùng (không phân biệt hoa thường). Trả về ['dia_chi' => [...]] hoặc ['loi' => ...].
function tachDanhSachDiaChi(string $nhap): array
{
    $ds = [];
    foreach (preg_split('/[\s,;]+/u', $nhap, -1, PREG_SPLIT_NO_EMPTY) as $diaChi) {
        $ds[strtolower($diaChi)] ??= $diaChi;
    }
    if ($ds === []) {
        return ['loi' => 'Hãy dán ít nhất một địa chỉ.'];
    }
    if (count($ds) > SO_DIA_CHI_TOI_DA) {
        return ['loi' => 'Có ' . count($ds) . ' địa chỉ; mỗi lượt đọc tối đa ' . SO_DIA_CHI_TOI_DA . '. Hãy chia làm nhiều lượt.'];
    }
    return ['dia_chi' => array_values($ds)];
}

// Thanh tab của trang Thêm chứng chỉ, dùng chung cho form một site và trang Nhập hàng loạt.
// Tab là link thường: đổi tab là tải trang khác, không cần JavaScript.
function tabThemChungChi(string $dangMo): string
{
    $cacTab = [
        'tu_dong' => ['admin/chung_chi_form.php?che_do=tu_dong', 'Đọc từ máy chủ', 'Máy chủ'],
        'hang_loat' => ['admin/chung_chi_nhap.php', 'Nhập hàng loạt', 'Hàng loạt'],
        'thu_cong' => ['admin/chung_chi_form.php?che_do=thu_cong', 'Nhập thủ công', 'Thủ công'],
    ];
    // Màn hẹp hơn 576px dùng nhãn ngắn: ba nhãn đầy đủ rớt xuống hai dòng ở khổ iPhone 390px (chụp 09/11/2026).
    $html = '<div class="card-header"><ul class="nav nav-tabs card-header-tabs">';
    foreach ($cacTab as $ma => [$duongDan, $nhan, $nhanNgan]) {
        $mo = $ma === $dangMo;
        $html .= '<li class="nav-item"><a class="nav-link' . ($mo ? ' active' : '') . '" href="' . e(url($duongDan)) . '"'
            . ($mo ? ' aria-current="page"' : '') . '><span class="d-none d-sm-inline">' . e($nhan) . '</span>'
            . '<span class="d-sm-none">' . e($nhanNgan) . '</span></a></li>';
    }
    return $html . '</ul></div>';
}

// Chứng chỉ đã lưu có fingerprint này (id, mã quản lý), null nếu chưa có: báo trước khi người dùng bấm Lưu.
function chungChiTheoFingerprint(PDO $pdo, string $fingerprint): ?array
{
    $cau = $pdo->prepare('SELECT id, cert_code FROM certificates WHERE fingerprint_sha256 = ?');
    $cau->execute([chuanHoaFingerprint($fingerprint)]);
    return $cau->fetch() ?: null;
}
