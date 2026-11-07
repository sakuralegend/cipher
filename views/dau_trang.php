<?php
// Đầu trang dùng chung. Trang gọi file này phải có $tieuDe.
// $nguoi:          có thì hiện thanh menu quản trị.
// $trangHienTai:   tên mục menu đang mở, để tô đậm ('dashboard', 'may_chu', 'nguoi_dung'...).
// $coThanhRieng:   trang tự có thanh trên cùng (landing), nên không hiện nút giao diện nổi.
$nguoi ??= null;
$trangHienTai ??= '';
$coThanhRieng ??= false;

// Số cảnh báo trên menu chỉ đếm, không tạo: việc tạo chạy ở Dashboard và trang Cảnh báo (docs/SPEC.md mục 5),
// để mỗi lần mở trang không phải ghi CSDL. Trang quản trị nào cũng có sẵn $pdo.
$soCanhBaoMenu = 0;
if ($nguoi !== null) {
    require_once __DIR__ . '/../src/chung_chi.php';
    $soCanhBaoMenu = demCanhBaoMoi($pdo);
}
$trongDanhMuc = in_array($trangHienTai, ['may_chu', 'ten_mien', 'to_chuc_cap'], true);

// Biểu tượng trong menu thả xuống (bộ Tabler Icons, MIT): nét đường của từng hình, vẽ inline để không tải thêm file.
const BIEU_TUONG_MENU = [
    'may_chu' => '<rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 8v.01M7 17v.01"/>',
    'ten_mien' => '<circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 0 0 0 18M12.5 3a17 17 0 0 1 0 18"/>',
    'to_chuc_cap' => '<path d="M12 3l8 4.5v4.5c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V7.5z"/><path d="M9 12l2 2 4-4"/>',
    'tai_khoan' => '<circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/>',
    'dang_xuat' => '<path d="M14 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2"/><path d="M9 12h12M18 9l3 3-3 3"/>',
];

function bieuTuongMenu(string $ten): string
{
    return '<svg class="icon dropdown-item-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . BIEU_TUONG_MENU[$ten] . '</svg>';
}

$nutGiaoDien = <<<'HTML'
<button type="button" class="nav-link px-2 bg-transparent border-0" data-doi-giao-dien
        title="Đổi giao diện sáng/tối" aria-label="Đổi giao diện sáng/tối">
    <svg class="icon hide-theme-dark" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/></svg>
    <svg class="icon hide-theme-light" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/></svg>
</button>
HTML;
?>
<!doctype html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tieuDe) ?> · Quản lý chứng chỉ số</title>
    <link rel="stylesheet" href="<?= e(url('assets/tabler/tabler.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/inter/inter.css')) ?>">
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
                <?php // Tên hệ thống dẫn về landing; về Dashboard đã có mục Dashboard ngay bên cạnh. ?>
                <a class="navbar-brand me-3 ten-he-thong-qt" href="<?= e(url('index.php')) ?>">Quản lý chứng chỉ số</a>
                <div class="navbar-nav flex-row order-md-last align-items-center thanh-tai-khoan">
                    <?= $nutGiaoDien ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nut-tai-khoan dropdown-toggle" data-bs-toggle="dropdown" aria-label="Mở menu tài khoản">
                            <span class="avatar avatar-sm"><?= e(mb_substr($nguoi['full_name'], 0, 1)) ?></span>
                            <?php // Màn hẹp chỉ hiện chữ cái đầu, để đầu trang gọn trên một hàng. ?>
                            <span class="d-none d-md-block">
                                <span class="d-block nut-tai-khoan-ten"><?= e($nguoi['full_name']) ?></span>
                                <span class="d-block small text-secondary"><?= e($nguoi['role_name']) ?></span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="<?= e(url('admin/tai_khoan.php')) ?>"><?= bieuTuongMenu('tai_khoan') ?>Tài khoản của tôi</a>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="<?= e(url('logout.php')) ?>">
                                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                                <button type="submit" class="dropdown-item"><?= bieuTuongMenu('dang_xuat') ?>Đăng xuất</button>
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
                        <li class="nav-item <?= $trangHienTai === 'canh_bao' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= e(url('admin/canh_bao.php')) ?>">
                                Cảnh báo
                                <?php if ($soCanhBaoMenu > 0): ?>
                                    <span class="badge so-canh-bao ms-2"><?= $soCanhBaoMenu ?><span class="visually-hidden"> cảnh báo mới</span></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item <?= $trangHienTai === 'thong_ke' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= e(url('admin/thong_ke.php')) ?>">Thống kê</a>
                        </li>
                        <li class="nav-item dropdown <?= $trongDanhMuc ? 'active' : '' ?>">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">Danh mục</a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item <?= $trangHienTai === 'may_chu' ? 'active' : '' ?>" href="<?= e(url('admin/may_chu.php')) ?>"><?= bieuTuongMenu('may_chu') ?>Máy chủ</a>
                                <a class="dropdown-item <?= $trangHienTai === 'ten_mien' ? 'active' : '' ?>" href="<?= e(url('admin/ten_mien.php')) ?>"><?= bieuTuongMenu('ten_mien') ?>Tên miền</a>
                                <a class="dropdown-item <?= $trangHienTai === 'to_chuc_cap' ? 'active' : '' ?>" href="<?= e(url('admin/to_chuc_cap.php')) ?>"><?= bieuTuongMenu('to_chuc_cap') ?>Tổ chức cấp</a>
                            </div>
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
