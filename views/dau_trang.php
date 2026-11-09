<?php
// Đầu trang dùng chung. Trang gọi file này phải có $tieuDe.
// $nguoi:          có thì hiện menu quản trị.
// $trangHienTai:   khoá của mục menu đang mở, để tô sáng ('dashboard', 'may_chu', 'nguoi_dung'...).
// $coThanhRieng:   trang tự đặt nút giao diện (landing, đăng nhập), nên không hiện nút giao diện nổi.
// $tieuDeTab:      chữ trên tab trình duyệt; bỏ trống thì là "$tieuDe | Cipher Cert".
$nguoi ??= null;
$trangHienTai ??= '';
$coThanhRieng ??= false;
// $coChuyenCanh:   bật chuyển cảnh giữa các trang có cờ này (landing, đăng nhập): trang cũ mờ đi, trang mới hiện lên.
$coChuyenCanh ??= false;

// Tab trình duyệt: "Tên trang | Cipher Cert" (user chốt 08/11/2026), mở nhiều tab vẫn biết tab nào là trang nào.
$tieuDeTab ??= $tieuDe . ' | ' . TEN_THUONG_HIEU;

// Số cảnh báo trên menu chỉ đếm, không tạo: việc tạo chạy ở Dashboard và trang Cảnh báo (xem admin/index.php),
// để mỗi lần mở trang không phải ghi CSDL. Trang quản trị nào cũng có sẵn $pdo.
$soCanhBaoMenu = 0;
if ($nguoi !== null) {
    require_once __DIR__ . '/../src/chung_chi.php';
    $soCanhBaoMenu = demCanhBaoMoi($pdo);
}

// Biểu tượng (bộ Tabler Icons, MIT): nét đường của từng hình, vẽ inline để không tải thêm file.
// Khoá trùng khoá trang trong $nhomMenu bên dưới.
const BIEU_TUONG_MENU = [
    'dashboard' => '<rect x="4" y="4" width="6" height="8" rx="1"/><rect x="4" y="16" width="6" height="4" rx="1"/><rect x="14" y="12" width="6" height="8" rx="1"/><rect x="14" y="4" width="6" height="4" rx="1"/>',
    'canh_bao' => '<path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3H4a4 4 0 0 0 2-3v-3a7 7 0 0 1 4-6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/>',
    'chung_chi' => '<circle cx="15" cy="15" r="3"/><path d="M13 17.5V22l2-1.5 2 1.5v-4.5"/><path d="M10 19H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-1 1.73"/><path d="M6 9h12M6 12h3M6 15h2"/>',
    'thong_ke' => '<rect x="3" y="12" width="6" height="8" rx="1"/><rect x="9" y="8" width="6" height="12" rx="1"/><rect x="15" y="4" width="6" height="16" rx="1"/><path d="M4 20h14"/>',
    'may_chu' => '<rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 8v.01M7 17v.01"/>',
    'ten_mien' => '<circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 0 0 0 18M12.5 3a17 17 0 0 1 0 18"/>',
    'to_chuc_cap' => '<path d="M12 3l8 4.5v4.5c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V7.5z"/><path d="M9 12l2 2 4-4"/>',
    'nguoi_dung' => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75M21 21v-2a4 4 0 0 0-3-3.85"/>',
    'nhat_ky' => '<path d="M12 8v4l2 2"/><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"/>',
    'tai_khoan' => '<circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/>',
    'giao_dien_toi' => '<path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/>',
    'giao_dien_sang' => '<circle cx="12" cy="12" r="4"/><path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/>',
    'dang_xuat' => '<path d="M14 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2"/><path d="M9 12h12M18 9l3 3-3 3"/>',
];

function bieuTuongMenu(string $ten, string $lop = 'dropdown-item-icon'): string
{
    return '<svg class="icon ' . $lop . '" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . BIEU_TUONG_MENU[$ten] . '</svg>';
}

// Menu dọc chia nhóm theo việc (review 08/11/2026). Mỗi mục: [khoá trang, địa chỉ, nhãn].
$nhomMenu = [
    'Tổng quan' => [['dashboard', 'admin/index.php', 'Dashboard'], ['canh_bao', 'admin/canh_bao.php', 'Cảnh báo']],
    'Chứng chỉ' => [['chung_chi', 'admin/chung_chi.php', 'Danh sách chứng chỉ'], ['thong_ke', 'admin/thong_ke.php', 'Thống kê và báo cáo']],
    'Hạ tầng' => [['ten_mien', 'admin/ten_mien.php', 'Tên miền'], ['may_chu', 'admin/may_chu.php', 'Máy chủ'], ['to_chuc_cap', 'admin/to_chuc_cap.php', 'Tổ chức cấp']],
];
// Chỉ ẩn mục; quyền thật do từng trang kiểm tra bằng yeuCauAdmin().
if ($nguoi !== null && laAdmin($nguoi)) {
    $nhomMenu['Hệ thống'] = [['nguoi_dung', 'admin/nguoi_dung.php', 'Người dùng'], ['nhat_ky', 'admin/nhat_ky.php', 'Nhật ký']];
}

