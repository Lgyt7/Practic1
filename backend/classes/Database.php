<?php
declare(strict_types=1);

/**
 * Класс-обёртка над PDO.
 * Реализует паттерн Singleton: одно подключение на весь запрос.
 */
final class Database
{
    private static ?PDO $instance = null;

    /** Подключение к PostgreSQL (создаётся при первом обращении). */
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

    /** Выполнить подготовленный запрос и вернуть все строки. */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** Выполнить подготовленный запрос и вернуть одну строку. */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Выполнить подготовленный запрос без возврата данных. */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Выполнить INSERT и вернуть id новой записи.
     * Порядок вставки таблиц в dump.sql совпадает с этим порядком,
     * поэтому lastInsertId() без имени последовательности достаточно.
     */
    public static function insert(string $sql, array $params = []): int
    {
        self::execute($sql, $params);

        return (int) self::getConnection()->lastInsertId();
    }

    private function __construct()
    {
    }
}