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
$coGiaTri = fn(string $giaTri): bool => $giaTri !== '';
$locDangDung = array_filter($loc, $coGiaTri);
// Chưa chọn cột thì địa chỉ không cần mang cách xếp.
$sapXepDangDung = $sapXep['sap_xep'] === '' ? [] : $sapXep;

$tongDong = demChungChi($pdo, $loc);
$tongTrang = max(1, (int) ceil($tongDong / SO_DONG_MOT_TRANG));
$trang = min(max(1, (int) ($_GET['trang'] ?? 1)), $tongTrang);
$danhSach = danhSachChungChi($pdo, $trang, $loc, $sapXep);
$soTheoTrangThai = demTheoTrangThai($pdo, $loc);

$cacToChuc = array_column(danhSachToChuc($pdo), 'name', 'id');
$cacTenMien = array_column(danhSachTenMien($pdo), 'domain_name', 'id');
$cacMayChu = array_column(danhSachMayChu($pdo), 'hostname', 'id');

// Các ô nằm trong phần thu gọn. Đang lọc bằng ô nào trong đó thì mở sẵn, để không quên là đang lọc.
$soLocTrongPhanGon = count(array_intersect_key($locDangDung, array_flip(['het_han_tu', 'het_han_den', 'ca_id', 'domain_id', 'server_id'])));

// Dựng địa chỉ giữ nguyên lọc và cách xếp hiện tại, chỉ đổi các khoá trong $doi. Giá trị '' là bỏ khoá.
$diaChi = fn(array $doi = []): string => url('admin/chung_chi.php?' . http_build_query(
    array_filter([...$locDangDung, ...$sapXepDangDung, ...$doi], fn($giaTri): bool => $giaTri !== '')
));

// Tiêu đề cột bấm được: lần đầu xếp tăng dần, bấm lại đảo chiều. Đổi cách xếp thì về trang 1.
$tieuDeCot = function (string $cot, string $nhan) use ($sapXep, $diaChi): string {
    $dangXep = $sapXep['sap_xep'] === $cot;
    $chieuMoi = $dangXep && $sapXep['chieu'] === 'tang' ? 'giam' : 'tang';
    $muiTen = $dangXep ? ($sapXep['chieu'] === 'tang' ? ' ↑' : ' ↓') : '';
    $ariaSort = $dangXep ? ' aria-sort="' . ($sapXep['chieu'] === 'tang' ? 'ascending' : 'descending') . '"' : '';
    return '<th' . $ariaSort . '><a class="cot-sap-xep" href="' . e($diaChi(['sap_xep' => $cot, 'chieu' => $chieuMoi]))
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
        <div class="d-flex flex-wrap gap-2 mb-3 d-print-none">
            <a class="btn btn-sm <?= $loc['trang_thai'] === '' ? 'btn-primary' : '' ?>"
               <?= $loc['trang_thai'] === '' ? 'aria-current="true"' : '' ?>
               href="<?= e($diaChi(['trang_thai' => ''])) ?>">Tất cả <span class="ms-1"><?= array_sum($soTheoTrangThai) ?></span></a>
            <?php foreach (TRANG_THAI as $khoa => $thongTin): ?>
                <a class="btn btn-sm <?= $loc['trang_thai'] === $khoa ? 'btn-primary' : '' ?>"
                   <?= $loc['trang_thai'] === $khoa ? 'aria-current="true"' : '' ?>
                   href="<?= e($diaChi(['trang_thai' => $khoa])) ?>">
                    <span class="badge bg-<?= e($thongTin['mau']) ?> badge-empty me-2"></span><?= e($thongTin['nhan']) ?>
                    <span class="ms-1"><?= $soTheoTrangThai[$khoa] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="card mb-3 d-print-none" method="get" action="<?= e(url('admin/chung_chi.php')) ?>">
            <?php // Bấm Tìm vẫn giữ nút trạng thái và cách xếp đang chọn. ?>
            <?php foreach (array_filter(['trang_thai' => $loc['trang_thai'], ...$sapXepDangDung], $coGiaTri) as $khoa => $giaTri): ?>
                <input type="hidden" name="<?= e($khoa) ?>" value="<?= e($giaTri) ?>">
            <?php endforeach; ?>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <label class="visually-hidden" for="tu_khoa">Từ khoá</label>
                    <input class="form-control flex-fill w-auto" id="tu_khoa" name="tu_khoa" value="<?= e($loc['tu_khoa']) ?>"
                           maxlength="255" autocomplete="off" placeholder="Tìm mã, tên chính, tên phụ, serial, fingerprint">
                    <button type="button" class="btn" data-bs-toggle="collapse" data-bs-target="#bo-loc-them"
                            aria-controls="bo-loc-them" aria-expanded="<?= $soLocTrongPhanGon > 0 ? 'true' : 'false' ?>">
                        Bộ lọc<?= $soLocTrongPhanGon > 0 ? ' (' . $soLocTrongPhanGon . ')' : '' ?>
                    </button>
                    <button type="submit" class="btn btn-primary">Tìm</button>
                    <?php if ($locDangDung !== [] || $sapXepDangDung !== []): ?>
                        <a class="btn" href="<?= e(url('admin/chung_chi.php')) ?>">Xoá lọc</a>
                    <?php endif; ?>
                </div>
                <div class="collapse <?= $soLocTrongPhanGon > 0 ? 'show' : '' ?>" id="bo-loc-them">
                    <div class="row mt-3">
                        <div class="col-6 col-lg">
                            <?= oNhap('het_han_tu', 'Hết hạn từ ngày', $loc['het_han_tu'], [], 'type="date"') ?>
                        </div>
                        <div class="col-6 col-lg">
                            <?= oNhap('het_han_den', 'Đến ngày', $loc['het_han_den'], [], 'type="date"') ?>
                        </div>
                        <div class="col-12 col-sm-4 col-lg">
                            <?= oChon('ca_id', 'Tổ chức cấp', $cacToChuc, $loc['ca_id'], [], '', false) ?>
                        </div>
                        <div class="col-6 col-sm-4 col-lg">
                            <?= oChon('domain_id', 'Tên miền', $cacTenMien, $loc['domain_id'], [], '', false) ?>
                        </div>
                        <div class="col-6 col-sm-4 col-lg">
                            <?= oChon('server_id', 'Máy chủ', $cacMayChu, $loc['server_id'], [], '', false) ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <?= $tieuDeCot('ma', 'Mã') ?>
                            <?= $tieuDeCot('ten', 'Tên chính') ?>
                            <?= $tieuDeCot('to_chuc', 'Tổ chức cấp') ?>
                            <?= $tieuDeCot('het_han', 'Hết hạn') ?>
                            <?= $tieuDeCot('con_lai', 'Còn lại') ?>
                            <?= $tieuDeCot('trang_thai', 'Trạng thái') ?>
                            <th class="w-1"><span class="visually-hidden">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSach as $dong): ?>
                            <tr class="<?= $dong['status'] === 'revoked' ? 'dong-thu-hoi' : '' ?>">
                                <td class="ma-so text-nowrap">
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
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-secondary"><?= $locDangDung === [] ? 'Tổng' : 'Tìm thấy' ?> <?= $tongDong ?> chứng chỉ</p>
                <?php if ($tongTrang > 1): ?>
                    <ul class="pagination m-0 ms-auto">
                        <?php for ($so = 1; $so <= $tongTrang; $so++): ?>
                            <li class="page-item <?= $so === $trang ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e($diaChi(['trang' => $so])) ?>"><?= $so ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
