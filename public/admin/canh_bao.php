<?php
// Trang Cảnh báo: mỗi lần mở đều tạo cảnh báo còn thiếu rồi hiện cảnh báo của chu kỳ hiện tại.
// Nút Đã xem gửi tới chung_chi_thao_tac.php.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

taoCanhBao($pdo);
$danhSach = danhSachCanhBao($pdo);
$soMoi = demCanhBaoMoi($pdo);

// Mốc càng gần càng gấp; màu là tên màu của Tabler.
const MAU_MOC = [7 => 'red', 15 => 'orange', 30 => 'yellow'];

$tieuDe = 'Cảnh báo';
$trangHienTai = 'canh_bao';
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $soMoi ?> cảnh báo mới</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Mốc</th>
                            <th>Chứng chỉ</th>
                            <th>Tên chính</th>
                            <th>Hết hạn</th>
                            <th>Còn lại</th>
                            <th>Trạng thái</th>
                            <th>Cảnh báo</th>
                            <th class="w-1"><span class="visually-hidden">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSach as $dong): ?>
                            <tr>
                                <td><span class="badge bg-<?= e(MAU_MOC[(int) $dong['threshold_days']]) ?>-lt"><?= (int) $dong['threshold_days'] ?> ngày</span></td>
                                <td class="ma-so">
                                    <a href="<?= e(url('admin/chung_chi_xem.php?id=' . (int) $dong['certificate_id'])) ?>"><?= e($dong['cert_code']) ?></a>
                                </td>
                                <td class="ma-so"><?= e($dong['subject_cn']) ?></td>
                                <td><?= e(date('d/m/Y', strtotime($dong['valid_to']))) ?></td>
                                <td><?= e(moTaSoNgay($dong)) ?></td>
                                <td>
                                    <?php
                                    $trangThaiHienThi = $dong['status'];
                                    require __DIR__ . '/../../views/nhan_trang_thai.php';
                                    ?>
                                </td>
                                <td><?= $dong['alert_status'] === 'new' ? '<span class="badge bg-blue-lt">Mới</span>' : '<span class="text-secondary">Đã xem</span>' ?></td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <?php if ($dong['alert_status'] === 'new'): ?>
                                            <form method="post" action="<?= e(url('admin/chung_chi_thao_tac.php')) ?>">
                                                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $dong['id'] ?>">
                                                <button class="btn btn-sm" name="hanh_dong" value="da_xem">Đã xem</button>
                                            </form>
                                        <?php endif; ?>
                                        <a class="btn btn-sm" href="<?= e(url('admin/chung_chi_gia_han.php?id=' . (int) $dong['certificate_id'])) ?>">Gia hạn</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($danhSach === []): ?>
                            <tr><td colspan="8" class="text-center text-secondary py-4">Không có chứng chỉ nào cần chú ý.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