$nutGiaoDien = <<<'HTML'
<button type="button" class="nav-link px-2 bg-transparent border-0" data-doi-giao-dien
        title="Đổi giao diện sáng/tối" aria-label="Đổi giao diện sáng/tối">
    <svg class="icon hide-theme-dark" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/></svg>
    <svg class="icon hide-theme-light" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/></svg>
</button>
HTML;

// Logo: ổ khoá đóng, như ổ khoá HTTPS trên thanh địa chỉ, trong ô bo góc dải cyan sang xanh dương (user chốt 08/11/2026).
// Dùng ở landing, menu quản trị, trang đăng nhập; cùng hình với biểu tượng tab ngay dưới.
// Logo "vòng thời hạn" (user chọn 09/11/2026 trong 4 phương án): vòng gần kín vừa là chữ C, vừa là vòng đếm ngược;
// chấm ở chỗ hở là ngày hết hạn đang tới. Một màu như logo của Better Stack, không ô nền: tô bằng currentColor nên
// trắng trên nền tối, tối trên nền sáng (cột form trang đăng nhập).
$hinhLogo = '<path d="M23.4 8.6A10.5 10.5 0 1 0 23.4 23.4" fill="none" stroke="currentColor" stroke-width="4.4" stroke-linecap="round"/>'
    . '<circle cx="26.6" cy="16" r="2.5" fill="currentColor"/>';

// Logo kèm tên, dùng cho thanh trên landing, trang đăng nhập và đầu menu quản trị. Chữ cuối ("Cert") nhạt hơn
// (user chọn 09/11/2026). Đổi logo thì đổi ở đây, trang không tự ghép.
$phanTen = explode(' ', TEN_THUONG_HIEU, 2);
$thuongHieu = '<span class="thuong-hieu"><svg class="logo-dau" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-hidden="true">'
    . $hinhLogo . '</svg><span>' . e($phanTen[0])
    . (isset($phanTen[1]) ? ' <span class="ten-phu">' . e($phanTen[1]) . '</span>' : '') . '</span></span>';

// Nền sao (trang đăng nhập): sao rải như trên nền vũ trụ, lệch nhau tự nhiên, cỡ và độ sáng khác nhau, dày hơn
// một chút quanh giữa trang. User 09/11/2026: "điểm xuyết vài chấm nhỏ như những ngôi sao trên nền vũ trụ rất tự
// nhiên, không phải là chấm theo grid kiểu AI". Hạt giống cố định nên lần nào mở trang sao cũng nằm đúng chỗ cũ;
// gieo lại hạt ngẫu nhiên sau khi dùng để không ảnh hưởng chỗ khác gọi mt_rand. SVG co giãn phủ kín khung (slice).
mt_srand(69);
$cacSao = '';
for ($i = 0; $i < 150; $i++) {
    if ($i % 3 === 0) {
        // Một phần ba số sao gom quanh giữa: cộng ba số ngẫu nhiên cho phân bố hình chuông.
        $x = 800 + (mt_rand(0, 1000) + mt_rand(0, 1000) + mt_rand(0, 1000) - 1500) * 0.42;
        $y = 520 + (mt_rand(0, 1000) + mt_rand(0, 1000) + mt_rand(0, 1000) - 1500) * 0.3;
    } else {
        $x = mt_rand(0, 16000) / 10;
        $y = mt_rand(0, 10000) / 10;
    }
    $sang = mt_rand(0, 100);
    $banKinh = $sang > 94 ? 1.5 : ($sang > 75 ? 1.1 : 0.7); // Đa số sao rất nhỏ, vài sao sáng.
    $doMo = round(0.18 + mt_rand(0, 55) / 100, 2);
    $mau = mt_rand(0, 5) === 0 ? '#a5b0ff' : '#c9d3ee'; // Thỉnh thoảng một sao ánh tím.
    $cacSao .= sprintf('<circle cx="%.1f" cy="%.1f" r="%.1f" fill="%s" opacity="%.2f"/>', $x, $y, $banKinh, $mau, $doMo);
}
mt_srand();
$nenSao = '<svg class="nen-sao" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 1000" preserveAspectRatio="xMidYMid slice"'
    . ' aria-hidden="true" focusable="false">' . $cacSao . '</svg>';

// Biểu tượng tab: cùng hình logo, màu trắng trong ô nền tối, vì dấu một màu đứng riêng sẽ biến mất trên thanh tab
// sáng. Nhúng thẳng vào trang nên không thêm file và không thêm một lượt tải.
$bieuTuongTab = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">'
    . '<rect width="32" height="32" rx="7" fill="#0b0c14"/>'
    . '<g transform="translate(4 4) scale(0.75)" color="#f1f4fd">' . $hinhLogo . '</g></svg>');

