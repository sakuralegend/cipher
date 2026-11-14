<?php
// Đăng nhập, phiên làm việc, phân quyền, mã CSRF.

const SO_LAN_SAI_TOI_DA = 5;
const PHUT_TAM_CHAN = 15;
const VAI_TRO_ADMIN = 'Admin';

// Dùng chung cho sai tên, sai mật khẩu và đang tạm chặn, để kẻ dò không phân biệt được.
const LOI_DANG_NHAP = 'Sai tên đăng nhập hoặc mật khẩu. Sai quá 5 lần, tài khoản bị tạm chặn 15 phút.';

// Khi không có người dùng vẫn chạy password_verify trên mã băm này, để thời gian trả lời
// không tiết lộ tên đăng nhập nào tồn tại. Kết quả của lần so sánh này bị bỏ qua.
const MA_BAM_GIA = '$2y$10$EHEJOeJC57qPKDb.wGU5b.SVbUx2J0vJdfshHa16aQ7c45PeH.XPu';

// Chỉ kiểm tra và ghi số lần sai, chưa đụng phiên. Trả về ['id' => ...] hoặc ['loi' => ...].
function dangNhap(PDO $pdo, string $ten, string $matKhau): array
{
    // Chỉ tài khoản còn dùng: tên của tài khoản đã xoá có thể đã được cấp lại cho người khác (QD-26).
    $cau = $pdo->prepare('SELECT id, password_hash, is_locked, locked_until > NOW() AS dang_tam_chan
                          FROM users WHERE username = ? AND deleted_at IS NULL');
    $cau->execute([$ten]);
    $nguoi = $cau->fetch();

    $dungMatKhau = password_verify($matKhau, $nguoi ? $nguoi['password_hash'] : MA_BAM_GIA);

    // Lần sai nào cũng vào nhật ký với người làm để trống (chưa biết là ai). Tên đã gõ cắt ngắn
    // và chỉ được in ra qua e(), nên gõ mã độc vào ô tên cũng không chạy được ở trang Nhật ký.
    if (!$nguoi) {
        ghiNhatKy($pdo, 'dang_nhap_sai', null, null, 'Tên đã gõ: ' . mb_substr($ten, 0, 100));
        return ['loi' => LOI_DANG_NHAP];
    }
    $id = (int) $nguoi['id'];

    // Đang tạm chặn thì bỏ qua kết quả mật khẩu; nếu báo khác đi, kẻ dò vẫn thử tiếp được.
    if ($nguoi['dang_tam_chan']) {
        // Mã riêng (mức Nguy hiểm): đã sai đủ 5 lần mà vẫn thử là dấu hiệu dò mật khẩu.
        ghiNhatKy($pdo, 'dang_nhap_bi_chan', 'nguoi_dung', $id, $ten . ', đang bị tạm chặn');
        return ['loi' => LOI_DANG_NHAP];
    }

    if (!$dungMatKhau) {
        ghiNhanSaiMatKhau($pdo, $id);
        ghiNhatKy($pdo, 'dang_nhap_sai', 'nguoi_dung', $id, $ten . ', sai mật khẩu');
        return ['loi' => LOI_DANG_NHAP];
    }

    // Chỉ người nhập đúng mật khẩu mới biết tài khoản bị khoá.
    if ($nguoi['is_locked']) {
        ghiNhatKy($pdo, 'dang_nhap_sai', 'nguoi_dung', $id, $ten . ', tài khoản đã bị khoá');
        return ['loi' => 'Tài khoản đã bị khoá. Hãy liên hệ Admin.'];
    }

    $pdo->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?')
        ->execute([$id]);
    // Phiên chưa có người dùng ở bước này, nên truyền thẳng người làm.
    ghiNhatKy($pdo, 'dang_nhap', 'nguoi_dung', $id, $ten, $id);

    return ['id' => $id, 'dau_mk' => dauMatKhau($nguoi['password_hash'])];
}

// Dấu của mật khẩu đang dùng, cất trong phiên lúc đăng nhập. Đổi mật khẩu thì mã băm đổi, dấu trong các phiên cũ không
// còn khớp và nguoiDungHienTai() đẩy chúng ra (kiểm thử 12/11/2026: phiên cũ vẫn dùng được sau khi đổi mật khẩu).
// Chỉ cất dấu SHA-256 của mã băm, không cất chính mã băm vào file phiên.
function dauMatKhau(string $maBam): string
{
    return hash('sha256', $maBam);
}

function ghiNhanSaiMatKhau(PDO $pdo, int $id): void
{
    // Tính ngay trong một câu SQL để hai yêu cầu cùng lúc không đếm thiếu.
    // Thứ tự có ý nghĩa: MariaDB gán từ trái sang phải, nên locked_until phải đứng trước
    // khi failed_logins bị đổi.
    $pdo->prepare('UPDATE users
                   SET locked_until = IF(failed_logins + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until),
                       failed_logins = IF(failed_logins + 1 >= ?, 0, failed_logins + 1)
                   WHERE id = ?')
        ->execute([SO_LAN_SAI_TOI_DA, PHUT_TAM_CHAN, SO_LAN_SAI_TOI_DA, $id]);
}

function batDauPhien(): void
{
    session_name('cipher_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true, // JavaScript không đọc được cookie, giảm thiệt hại khi bị XSS.
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // localhost không có HTTPS.
    ]);
    ini_set('session.use_strict_mode', '1'); // Từ chối mã phiên do trình duyệt tự đặt ra.
    session_start();
}

function ghiNhanDangNhap(int $id, string $dauMk): void
{
    // Đổi mã phiên, để mã bị lộ từ trước khi đăng nhập không dùng tiếp được.
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['dau_mk'] = $dauMk;
    unset($_SESSION['csrf']);
}

// Ghi nhật ký trước khi xoá phiên, vì sau đó không còn biết ai đăng xuất.
function dangXuat(PDO $pdo): void
{
    if (!empty($_SESSION['user_id'])) {
        $id = (int) $_SESSION['user_id'];
        ghiNhatKy($pdo, 'dang_xuat', 'nguoi_dung', $id, tenDoiTuong($pdo, 'nguoi_dung', $id));
    }
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $cookie['path'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'],
        'secure' => $cookie['secure'],
    ]);
    session_destroy();
}

