<?php
// Mỗi người tự sửa họ tên và đổi mật khẩu của chính mình. Không nhận id từ form,
// nên không thể sửa tài khoản của người khác qua trang này.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/nguoi_dung.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);
$id = (int) $nguoi['id'];
$loi = [];
$hoTen = $nguoi['full_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();

    if (chuoiPost('hanh_dong') === 'doi_mat_khau') {
        $loi = doiMatKhau($pdo, $id, chuoiPost('password_cu'), chuoiPost('password'), chuoiPost('password2'));
        $thanhCong = 'Đã đổi mật khẩu.';
        if ($loi === []) {
            // Đổi mã phiên, để mã phiên lộ từ trước không dùng tiếp được sau khi đổi mật khẩu.
            session_regenerate_id(true);
        }
    } else {
        $hoTen = trim(chuoiPost('full_name'));
        $loi = suaHoTen($pdo, $id, $hoTen);
        $thanhCong = 'Đã lưu họ tên.';
    }

    if ($loi === []) {
        datThongBao('success', $thanhCong);
        chuyenHuong('admin/tai_khoan.php');
    }
}

$tieuDe = 'Tài khoản của tôi';
require __DIR__ . '/../../views/dau_trang.php';
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <h1 class="page-title">Tài khoản của tôi</h1>
    </div>
</div>
<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-lg-6">
                <form class="card" method="post" action="<?= e(url('admin/tai_khoan.php')) ?>" data-kiem-tra novalidate>
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <input type="hidden" name="hanh_dong" value="ho_ten">
                    <div class="card-header"><h2 class="card-title">Thông tin</h2></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập</label>
                            <div class="form-control-plaintext ma-so"><?= e($nguoi['username']) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vai trò</label>
                            <div class="form-control-plaintext"><?= e($nguoi['role_name']) ?></div>
                        </div>
                        <div>
                            <label class="form-label required" for="full_name">Họ tên</label>
                            <input class="form-control <?= isset($loi['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name"
                                   value="<?= e($hoTen) ?>" required maxlength="100">
                            <div class="invalid-feedback"><?= e($loi['full_name'] ?? 'Họ tên là bắt buộc.') ?></div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Lưu họ tên</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-6">
                <form class="card" method="post" action="<?= e(url('admin/tai_khoan.php')) ?>" data-kiem-tra novalidate>
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <input type="hidden" name="hanh_dong" value="doi_mat_khau">
                    <div class="card-header"><h2 class="card-title">Đổi mật khẩu</h2></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required" for="password_cu">Mật khẩu hiện tại</label>
                            <input class="form-control <?= isset($loi['password_cu']) ? 'is-invalid' : '' ?>" id="password_cu" name="password_cu"
                                   type="password" required autocomplete="current-password">
                            <div class="invalid-feedback"><?= e($loi['password_cu'] ?? 'Hãy nhập mật khẩu hiện tại.') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required" for="password">Mật khẩu mới</label>
                            <input class="form-control <?= isset($loi['password']) ? 'is-invalid' : '' ?>" id="password" name="password"
                                   type="password" required minlength="8" pattern="<?= e(MAU_MAT_KHAU) ?>" autocomplete="new-password">
                            <div class="invalid-feedback"><?= e($loi['password'] ?? LUAT_MAT_KHAU) ?></div>
                        </div>
                        <div>
                            <label class="form-label required" for="password2">Nhập lại mật khẩu mới</label>
                            <input class="form-control" id="password2" name="password2" type="password" required
                                   data-giong="password" autocomplete="new-password">
                            <div class="invalid-feedback">Hai lần nhập mật khẩu không giống nhau.</div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
