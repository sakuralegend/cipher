<?php
// Mọi trang web gọi file này đầu tiên. Test không gọi, vì test không có trình duyệt và phiên.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
// Các hàm ghi dữ liệu ở src/ đều gọi ghiNhatKy(), nên nạp sẵn cho mọi trang.
require_once __DIR__ . '/nhat_ky.php';

batDauPhien();

// Chạy bằng php -S (CHAY.bat): mỗi trang mở in một dòng ra cửa sổ (inRaManHinh ở nhat_ky.php): cách gửi, địa chỉ, mã trả về,
// thời gian xử lý, người. In lúc trang chạy xong để có mã trả về thật (302 khi chuyển hướng, 403 khi không đủ quyền).
if (PHP_SAPI === 'cli-server') {
    register_shutdown_function(function (): void {
        $id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        try {
            $nguoi = tenDangNhap(ketNoiCsdl(), $id);
        } catch (Throwable) {
            $nguoi = $id === null ? 'khach' : "#$id"; // CSDL hỏng thì vẫn in được dòng này.
        }
        // Gửi form bị từ chối: các trang gom lỗi vào $loi (mảng theo ô, hoặc một câu), đọc chứng chỉ hỏng vào $loiDoc,
        // lỗi thao tác rồi chuyển trang thì nằm ở thông báo đỏ. Đăng nhập sai bỏ qua: ghiNhatKy() đã in dòng *.
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'login.php') {
            $loi = $GLOBALS['loi'] ?? null;
            $cacLoi = is_array($loi) ? $loi : (is_string($loi) && $loi !== '' ? [$loi] : []);
            if (is_string($GLOBALS['loiDoc'] ?? null)) {
                $cacLoi['doc'] = $GLOBALS['loiDoc'];
            }
            if (($_SESSION['thong_bao']['loai'] ?? '') === 'danger') {
                $cacLoi[] = $_SESSION['thong_bao']['noi_dung'];
            }
            if ($cacLoi !== []) {
                $tung = array_map(fn($o, $cau) => is_int($o) ? $cau : "o $o: $cau", array_keys($cacLoi), $cacLoi);
                inRaManHinh("! $nguoi: bi tu choi - " . implode(' | ', $tung));
            }
        }
        $ms = (int) round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
        inRaManHinh(sprintf('%-4s %-42s %3d %5d ms  %s', $_SERVER['REQUEST_METHOD'] ?? '', $_SERVER['REQUEST_URI'] ?? '',
            http_response_code(), $ms, $nguoi));
    });
}

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


// Ô gõ có gợi ý từ danh mục [mã => tên], dùng datalist có sẵn của trình duyệt (không thêm thư viện): gõ vài chữ
// là hiện tên khớp để chọn. Gửi đi TÊN; trang nhận tra ra mã bằng idTheoTen() (src/danh_muc.php), tên lạ thì báo
// lỗi chứ không tự tạo. Không gộp vào oChon(): bộ lọc vẫn cần ô chọn có dòng "Tất cả".
// data-trong-danh-sach: app.js báo lỗi ngay khi tên gõ vào không có trong danh sách.
// $goiYHtml là HTML đã thoát sẵn (có thể chứa link), in dưới ô.
function oGoiY(string $ten, string $nhan, array $danhMuc, string $giaTri, array $loi, string $loiMacDinh, string $goiYHtml = ''): string
{
    $html = '<div class="mb-3">'
        . '<label class="form-label required" for="' . e($ten) . '">' . e($nhan) . '</label>'
        . '<input class="form-control' . (isset($loi[$ten]) ? ' is-invalid' : '') . '" id="' . e($ten) . '" name="' . e($ten) . '"'
        . ' value="' . e($giaTri) . '" list="ds-' . e($ten) . '" required maxlength="255" autocomplete="off"'
        . ' placeholder="Gõ để tìm" data-trong-danh-sach>'
        . '<datalist id="ds-' . e($ten) . '">';
    foreach ($danhMuc as $tenMuc) {
        $html .= '<option value="' . e((string) $tenMuc) . '"></option>';
    }
    $html .= '</datalist><div class="invalid-feedback">' . e($loi[$ten] ?? $loiMacDinh) . '</div>';
    if ($goiYHtml !== '') {
        $html .= '<small class="form-hint">' . $goiYHtml . '</small>';
    }
    return $html . '</div>';
}

// Khung biểu đồ cho app.js vẽ, dùng ở Thống kê và Dashboard. $bieuDo có dạng mô tả ở đầu phần biểu đồ trong app.js.
// $moTa đọc cho trình đọc màn hình; số liệu đầy đủ luôn nằm ở bảng hoặc trang khác, không chỉ trong hình.
function khungBieuDo(array $bieuDo, string $moTa, string $lopThem = ''): string
{
    return '<div class="khung-bieu-do' . ($lopThem !== '' ? ' ' . e($lopThem) : '') . '">'
        . '<canvas role="img" aria-label="' . e($moTa) . '" data-bieu-do="' . e(json_encode($bieuDo, JSON_UNESCAPED_UNICODE)) . '"></canvas>'
        . '</div>';
}
