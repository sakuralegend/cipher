<?php
// Nhập hàng loạt (user chốt 09/11/2026): dán tối đa 50 địa chỉ, hệ thống đọc từng cái rồi hiện bảng xem trước có
// ô tick; bấm Lưu thì thêm các dòng đã tick, tự tạo tên miền, máy chủ, tổ chức cấp còn thiếu y như form một site.
// Kết quả đọc nằm trong phiên (khoá nhap_hang_loat) giữa hai bước: bước Lưu chỉ nhận SỐ THỨ TỰ dòng từ trình duyệt,
// không nhận dữ liệu chứng chỉ, nên không ai gửi được chứng chỉ hay IP giả qua trang này.
// Có JavaScript thì thanh tiến trình chạy theo số địa chỉ đã đọc (đọc từng địa chỉ một, xem khối "Hai cách đọc" bên dưới).
require __DIR__ . '/../../src/khoi_dong.php';
require __DIR__ . '/../../src/danh_muc.php';
require __DIR__ . '/../../src/chung_chi.php';

$pdo = ketNoiCsdl();
$nguoi = yeuCauDangNhap($pdo);

$nhap = '';
$congChon = 443;
$loiNhap = null;
$cacDong = null;   // Bảng xem trước sau bước Đọc.
$ketQuaLuu = null; // Bảng kết quả sau bước Lưu.

// Dòng lưu được: đọc được, chưa có trong hệ thống, không trùng dòng khác trong cùng lượt.
$luuDuoc = fn(array $dong): bool => !isset($dong['loi']) && $dong['trung'] === null && $dong['trung_dong'] === null;

// Đọc một địa chỉ thành một dòng của bảng xem trước. Dòng trùng nhau trong lượt đánh dấu sau, bằng danhDauTrungTrongLuot().
$docMot = fn(string $diaChi, int $cong): array => docChungChiChoForm($pdo, $diaChi, $cong) + ['nhap' => $diaChi, 'trung' => null];

// Trả lời cho app.js (thanh tiến trình) rồi dừng trang.
$traJson = function (array $duLieu): never {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($duLieu, JSON_UNESCAPED_UNICODE);
    exit;
};

