<?php
// Form thêm (không có ?id=) và sửa (có ?id=) chứng chỉ.
// Form sửa dùng để sửa thông tin nhập sai, không ghi lịch sử; chứng chỉ được cấp lại thì dùng Gia hạn.
// Form thêm có 3 tab: "Đọc từ máy chủ" (mở trước) kết nối tới máy chủ, lấy chứng chỉ đang chạy rồi điền sẵn các ô,
// bấm Thêm thì tạo luôn tên miền, máy chủ, tổ chức cấp còn thiếu (user chốt 09/11/2026); "Nhập hàng loạt" là trang
// chung_chi_nhap.php; "Nhập thủ công" là form trống. Mọi nút đều gửi về chính trang này: hanh_dong=doc là đọc,
// cap_nhat_ip là sửa IP máy chủ, còn lại là lưu. Không cần JavaScript, nên form vẫn dùng được khi trình duyệt tắt nó.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$cacToChuc = array_column(danhSachToChuc($pdo), 'name', 'id');
$cacTenMien = array_column(danhSachTenMien($pdo), 'domain_name', 'id');

$loi = [];
$giaTri = [
    'cert_code' => maChungChiKeTiep($pdo),
    'fingerprint_sha256' => '', 'serial' => '', 'subject_cn' => '', 'san' => '',
    'ca_id' => '', 'domain_id' => '', 'valid_from' => '', 'valid_to' => '',
    'key_algorithm' => '', 'key_size' => '', 'signature_algorithm' => '',
];
// Tên đang hiện trong hai ô gõ có gợi ý; null thì lấy theo mã đã chọn.
$tenMienGo = null;
$toChucGo = null;

if ($id !== null) {
    $giaTri = timChungChi($pdo, $id);
    if ($giaTri === null) {
        datThongBao('danger', 'Chứng chỉ không còn tồn tại.');
        chuyenHuong('admin/chung_chi.php');
    }
    // Đã thu hồi thì không sửa được (luuChungChi cũng chặn): báo ngay thay vì để người dùng điền xong mới bị từ chối.
    if ($giaTri['is_revoked']) {
        datThongBao('warning', 'Chứng chỉ đã thu hồi chỉ còn để tra cứu, không sửa được. Theo dõi chứng chỉ mới thì thêm mới.');
        chuyenHuong('admin/chung_chi_xem.php?id=' . $id);
    }
}

// Tab đang mở. Form sửa chỉ có nhập thủ công.
$cheDo = $_POST['che_do'] ?? $_GET['che_do'] ?? 'tu_dong';
$cheDo = $id === null && $cheDo !== 'thu_cong' ? 'tu_dong' : 'thu_cong';

// Trạng thái của tab đọc: địa chỉ đã gõ, lỗi khi đọc, và tóm tắt chứng chỉ vừa đọc được.
$diaChiDoc = '';
$congDoc = 443;
$loiDoc = null;
$daDoc = null;

