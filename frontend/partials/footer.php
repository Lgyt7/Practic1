<?php
declare(strict_types=1);

/**
 * frontend/partials/footer.php
 * Подвал сайта и подключение скриптов.
 */

declare(strict_types=1);
?>
<footer class="site-footer">
    <div class="container footer-inner">
        <p class="footer__copy">
            © <?= date('Y') ?> <?= e(APP_NAME) ?>. Учебная практика, вариант 4.
        </p>
        <p class="footer__contacts">
            Телефон: <?= e(CLINIC_PHONE) ?> &nbsp;|&nbsp; E-mail: <?= e(CLINIC_EMAIL) ?>
        </p>
    </div>
</footer>

<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>