<?php
require __DIR__ . '/../src/khoi_dong.php';

// Chỉ nhận POST kèm mã CSRF, để trang lạ không đăng xuất người dùng bằng một đường link.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Chỉ đăng xuất bằng nút trên trang.');
}

yeuCauCsrf();
$pdo = ketNoiCsdl();
dangXuat($pdo);
// Về landing (user chốt 07/11/2026); muốn vào lại thì landing có sẵn nút Đăng nhập.
chuyenHuong('index.php');
