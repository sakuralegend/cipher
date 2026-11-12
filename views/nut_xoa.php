<?php
// Nút xoá một dòng danh mục (máy chủ, tên miền, tổ chức cấp): gửi POST về chính trang danh sách, có hỏi xác nhận (app.js).
// Cần $idXoa, $cauHoiXoa và $nguoi (người đang đăng nhập). Chỉ Admin thấy nút: hàm xoá cũng từ chối Nhân viên
// (LOI_CHI_ADMIN_XOA trong src/danh_muc.php), ẩn nút chỉ để Nhân viên không bấm vào thứ chắc chắn bị từ chối.
if (!laAdmin($nguoi)) {
    return;
}
?>
<form method="post" data-xac-nhan="<?= e($cauHoiXoa) ?>">
    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
    <input type="hidden" name="id" value="<?= (int) $idXoa ?>">
    <button class="btn btn-sm btn-outline-danger">Xoá</button>
</form>
