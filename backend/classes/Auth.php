<?php
declare(strict_types=1);

/**
 * Аутентификация и авторизация.
 * Хранит в сессии идентификатор пользователя и его роль.
 */
final class Auth
{
    /** Попытка входа по логину и паролю. */
    public static function attempt(string $login, string $password): bool
    {
        $user = User::findByLogin($login);

        if ($user === null) {
            // Считаем хеш, чтобы время ответа не зависело от наличия логина.
            password_verify($password, '$2y$10$usesomesillystringfoequxqxqxqxqxqxqxqxqxqxqxqxqxqxqxqxqxqxu');

            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        return true;
    }

    /** Выход из системы. */
    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    /** Авторизован ли пользователь. */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }

    /** Идентификатор текущего пользователя. */
    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['user_id'] : null;
    }

    /** Является ли текущий пользователь администратором. */
    public static function isAdmin(): bool
    {
        $user = currentUser();

        return $user !== null && $user['role'] === 'admin';
    }

    /** Требовать авторизации. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('danger', 'Требуется авторизация.');
            redirect('frontend/login.php');
        }
    }

    /** Требовать роль администратора. */
    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            http_response_code(403);
            exit('Доступ запрещён: требуется роль администратора.');
        }
    }
}