// Hai cách đọc, cùng kết quả:
// - Có JavaScript (thanh tiến trình, kiểm thử 12/11/2026: đọc cả lượt thì trang đứng im cả phút): app.js gửi hanh_dong=bat_dau
//   kèm danh sách, rồi doc_mot nhiều lần, mỗi lần máy chủ tự lấy địa chỉ kế tiếp trong phiên (trình duyệt không gửi địa chỉ
//   lẻ nào), cuối cùng gửi xem để hiện bảng xem trước.
// - Không có JavaScript: hanh_dong=doc đọc cả lượt trong một yêu cầu như trước.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['nhap_hang_loat'], $_SESSION['nhap_dang_doc']);
} else {
    yeuCauCsrf();
    $hanhDong = chuoiPost('hanh_dong');
    if ($hanhDong === 'doc' || $hanhDong === 'bat_dau') {
        unset($_SESSION['nhap_hang_loat'], $_SESSION['nhap_dang_doc']);
        $nhap = chuoiPost('dia_chi');
        $congChon = (int) chuoiPost('cong');
        $ds = tachDanhSachDiaChi($nhap);
        if ($hanhDong === 'bat_dau') {
            if (isset($ds['loi'])) {
                $traJson(['loi' => $ds['loi']]);
            }
            $_SESSION['nhap_dang_doc'] = ['nhap' => $nhap, 'cong' => $congChon, 'dia_chi' => $ds['dia_chi'], 'da_doc' => []];
            $traJson(['tong' => count($ds['dia_chi'])]);
        }
        if (isset($ds['loi'])) {
            $loiNhap = $ds['loi'];
        } else {
            // Tệ nhất mỗi địa chỉ chờ đủ thời gian chờ: nới giới hạn 30 giây mặc định của PHP cho đủ cả lượt.
            set_time_limit(count($ds['dia_chi']) * GIAY_CHO_DOC_CHUNG_CHI + 60);
            // PHP khoá phiên tới hết trang: đọc cả phút thì mọi tab khác của người này treo theo. Nhả khoá trong lúc
            // đọc ($_SESSION vẫn đọc được, nhật ký vẫn biết ai đọc), đọc xong mở lại để ghi kết quả.
            session_write_close();
            $cacDong = danhDauTrungTrongLuot(array_map(fn(string $diaChi): array => $docMot($diaChi, $congChon), $ds['dia_chi']));
            session_start();
            $_SESSION['nhap_hang_loat'] = $cacDong;
        }
    } elseif ($hanhDong === 'doc_mot' && isset($_SESSION['nhap_dang_doc'])) {
        $dang = $_SESSION['nhap_dang_doc'];
        $viTri = count($dang['da_doc']);
        $diaChi = $dang['dia_chi'][$viTri] ?? null;
        if ($diaChi !== null) {
            // Nhả khoá phiên trong lúc chờ máy chủ kia (tối đa vài giây), như đường đọc cả lượt.
            session_write_close();
            $dong = $docMot($diaChi, $dang['cong']);
            session_start();
            $_SESSION['nhap_dang_doc']['da_doc'][] = $dong;
        }
        $traJson(['da_doc' => count($_SESSION['nhap_dang_doc']['da_doc']), 'tong' => count($dang['dia_chi']), 'vua_doc' => $diaChi]);
    } elseif ($hanhDong === 'xem' && isset($_SESSION['nhap_dang_doc'])) {
        $dang = $_SESSION['nhap_dang_doc'];
        unset($_SESSION['nhap_dang_doc']);
        $nhap = $dang['nhap'];
        $congChon = $dang['cong'];
        $cacDong = danhDauTrungTrongLuot($dang['da_doc']);
        $_SESSION['nhap_hang_loat'] = $cacDong;
    } elseif ($hanhDong === 'luu' && isset($_SESSION['nhap_hang_loat'])) {
        $daDoc = $_SESSION['nhap_hang_loat'];
        unset($_SESSION['nhap_hang_loat']);
        $chon = array_unique(array_map('intval', is_array($_POST['chon'] ?? null) ? $_POST['chon'] : []));
        sort($chon);
        $ketQuaLuu = [];
        foreach ($chon as $i) {
            if (!isset($daDoc[$i]) || !$luuDuoc($daDoc[$i])) {
                continue;
            }
            $dong = $daDoc[$i];
            // Kế hoạch tính lại theo CSDL lúc này: dòng sau dùng lại tên miền, máy chủ, tổ chức cấp dòng trước vừa tạo.
            $keHoach = keHoachDanhMuc($pdo, $dong['nguon']);
            // Bỏ mã tên miền, tổ chức cấp lúc đọc: kế hoạch vừa tính mới là đúng với CSDL lúc này.
            $giaTri = ['cert_code' => maChungChiKeTiep($pdo), 'domain_id' => '', 'ca_id' => ''] + $dong['gia_tri'];
            $loi = luuChungChiKemDanhMuc($pdo, $keHoach, $giaTri, (int) $nguoi['id']);
            $ketQuaLuu[] = ['nhap' => $dong['nhap'], 'ma' => $loi === [] ? $giaTri['cert_code'] : null,
                'loi' => implode(' ', array_unique($loi))];
        }
        $soThem = count(array_filter($ketQuaLuu, fn(array $k): bool => $k['ma'] !== null));
        datThongBao($soThem === count($ketQuaLuu) ? 'success' : 'warning',
            "Đã thêm $soThem/" . count($ketQuaLuu) . ' chứng chỉ.');
    }
}

// Số ngày lịch tới ngày hết hạn, cùng cách đếm với cột days_left của CSDL.
$soNgay = fn(string $hetHan): int => (int) round((strtotime(date('Y-m-d', strtotime($hetHan))) - strtotime(date('Y-m-d'))) / 86400);
$idTheoMa = function (string $ma) use ($pdo): int {
    $cau = $pdo->prepare('SELECT id FROM certificates WHERE cert_code = ?');
    $cau->execute([$ma]);
    return (int) $cau->fetchColumn();
};

