<?php
// Trang giới thiệu công khai. Không truy vấn CSDL, nên vẫn mở được khi CSDL gặp sự cố.
// Ai dùng: khách chưa đăng nhập (giảng viên, người trong tổ chức) và người đã đăng nhập quay lại trang đầu.
// Luồng chính: đọc giới thiệu, xem phim thẻ chứng chỉ tự chạy qua các mốc 30/15/7 ngày, bấm Đăng nhập
// (hoặc Vào trang quản trị nếu đã đăng nhập). Không có lỗi nghiệp vụ: trang chỉ hiển thị.
// Phong cách tối giản nền tối theo betterstack.com (user chốt 09/11/2026): luôn tối, không theo nút đổi giao diện,
// không có hiệu ứng theo cuộn; chuyển động chỉ còn phim thẻ tự chạy và hiệu ứng rê chuột.
require __DIR__ . '/../src/khoi_dong.php';

// Đã đăng nhập thì nút dẫn thẳng vào trang quản trị. Chỉ quyết định nút nào hiện;
// quyền thật vẫn do trang quản trị kiểm tra.
$daDangNhap = !empty($_SESSION['user_id']);
$nutChinh = $daDangNhap
    ? ['nhan' => 'Vào trang quản trị', 'dich' => url('admin/index.php')]
    : ['nhan' => 'Đăng nhập', 'dich' => url('login.php')];

$tieuDe = 'Giới thiệu';
$tieuDeTab = TEN_THUONG_HIEU . ' | Quản lý chứng chỉ số'; // Trang đầu tiên khách thấy: brand đứng trước.
$coThanhRieng = true;
$coChuyenCanh = true; // Bấm Đăng nhập: thẻ chứng chỉ bay sang trang đăng nhập.
require __DIR__ . '/../views/dau_trang.php';

// Khung giữ chỗ cho ảnh chụp giao diện: user 09/11/2026 muốn chụp ảnh thật sau khi làm xong giao diện mới,
// trong lúc chờ thì để khung trống đúng tỉ lệ ảnh sẽ đặt vào. $loai: 'may' (máy tính 16:10) hoặc 'dt' (điện thoại).
$khungChoAnh = function (string $ten, string $loai = 'may'): string {
    $dau = $loai === 'may' ? '<div class="ld-cho-anh-dau" aria-hidden="true"><i></i><i></i><i></i></div>' : '';
    return '<figure class="ld-cho-anh ld-cho-anh-' . $loai . '">' . $dau
        . '<div class="ld-cho-anh-than"><span>' . e($ten) . '</span><small>Ảnh chụp sẽ đặt ở đây</small></div></figure>';
};
?>
<?php // ld-vung: vùng mang bảng màu tối giản nền tối (biến --cc-* trong app.css). ?>
<header class="navbar navbar-expand d-print-none ld-thanh-tren ld-vung" data-bs-theme="dark">
    <div class="container-xl">
        <span class="navbar-brand d-inline-flex align-items-center"><?= $logo ?><?= e(TEN_THUONG_HIEU) ?></span>
        <div class="navbar-nav flex-row ms-auto align-items-center">
            <a class="btn ld-nut-chinh" href="<?= e($nutChinh['dich']) ?>"><?= e($nutChinh['nhan']) ?></a>
        </div>
    </div>
</header>

