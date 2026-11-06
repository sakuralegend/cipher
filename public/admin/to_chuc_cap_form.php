<?php
// Form thêm (không có ?id=) và sửa (có ?id=) tổ chức cấp chứng chỉ.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$loi = [];
$giaTri = ['name' => '', 'country' => '', 'is_trusted' => 1];

if ($id !== null) {
    $giaTri = timToChuc($pdo, $id);
    if ($giaTri === null) {
        datThongBao('danger', 'Tổ chức không còn tồn tại.');
        chuyenHuong('admin/to_chuc_cap.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    yeuCauCsrf();
    $giaTri = [
        'name' => trim(chuoiPost('name')),
        'country' => trim(chuoiPost('country')),
        'is_trusted' => chuoiPost('is_trusted') === '1' ? 1 : 0, // Ô không tick thì trình duyệt không gửi.
    ];
    $loi = luuToChuc($pdo, $id, $giaTri);
    if ($loi === []) {
        datThongBao('success', $id === null ? 'Đã thêm tổ chức.' : 'Đã lưu thay đổi.');
        chuyenHuong('admin/to_chuc_cap.php');
    }
}

$hanhDong = 'admin/to_chuc_cap_form.php' . ($id !== null ? '?id=' . $id : '');
$tieuDe = $id === null ? 'Thêm tổ chức cấp' : 'Sửa tổ chức cấp';
$trangHienTai = 'to_chuc_cap';
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-lg-7">
                <form class="card" method="post" action="<?= e(url($hanhDong)) ?>" data-kiem-tra novalidate>
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <div class="card-body">
                        <?= oNhap('name', 'Tên tổ chức', $giaTri['name'], $loi,
                            'required maxlength="100" autocomplete="off"', 'Tên tổ chức là bắt buộc.', "Ví dụ: Let's Encrypt") ?>
                        <?= oNhap('country', 'Quốc gia', $giaTri['country'], $loi,
                            'required maxlength="100" autocomplete="off"', 'Quốc gia là bắt buộc.') ?>
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_trusted" value="1" <?= $giaTri['is_trusted'] ? 'checked' : '' ?>>
                            <span class="form-check-label">Tổ chức được tin cậy</span>
                        </label>
                    </div>
                    <div class="card-footer text-end">
                        <a class="btn" href="<?= e(url('admin/to_chuc_cap.php')) ?>">Huỷ</a>
                        <button type="submit" class="btn btn-primary"><?= $id === null ? 'Thêm tổ chức' : 'Lưu thay đổi' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
