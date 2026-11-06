<?php
// Form thêm (không có ?id=) và sửa (có ?id=) máy chủ.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$loi = [];
$giaTri = ['hostname' => '', 'ip_address' => '', 'port' => '443', 'environment' => '', 'owner' => ''];

if ($id !== null) {
    $giaTri = timMayChu($pdo, $id);
    if ($giaTri === null) {
        datThongBao('danger', 'Máy chủ không còn tồn tại.');
        chuyenHuong('admin/may_chu.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $giaTri = [
        'hostname' => trim(chuoiPost('hostname')),
        'ip_address' => trim(chuoiPost('ip_address')),
        'port' => trim(chuoiPost('port')),
        'environment' => chuoiPost('environment'),
        'owner' => trim(chuoiPost('owner')),
    ];
    $loi = luuMayChu($pdo, $id, $giaTri);
    if ($loi === []) {
        datThongBao('success', $id === null ? 'Đã thêm máy chủ.' : 'Đã lưu thay đổi.');
        chuyenHuong('admin/may_chu.php');
    }
}

$hanhDong = 'admin/may_chu_form.php' . ($id !== null ? '?id=' . $id : '');
$tieuDe = $id === null ? 'Thêm máy chủ' : 'Sửa máy chủ';
$trangHienTai = 'may_chu';
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-lg-7">
                <form class="card" method="post" action="<?= e(url($hanhDong)) ?>" data-kiem-tra novalidate>
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <div class="card-body">
                        <?= oNhap('hostname', 'Tên máy chủ', $giaTri['hostname'], $loi,
                            'required maxlength="255" pattern="[A-Za-z0-9._\-]+" autocomplete="off"',
                            'Gồm chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.') ?>
                        <?= oNhap('ip_address', 'Địa chỉ IP', $giaTri['ip_address'], $loi,
                            'required maxlength="45" pattern="[0-9A-Fa-f:.]+" autocomplete="off"',
                            'Địa chỉ IP không hợp lệ.', 'IPv4 hoặc IPv6, ví dụ 192.0.2.10') ?>
                        <?= oNhap('port', 'Cổng', (string) $giaTri['port'], $loi,
                            'required type="number" min="1" max="65535"', 'Cổng phải là số từ 1 đến 65535.') ?>
                        <?= oChon('environment', 'Môi trường', MOI_TRUONG, $giaTri['environment'], $loi, 'Hãy chọn môi trường.') ?>
                        <?= oNhap('owner', 'Người phụ trách', $giaTri['owner'], $loi,
                            'required maxlength="100" autocomplete="off"', 'Người phụ trách là bắt buộc.') ?>
                    </div>
                    <div class="card-footer text-end">
                        <a class="btn" href="<?= e(url('admin/may_chu.php')) ?>">Huỷ</a>
                        <button type="submit" class="btn btn-primary"><?= $id === null ? 'Thêm máy chủ' : 'Lưu thay đổi' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
