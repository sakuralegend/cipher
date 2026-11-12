<?php
// Quy tắc quản lý người dùng. Các trang chỉ đọc form, gọi hàm ở đây, rồi hiển thị kết quả;
// test cũng gọi trực tiếp các hàm này, không cần trình duyệt.
//
// Hàm trả về mảng lỗi dạng ['tên ô' => 'câu lỗi']; mảng rỗng nghĩa là thành công.
// Hàm thao tác trên cả dòng (khoá, xoá) trả về câu lỗi, hoặc null nếu thành công.

function danhSachVaiTro(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();
}

// Mặc định chỉ tài khoản còn dùng (trang Người dùng). $kemDaXoa = true cho bộ lọc Nhật ký: việc người đã xoá từng làm
// vẫn phải lọc được (QD-26).
function danhSachNguoiDung(PDO $pdo, bool $kemDaXoa = false): array
{
    // dang_tam_chan để giao diện phân biệt "bị Admin khoá" với "tạm chặn do sai mật khẩu".
    return $pdo->query('SELECT u.id, u.username, u.full_name, u.role_id, u.is_locked, u.deleted_at, r.name AS role_name,
                               COALESCE(u.locked_until > NOW(), 0) AS dang_tam_chan
                        FROM users u JOIN roles r ON r.id = u.role_id'
                        . ($kemDaXoa ? '' : ' WHERE u.deleted_at IS NULL') . '
                        ORDER BY u.id')->fetchAll();
}

// Tài khoản đã xoá coi như không còn: không sửa, khoá, xoá lại được.
function timNguoiDung(PDO $pdo, int $id): ?array
{
    $cau = $pdo->prepare('SELECT id, username, full_name, role_id, is_locked FROM users WHERE id = ? AND deleted_at IS NULL');
    $cau->execute([$id]);
    return $cau->fetch() ?: null;
}

// Luật mật khẩu mới (user chốt 12/11/2026: "8 ký tự, có chữ và số"; trước đó "abcdefgh" vẫn qua). MAU_MAT_KHAU là cùng luật
// cho thuộc tính pattern của ô nhập, để trình duyệt báo ngay; PHP vẫn là lớp quyết định.
const LUAT_MAT_KHAU = 'Mật khẩu phải có ít nhất 8 ký tự, gồm cả chữ và số.';
const MAU_MAT_KHAU = '(?=.*\p{L})(?=.*\d).{8,}';

// Tách riêng vì cả form Admin lẫn trang tài khoản cá nhân đều đặt mật khẩu.
function kiemTraMatKhauMoi(string $matKhau, string $nhapLai): ?string
{
    // \p{L}: chữ cái mọi thứ tiếng, kể cả có dấu; \d: chữ số.
    if (mb_strlen($matKhau) < 8 || preg_match('/\p{L}/u', $matKhau) !== 1 || preg_match('/\d/', $matKhau) !== 1) {
        return LUAT_MAT_KHAU;
    }
    if (strlen($matKhau) > 72) {
        // bcrypt chỉ dùng 72 byte đầu; phần dư sẽ bị bỏ qua mà không báo lỗi.
        return 'Mật khẩu quá dài, tối đa 72 ký tự không dấu.';
    }
    if ($matKhau !== $nhapLai) {
        return 'Hai lần nhập mật khẩu không giống nhau.';
    }
    return null;
}

function kiemTraHoTen(string $hoTen): ?string
{
    if ($hoTen === '' || mb_strlen($hoTen) > 100) {
        return 'Họ tên là bắt buộc, tối đa 100 ký tự.';
    }
    return null;
}

// $duLieu gồm: username, full_name, role_id, password, password2.
function kiemTraNguoiDung(PDO $pdo, array $duLieu, bool $batBuocMatKhau): array
{
    $loi = [];

    // Cờ D: dấu $ chỉ khớp đúng cuối chuỗi, không khớp trước ký tự xuống dòng.
    if (preg_match('/^[A-Za-z0-9._-]{3,50}$/D', $duLieu['username']) !== 1) {
        $loi['username'] = 'Tên đăng nhập gồm 3–50 ký tự: chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.';
    }

    $loiHoTen = kiemTraHoTen($duLieu['full_name']);
    if ($loiHoTen !== null) {
        $loi['full_name'] = $loiHoTen;
    }

    $cau = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE id = ?');
    $cau->execute([$duLieu['role_id']]);
    if ((int) $cau->fetchColumn() === 0) {
        $loi['role_id'] = 'Hãy chọn vai trò.';
    }

    // Khi sửa, để trống mật khẩu nghĩa là giữ nguyên.
    if ($batBuocMatKhau || $duLieu['password'] !== '') {
        $loiMatKhau = kiemTraMatKhauMoi($duLieu['password'], $duLieu['password2']);
        if ($loiMatKhau !== null) {
            $loi['password'] = $loiMatKhau;
        }
    }

    return $loi;
}

function themNguoiDung(PDO $pdo, array $duLieu): array
{
    $loi = kiemTraNguoiDung($pdo, $duLieu, true);
    if ($loi !== []) {
        return $loi;
    }

    try {
        $pdo->prepare('INSERT INTO users (username, password_hash, full_name, role_id) VALUES (?, ?, ?, ?)')
            ->execute([
                $duLieu['username'],
                password_hash($duLieu['password'], PASSWORD_DEFAULT),
                $duLieu['full_name'],
                $duLieu['role_id'],
            ]);
        ghiNhatKyLuu($pdo, null, 'nguoi_dung');
    } catch (PDOException $e) {
        return loiTrungTenDangNhap($e);
    }
    return [];
}

function suaNguoiDung(PDO $pdo, int $id, array $duLieu, int $idNguoiThucHien): array
{
    $loi = kiemTraNguoiDung($pdo, $duLieu, false);

    $hienTai = timNguoiDung($pdo, $id);
    if ($hienTai === null) {
        return ['username' => 'Người dùng không còn tồn tại.'];
    }
    // Admin không tự hạ quyền mình; nhờ đó hệ thống luôn còn ít nhất một Admin.
    if ($id === $idNguoiThucHien && (int) $duLieu['role_id'] !== (int) $hienTai['role_id']) {
        $loi['role_id'] = 'Bạn không thể tự đổi vai trò của mình.';
    }
    if ($loi !== []) {
        return $loi;
    }

    try {
        $pdo->prepare('UPDATE users SET username = ?, full_name = ?, role_id = ? WHERE id = ?')
            ->execute([$duLieu['username'], $duLieu['full_name'], $duLieu['role_id'], $id]);
    } catch (PDOException $e) {
        return loiTrungTenDangNhap($e);
    }

    // Hệ thống không có "quên mật khẩu", nên đây là đường duy nhất để cứu một tài khoản.
    if ($duLieu['password'] !== '') {
        capNhatMatKhau($pdo, $id, $duLieu['password']);
    }
    ghiNhatKyLuu($pdo, $id, 'nguoi_dung', $duLieu['password'] !== '' ? ', đặt mật khẩu mới' : '');
    return [];
}

function loiTrungTenDangNhap(PDOException $e): array
{
    return loiNeuTrung($e, 'username', 'Tên đăng nhập này đã tồn tại.');
}

// Mật khẩu đổi thì mọi phiên đang mở của người này bị đẩy ra (dauMatKhau trong src/auth.php), trừ phiên đang đổi
// cho chính mình: phiên đó nhận dấu mới để không phải đăng nhập lại.
function capNhatMatKhau(PDO $pdo, int $id, string $matKhau): void
{
    $maBam = password_hash($matKhau, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$maBam, $id]);
    if ((int) ($_SESSION['user_id'] ?? 0) === $id) {
        $_SESSION['dau_mk'] = dauMatKhau($maBam);
    }
}

