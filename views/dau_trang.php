<?php
// Đầu trang dùng chung. Trang gọi file này phải có $tieuDe.
// Có $nguoi thì hiện thanh menu; không có (trang đăng nhập) thì chỉ hiện nút đổi giao diện.
$nguoi ??= null;

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
<?php if ($nguoi === null): ?>
    <div class="position-absolute top-0 end-0 p-3"><?= $nutGiaoDien ?></div>
<?php else: ?>
    <div class="page">
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <a class="navbar-brand" href="<?= e(url('admin/index.php')) ?>">Quản lý chứng chỉ số</a>
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
                            <form method="post" action="<?= e(url('logout.php')) ?>">
                                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                                <button type="submit" class="dropdown-item">Đăng xuất</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <div class="page-wrapper">
<?php endif; ?>
