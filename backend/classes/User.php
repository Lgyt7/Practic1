<?php
declare(strict_types=1);

final class User
{

    public static function create(string $login, string $password, string $fullName, string $phone, string $email): int
    {
        return Database::insert(
            'INSERT INTO users (login, password, full_name, phone, email, role)
             VALUES (:login, :password, :full_name, :phone, :email, :role)',
            [
                'login'      => $login,
                'password'   => password_hash($password, PASSWORD_DEFAULT),
                'full_name'  => $fullName,
                'phone'      => $phone,
                'email'      => $email,
                'role'       => 'user',
            ]
        );
    }

    public static function findByLogin(string $login): ?array
    {
        return Database::fetchOne(
            'SELECT id, login, password, full_name, phone, email, role
               FROM users
              WHERE login = :login',
            ['login' => $login]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT id, login, password, full_name, phone, email, role
               FROM users
              WHERE id = :id',
            ['id' => $id]
        );
    }

    public static function loginExists(string $login): bool
    {
        $row = Database::fetchOne(
            'SELECT 1 FROM users WHERE login = :login',
            ['login' => $login]
        );

        return $row !== null;
    }

    public static function emailExists(string $email): bool
    {
        $row = Database::fetchOne(
            'SELECT 1 FROM users WHERE email = :email',
            ['email' => $email]
        );

        return $row !== null;
    }

    public static function count(): int
    {
        $row = Database::fetchOne('SELECT COUNT(*) AS n FROM users');

        return (int) ($row['n'] ?? 0);
    }

    private function __construct()
    {
    }
}
