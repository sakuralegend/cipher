<?php
// Router cho "php -S" khi chụp ảnh báo cáo. Chrome chạy ngầm không gửi được cookie, nên mã phiên đi qua ?__sid=.
// /__khung?u=<địa chỉ>&w=390&h=800: nhúng trang vào iframe hẹp để chụp khổ điện thoại (Chrome ngầm không hẹp dưới ~500px).
// Thêm &an=1: ẩn hai tầng menu của trang trong iframe (ảnh thẻ trên landing chỉ lấy phần nội dung). Đặt qua thuộc tính
// style của phần tử chứ không chèn thẻ <style>, vì CSP của trang cấm style chèn thêm.
$duong = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($duong === '/__khung') {
    $u = $_GET['u'] ?? '/';
    $w = (int) ($_GET['w'] ?? 390);
    $h = (int) ($_GET['h'] ?? 800);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><body style="margin:0;background:#0b0e14"><iframe src="' . htmlspecialchars($u)
        . '" style="border:0;display:block;width:' . $w . 'px;height:' . $h . 'px"></iframe>';
    if (isset($_GET['an'])) {
        echo '<script>document.querySelector("iframe").addEventListener("load", function () {'
            . 'this.contentDocument.querySelectorAll(".qt-thanh, .qt-phu").forEach(function (e) { e.style.display = "none"; });'
            . '});</script>';
    }
    echo '</body></html>';
    return true;
}
if (isset($_GET['__sid'])) {
    session_id(preg_replace('/[^A-Za-z0-9,-]/', '', (string) $_GET['__sid']));
    unset($_GET['__sid']);
}
return false;
