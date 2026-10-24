<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME
            );

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            self::$instance->exec("SET NAMES 'UTF8'");
        }

        return self::$instance;
    }

    private static function run(string $sql, array $params, array $types = []): PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            if ($value === null) {
                $stmt->bindValue(':' . $key, null, PDO::PARAM_NULL);
                continue;
            }

            $stmt->bindValue(':' . $key, $value, $types[$key] ?? PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = [], array $types = []): array
    {
        return self::run($sql, $params, $types)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = [], array $types = []): ?array
    {
        $row = self::run($sql, $params, $types)->fetch();

        return $row === false ? null : $row;
    }

    public static function execute(string $sql, array $params = [], array $types = []): int
    {
        return self::run($sql, $params, $types)->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::execute($sql, $params);

        return (int) self::getConnection()->lastInsertId();
    }

    private function __construct()
    {
    }
}
