<?php
// Form gia hạn: chứng chỉ được cấp lại thì dùng trang này, không dùng form sửa, để có lịch sử.
// Quy tắc nằm ở giaHanChungChi(); trang này chỉ nhận dữ liệu và hiện kết quả.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$id = (int) ($_GET['id'] ?? 0);
$cc = timChungChi($pdo, $id);
if ($cc === null) {
    datThongBao('danger', 'Chứng chỉ không còn tồn tại.');
    chuyenHuong('admin/chung_chi.php');
}
if ($cc['is_revoked']) {
    datThongBao('danger', 'Chứng chỉ đã thu hồi không gia hạn được. Muốn dùng lại, hãy thêm chứng chỉ mới.');
    chuyenHuong('admin/chung_chi_xem.php?id=' . $id);
}

$loi = [];
$giaTri = ['valid_to' => '', 'fingerprint_sha256' => '', 'serial' => '', 'note' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $giaTri = [
        'valid_to' => chuoiPost('valid_to'),
        'fingerprint_sha256' => trim(chuoiPost('fingerprint_sha256')),
        'serial' => trim(chuoiPost('serial')),
        'note' => trim(chuoiPost('note')),
    ];
    $loi = giaHanChungChi($pdo, $id, $giaTri, (int) $nguoi['id']);
    if ($loi === []) {
        datThongBao('success', 'Đã gia hạn chứng chỉ.');
        chuyenHuong('admin/chung_chi_xem.php?id=' . $id);
    }
    // Người khác vừa xoá hoặc thu hồi chứng chỉ trong lúc đang nhập.
    if (isset($loi['chung'])) {
        datThongBao('danger', $loi['chung']);
        chuyenHuong('admin/chung_chi_xem.php?id=' . $id);
    }
}

// Trình duyệt chặn sớm ngày quá sớm; PHP vẫn kiểm tra lại. Ô datetime-local cần dạng có chữ T.
$hanSomNhat = str_replace(' ', 'T', max($cc['valid_to'], date('Y-m-d H:i:s')));

$tieuDe = 'Gia hạn ' . $cc['cert_code'];
$trangHienTai = 'chung_chi';
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <form class="card" method="post" action="<?= e(url('admin/chung_chi_gia_han.php?id=' . $id)) ?>" data-kiem-tra novalidate>
            <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
            <div class="card-body">
                <div class="datagrid mb-4">
                    <div class="datagrid-item">
                        <div class="datagrid-title">Tên chính</div>
                        <div class="datagrid-content"><?= e($cc['subject_cn']) ?></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Hạn hiện tại</div>
                        <div class="datagrid-content"><?= e(date('d/m/Y H:i:s', strtotime($cc['valid_to']))) ?></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Còn lại</div>
                        <div class="datagrid-content"><?= e(moTaSoNgay($cc)) ?></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <?= oNhap('valid_to', 'Ngày hết hạn mới', $giaTri['valid_to'], $loi,
                            'required type="datetime-local" step="1" min="' . e($hanSomNhat) . '"',
                            'Ngày hết hạn mới phải sau hạn hiện tại và sau thời điểm này.', 'Theo giờ Việt Nam.') ?>
                    </div>
                    <div class="col-md-6">
                        <?= oNhap('note', 'Ghi chú', $giaTri['note'], $loi, 'maxlength="255" autocomplete="off"', 'Ghi chú tối đa 255 ký tự.', 'Không bắt buộc.') ?>
                    </div>
                    <div class="col-12">
                        <?= oNhap('fingerprint_sha256', 'Fingerprint SHA-256 mới', $giaTri['fingerprint_sha256'], $loi,
                            'maxlength="128" pattern="[0-9A-Fa-f:\s]+" autocomplete="off"',
                            'Phải là ký tự hex, có thể kèm dấu hai chấm.',
                            'Không bắt buộc. Để trống thì chứng chỉ hiện "Chưa có"; giá trị cũ vẫn nằm trong lịch sử.') ?>
                    </div>
                    <div class="col-12">
                        <?= oNhap('serial', 'Serial mới', $giaTri['serial'], $loi,
                            'maxlength="256" pattern="[0-9A-Fa-f:\s]+" autocomplete="off"',
                            'Phải là ký tự hex, có thể kèm dấu hai chấm.',
                            'Không bắt buộc. Để trống thì chứng chỉ hiện "Chưa có"; giá trị cũ vẫn nằm trong lịch sử.') ?>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a class="btn" href="<?= e(url('admin/chung_chi_xem.php?id=' . $id)) ?>">Huỷ</a>
                <button type="submit" class="btn btn-primary">Gia hạn</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
