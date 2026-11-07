<?php
// Thống kê: 4 biểu đồ, mỗi biểu đồ có bảng số ngay dưới, cùng theo một bộ lọc với danh sách chứng chỉ.
// Khoảng thời gian là khoảng ngày hết hạn, vì hệ thống không lưu thời điểm thêm chứng chỉ.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$loc = docBoLoc($_GET);
$sapXep = docSapXep([]); // Thống kê không có cách xếp để giữ.
$trangLoc = 'admin/thong_ke.php';

$soTheoTrangThai = demTheoTrangThai($pdo, $loc);
$cacToChuc = array_column(danhSachToChuc($pdo), 'name', 'id');
$cacTenMien = array_column(danhSachTenMien($pdo), 'domain_name', 'id');
$cacMayChu = array_column(danhSachMayChu($pdo), 'hostname', 'id');

// Biểu đồ trạng thái giữ cả trạng thái đang chọn, để tổng khớp ba biểu đồ còn lại.
$trangThai = demTheoTrangThai($pdo, $loc, true);
$tong = array_sum($trangThai);
$toChuc = demTheoNhom($pdo, 'to_chuc', $loc);
$thuatToan = demTheoNhom($pdo, 'thuat_toan', $loc);
$theoThang = hetHanTheoThang($pdo, $loc);
// Trần trục theo toàn bộ chứng chỉ, giữ nguyên khi lọc (xem tranBieuDoThongKe()).
$tran = tranBieuDoThongKe($pdo);
// '2026-11' thành '11/2026' để đọc theo kiểu Việt Nam.
$nhanThang = array_map(fn(string $thang): string => substr($thang, 5) . '/' . substr($thang, 0, 4), array_keys($theoThang));

$tieuDe = 'Thống kê';
$trangHienTai = 'thong_ke';
$coBieuDo = true;
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <?php require __DIR__ . '/../../views/bo_loc.php'; ?>

        <div class="row row-cards">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="card-title">Theo trạng thái</h2>
                        <div class="card-actions text-secondary">Tổng <?= $tong ?> chứng chỉ</div>
                    </div>
                    <div class="card-body">
                        <?= khungBieuDo([
                            'huong' => 'ngang',
                            'nhan' => array_values(array_column(TRANG_THAI, 'nhan')),
                            'so' => array_values($trangThai),
                            'mau' => array_values(array_column(TRANG_THAI, 'mau')),
                            'tran' => $tran['trang_thai'],
                        ], 'Biểu đồ số chứng chỉ theo trạng thái; số liệu ở bảng bên dưới.') ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm card-table">
                            <thead><tr><th>Trạng thái</th><th class="text-end">Số chứng chỉ</th></tr></thead>
                            <tbody>
                                <?php foreach (TRANG_THAI as $khoa => $thongTin): ?>
                                    <tr>
                                        <td><span class="badge bg-<?= e($thongTin['mau']) ?> badge-empty me-2"></span><?= e($thongTin['nhan']) ?></td>
                                        <td class="text-end"><?= $trangThai[$khoa] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="card-title">Hết hạn trong 12 tháng tới</h2>
                        <div class="card-actions text-secondary">Không tính đã thu hồi, đã quá hạn</div>
                    </div>
                    <div class="card-body">
                        <?= khungBieuDo(['huong' => 'doc', 'nhan' => nhanTrucThang($theoThang), 'so' => array_values($theoThang), 'tran' => $tran['thang']],
                            'Biểu đồ số chứng chỉ hết hạn theo từng tháng; số liệu ở bảng bên dưới.') ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm card-table">
                            <thead><tr><th>Tháng</th><th class="text-end">Số chứng chỉ</th><th class="ps-5">Tháng</th><th class="text-end">Số chứng chỉ</th></tr></thead>
                            <tbody>
                                <?php // Hai cặp cột, sáu hàng: bảng không dài gấp đôi biểu đồ bên cạnh. ?>
                                <?php $cacSo = array_values($theoThang); ?>
                                <?php for ($i = 0; $i < 6; $i++): ?>
                                    <tr>
                                        <td><?= e($nhanThang[$i]) ?></td>
                                        <td class="text-end"><?= $cacSo[$i] ?></td>
                                        <td class="ps-5"><?= e($nhanThang[$i + 6]) ?></td>
                                        <td class="text-end"><?= $cacSo[$i + 6] ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php foreach ([['Theo tổ chức cấp', 'Tổ chức cấp', $toChuc, $tran['to_chuc']], ['Theo thuật toán khoá', 'Thuật toán', $thuatToan, $tran['thuat_toan']]] as [$tieuDeThe, $tenCot, $soLieu, $tranTruc]): ?>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h2 class="card-title"><?= e($tieuDeThe) ?></h2></div>
                        <?php if ($soLieu === []): ?>
                            <div class="card-body text-center text-secondary py-4">Không có chứng chỉ nào khớp bộ lọc.</div>
                        <?php else: ?>
                            <div class="card-body">
                                <?= khungBieuDo(['huong' => 'ngang', 'nhan' => array_map('strval', array_keys($soLieu)), 'so' => array_values($soLieu), 'tran' => $tranTruc],
                                    $tieuDeThe . '; số liệu ở bảng bên dưới.') ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm card-table">
                                    <thead><tr><th><?= e($tenCot) ?></th><th class="text-end">Số chứng chỉ</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($soLieu as $ten => $so): ?>
                                            <tr><td><?= e((string) $ten) ?></td><td class="text-end"><?= $so ?></td></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
