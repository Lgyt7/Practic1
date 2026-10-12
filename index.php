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
    ['file' => 'slide-1.svg', 'title' => 'Забота о питомцах',   'text' => 'Врачи, которые умеют разговаривать с животными'],
    ['file' => 'slide-2.svg', 'title' => 'Онлайн-запись',       'text' => 'Выберите удобное время — без очередей и звонков'],
    ['file' => 'slide-3.svg', 'title' => 'Диагностика',         'text' => 'Современное оборудование и точные результаты'],
    ['file' => 'slide-4.svg', 'title' => 'Профилактика',        'text' => 'Прививки и осмотры, чтобы питомец был здоров'],
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
                         width="1600" height="700">
                    <div class="slider__caption">
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
                    <a class="btn btn--primary" href="<?= url('frontend/register.php') ?>">Создать пользователя</a>
                    <a class="btn btn--ghost" href="<?= url('frontend/login.php') ?>">Войти</a>
                </div>
            <?php elseif ($__user['role'] === 'admin'): ?>
                <h2 class="cta__title">Здравствуйте, <?= e($__user['full_name']) ?>!</h2>
                <p class="cta__text">Откройте панель администратора, чтобы подтвердить заявки.</p>
                <div class="cta__actions">
                    <a class="btn btn--primary" href="<?= url('frontend/admin.php') ?>">Панель администратора</a>
                </div>
            <?php else: ?>
                <h2 class="cta__title">Здравствуйте, <?= e($__user['full_name']) ?>!</h2>
                <p class="cta__text">Оформите новую заявку или посмотрите статус прошлых.</p>
                <div class="cta__actions">
                    <a class="btn btn--primary" href="<?= url('frontend/application_create.php') ?>">Записаться на приём</a>
                    <a class="btn btn--ghost" href="<?= url('frontend/applications.php') ?>">Мои заявки</a>
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
                    <h3 class="card__title">Опытные врачи</h3>
                    <p class="card__text">Специалисты с профильным образованием и постоянным повышением квалификации.</p>
                </article>
                <article class="card">
                    <h3 class="card__title">Удобная запись</h3>
                    <p class="card__text">Заявка отправляется напрямую администратору, статус виден в личном кабинете.</p>
                </article>
                <article class="card">
                    <h3 class="card__title">Полный спектр услуг</h3>
                    <p class="card__text">Консультации, прививки, стрижка, обработка когтей и послеоперационное наблюдение.</p>
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