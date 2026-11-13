<?php
// Nhật ký hoạt động (chức năng mở rộng 1): ai làm gì, với đối tượng nào, lúc nào.
// Các hàm ghi dữ liệu trong src/ tự gọi ghiNhatKy() ngay sau khi thao tác thành công, nên trang nào
// gọi chúng cũng được ghi. Nhật ký chỉ thêm được: CSDL từ chối sửa, xoá (trigger trong schema.sql).

const NHAT_KY_MOI_TRANG = 50;

// Nhãn và màu nhãn (lớp bg-*-lt của Tabler) cho từng hành động lưu ở cột action.
const HANH_DONG = [
    'them' => ['nhan' => 'Thêm', 'mau' => 'green'],
    'sua' => ['nhan' => 'Sửa', 'mau' => 'blue'],
    'xoa' => ['nhan' => 'Xoá', 'mau' => 'red'],
    'gia_han' => ['nhan' => 'Gia hạn', 'mau' => 'green'],
    'thu_hoi' => ['nhan' => 'Thu hồi', 'mau' => 'orange'],
    'khoa' => ['nhan' => 'Khoá', 'mau' => 'orange'],
    'mo_khoa' => ['nhan' => 'Mở khoá', 'mau' => 'blue'],
    'doi_mat_khau' => ['nhan' => 'Đổi mật khẩu', 'mau' => 'blue'],
    'dang_nhap' => ['nhan' => 'Đăng nhập', 'mau' => 'secondary'],
    'dang_xuat' => ['nhan' => 'Đăng xuất', 'mau' => 'secondary'],
    'dang_nhap_sai' => ['nhan' => 'Đăng nhập sai', 'mau' => 'red'],
    // Đọc chứng chỉ từ máy chủ ngoài (form Thêm chứng chỉ): chi tiết ghi địa chỉ đã đọc và kết quả,
    // để biết ai đã cho máy chủ kết nối đi đâu.
    'doc_chung_chi' => ['nhan' => 'Đọc chứng chỉ', 'mau' => 'blue'],
];

// Loại đối tượng lưu ở cột object_type: bảng, cột dùng làm tên, trang xem hoặc sửa đối tượng.
const LOAI_DOI_TUONG = [
    'chung_chi' => ['nhan' => 'Chứng chỉ', 'bang' => 'certificates', 'cot' => 'cert_code', 'trang' => 'admin/chung_chi_xem.php?id='],
    'may_chu' => ['nhan' => 'Máy chủ', 'bang' => 'servers', 'cot' => 'hostname', 'trang' => 'admin/may_chu_form.php?id='],
    'ten_mien' => ['nhan' => 'Tên miền', 'bang' => 'domains', 'cot' => 'domain_name', 'trang' => 'admin/ten_mien_form.php?id='],
    'to_chuc_cap' => ['nhan' => 'Tổ chức cấp', 'bang' => 'certificate_authorities', 'cot' => 'name', 'trang' => 'admin/to_chuc_cap_form.php?id='],
    'nguoi_dung' => ['nhan' => 'Người dùng', 'bang' => 'users', 'cot' => 'username', 'trang' => 'admin/nguoi_dung_form.php?id='],
];

