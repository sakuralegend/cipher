<?php
// Nút xoá một dòng: gửi POST về chính trang danh sách, có hỏi xác nhận (app.js).
// Cần $idXoa và $cauHoiXoa.
?>
<form method="post" data-xac-nhan="<?= e($cauHoiXoa) ?>">
    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
    <input type="hidden" name="id" value="<?= (int) $idXoa ?>">
    <button class="btn btn-sm btn-outline-danger">Xoá</button>
</form>
