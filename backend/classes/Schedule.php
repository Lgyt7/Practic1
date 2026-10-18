<?php
declare(strict_types=1);

/**
 * Режим работы клиники.
 *
 * Единственный источник правды о часах приёма: одинаковые правила
 * используются валидатором на сервере и атрибутами min/max/step
 * в форме записи. Дублировать «09:00–20:00» в разных файлах не нужно —
 * при изменении CLINIC_OPEN/CLINIC_CLOSE в config.php меняется всё сразу.
 */
final class Schedule
{
    /** Шаг сетки приёма, минут. Поле time получает step — браузер
     *  не даст выбрать 10:07, а сервер всё равно проверяет значение. */
    public const STEP_MINUTES = 30;

    /** Открытие клиники, ЧЧ:ММ. */
    public static function open(): string
    {
        return CLINIC_OPEN;
    }

    /** Закрытие клиники, ЧЧ:ММ. */
    public static function close(): string
    {
        return CLINIC_CLOSE;
    }

    /** Пояснение для пользователя: «Клиника работает с 09:00 до 20:00». */
    public static function hint(): string
    {
        return sprintf('Клиника работает с %s до %s.', self::open(), self::close());
    }

    /** Формат времени: ЧЧ:ММ, часы 00–23. */
    public static function isValidTime(string $time): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    /** Попадает ли время в часы работы клиники. */
    public static function isOpenTime(string $time): bool
    {
        return self::isValidTime($time) && $time >= self::open() && $time <= self::close();
    }

    /** Причина отказа в записи на это время — текст ошибки. */
    public static function outsideReason(): string
    {
        return self::hint() . ' Выберите время в этом диапазоне.';
    }

    private function __construct()
    {
    }
}