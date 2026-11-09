<?php
// Danh sách máy chủ. Nút xoá gửi POST về chính trang này.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $loi = xoaMayChu($pdo, (int) chuoiPost('id'));
    datThongBao($loi === null ? 'success' : 'danger', $loi ?? 'Đã xoá máy chủ.');
    chuyenHuong('admin/may_chu.php');
}

$danhSach = danhSachMayChu($pdo);
$tieuDe = 'Máy chủ';
$trangHienTai = 'may_chu';
$nutThem = ['nhan' => 'Thêm máy chủ', 'dich' => 'admin/may_chu_form.php'];
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table bang-keo-ngang">
                    <thead>
                        <tr>
                            <th>Tên máy chủ</th>
                            <th>Địa chỉ</th>
                            <th>Môi trường</th>
                            <th>Người phụ trách</th>
                            <th>Tên miền</th>
                            <th class="w-1"><span class="visually-hidden">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSach as $dong): ?>
                            <tr>
                                <td><?= e($dong['hostname']) ?></td>
                                <td class="ma-so"><?= e($dong['ip_address']) ?>:<?= (int) $dong['port'] ?></td>
                                <td><?= e(MOI_TRUONG[$dong['environment']]) ?></td>
                                <td><?= e($dong['owner']) ?></td>
                                <td><?= (int) $dong['so_ten_mien'] ?></td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a class="btn btn-sm" href="<?= e(url('admin/may_chu_form.php?id=' . (int) $dong['id'])) ?>">Sửa</a>
                                        <?php
                                        $idXoa = $dong['id'];
                                        $cauHoiXoa = 'Xoá máy chủ ' . $dong['hostname'] . '?';
                                        require __DIR__ . '/../../views/nut_xoa.php';
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($danhSach === []): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Chưa có máy chủ nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
