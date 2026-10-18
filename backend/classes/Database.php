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

    /**
     * Подготовить и выполнить запрос.
     *
     * Значение null передаётся как SQL NULL, а не как пустая строка:
     * для этого у PHP есть отдельный тип PDO::PARAM_NULL, а PDO::PARAM_STR
     * молча превратил бы null в ''.
     *
     * @param string              $sql    запрос с плейсхолдерами
     * @param array<string,mixed> $params значения для подстановки
     * @param array<string,int>   $types  типы PDO::PARAM_* (нужны для LIMIT/OFFSET)
     */
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

    /** Выполнить подготовленный запрос и вернуть все строки. */
    public static function fetchAll(string $sql, array $params = [], array $types = []): array
    {
        return self::run($sql, $params, $types)->fetchAll();
    }

    /** Выполнить подготовленный запрос и вернуть одну строку. */
    public static function fetchOne(string $sql, array $params = [], array $types = []): ?array
    {
        $row = self::run($sql, $params, $types)->fetch();

        return $row === false ? null : $row;
    }

    /** Выполнить подготовленный запрос без возврата данных. */
    public static function execute(string $sql, array $params = [], array $types = []): int
    {
        return self::run($sql, $params, $types)->rowCount();
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