// Lần đọc gần nhất nằm trong phiên (khoá doc_chung_chi): nút Thêm và nút Cập nhật IP dùng nó, không nhận lại
// IP hay hãng từ trình duyệt. Mở form mới (GET) là bắt đầu lại từ đầu.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['doc_chung_chi']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $hanhDongGui = chuoiPost('hanh_dong');
    if ($id === null && $hanhDongGui === 'doc') {
        unset($_SESSION['doc_chung_chi']);
        $diaChiDoc = trim(chuoiPost('dia_chi'));
        $congDoc = (int) chuoiPost('cong');
        $ketQua = docChungChiChoForm($pdo, $diaChiDoc, $congDoc);
        if (isset($ketQua['loi'])) {
            $loiDoc = $ketQua['loi'];
        } else {
            $_SESSION['doc_chung_chi'] = $ketQua;
            $daDoc = $ketQua;
        }
    } elseif ($id === null && $hanhDongGui === 'cap_nhat_ip' && isset($_SESSION['doc_chung_chi'])) {
        $daDoc = $_SESSION['doc_chung_chi'];
        $ipKhac = keHoachDanhMuc($pdo, $daDoc['nguon'])['ip_khac'];
        if ($ipKhac !== null) {
            $loiDoc = capNhatIpMayChu($pdo, $ipKhac['server_id'], $ipKhac['ip_moi']);
            if ($loiDoc === null) {
                datThongBao('success', "Đã đổi IP máy chủ {$ipKhac['hostname']} từ {$ipKhac['ip_cu']} sang {$ipKhac['ip_moi']}.");
            }
        }
    } else {
        $tenMienGo = trim(chuoiPost('ten_mien'));
        $toChucGo = trim(chuoiPost('to_chuc_cap'));
        $giaTri = [
            'cert_code' => trim(chuoiPost('cert_code')),
            'fingerprint_sha256' => trim(chuoiPost('fingerprint_sha256')),
            'serial' => trim(chuoiPost('serial')),
            'subject_cn' => trim(chuoiPost('subject_cn')),
            'san' => trim(chuoiPost('san')),
            // Ô gõ gửi tên; tra ra mã trong danh mục. Tên lạ thành rỗng: luuChungChi() từ chối, trừ khi đó đúng
            // là tên lần đọc vừa rồi định tạo (keHoachTheoONhap).
            'ca_id' => (string) (idTheoTen($cacToChuc, $toChucGo) ?? ''),
            'domain_id' => (string) (idTheoTen($cacTenMien, $tenMienGo) ?? ''),
            'valid_from' => chuoiPost('valid_from'),
            'valid_to' => chuoiPost('valid_to'),
            'key_algorithm' => chuoiPost('key_algorithm'),
            'key_size' => trim(chuoiPost('key_size')),
            'signature_algorithm' => trim(chuoiPost('signature_algorithm')),
        ];
        if ($id === null) {
            // Tính lại kế hoạch theo CSDL lúc bấm, phòng khi danh mục vừa đổi từ lúc đọc.
            $keHoach = ['ten_mien_moi' => null, 'may_chu_moi' => null, 'hang_moi' => null, 'server_id' => null];
            if ($cheDo === 'tu_dong' && isset($_SESSION['doc_chung_chi'])) {
                $keHoach = keHoachTheoONhap(keHoachDanhMuc($pdo, $_SESSION['doc_chung_chi']['nguon']), $tenMienGo, $toChucGo);
            }
            $loi = luuChungChiKemDanhMuc($pdo, $keHoach, $giaTri, (int) $nguoi['id']);
        } else {
            $loi = luuChungChi($pdo, $id, $giaTri, (int) $nguoi['id']);
        }
        if ($loi === []) {
            unset($_SESSION['doc_chung_chi']);
            datThongBao('success', $id === null ? 'Đã thêm chứng chỉ.' : 'Đã lưu thay đổi.');
            chuyenHuong($id === null ? 'admin/chung_chi.php' : 'admin/chung_chi_xem.php?id=' . $id);
        }
        // Lỗi của luuChungChi() theo mã; ô trên form theo tên. Gõ tên chưa có thì nói rõ tên nào.
        if (isset($loi['domain_id'])) {
            $loi['ten_mien'] = $tenMienGo === '' ? $loi['domain_id'] : "Chưa có tên miền \"$tenMienGo\" trong danh mục.";
        }
        if (isset($loi['ca_id'])) {
            $loi['to_chuc_cap'] = $toChucGo === '' ? $loi['ca_id'] : "Chưa có tổ chức cấp \"$toChucGo\" trong danh mục.";
        }
    }
}

// Vừa đọc (hoặc vừa cập nhật IP): điền các ô đã đọc, kèm số ngày còn lại cho thẻ tóm tắt.
if ($daDoc !== null) {
    $giaTri = array_merge($giaTri, $daDoc['gia_tri']);
    $diaChiDoc = $daDoc['host'];
    $congDoc = $daDoc['cong'];
    $daDoc += [
        // Cùng cách đếm với cột days_left của CSDL: số ngày lịch tới ngày hết hạn, âm là đã quá.
        'so_ngay' => (int) round((strtotime(date('Y-m-d', strtotime($giaTri['valid_to']))) - strtotime(date('Y-m-d'))) / 86400),
        'chua_hieu_luc' => strtotime($giaTri['valid_from']) > time(),
    ];
}

