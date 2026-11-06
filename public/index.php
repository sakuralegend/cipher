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
<header class="navbar navbar-expand d-print-none ld-thanh-tren">
    <div class="container-xl">
        <span class="navbar-brand">Quản lý chứng chỉ số</span>
        <div class="navbar-nav flex-row ms-auto align-items-center gap-2">
            <?= $nutGiaoDien ?>
            <!-- Màn hình hẹp không đủ chỗ cho nút này, và ngay bên dưới đã có nút chính. -->
            <a class="btn btn-primary d-none d-sm-inline-block" href="<?= e($nutChinh['dich']) ?>"><?= e($nutChinh['nhan']) ?></a>
        </div>
    </div>
</header>

<main>
    <section class="ld-hero">
        <div class="container-xl">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <h1 class="ld-tieu-de">Không để chứng chỉ nào hết hạn bất ngờ</h1>
                    <p class="ld-mo-ta">
                        Theo dõi tập trung chứng chỉ SSL/TLS của website, máy chủ và dịch vụ trong tổ chức.
                        Hệ thống tự tính số ngày còn hiệu lực và cảnh báo ở các mốc 30, 15 và 7 ngày.
                    </p>
                    <a class="btn btn-primary btn-lg" href="<?= e($nutChinh['dich']) ?>"><?= e($nutChinh['nhan']) ?></a>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Minh hoạ: chứng chỉ cần chú ý</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr><th>Tên miền</th><th>Còn lại</th><th>Trạng thái</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="ma-so">test.congty.example</td>
                                        <td>7 ngày</td>
                                        <td><span class="badge bg-red-lt">Mốc 7 ngày</span></td>
                                    </tr>
                                    <tr>
                                        <td class="ma-so">mail.congty.example</td>
                                        <td>15 ngày</td>
                                        <td><span class="badge bg-orange-lt">Mốc 15 ngày</span></td>
                                    </tr>
                                    <tr>
                                        <td class="ma-so">portal.congty.example</td>
                                        <td>30 ngày</td>
                                        <td><span class="badge bg-yellow-lt">Mốc 30 ngày</span></td>
                                    </tr>
                                    <tr>
                                        <td class="ma-so">congty.example</td>
                                        <td>200 ngày</td>
                                        <td><span class="badge bg-green-lt">Còn hiệu lực</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ld-muc">
        <div class="container-xl">
            <h2 class="ld-tieu-de-muc">Mọi chứng chỉ ở cùng một nơi</h2>
            <div class="row row-cards">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Quản lý tập trung</h3>
                            <p class="text-secondary mb-0">Lưu chứng chỉ cùng tên miền, máy chủ và tổ chức cấp. Mỗi chứng chỉ có mã quản lý và dấu vân tay riêng.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Cảnh báo theo mốc</h3>
                            <p class="text-secondary mb-0">Trạng thái và số ngày còn lại luôn được tính theo ngày hiện tại. Sắp đến hạn thì cảnh báo hiện ngay.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title">Thống kê và báo cáo</h3>
                            <p class="text-secondary mb-0">Xem số liệu theo trạng thái bằng bảng và biểu đồ, giữ lịch sử gia hạn, xuất báo cáo khi cần.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ld-muc">
        <div class="container-xl">
            <h2 class="ld-tieu-de-muc">Ba bước đơn giản</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="ld-so-buoc">1</div>
                    <h3>Nhập chứng chỉ</h3>
                    <p class="text-secondary">Khai báo chứng chỉ, tên miền và tổ chức cấp vào hệ thống.</p>
                </div>
                <div class="col-md-4">
                    <div class="ld-so-buoc">2</div>
                    <h3>Theo dõi tự động</h3>
                    <p class="text-secondary">Hệ thống tính số ngày còn lại và báo khi chứng chỉ chạm mốc cảnh báo.</p>
                </div>
                <div class="col-md-4">
                    <div class="ld-so-buoc">3</div>
                    <h3>Gia hạn kịp thời</h3>
                    <p class="text-secondary">Cập nhật chứng chỉ mới; thông tin bản cũ được lưu lại trong lịch sử.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="ld-muc">
        <div class="container-xl">
            <div class="card">
                <div class="card-body text-center py-5">
                    <h2 class="mb-3">Sẵn sàng kiểm tra chứng chỉ của bạn?</h2>
                    <a class="btn btn-primary btn-lg" href="<?= e($nutChinh['dich']) ?>"><?= e($nutChinh['nhan']) ?></a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="ld-chan-trang">
    <div class="container-xl text-secondary">
        Đề tài 69: Xây dựng hệ thống quản lý chứng chỉ số và thời hạn sử dụng
    </div>
</footer>
<?php require __DIR__ . '/../views/cuoi_trang.php'; ?>
