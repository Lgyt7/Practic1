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

/** Питомцы выбираются из профиля — вводить кличку заново не нужно. */
$__pets = Pet::forUser(Auth::id());

$value = static fn (string $field): string => oldValue($__old, $field);
$error = static fn (string $field): ?string => $__errors[$field] ?? null;

/** Минимальная дата записи — сегодня. */
$__minDate = date('Y-m-d');

require __DIR__ . '/partials/header.php';
?>

<main class="page">
    <div class="container container--narrow">
        <?php if ($__pets === []): ?>
            <!-- Питомцев нет: записаться ещё не на кого, поэтому форма не показывается.
                 Ошибки при этом не теряются — показываются рядом с объяснением. -->
            <div class="card card--empty" data-reveal>
                <i class="bi bi-heart card--empty__icon"></i>
                <h1 class="page-title">
                    <i class="bi bi-person-heart page-title__icon"></i>Запись на приём
                </h1>
                <p>
                    Сначала добавьте питомца в профиль — после этого он появится
                    в списке выбора, и записывать его можно будет в один клик.
                </p>

                <?php foreach ($__errors as $__message): ?>
                    <div class="alert alert--danger" data-toast>
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?= e($__message) ?></span>
                        <button type="button" class="alert__close" aria-label="Закрыть">&times;</button>
                    </div>
                <?php endforeach; ?>

                <a class="btn btn--primary" href="<?= url('frontend/pets.php') ?>">
                    <i class="bi bi-plus-circle"></i> Добавить питомца
                </a>
            </div>
        <?php else: ?>
            <div class="card card--form" data-reveal>
                <h1 class="page-title">
                    <i class="bi bi-calendar-plus page-title__icon"></i>Запись на приём
                </h1>
                <p class="page-subtitle">
                    Заявка будет отправлена администратору и получит статус «<?= e(STATUS_NEW) ?>».
                </p>

                <form method="post" action="<?= url('backend/actions/application_create.php') ?>" novalidate data-validate>

                    <div class="field<?= $error('pet_id') ? ' field--invalid' : '' ?>">
                        <label class="field__label" for="pet_id">Питомец</label>
                        <select class="field__input" id="pet_id" name="pet_id" required>
                            <option value="">— выберите питомца —</option>
                            <?php foreach ($__pets as $__pet): ?>
                                <?php $__petType = (string) $__pet['animal_type']; ?>
                                <option value="<?= (int) $__pet['id'] ?>" <?= $value('pet_id') === (string) $__pet['id'] ? 'selected' : '' ?>>
                                    <?= e($__pet['name']) ?> — <?= e(ucfirst($__petType)) ?>
                                    <?php if (!empty($__pet['breed'])): ?>(<?= e($__pet['breed']) ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field__hint">
                            Нет нужного питомца?
                            <a href="<?= url('frontend/pets.php') ?>">Добавьте в профиль</a>.
                        </p>
                        <?php if ($__e = $error('pet_id')): ?>
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
                                <input class="field__input field__input--icon" type="date" id="appt_date"
                                       name="appt_date" value="<?= e($value('appt_date')) ?>" required
                                       min="<?= e($__minDate) ?>">
                            </span>
                            <p class="field__hint">Не раньше сегодняшнего дня.</p>
                            <?php if ($__e = $error('appt_date')): ?>
                                <p class="field__error"><?= e($__e) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="field<?= $error('appt_time') ? ' field--invalid' : '' ?>">
                            <label class="field__label" for="appt_time">Время приёма</label>
                            <span class="field__control">
                                <i class="bi bi-clock field__control-icon"></i>
                                <?php /* min/max/step проверяются браузером до отправки,
                                         сервер проверяет то же самое повторно. */ ?>
                                <input class="field__input field__input--icon" type="time" id="appt_time"
                                       name="appt_time" value="<?= e($value('appt_time')) ?>" required
                                       min="<?= e(Schedule::open()) ?>" max="<?= e(Schedule::close()) ?>"
                                       step="<?= Schedule::STEP_MINUTES * 60 ?>">
                            </span>
                            <p class="field__hint">
                                Клиника работает с <?= e(Schedule::open()) ?> до <?= e(Schedule::close()) ?>,
                                шаг <?= Schedule::STEP_MINUTES ?> мин.
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
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>