// Kế hoạch danh mục của lần đọc trong phiên, tính theo CSDL lúc này (vừa cập nhật IP thì ip_khac tự hết).
$keHoachHien = $id === null && $cheDo === 'tu_dong' && isset($_SESSION['doc_chung_chi'])
    ? keHoachDanhMuc($pdo, $_SESSION['doc_chung_chi']['nguon']) : null;
$tenMienMoi = $keHoachHien['ten_mien_moi'] ?? null;
$hangMoi = $keHoachHien['hang_moi'] ?? null;
// Vừa đọc xong: hai ô gõ điền sẵn tên sắp tạo. Sau khi Lưu hỏng thì giữ đúng chữ người dùng đã gõ.
if ($daDoc !== null) {
    $tenMienGo = $giaTri['domain_id'] === '' ? $tenMienMoi : null;
    $toChucGo = $giaTri['ca_id'] === '' ? ($hangMoi['name'] ?? null) : null;
}

// Ô datetime-local cần dạng có chữ T. Hiện cả giây (step="1"), để sửa thông tin khác
// không làm tròn mất giây của ngày thật.
$choO = fn(?string $ngay): string => str_replace(' ', 'T', (string) $ngay);

// Dòng gợi ý dưới hai ô gõ: luôn có link thêm vào danh mục; vừa đọc thì nói cái gì sẽ được tạo khi bấm Thêm,
// hoặc (đọc bằng IP, tên chính không phải tên miền) không khớp gì để người dùng tự chọn.
$linkThemTenMien = '<a href="' . e(url('admin/ten_mien_form.php')) . '">Thêm tên miền mới</a>';
$linkThemToChuc = '<a href="' . e(url('admin/to_chuc_cap_form.php')) . '">Thêm tổ chức cấp mới</a>';
$goiYTenMien = $linkThemTenMien;
$goiYToChuc = $linkThemToChuc;
if ($tenMienMoi !== null) {
    $goiYTenMien = 'Chưa có trong danh mục: sẽ được tạo khi bấm Thêm chứng chỉ.';
} elseif ($daDoc !== null && $giaTri['domain_id'] === '') {
    $goiYTenMien = 'Chưa có tên miền nào khớp ' . e($diaChiDoc) . '. ' . $linkThemTenMien . ' rồi đọc lại.';
}
if ($hangMoi !== null) {
    $goiYToChuc = 'Chưa có trong danh mục: sẽ được tạo khi bấm Thêm chứng chỉ.';
}
// Ô gõ chỉ nhận tên có trong gợi ý (app.js), nên tên sắp tạo cũng phải nằm trong gợi ý.
$goiYDsTenMien = $cacTenMien + ($tenMienMoi !== null ? ['moi' => $tenMienMoi] : []);
$goiYDsToChuc = $cacToChuc + ($hangMoi !== null ? ['moi' => $hangMoi['name']] : []);

