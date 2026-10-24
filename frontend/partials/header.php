<?php
declare(strict_types=1);

$__flashes = flashTake();
$__user    = currentUser();
$__here    = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> — <?= e($__pageTitle ?? APP_TAGLINE) ?></title>
    <link rel="icon" href="<?= asset('img/logo.svg') ?>">

    <script>document.documentElement.className += ' js';</script>
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= url('index.php') ?>">
            <img src="<?= asset('img/logo.svg') ?>" alt="Логотип" class="logo__img" width="44" height="44">
            <span class="logo__text"><?= e(APP_NAME) ?></span>
        </a>

        <button class="burger" type="button" aria-label="Меню" aria-expanded="false" data-burger>
            <span></span><span></span><span></span>
        </button>

        <nav class="nav" data-nav>
            <a class="nav__link<?= $__here === 'index.php' ? ' nav__link--active' : '' ?>"
               href="<?= url('index.php') ?>"><i class="bi bi-house"></i> Главная</a>

            <?php if ($__user !== null && $__user['role'] === 'user'): ?>
                <a class="nav__link<?= $__here === 'applications.php' ? ' nav__link--active' : '' ?>"
                   href="<?= url('frontend/applications.php') ?>">
                    <i class="bi bi-card-list"></i> Мои заявки
                </a>
                <a class="nav__link<?= $__here === 'pets.php' ? ' nav__link--active' : '' ?>"
                   href="<?= url('frontend/pets.php') ?>">
                    <i class="bi bi-heart"></i> Питомцы
                </a>
                <a class="nav__link<?= $__here === 'application_create.php' ? ' nav__link--active' : '' ?>"
                   href="<?= url('frontend/application_create.php') ?>">
                    <i class="bi bi-calendar-plus"></i> Записаться
                </a>
            <?php elseif ($__user !== null && $__user['role'] === 'admin'): ?>
                <a class="nav__link<?= $__here === 'admin.php' ? ' nav__link--active' : '' ?>"
                   href="<?= url('frontend/admin.php') ?>">
                    <i class="bi bi-clipboard2-pulse"></i> Админ-панель
                </a>
            <?php endif; ?>

            <?php if ($__user === null): ?>
                <a class="nav__link<?= $__here === 'login.php' ? ' nav__link--active' : '' ?>"
                   href="<?= url('frontend/login.php') ?>"><i class="bi bi-box-arrow-in-right"></i> Вход</a>
                <a class="btn btn--sm btn--primary<?= $__here === 'register.php' ? ' btn--active' : '' ?>"
                   href="<?= url('frontend/register.php') ?>"><i class="bi bi-person-plus"></i> Регистрация</a>
            <?php else: ?>
                <span class="nav__user" title="<?= e($__user['full_name']) ?>">
                    <i class="bi bi-person-circle"></i> <?= e($__user['login']) ?>
                </span>
                <form method="post" action="<?= url('backend/actions/logout.php') ?>" class="nav__form">
                    <button type="submit" class="btn btn--sm btn--ghost">
                        <i class="bi bi-box-arrow-right"></i> Выйти
                    </button>
                </form>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="notifications" data-toasts>
    <?php foreach ($__flashes as $__flash): ?>
        <?php
        $__alertType = $__flash['type'];
        $__alertText = $__flash['text'];
        require __DIR__ . '/alert.php';
        ?>
    <?php endforeach; ?>
</div>
