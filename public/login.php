<?php
// Đăng nhập. Ai dùng: mọi người có tài khoản (Admin, Nhân viên quản trị); không có trang tự đăng ký (QD-03).
// Luồng: gõ tên và mật khẩu -> dangNhap() kiểm tra -> vào Dashboard. Lỗi: sai tên hay sai mật khẩu báo cùng một câu;
// sai 5 lần thì tài khoản tạm chặn 15 phút; tài khoản bị Admin khoá thì không vào được. Đã đăng nhập thì về Dashboard.
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
$coThanhRieng = true; // Nút đổi giao diện đặt trong cột form, vì nửa trái luôn tối.
$coChuyenCanh = true; // Thẻ chứng chỉ bay từ landing sang, và bay ngược về khi bấm "Trang giới thiệu".
require __DIR__ . '/../views/dau_trang.php';
?>
<?php // Nửa trái luôn tối như hero của landing, có thẻ chứng chỉ tự chạy; cột phải là form theo sáng/tối (CSS mục "Trang đăng nhập"). ?>
<main class="dn-trang">
    <?php // data-ld-muc: màu trạng thái của thẻ và quầng sáng, app.js đổi theo phim (data-ld-phim). Khung tĩnh là "còn 7 ngày" nên bắt đầu bằng đỏ. ?>
    <section class="dn-trai" data-bs-theme="dark" data-ld-muc="do" data-ld-phim>
        <div class="ld-anh-sang" aria-hidden="true"></div>
        <a class="dn-ve-landing" href="<?= e(url('index.php')) ?>">
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M5 12l6 6M5 12l6-6"/></svg>
            Trang giới thiệu
        </a>
        <div class="dn-giua">
            <div class="dn-loi-chao">
                <p class="ld-nhan-nho">Quản lý chứng chỉ số và thời hạn sử dụng</p>
                <p class="ld-tieu-de"><span class="dn-dong-dau">Không để chứng chỉ nào</span> <span class="ld-chu-sang">hết hạn bất ngờ</span></p>
                <p class="ld-mo-ta">Đăng nhập để xem chứng chỉ sắp hết hạn và xử lý cảnh báo theo từng mốc.</p>
            </div>
            <div class="dn-phim">
                <?php require __DIR__ . '/../views/the_chung_chi.php'; ?>
            </div>
        </div>
    </section>

    <section class="dn-phai">
        <div class="dn-phai-dau">
            <span class="dn-ten"><?= $logo ?><?= e(TEN_THUONG_HIEU) ?></span>
            <?= $nutGiaoDien ?>
        </div>
        <div class="dn-form">
            <h1 class="dn-tieu-de">Đăng nhập</h1>
            <?php // Không có trang tự đăng ký (QD-03), nên nói rõ tài khoản lấy ở đâu. ?>
            <p class="text-secondary mb-4">Dùng tài khoản do quản trị viên cấp.</p>
            <?php if ($loi !== null): ?>
                <div class="alert alert-danger" role="alert"><?= e($loi) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('login.php')) ?>">
                <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                <?php // Biểu tượng trong ô (input-icon của Tabler, hình của bộ Tabler Icons) chỉ để trang trí nên ẩn với trình đọc màn hình. ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Tên đăng nhập</label>
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
                        </span>
                        <input class="form-control" id="username" name="username" value="<?= e($ten) ?>"
                               placeholder="Nhập tên đăng nhập" autocomplete="username" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><circle cx="12" cy="16" r="1"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        </span>
                        <input class="form-control" id="password" name="password" type="password"
                               placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                        <button type="button" class="dn-hien-mat-khau" data-hien-mat-khau aria-controls="password"
                                aria-pressed="false" aria-label="Hiện mật khẩu" hidden>
                            <svg class="icon dn-mat-mo" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.7 4.7-6 7-10 7s-7.3-2.3-10-7c2.7-4.7 6-7 10-7s7.3 2.3 10 7"/></svg>
                            <svg class="icon dn-mat-nham" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.4 5.4A9 9 0 0 1 12 5c4 0 7.3 2.3 10 7a17 17 0 0 1-2.2 3M17.4 17.4A9.6 9.6 0 0 1 12 19c-4 0-7.3-2.3-10-7 1.3-2.2 2.7-3.9 4.4-5.1M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-lg ld-nut-chinh w-100">Đăng nhập</button>
            </form>
        </div>
        <p class="dn-chan">Đề tài 69: Xây dựng hệ thống quản lý chứng chỉ số và thời hạn sử dụng</p>
    </section>
</main>
<?php require __DIR__ . '/../views/cuoi_trang.php'; ?>
