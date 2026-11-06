<?php
// Danh sách người dùng, chỉ Admin. Nút khoá, mở khoá, xoá gửi tới nguoi_dung_thao_tac.php.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/nguoi_dung.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauAdmin($pdo);
$danhSach = danhSachNguoiDung($pdo);

$tieuDe = 'Người dùng';
$trangHienTai = 'nguoi_dung';
require __DIR__ . '/../../views/dau_trang.php';
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">Người dùng</h1>
            </div>
            <div class="col-auto">
                <a class="btn btn-primary" href="<?= e(url('admin/nguoi_dung_form.php')) ?>">Thêm người dùng</a>
            </div>
        </div>
    </div>
</div>
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Tên đăng nhập</th>
                            <th>Họ tên</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th class="w-1"><span class="visually-hidden">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSach as $dong): ?>
                            <?php $laMinh = (int) $dong['id'] === (int) $nguoi['id']; ?>
                            <tr>
                                <td class="ma-so">
                                    <?= e($dong['username']) ?>
                                    <?php if ($laMinh): ?><span class="badge bg-blue-lt ms-1">Bạn</span><?php endif; ?>
                                </td>
                                <td><?= e($dong['full_name']) ?></td>
                                <td><?= e($dong['role_name']) ?></td>
                                <td>
                                    <?php if ($dong['is_locked']): ?>
                                        <span class="badge bg-red-lt">Đã khoá</span>
                                    <?php elseif ($dong['dang_tam_chan']): ?>
                                        <span class="badge bg-yellow-lt">Tạm chặn</span>
                                    <?php else: ?>
                                        <span class="badge bg-green-lt">Hoạt động</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a class="btn btn-sm" href="<?= e(url('admin/nguoi_dung_form.php?id=' . (int) $dong['id'])) ?>">Sửa</a>
                                        <?php if (!$laMinh): ?>
                                            <form method="post" action="<?= e(url('admin/nguoi_dung_thao_tac.php')) ?>">
                                                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $dong['id'] ?>">
                                                <?php if ($dong['is_locked'] || $dong['dang_tam_chan']): ?>
                                                    <button class="btn btn-sm" name="hanh_dong" value="mo_khoa">Mở khoá</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm" name="hanh_dong" value="khoa">Khoá</button>
                                                <?php endif; ?>
                                            </form>
                                            <form method="post" action="<?= e(url('admin/nguoi_dung_thao_tac.php')) ?>"
                                                  data-xac-nhan="Xoá người dùng <?= e($dong['username']) ?>?">
                                                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $dong['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" name="hanh_dong" value="xoa">Xoá</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
