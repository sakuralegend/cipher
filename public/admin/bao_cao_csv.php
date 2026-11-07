<?php
// Tải báo cáo CSV theo bộ lọc và cách xếp đang chọn. Không có giao diện.
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$danhSach = tatCaChungChi($pdo, docBoLoc($_GET), docSapXep($_GET));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="bao-cao-chung-chi-' . date('Y-m-d') . '.csv"');
// Báo cáo có dữ liệu nội bộ: không để trình duyệt hay proxy giữ lại bản sao.
header('Cache-Control: no-store');

$dauRa = fopen('php://output', 'w');
// Dấu BOM để Excel nhận ra UTF-8; thiếu nó tiếng Việt sẽ hiện thành chữ lỗi.
fwrite($dauRa, "\xEF\xBB\xBF");
// Đối số cuối rỗng: dùng đúng cách thoát chuẩn của CSV, không dùng dấu gạch ngược riêng của PHP.
fputcsv($dauRa, COT_CSV, ',', '"', '');
foreach ($danhSach as $dong) {
    fputcsv($dauRa, dongCsv($dong), ',', '"', '');
}
fclose($dauRa);
