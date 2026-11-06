<?php
// Nhận POST thu hồi, xoá chứng chỉ. Không có giao diện: xử lý, lưu thông báo, rồi chuyển trang.
// Quyền xoá do xoaChungChi() kiểm tra, nên nhân viên gửi lệnh thẳng tới đây cũng bị từ chối.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chuyenHuong('admin/chung_chi.php');
}
yeuCauCsrf();

$id = (int) chuoiPost('id');

switch (chuoiPost('hanh_dong')) {
    case 'thu_hoi':
        $loi = thuHoiChungChi($pdo, $id);
        datThongBao($loi === null ? 'success' : 'danger', $loi ?? 'Đã thu hồi chứng chỉ.');
        chuyenHuong('admin/chung_chi_xem.php?id=' . $id);
        // chuyenHuong() kết thúc yêu cầu, nên không cần break.
    case 'xoa':
        $loi = xoaChungChi($pdo, $id, $nguoi);
        datThongBao($loi === null ? 'success' : 'danger', $loi ?? 'Đã xoá chứng chỉ.');
        chuyenHuong($loi === null ? 'admin/chung_chi.php' : 'admin/chung_chi_xem.php?id=' . $id);
    default:
        datThongBao('danger', 'Thao tác không hợp lệ.');
        chuyenHuong('admin/chung_chi.php');
}
