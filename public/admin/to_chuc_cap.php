<?php
// Danh sách tổ chức cấp chứng chỉ (CA). Nút xoá gửi POST về chính trang này.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $loi = xoaToChuc($pdo, (int) chuoiPost('id'));
    datThongBao($loi === null ? 'success' : 'danger', $loi ?? 'Đã xoá tổ chức cấp.');
    chuyenHuong('admin/to_chuc_cap.php');
}

$danhSach = danhSachToChuc($pdo);
$tieuDe = 'Tổ chức cấp';
$trangHienTai = 'to_chuc_cap';
$nutThem = ['nhan' => 'Thêm tổ chức', 'dich' => 'admin/to_chuc_cap_form.php'];
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Tên tổ chức</th>
                            <th>Quốc gia</th>
                            <th>Độ tin cậy</th>
                            <th>Chứng chỉ</th>
                            <th class="w-1"><span class="visually-hidden">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSach as $dong): ?>
                            <tr>
                                <td><?= e($dong['name']) ?></td>
                                <td><?= e($dong['country']) ?></td>
                                <td>
                                    <?php if ($dong['is_trusted']): ?>
                                        <span class="badge bg-green-lt">Tin cậy</span>
                                    <?php else: ?>
                                        <span class="badge bg-yellow-lt">Không tin cậy</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $dong['so_chung_chi'] ?></td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a class="btn btn-sm" href="<?= e(url('admin/to_chuc_cap_form.php?id=' . (int) $dong['id'])) ?>">Sửa</a>
                                        <?php
                                        $idXoa = $dong['id'];
                                        $cauHoiXoa = 'Xoá tổ chức ' . $dong['name'] . '?';
                                        require __DIR__ . '/../../views/nut_xoa.php';
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($danhSach === []): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">Chưa có tổ chức nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
