<?php
// Danh sách tên miền. Ai dùng: Admin và Nhân viên; chỉ Admin thấy và dùng được nút Xoá (user chốt 12/11/2026). Nút xoá gửi POST về chính trang này.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $loi = xoaTenMien($pdo, (int) chuoiPost('id'), $nguoi);
    datThongBao($loi === null ? 'success' : 'danger', $loi ?? 'Đã xoá tên miền.');
    chuyenHuong('admin/ten_mien.php');
}

$danhSach = danhSachTenMien($pdo);
$tieuDe = 'Tên miền';
$trangHienTai = 'ten_mien';
$nutThem = ['nhan' => 'Thêm tên miền', 'dich' => 'admin/ten_mien_form.php'];
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
                            <th>Tên miền</th>
                            <th>Máy chủ</th>
                            <th>Chứng chỉ</th>
                            <th class="w-1"><span class="visually-hidden">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSach as $dong): ?>
                            <tr>
                                <td><?= e($dong['domain_name']) ?></td>
                                <td><?= e($dong['hostname']) ?></td>
                                <td><?= (int) $dong['so_chung_chi'] ?></td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a class="btn btn-sm" href="<?= e(url('admin/ten_mien_form.php?id=' . (int) $dong['id'])) ?>">Sửa</a>
                                        <?php
                                        $idXoa = $dong['id'];
                                        $cauHoiXoa = 'Xoá tên miền ' . $dong['domain_name'] . '?';
                                        require __DIR__ . '/../../views/nut_xoa.php';
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($danhSach === []): ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4">Chưa có tên miền nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
