<?php
// Đầu trang dùng chung. Trang gọi file này phải có $tieuDe.
// $nguoi:          có thì hiện khu quản trị (menu hai tầng).
// $trangHienTai:   khoá của trang đang mở, để tô sáng khu và mục ('dashboard', 'may_chu', 'chung_chi_them'...).
// $coThanhRieng:   trang tự dựng thanh trên của mình (landing, đăng nhập).
// $tieuDeTab:      chữ trên tab trình duyệt; bỏ trống thì là "$tieuDe | Cipher Cert".
// Web chỉ có giao diện tối (QD-25, user 09/11/2026); bản in vẫn nền trắng nhờ khối @media print trong app.css.
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
// Khoá trùng khoá khu và khoá trang trong $khuMenu bên dưới.
const BIEU_TUONG_MENU = [
    'tong_quan' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
    'dashboard' => '<rect x="4" y="4" width="6" height="8" rx="1"/><rect x="4" y="16" width="6" height="4" rx="1"/><rect x="14" y="12" width="6" height="8" rx="1"/><rect x="14" y="4" width="6" height="4" rx="1"/>',
    'canh_bao' => '<path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3H4a4 4 0 0 0 2-3v-3a7 7 0 0 1 4-6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/>',
    'khu_chung_chi' => '<circle cx="15" cy="15" r="3"/><path d="M13 17.5V22l2-1.5 2 1.5v-4.5"/><path d="M10 19H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-1 1.73"/><path d="M6 9h12M6 12h3M6 15h2"/>',
    'chung_chi' => '<path d="M9 6h11M9 12h11M9 18h11M5 6v.01M5 12v.01M5 18v.01"/>',
    'chung_chi_them' => '<path d="M12 5v14M5 12h14"/>',
    'chung_chi_nhap' => '<path d="M4 17v2a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2"/><path d="M7 11l5 5 5-5M12 4v12"/>',
    'thong_ke' => '<rect x="3" y="12" width="6" height="8" rx="1"/><rect x="9" y="8" width="6" height="12" rx="1"/><rect x="15" y="4" width="6" height="16" rx="1"/><path d="M4 20h14"/>',
    'ha_tang' => '<rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 8v.01M7 17v.01"/>',
    'may_chu' => '<rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 8v.01M7 17v.01"/>',
    'ten_mien' => '<circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 0 0 0 18M12.5 3a17 17 0 0 1 0 18"/>',
    'to_chuc_cap' => '<path d="M12 3l8 4.5v4.5c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V7.5z"/><path d="M9 12l2 2 4-4"/>',
    'he_thong' => '<path d="M4 6h8M16 6h4M4 12h2M10 12h10M4 18h10M18 18h2"/><circle cx="14" cy="6" r="2"/><circle cx="8" cy="12" r="2"/><circle cx="16" cy="18" r="2"/>',
    'nguoi_dung' => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75M21 21v-2a4 4 0 0 0-3-3.85"/>',
    'nhat_ky' => '<path d="M12 8v4l2 2"/><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"/>',
    'tai_khoan' => '<circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/>',
    'dang_xuat' => '<path d="M14 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2"/><path d="M9 12h12M18 9l3 3-3 3"/>',
    'mo_cot' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M15 4v16M9 10l2 2-2 2"/>',
    'thu_cot' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 4v16M15 10l-2 2 2 2"/>',
    'ba_gach' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'dong' => '<path d="M6 6l12 12M18 6L6 18"/>',
    'mui_ten' => '<path d="M9 6l6 6-6 6"/>',
];

function bieuTuongMenu(string $ten, string $lop = 'dropdown-item-icon'): string
{
    return '<svg class="icon ' . $lop . '" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . BIEU_TUONG_MENU[$ten] . '</svg>';
}

