<?php
// Cửa duy nhất tới CSDL: mọi nơi cần CSDL đều gọi ketNoiCsdl(), không tự tạo PDO.
// Máy khác cấu hình khác (VPS) thì tạo config/config.local.php, file này không vào git:
//   <?php return ['user' => 'cipher', 'pass' => '...'];

// Số ngày còn lại phụ thuộc giờ hiện tại, nên múi giờ đặt trong code, không dựa php.ini.
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Mã lỗi của MariaDB, để phân biệt nguyên nhân khi CSDL từ chối.
const LOI_CSDL_TRUNG_KHOA = 1062;
const LOI_CSDL_CON_DU_LIEU_CON = 1451;

// Trùng giá trị thì trả về lỗi cho đúng ô; lỗi khác là lỗi hệ thống nên ném tiếp.
// UNIQUE trong CSDL là nơi quyết định, vì hai yêu cầu cùng lúc vẫn qua được kiểm tra bằng PHP.
function loiNeuTrung(PDOException $e, string $o, string $thongBao): array
{
    if (($e->errorInfo[1] ?? null) === LOI_CSDL_TRUNG_KHOA) {
        return [$o => $thongBao];
    }
    throw $e;
}

// Xoá một dòng và để khoá ngoại quyết định có được xoá không. Không đếm dữ liệu con trước:
// thêm bảng mới có khoá ngoại thì quy tắc vẫn đúng mà không phải sửa ở đây.
// $bang chỉ nhận tên bảng viết cứng trong code, không bao giờ lấy từ người dùng.
function xoaHoacBaoConDuLieu(PDO $pdo, string $bang, int $id, string $thongBao): ?string
{
    try {
        $cau = $pdo->prepare("DELETE FROM $bang WHERE id = ?");
        $cau->execute([$id]);
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) === LOI_CSDL_CON_DU_LIEU_CON) {
            return $thongBao;
        }
        throw $e;
    }
    // Không có dòng nào bị xoá, ví dụ người khác vừa xoá: không được báo "đã xoá".
    return $cau->rowCount() === 0 ? 'Bản ghi này không còn tồn tại.' : null;
}

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
