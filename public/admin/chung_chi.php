<?php
// Danh sách chứng chỉ: hàng nút trạng thái, tìm kiếm, lọc, sắp xếp, phân trang. Số ngày và trạng thái
// lấy từ view, luôn đúng theo ngày hiện tại. Lọc và cách xếp đi qua địa chỉ trang (GET), nên chuyển
// trang vẫn giữ nguyên.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$loc = docBoLoc($_GET);
$sapXep = docSapXep($_GET);
$tongDong = demChungChi($pdo, $loc);
$tongTrang = max(1, (int) ceil($tongDong / SO_DONG_MOT_TRANG));
$trang = min(max(1, (int) ($_GET['trang'] ?? 1)), $tongTrang);
$danhSach = danhSachChungChi($pdo, $trang, $loc, $sapXep);
$soTheoTrangThai = demTheoTrangThai($pdo, $loc);

$cacToChuc = array_column(danhSachToChuc($pdo), 'name', 'id');
$cacTenMien = array_column(danhSachTenMien($pdo), 'domain_name', 'id');
$cacMayChu = array_column(danhSachMayChu($pdo), 'hostname', 'id');

$trangLoc = 'admin/chung_chi.php';
$coLoc = array_filter($loc, fn(string $giaTri): bool => $giaTri !== '') !== [];

// Tiêu đề cột bấm được: lần đầu xếp tăng dần, bấm lại đảo chiều. Đổi cách xếp thì về trang 1.
$tieuDeCot = function (string $cot, string $nhan, string $lop = '') use ($sapXep, $loc, $trangLoc): string {
    $dangXep = $sapXep['sap_xep'] === $cot;
    $chieuMoi = $dangXep && $sapXep['chieu'] === 'tang' ? 'giam' : 'tang';
    $muiTen = $dangXep ? ($sapXep['chieu'] === 'tang' ? ' ↑' : ' ↓') : '';
    $ariaSort = $dangXep ? ' aria-sort="' . ($sapXep['chieu'] === 'tang' ? 'ascending' : 'descending') . '"' : '';
    return '<th' . ($lop === '' ? '' : ' class="' . $lop . '"') . $ariaSort . '><a class="cot-sap-xep" href="' . e(diaChiCoLoc($trangLoc, $loc, $sapXep, ['sap_xep' => $cot, 'chieu' => $chieuMoi]))
        . '">' . e($nhan) . $muiTen . '</a></th>';
};

$tieuDe = 'Chứng chỉ';
$trangHienTai = 'chung_chi';
$nutThem = ['nhan' => 'Thêm chứng chỉ', 'dich' => 'admin/chung_chi_form.php'];
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <?php require __DIR__ . '/../../views/bo_loc.php'; ?>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <?= $tieuDeCot('ma', 'Mã') ?>
                            <?= $tieuDeCot('ten', 'Tên chính') ?>
                            <?= $tieuDeCot('to_chuc', 'Tổ chức cấp', 'd-none d-md-table-cell') ?>
                            <?= $tieuDeCot('het_han', 'Hết hạn', 'd-none d-md-table-cell') ?>
                            <?= $tieuDeCot('con_lai', 'Còn lại') ?>
                            <?= $tieuDeCot('trang_thai', 'Trạng thái') ?>
                            <th class="w-1 d-none d-md-table-cell"><span class="visually-hidden">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSach as $dong): ?>
                            <tr class="<?= $dong['status'] === 'revoked' ? 'dong-thu-hoi' : '' ?>">
                                <td class="ma-so text-nowrap">
                                    <a href="<?= e(url('admin/chung_chi_xem.php?id=' . (int) $dong['id'])) ?>"><?= e($dong['cert_code']) ?></a>
                                </td>
                                <td class="text-break"><?= e($dong['subject_cn']) ?></td>
                                <td class="d-none d-md-table-cell"><?= e($dong['ca_name']) ?></td>
                                <td class="d-none d-md-table-cell"><?= e(date('d/m/Y', strtotime($dong['valid_to']))) ?></td>
                                <td><?= e(moTaSoNgay($dong)) ?></td>
                                <td>
                                    <?php
                                    $trangThaiHienThi = $dong['status'];
                                    require __DIR__ . '/../../views/nhan_trang_thai.php';
                                    ?>
                                </td>
                                <td class="d-none d-md-table-cell">
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
                                    <?= !$coLoc ? 'Chưa có chứng chỉ nào.' : 'Không có chứng chỉ nào khớp bộ lọc.' ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-secondary"><?= !$coLoc ? 'Tổng' : 'Tìm thấy' ?> <?= $tongDong ?> chứng chỉ</p>
                <?php if ($tongTrang > 1): ?>
                    <ul class="pagination m-0 ms-auto">
                        <?php for ($so = 1; $so <= $tongTrang; $so++): ?>
                            <li class="page-item <?= $so === $trang ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(diaChiCoLoc($trangLoc, $loc, $sapXep, ['trang' => $so])) ?>"><?= $so ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
