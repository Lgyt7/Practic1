<?php
declare(strict_types=1);

/**
 * frontend/application_create.php
 * Формирование заявки на приём.
 */

require_once __DIR__ . '/../backend/bootstrap.php';

// Только для авторизованных владельцев питомцев.
Auth::requireLogin();

if (Auth::isAdmin()) {
    redirect('frontend/admin.php');
}

$__pageTitle = 'Запись на приём';
$__errors    = takeErrors();
$__old       = takeOld();

$value = static fn (string $field): string => oldValue($__old, $field);
$error = static fn (string $field): ?string => $__errors[$field] ?? null;

require __DIR__ . '/partials/header.php';
?>

<main class="page">
    <div class="container">
        <div class="card card--form">
            <h1 class="page-title">Запись на приём</h1>
            <p class="page-subtitle">Заявка будет отправлена администратору и получит статус «<?= e(STATUS_NEW) ?>».</p>

            <form method="post" action="<?= url('backend/actions/application_create.php') ?>" novalidate data-validate>

                <div class="field<?= $error('pet_name') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="pet_name">Кличка питомца</label>
                    <input class="field__input" type="text" id="pet_name" name="pet_name"
                           value="<?= e($value('pet_name')) ?>" required maxlength="100" placeholder="Барсик">
                    <?php if ($__e = $error('pet_name')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $error('animal_type') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="animal_type">Вид животного</label>
                    <input class="field__input" type="text" id="animal_type" name="animal_type"
                           value="<?= e($value('animal_type')) ?>" required maxlength="50" placeholder="Кошка">
                    <?php if ($__e = $error('animal_type')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $error('service') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="service">Услуга</label>
                    <input class="field__input" type="text" id="service" name="service"
                           value="<?= e($value('service')) ?>" required minlength="3" maxlength="200"
                           placeholder="Вакцинация от бешенства">
                    <?php if ($__e = $error('service')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field-row">
                    <div class="field<?= $error('appt_date') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="appt_date">Дата приёма</label>
                        <input class="field__input" type="text" id="appt_date" name="appt_date"
                               value="<?= e($value('appt_date')) ?>" required
                               placeholder="ДД.ММ.ГГГГ" inputmode="numeric">
                        <p class="field__hint">Например: 15.06.2026</p>
                        <?php if ($__e = $error('appt_date')): ?>
                            <p class="field__error"><?= e($__e) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field<?= $error('appt_time') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="appt_time">Время приёма</label>
                        <input class="field__input" type="text" id="appt_time" name="appt_time"
                               value="<?= e($value('appt_time')) ?>" required
                               placeholder="ЧЧ:ММ" inputmode="numeric">
                        <p class="field__hint">Например: 10:30</p>
                        <?php if ($__e = $error('appt_time')): ?>
                            <p class="field__error"><?= e($__e) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field<?= $error('payment_method') ? ' field--invalid' : '' ?>">
                    <span class="field__label">Способ оплаты</span>
                    <div class="radios">
                        <label class="radio">
                            <input type="radio" name="payment_method" value="<?= e(PAYMENT_CASH) ?>"
                                <?= $value('payment_method') === PAYMENT_CASH ? 'checked' : '' ?> required>
                            <span>Наличными</span>
                        </label>
                        <label class="radio">
                            <input type="radio" name="payment_method" value="<?= e(PAYMENT_CARD) ?>"
                                <?= $value('payment_method') === PAYMENT_CARD ? 'checked' : '' ?>>
                            <span>Картой в клинике</span>
                        </label>
                    </div>
                    <?php if ($__e = $error('payment_method')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn--primary btn--block">Записаться</button>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>