<main class="ld-vung" data-bs-theme="dark">
    <?php // Hero kiểu Better Stack: chữ căn giữa, bên dưới là ảnh giao diện máy tính và điện thoại nằm nghiêng, mờ dần vào nền. ?>
    <section class="ld-hero">
        <div class="ld-hero-nen" aria-hidden="true"></div>
        <div class="container-xl ld-hero-chu">
            <p class="ld-nhan-nho"><i aria-hidden="true"></i>Quản lý chứng chỉ số và thời hạn sử dụng</p>
            <h1 class="ld-tieu-de">Không để chứng chỉ nào <span class="ld-chu-sang">hết hạn bất ngờ</span></h1>
            <p class="ld-mo-ta">
                Theo dõi tập trung chứng chỉ SSL/TLS của website, máy chủ và dịch vụ trong tổ chức.
                Hệ thống tự tính số ngày còn hiệu lực và cảnh báo ở các mốc 30, 15 và 7 ngày.
            </p>
            <div class="ld-hang-nut">
                <a class="btn btn-lg ld-nut-chinh" href="<?= e($nutChinh['dich']) ?>"><?= e($nutChinh['nhan']) ?></a>
                <a class="btn btn-lg ld-nut-phu" href="#vong-doi">Xem vòng đời chứng chỉ</a>
            </div>
        </div>
        <div class="container-xl">
            <div class="ld-trung-bay">
                <div class="ld-trung-bay-trong">
                    <?= $khungChoAnh('Dashboard trên máy tính') ?>
                    <?= $khungChoAnh('Dashboard trên điện thoại', 'dt') ?>
                </div>
            </div>
        </div>
    </section>

    <?php
    // Phim thẻ chứng chỉ (QD-23): app.js tìm data-ld-phim và cho tự chạy lặp như video, khoảng 18 giây một vòng.
    // Không có JavaScript hoặc máy bật giảm chuyển động thì thẻ đứng yên ở khung "còn 7 ngày", nên bắt đầu bằng đỏ.
    ?>
    <section class="ld-muc ld-vong-doi" id="vong-doi" data-ld-muc="do" data-ld-phim>
        <div class="ld-anh-sang" aria-hidden="true"></div>
        <div class="container-xl">
            <div class="row align-items-center gy-5 gx-lg-5">
                <div class="col-lg-5">
                    <h2 class="ld-tieu-de-muc">Vòng đời một chứng chỉ</h2>
                    <p class="ld-mo-ta-muc">Từ lúc còn nhiều ngày tới lúc gia hạn, hệ thống báo đúng lúc ở từng mốc.</p>
                    <ol class="ld-chuong" data-ld-chuong>
                        <li>Mỗi ngày, hệ thống tự tính số ngày còn lại.</li>
                        <li>Còn 30 ngày: cảnh báo đầu tiên xuất hiện.</li>
                        <li>Còn 15 ngày: cảnh báo chuyển sang mức gấp hơn.</li>
                        <li class="ld-bat">Còn 7 ngày: chứng chỉ được đưa lên đầu danh sách cần xử lý.</li>
                        <li>Gia hạn xong: bản cũ vào lịch sử, số ngày được tính lại.</li>
                    </ol>
                </div>
                <div class="col-lg-7">
                    <?php require __DIR__ . '/../views/the_chung_chi.php'; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="ld-muc">
        <div class="container-xl">
            <div class="ld-dau-muc">
                <h2 class="ld-tieu-de-muc">Mở ra là biết việc nào cần làm trước</h2>
                <p class="ld-mo-ta-muc">Dashboard đếm chứng chỉ theo trạng thái và xếp những chứng chỉ gần hết hạn nhất lên đầu.</p>
            </div>
            <?= $khungChoAnh('Danh sách chứng chỉ cần xử lý') ?>
        </div>
    </section>

    <section class="ld-muc">
        <div class="container-xl">
            <div class="ld-dau-muc">
                <h2 class="ld-tieu-de-muc">Mọi chứng chỉ ở cùng một nơi</h2>
            </div>
            <div class="row row-cards">
                <div class="col-md-4">
                    <div class="ld-the-tinh">
                        <div class="ld-minh-hoa ld-minh-hoa-ds" aria-hidden="true">
                            <div><i class="ld-cham-xanh"></i><span class="ma-so">portal.congty.example</span></div>
                            <div><i class="ld-cham-cam"></i><span class="ma-so">mail.congty.example</span></div>
                            <div><i class="ld-cham-do"></i><span class="ma-so">test.congty.example</span></div>
                        </div>
                        <h3 class="ld-the-tinh-ten">Quản lý tập trung</h3>
                        <p class="text-secondary mb-0">Lưu chứng chỉ cùng tên miền, máy chủ và tổ chức cấp. Mỗi chứng chỉ có mã quản lý và dấu vân tay riêng.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ld-the-tinh">
                        <div class="ld-minh-hoa ld-minh-hoa-moc" aria-hidden="true">
                            <span class="badge bg-yellow-lt">Mốc 30 ngày</span>
                            <span class="badge bg-orange-lt">Mốc 15 ngày</span>
                            <span class="badge bg-red-lt">Mốc 7 ngày</span>
                        </div>
                        <h3 class="ld-the-tinh-ten">Cảnh báo theo mốc</h3>
                        <p class="text-secondary mb-0">Trạng thái và số ngày còn lại luôn được tính theo ngày hiện tại. Sắp đến hạn thì cảnh báo hiện ngay.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ld-the-tinh">
                        <!-- Chiều cao cột theo số liệu của bộ dữ liệu mẫu 50 chứng chỉ: 6, 13, 22, 3, 6. -->
                        <div class="ld-minh-hoa ld-minh-hoa-cot" aria-hidden="true">
                            <i class="ld-cham-do" style="height: 27%"></i>
                            <i class="ld-cham-cam" style="height: 59%"></i>
                            <i class="ld-cham-xanh" style="height: 100%"></i>
                            <i class="ld-cham-lam" style="height: 14%"></i>
                            <i class="ld-cham-xam" style="height: 27%"></i>
                        </div>
                        <h3 class="ld-the-tinh-ten">Thống kê và báo cáo</h3>
                        <p class="text-secondary mb-0">Xem số liệu theo trạng thái bằng bảng và biểu đồ, giữ lịch sử gia hạn, xuất báo cáo khi cần.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ld-muc">
        <div class="container-xl">
            <div class="ld-dau-muc">
                <h2 class="ld-tieu-de-muc">Ba bước đơn giản</h2>
            </div>
            <ol class="ld-cac-buoc">
                <li class="ld-buoc">
                    <div class="ld-so-buoc">1</div>
                    <h3 class="ld-the-tinh-ten">Nhập chứng chỉ</h3>
                    <p class="text-secondary mb-0">Khai báo chứng chỉ, tên miền và tổ chức cấp vào hệ thống.</p>
                </li>
                <li class="ld-buoc">
                    <div class="ld-so-buoc">2</div>
                    <h3 class="ld-the-tinh-ten">Theo dõi tự động</h3>
                    <p class="text-secondary mb-0">Hệ thống tính số ngày còn lại và báo khi chứng chỉ chạm mốc cảnh báo.</p>
                </li>
                <li class="ld-buoc">
                    <div class="ld-so-buoc">3</div>
                    <h3 class="ld-the-tinh-ten">Gia hạn kịp thời</h3>
                    <p class="text-secondary mb-0">Cập nhật chứng chỉ mới; thông tin bản cũ được lưu lại trong lịch sử.</p>
                </li>
            </ol>
        </div>
    </section>
</main>

<?php // Dải cuối luôn nền tối, chứa luôn chân trang. ?>
<footer class="ld-dai-cuoi ld-vung" data-bs-theme="dark">
    <div class="ld-anh-sang" aria-hidden="true"></div>
    <div class="container-xl ld-dai-cuoi-trong">
        <h2 class="ld-dai-cuoi-chu">Sẵn sàng kiểm tra chứng chỉ của bạn?</h2>
        <a class="btn btn-lg ld-nut-chinh" href="<?= e($nutChinh['dich']) ?>"><?= e($nutChinh['nhan']) ?></a>
        <p class="ld-chan-trang">Đề tài 69: Xây dựng hệ thống quản lý chứng chỉ số và thời hạn sử dụng</p>
    </div>
</footer>
<?php require __DIR__ . '/../views/cuoi_trang.php'; ?>
