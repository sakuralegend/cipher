<?php
// Quy tắc của 3 danh mục: máy chủ, tên miền, tổ chức cấp. Chứng chỉ sẽ chọn từ các danh mục này.
// Cùng cách làm với src/nguoi_dung.php: hàm lưu trả về mảng lỗi ['tên ô' => 'câu lỗi'],
// mảng rỗng là thành công; hàm xoá trả về câu lỗi, hoặc null nếu thành công.

const MOI_TRUONG = [
    'production' => 'Đang chạy thật',
    'staging' => 'Thử nghiệm',
    'development' => 'Phát triển',
];

// ---------- Máy chủ ----------

function danhSachMayChu(PDO $pdo): array
{
    return $pdo->query('SELECT s.*, COUNT(d.id) AS so_ten_mien
                        FROM servers s LEFT JOIN domains d ON d.server_id = s.id
                        GROUP BY s.id ORDER BY s.hostname')->fetchAll();
}

function timMayChu(PDO $pdo, int $id): ?array
{
    $cau = $pdo->prepare('SELECT * FROM servers WHERE id = ?');
    $cau->execute([$id]);
    return $cau->fetch() ?: null;
}

function kiemTraMayChu(array $d): array
{
    $loi = [];
    if (preg_match('/^[A-Za-z0-9._-]{1,255}$/D', $d['hostname']) !== 1) {
        $loi['hostname'] = 'Tên máy chủ là bắt buộc, gồm chữ không dấu, số, dấu chấm, gạch dưới, gạch ngang.';
    }
    // filter_var nhận cả IPv4 lẫn IPv6, và tự loại các giá trị như 999.1.1.1.
    if (filter_var($d['ip_address'], FILTER_VALIDATE_IP) === false) {
        $loi['ip_address'] = 'Địa chỉ IP không hợp lệ.';
    }
    if (filter_var($d['port'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
        $loi['port'] = 'Cổng phải là số từ 1 đến 65535.';
    }
    if (!array_key_exists($d['environment'], MOI_TRUONG)) {
        $loi['environment'] = 'Hãy chọn môi trường.';
    }
    if ($d['owner'] === '' || mb_strlen($d['owner']) > 100) {
        $loi['owner'] = 'Người phụ trách là bắt buộc, tối đa 100 ký tự.';
    }
    return $loi;
}

// $id là null khi thêm mới.
function luuMayChu(PDO $pdo, ?int $id, array $d): array
{
    $loi = kiemTraMayChu($d);
    if ($loi !== []) {
        return $loi;
    }
    $giaTri = [$d['hostname'], $d['ip_address'], (int) $d['port'], $d['environment'], $d['owner']];
    if ($id === null) {
        $pdo->prepare('INSERT INTO servers (hostname, ip_address, port, environment, owner) VALUES (?, ?, ?, ?, ?)')
            ->execute($giaTri);
    } else {
        $pdo->prepare('UPDATE servers SET hostname = ?, ip_address = ?, port = ?, environment = ?, owner = ? WHERE id = ?')
            ->execute([...$giaTri, $id]);
    }
    ghiNhatKyLuu($pdo, $id, 'may_chu');
    return [];
}

function xoaMayChu(PDO $pdo, int $id): ?string
{
    return xoaVaGhiNhatKy($pdo, 'may_chu', $id, 'Máy chủ này còn tên miền, hãy chuyển hoặc xoá tên miền trước.');
}

// ---------- Tên miền ----------

function danhSachTenMien(PDO $pdo): array
{
    return $pdo->query('SELECT d.*, s.hostname, COUNT(c.id) AS so_chung_chi
                        FROM domains d
                        JOIN servers s ON s.id = d.server_id
                        LEFT JOIN certificates c ON c.domain_id = d.id
                        GROUP BY d.id ORDER BY d.domain_name')->fetchAll();
}

function timTenMien(PDO $pdo, int $id): ?array
{
    $cau = $pdo->prepare('SELECT * FROM domains WHERE id = ?');
    $cau->execute([$id]);
    return $cau->fetch() ?: null;
}

// Chấp nhận cert.cipher.id.vn và *.cipher.id.vn. Từ chối tên không có dấu chấm và địa chỉ IP.
function tenMienHopLe(string $ten): bool
{
    $nhan = '[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?';
    return strlen($ten) <= 253
        && preg_match("/^(\*\.)?($nhan\.)+[a-z]([a-z0-9-]{0,61}[a-z0-9])$/D", $ten) === 1;
}

function luuTenMien(PDO $pdo, ?int $id, array $d): array
{
    // Tên miền không phân biệt hoa thường: lưu chữ thường để CERT.vn và cert.vn không thành hai dòng.
    $ten = strtolower($d['domain_name']);
    $loi = [];
    if (!tenMienHopLe($ten)) {
        $loi['domain_name'] = 'Tên miền không hợp lệ. Ví dụ đúng: cert.cipher.id.vn hoặc *.cipher.id.vn';
    }
    if (timMayChu($pdo, (int) $d['server_id']) === null) {
        $loi['server_id'] = 'Hãy chọn máy chủ.';
    }
    if ($loi !== []) {
        return $loi;
    }

    try {
        if ($id === null) {
            $pdo->prepare('INSERT INTO domains (domain_name, server_id) VALUES (?, ?)')
                ->execute([$ten, (int) $d['server_id']]);
        } else {
            $pdo->prepare('UPDATE domains SET domain_name = ?, server_id = ? WHERE id = ?')
                ->execute([$ten, (int) $d['server_id'], $id]);
        }
        ghiNhatKyLuu($pdo, $id, 'ten_mien');
    } catch (PDOException $e) {
        return loiNeuTrung($e, 'domain_name', 'Tên miền này đã tồn tại.');
    }
    return [];
}

function xoaTenMien(PDO $pdo, int $id): ?string
{
    return xoaVaGhiNhatKy($pdo, 'ten_mien', $id, 'Tên miền này còn chứng chỉ, hãy chuyển hoặc xoá chứng chỉ trước.');
}

// ---------- Tổ chức cấp ----------

function danhSachToChuc(PDO $pdo): array
{
    return $pdo->query('SELECT a.*, COUNT(c.id) AS so_chung_chi
                        FROM certificate_authorities a LEFT JOIN certificates c ON c.ca_id = a.id
                        GROUP BY a.id ORDER BY a.name')->fetchAll();
}

function timToChuc(PDO $pdo, int $id): ?array
{
    $cau = $pdo->prepare('SELECT * FROM certificate_authorities WHERE id = ?');
    $cau->execute([$id]);
    return $cau->fetch() ?: null;
}

function luuToChuc(PDO $pdo, ?int $id, array $d): array
{
    $loi = [];
    if ($d['name'] === '' || mb_strlen($d['name']) > 100) {
        $loi['name'] = 'Tên tổ chức là bắt buộc, tối đa 100 ký tự.';
    }
    if ($d['country'] === '' || mb_strlen($d['country']) > 100) {
        $loi['country'] = 'Quốc gia là bắt buộc, tối đa 100 ký tự.';
    }
    if ($loi !== []) {
        return $loi;
    }

    $tinCay = $d['is_trusted'] ? 1 : 0;
    try {
        if ($id === null) {
            $pdo->prepare('INSERT INTO certificate_authorities (name, country, is_trusted) VALUES (?, ?, ?)')
                ->execute([$d['name'], $d['country'], $tinCay]);
        } else {
            $pdo->prepare('UPDATE certificate_authorities SET name = ?, country = ?, is_trusted = ? WHERE id = ?')
                ->execute([$d['name'], $d['country'], $tinCay, $id]);
        }
        ghiNhatKyLuu($pdo, $id, 'to_chuc_cap');
    } catch (PDOException $e) {
        return loiNeuTrung($e, 'name', 'Tên tổ chức này đã tồn tại.');
    }
    return [];
}

function xoaToChuc(PDO $pdo, int $id): ?string
{
    return xoaVaGhiNhatKy($pdo, 'to_chuc_cap', $id, 'Tổ chức này còn chứng chỉ, hãy chuyển hoặc xoá chứng chỉ trước.');
}
