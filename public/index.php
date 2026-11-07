<?php
// Trang giới thiệu công khai. Không truy vấn CSDL, nên vẫn mở được khi CSDL gặp sự cố.
require __DIR__ . '/../src/khoi_dong.php';

// Đã đăng nhập thì nút dẫn thẳng vào trang quản trị. Chỉ quyết định nút nào hiện;
// quyền thật vẫn do trang quản trị kiểm tra.
$daDangNhap = !empty($_SESSION['user_id']);
$nutChinh = $daDangNhap
    ? ['nhan' => 'Vào trang quản trị', 'dich' => url('admin/index.php')]
    : ['nhan' => 'Đăng nhập', 'dich' => url('login.php')];

$tieuDe = 'Giới thiệu';
$coThanhRieng = true;
require __DIR__ . '/../views/dau_trang.php';
?>
<header class="navbar navbar-expand d-print-none ld-thanh-tren" data-bs-theme="dark" data-ld-thanh-tren>
    <div class="container-xl">
        <span class="navbar-brand">Quản lý chứng chỉ số</span>
        <div class="navbar-nav flex-row ms-auto align-items-center gap-2">
            <?= $nutGiaoDien ?>
            <!-- Màn hình hẹp không đủ chỗ cho nút này, và ngay bên dưới đã có nút chính. -->
            <a class="btn ld-nut-chinh d-none d-sm-inline-block" href="<?= e($nutChinh['dich']) ?>"><?= e($nutChinh['nhan']) ?></a>
        </div>
    </div>
</header>

