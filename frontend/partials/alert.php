<?php
declare(strict_types=1);

/**
 * frontend/partials/alert.php
 * Один уведомляющий блок — используется flash-сообщениями, ошибками
 * вне формы и ответами на fetch-запросы.
 *
 * Ожидает две переменные:
 *   $__alertType — success | danger
 *   $__alertText — текст (выводится с экранированием)
 */

$__alertType = $__alertType ?? 'danger';
$__alertText = (string) ($__alertText ?? '');
?>
<div class="alert alert--<?= e($__alertType) ?>" data-toast>
    <i class="bi <?= e(alertIcon($__alertType)) ?>"></i>
    <span><?= e($__alertText) ?></span>
    <button type="button" class="alert__close" aria-label="Закрыть">&times;</button>
</div>