// Khối tài khoản: máy tính đặt ở thanh trên, màn hẹp đặt cạnh nút mở menu. Dựng một lần, in hai chỗ.
// Đổi sáng/tối nằm trong menu tài khoản cho thanh trên gọn (user yêu cầu 08/11/2026); app.js bắt mọi
// phần tử có data-doi-giao-dien nên dòng menu dùng chung cách xử lý với nút ở landing.
$khoiTaiKhoan = '';
if ($nguoi !== null) {
    ob_start();
    ?>
    <div class="nav-item dropdown">
        <a href="#" class="nut-tai-khoan dropdown-toggle" data-bs-toggle="dropdown" aria-label="Mở menu tài khoản">
            <span class="avatar avatar-sm"><?= e(mb_substr($nguoi['full_name'], 0, 1)) ?></span>
            <?php // Màn hẹp chỉ hiện chữ cái đầu, để thanh trên gọn một hàng. ?>
            <span class="d-none d-lg-block">
                <span class="d-block nut-tai-khoan-ten"><?= e($nguoi['full_name']) ?></span>
                <span class="d-block small text-secondary"><?= e($nguoi['role_name']) ?></span>
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="<?= e(url('admin/tai_khoan.php')) ?>"><?= bieuTuongMenu('tai_khoan') ?>Tài khoản của tôi</a>
            <?php // Chữ và hình nói giao diện SẼ chuyển sang; hide-theme-* của Tabler ẩn dòng không đúng giao diện hiện tại. ?>
            <button type="button" class="dropdown-item" data-doi-giao-dien>
                <span class="hide-theme-dark"><?= bieuTuongMenu('giao_dien_toi') ?>Giao diện tối</span>
                <span class="hide-theme-light"><?= bieuTuongMenu('giao_dien_sang') ?>Giao diện sáng</span>
            </button>
            <div class="dropdown-divider"></div>
            <form method="post" action="<?= e(url('logout.php')) ?>">
                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                <button type="submit" class="dropdown-item"><?= bieuTuongMenu('dang_xuat') ?>Đăng xuất</button>
            </form>
        </div>
    </div>
    <?php
    $khoiTaiKhoan = ob_get_clean();
}
?>
<!doctype html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tieuDeTab) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e($bieuTuongTab) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/tabler/tabler.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/inter/inter.css')) ?>">
    <link rel="stylesheet" href="<?= e(urlTep('assets/app.css')) ?>">
    <!-- Đặt trong head và không defer: đổi màu trước khi vẽ trang, để không loé màu sáng. -->
    <script src="<?= e(url('assets/tabler/tabler-theme.min.js')) ?>"></script>
    <?php if ($coChuyenCanh): ?>
        <!-- Chỉ chuyển cảnh khi cả trang cũ và trang mới cùng bật, nên trang quản trị vẫn chuyển tức thì (QD-21).
             Trình duyệt chưa hỗ trợ hoặc máy bật giảm chuyển động thì chuyển trang như thường. -->
        <style>@media (prefers-reduced-motion: no-preference) { @view-transition { navigation: auto; } }</style>
    <?php endif; ?>
</head>
<body>
<?php if ($nguoi === null && !$coThanhRieng): ?>
    <div class="position-absolute top-0 end-0 p-3"><?= $nutGiaoDien ?></div>
<?php elseif ($nguoi !== null): ?>
    <?php // Menu dọc của Tabler: máy tính là cột cố định bên trái, màn dưới 992px thu thành thanh trên có nút mở menu.
          // Luôn nền navy như hero của landing (data-bs-theme="dark"), để vào quản trị vẫn thấy cùng một sản phẩm. ?>
    <aside class="navbar navbar-vertical navbar-expand-lg menu-doc d-print-none" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu-chinh"
                    aria-controls="menu-chinh" aria-expanded="false" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php // Tên hệ thống dẫn về landing; về Dashboard đã có mục Dashboard ngay bên dưới. ?>
            <a class="navbar-brand ten-thuong-hieu" href="<?= e(url('index.php')) ?>"><?= $thuongHieu ?></a>
            <div class="navbar-nav flex-row align-items-center thanh-tai-khoan d-lg-none"><?= $khoiTaiKhoan ?></div>
            <div class="collapse navbar-collapse" id="menu-chinh">
                <ul class="navbar-nav">
                    <?php foreach ($nhomMenu as $tenNhom => $cacMuc): ?>
                        <li class="menu-nhom"><?= e($tenNhom) ?></li>
                        <?php foreach ($cacMuc as [$khoa, $dich, $nhan]): ?>
                            <li class="nav-item<?= $trangHienTai === $khoa ? ' active' : '' ?>">
                                <a class="nav-link" href="<?= e(url($dich)) ?>"<?= $trangHienTai === $khoa ? ' aria-current="page"' : '' ?>>
                                    <span class="nav-link-icon"><?= bieuTuongMenu($khoa, '') ?></span>
                                    <span class="nav-link-title"><?= e($nhan) ?></span>
                                    <?php if ($khoa === 'canh_bao' && $soCanhBaoMenu > 0): ?>
                                        <span class="badge so-canh-bao ms-auto"><?= $soCanhBaoMenu ?><span class="visually-hidden"> cảnh báo mới</span></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </aside>
    <div class="page">
        <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none thanh-dau">
            <div class="container-xl">
                <div class="navbar-nav flex-row align-items-center thanh-tai-khoan ms-auto"><?= $khoiTaiKhoan ?></div>
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
