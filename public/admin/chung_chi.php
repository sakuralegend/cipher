<?php
// Danh sách chứng chỉ có tìm kiếm, lọc, phân trang; số ngày và trạng thái lấy từ view,
// luôn đúng theo ngày hiện tại. Bộ lọc đi qua địa chỉ trang (GET), nên chuyển trang vẫn giữ lọc.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$loc = docBoLoc($_GET);
$locDangDung = array_filter($loc, fn(string $giaTri): bool => $giaTri !== '');

$tongDong = demChungChi($pdo, $loc);
$tongTrang = max(1, (int) ceil($tongDong / SO_DONG_MOT_TRANG));
$trang = min(max(1, (int) ($_GET['trang'] ?? 1)), $tongTrang);
$danhSach = danhSachChungChi($pdo, $trang, $loc);

$cacTrangThai = array_map(fn(array $trangThai): string => $trangThai['nhan'], TRANG_THAI);
$cacToChuc = array_column(danhSachToChuc($pdo), 'name', 'id');
$cacTenMien = array_column(danhSachTenMien($pdo), 'domain_name', 'id');
$cacMayChu = array_column(danhSachMayChu($pdo), 'hostname', 'id');

$tieuDe = 'Chứng chỉ';
$trangHienTai = 'chung_chi';
$nutThem = ['nhan' => 'Thêm chứng chỉ', 'dich' => 'admin/chung_chi_form.php'];
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <form class="card mb-3 d-print-none" method="get" action="<?= e(url('admin/chung_chi.php')) ?>">
            <div class="card-body pb-0">
                <div class="row">
                    <div class="col-12 col-lg-6">
                        <?= oNhap('tu_khoa', 'Từ khoá', $loc['tu_khoa'], [], 'maxlength="255" autocomplete="off"', '', 'Tìm trong mã, tên chính, tên phụ, serial, fingerprint.') ?>
                    </div>
                    <div class="col-6 col-lg-3">
                        <?= oNhap('het_han_tu', 'Hết hạn từ ngày', $loc['het_han_tu'], [], 'type="date"') ?>
                    </div>
                    <div class="col-6 col-lg-3">
                        <?= oNhap('het_han_den', 'Đến ngày', $loc['het_han_den'], [], 'type="date"') ?>
                    </div>
                    <div class="col-6 col-lg-3">
                        <?= oChon('trang_thai', 'Trạng thái', $cacTrangThai, $loc['trang_thai'], [], '', false) ?>
                    </div>
                    <div class="col-6 col-lg-3">
                        <?= oChon('ca_id', 'Tổ chức cấp', $cacToChuc, $loc['ca_id'], [], '', false) ?>
                    </div>
                    <div class="col-6 col-lg-3">
                        <?= oChon('domain_id', 'Tên miền', $cacTenMien, $loc['domain_id'], [], '', false) ?>
                    </div>
                    <div class="col-6 col-lg-3">
                        <?= oChon('server_id', 'Máy chủ', $cacMayChu, $loc['server_id'], [], '', false) ?>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center">
                <span class="text-secondary"><?= $locDangDung === [] ? 'Tổng' : 'Tìm thấy' ?> <?= $tongDong ?> chứng chỉ</span>
                <div class="btn-list ms-auto">
                    <?php if ($locDangDung !== []): ?>
                        <a class="btn" href="<?= e(url('admin/chung_chi.php')) ?>">Xoá lọc</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Tìm</button>
                </div>
            </div>
        </form>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tên chính</th>
                            <th>Tổ chức cấp</th>
                            <th>Hết hạn</th>
                            <th>Còn lại</th>
                            <th>Trạng thái</th>
                            <th class="w-1"><span class="visually-hidden">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSach as $dong): ?>
                            <tr>
                                <td class="ma-so">
                                    <a href="<?= e(url('admin/chung_chi_xem.php?id=' . (int) $dong['id'])) ?>"><?= e($dong['cert_code']) ?></a>
                                </td>
                                <td class="ma-so"><?= e($dong['subject_cn']) ?></td>
                                <td><?= e($dong['ca_name']) ?></td>
                                <td><?= e(date('d/m/Y', strtotime($dong['valid_to']))) ?></td>
                                <td><?= e(moTaSoNgay($dong)) ?></td>
                                <td>
                                    <?php
                                    $trangThaiHienThi = $dong['status'];
                                    require __DIR__ . '/../../views/nhan_trang_thai.php';
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a class="btn btn-sm" href="<?= e(url('admin/chung_chi_xem.php?id=' . (int) $dong['id'])) ?>">Xem</a>
                                        <a class="btn btn-sm" href="<?= e(url('admin/chung_chi_form.php?id=' . (int) $dong['id'])) ?>">Sửa</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($danhSach === []): ?>
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">
                                    <?= $locDangDung === [] ? 'Chưa có chứng chỉ nào.' : 'Không có chứng chỉ nào khớp bộ lọc.' ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($tongTrang > 1): ?>
                <div class="card-footer d-flex align-items-center">
                    <ul class="pagination m-0 ms-auto">
                        <?php for ($so = 1; $so <= $tongTrang; $so++): ?>
                            <li class="page-item <?= $so === $trang ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(url('admin/chung_chi.php?' . http_build_query([...$locDangDung, 'trang' => $so]))) ?>"><?= $so ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