// Các ô dựng sẵn, xếp khác nhau ở hai tab.
$o = [
    'ma' => oNhap('cert_code', 'Mã quản lý', $giaTri['cert_code'], $loi,
        'required maxlength="30" pattern="[A-Za-z0-9._\-]+" autocomplete="off"',
        'Gồm chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.'),
    'ten_chinh' => oNhap('subject_cn', 'Tên chính (CN)', $giaTri['subject_cn'], $loi,
        'required maxlength="255" autocomplete="off"', 'Tên chính là bắt buộc.'),
    'ten_mien' => oGoiY('ten_mien', 'Tên miền', $goiYDsTenMien, $tenMienGo ?? ($cacTenMien[$giaTri['domain_id']] ?? ''),
        $loi, 'Hãy chọn một tên miền có trong danh sách.', $goiYTenMien),
    'to_chuc' => oGoiY('to_chuc_cap', 'Tổ chức cấp', $goiYDsToChuc, $toChucGo ?? ($cacToChuc[$giaTri['ca_id']] ?? ''),
        $loi, 'Hãy chọn một tổ chức cấp có trong danh sách.', $goiYToChuc),
    'bat_dau' => oNhap('valid_from', 'Ngày bắt đầu', $choO($giaTri['valid_from']), $loi,
        'required type="datetime-local" step="1"', 'Hãy nhập ngày bắt đầu.', 'Theo giờ Việt Nam.'),
    'het_han' => oNhap('valid_to', 'Ngày hết hạn', $choO($giaTri['valid_to']), $loi,
        'required type="datetime-local" step="1" data-sau="valid_from"', 'Ngày hết hạn phải sau ngày bắt đầu.', 'Theo giờ Việt Nam.'),
    'thuat_toan' => oChon('key_algorithm', 'Thuật toán khoá', THUAT_TOAN_KHOA, $giaTri['key_algorithm'], $loi, 'Hãy chọn thuật toán khoá.'),
    'do_dai' => oNhap('key_size', 'Độ dài khoá (bit)', (string) $giaTri['key_size'], $loi,
        'required type="number" min="1" max="65535"', 'Độ dài khoá phải là số dương.'),
    'ky' => oNhap('signature_algorithm', 'Thuật toán ký', $giaTri['signature_algorithm'], $loi,
        'required maxlength="50" autocomplete="off"', 'Thuật toán ký là bắt buộc.', 'Ví dụ: sha256WithRSAEncryption'),
    'fingerprint' => oNhap('fingerprint_sha256', 'Fingerprint SHA-256', $giaTri['fingerprint_sha256'], $loi,
        'maxlength="128" pattern="[0-9A-Fa-f:\s]+" autocomplete="off"',
        'Phải là ký tự hex, có thể kèm dấu hai chấm.', 'Không bắt buộc. Dán dạng EE:7A:1D… cũng được.'),
    'serial' => oNhap('serial', 'Serial', $giaTri['serial'], $loi,
        'maxlength="256" pattern="[0-9A-Fa-f:\s]+" autocomplete="off"',
        'Phải là ký tự hex, có thể kèm dấu hai chấm.', 'Không bắt buộc.'),
    'san' => '<div class="mb-3"><label class="form-label" for="san">Tên phụ (SAN)</label>'
        . '<textarea class="form-control ma-so' . (isset($loi['san']) ? ' is-invalid' : '') . '" id="san" name="san" rows="2" maxlength="5000">'
        . e($giaTri['san']) . '</textarea>'
        . '<div class="invalid-feedback">' . e($loi['san'] ?? 'Tên phụ tối đa 5000 ký tự.') . '</div>'
        . '<small class="form-hint">Không bắt buộc. Các tên miền phụ mà chứng chỉ cũng bảo vệ.</small></div>',
];
// Tab đọc: ô nào đã đọc được thì gom vào phần thu gọn; có lỗi ở một trong số đó, hoặc thuật toán khoá
// không đọc ra được (Ed25519, GOST) thì mở sẵn để người dùng thấy ô cần điền.
$moChiTiet = array_intersect_key($loi, array_flip(['subject_cn', 'valid_from', 'valid_to', 'key_algorithm', 'key_size',
    'signature_algorithm', 'fingerprint_sha256', 'serial', 'san'])) !== [] || ($daDoc !== null && $giaTri['key_algorithm'] === '');
// Tab đọc chỉ hiện form lưu khi đã đọc được, hoặc vừa lưu hỏng (để sửa tiếp).
$hienFormLuu = $cheDo === 'thu_cong' || $daDoc !== null || $loi !== [];