<main>
    <?php
    // Sân khấu hero: kể vòng đời một chứng chỉ, app.js cho chạy theo tay cuộn (QD-23).
    // HTML dưới đây sẵn là một khung hoàn chỉnh (còn 7 ngày, đủ 3 cảnh báo): tắt JavaScript hoặc
    // bật giảm chuyển động thì người xem vẫn thấy trọn nội dung, chỉ là không chạy.
    // Luôn nền tối, không theo nút đổi giao diện (user chốt 07/11/2026).
    ?>
    <section class="ld-san-khau" data-bs-theme="dark" data-ld-san-khau data-ld-muc="do">
        <div class="ld-dinh">
            <div class="ld-anh-sang" aria-hidden="true"></div>
            <div class="container-xl ld-dinh-trong">
                <div class="row align-items-center gy-5 gx-lg-5">
                    <div class="col-lg-6 ld-cot-chu">
                        <p class="ld-nhan-nho">Quản lý chứng chỉ số và thời hạn sử dụng</p>
                        <h1 class="ld-tieu-de">Không để chứng chỉ nào hết hạn bất ngờ</h1>
                        <p class="ld-mo-ta">
                            Theo dõi tập trung chứng chỉ SSL/TLS của website, máy chủ và dịch vụ trong tổ chức.
                            Hệ thống tự tính số ngày còn hiệu lực và cảnh báo ở các mốc 30, 15 và 7 ngày.
                        </p>
                        <a class="btn btn-lg ld-nut-chinh" href="<?= e($nutChinh['dich']) ?>"><?= e($nutChinh['nhan']) ?></a>
                    </div>
                    <div class="col-lg-6 ld-cot-phim">
                        <div class="ld-chong-the" data-ld-chong-the>
                            <!-- Hai thẻ phía sau chỉ để tạo chiều sâu: còn nhiều chứng chỉ khác. -->
                            <div class="ld-the-sau ld-the-sau-2" aria-hidden="true"></div>
                            <div class="ld-the-sau ld-the-sau-1" aria-hidden="true"></div>
                            <figure class="ld-the" data-ld-the
                                    aria-label="Minh hoạ: chứng chỉ của portal.congty.example đi qua các mốc cảnh báo 30, 15, 7 ngày rồi được gia hạn">
                                <div class="ld-the-dau">
                                    <span class="ma-so ld-the-ten">portal.congty.example</span>
                                    <span class="ld-trang-thai"><i aria-hidden="true"></i><span data-ld-nhan>Mốc 7 ngày</span></span>
                                </div>
                                <div class="ld-dem">
                                    <span class="ld-so" data-ld-so>7</span>
                                    <span class="ld-don-vi">ngày còn lại</span>
                                </div>
                                <div class="ld-ray" aria-hidden="true" data-ld-ray style="--ld-ray: 0.84">
                                    <div class="ld-ray-nen"><div class="ld-ray-day"></div></div>
                                    <div class="ld-ray-con"><i></i></div>
                                    <span class="ld-ray-moc" style="left: 40%">30</span>
                                    <span class="ld-ray-moc" style="left: 62%">15</span>
                                    <span class="ld-ray-moc" style="left: 84%">7</span>
                                    <span class="ld-ray-moc ld-ray-het" style="left: 100%">Hết hạn</span>
                                </div>
                                <ul class="ld-nhat-ky">
                                    <li class="ld-bat" data-ld-dong="30"><i aria-hidden="true"></i>Cảnh báo mốc 30 ngày</li>
                                    <li class="ld-bat" data-ld-dong="15"><i aria-hidden="true"></i>Cảnh báo mốc 15 ngày</li>
                                    <li class="ld-bat" data-ld-dong="7"><i aria-hidden="true"></i>Cảnh báo mốc 7 ngày</li>
                                    <li data-ld-dong="gia-han"><i aria-hidden="true"></i>Đã gia hạn, bản cũ lưu vào lịch sử</li>
                                </ul>
                                <figcaption class="ld-the-chan">Minh hoạ</figcaption>
                            </figure>
                        </div>
                        <ol class="ld-chuong" data-ld-chuong>
                            <li>Mỗi ngày, hệ thống tự tính số ngày còn lại.</li>
                            <li>Còn 30 ngày: cảnh báo đầu tiên xuất hiện.</li>
                            <li>Còn 15 ngày: cảnh báo chuyển sang mức gấp hơn.</li>
                            <li class="ld-bat">Còn 7 ngày: chứng chỉ được đưa lên đầu danh sách cần xử lý.</li>
                            <li>Gia hạn xong: bản cũ vào lịch sử, số ngày được tính lại.</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="ld-goi-y-cuon" aria-hidden="true">Cuộn để xem vòng đời chứng chỉ<i></i></div>
        </div>
    </section>

    <?php
    // Các mục dưới hero. Lớp ld-hien: app.js cho hiện dần khi cuộn tới, không có JavaScript thì hiện sẵn.
    ?>
    <section class="ld-muc">
        <div class="container-xl">
            <div class="ld-dau-muc ld-hien">
                <h2 class="ld-tieu-de-muc">Mở ra là biết việc nào cần làm trước</h2>
                <p class="ld-mo-ta-muc">Dashboard đếm chứng chỉ theo trạng thái và xếp những chứng chỉ gần hết hạn nhất lên đầu.</p>
            </div>
            <!-- Ảnh chụp từ Dashboard thật với dữ liệu mẫu; mỗi giao diện một ảnh, ảnh bị ẩn thì trình duyệt không tải. -->
            <div class="ld-san-anh" data-ld-san-anh>
                <figure class="ld-khung-anh">
                    <div class="ld-khung-dau" aria-hidden="true"><i></i><i></i><i></i></div>
                    <img class="hide-theme-dark" src="<?= e(url('assets/landing/dashboard-sang.webp')) ?>" width="1600" height="1000" loading="lazy" alt="Màn hình Dashboard: số chứng chỉ theo trạng thái và danh sách chứng chỉ cần xử lý ngay">
                    <img class="hide-theme-light" src="<?= e(url('assets/landing/dashboard-toi.webp')) ?>" width="1600" height="1000" loading="lazy" alt="Màn hình Dashboard: số chứng chỉ theo trạng thái và danh sách chứng chỉ cần xử lý ngay">
                </figure>
                <p class="ld-chu-thich-anh">Ảnh chụp từ hệ thống, dùng dữ liệu mẫu.</p>
            </div>
        </div>
    </section>

    <section class="ld-muc">
        <div class="container-xl">
            <div class="ld-dau-muc ld-hien">
                <h2 class="ld-tieu-de-muc">Mọi chứng chỉ ở cùng một nơi</h2>
            </div>
            <div class="row row-cards">
                <div class="col-md-4 ld-hien">
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
                <div class="col-md-4 ld-hien">
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
                <div class="col-md-4 ld-hien">
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
            <div class="ld-dau-muc ld-hien">
                <h2 class="ld-tieu-de-muc">Ba bước đơn giản</h2>
            </div>
            <!-- Đường nối giữa các bước tự vẽ theo độ cuộn; không có JavaScript thì hiện đầy đủ. -->
            <ol class="ld-cac-buoc" data-ld-cac-buoc>
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

<?php // Dải cuối luôn nền tối như hero (user chốt 07/11/2026), chứa luôn chân trang. ?>
<footer class="ld-dai-cuoi" data-bs-theme="dark" data-ld-dai-cuoi>
    <div class="ld-anh-sang" aria-hidden="true"></div>
    <div class="container-xl ld-dai-cuoi-trong">
        <div class="ld-hien">
            <h2 class="ld-dai-cuoi-chu">Sẵn sàng kiểm tra chứng chỉ của bạn?</h2>
            <a class="btn btn-lg ld-nut-chinh" href="<?= e($nutChinh['dich']) ?>"><?= e($nutChinh['nhan']) ?></a>
        </div>
        <p class="ld-chan-trang">Đề tài 69: Xây dựng hệ thống quản lý chứng chỉ số và thời hạn sử dụng</p>
    </div>
</footer>
<?php require __DIR__ . '/../views/cuoi_trang.php'; ?>
