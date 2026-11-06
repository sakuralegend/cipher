<?php
require __DIR__ . '/../src/khoi_dong.php';

$pdo = ketNoiCsdl();

if (nguoiDungHienTai($pdo) !== null) {
    chuyenHuong('admin/index.php');
}

$loi = null;
$ten = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $ten = trim(chuoiPost('username'));
    $ketQua = dangNhap($pdo, $ten, chuoiPost('password'));

    if (isset($ketQua['id'])) {
        ghiNhanDangNhap($ketQua['id']);
        chuyenHuong('admin/index.php');
    }
    $loi = $ketQua['loi'];
}

$tieuDe = 'Đăng nhập';
require __DIR__ . '/../views/dau_trang.php';
?>
<main class="page page-center">
    <div class="container container-tight py-4">
        <h1 class="h2 text-center mb-4">Quản lý chứng chỉ số</h1>
        <div class="card card-md">
            <div class="card-body">
                <h2 class="h3 text-center mb-4">Đăng nhập</h2>
                <?php if ($loi !== null): ?>
                    <div class="alert alert-danger" role="alert"><?= e($loi) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= e(url('login.php')) ?>">
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="username">Tên đăng nhập</label>
                        <input class="form-control" id="username" name="username" value="<?= e($ten) ?>"
                               autocomplete="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <input class="form-control" id="password" name="password" type="password"
                               autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                </form>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../views/cuoi_trang.php'; ?>
