<?php
declare(strict_types=1);

/**
 * frontend/applications.php
 * Личный кабинет владельца питомца: список своих заявок и отзывы.
 */

require_once __DIR__ . '/../backend/bootstrap.php';

Auth::requireLogin();

if (Auth::isAdmin()) {
    redirect('frontend/admin.php');
}

$__pageTitle = 'Мои заявки';
$__errors    = takeErrors();
$__old       = takeOld();

/** Заявки текущего пользователя — не чужие. */
$__applications = Application::forUser(Auth::id());

require __DIR__ . '/partials/header.php';
?>

<main class="page">
    <div class="container">
        <div class="page-head">
            <h1 class="page-title">Мои заявки</h1>
            <a class="btn btn--primary" href="<?= url('frontend/application_create.php') ?>">Новая заявка</a>
        </div>

        <?php /* Ошибки отправки форм (например, отказ в отзыве) — вне формы. */ ?>
        <?php foreach ($__errors as $__message): ?>
            <div class="alert alert--danger" data-toast>
                <?= e($__message) ?>
                <button type="button" class="alert__close" aria-label="Закрыть">&times;</button>
            </div>
        <?php endforeach; ?>

        <?php if ($__applications === []): ?>
            <div class="card card--empty">
                <p>Заявок пока нет.</p>
                <a class="btn btn--primary" href="<?= url('frontend/application_create.php') ?>">Записаться на приём</a>
            </div>
        <?php else: ?>
            <?php foreach ($__applications as $__app): ?>
                <?php $__canReview = Application::canReview($__app['status']) && !$__app['has_review']; ?>
                <article class="card card--application">
                    <div class="card--application__head">
                        <h2 class="card__title"><?= e($__app['pet_name']) ?> · <?= e($__app['animal_type']) ?></h2>
                        <span class="badge <?= e(statusBadgeClass($__app['status'])) ?>"><?= e($__app['status']) ?></span>
                    </div>

                    <dl class="details">
                        <div class="details__row">
                            <dt class="details__key">Услуга</dt>
                            <dd class="details__value"><?= e($__app['service']) ?></dd>
                        </div>
                        <div class="details__row">
                            <dt class="details__key">Дата и время</dt>
                            <dd class="details__value"><?= e($__app['appt_date']) ?>, <?= e($__app['appt_time']) ?></dd>
                        </div>
                        <div class="details__row">
                            <dt class="details__key">Оплата</dt>
                            <dd class="details__value"><?= e($__app['payment_method']) ?></dd>
                        </div>
                    </dl>

                    <?php $__review = Review::forApplication((int) $__app['id']); ?>
                    <?php if ($__review !== null): ?>
                        <div class="review-box">
                            <p class="review-box__label">Ваш отзыв</p>
                            <div class="stars">
                                <?php for ($__s = 1; $__s <= 5; $__s++): ?>
                                    <span class="star<?= $__s <= (int) $__review['rating'] ? ' star--on' : '' ?>">&#9733;</span>
                                <?php endfor; ?>
                            </div>
                            <p class="review-box__text"><?= e($__review['review']) ?></p>
                        </div>
                    <?php elseif ($__canReview): ?>
                        <form method="post" action="<?= url('backend/actions/review_create.php') ?>"
                              class="review-form" novalidate>
                            <input type="hidden" name="application_id" value="<?= (int) $__app['id'] ?>">

                            <?php if ($__errors !== [] && isset($__errors['review'])): ?>
                                <p class="field__error"><?= e($__errors['review']) ?></p>
                            <?php endif; ?>

                            <label class="field__label" for="review_<?= (int) $__app['id'] ?>">Отзыв о работе клиники</label>
                            <textarea class="field__input" id="review_<?= (int) $__app['id'] ?>" name="review"
                                      rows="3" required minlength="3" maxlength="1000"
                                      placeholder="Как прошёл приём?"><?= e(oldValue($__old, 'review')) ?></textarea>

                            <div class="field">
                                <label class="field__label" for="rating_<?= (int) $__app['id'] ?>">Оценка</label>
                                <select class="field__input" id="rating_<?= (int) $__app['id'] ?>" name="rating">
                                    <option value="">Без оценки</option>
                                    <?php for ($__r = 5; $__r >= 1; $__r--): ?>
                                        <option value="<?= $__r ?>"
                                            <?= oldValue($__old, 'rating') === (string) $__r ? 'selected' : '' ?>>
                                            <?= $__r ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn--primary btn--sm">Оставить отзыв</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>