function khoaNguoiDung(PDO $pdo, int $id, int $idNguoiThucHien): ?string
{
    if ($id === $idNguoiThucHien) {
        return 'Bạn không thể tự khoá tài khoản của mình.';
    }
    if (timNguoiDung($pdo, $id) === null) {
        return 'Người dùng không còn tồn tại.';
    }
    $pdo->prepare('UPDATE users SET is_locked = 1 WHERE id = ?')->execute([$id]);
    ghiNhatKy($pdo, 'khoa', 'nguoi_dung', $id, tenDoiTuong($pdo, 'nguoi_dung', $id));
    return null;
}

function moKhoaNguoiDung(PDO $pdo, int $id): void
{
    // Xoá luôn trạng thái tạm chặn, để người dùng vào được ngay sau khi Admin mở khoá.
    $pdo->prepare('UPDATE users SET is_locked = 0, failed_logins = 0, locked_until = NULL WHERE id = ? AND deleted_at IS NULL')
        ->execute([$id]);
    ghiNhatKy($pdo, 'mo_khoa', 'nguoi_dung', $id, tenDoiTuong($pdo, 'nguoi_dung', $id));
}

// Xoá = đánh dấu deleted_at (QD-26, user chốt 12/11/2026): dòng còn lại để nhật ký, chứng chỉ, lịch sử gia hạn vẫn biết
// ai đã làm; khoá ngoại từ ba bảng đó cũng không cho xoá hẳn. Trước đó người đã có lịch sử chỉ khoá được.
// Mã băm mật khẩu thay bằng '!' (password_verify không bao giờ khớp): một câu truy vấn lỡ quên điều kiện deleted_at cũng
// không mở được đường đăng nhập, và phiên đang mở bị đẩy ra vì dấu mật khẩu đổi (nguoiDungHienTai trong src/auth.php).
function xoaNguoiDung(PDO $pdo, int $id, int $idNguoiThucHien): ?string
{
    if ($id === $idNguoiThucHien) {
        return 'Bạn không thể tự xoá tài khoản của mình.';
    }
    $ten = tenDoiTuong($pdo, 'nguoi_dung', $id);
    $cau = $pdo->prepare("UPDATE users SET deleted_at = NOW(), password_hash = '!', failed_logins = 0, locked_until = NULL
                          WHERE id = ? AND deleted_at IS NULL");
    $cau->execute([$id]);
    if ($cau->rowCount() === 0) {
        return 'Người dùng không còn tồn tại.';
    }
    ghiNhatKy($pdo, 'xoa', 'nguoi_dung', $id, $ten);
    return null;
}

function suaHoTen(PDO $pdo, int $id, string $hoTen): array
{
    $loi = kiemTraHoTen($hoTen);
    if ($loi !== null) {
        return ['full_name' => $loi];
    }
    $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?')->execute([$hoTen, $id]);
    ghiNhatKyLuu($pdo, $id, 'nguoi_dung', ', đổi họ tên');
    return [];
}

function doiMatKhau(PDO $pdo, int $id, string $matKhauCu, string $matKhauMoi, string $nhapLai): array
{
    $cau = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $cau->execute([$id]);
    $maBam = $cau->fetchColumn();

    // Người khác ngồi vào máy đang đăng nhập cũng không đổi được mật khẩu.
    if ($maBam === false || !password_verify($matKhauCu, $maBam)) {
        return ['password_cu' => 'Mật khẩu hiện tại không đúng.'];
    }

    $loi = kiemTraMatKhauMoi($matKhauMoi, $nhapLai);
    if ($loi !== null) {
        return ['password' => $loi];
    }

    capNhatMatKhau($pdo, $id, $matKhauMoi);
    ghiNhatKy($pdo, 'doi_mat_khau', 'nguoi_dung', $id, tenDoiTuong($pdo, 'nguoi_dung', $id));
    return [];
}
