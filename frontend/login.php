<?php
declare(strict_types=1);

/**
 * frontend/login.php
 * Страница авторизации пользователя.
 */

require_once __DIR__ . '/../backend/bootstrap.php';

if (Auth::check()) {
    redirect(Auth::isAdmin() ? 'frontend/admin.php' : 'frontend/applications.php');
}

$__pageTitle = 'Авторизация';
$__errors    = takeErrors();
$__old       = takeOld();

$error = static fn (string $field): ?string => $__errors[$field] ?? null;

require __DIR__ . '/partials/header.php';
?>

<main class="page">
    <div class="container">
        <div class="card card--form">
            <h1 class="page-title">
                <i class="bi bi-box-arrow-in-right page-title__icon"></i>Вход
            </h1>
            <p class="page-subtitle">Введите логин и пароль зарегистрированного пользователя.</p>

            <form method="post" action="<?= url('backend/actions/login.php') ?>" novalidate data-validate>

                <div class="field<?= $error('login') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="login">Логин</label>
                    <span class="field__control">
                        <i class="bi bi-person field__control-icon"></i>
                        <input class="field__input field__input--icon" type="text" id="login" name="login"
                               value="<?= e(oldValue($__old, 'login')) ?>" required autocomplete="username">
                    </span>
                    <?php if ($__e = $error('login')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $error('password') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="password">Пароль</label>
                    <span class="field__control">
                        <i class="bi bi-lock field__control-icon"></i>
                        <input class="field__input field__input--icon" type="password" id="password" name="password"
                               required autocomplete="current-password">
                    </span>
                    <?php if ($__e = $error('password')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn--primary btn--block">
                    <i class="bi bi-box-arrow-in-right"></i> Войти
                </button>
            </form>

            <p class="form-footer">
                Еще не зарегистрированы? <a href="<?= url('frontend/register.php') ?>">Регистрация</a>
            </p>
        </div>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>