$tieuDe = 'Thêm chứng chỉ';
$trangHienTai = 'chung_chi_nhap';
require __DIR__ . '/../../views/dau_trang.php';
require __DIR__ . '/../../views/tieu_de_trang.php';
?>
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <?= tabThemChungChi('hang_loat') ?>

            <?php if ($ketQuaLuu !== null): ?>
                <div class="card-body">
                    <h2 class="card-title mb-3">Kết quả</h2>
                    <?php if ($ketQuaLuu === []): ?>
                        <p class="text-secondary mb-0">Chưa chọn dòng nào để lưu.</p>
                    <?php else: ?>
                        <ul class="list-unstyled ds-nhap mb-0">
                            <?php foreach ($ketQuaLuu as $k): ?>
                                <li>
                                    <span class="fw-semibold"><?= tenMienNgatDong($k['nhap']) ?></span>
                                    <?php if ($k['ma'] !== null): ?>
                                        <span class="badge bg-green-lt">Đã thêm</span>
                                        <a href="<?= e(url('admin/chung_chi_xem.php?id=' . $idTheoMa($k['ma']))) ?>"><?= e($k['ma']) ?></a>
                                    <?php else: ?>
                                        <span class="badge bg-red-lt">Không thêm được</span>
                                        <div class="text-secondary small"><?= e($k['loi']) ?></div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-end">
                    <a class="btn" href="<?= e(url('admin/chung_chi_nhap.php')) ?>">Nhập tiếp</a>
                    <a class="btn btn-primary" href="<?= e(url('admin/chung_chi.php')) ?>">Xem danh sách chứng chỉ</a>
                </div>
            <?php else: ?>
                <?php // data-nhap-tung-dia-chi: app.js đọc từng địa chỉ và chạy thanh .nhap-tien-do; lỗi giữa chừng thì gửi form như thường. ?>
                <form class="card-body<?= $cacDong !== null ? ' border-bottom' : '' ?>" method="post"
                      action="<?= e(url('admin/chung_chi_nhap.php')) ?>" data-dang-cho="Đang đọc…" data-nhap-tung-dia-chi>
                    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                    <label class="form-label" for="dia_chi">Danh sách địa chỉ</label>
                    <textarea class="form-control ma-so<?= $loiNhap !== null ? ' is-invalid' : '' ?>" id="dia_chi" name="dia_chi"
                              rows="<?= $cacDong !== null ? 3 : 8 ?>" required autocapitalize="off" spellcheck="false"
                              placeholder="Mỗi dòng một địa chỉ, ví dụ:&#10;vnexpress.net&#10;smtp.gmail.com:465"><?= e($nhap) ?></textarea>
                    <?php if ($loiNhap !== null): ?>
                        <div class="invalid-feedback"><?= e($loiNhap) ?></div>
                    <?php endif; ?>
                    <div class="row g-2 mt-1 align-items-center">
                        <div class="col col-md-auto">
                            <select class="form-select" name="cong" aria-label="Cổng khi địa chỉ không ghi cổng">
                                <?php foreach (CONG_DOC_CHUNG_CHI as $cong => $nhanCong): ?>
                                    <option value="<?= $cong ?>"<?= $cong === $congChon ? ' selected' : '' ?>><?= e($nhanCong) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary" name="hanh_dong" value="doc">Đọc tất cả</button>
                        </div>
                    </div>
                    <small class="form-hint mt-2">Tối đa <?= SO_DIA_CHI_TOI_DA ?> địa chỉ mỗi lượt, cách nhau bằng xuống dòng,
                        dấu cách hoặc dấu phẩy. Cổng chọn ở trên dùng cho địa chỉ không ghi cổng. Mỗi địa chỉ chờ tối đa
                        <?= GIAY_CHO_DOC_CHUNG_CHI ?> giây; <?= SO_DIA_CHI_TOI_DA ?> địa chỉ thường mất dưới một phút. Đọc xong
                        chưa lưu gì: xem lại rồi mới bấm Lưu.</small>
                    <div class="nhap-tien-do mt-3" hidden>
                        <div class="progress progress-sm">
                            <div class="progress-bar" role="progressbar" aria-label="Tiến độ đọc" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                        </div>
                        <div class="small text-secondary mt-1" aria-live="polite"></div>
                    </div>
                </form>

                <?php if ($cacDong !== null): ?>
                    <?php $soDuoc = count(array_filter($cacDong, $luuDuoc)); ?>
                    <form method="post" action="<?= e(url('admin/chung_chi_nhap.php')) ?>">
                        <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-baseline gap-2 mb-3">
                                <h2 class="card-title mb-0 me-auto">Xem trước</h2>
                                <span class="text-secondary small">Lưu được <?= $soDuoc ?>/<?= count($cacDong) ?> địa chỉ.
                                    Tên miền, máy chủ, tổ chức cấp trùng nhau giữa các dòng chỉ tạo một lần.</span>
                            </div>
                            <ul class="list-unstyled ds-nhap mb-0">
                                <?php foreach ($cacDong as $i => $dong): ?>
                                    <?php $duoc = $luuDuoc($dong); ?>
                                    <li class="d-flex gap-2">
                                        <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" name="chon[]" value="<?= $i ?>"
                                               id="chon-<?= $i ?>"<?= $duoc ? ' checked' : ' disabled' ?>>
                                        <label class="flex-grow-1 min-w-0" for="chon-<?= $i ?>">
                                            <span class="fw-semibold"><?= tenMienNgatDong($dong['nhap']) ?></span>
                                            <?php if (isset($dong['loi'])): ?>
                                                <span class="badge bg-red-lt">Không đọc được</span>
                                                <div class="text-secondary small"><?= e($dong['loi']) ?></div>
                                            <?php else: ?>
                                                <?php $gt = $dong['gia_tri']; $n = $soNgay($gt['valid_to']); ?>
                                                <?php if ($n < 0): ?>
                                                    <span class="badge bg-red-lt">Hết hạn <?= abs($n) ?> ngày</span>
                                                <?php elseif ($n === 0): ?>
                                                    <span class="badge bg-red-lt">Hết hạn hôm nay</span>
                                                <?php else: ?>
                                                    <span class="badge <?= $n <= 30 ? 'bg-yellow-lt' : 'bg-green-lt' ?>">Còn <?= $n ?> ngày</span>
                                                <?php endif; ?>
                                                <div class="text-secondary small">
                                                    <?= tenMienNgatDong($gt['subject_cn']) ?> · <?= e($dong['nguon']['hang']) ?>
                                                    <?php if ($gt['key_algorithm'] !== ''): ?>· <?= e($gt['key_algorithm'] . ' ' . $gt['key_size']) ?><?php endif; ?>
                                                    · hết hạn <?= e(date('d/m/Y', strtotime($gt['valid_to']))) ?>
                                                </div>
                                                <?php if ($dong['trung'] !== null): ?>
                                                    <div class="small text-warning">Đã có trong hệ thống:
                                                        <a href="<?= e(url('admin/chung_chi_xem.php?id=' . (int) $dong['trung']['id'])) ?>"><?= e($dong['trung']['cert_code']) ?></a></div>
                                                <?php elseif ($dong['trung_dong'] !== null): ?>
                                                    <div class="small text-warning">Cùng chứng chỉ với <?= e($dong['trung_dong']) ?> ở trên.</div>
                                                <?php else: ?>
                                                    <?php foreach (moTaSeTao($dong['ke_hoach']) as $seTao): ?>
                                                        <div class="small">+ <?= e($seTao) ?></div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <?php if ($dong['ke_hoach']['ip_khac'] !== null): ?>
                                                    <?php $ipKhac = $dong['ke_hoach']['ip_khac']; ?>
                                                    <div class="small text-secondary">IP lúc đọc <?= e($ipKhac['ip_moi']) ?> khác IP đã lưu
                                                        <?= e($ipKhac['ip_cu']) ?> của máy chủ <?= e($ipKhac['hostname']) ?> (giữ nguyên).</div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer text-end">
                            <a class="btn" href="<?= e(url('admin/chung_chi_nhap.php')) ?>">Huỷ</a>
                            <button type="submit" class="btn btn-primary" name="hanh_dong" value="luu"<?= $soDuoc === 0 ? ' disabled' : '' ?>>Lưu các dòng đã chọn</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../views/cuoi_trang.php'; ?>