// Menu hai tầng theo app Better Stack (user duyệt mẫu 09/11/2026). Mỗi khu: [nhãn, các trang]; mỗi trang:
// [khoá trang, địa chỉ, nhãn]. Bấm khu là vào trang đầu của khu; khu chỉ có một trang (Cảnh báo) không mở bảng phụ.
$khuMenu = [
    'tong_quan' => ['Tổng quan', [['dashboard', 'admin/index.php', 'Dashboard'], ['thong_ke', 'admin/thong_ke.php', 'Thống kê và báo cáo']]],
    'canh_bao' => ['Cảnh báo', [['canh_bao', 'admin/canh_bao.php', 'Cảnh báo']]],
    'chung_chi' => ['Chứng chỉ', [['chung_chi', 'admin/chung_chi.php', 'Danh sách chứng chỉ'], ['chung_chi_them', 'admin/chung_chi_form.php', 'Thêm chứng chỉ'], ['chung_chi_nhap', 'admin/chung_chi_nhap.php', 'Nhập hàng loạt']]],
    'ha_tang' => ['Hạ tầng', [['ten_mien', 'admin/ten_mien.php', 'Tên miền'], ['may_chu', 'admin/may_chu.php', 'Máy chủ'], ['to_chuc_cap', 'admin/to_chuc_cap.php', 'Tổ chức cấp']]],
];
// Chỉ ẩn mục; quyền thật do từng trang kiểm tra bằng yeuCauAdmin().
if ($nguoi !== null && laAdmin($nguoi)) {
    $khuMenu['he_thong'] = ['Hệ thống', [['nguoi_dung', 'admin/nguoi_dung.php', 'Người dùng'], ['nhat_ky', 'admin/nhat_ky.php', 'Nhật ký']]];
}

// Khu chứa trang đang mở (trang ngoài menu như Tài khoản thì không khu nào sáng, không có bảng phụ).
$khuHienTai = null;
foreach ($khuMenu as $maKhu => [, $cacTrang]) {
    if (in_array($trangHienTai, array_column($cacTrang, 0), true)) {
        $khuHienTai = $maKhu;
    }
}
$soCanhBaoHtml = $soCanhBaoMenu > 0
    ? '<span class="qt-so-do">' . $soCanhBaoMenu . '<span class="visually-hidden"> cảnh báo mới</span></span>'
    : '';

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

// Tài khoản ở đáy thanh khu: ảnh đại diện vuông bo góc; hệ thống chưa có ảnh đại diện nên luôn là hình nhân (user
// 09/11/2026: không dùng chữ cái đầu). Menu tài khoản theo app Better Stack: tên, vai trò · tên đăng nhập, các mục,
// Đăng xuất dưới cùng. Dựng một lần, in hai chỗ (thanh khu trên máy tính, ngăn kéo trên điện thoại).
$daiDien = '<span class="qt-dai-dien" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">'
    . '<circle cx="12" cy="8.5" r="4"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0z"/></svg></span>';
$mucTaiKhoan = '';
if ($nguoi !== null) {
    ob_start();
    ?>
    <a class="dropdown-item" href="<?= e(url('admin/tai_khoan.php')) ?>"><?= bieuTuongMenu('tai_khoan') ?>Tài khoản và mật khẩu</a>
    <div class="dropdown-divider"></div>
    <form method="post" action="<?= e(url('logout.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
        <button type="submit" class="dropdown-item"><?= bieuTuongMenu('dang_xuat') ?>Đăng xuất</button>
    </form>
    <?php
    $mucTaiKhoan = ob_get_clean();
}
?>
<!doctype html>
<html lang="vi" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tieuDeTab) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e($bieuTuongTab) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/tabler/tabler.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/inter/inter.css')) ?>">
    <link rel="stylesheet" href="<?= e(urlTep('assets/app.css')) ?>">
    <?php if ($nguoi !== null): ?>
        <!-- Trạng thái thu gọn của hai tầng menu, đặt trước khi vẽ để menu không nhảy lúc mở trang (app.js lưu khi bấm). -->
        <script>
            try {
                if (localStorage.getItem('qt-no-thanh') === '1') document.documentElement.classList.add('qt-no-thanh');
                if (localStorage.getItem('qt-thu-phu') === '1') document.documentElement.classList.add('qt-thu-phu');
            } catch (loi) {}
        </script>
    <?php endif; ?>
    <?php if ($coChuyenCanh): ?>
        <!-- Chỉ chuyển cảnh khi cả trang cũ và trang mới cùng bật, nên trang quản trị vẫn chuyển tức thì.
             Trình duyệt chưa hỗ trợ hoặc máy bật giảm chuyển động thì chuyển trang như thường. -->
        <style>@media (prefers-reduced-motion: no-preference) { @view-transition { navigation: auto; } }</style>
    <?php endif; ?>
