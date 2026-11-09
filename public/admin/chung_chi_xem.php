<?php
// Chi tiết một chứng chỉ, kèm lịch sử gia hạn. Nút Thu hồi, Xoá gửi tới chung_chi_thao_tac.php.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$cc = timChungChi($pdo, (int) ($_GET['id'] ?? 0));
if ($cc === null) {
    datThongBao('danger', 'Chứng chỉ không còn tồn tại.');
    chuyenHuong('admin/chung_chi.php');
}
$lichSu = lichSuGiaHan($pdo, (int) $cc['id']);
$ngayGio = fn(string $giaTri): string => date('d/m/Y H:i:s', strtotime($giaTri));

$tieuDe = 'Chứng chỉ ' . $cc['cert_code'];
$quayLai = ['nhan' => 'Danh sách chứng chỉ', 'dich' => 'admin/chung_chi.php'];
$trangHienTai = 'chung_chi';
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Thông tin chứng chỉ</h2>
                        <div class="card-actions">
                            <?php
                            $trangThaiHienThi = $cc['status'];
                            require __DIR__ . '/../../views/nhan_trang_thai.php';
                            ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Còn lại</div>
                                <div class="datagrid-content"><?= e(moTaSoNgay($cc)) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Tên chính</div>
                                <div class="datagrid-content"><?= e($cc['subject_cn']) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Tên miền quản lý</div>
                                <div class="datagrid-content"><?= e($cc['domain_name']) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Tổ chức cấp</div>
                                <div class="datagrid-content"><?= e($cc['ca_name']) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Ngày bắt đầu</div>
                                <div class="datagrid-content"><?= e($ngayGio($cc['valid_from'])) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Ngày hết hạn</div>
                                <div class="datagrid-content"><?= e($ngayGio($cc['valid_to'])) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Khoá</div>
                                <div class="datagrid-content"><?= e($cc['key_algorithm']) ?> <?= (int) $cc['key_size'] ?> bit</div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Thuật toán ký</div>
                                <div class="datagrid-content ma-so"><?= e($cc['signature_algorithm']) ?></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Người tạo</div>
                                <div class="datagrid-content"><?= e($cc['created_by_name']) ?></div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="datagrid-title">Fingerprint SHA-256</div>
                            <?php // Chữ "Chưa có", "Không có" là câu thường, không phải mã, nên không dùng font mã (.ma-so). ?>
                            <div class="text-break<?= $cc['fingerprint_sha256'] === null ? ' text-secondary' : ' ma-so' ?>"><?= $cc['fingerprint_sha256'] === null ? 'Chưa có' : e($cc['fingerprint_sha256']) ?></div>
                        </div>
                        <div class="mt-3">
                            <div class="datagrid-title">Serial</div>
                            <div class="text-break<?= $cc['serial'] === null ? ' text-secondary' : ' ma-so' ?>"><?= $cc['serial'] === null ? 'Chưa có' : e($cc['serial']) ?></div>
                        </div>
                        <div class="mt-3">
                            <div class="datagrid-title">Tên phụ (SAN)</div>
                            <div class="text-break<?= $cc['san'] === null ? ' text-secondary' : ' ma-so' ?>"><?= $cc['san'] === null ? 'Không có' : e($cc['san']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">Thao tác</h2></div>
                    <div class="card-body d-grid gap-2">
                        <?php // Đã thu hồi là trạng thái cuối: không gia hạn, không sửa (user chốt 09/11/2026), chỉ còn Xoá cho Admin. ?>
                        <?php if (!$cc['is_revoked']): ?>
                            <a class="btn btn-primary" href="<?= e(url('admin/chung_chi_gia_han.php?id=' . (int) $cc['id'])) ?>">Gia hạn</a>
                            <a class="btn" href="<?= e(url('admin/chung_chi_form.php?id=' . (int) $cc['id'])) ?>">Sửa</a>
                        <?php else: ?>
                            <p class="text-secondary small mb-0">Đã thu hồi: chỉ còn để tra cứu. Theo dõi chứng chỉ mới thì thêm mới.</p>
                        <?php endif; ?>
                        <?php if (!$cc['is_revoked']): ?>
                            <form method="post" action="<?= e(url('admin/chung_chi_thao_tac.php')) ?>" class="d-grid"
                                  data-xac-nhan="Thu hồi chứng chỉ <?= e($cc['cert_code']) ?>? Không thể hoàn tác.">
                                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $cc['id'] ?>">
                                <button class="btn btn-outline-warning" name="hanh_dong" value="thu_hoi">Thu hồi</button>
                            </form>
                        <?php endif; ?>
                        <?php if (laAdmin($nguoi)): ?>
                            <form method="post" action="<?= e(url('admin/chung_chi_thao_tac.php')) ?>" class="d-grid"
                                  data-xac-nhan="Xoá chứng chỉ <?= e($cc['cert_code']) ?> cùng cảnh báo và lịch sử gia hạn?">
                                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $cc['id'] ?>">
                                <button class="btn btn-outline-danger" name="hanh_dong" value="xoa">Xoá</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">Lịch sử gia hạn</h2></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Thời điểm</th>
                                    <th>Hạn cũ</th>
                                    <th>Hạn mới</th>
                                    <th>Fingerprint, serial cũ</th>
                                    <th>Người thực hiện</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lichSu as $dong): ?>
                                    <tr>
                                        <td><?= e($ngayGio($dong['renewed_at'])) ?></td>
                                        <td><?= e($ngayGio($dong['old_valid_to'])) ?></td>
                                        <td><?= e($ngayGio($dong['new_valid_to'])) ?></td>
                                        <td class="ma-so small text-break">
                                            <div><?= $dong['old_fingerprint'] === null ? 'Chưa có' : e($dong['old_fingerprint']) ?></div>
                                            <div class="text-secondary"><?= $dong['old_serial'] === null ? 'Chưa có' : e($dong['old_serial']) ?></div>
                                        </td>
                                        <td><?= e($dong['renewed_by_name']) ?></td>
                                        <td><?= e($dong['note']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($lichSu === []): ?>
                                    <tr><td colspan="6" class="text-center text-secondary py-4">Chưa gia hạn lần nào.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
