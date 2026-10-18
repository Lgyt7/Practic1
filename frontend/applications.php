<?php
declare(strict_types=1);

/**
 * frontend/applications.php
 * Личный кабинет владельца питомца: список своих заявок, отмена и отзывы.
 */

require_once __DIR__ . '/../backend/bootstrap.php';

Auth::requireLogin();

if (Auth::isAdmin()) {
    redirect('frontend/admin.php');
}

$__pageTitle = 'Мои заявки';
$__errors    = takeErrors();
$__old       = takeOld();

/** Заявки текущего пользователя с питомцами и отзывами — не чужие. */
$__applications = Application::forUser(Auth::id());

/** Порядковый номер карточки: задаёт задержку появления при анимации. */
$__index = 0;

require __DIR__ . '/partials/header.php';
?>

<main class="page">
    <div class="container">
        <div class="page-head">
            <h1 class="page-title">
                <i class="bi bi-card-list page-title__icon"></i>Мои заявки
            </h1>
            <a class="btn btn--primary" href="<?= url('frontend/application_create.php') ?>">
                <i class="bi bi-plus-circle"></i> Новая заявка
            </a>
        </div>

        <?php /* Ошибки отправки форм (например, отказ в отзыве) — вне формы. */ ?>
        <?php foreach ($__errors as $__message): ?>
            <?php
            $__alertType = 'danger';
            $__alertText = $__message;
            require __DIR__ . '/partials/alert.php';
            ?>
        <?php endforeach; ?>

        <?php if ($__applications === []): ?>
            <div class="card card--empty">
                <i class="bi bi-journal-text card--empty__icon"></i>
                <p>Заявок пока нет.</p>
                <a class="btn btn--primary" href="<?= url('frontend/application_create.php') ?>">
                    <i class="bi bi-calendar-plus"></i> Записаться на приём
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($__applications as $__app): ?>
                <?php
                $__status    = (string) $__app['status'];
                $__id        = (int) $__app['id'];
                $__hasReview = $__app['review_id'] !== null;
                $__canReview = Application::canReview($__status) && !$__hasReview;
                $__canCancel = Application::canCancel($__status);
                ?>
                <article class="card card--application" data-reveal style="--reveal-index: <?= $__index++ ?>">
                    <div class="card--application__head">
                        <h2 class="card__title">
                            <i class="bi <?= e(animalIcon((string) $__app['animal_type'])) ?> card__title-icon"></i>
                            <?= e($__app['pet_name']) ?>
                            <span class="card__title-sub"><?= e(ucfirst((string) $__app['animal_type'])) ?></span>
                        </h2>
                        <span class="badge <?= e(statusBadgeClass($__status)) ?>">
                            <?= e($__status) ?>
                        </span>
                    </div>

                    <dl class="details">
                        <div class="details__row">
                            <dt class="details__key">
                                <i class="bi bi-hash"></i> Заявка
                            </dt>
                            <dd class="details__value">№<?= $__id ?></dd>
                        </div>
                        <div class="details__row">
                            <dt class="details__key">
                                <i class="bi <?= e(serviceIcon((string) $__app['service'])) ?>"></i> Услуга
                            </dt>
                            <dd class="details__value"><?= e(ucfirst((string) $__app['service'])) ?></dd>
                        </div>
                        <div class="details__row">
                            <dt class="details__key"><i class="bi bi-calendar-event"></i> Дата и время</dt>
                            <dd class="details__value">
                                <?= e(formatDate((string) $__app['appt_date'])) ?>, <?= e($__app['appt_time']) ?>
                            </dd>
                        </div>
                        <div class="details__row">
                            <dt class="details__key"><i class="bi bi-cash-stack"></i> Оплата</dt>
                            <dd class="details__value"><?= e($__app['payment_method']) ?></dd>
                        </div>
                    </dl>

                    <div class="card--application__actions">
                        <?php if ($__canCancel): ?>
                            <div class="confirm" data-confirm>
                                <form method="post" action="<?= url('backend/actions/application_cancel.php') ?>">
                                    <input type="hidden" name="application_id" value="<?= $__id ?>">

                                    <button type="submit" class="btn btn--sm btn--danger" data-confirm-open>
                                        <i class="bi bi-x-circle"></i> Отменить заявку
                                    </button>

                                    <span class="confirm__ask" data-confirm-ask hidden>
                                        Отменить заявку №<?= $__id ?>?
                                        <button type="submit" class="btn btn--sm btn--danger" data-confirm-accept>
                                            <i class="bi bi-check-lg"></i> Да, отменить
                                        </button>
                                        <button type="button" class="btn btn--sm btn--ghost" data-confirm-cancel>
                                            Оставить
                                        </button>
                                    </span>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($__hasReview): ?>
                        <div class="review-box">
                            <p class="review-box__label">
                                <i class="bi bi-chat-quote-fill"></i> Ваш отзыв
                            </p>
                            <div class="stars">
                                <?php for ($__s = 1; $__s <= 5; $__s++): ?>
                                    <span class="star<?= $__s <= (int) $__app['review_rating'] ? ' star--on' : '' ?>">&#9733;</span>
                                <?php endfor; ?>
                            </div>
                            <p class="review-box__text"><?= e((string) $__app['review_text']) ?></p>
                        </div>
                    <?php elseif ($__canReview): ?>
                        <form method="post" action="<?= url('backend/actions/review_create.php') ?>"
                              class="review-form" novalidate>
                            <input type="hidden" name="application_id" value="<?= $__id ?>">

                            <?php if (isset($__errors['review'])): ?>
                                <p class="field__error"><?= e($__errors['review']) ?></p>
                            <?php endif; ?>

                            <div class="review-form__head">
                                <i class="bi bi-pencil-square"></i> Приём завершён — оставьте отзыв
                            </div>

                            <div class="field">
                                <label class="field__label" for="review_<?= $__id ?>">
                                    Отзыв о работе клиники
                                </label>
                                <textarea class="field__input" id="review_<?= $__id ?>" name="review"
                                          rows="3" required minlength="3" maxlength="1000"
                                          placeholder="Как прошёл приём?"><?= e(oldValue($__old, 'review')) ?></textarea>
                            </div>

                            <div class="field">
                                <label class="field__label" for="rating_<?= $__id ?>">Оценка</label>
                                <select class="field__input" id="rating_<?= $__id ?>" name="rating">
                                    <option value="">Без оценки</option>
                                    <?php for ($__r = 5; $__r >= 1; $__r--): ?>
                                        <option value="<?= $__r ?>"
                                            <?= oldValue($__old, 'rating') === (string) $__r ? 'selected' : '' ?>>
                                            <?= $__r ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn--primary btn--sm">
                                <i class="bi bi-send"></i> Оставить отзыв
                            </button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>