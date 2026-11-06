<?php
// Mọi trang web gọi file này đầu tiên. Test không gọi, vì test không có trình duyệt và phiên.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

batDauPhien();

// Chống XSS: mọi dữ liệu in ra trang đều đi qua hàm này.
function e(?string $chuoi): string
{
    return htmlspecialchars((string) $chuoi, ENT_QUOTES, 'UTF-8');
}

// Địa chỉ tính từ thư mục public/, nên đúng cả khi web nằm trong thư mục con của htdocs.
function url(string $duongDan): string
{
    $thuMucPublic = str_replace('\\', '/', realpath(__DIR__ . '/../public'));
    $gocWeb = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    return substr($thuMucPublic, strlen($gocWeb)) . '/' . $duongDan;
}

function chuyenHuong(string $duongDan): never
{
    header('Location: ' . url($duongDan));
    exit;
}

// Thông báo sau thao tác: lưu vào phiên trước khi chuyển hướng, trang sau hiện một lần rồi xoá.
// $loai là 'success' hoặc 'danger', trùng tên class alert của Tabler.
function datThongBao(string $loai, string $noiDung): void
{
    $_SESSION['thong_bao'] = ['loai' => $loai, 'noi_dung' => $noiDung];
}

function layThongBao(): ?array
{
    $thongBao = $_SESSION['thong_bao'] ?? null;
    unset($_SESSION['thong_bao']);
    return $thongBao;
}

// Dựng một ô nhập kèm nhãn và dòng lỗi, để các form không lặp lại cùng một đoạn HTML.
// $loi là mảng lỗi từ PHP; $thuocTinh là quy tắc cho JavaScript, ví dụ 'required maxlength="100"'.
// $loiMacDinh hiện khi JavaScript chặn; PHP báo lỗi thì câu của PHP được ưu tiên.
function oNhap(string $ten, string $nhan, ?string $giaTri, array $loi, string $thuocTinh = '', string $loiMacDinh = '', string $goiY = ''): string
{
    $batBuoc = str_contains($thuocTinh, 'required') ? ' required' : '';
    $saiCss = isset($loi[$ten]) ? ' is-invalid' : '';
    $html = '<div class="mb-3">'
        . '<label class="form-label' . $batBuoc . '" for="' . e($ten) . '">' . e($nhan) . '</label>'
        . '<input class="form-control' . $saiCss . '" id="' . e($ten) . '" name="' . e($ten) . '" value="' . e($giaTri) . '" ' . $thuocTinh . '>'
        . '<div class="invalid-feedback">' . e($loi[$ten] ?? $loiMacDinh) . '</div>';
    if ($goiY !== '') {
        $html .= '<small class="form-hint">' . e($goiY) . '</small>';
    }
    return $html . '</div>';
}

// Dựng một ô chọn. $luaChon có dạng [giá trị => nhãn].
function oChon(string $ten, string $nhan, array $luaChon, ?string $dangChon, array $loi, string $loiMacDinh): string
{
    $saiCss = isset($loi[$ten]) ? ' is-invalid' : '';
    $html = '<div class="mb-3">'
        . '<label class="form-label required" for="' . e($ten) . '">' . e($nhan) . '</label>'
        . '<select class="form-select' . $saiCss . '" id="' . e($ten) . '" name="' . e($ten) . '" required>'
        . '<option value="">Chọn</option>';
    foreach ($luaChon as $giaTri => $nhanLuaChon) {
        $chon = (string) $giaTri === (string) $dangChon ? ' selected' : '';
        $html .= '<option value="' . e((string) $giaTri) . '"' . $chon . '>' . e($nhanLuaChon) . '</option>';
    }
    return $html . '</select><div class="invalid-feedback">' . e($loi[$ten] ?? $loiMacDinh) . '</div></div>';
}

