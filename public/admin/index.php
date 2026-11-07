<?php
// Dashboard: số chứng chỉ theo trạng thái, các chứng chỉ cần xử lý ngay, và hai biểu đồ nhỏ dùng chung với Thống kê.
// Số cảnh báo mới không có ô riêng: con số đó đã nằm cạnh mục Cảnh báo trên menu (review 08/11/2026).
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

// Cảnh báo được tạo khi có người xem, không dùng lịch chạy nền (docs/SPEC.md mục 5).
taoCanhBao($pdo);
$soTheoTrangThai = demTheoTrangThai($pdo);
$canXuLy = chungChiSapHetHan($pdo);
$theoThang = hetHanTheoThang($pdo);

$tieuDe = 'Dashboard';
$trangHienTai = 'dashboard';
$coBieuDo = true;
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards mb-3">
            <?php // 6 ô: 3×2 trên điện thoại và máy tính bảng, một hàng trên máy tính. ?>
            <div class="col-6 col-md-4 col-xl-2">
                <a class="card card-link the-so" href="<?= e(url('admin/chung_chi.php')) ?>">
                    <div class="card-body">
                        <div class="text-secondary">Tổng chứng chỉ</div>
                        <div class="the-so-gia-tri"><?= array_sum($soTheoTrangThai) ?></div>
                    </div>
                </a>
            </div>
            <?php foreach (TRANG_THAI as $khoa => $thongTin): ?>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="card card-link the-so" href="<?= e(url('admin/chung_chi.php?trang_thai=' . $khoa)) ?>">
                        <div class="card-body">
                            <div class="text-secondary text-nowrap">
                                <?php // Chỉ chấm "Còn hiệu lực" có vòng sáng lan ra, như đèn "đang chạy" (CSS .cham-song). ?>
                                <span class="badge bg-<?= e($thongTin['mau']) ?> badge-empty me-2<?= $khoa === 'valid' ? ' cham-song' : '' ?>"></span><?= e($thongTin['nhan']) ?>
                            </div>
                            <div class="the-so-gia-tri"><?= $soTheoTrangThai[$khoa] ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row row-cards">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="card-title">Cần xử lý ngay</h2>
                        <div class="card-actions">
                            <a href="<?= e(url('admin/chung_chi.php?trang_thai=expiring')) ?>">Xem tất cả <?= $soTheoTrangThai['expiring'] ?> chứng chỉ sắp hết hạn</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Mã</th>
                                    <th>Tên chính</th>
                                    <th class="d-none d-md-table-cell">Hết hạn</th>
                                    <th>Còn lại</th>
                                    <th class="w-1"><span class="visually-hidden">Thao tác</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($canXuLy as $dong): ?>
                                    <tr>
                                        <td class="ma-so text-nowrap">
                                            <a href="<?= e(url('admin/chung_chi_xem.php?id=' . (int) $dong['id'])) ?>"><?= e($dong['cert_code']) ?></a>
                                        </td>
                                        <td><?= tenMienNgatDong($dong['subject_cn']) ?></td>
                                        <td class="d-none d-md-table-cell"><?= e(date('d/m/Y', strtotime($dong['valid_to']))) ?></td>
                                        <td class="text-nowrap"><?= e(moTaSoNgay($dong)) ?></td>
                                        <td>
                                            <a class="btn btn-sm" href="<?= e(url('admin/chung_chi_gia_han.php?id=' . (int) $dong['id'])) ?>">Gia hạn</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($canXuLy === []): ?>
                                    <tr><td colspan="5" class="text-center text-secondary py-4">Không có chứng chỉ nào sắp hết hạn.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php // Hai biểu đồ nhỏ, số liệu đầy đủ kèm bảng ở trang Thống kê. ?>
            <div class="col-xl-5">
                <div class="row row-cards">
                    <div class="col-md-6 col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Theo trạng thái</h2>
                                <div class="card-actions"><a href="<?= e(url('admin/thong_ke.php')) ?>">Thống kê</a></div>
                            </div>
                            <div class="card-body">
                                <?= khungBieuDo([
                                    'huong' => 'ngang',
                                    'nhan' => array_values(array_column(TRANG_THAI, 'nhan')),
                                    'so' => array_values($soTheoTrangThai),
                                    'mau' => array_values(array_column(TRANG_THAI, 'mau')),
                                ], 'Biểu đồ số chứng chỉ theo trạng thái; số liệu ở các ô phía trên.', 'khung-bieu-do-nho') ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Hết hạn trong 12 tháng tới</h2>
                            </div>
                            <div class="card-body">
                                <?= khungBieuDo(['huong' => 'doc', 'nhan' => nhanTrucThang($theoThang), 'so' => array_values($theoThang)],
                                    'Biểu đồ số chứng chỉ hết hạn theo từng tháng; số liệu ở trang Thống kê.', 'khung-bieu-do-nho') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
