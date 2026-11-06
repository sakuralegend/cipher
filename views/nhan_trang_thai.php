<?php
// Nhãn trạng thái có màu. Cần $trangThaiHienThi là một khoá của TRANG_THAI.
$thongTinTrangThai = TRANG_THAI[$trangThaiHienThi];
?>
<span class="badge bg-<?= e($thongTinTrangThai['mau']) ?>-lt"><?= e($thongTinTrangThai['nhan']) ?></span>