$hanhDong = 'admin/chung_chi_form.php' . ($id !== null ? '?id=' . $id : '');
$tieuDe = $id === null ? 'Thêm chứng chỉ' : 'Sửa chứng chỉ';
$trangHienTai = $id === null ? 'chung_chi_them' : 'chung_chi'; // Thêm mới sáng mục "Thêm chứng chỉ", sửa thì sáng "Danh sách".
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <?php if ($cheDo === 'thu_cong' && ($cacToChuc === [] || $cacTenMien === [])): ?>
            <div class="alert alert-warning" role="alert">
                Cần có ít nhất một <a href="<?= e(url('admin/to_chuc_cap_form.php')) ?>">tổ chức cấp</a>
                và một <a href="<?= e(url('admin/ten_mien_form.php')) ?>">tên miền</a> trước khi thêm chứng chỉ.
            </div>
        <?php endif; ?>
        <div class="card">
            <?php if ($id === null): ?>
                <?= tabThemChungChi($cheDo) ?>
            <?php endif; ?>

            <?php if ($cheDo === 'tu_dong'): ?>
                <form class="card-body border-bottom" method="post" action="<?= e(url($hanhDong)) ?>">
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <input type="hidden" name="che_do" value="tu_dong">
                    <label class="form-label" for="dia_chi">Địa chỉ máy chủ</label>
                    <div class="row g-2">
                        <div class="col-12 col-md">
                            <input class="form-control<?= $loiDoc !== null ? ' is-invalid' : '' ?>" id="dia_chi" name="dia_chi"
                                   value="<?= e($diaChiDoc) ?>" placeholder="Ví dụ: cert.cipher.id.vn" maxlength="255"
                                   autocomplete="off" autocapitalize="off" spellcheck="false" required>
                            <?php if ($loiDoc !== null): ?>
                                <div class="invalid-feedback"><?= e($loiDoc) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col col-md-auto">
                            <select class="form-select" name="cong" aria-label="Cổng">
                                <?php foreach (CONG_DOC_CHUNG_CHI as $cong => $nhanCong): ?>
                                    <option value="<?= $cong ?>"<?= $cong === $congDoc ? ' selected' : '' ?>><?= e($nhanCong) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary" name="hanh_dong" value="doc">Đọc chứng chỉ</button>
                        </div>
                    </div>
                    <small class="form-hint mt-2">Hệ thống kết nối tới máy chủ, lấy chứng chỉ đang chạy và điền sẵn các ô.
                        Đọc được cả chứng chỉ đã hết hạn hoặc tự ký. Chỉ đọc máy chủ có địa chỉ công khai.</small>

                    <?php if ($daDoc !== null): ?>
                        <?php // Tóm tắt chứng chỉ vừa đọc: đủ để nhận ra đúng chứng chỉ trước khi bấm Thêm. ?>
                        <div class="the-doc mt-3">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="fw-semibold"><?= tenMienNgatDong($giaTri['subject_cn']) ?></span>
                                <?php if ($daDoc['so_ngay'] < 0): ?>
                                    <span class="badge bg-red-lt">Đã hết hạn <?= abs($daDoc['so_ngay']) ?> ngày</span>
                                <?php elseif ($daDoc['so_ngay'] === 0): ?>
                                    <span class="badge bg-red-lt">Hết hạn hôm nay</span>
                                <?php elseif ($daDoc['chua_hieu_luc']): ?>
                                    <span class="badge bg-blue-lt">Chưa hiệu lực</span>
                                <?php else: ?>
                                    <span class="badge <?= $daDoc['so_ngay'] <= 30 ? 'bg-yellow-lt' : 'bg-green-lt' ?>">Còn <?= $daDoc['so_ngay'] ?> ngày</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-secondary small mt-1">
                                <?= e($diaChiDoc . ':' . $congDoc) ?>
                                <?php if ($daDoc['nguon']['cac_ip'] !== []): ?>· IP <?= e($daDoc['nguon']['cac_ip'][0]) ?><?php endif; ?>
                                · <?= e($daDoc['nguon']['hang']) ?>
                                <?php if ($giaTri['key_algorithm'] !== ''): ?>· <?= e($giaTri['key_algorithm'] . ' ' . $giaTri['key_size']) ?><?php endif; ?>
                                · hết hạn <?= e(date('d/m/Y', strtotime($giaTri['valid_to']))) ?>
                            </div>
                            <?php $seTao = $keHoachHien !== null ? moTaSeTao($keHoachHien) : []; ?>
                            <?php if ($seTao !== []): ?>
                                <div class="mt-3 small">
                                    <div class="fw-semibold">Sẽ tạo mới khi bấm Thêm chứng chỉ:</div>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($seTao as $dong): ?><li><?= e($dong) ?></li><?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if (($keHoachHien['ip_khac'] ?? null) !== null): ?>
                                <?php $ipKhac = $keHoachHien['ip_khac']; ?>
                                <?php // Chỉ ghi chú: site sau CDN đổi IP thường xuyên. Không bấm thì không có gì thay đổi. ?>
                                <div class="alert alert-info mt-3 mb-0 d-flex flex-wrap align-items-center gap-2" role="status">
                                    <span class="me-auto">IP lúc đọc <?= e($ipKhac['ip_moi']) ?> khác IP đã lưu <?= e($ipKhac['ip_cu']) ?>
                                        của máy chủ <?= e($ipKhac['hostname']) ?>. Site dùng CDN thường đổi IP, chứng chỉ không đổi theo.</span>
                                    <button type="submit" class="btn btn-sm" name="hanh_dong" value="cap_nhat_ip" formnovalidate>Cập nhật IP máy chủ</button>
                                </div>
                            <?php endif; ?>
                            <?php if ($daDoc['trung'] !== null): ?>
                                <div class="alert alert-warning mt-3 mb-0" role="alert">
                                    Chứng chỉ này đã có trong hệ thống:
                                    <a href="<?= e(url('admin/chung_chi_xem.php?id=' . (int) $daDoc['trung']['id'])) ?>"><?= e($daDoc['trung']['cert_code']) ?></a>.
                                    Thêm lần nữa sẽ bị từ chối vì trùng fingerprint.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <?php if ($hienFormLuu): ?>
                <form method="post" action="<?= e(url($hanhDong)) ?>" data-kiem-tra novalidate>
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <input type="hidden" name="che_do" value="<?= e($cheDo) ?>">
                    <div class="card-body">
                        <?php if ($cheDo === 'tu_dong'): ?>
                            <div class="row">
                                <div class="col-md-4"><?= $o['ma'] ?></div>
                                <div class="col-md-4"><?= $o['ten_mien'] ?></div>
                                <div class="col-md-4"><?= $o['to_chuc'] ?></div>
                            </div>
                            <details class="chi-tiet-doc"<?= $moChiTiet ? ' open' : '' ?>>
                                <summary>Các ô đã đọc từ máy chủ: tên chính, ngày, khoá, fingerprint, serial</summary>
                                <div class="row mt-3">
                                    <div class="col-12"><?= $o['ten_chinh'] ?></div>
                                    <div class="col-md-6"><?= $o['bat_dau'] ?></div>
                                    <div class="col-md-6"><?= $o['het_han'] ?></div>
                                    <div class="col-md-4"><?= $o['thuat_toan'] ?></div>
                                    <div class="col-md-4"><?= $o['do_dai'] ?></div>
                                    <div class="col-md-4"><?= $o['ky'] ?></div>
                                    <div class="col-12"><?= $o['fingerprint'] ?></div>
                                    <div class="col-12"><?= $o['serial'] ?></div>
                                    <div class="col-12"><?= $o['san'] ?></div>
                                </div>
                            </details>
                        <?php else: ?>
                            <div class="row">
                                <div class="col-md-6"><?= $o['ma'] ?></div>
                                <div class="col-md-6"><?= $o['ten_chinh'] ?></div>
                                <div class="col-md-6"><?= $o['ten_mien'] ?></div>
                                <div class="col-md-6"><?= $o['to_chuc'] ?></div>
                                <div class="col-md-6"><?= $o['bat_dau'] ?></div>
                                <div class="col-md-6"><?= $o['het_han'] ?></div>
                                <div class="col-md-4"><?= $o['thuat_toan'] ?></div>
                                <div class="col-md-4"><?= $o['do_dai'] ?></div>
                                <div class="col-md-4"><?= $o['ky'] ?></div>
                                <div class="col-12"><?= $o['fingerprint'] ?></div>
                                <div class="col-12"><?= $o['serial'] ?></div>
                                <div class="col-12"><?= $o['san'] ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <a class="btn" href="<?= e(url($id === null ? 'admin/chung_chi.php' : 'admin/chung_chi_xem.php?id=' . $id)) ?>">Huỷ</a>
                        <button type="submit" class="btn btn-primary"><?= $id === null ? 'Thêm chứng chỉ' : 'Lưu thay đổi' ?></button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