// $idNguoi: người làm; để null thì lấy người đang đăng nhập trong phiên (không có ai thì để trống,
// ví dụ đăng nhập sai). Chi tiết lưu tên đối tượng lúc đó, vì đối tượng có thể bị đổi tên hay xoá sau này.
// IP lấy từ máy chủ web (REMOTE_ADDR). Trên VPS khách đi qua một cổng trung chuyển ở 443 rồi mới tới Caddy; từ
// 08/11/2026 cổng đó gửi kèm IP gốc bằng PROXY protocol nên đây là IP thật của khách (dòng cũ hơn ghi 127.0.0.1). Không đọc header
// X-Forwarded-For: khách tự gõ được header đó để giả IP.
function ghiNhatKy(PDO $pdo, string $hanhDong, ?string $loai = null, ?int $idDoiTuong = null, ?string $chiTiet = null, ?int $idNguoi = null): void
{
    $idNguoi ??= isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $pdo->prepare('INSERT INTO audit_logs (user_id, action, object_type, object_id, detail, ip) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([
            $idNguoi,
            $hanhDong,
            $loai,
            $idDoiTuong,
            $chiTiet === null ? null : mb_substr($chiTiet, 0, 500),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
}

// Tên hiện tại của đối tượng (mã chứng chỉ, tên máy chủ...), null nếu không còn.
function tenDoiTuong(PDO $pdo, string $loai, int $id): ?string
{
    $l = LOAI_DOI_TUONG[$loai];
    $cau = $pdo->prepare("SELECT {$l['cot']} FROM {$l['bang']} WHERE id = ?");
    $cau->execute([$id]);
    $ten = $cau->fetchColumn();
    return $ten === false ? null : (string) $ten;
}

// Gọi ngay sau câu INSERT hoặc UPDATE thành công của các hàm lưu. $id null là vừa thêm mới:
// mã lấy từ lastInsertId(), nên không được chạy câu INSERT nào khác xen giữa.
function ghiNhatKyLuu(PDO $pdo, ?int $id, string $loai, string $themChiTiet = ''): void
{
    $idDoiTuong = $id ?? (int) $pdo->lastInsertId();
    ghiNhatKy($pdo, $id === null ? 'them' : 'sua', $loai, $idDoiTuong, tenDoiTuong($pdo, $loai, $idDoiTuong) . $themChiTiet);
}

// Xoá như xoaHoacBaoConDuLieu() và ghi nhật ký khi xoá được. Tên phải đọc trước khi xoá, vì sau đó không còn.
function xoaVaGhiNhatKy(PDO $pdo, string $loai, int $id, string $thongBao): ?string
{
    $ten = tenDoiTuong($pdo, $loai, $id);
    $loi = xoaHoacBaoConDuLieu($pdo, LOAI_DOI_TUONG[$loai]['bang'], $id, $thongBao);
    if ($loi === null) {
        ghiNhatKy($pdo, 'xoa', $loai, $id, $ten);
    }
    return $loi;
}

// ---------- Xem nhật ký ----------

// Đọc bộ lọc từ địa chỉ trang. Giá trị sai bị coi như chưa chọn (giống bộ lọc chứng chỉ).
// nguoi: mã người dùng, hoặc 'khong_ro' cho các dòng không có người (đăng nhập sai).
function docLocNhatKy(array $nguon): array
{
    $chuoi = fn(string $khoa): string => is_string($nguon[$khoa] ?? null) ? trim($nguon[$khoa]) : '';
    $loc = [
        'nguoi' => $chuoi('nguoi'),
        'hanh_dong' => $chuoi('hanh_dong'),
        'tu_ngay' => $chuoi('tu_ngay'),
        'den_ngay' => $chuoi('den_ngay'),
    ];
    if ($loc['nguoi'] !== 'khong_ro' && !ctype_digit($loc['nguoi'])) {
        $loc['nguoi'] = '';
    }
    if (!isset(HANH_DONG[$loc['hanh_dong']])) {
        $loc['hanh_dong'] = '';
    }
    foreach (['tu_ngay', 'den_ngay'] as $o) {
        $ngay = DateTime::createFromFormat('!Y-m-d', $loc[$o]);
        if ($ngay === false || $ngay->format('Y-m-d') !== $loc[$o]) {
            $loc[$o] = '';
        }
    }
    return $loc;
}

// Trả về ['dong' => các dòng của trang $trang (tính từ 1), 'tong' => tổng số dòng khớp bộ lọc].
// Mỗi dòng có thêm con_ton_tai: đối tượng còn trong CSDL không, để trang chỉ đặt link khi còn.
function layNhatKy(PDO $pdo, array $loc, int $trang): array
{
    $dieuKien = [];
    $thamSo = [];
    if ($loc['nguoi'] === 'khong_ro') {
        $dieuKien[] = 'a.user_id IS NULL';
    } elseif ($loc['nguoi'] !== '') {
        $dieuKien[] = 'a.user_id = ?';
        $thamSo[] = (int) $loc['nguoi'];
    }
    if ($loc['hanh_dong'] !== '') {
        $dieuKien[] = 'a.action = ?';
        $thamSo[] = $loc['hanh_dong'];
    }
    if ($loc['tu_ngay'] !== '') {
        $dieuKien[] = 'a.created_at >= ?';
        $thamSo[] = $loc['tu_ngay'] . ' 00:00:00';
    }
    if ($loc['den_ngay'] !== '') {
        // Hết ngày "đến": nhỏ hơn 0 giờ của ngày hôm sau.
        $dieuKien[] = 'a.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
        $thamSo[] = $loc['den_ngay'];
    }
    $where = $dieuKien === [] ? '' : 'WHERE ' . implode(' AND ', $dieuKien);

    $cau = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a $where");
    $cau->execute($thamSo);
    $tong = (int) $cau->fetchColumn();

    // Hai số LIMIT, OFFSET là số nguyên do code tính ra, không lấy từ người dùng.
    $boQua = (max(1, $trang) - 1) * NHAT_KY_MOI_TRANG;
    // nguoi_da_xoa: người làm đã bị xoá tài khoản (QD-26); dòng nhật ký vẫn còn, trang ghi thêm "đã xoá".
    $cau = $pdo->prepare("SELECT a.*, u.username, u.full_name, u.deleted_at IS NOT NULL AS nguoi_da_xoa
                          FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                          $where ORDER BY a.id DESC LIMIT " . NHAT_KY_MOI_TRANG . " OFFSET $boQua");
    $cau->execute($thamSo);
    $dong = $cau->fetchAll();

    // Mỗi loại đối tượng một câu truy vấn, thay vì một câu cho mỗi dòng.
    $cacId = [];
    foreach ($dong as $d) {
        if (isset(LOAI_DOI_TUONG[$d['object_type']]) && $d['object_id'] !== null) {
            $cacId[$d['object_type']][] = (int) $d['object_id'];
        }
    }
    $conTonTai = [];
    foreach ($cacId as $loai => $ids) {
        $ids = array_values(array_unique($ids));
        $dauHoi = implode(',', array_fill(0, count($ids), '?'));
        // Người dùng đã xoá vẫn còn dòng trong users nhưng không mở được trang sửa: coi như không còn, không đặt link.
        $cau = $pdo->prepare('SELECT id FROM ' . LOAI_DOI_TUONG[$loai]['bang'] . " WHERE id IN ($dauHoi)"
            . ($loai === 'nguoi_dung' ? ' AND deleted_at IS NULL' : ''));
        $cau->execute($ids);
        foreach ($cau->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $conTonTai[$loai][(int) $id] = true;
        }
    }
    foreach ($dong as &$d) {
        $d['con_ton_tai'] = isset($conTonTai[$d['object_type']][(int) $d['object_id']]);
    }
    unset($d);

    return ['dong' => $dong, 'tong' => $tong];
}
