<?php
declare(strict_types=1);

final class Schedule
{

    public const STEP_MINUTES = 30;

    public static function open(): string
    {
        return CLINIC_OPEN;
    }

    public static function close(): string
    {
        return CLINIC_CLOSE;
    }

    public static function hint(): string
    {
        return sprintf('Клиника работает с %s до %s.', self::open(), self::close());
    }

    public static function isValidTime(string $time): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    public static function isOpenTime(string $time): bool
    {
        return self::isValidTime($time) && $time >= self::open() && $time <= self::close();
    }

    public static function outsideReason(): string
    {
        return self::hint() . ' Выберите время в этом диапазоне.';
    }

    private function __construct()
    {
    }
}
