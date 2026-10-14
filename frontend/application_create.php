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
            <h1 class="page-title">
                <i class="bi bi-calendar-plus page-title__icon"></i>Запись на приём
            </h1>
            <p class="page-subtitle">Заявка будет отправлена администратору и получит статус «<?= e(STATUS_NEW) ?>».</p>

            <form method="post" action="<?= url('backend/actions/application_create.php') ?>" novalidate data-validate>

                <div class="field<?= $error('pet_name') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="pet_name">Кличка питомца</label>
                    <span class="field__control">
                        <i class="bi bi-heart-fill field__control-icon"></i>
                        <input class="field__input field__input--icon" type="text" id="pet_name" name="pet_name"
                               value="<?= e($value('pet_name')) ?>" required maxlength="100" placeholder="Барсик">
                    </span>
                    <?php if ($__e = $error('pet_name')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $error('animal_type') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="animal_type">Вид животного</label>
                    <select class="field__input" id="animal_type" name="animal_type" required>
                        <option value="">— выберите вид —</option>
                        <?php foreach (ANIMAL_TYPES as $__type): ?>
                            <option value="<?= e($__type) ?>" <?= $value('animal_type') === $__type ? 'selected' : '' ?>>
                                <?= e(ucfirst($__type)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($__e = $error('animal_type')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $error('service') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="service">Услуга</label>
                    <select class="field__input" id="service" name="service" required>
                        <option value="">— выберите услугу —</option>
                        <?php foreach (SERVICES as $__service): ?>
                            <option value="<?= e($__service) ?>" <?= $value('service') === $__service ? 'selected' : '' ?>>
                                <?= e(ucfirst($__service)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($__e = $error('service')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field-row">
                    <div class="field<?= $error('appt_date') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="appt_date">Дата приёма</label>
                        <span class="field__control">
                            <i class="bi bi-calendar-event field__control-icon"></i>
                            <input class="field__input field__input--icon" type="text" id="appt_date" name="appt_date"
                                   value="<?= e($value('appt_date')) ?>" required
                                   placeholder="ДД.ММ.ГГГГ" inputmode="numeric">
                        </span>
                        <p class="field__hint">Например: 15.06.2026</p>
                        <?php if ($__e = $error('appt_date')): ?>
                            <p class="field__error"><?= e($__e) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field<?= $error('appt_time') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="appt_time">Время приёма</label>
                        <span class="field__control">
                            <i class="bi bi-clock field__control-icon"></i>
                            <input class="field__input field__input--icon" type="text" id="appt_time" name="appt_time"
                                   value="<?= e($value('appt_time')) ?>" required
                                   placeholder="ЧЧ:ММ" inputmode="numeric">
                        </span>
                        <p class="field__hint">
                            Клиника работает с <?= e(CLINIC_OPEN) ?> до <?= e(CLINIC_CLOSE) ?>
                        </p>
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

                <button type="submit" class="btn btn--primary btn--block">
                    <i class="bi bi-send-check"></i> Записаться
                </button>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>