</head>
<body>
<?php if ($nguoi !== null): ?>
<div class="qt">
    <?php // Tầng 1, máy tính: thanh khu. Thu còn biểu tượng; rê vào logo hiện nút nở/thu (app.js: data-qt-doi). ?>
    <nav class="qt-thanh d-print-none" aria-label="Menu chính">
        <button type="button" class="qt-o qt-logo" data-qt-doi="thanh" aria-label="Nở hoặc thu menu" title="Nở hoặc thu menu">
            <svg class="qt-logo-dau" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-hidden="true"><?= $hinhLogo ?></svg>
            <span class="qt-logo-nut"><?= bieuTuongMenu('mo_cot', 'qt-khi-thu') ?><?= bieuTuongMenu('thu_cot', 'qt-khi-no') ?></span>
            <span class="qt-chu"><?= e($phanTen[0]) ?><?= isset($phanTen[1]) ? ' <span class="ten-phu">' . e($phanTen[1]) . '</span>' : '' ?></span>
        </button>
        <?php foreach ($khuMenu as $maKhu => [$nhanKhu, $cacTrang]): ?>
            <a class="qt-o<?= $maKhu === $khuHienTai ? ' mo' : '' ?>" href="<?= e(url($cacTrang[0][1])) ?>" title="<?= e($nhanKhu) ?>"<?= $maKhu === $khuHienTai ? ' aria-current="true"' : '' ?>>
                <?= bieuTuongMenu($maKhu === 'chung_chi' ? 'khu_chung_chi' : $maKhu, '') ?>
                <span class="qt-chu"><?= e($nhanKhu) ?></span>
                <?= $maKhu === 'canh_bao' ? $soCanhBaoHtml : '' ?>
            </a>
        <?php endforeach; ?>
        <div class="qt-khoang"></div>
        <div class="qt-tk dropdown">
            <button type="button" class="qt-o qt-tk-nut" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu tài khoản"
                    data-bs-popper-config='{"placement":"right-end","modifiers":[{"name":"offset","options":{"offset":[0,12]}}]}'>
                <?= $daiDien ?>
                <span class="qt-chu"><b><?= e($nguoi['full_name']) ?></b><small><?= e($nguoi['role_name']) ?></small></span>
            </button>
            <div class="dropdown-menu qt-tk-menu">
                <div class="qt-tk-dau"><b><?= e($nguoi['full_name']) ?></b><small><?= e($nguoi['role_name']) ?> · <?= e($nguoi['username']) ?></small></div>
                <div class="dropdown-divider"></div>
                <?= $mucTaiKhoan ?>
            </div>
        </div>
    </nav>

    <?php // Tầng 2, máy tính: bảng phụ của khu đang mở; thu lại còn một cột biểu tượng. Khu một trang không có bảng phụ. ?>
    <?php if ($khuHienTai !== null && count($khuMenu[$khuHienTai][1]) > 1): ?>
        <nav class="qt-phu d-print-none" aria-label="<?= e($khuMenu[$khuHienTai][0]) ?>">
            <div class="qt-phu-dau">
                <h2 class="qt-phu-ten"><?= e($khuMenu[$khuHienTai][0]) ?></h2>
                <button type="button" class="qt-o qt-phu-nut" data-qt-doi="phu" aria-label="Thu hoặc mở bảng phụ" title="Thu hoặc mở bảng phụ">
                    <?= bieuTuongMenu('thu_cot', 'qt-khi-mo-phu') ?><?= bieuTuongMenu('mo_cot', 'qt-khi-thu-phu') ?>
                </button>
            </div>
            <?php foreach ($khuMenu[$khuHienTai][1] as [$khoa, $dich, $nhan]): ?>
                <a class="qt-o<?= $khoa === $trangHienTai ? ' mo' : '' ?>" href="<?= e(url($dich)) ?>" title="<?= e($nhan) ?>"<?= $khoa === $trangHienTai ? ' aria-current="page"' : '' ?>>
                    <?= bieuTuongMenu($khoa, '') ?><span class="qt-chu"><?= e($nhan) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <div class="page-wrapper qt-trang">
        <?php // Điện thoại: thanh trên có logo và nút ba gạch; menu là ngăn kéo trượt từ phải (offcanvas của Bootstrap trong Tabler). ?>
        <header class="qt-tren d-print-none">
            <a class="qt-tren-logo" href="<?= e(url('index.php')) ?>"><?= $thuongHieu ?></a>
            <button type="button" class="qt-tren-nut" data-bs-toggle="offcanvas" data-bs-target="#qt-ngan" aria-controls="qt-ngan" aria-label="Mở menu">
                <?= bieuTuongMenu('ba_gach', '') ?>
            </button>
        </header>
        <?php // Mở ra chỉ thấy khu lớn; bấm khu nhiều trang thì khu đó mới xổ trang con (user 09/11/2026). ?>
        <div class="offcanvas offcanvas-end qt-ngan d-print-none" tabindex="-1" id="qt-ngan" aria-label="Menu">
            <div class="qt-ngan-dau">
                <button type="button" class="qt-ngan-dong" data-bs-dismiss="offcanvas" aria-label="Đóng menu"><?= bieuTuongMenu('dong', '') ?></button>
            </div>
            <div class="qt-ngan-than">
                <?php foreach ($khuMenu as $maKhu => [$nhanKhu, $cacTrang]): ?>
                    <?php $bieuTuongKhu = bieuTuongMenu($maKhu === 'chung_chi' ? 'khu_chung_chi' : $maKhu, ''); ?>
                    <?php if (count($cacTrang) === 1): ?>
                        <a class="qt-ngan-khu<?= $maKhu === $khuHienTai ? ' mo' : '' ?>" href="<?= e(url($cacTrang[0][1])) ?>"><?= $bieuTuongKhu ?><?= e($nhanKhu) ?><?= $maKhu === 'canh_bao' ? $soCanhBaoHtml : '' ?></a>
                    <?php else: ?>
                        <button type="button" class="qt-ngan-khu" data-bs-toggle="collapse" data-bs-target="#qt-ngan-<?= $maKhu ?>" aria-expanded="false" aria-controls="qt-ngan-<?= $maKhu ?>">
                            <?= $bieuTuongKhu ?><?= e($nhanKhu) ?><?= bieuTuongMenu('mui_ten', 'qt-mui') ?>
                        </button>
                        <div class="collapse" id="qt-ngan-<?= $maKhu ?>">
                            <div class="qt-ngan-con">
                                <?php foreach ($cacTrang as [$khoa, $dich, $nhan]): ?>
                                    <a class="<?= $khoa === $trangHienTai ? 'mo' : '' ?>" href="<?= e(url($dich)) ?>"<?= $khoa === $trangHienTai ? ' aria-current="page"' : '' ?>><?= e($nhan) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="qt-ngan-tk">
                <button type="button" class="qt-ngan-khu" data-bs-toggle="collapse" data-bs-target="#qt-ngan-tai-khoan" aria-expanded="false" aria-controls="qt-ngan-tai-khoan">
                    <?= $daiDien ?><span class="qt-chu"><b><?= e($nguoi['full_name']) ?></b><small><?= e($nguoi['role_name']) ?> · <?= e($nguoi['username']) ?></small></span><?= bieuTuongMenu('mui_ten', 'qt-mui') ?>
                </button>
                <div class="collapse" id="qt-ngan-tai-khoan"><div class="qt-ngan-con qt-ngan-tk-con"><?= $mucTaiKhoan ?></div></div>
            </div>
        </div>
        <?php $thongBao = layThongBao(); ?>
        <?php if ($thongBao !== null): ?>
            <div class="container-xl pt-3">
                <div class="alert alert-<?= e($thongBao['loai']) ?> mb-0" role="alert"><?= e($thongBao['noi_dung']) ?></div>
            </div>
        <?php endif; ?>
<?php endif; ?>
