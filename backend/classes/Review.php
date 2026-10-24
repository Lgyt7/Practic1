<?php
declare(strict_types=1);

final class Review
{

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

    public static function exists(int $applicationId): bool
    {
        return Database::fetchOne(
            'SELECT 1 FROM reviews WHERE application_id = :application_id',
            ['application_id' => $applicationId]
        ) !== null;
    }

    public static function latest(int $limit = 5): array
    {
        return Database::fetchAll(
            'SELECT r.*, u.full_name, p.name AS pet_name, a.service
               FROM reviews r
               JOIN users u       ON u.id = r.user_id
               JOIN applications a ON a.id = r.application_id
               JOIN pets p        ON p.id = a.pet_id
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
