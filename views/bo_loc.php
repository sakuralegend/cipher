<?php
// Hàng nút trạng thái, ô tìm kiếm, bộ lọc thu gọn; dùng chung cho danh sách và thống kê.
// Cần: $trangLoc (trang đang mở), $loc, $sapXep, $soTheoTrangThai, $cacToChuc, $cacTenMien, $cacMayChu.
// Nút Xuất CSV, Bản in nằm ở dòng tiêu đề (nutXuatBaoCao(), tieu_de_trang.php), mang theo đúng bộ lọc này.
$coGiaTri = fn(string $giaTri): bool => $giaTri !== '';
$locDangDung = array_filter($loc, $coGiaTri);
$sapXepDangDung = $sapXep['sap_xep'] === '' ? [] : $sapXep;

// Ô "Sắp xếp theo" chỉ ở Danh sách (Thống kê không có danh sách để xếp). Giá trị "cột.chiều" như CACH_SAP_XEP; đang xếp
// theo một cột không có trong danh sách chọn (bấm tiêu đề cột Mã, Tên...) thì thêm một lựa chọn cho cách đó, để bấm Tìm
// không làm mất cách xếp đang dùng.
$coChonSapXep = $trangLoc === 'admin/chung_chi.php';
$xepDangChon = $sapXepDangDung === [] ? '' : $sapXep['sap_xep'] . '.' . $sapXep['chieu'];
$cacCachXep = CACH_SAP_XEP + ($xepDangChon !== '' && !isset(CACH_SAP_XEP[$xepDangChon]) ? [$xepDangChon => 'Theo cột đang chọn'] : []);

// Các ô nằm trong phần thu gọn. Đang lọc bằng ô nào trong đó (hoặc đang chọn cách xếp) thì mở sẵn, để không quên.
$soLocTrongPhanGon = count(array_intersect_key($locDangDung, array_flip(['het_han_tu', 'het_han_den', 'ca_id', 'domain_id', 'server_id'])))
    + ($coChonSapXep && $xepDangChon !== '' ? 1 : 0);
?>
<div class="d-flex flex-wrap gap-2 mb-3 d-print-none">
    <a class="btn btn-sm <?= $loc['trang_thai'] === '' ? 'btn-primary' : '' ?>"
       <?= $loc['trang_thai'] === '' ? 'aria-current="true"' : '' ?>
       href="<?= e(diaChiCoLoc($trangLoc, $loc, $sapXep, ['trang_thai' => ''])) ?>">Tất cả <span class="ms-1"><?= array_sum($soTheoTrangThai) ?></span></a>
    <?php foreach (TRANG_THAI as $khoa => $thongTin): ?>
        <a class="btn btn-sm <?= $loc['trang_thai'] === $khoa ? 'btn-primary' : '' ?>"
           <?= $loc['trang_thai'] === $khoa ? 'aria-current="true"' : '' ?>
           href="<?= e(diaChiCoLoc($trangLoc, $loc, $sapXep, ['trang_thai' => $khoa])) ?>">
            <span class="badge bg-<?= e($thongTin['mau']) ?> badge-empty me-2"></span><?= e($thongTin['nhan']) ?>
            <span class="ms-1"><?= $soTheoTrangThai[$khoa] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<form class="card mb-3 d-print-none" method="get" action="<?= e(url($trangLoc)) ?>">
    <?php // Bấm Tìm vẫn giữ nút trạng thái và cách xếp đang chọn (ở Danh sách, cách xếp đi qua ô "Sắp xếp theo"). ?>
    <?php foreach (array_filter(['trang_thai' => $loc['trang_thai'], ...($coChonSapXep ? [] : $sapXepDangDung)], $coGiaTri) as $khoa => $giaTri): ?>
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
                <a class="btn" href="<?= e(url($trangLoc)) ?>">Xoá lọc</a>
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
                <?php if ($coChonSapXep): ?>
                    <?php // Rộng hơn các ô khác ở màn lớn: nhãn dài nhất ("Hết hạn gần nhất trước") bị cắt khi chia đều sáu ô. ?>
                    <div class="col-12 col-sm-4 col-lg-3">
                        <label class="form-label" for="xep">Sắp xếp theo</label>
                        <select class="form-select" id="xep" name="xep">
                            <?php foreach ($cacCachXep as $giaTri => $nhan): ?>
                                <option value="<?= e($giaTri) ?>"<?= $giaTri === $xepDangChon ? ' selected' : '' ?>><?= e($nhan) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
