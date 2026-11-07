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
$coThanhRieng = true; // Nút đổi giao diện đặt trong nửa form, vì nửa trái luôn tối.
require __DIR__ . '/../views/dau_trang.php';
?>
<?php // Chia đôi: nửa trái luôn tối như hero của landing, nửa phải là form theo sáng/tối (CSS mục "Trang đăng nhập"). ?>
<main class="dn-trang">
    <section class="dn-trai" data-bs-theme="dark">
        <div class="ld-anh-sang" aria-hidden="true"></div>
        <a class="dn-ve-landing" href="<?= e(url('index.php')) ?>">
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M5 12l6 6M5 12l6-6"/></svg>
            Trang giới thiệu
        </a>
        <div class="dn-loi-chao">
            <p class="ld-nhan-nho">Quản lý chứng chỉ số</p>
            <p class="ld-tieu-de">Không để chứng chỉ nào hết hạn bất ngờ</p>
            <p class="ld-mo-ta">Đăng nhập để xem chứng chỉ sắp hết hạn và xử lý cảnh báo theo từng mốc.</p>
            <div class="dn-moc" aria-hidden="true">
                <span class="badge bg-yellow-lt">Mốc 30 ngày</span>
                <span class="badge bg-orange-lt">Mốc 15 ngày</span>
                <span class="badge bg-red-lt">Mốc 7 ngày</span>
            </div>
        </div>
        <p class="ld-chan-trang">Đề tài 69: Xây dựng hệ thống quản lý chứng chỉ số và thời hạn sử dụng</p>
    </section>

    <section class="dn-phai">
        <div class="dn-phai-dau"><?= $nutGiaoDien ?></div>
        <div class="dn-form">
            <h1 class="dn-tieu-de">Đăng nhập</h1>
            <?php // Không có trang tự đăng ký (QD-03), nên nói rõ tài khoản lấy ở đâu. ?>
            <p class="text-secondary mb-4">Dùng tài khoản do quản trị viên cấp.</p>
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
                <div class="mb-4">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input class="form-control" id="password" name="password" type="password"
                           autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-lg ld-nut-chinh w-100">Đăng nhập</button>
            </form>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../views/cuoi_trang.php'; ?>
