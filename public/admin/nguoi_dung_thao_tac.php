<?php
// Nhận POST khoá, mở khoá, xoá. Không có giao diện: xử lý, lưu thông báo, rồi chuyển về danh sách.
// Nhờ chuyển hướng, tải lại trang không gửi lại thao tác.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/nguoi_dung.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenHuong('admin/nguoi_dung.php');
}
yeuCauCsrf();

$id = (int) chuoiPost('id');
$idNguoiThucHien = (int) $nguoi['id'];

switch (chuoiPost('hanh_dong')) {
    case 'khoa':
        $loi = khoaNguoiDung($pdo, $id, $idNguoiThucHien);
        $thanhCong = 'Đã khoá tài khoản.';
        break;
    case 'mo_khoa':
        moKhoaNguoiDung($pdo, $id);
        $loi = null;
        $thanhCong = 'Đã mở khoá tài khoản.';
        break;
    case 'xoa':
        $loi = xoaNguoiDung($pdo, $id, $idNguoiThucHien);
        $thanhCong = 'Đã xoá người dùng.';
        break;
    default:
        $loi = 'Thao tác không hợp lệ.';
        $thanhCong = '';
}

datThongBao($loi === null ? 'success' : 'danger', $loi ?? $thanhCong);
chuyenHuong('admin/nguoi_dung.php');
