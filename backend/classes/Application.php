<?php
declare(strict_types=1);

/**
 * Модель заявки на приём.
 * Жизненный цикл: «Новая» → «Подтверждена» → «Приём завершён» | «Отменена».
 */
final class Application
{
    /** Создание заявки. Статус автоматически «Новая». */
    public static function create(
        int $userId,
        string $petName,
        string $animalType,
        string $service,
        string $apptDate,
        string $apptTime,
        string $paymentMethod
    ): int {
        return Database::insert(
            'INSERT INTO applications
                (user_id, pet_name, animal_type, service, appt_date, appt_time, payment_method, status)
             VALUES
                (:user_id, :pet_name, :animal_type, :service, :appt_date, :appt_time, :payment_method, :status)',
            [
                'user_id'        => $userId,
                'pet_name'       => $petName,
                'animal_type'    => $animalType,
                'service'        => $service,
                'appt_date'      => $apptDate,
                'appt_time'      => $apptTime,
                'payment_method' => $paymentMethod,
                'status'         => STATUS_NEW,
            ]
        );
    }

    /** Все заявки конкретного пользователя. */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT a.*,
                    (SELECT COUNT(*) FROM reviews r WHERE r.application_id = a.id) AS has_review
               FROM applications a
              WHERE a.user_id = :user_id
              ORDER BY a.appt_date DESC, a.appt_time DESC, a.id DESC',
            ['user_id' => $userId]
        );
    }

    /** Все заявки системы (панель администратора). */
    public static function all(): array
    {
        return Database::fetchAll(
            "SELECT a.*,
                    u.full_name,
                    u.login,
                    u.phone,
                    u.email,
                    (SELECT COUNT(*) FROM reviews r WHERE r.application_id = a.id) AS has_review
               FROM applications a
               JOIN users u ON u.id = a.user_id
              ORDER BY
                    CASE a.status
                        WHEN 'Новая' THEN 1
                        WHEN 'Подтверждена' THEN 2
                        WHEN 'Приём завершён' THEN 3
                        ELSE 4
                    END,
                    a.appt_date DESC,
                    a.id DESC"
        );
    }

    /** Заявка по идентификатору. */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT a.*, u.full_name, u.login
               FROM applications a
               JOIN users u ON u.id = a.user_id
              WHERE a.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Смена статуса с проверкой допустимости перехода.
     * Возвращает текст ошибки либо null при успехе.
     */
    public static function changeStatus(int $id, string $status): ?string
    {
        if (!in_array($status, [STATUS_NEW, STATUS_CONFIRMED, STATUS_DONE, STATUS_CANCELLED], true)) {
            return 'Неизвестный статус заявки.';
        }

        $application = self::findById($id);

        if ($application === null) {
            return 'Заявка не найдена.';
        }

        $allowed = STATUS_TRANSITIONS[$application['status']] ?? [];

        if (!in_array($status, $allowed, true)) {
            return sprintf(
                'Недопустимый переход: «%s» → «%s».',
                $application['status'],
                $status
            );
        }

        Database::execute(
            'UPDATE applications SET status = :status WHERE id = :id',
            ['status' => $status, 'id' => $id]
        );

        return null;
    }

    /** Статусы, доступные из текущего. */
    public static function availableTransitions(string $current): array
    {
        return STATUS_TRANSITIONS[$current] ?? [];
    }

    /** Разрешено ли оставить отзыв по заявке. */
    public static function canReview(string $status): bool
    {
        return $status === STATUS_FOR_REVIEW;
    }

    /** Счётчики заявок по статусам (панель администратора). */
    public static function countByStatus(): array
    {
        $counts = array_fill_keys(
            [STATUS_NEW, STATUS_CONFIRMED, STATUS_DONE, STATUS_CANCELLED],
            0
        );

        foreach (Database::fetchAll('SELECT status, COUNT(*) AS n FROM applications GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        return $counts;
    }

    private function __construct()
    {
    }
}