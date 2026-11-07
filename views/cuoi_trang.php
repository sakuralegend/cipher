<?php if ($nguoi !== null): ?>
        </div>
    </div>
<?php endif; ?>
    <script src="<?= e(url('assets/tabler/tabler.min.js')) ?>"></script>
    <?php // Chart.js nặng khoảng 200 KB, nên chỉ trang có biểu đồ mới đặt $coBieuDo = true. ?>
    <?php if ($coBieuDo ?? false): ?>
        <script src="<?= e(url('assets/chartjs/chart.umd.min.js')) ?>"></script>
    <?php endif; ?>
    <script src="<?= e(urlTep('assets/app.js')) ?>"></script>
</body>
</html>
