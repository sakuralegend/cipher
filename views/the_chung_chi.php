<?php
// Thẻ chứng chỉ minh hoạ, dùng chung cho landing và trang đăng nhập. app.js (taoPhimThe) cho thẻ chạy vòng đời
// qua các thuộc tính data-ld-*. HTML dưới đây sẵn là một khung hoàn chỉnh (còn 7 ngày, đủ 3 cảnh báo):
// tắt JavaScript hoặc bật giảm chuyển động thì người xem vẫn thấy trọn nội dung, chỉ là không chạy.
?>
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
