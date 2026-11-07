<?php
// Bản in báo cáo theo bộ lọc và cách xếp đang chọn. In hoặc lưu PDF bằng lệnh In của trình duyệt.
// Chỉ in bảng số, không in biểu đồ, để bản in gọn và không phụ thuộc thời điểm vẽ.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$loc = docBoLoc($_GET);
$sapXep = docSapXep($_GET);
$danhSach = tatCaChungChi($pdo, $loc, $sapXep);
$trangThai = demTheoTrangThai($pdo, $loc, true);

// Đổi bộ lọc thành chữ, để người đọc bản giấy biết báo cáo này gồm những gì.
$tenTheoId = fn(array $hang, string $cot, string $id): string => (string) (array_column($hang, $cot, 'id')[$id] ?? $id);
$moTaLoc = [];
if ($loc['tu_khoa'] !== '') {
    $moTaLoc[] = 'Từ khoá: ' . $loc['tu_khoa'];
}
if ($loc['trang_thai'] !== '') {
    $moTaLoc[] = 'Trạng thái: ' . TRANG_THAI[$loc['trang_thai']]['nhan'];
}
if ($loc['ca_id'] !== '') {
    $moTaLoc[] = 'Tổ chức cấp: ' . $tenTheoId(danhSachToChuc($pdo), 'name', $loc['ca_id']);
}
if ($loc['domain_id'] !== '') {
    $moTaLoc[] = 'Tên miền: ' . $tenTheoId(danhSachTenMien($pdo), 'domain_name', $loc['domain_id']);
}
if ($loc['server_id'] !== '') {
    $moTaLoc[] = 'Máy chủ: ' . $tenTheoId(danhSachMayChu($pdo), 'hostname', $loc['server_id']);
}
if ($loc['het_han_tu'] !== '') {
    $moTaLoc[] = 'Hết hạn từ ' . date('d/m/Y', strtotime($loc['het_han_tu']));
}
if ($loc['het_han_den'] !== '') {
    $moTaLoc[] = 'Hết hạn đến ' . date('d/m/Y', strtotime($loc['het_han_den']));
}

$tieuDe = 'Báo cáo chứng chỉ số';
$trangHienTai = 'thong_ke';
require __DIR__ . '/../../views/dau_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="d-flex justify-content-end gap-2 mb-3 d-print-none">
            <a class="btn" href="<?= e(diaChiCoLoc('admin/thong_ke.php', $loc)) ?>">Quay lại thống kê</a>
            <button type="button" class="btn btn-primary" data-in>In hoặc lưu PDF</button>
        </div>
        <h1>Báo cáo chứng chỉ số</h1>
        <p class="mb-1">Thời điểm xuất: <?= e(date('d/m/Y H:i')) ?> · Người xuất: <?= e($nguoi['full_name']) ?></p>
        <p>Phạm vi: <?= $moTaLoc === [] ? 'Toàn bộ chứng chỉ' : e(implode(' · ', $moTaLoc)) ?></p>

        <h2 class="mt-4">Tổng hợp theo trạng thái</h2>
        <div class="table-responsive">
            <table class="table table-sm bang-in w-auto">
                <thead>
                    <tr>
                        <?php foreach (TRANG_THAI as $thongTin): ?>
                            <th><?= e($thongTin['nhan']) ?></th>
                        <?php endforeach; ?>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($trangThai as $so): ?>
                            <td><?= $so ?></td>
                        <?php endforeach; ?>
                        <td><strong><?= array_sum($trangThai) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="mt-4">Danh sách chứng chỉ</h2>
        <div class="table-responsive">
            <table class="table table-sm bang-in">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên chính</th>
                        <th>Tổ chức cấp</th>
                        <th>Máy chủ</th>
                        <th>Khoá</th>
                        <th>Hết hạn</th>
                        <th>Còn lại</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($danhSach as $dong): ?>
                        <tr>
                            <td class="text-nowrap"><?= e($dong['cert_code']) ?></td>
                            <td class="text-break"><?= e($dong['subject_cn']) ?></td>
                            <td><?= e($dong['ca_name']) ?></td>
                            <td><?= e($dong['server_name']) ?></td>
                            <td class="text-nowrap"><?= e($dong['key_algorithm']) ?> <?= (int) $dong['key_size'] ?></td>
                            <td class="text-nowrap"><?= e(date('d/m/Y', strtotime($dong['valid_to']))) ?></td>
                            <td class="text-nowrap"><?= e(moTaSoNgay($dong)) ?></td>
                            <td class="text-nowrap"><?= e(TRANG_THAI[$dong['status']]['nhan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($danhSach === []): ?>
                        <tr><td colspan="8" class="text-center text-secondary py-4">Không có chứng chỉ nào khớp bộ lọc.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
