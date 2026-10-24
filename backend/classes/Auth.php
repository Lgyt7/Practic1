<?php
declare(strict_types=1);

final class Auth
{

    public static function attempt(string $login, string $password): bool
    {
        $user = User::findByLogin($login);

        if ($user === null) {

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

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['user_id'] : null;
    }

    public static function isAdmin(): bool
    {
        $user = currentUser();

        return $user !== null && $user['role'] === 'admin';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('danger', 'Требуется авторизация.');
            redirect('frontend/login.php');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            http_response_code(403);
            exit('Доступ запрещён: требуется роль администратора.');
        }
    }
}
