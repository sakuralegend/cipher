<?php
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

// Cảnh báo được tạo khi có người xem, không dùng lịch chạy nền (docs/SPEC.md mục 5).
taoCanhBao($pdo);
$soCanhBaoMoi = demCanhBaoMoi($pdo);

$tieuDe = 'Dashboard';
$trangHienTai = 'dashboard';
require __DIR__ . '/../../views/dau_trang.php';
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <h1 class="page-title">Dashboard</h1>
    </div>
</div>
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <p>Xin chào, <?= e($nguoi['full_name']) ?>. Số liệu tổng quan sẽ hiển thị ở đây.</p>
                <a href="<?= e(url('admin/canh_bao.php')) ?>"><?= $soCanhBaoMoi ?> cảnh báo mới</a>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
