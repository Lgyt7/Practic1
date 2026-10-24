<?php
declare(strict_types=1);

final class Application
{

    public const LIKE_ESCAPE = '!';

    public static function escapeLike(string $value): string
    {
        return str_replace(
            [self::LIKE_ESCAPE, '%', '_'],
            [self::LIKE_ESCAPE . self::LIKE_ESCAPE, self::LIKE_ESCAPE . '%', self::LIKE_ESCAPE . '_'],
            $value
        );
    }

    public static function create(
        int $userId,
        int $petId,
        string $service,
        string $apptDate,
        string $apptTime,
        string $paymentMethod
    ): int {
        return Database::insert(
            'INSERT INTO applications
                (user_id, pet_id, service, appt_date, appt_time, payment_method, status)
             VALUES
                (:user_id, :pet_id, :service, :appt_date, :appt_time, :payment_method, :status)',
            [
                'user_id'        => $userId,
                'pet_id'         => $petId,
                'service'        => $service,
                'appt_date'      => $apptDate,
                'appt_time'      => $apptTime,
                'payment_method' => $paymentMethod,
                'status'         => STATUS_NEW,
            ]
        );
    }

    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT a.*,
                    p.name        AS pet_name,
                    p.animal_type,
                    p.breed,
                    r.id          AS review_id,
                    r.review      AS review_text,
                    r.rating      AS review_rating
               FROM applications a
               JOIN pets p        ON p.id = a.pet_id
               LEFT JOIN reviews r ON r.application_id = a.id
              WHERE a.user_id = :user_id
              ORDER BY a.appt_date DESC, a.appt_time DESC, a.id DESC',
            ['user_id' => $userId]
        );
    }

    public static function search(
        string $status = '',
        string $service = '',
        string $query = '',
        int $page = 1
    ): array {
        $where  = [];
        $params = [];

        if ($status !== '') {
            $where[]  = 'a.status = :status';
            $params['status'] = $status;
        }

        if ($service !== '') {
            $where[]  = 'a.service = :service';
            $params['service'] = $service;
        }

        if ($query !== '') {

            $where[] = "(p.name ILIKE :query_pet ESCAPE '" . self::LIKE_ESCAPE . "'"
                . " OR u.full_name ILIKE :query_owner ESCAPE '" . self::LIKE_ESCAPE . "')";

            $like = '%' . self::escapeLike($query) . '%';

            $params['query_pet']   = $like;
            $params['query_owner'] = $like;
        }

        $clause = $where === [] ? '' : (' WHERE ' . implode(' AND ', $where));

        $from = ' FROM applications a
                  JOIN users u ON u.id = a.user_id
                  JOIN pets p  ON p.id = a.pet_id';

        $total = (int) (Database::fetchOne('SELECT COUNT(*) AS n' . $from . $clause, $params)['n'] ?? 0);

        $perPage = max(1, ADMIN_PAGE_SIZE);
        $pages   = max(1, (int) ceil($total / $perPage));
        $page    = min(max(1, $page), $pages);
        $offset  = ($page - 1) * $perPage;

        $sql = <<<'SQL'
            SELECT a.*,
                   p.name        AS pet_name,
                   p.animal_type,
                   p.breed,
                   u.full_name,
                   u.login,
                   u.phone,
                   u.email,
                   (SELECT COUNT(*) FROM reviews r WHERE r.application_id = a.id) AS has_review
            SQL;

        $sql .= $from . $clause;

        $sql .= <<<'SQL'

              ORDER BY
                    CASE a.status
                        WHEN 'Новая' THEN 1
                        WHEN 'Подтверждена' THEN 2
                        WHEN 'Приём завершён' THEN 3
                        ELSE 4
                    END,
                    a.appt_date DESC,
                    a.id DESC
             LIMIT :limit OFFSET :offset
            SQL;

        $rows = Database::fetchAll(
            $sql,
            $params + ['limit' => $perPage, 'offset' => $offset],
            ['limit' => PDO::PARAM_INT, 'offset' => PDO::PARAM_INT]
        );

        return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT a.*, p.name AS pet_name, p.animal_type, u.full_name, u.login
               FROM applications a
               JOIN pets p        ON p.id = a.pet_id
               JOIN users u       ON u.id = a.user_id
              WHERE a.id = :id',
            ['id' => $id]
        );
    }

    public static function changeStatus(int $id, string $status): ?string
    {
        if (!in_array($status, STATUSES, true)) {
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

    public static function cancelByUser(int $id, int $userId): ?string
    {
        $application = Database::fetchOne(
            'SELECT status, user_id FROM applications WHERE id = :id',
            ['id' => $id]
        );

        if ($application === null || (int) $application['user_id'] !== $userId) {
            return 'Заявка не найдена.';
        }

        if ($application['status'] !== STATUS_NEW) {
            return sprintf(
                'Отменить можно только заявку со статусом «%s». Текущий статус — «%s».',
                STATUS_NEW,
                $application['status']
            );
        }

        Database::execute(
            'UPDATE applications SET status = :status WHERE id = :id AND status = :current',
            ['status' => STATUS_CANCELLED, 'id' => $id, 'current' => STATUS_NEW]
        );

        return null;
    }

    public static function availableTransitions(string $current): array
    {
        return STATUS_TRANSITIONS[$current] ?? [];
    }

    public static function canReview(string $status): bool
    {
        return $status === STATUS_FOR_REVIEW;
    }

    public static function canCancel(string $status): bool
    {
        return $status === STATUS_NEW;
    }

    public static function countByStatus(): array
    {
        $counts = array_fill_keys(STATUSES, 0);

        foreach (Database::fetchAll('SELECT status, COUNT(*) AS n FROM applications GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        return $counts;
    }

    private function __construct()
    {
    }
}
