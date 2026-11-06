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

// Form gửi lên có thể là mảng (username[]=x) nếu bị sửa tay, nên chỉ nhận chuỗi.
function chuoiPost(string $khoa): string
{
    $giaTri = $_POST[$khoa] ?? '';
    return is_string($giaTri) ? $giaTri : '';
}
