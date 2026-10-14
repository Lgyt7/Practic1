<?php
declare(strict_types=1);

/**
 * Модель заявки на приём.
 * Жизненный цикл: «Новая» → «Подтверждена» → «Приём завершён» | «Отменена».
 */
final class Application
{
    /**
     * Символ экранирования для шаблонов LIKE.
     *
     * Именно «!», а не «\»: pdo_pgsql разбирает именованные плейсхолдеры
     * самостоятельно, и обратный слэш внутри строки сбивает этот разбор —
     * все плейсхолдеры после ESCAPE '\' теряются («Invalid parameter number»).
     */
    public const LIKE_ESCAPE = '!';

    /** Экранирование строки для сравнения по шаблону LIKE. */
    public static function escapeLike(string $value): string
    {
        return str_replace(
            [self::LIKE_ESCAPE, '%', '_'],
            [self::LIKE_ESCAPE . self::LIKE_ESCAPE, self::LIKE_ESCAPE . '%', self::LIKE_ESCAPE . '_'],
            $value
        );
    }

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

    /**
     * Все заявки системы с фильтрами, поиском и пагинацией.
     *
     * @param string $status  фильтр по статусу ('' — без фильтра)
     * @param string $service фильтр по услуге  ('' — без фильтра)
     * @param string $query   поиск по кличке питомца или ФИО владельца
     * @param int    $page    номер страницы, начиная с 1
     * @return array{rows: array, total: int, pages: int, page: int}
     */
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
            // Имена плейсхолдеров разные: в нативных препаратах PDO
            // один и тот же именованный параметр нельзя использовать дважды.
            //
            // ESCAPE '!' — чтобы «%» и «_» в поисковой строке искались
            // как обычные символы, а не как служебные шаблоны LIKE.
            $where[] = "(a.pet_name ILIKE :query_pet ESCAPE '" . self::LIKE_ESCAPE . "'"
                . " OR u.full_name ILIKE :query_owner ESCAPE '" . self::LIKE_ESCAPE . "')";

            $like = '%' . self::escapeLike($query) . '%';

            $params['query_pet']   = $like;
            $params['query_owner'] = $like;
        }

        $clause = $where === [] ? '' : (' WHERE ' . implode(' AND ', $where));

        // Общее количество записей — для расчёта числа страниц.
        $total = (int) (Database::fetchOne(
            'SELECT COUNT(*) AS n FROM applications a JOIN users u ON u.id = a.user_id' . $clause,
            $params
        )['n'] ?? 0);

        $perPage = max(1, ADMIN_PAGE_SIZE);
        $pages   = max(1, (int) ceil($total / $perPage));
        $page    = min(max(1, $page), $pages);
        $offset  = ($page - 1) * $perPage;

        $sql = <<<'SQL'
            SELECT a.*,
                   u.full_name,
                   u.login,
                   u.phone,
                   u.email,
                   (SELECT COUNT(*) FROM reviews r WHERE r.application_id = a.id) AS has_review
              FROM applications a
              JOIN users u ON u.id = a.user_id
            SQL;

        $sql .= $clause;

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