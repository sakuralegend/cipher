<?php
// Nhật ký hoạt động, chỉ Admin. Chỉ xem: không có nút sửa hay xoá, và CSDL cũng từ chối sửa, xoá (schema.sql).
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/nguoi_dung.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauAdmin($pdo);

$loc = docLocNhatKy($_GET);
$trang = max(1, (int) ($_GET['trang'] ?? 1));
$ketQua = layNhatKy($pdo, $loc, $trang);
$soTrang = max(1, (int) ceil($ketQua['tong'] / NHAT_KY_MOI_TRANG));

$cacNguoi = ['khong_ro' => 'Không rõ (đăng nhập sai)'];
foreach (danhSachNguoiDung($pdo) as $nd) {
    $cacNguoi[$nd['id']] = $nd['full_name'] . ' (' . $nd['username'] . ')';
}
$cacHanhDong = array_map(fn(array $hd): string => $hd['nhan'], HANH_DONG);

// Địa chỉ của một trang kết quả, giữ nguyên bộ lọc đang dùng.
$diaChiTrang = fn(int $so): string => url('admin/nhat_ky.php?' . http_build_query(array_filter($loc, fn($v) => $v !== '') + ['trang' => $so]));

$tieuDe = 'Nhật ký hoạt động';
$trangHienTai = 'nhat_ky';
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <form class="card mb-3 d-print-none" method="get" action="<?= e(url('admin/nhat_ky.php')) ?>">
            <div class="card-body pb-0">
                <div class="row">
                    <div class="col-sm-6 col-lg-3"><?= oChon('nguoi', 'Người làm', $cacNguoi, $loc['nguoi'], [], '', false) ?></div>
                    <div class="col-sm-6 col-lg-3"><?= oChon('hanh_dong', 'Hành động', $cacHanhDong, $loc['hanh_dong'], [], '', false) ?></div>
                    <div class="col-sm-6 col-lg-2">
                        <div class="mb-3">
                            <label class="form-label" for="tu_ngay">Từ ngày</label>
                            <input class="form-control" type="date" id="tu_ngay" name="tu_ngay" value="<?= e($loc['tu_ngay']) ?>">
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <div class="mb-3">
                            <label class="form-label" for="den_ngay">Đến ngày</label>
                            <input class="form-control" type="date" id="den_ngay" name="den_ngay" value="<?= e($loc['den_ngay']) ?>">
                        </div>
                    </div>
                    <div class="col-lg-2 d-flex align-items-end gap-2 mb-3">
                        <button class="btn btn-primary flex-fill" type="submit">Lọc</button>
                        <a class="btn flex-fill" href="<?= e(url('admin/nhat_ky.php')) ?>">Bỏ lọc</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-header flex-wrap gap-1">
                <h2 class="card-title text-nowrap me-3"><?= $ketQua['tong'] ?> dòng</h2>
                <?php // Nói rõ cho người xem: không có nút xoá là cố ý, không phải thiếu. ?>
                <div class="card-subtitle ms-md-auto text-secondary">Nhật ký chỉ được thêm, không ai sửa hay xoá được.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Người làm</th>
                            <th class="d-none d-md-table-cell">Hành động</th>
                            <th>Đối tượng</th>
                            <th class="d-none d-lg-table-cell">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ketQua['dong'] as $dong): ?>
                            <?php
                            $hd = HANH_DONG[$dong['action']] ?? ['nhan' => $dong['action'], 'mau' => 'secondary'];
                            $loai = LOAI_DOI_TUONG[$dong['object_type']] ?? null;
                            ?>
                            <tr>
                                <?php // Ngày và giờ hai dòng, để bảng vừa màn hình điện thoại mà không bỏ cột nào. ?>
                                <td class="text-nowrap">
                                    <?= e(date('d/m/Y', strtotime($dong['created_at']))) ?>
                                    <div class="small text-secondary"><?= e(date('H:i:s', strtotime($dong['created_at']))) ?></div>
                                </td>
                                <td>
                                    <?php if ($dong['user_id'] === null): ?>
                                        <span class="text-secondary">Không rõ</span>
                                    <?php else: ?>
                                        <?= e($dong['full_name']) ?>
                                        <div class="small text-secondary ma-so"><?= e($dong['username']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell"><span class="badge bg-<?= e($hd['mau']) ?>-lt"><?= e($hd['nhan']) ?></span></td>
                                <td>
                                    <?php // Màn hẹp không có cột Hành động: nhãn hành động nằm đầu ô này. ?>
                                    <span class="badge bg-<?= e($hd['mau']) ?>-lt d-md-none mb-1"><?= e($hd['nhan']) ?></span>
                                    <?php // Link chỉ đặt trên loại đối tượng: mô tả có thể kèm lý do ("nhanvien, sai mật khẩu"),
                                          // tô màu link cả câu thì lý do trông như một phần tên (review 08/11/2026).
                                          // Chỉ đặt link khi đối tượng còn trong CSDL; đã xoá thì vẫn đọc được tên lúc đó. ?>
                                    <?php if ($loai !== null): ?>
                                        <div class="small">
                                            <?php if ($dong['con_ton_tai']): ?>
                                                <a href="<?= e(url($loai['trang'] . (int) $dong['object_id'])) ?>"><?= e($loai['nhan']) ?></a>
                                            <?php else: ?>
                                                <span class="text-secondary"><?= e($loai['nhan']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?= e($dong['detail']) ?>
                                </td>
                                <td class="d-none d-lg-table-cell small text-secondary ma-so"><?= e($dong['ip']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($ketQua['dong'] === []): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">Chưa có dòng nhật ký nào khớp bộ lọc.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($soTrang > 1): ?>
                <div class="card-footer d-flex align-items-center">
                    <p class="m-0 text-secondary">Trang <?= $trang ?> / <?= $soTrang ?></p>
                    <ul class="pagination m-0 ms-auto">
                        <li class="page-item <?= $trang <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e($diaChiTrang($trang - 1)) ?>">Trước</a>
                        </li>
                        <li class="page-item <?= $trang >= $soTrang ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e($diaChiTrang($trang + 1)) ?>">Sau</a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
