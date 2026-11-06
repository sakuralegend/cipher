<?php
// Đầu trang dùng chung. Trang gọi file này phải có $tieuDe.
// $nguoi:          có thì hiện thanh menu quản trị.
// $trangHienTai:   tên mục menu đang mở, để tô đậm ('dashboard', 'may_chu', 'nguoi_dung'...).
// $coThanhRieng:   trang tự có thanh trên cùng (landing), nên không hiện nút giao diện nổi.
$nguoi ??= null;
$trangHienTai ??= '';
$coThanhRieng ??= false;

$nutGiaoDien = <<<'HTML'
<button type="button" class="nav-link px-2 bg-transparent border-0" data-doi-giao-dien
        title="Đổi giao diện sáng/tối" aria-label="Đổi giao diện sáng/tối">
    <svg class="icon hide-theme-dark" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/></svg>
    <svg class="icon hide-theme-light" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/></svg>
</button>
HTML;
?>
<!doctype html>
<html lang="vi" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tieuDe) ?> · Quản lý chứng chỉ số</title>
    <link rel="stylesheet" href="<?= e(url('assets/tabler/tabler.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/app.css')) ?>">
    <!-- Đặt trong head và không defer: đổi màu trước khi vẽ trang, để không loé màu sáng. -->
    <script src="<?= e(url('assets/tabler/tabler-theme.min.js')) ?>"></script>
</head>
<body>
<?php if ($nguoi === null && !$coThanhRieng): ?>
    <div class="position-absolute top-0 end-0 p-3"><?= $nutGiaoDien ?></div>
<?php elseif ($nguoi !== null): ?>
    <div class="page">
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu-chinh"
                        aria-controls="menu-chinh" aria-expanded="false" aria-label="Mở menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand me-3" href="<?= e(url('admin/index.php')) ?>">Quản lý chứng chỉ số</a>
                <div class="navbar-nav flex-row order-md-last align-items-center">
                    <?= $nutGiaoDien ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link px-2" data-bs-toggle="dropdown" aria-label="Mở menu tài khoản">
                            <span>
                                <span class="d-block"><?= e($nguoi['full_name']) ?></span>
                                <span class="d-block small text-secondary"><?= e($nguoi['role_name']) ?></span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="<?= e(url('admin/tai_khoan.php')) ?>">Tài khoản của tôi</a>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="<?= e(url('logout.php')) ?>">
                                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                                <button type="submit" class="dropdown-item">Đăng xuất</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="collapse navbar-collapse" id="menu-chinh">
                    <ul class="navbar-nav">
                        <li class="nav-item <?= $trangHienTai === 'dashboard' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= e(url('admin/index.php')) ?>">Dashboard</a>
                        </li>
                        <li class="nav-item <?= $trangHienTai === 'chung_chi' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= e(url('admin/chung_chi.php')) ?>">Chứng chỉ</a>
                        </li>
                        <li class="nav-item <?= $trangHienTai === 'may_chu' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= e(url('admin/may_chu.php')) ?>">Máy chủ</a>
                        </li>
                        <li class="nav-item <?= $trangHienTai === 'ten_mien' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= e(url('admin/ten_mien.php')) ?>">Tên miền</a>
                        </li>
                        <li class="nav-item <?= $trangHienTai === 'to_chuc_cap' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= e(url('admin/to_chuc_cap.php')) ?>">Tổ chức cấp</a>
                        </li>
                        <?php if (laAdmin($nguoi)): ?>
                            <li class="nav-item <?= $trangHienTai === 'nguoi_dung' ? 'active' : '' ?>">
                                <a class="nav-link" href="<?= e(url('admin/nguoi_dung.php')) ?>">Người dùng</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </header>
        <div class="page-wrapper">
            <?php $thongBao = layThongBao(); ?>
            <?php if ($thongBao !== null): ?>
                <div class="container-xl pt-3">
                    <div class="alert alert-<?= e($thongBao['loai']) ?> mb-0" role="alert"><?= e($thongBao['noi_dung']) ?></div>
                </div>
            <?php endif; ?>
<?php endif; ?>
