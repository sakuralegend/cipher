<?php
// Cửa duy nhất tới CSDL: mọi nơi cần CSDL đều gọi ketNoiCsdl(), không tự tạo PDO.
// Máy khác cấu hình khác (VPS) thì tạo config/config.local.php, file này không vào git:
//   <?php return ['user' => 'cipher', 'pass' => '...'];

// Số ngày còn lại phụ thuộc giờ hiện tại, nên múi giờ đặt trong code, không dựa php.ini.
date_default_timezone_set('Asia/Ho_Chi_Minh');

function ketNoiCsdl(?string $tenCsdl = null): PDO
{
    $cauHinh = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'cipher_dev',
        'user' => 'root',
        'pass' => '',
    ];

    $fileRieng = __DIR__ . '/config.local.php';
    if (is_file($fileRieng)) {
        $cauHinh = array_merge($cauHinh, require $fileRieng);
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $cauHinh['host'],
        $cauHinh['port'],
        $tenCsdl ?? $cauHinh['dbname']
    );

    $pdo = new PDO($dsn, $cauHinh['user'], $cauHinh['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Dùng Prepared Statements thật của CSDL.
    ]);

    // CSDL XAMPP chưa nạp bảng tên múi giờ, nên phải dùng độ lệch thay vì 'Asia/Ho_Chi_Minh'.
    $pdo->exec("SET time_zone = '+07:00'");
    // XAMPP mặc định không strict: chuỗi quá dài bị cắt bớt mà không báo lỗi.
    $pdo->exec("SET sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

    return $pdo;
}
