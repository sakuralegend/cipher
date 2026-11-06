<?php
// Form thêm (không có ?id=) và sửa (có ?id=) chứng chỉ.
// Form sửa dùng để sửa thông tin nhập sai, không ghi lịch sử; chứng chỉ được cấp lại thì dùng Gia hạn.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$loi = [];
$giaTri = [
    'cert_code' => maChungChiKeTiep($pdo),
    'fingerprint_sha256' => '', 'serial' => '', 'subject_cn' => '', 'san' => '',
    'ca_id' => '', 'domain_id' => '', 'valid_from' => '', 'valid_to' => '',
    'key_algorithm' => '', 'key_size' => '', 'signature_algorithm' => '',
];

if ($id !== null) {
    $giaTri = timChungChi($pdo, $id);
    if ($giaTri === null) {
        datThongBao('danger', 'Chứng chỉ không còn tồn tại.');
        chuyenHuong('admin/chung_chi.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $giaTri = [
        'cert_code' => trim(chuoiPost('cert_code')),
        'fingerprint_sha256' => trim(chuoiPost('fingerprint_sha256')),
        'serial' => trim(chuoiPost('serial')),
        'subject_cn' => trim(chuoiPost('subject_cn')),
        'san' => trim(chuoiPost('san')),
        'ca_id' => chuoiPost('ca_id'),
        'domain_id' => chuoiPost('domain_id'),
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
}

// Ô datetime-local cần dạng có chữ T. Hiện cả giây (step="1"), để sửa thông tin khác
// không làm tròn mất giây của ngày thật.
$choO = fn(?string $ngay): string => str_replace(' ', 'T', (string) $ngay);

$cacToChuc = array_column(danhSachToChuc($pdo), 'name', 'id');
$cacTenMien = array_column(danhSachTenMien($pdo), 'domain_name', 'id');

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
        <form class="card" method="post" action="<?= e(url($hanhDong)) ?>" data-kiem-tra novalidate>
            <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <?= oNhap('cert_code', 'Mã quản lý', $giaTri['cert_code'], $loi,
                            'required maxlength="30" pattern="[A-Za-z0-9._\-]+" autocomplete="off"',
                            'Gồm chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.') ?>
                    </div>
                    <div class="col-md-6">
                        <?= oNhap('subject_cn', 'Tên chính (CN)', $giaTri['subject_cn'], $loi,
                            'required maxlength="255" autocomplete="off"', 'Tên chính là bắt buộc.') ?>
                    </div>
                    <div class="col-md-6">
                        <?= oChon('domain_id', 'Tên miền', $cacTenMien, (string) $giaTri['domain_id'], $loi, 'Hãy chọn tên miền.') ?>
                    </div>
                    <div class="col-md-6">
                        <?= oChon('ca_id', 'Tổ chức cấp', $cacToChuc, (string) $giaTri['ca_id'], $loi, 'Hãy chọn tổ chức cấp.') ?>
                    </div>
                    <div class="col-md-6">
                        <?= oNhap('valid_from', 'Ngày bắt đầu', $choO($giaTri['valid_from']), $loi,
                            'required type="datetime-local" step="1"', 'Hãy nhập ngày bắt đầu.', 'Theo giờ Việt Nam.') ?>
                    </div>
                    <div class="col-md-6">
                        <?= oNhap('valid_to', 'Ngày hết hạn', $choO($giaTri['valid_to']), $loi,
                            'required type="datetime-local" step="1" data-sau="valid_from"', 'Ngày hết hạn phải sau ngày bắt đầu.', 'Theo giờ Việt Nam.') ?>
                    </div>
                    <div class="col-md-4">
                        <?= oChon('key_algorithm', 'Thuật toán khoá', THUAT_TOAN_KHOA, $giaTri['key_algorithm'], $loi, 'Hãy chọn thuật toán khoá.') ?>
                    </div>
                    <div class="col-md-4">
                        <?= oNhap('key_size', 'Độ dài khoá (bit)', (string) $giaTri['key_size'], $loi,
                            'required type="number" min="1" max="65535"', 'Độ dài khoá phải là số dương.') ?>
                    </div>
                    <div class="col-md-4">
                        <?= oNhap('signature_algorithm', 'Thuật toán ký', $giaTri['signature_algorithm'], $loi,
                            'required maxlength="50" autocomplete="off"', 'Thuật toán ký là bắt buộc.', 'Ví dụ: sha256WithRSAEncryption') ?>
                    </div>
                    <div class="col-12">
                        <?= oNhap('fingerprint_sha256', 'Fingerprint SHA-256', $giaTri['fingerprint_sha256'], $loi,
                            'maxlength="128" pattern="[0-9A-Fa-f:\s]+" autocomplete="off"',
                            'Phải là ký tự hex, có thể kèm dấu hai chấm.', 'Không bắt buộc. Dán dạng EE:7A:1D… cũng được.') ?>
                    </div>
                    <div class="col-12">
                        <?= oNhap('serial', 'Serial', $giaTri['serial'], $loi,
                            'maxlength="256" pattern="[0-9A-Fa-f:\s]+" autocomplete="off"',
                            'Phải là ký tự hex, có thể kèm dấu hai chấm.', 'Không bắt buộc.') ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="san">Tên phụ (SAN)</label>
                        <textarea class="form-control ma-so <?= isset($loi['san']) ? 'is-invalid' : '' ?>" id="san" name="san" rows="2" maxlength="5000"><?= e($giaTri['san']) ?></textarea>
                        <div class="invalid-feedback"><?= e($loi['san'] ?? 'Tên phụ tối đa 5000 ký tự.') ?></div>
                        <small class="form-hint">Không bắt buộc. Các tên miền phụ mà chứng chỉ cũng bảo vệ.</small>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a class="btn" href="<?= e(url($id === null ? 'admin/chung_chi.php' : 'admin/chung_chi_xem.php?id=' . $id)) ?>">Huỷ</a>
                <button type="submit" class="btn btn-primary"><?= $id === null ? 'Thêm chứng chỉ' : 'Lưu thay đổi' ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
