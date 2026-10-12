<?php
declare(strict_types=1);

/**
 * frontend/register.php
 * Страница регистрации нового пользователя.
 */

require_once __DIR__ . '/../backend/bootstrap.php';

if (Auth::check()) {
    redirect(Auth::isAdmin() ? 'frontend/admin.php' : 'frontend/applications.php');
}

$__pageTitle = 'Регистрация';
$__errors    = takeErrors();
$__old       = takeOld();

/** Значение поля: введённое пользователем, иначе пустое. */
$value = static fn (string $field): string => oldValue($__old, $field);

/** Ошибка поля, если она есть. */
$error = static function (string $field) use ($__errors): ?string {
    return $__errors[$field] ?? null;
};

require __DIR__ . '/partials/header.php';
?>

<main class="page">
    <div class="container">
        <div class="card card--form">
            <h1 class="page-title">Регистрация</h1>
            <p class="page-subtitle">Все поля обязательны для заполнения.</p>

            <form method="post" action="<?= url('backend/actions/register.php') ?>" novalidate data-validate>

                <div class="field<?= $error('login') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="login">Логин</label>
                    <input class="field__input" type="text" id="login" name="login"
                           value="<?= e($value('login')) ?>" required minlength="6"
                           pattern="[A-Za-z0-9]{6,}"
                           placeholder="Не менее 6 латинских букв и цифр">
                    <p class="field__hint">Латиница и цифры, не менее 6 символов.</p>
                    <?php if ($__e = $error('login')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $error('full_name') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="full_name">ФИО</label>
                    <input class="field__input" type="text" id="full_name" name="full_name"
                           value="<?= e($value('full_name')) ?>" required
                           placeholder="Иванов Иван Иванович">
                    <p class="field__hint">Только кириллица и пробелы.</p>
                    <?php if ($__e = $error('full_name')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $error('phone') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="phone">Телефон</label>
                    <input class="field__input" type="text" id="phone" name="phone"
                           value="<?= e($value('phone')) ?>" required
                           placeholder="+7(999)123-45-67">
                    <p class="field__hint">Формат: +7(XXX)XXX-XX-XX</p>
                    <?php if ($__e = $error('phone')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $error('email') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="email">Электронная почта</label>
                    <input class="field__input" type="email" id="email" name="email"
                           value="<?= e($value('email')) ?>" required
                           placeholder="ivanov@example.com">
                    <?php if ($__e = $error('email')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $error('password') ? ' field--invalid' : '' ?>">
                    <label class="field__label" for="password">Пароль</label>
                    <input class="field__input" type="password" id="password" name="password"
                           required minlength="6" placeholder="Не менее 6 символов">
                    <p class="field__hint">Не короче 6 символов.</p>
                    <?php if ($__e = $error('password')): ?>
                        <p class="field__error"><?= e($__e) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn--primary btn--block">Создать пользователя</button>
            </form>

            <p class="form-footer">
                Уже зарегистрированы? <a href="<?= url('frontend/login.php') ?>">Войти</a>
            </p>
        </div>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>