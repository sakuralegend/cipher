<?php
// Form thêm (không có ?id=) và sửa (có ?id=) tên miền.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$loi = [];
$giaTri = ['domain_name' => '', 'server_id' => ''];

if ($id !== null) {
    $giaTri = timTenMien($pdo, $id);
    if ($giaTri === null) {
        datThongBao('danger', 'Tên miền không còn tồn tại.');
        chuyenHuong('admin/ten_mien.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $giaTri = [
        'domain_name' => trim(chuoiPost('domain_name')),
        'server_id' => chuoiPost('server_id'),
    ];
    $loi = luuTenMien($pdo, $id, $giaTri);
    if ($loi === []) {
        datThongBao('success', $id === null ? 'Đã thêm tên miền.' : 'Đã lưu thay đổi.');
        chuyenHuong('admin/ten_mien.php');
    }
}

$cacMayChu = array_column(danhSachMayChu($pdo), 'hostname', 'id');

$hanhDong = 'admin/ten_mien_form.php' . ($id !== null ? '?id=' . $id : '');
$tieuDe = $id === null ? 'Thêm tên miền' : 'Sửa tên miền';
$trangHienTai = 'ten_mien';
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-lg-7">
                <?php if ($cacMayChu === []): ?>
                    <div class="alert alert-warning" role="alert">
                        Chưa có máy chủ nào. Hãy <a href="<?= e(url('admin/may_chu_form.php')) ?>">thêm máy chủ</a> trước.
                    </div>
                <?php endif; ?>
                <form class="card" method="post" action="<?= e(url($hanhDong)) ?>" data-kiem-tra novalidate>
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <div class="card-body">
                        <?= oNhap('domain_name', 'Tên miền', $giaTri['domain_name'], $loi,
                            'required maxlength="253" pattern="[A-Za-z0-9*][A-Za-z0-9.\-]*\.[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9]" autocomplete="off"',
                            'Tên miền không hợp lệ. Ví dụ đúng: cert.cipher.id.vn hoặc *.cipher.id.vn') ?>
                        <?= oChon('server_id', 'Máy chủ', $cacMayChu, (string) $giaTri['server_id'], $loi, 'Hãy chọn máy chủ.') ?>
                    </div>
                    <div class="card-footer text-end">
                        <a class="btn" href="<?= e(url('admin/ten_mien.php')) ?>">Huỷ</a>
                        <button type="submit" class="btn btn-primary"><?= $id === null ? 'Thêm tên miền' : 'Lưu thay đổi' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
