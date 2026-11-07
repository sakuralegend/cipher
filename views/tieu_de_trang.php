<?php
// Thanh tiêu đề của trang quản trị. Cần $tieuDe; có $nutThem = ['nhan' => ..., 'dich' => ...] thì hiện nút.
// Có $quayLai = ['nhan' => ..., 'dich' => ...] thì hiện link quay lại phía trên tiêu đề (trang chi tiết).
$nutThem ??= null;
$quayLai ??= null;
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <?php if ($quayLai !== null): ?>
                    <a class="page-pretitle quay-lai" href="<?= e(url($quayLai['dich'])) ?>">← <?= e($quayLai['nhan']) ?></a>
                <?php endif; ?>
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