// Đọc lại từ CSDL ở mỗi yêu cầu, để người bị khoá giữa phiên, hay đã đổi mật khẩu ở nơi khác, bị đẩy ra ngay.
// Phiên tạo trước khi có dấu mật khẩu (trước 12/11/2026) không có dau_mk nên phải đăng nhập lại một lần.
function nguoiDungHienTai(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id']) || !is_string($_SESSION['dau_mk'] ?? null)) {
        return null;
    }
    $cau = $pdo->prepare('SELECT u.id, u.username, u.full_name, u.password_hash, r.name AS role_name
                          FROM users u JOIN roles r ON r.id = u.role_id
                          WHERE u.id = ? AND u.is_locked = 0 AND u.deleted_at IS NULL');
    $cau->execute([$_SESSION['user_id']]);
    $nguoi = $cau->fetch();
    if (!$nguoi || !hash_equals(dauMatKhau($nguoi['password_hash']), $_SESSION['dau_mk'])) {
        return null;
    }
    unset($nguoi['password_hash']); // Mảng người dùng đi khắp các trang; mã băm không cần ra khỏi hàm này.
    return $nguoi;
}

// Biểu thức SQL cho tên người đã làm một việc (người tạo chứng chỉ, người gia hạn), kèm "(đã xoá)" nếu tài khoản đã bị xoá
// (QD-26). $bi là bí danh bảng users trong câu truy vấn, luôn viết cứng trong code.
function tenNguoiSql(string $bi): string
{
    return "CONCAT($bi.full_name, IF($bi.deleted_at IS NULL, '', ' (đã xoá)'))";
}

function laAdmin(array $nguoi): bool
{
    return $nguoi['role_name'] === VAI_TRO_ADMIN;
}

// Mọi trang trong public/admin/ phải gọi hàm này hoặc yeuCauAdmin() ngay đầu file.
function yeuCauDangNhap(PDO $pdo): array
{
    $nguoi = nguoiDungHienTai($pdo);
    if ($nguoi === null) {
        chuyenHuong('login.php');
    }
    return $nguoi;
}

function yeuCauAdmin(PDO $pdo): array
{
    $nguoi = yeuCauDangNhap($pdo);
    if (!laAdmin($nguoi)) {
        http_response_code(403);
        exit('Bạn không có quyền vào trang này.');
    }
    return $nguoi;
}

function maCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfHopLe(mixed $ma): bool
{
    // hash_equals so sánh hết chuỗi, không để lộ mã qua thời gian trả lời.
    return is_string($ma) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $ma);
}

// Đọc một ô form. Form bị sửa tay có thể gửi mảng (username[]=x) hoặc byte không phải UTF-8;
// coi như ô trống để quy tắc kiểm tra báo lỗi đúng ô, thay vì CSDL từ chối và trang chết.
function chuoiPost(string $khoa): string
{
    $giaTri = $_POST[$khoa] ?? '';
    return is_string($giaTri) && mb_check_encoding($giaTri, 'UTF-8') ? $giaTri : '';
}

// Mọi yêu cầu POST phải gọi hàm này trước khi đổi dữ liệu.
function yeuCauCsrf(): void
{
    if (!csrfHopLe($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Phiên làm việc đã hết hạn. Hãy tải lại trang rồi thử lại.');
    }
}
