<?php
// Thanh tiêu đề của trang quản trị. Cần $tieuDe; có $nutThem = ['nhan' => ..., 'dich' => ...] thì hiện nút.
$nutThem ??= null;
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title"><?= e($tieuDe) ?></h1>
            </div>
            <?php if ($nutThem !== null): ?>
                <div class="col-auto">
                    <a class="btn btn-primary" href="<?= e(url($nutThem['dich'])) ?>"><?= e($nutThem['nhan']) ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
