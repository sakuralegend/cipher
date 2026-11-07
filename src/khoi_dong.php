<?php
// Mọi trang web gọi file này đầu tiên. Test không gọi, vì test không có trình duyệt và phiên.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
// Các hàm ghi dữ liệu ở src/ đều gọi ghiNhatKy(), nên nạp sẵn cho mọi trang.
require_once __DIR__ . '/nhat_ky.php';

batDauPhien();

// Tên thương hiệu ngắn trên logo, menu và tab trình duyệt (QD-24). Tên đề tài đầy đủ vẫn ở landing, đăng nhập, báo cáo.
const TEN_THUONG_HIEU = 'Cipher Cert';

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

// Địa chỉ file tĩnh hay sửa (app.css, app.js) kèm ?v=thời điểm sửa file: máy chủ không gửi Cache-Control,
// nên không có số này thì trình duyệt có thể dùng bản cũ hàng giờ sau khi deploy. Sửa file là số tự đổi.
function urlTep(string $duongDan): string
{
    return url($duongDan) . '?v=' . filemtime(__DIR__ . '/../public/' . $duongDan);
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
// $batBuoc = false dùng cho bộ lọc: dòng đầu là "Tất cả" và được phép để trống.
function oChon(string $ten, string $nhan, array $luaChon, ?string $dangChon, array $loi, string $loiMacDinh, bool $batBuoc = true): string
{
    $saiCss = isset($loi[$ten]) ? ' is-invalid' : '';
    $html = '<div class="mb-3">'
        . '<label class="form-label' . ($batBuoc ? ' required' : '') . '" for="' . e($ten) . '">' . e($nhan) . '</label>'
        . '<select class="form-select' . $saiCss . '" id="' . e($ten) . '" name="' . e($ten) . '"' . ($batBuoc ? ' required' : '') . '>'
        . '<option value="">' . ($batBuoc ? 'Chọn' : 'Tất cả') . '</option>';
    foreach ($luaChon as $giaTri => $nhanLuaChon) {
        $chon = (string) $giaTri === (string) $dangChon ? ' selected' : '';
        $html .= '<option value="' . e((string) $giaTri) . '"' . $chon . '>' . e($nhanLuaChon) . '</option>';
    }
    return $html . '</select><div class="invalid-feedback">' . e($loi[$ten] ?? $loiMacDinh) . '</div></div>';
}


// Khung biểu đồ cho app.js vẽ, dùng ở Thống kê và Dashboard. $bieuDo có dạng mô tả ở đầu phần biểu đồ trong app.js.
// $moTa đọc cho trình đọc màn hình; số liệu đầy đủ luôn nằm ở bảng hoặc trang khác, không chỉ trong hình.
function khungBieuDo(array $bieuDo, string $moTa, string $lopThem = ''): string
{
    return '<div class="khung-bieu-do' . ($lopThem !== '' ? ' ' . e($lopThem) : '') . '">'
        . '<canvas role="img" aria-label="' . e($moTa) . '" data-bieu-do="' . e(json_encode($bieuDo, JSON_UNESCAPED_UNICODE)) . '"></canvas>'
        . '</div>';
}
