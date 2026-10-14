<?php
declare(strict_types=1);

/**
 * index.php
 * Главная страница портала — точка входа приложения.
 * Содержит слайдер из 4 изображений, преимущества и последние отзывы.
 *
 * Логика — в backend/, здесь только сборка страницы.
 */

require_once __DIR__ . '/backend/bootstrap.php';

$__pageTitle = 'Ветеринарная клиника';
$__user      = currentUser();
$__reviews   = Review::latest(3);
$__slides    = [
    ['file' => 'slide-1.jpg', 'icon' => 'bi-heart-pulse', 'title' => 'Врачи на приёме',    'text' => 'Опытные специалисты, которым доверяют питомцев'],
    ['file' => 'slide-2.jpg', 'icon' => 'bi-emoji-smile', 'title' => 'Довольные питомцы', 'text' => 'Забота и ласка — питомцы уходят с приёма спокойными'],
    ['file' => 'slide-3.jpg', 'icon' => 'bi-building',    'title' => 'Кабинет клиники',   'text' => 'Светлые кабинеты и современное оборудование'],
    ['file' => 'slide-4.jpg', 'icon' => 'bi-shield-fill-check', 'title' => 'Вакцинация',  'text' => 'Плановая защита от инфекций в удобное время'],
];

require __DIR__ . '/frontend/partials/header.php';
?>

<main>

    <!-- ================= Слайдер ================= -->
    <section class="slider" data-slider aria-roledescription="карусель" aria-label="Главные преимущества">
        <div class="slider__track" data-slider-track>
            <?php foreach ($__slides as $__i => $__slide): ?>
                <div class="slider__slide<?= $__i === 0 ? ' is-active' : '' ?>"
                     data-slide="<?= $__i ?>"
                     role="group"
                     aria-roledescription="слайд"
                     aria-label="Слайд <?= $__i + 1 ?> из <?= count($__slides) ?>">
                    <img class="slider__img"
                         src="<?= asset('img/' . $__slide['file']) ?>"
                         alt="<?= e($__slide['title']) ?>"
                         width="1200" height="675"
                         <?= $__i === 0 ? '' : 'loading="lazy"' ?> decoding="async">
                    <div class="slider__caption">
                        <span class="slider__icon"><i class="bi <?= e($__slide['icon']) ?>"></i></span>
                        <h2 class="slider__title"><?= e($__slide['title']) ?></h2>
                        <p class="slider__text"><?= e($__slide['text']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="slider__btn slider__btn--prev" type="button" data-slider-prev aria-label="Предыдущий слайд">&#10094;</button>
        <button class="slider__btn slider__btn--next" type="button" data-slider-next aria-label="Следующий слайд">&#10095;</button>

        <div class="slider__dots" data-slider-dots>
            <?php foreach ($__slides as $__i => $__slide): ?>
                <button class="slider__dot<?= $__i === 0 ? ' is-active' : '' ?>"
                        type="button"
                        data-slider-dot="<?= $__i ?>"
                        aria-label="Слайд <?= $__i + 1 ?>: <?= e($__slide['title']) ?>"></button>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ================= Действие ================= -->
    <section class="cta">
        <div class="container cta__inner">
            <?php if ($__user === null): ?>
                <h2 class="cta__title">Запишите питомца на приём онлайн</h2>
                <p class="cta__text">Регистрация займёт меньше минуты.</p>
                <div class="cta__actions">
                    <a class="btn btn--primary" href="<?= url('frontend/register.php') ?>">
                        <i class="bi bi-person-plus"></i> Создать пользователя
                    </a>
                    <a class="btn btn--ghost" href="<?= url('frontend/login.php') ?>">
                        <i class="bi bi-box-arrow-in-right"></i> Войти
                    </a>
                </div>
            <?php elseif ($__user['role'] === 'admin'): ?>
                <h2 class="cta__title">Здравствуйте, <?= e($__user['full_name']) ?>!</h2>
                <p class="cta__text">Откройте панель администратора, чтобы подтвердить заявки.</p>
                <div class="cta__actions">
                    <a class="btn btn--primary" href="<?= url('frontend/admin.php') ?>">
                        <i class="bi bi-clipboard2-pulse"></i> Панель администратора
                    </a>
                </div>
            <?php else: ?>
                <h2 class="cta__title">Здравствуйте, <?= e($__user['full_name']) ?>!</h2>
                <p class="cta__text">Оформите новую заявку или посмотрите статус прошлых.</p>
                <div class="cta__actions">
                    <a class="btn btn--primary" href="<?= url('frontend/application_create.php') ?>">
                        <i class="bi bi-calendar-plus"></i> Записаться на приём
                    </a>
                    <a class="btn btn--ghost" href="<?= url('frontend/applications.php') ?>">
                        <i class="bi bi-card-list"></i> Мои заявки
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= Преимущества ================= -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Почему выбирают нас</h2>
            <div class="features__grid">
                <article class="card">
                    <span class="card__icon card__icon--green"><i class="bi bi-person-badge"></i></span>
                    <h3 class="card__title">Опытные врачи</h3>
                    <p class="card__text">Специалисты с профильным образованием и постоянным повышением квалификации.</p>
                </article>
                <article class="card">
                    <span class="card__icon card__icon--blue"><i class="bi bi-calendar-check"></i></span>
                    <h3 class="card__title">Удобная запись</h3>
                    <p class="card__text">Заявка отправляется напрямую администратору, статус виден в личном кабинете.</p>
                </article>
                <article class="card">
                    <span class="card__icon card__icon--orange"><i class="bi bi-clipboard2-pulse"></i></span>
                    <h3 class="card__title">Полный спектр услуг</h3>
                    <p class="card__text">Осмотр, вакцинация, стрижка когтей и УЗИ — в одном расписании.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- ================= Отзывы ================= -->
    <?php if ($__reviews !== []): ?>
        <section class="reviews">
            <div class="container">
                <h2 class="section-title">Отзывы владельцев</h2>
                <div class="reviews__grid">
                    <?php foreach ($__reviews as $__review): ?>
                        <article class="card card--review">
                            <div class="stars" aria-label="Оценка <?= (int) $__review['rating'] ?> из 5">
                                <?php for ($__s = 1; $__s <= 5; $__s++): ?>
                                    <span class="star<?= $__s <= (int) $__review['rating'] ? ' star--on' : '' ?>">&#9733;</span>
                                <?php endfor; ?>
                            </div>
                            <p class="card__text"><?= e($__review['review']) ?></p>
                            <p class="card__meta">
                                <?= e($__review['full_name']) ?>, приём: <?= e($__review['service']) ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php require __DIR__ . '/frontend/partials/footer.php'; ?>