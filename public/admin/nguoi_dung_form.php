<?php
// Form thêm (không có ?id=) và sửa (có ?id=) người dùng, chỉ Admin.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/nguoi_dung.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauAdmin($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$laMinh = $id === (int) $nguoi['id'];
$loi = [];

if ($id !== null) {
    $hienTai = timNguoiDung($pdo, $id);
    if ($hienTai === null) {
        datThongBao('danger', 'Người dùng không còn tồn tại.');
        chuyenHuong('admin/nguoi_dung.php');
    }
    $giaTri = $hienTai;
} else {
    $giaTri = ['username' => '', 'full_name' => '', 'role_id' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $duLieu = [
        'username' => trim(chuoiPost('username')),
        'full_name' => trim(chuoiPost('full_name')),
        'role_id' => (int) chuoiPost('role_id'),
        'password' => chuoiPost('password'),
        'password2' => chuoiPost('password2'),
    ];

    $loi = $id === null
        ? themNguoiDung($pdo, $duLieu)
        : suaNguoiDung($pdo, $id, $duLieu, (int) $nguoi['id']);

    if ($loi === []) {
        datThongBao('success', $id === null ? 'Đã thêm người dùng.' : 'Đã lưu thay đổi.');
        chuyenHuong('admin/nguoi_dung.php');
    }
    // Có lỗi thì giữ lại những gì đã nhập, trừ mật khẩu.
    $giaTri = $duLieu;
}

$vaiTro = danhSachVaiTro($pdo);
$hanhDong = 'admin/nguoi_dung_form.php' . ($id !== null ? '?id=' . $id : '');
$tieuDe = $id === null ? 'Thêm người dùng' : 'Sửa người dùng';
$trangHienTai = 'nguoi_dung';
require __DIR__ . '/../../views/dau_trang.php';
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <h1 class="page-title"><?= e($tieuDe) ?></h1>
    </div>
</div>
<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-lg-7">
                <!-- novalidate: tắt bong bóng lỗi của trình duyệt, để app.js hiện lỗi tiếng Việt ngay dưới từng ô. -->
                <form class="card" method="post" action="<?= e(url($hanhDong)) ?>" data-kiem-tra novalidate>
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required" for="username">Tên đăng nhập</label>
                            <input class="form-control ma-so <?= isset($loi['username']) ? 'is-invalid' : '' ?>" id="username" name="username"
                                   value="<?= e($giaTri['username']) ?>" required pattern="[A-Za-z0-9._\-]{3,50}" maxlength="50" autocomplete="off">
                            <div class="invalid-feedback"><?= e($loi['username'] ?? 'Gồm 3–50 ký tự: chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required" for="full_name">Họ tên</label>
                            <input class="form-control <?= isset($loi['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name"
                                   value="<?= e($giaTri['full_name']) ?>" required maxlength="100" autocomplete="off">
                            <div class="invalid-feedback"><?= e($loi['full_name'] ?? 'Họ tên là bắt buộc.') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required" for="role_id">Vai trò</label>
                            <?php if ($laMinh): ?>
                                <!-- Ô bị vô hiệu không được gửi đi, nên cần ô ẩn; PHP vẫn kiểm tra lại quy tắc này. -->
                                <input type="hidden" name="role_id" value="<?= (int) $giaTri['role_id'] ?>">
                            <?php endif; ?>
                            <select class="form-select <?= isset($loi['role_id']) ? 'is-invalid' : '' ?>" id="role_id"
                                    <?= $laMinh ? 'disabled' : 'name="role_id" required' ?>>
                                <option value="">Chọn vai trò</option>
                                <?php foreach ($vaiTro as $vt): ?>
                                    <option value="<?= (int) $vt['id'] ?>" <?= (int) $vt['id'] === (int) $giaTri['role_id'] ? 'selected' : '' ?>>
                                        <?= e($vt['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e($loi['role_id'] ?? 'Hãy chọn vai trò.') ?></div>
                            <?php if ($laMinh): ?>
                                <small class="form-hint">Bạn không thể tự đổi vai trò của mình.</small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label <?= $id === null ? 'required' : '' ?>" for="password">
                                <?= $id === null ? 'Mật khẩu' : 'Đặt lại mật khẩu' ?>
                            </label>
                            <input class="form-control <?= isset($loi['password']) ? 'is-invalid' : '' ?>" id="password" name="password"
                                   type="password" minlength="8" autocomplete="new-password" <?= $id === null ? 'required' : '' ?>>
                            <div class="invalid-feedback"><?= e($loi['password'] ?? 'Mật khẩu phải có ít nhất 8 ký tự.') ?></div>
                            <?php if ($id !== null): ?>
                                <small class="form-hint">Để trống nếu giữ nguyên mật khẩu hiện tại.</small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="form-label <?= $id === null ? 'required' : '' ?>" for="password2">Nhập lại mật khẩu</label>
                            <input class="form-control" id="password2" name="password2" type="password"
                                   data-giong="password" autocomplete="new-password" <?= $id === null ? 'required' : '' ?>>
                            <div class="invalid-feedback">Hai lần nhập mật khẩu không giống nhau.</div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a class="btn" href="<?= e(url('admin/nguoi_dung.php')) ?>">Huỷ</a>
                        <button type="submit" class="btn btn-primary"><?= $id === null ? 'Thêm người dùng' : 'Lưu thay đổi' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
