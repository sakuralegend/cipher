<?php
// Thanh tiêu đề của trang quản trị. Cần $tieuDe; có $nutThem = ['nhan' => ..., 'dich' => ...] thì hiện nút.
// Có $quayLai = ['nhan' => ..., 'dich' => ...] thì hiện link quay lại phía trên tiêu đề (trang chi tiết).
// Có $nutPhu = [['nhan' => ..., 'href' => địa chỉ đã dựng sẵn], ...] thì hiện nút phụ ở góc phải, ví dụ Xuất CSV, Bản in
// (user 14/11/2026: đặt cùng dòng tiêu đề để màn hẹp không mất thêm một dòng cho hai nút này).
$nutThem ??= null;
$quayLai ??= null;
$nutPhu ??= [];
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center flex-nowrap">
            <div class="col">
                <?php if ($quayLai !== null): ?>
                    <a class="page-pretitle quay-lai" href="<?= e(url($quayLai['dich'])) ?>">← <?= e($quayLai['nhan']) ?></a>
                <?php endif; ?>
                <h1 class="page-title"><?= e($tieuDe) ?></h1>
            </div>
            <?php if ($nutThem !== null || $nutPhu !== []): ?>
                <div class="col-auto d-flex gap-2">
                    <?php foreach ($nutPhu as $nut): ?>
                        <a class="btn btn-sm" href="<?= e($nut['href']) ?>"><?= e($nut['nhan']) ?></a>
                    <?php endforeach; ?>
                    <?php if ($nutThem !== null): ?>
                        <a class="btn btn-primary" href="<?= e(url($nutThem['dich'])) ?>"><?= e($nutThem['nhan']) ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
