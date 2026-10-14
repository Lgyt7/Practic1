<?php
declare(strict_types=1);

/**
 * Модель отзыва о работе клиники.
 * Отзыв может быть оставлен один раз и только по завершённому приёму.
 */
final class Review
{
    /** Создание отзыва. Возвращает id. */
    public static function create(int $applicationId, int $userId, string $text, ?int $rating): int
    {
        return Database::insert(
            'INSERT INTO reviews (application_id, user_id, review, rating)
             VALUES (:application_id, :user_id, :review, :rating)',
            [
                'application_id' => $applicationId,
                'user_id'        => $userId,
                'review'         => $text,
                'rating'         => $rating,
            ]
        );
    }

    /** Отзывы по конкретной заявке. */
    public static function forApplication(int $applicationId): ?array
    {
        return Database::fetchOne(
            'SELECT r.*, u.full_name
               FROM reviews r
               JOIN users u ON u.id = r.user_id
              WHERE r.application_id = :application_id',
            ['application_id' => $applicationId]
        );
    }

    /** Есть ли уже отзыв по заявке. */
    public static function exists(int $applicationId): bool
    {
        return Database::fetchOne(
            'SELECT 1 FROM reviews WHERE application_id = :application_id',
            ['application_id' => $applicationId]
        ) !== null;
    }

    /** Последние отзывы (главная страница / панель администратора). */
    public static function latest(int $limit = 5): array
    {
        return Database::fetchAll(
            'SELECT r.*, u.full_name, a.pet_name, a.service
               FROM reviews r
               JOIN users u ON u.id = r.user_id
               JOIN applications a ON a.id = r.application_id
              ORDER BY r.created_at DESC
              LIMIT :limit',
            ['limit' => $limit],
            ['limit' => PDO::PARAM_INT]
        );
    }

    private function __construct()
    {
    }
}