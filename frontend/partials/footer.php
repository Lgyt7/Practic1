<?php
declare(strict_types=1);
?>
<footer class="site-footer">
    <div class="container footer-inner">
        <p class="footer__copy">
            © <?= date('Y') ?> <?= e(APP_NAME) ?>. Учебная практика · 09.02.07 · Вариант 4.
        </p>
        <p class="footer__contacts">
            <i class="bi bi-telephone-fill"></i> <?= e(CLINIC_PHONE) ?>
            <span class="footer__sep">|</span>
            <i class="bi bi-envelope-fill"></i> <?= e(CLINIC_EMAIL) ?>
        </p>
        <p class="footer__hours">
            <i class="bi bi-clock"></i> Ежедневно с <?= e(CLINIC_OPEN) ?> до <?= e(CLINIC_CLOSE) ?>
        </p>
    </div>
</footer>

<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
