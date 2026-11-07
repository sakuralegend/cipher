<?php
// Form thêm (không có ?id=) và sửa (có ?id=) chứng chỉ.
// Form sửa dùng để sửa thông tin nhập sai, không ghi lịch sử; chứng chỉ được cấp lại thì dùng Gia hạn.
// Form thêm có 2 tab (user chốt 08/11/2026): "Đọc từ máy chủ" (mở trước) kết nối tới máy chủ, lấy chứng chỉ
// đang chạy rồi điền sẵn các ô; "Nhập thủ công" là form trống. Mọi nút đều gửi về chính trang này:
// hanh_dong=doc là đọc, còn lại là lưu. Không cần JavaScript, nên form vẫn dùng được khi trình duyệt tắt nó.
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
}

// Tab đang mở. Form sửa chỉ có nhập thủ công.
$cheDo = $_POST['che_do'] ?? $_GET['che_do'] ?? 'tu_dong';
$cheDo = $id === null && $cheDo !== 'thu_cong' ? 'tu_dong' : 'thu_cong';

// Trạng thái của tab đọc: địa chỉ đã gõ, lỗi khi đọc, và tóm tắt chứng chỉ vừa đọc được.
$diaChiDoc = '';
$congDoc = 443;
$loiDoc = null;
$daDoc = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    if ($id === null && chuoiPost('hanh_dong') === 'doc') {
        $diaChiDoc = trim(chuoiPost('dia_chi'));
        $congDoc = (int) chuoiPost('cong');
        $ketQua = docChungChiChoForm($pdo, $diaChiDoc, $congDoc, $cacTenMien, $cacToChuc);
        if (isset($ketQua['loi'])) {
            $loiDoc = $ketQua['loi'];
        } else {
            $giaTri = array_merge($giaTri, $ketQua['gia_tri']);
            $diaChiDoc = $ketQua['host'];
            $congDoc = $ketQua['cong'];
            $daDoc = $ketQua + [
                // Cùng cách đếm với cột days_left của CSDL: số ngày lịch tới ngày hết hạn, âm là đã quá.
                'so_ngay' => (int) round((strtotime(date('Y-m-d', strtotime($giaTri['valid_to']))) - strtotime(date('Y-m-d'))) / 86400),
                'chua_hieu_luc' => strtotime($giaTri['valid_from']) > time(),
            ];
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
            // Ô gõ gửi tên; tra ra mã trong danh mục. Tên lạ thành rỗng, luuChungChi() sẽ từ chối.
            'ca_id' => (string) (idTheoTen($cacToChuc, $toChucGo) ?? ''),
            'domain_id' => (string) (idTheoTen($cacTenMien, $tenMienGo) ?? ''),
            'valid_from' => chuoiPost('valid_from'),
            'valid_to' => chuoiPost('valid_to'),
            'key_algorithm' => chuoiPost('key_algorithm'),
            'key_size' => trim(chuoiPost('key_size')),
            'signature_algorithm' => trim(chuoiPost('signature_algorithm')),
        ];
        $loi = luuChungChi($pdo, $id, $giaTri, (int) $nguoi['id']);
        if ($loi === []) {
            datThongBao('success', $id === null ? 'Đã thêm chứng chỉ.' : 'Đã lưu thay đổi.');
            chuyenHuong($id === null ? 'admin/chung_chi.php' : 'admin/chung_chi_xem.php?id=' . $id);
        }
        // Lỗi của luuChungChi() theo mã; ô trên form theo tên. Gõ tên chưa có thì nói rõ tên nào, không tự tạo.
        if (isset($loi['domain_id'])) {
            $loi['ten_mien'] = $tenMienGo === '' ? $loi['domain_id'] : "Chưa có tên miền \"$tenMienGo\" trong danh mục.";
        }
        if (isset($loi['ca_id'])) {
            $loi['to_chuc_cap'] = $toChucGo === '' ? $loi['ca_id'] : "Chưa có tổ chức cấp \"$toChucGo\" trong danh mục.";
        }
    }
}

// Ô datetime-local cần dạng có chữ T. Hiện cả giây (step="1"), để sửa thông tin khác
// không làm tròn mất giây của ngày thật.
$choO = fn(?string $ngay): string => str_replace(' ', 'T', (string) $ngay);

