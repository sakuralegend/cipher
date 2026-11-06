<?php
require __DIR__ . '/../../src/khoi_dong.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$tieuDe = 'Dashboard';
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
                Xin chào, <?= e($nguoi['full_name']) ?>. Số liệu tổng quan sẽ hiển thị ở đây.
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