// Dòng gợi ý dưới hai ô gõ: luôn có link thêm vào danh mục; vừa đọc mà không khớp thì nói máy chủ báo gì.
$linkThemTenMien = '<a href="' . e(url('admin/ten_mien_form.php')) . '">Thêm tên miền mới</a>';
$linkThemToChuc = '<a href="' . e(url('admin/to_chuc_cap_form.php')) . '">Thêm tổ chức cấp mới</a>';
$goiYTenMien = $linkThemTenMien;
$goiYToChuc = $linkThemToChuc;
if ($daDoc !== null && $giaTri['domain_id'] === '') {
    $goiYTenMien = 'Chưa có tên miền nào khớp ' . e($diaChiDoc) . '. ' . $linkThemTenMien . ' rồi đọc lại.';
}
if ($daDoc !== null && $giaTri['ca_id'] === '' && $daDoc['ben_cap'] !== []) {
    $goiYToChuc = 'Máy chủ báo tổ chức cấp: <strong>' . e($daDoc['ben_cap'][0]) . '</strong>, chưa có trong danh mục. '
        . $linkThemToChuc . ' rồi đọc lại.';
}

// Các ô dựng sẵn, xếp khác nhau ở hai tab.
$o = [
    'ma' => oNhap('cert_code', 'Mã quản lý', $giaTri['cert_code'], $loi,
        'required maxlength="30" pattern="[A-Za-z0-9._\-]+" autocomplete="off"',
        'Gồm chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.'),
    'ten_chinh' => oNhap('subject_cn', 'Tên chính (CN)', $giaTri['subject_cn'], $loi,
        'required maxlength="255" autocomplete="off"', 'Tên chính là bắt buộc.'),
    'ten_mien' => oGoiY('ten_mien', 'Tên miền', $cacTenMien, $tenMienGo ?? ($cacTenMien[$giaTri['domain_id']] ?? ''),
        $loi, 'Hãy chọn một tên miền có trong danh sách.', $goiYTenMien),
    'to_chuc' => oGoiY('to_chuc_cap', 'Tổ chức cấp', $cacToChuc, $toChucGo ?? ($cacToChuc[$giaTri['ca_id']] ?? ''),
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
$trangHienTai = 'chung_chi';
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <?php if ($cacToChuc === [] || $cacTenMien === []): ?>
            <div class="alert alert-warning" role="alert">
                Cần có ít nhất một <a href="<?= e(url('admin/to_chuc_cap_form.php')) ?>">tổ chức cấp</a>
                và một <a href="<?= e(url('admin/ten_mien_form.php')) ?>">tên miền</a> trước khi thêm chứng chỉ.
            </div>
        <?php endif; ?>
        <div class="card">
            <?php if ($id === null): ?>
                <?php // Tab là link thường: đổi tab là tải lại trang ở chế độ kia, không cần JavaScript. ?>
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link<?= $cheDo === 'tu_dong' ? ' active' : '' ?>" href="<?= e(url('admin/chung_chi_form.php?che_do=tu_dong')) ?>"<?= $cheDo === 'tu_dong' ? ' aria-current="page"' : '' ?>>Đọc từ máy chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?= $cheDo === 'thu_cong' ? ' active' : '' ?>" href="<?= e(url('admin/chung_chi_form.php?che_do=thu_cong')) ?>"<?= $cheDo === 'thu_cong' ? ' aria-current="page"' : '' ?>>Nhập thủ công</a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($cheDo === 'tu_dong'): ?>
                <form class="card-body border-bottom" method="post" action="<?= e(url($hanhDong)) ?>">
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <input type="hidden" name="che_do" value="tu_dong">
                    <input type="hidden" name="hanh_dong" value="doc">
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
                            <button type="submit" class="btn btn-primary">Đọc chứng chỉ</button>
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
                                <?php elseif ($daDoc['chua_hieu_luc']): ?>
                                    <span class="badge bg-blue-lt">Chưa hiệu lực</span>
                                <?php else: ?>
                                    <span class="badge <?= $daDoc['so_ngay'] <= 30 ? 'bg-yellow-lt' : 'bg-green-lt' ?>">Còn <?= $daDoc['so_ngay'] ?> ngày</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-secondary small mt-1">
                                <?= e($diaChiDoc . ':' . $congDoc) ?>
                                · <?= e($daDoc['ben_cap'][0] ?? 'Không rõ tổ chức cấp') ?>
                                <?php if ($giaTri['key_algorithm'] !== ''): ?>· <?= e($giaTri['key_algorithm'] . ' ' . $giaTri['key_size']) ?><?php endif; ?>
                                · hết hạn <?= e(date('d/m/Y', strtotime($giaTri['valid_to']))) ?>
                            </div>
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
